from flask import Flask, request, jsonify
from transformers import CLIPProcessor, CLIPModel
import torch
from PIL import Image
import os
import time

app = Flask(__name__)

# =========================
# Load FashionCLIP
# =========================
model_name = "patrickjohncyh/fashion-clip"
model = CLIPModel.from_pretrained(model_name)
processor = CLIPProcessor.from_pretrained(model_name)

UPLOAD_FOLDER = "../uploads"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# =========================
# Wardrobe Categories & Types
# =========================
CATEGORIES = [
    "suit", "shirt", "t-shirt", "blouse", "jacket", "blazer", "coat", "vest",
    "hoodie", "sweater", "cardigan", "tank-top", "crop-top", "polo",
    "pants", "jeans", "shorts", "skirt", "trousers", "leggings", "cargo-pants",
    "dress", "gown", "jumpsuit", "romper", "overalls", "bra-panty-set", "swimsuit"
]

CATEGORY_TO_TYPE = {
    "suit": "upper", "shirt": "upper", "t-shirt": "upper", "blouse": "upper",
    "jacket": "upper", "blazer": "upper", "coat": "upper", "vest": "upper",
    "hoodie": "upper", "sweater": "upper", "cardigan": "upper", "tank-top": "upper",
    "crop-top": "upper", "polo": "upper", "floral-polo": "upper", "barong": "upper",
    "pants": "lower", "jeans": "lower", "shorts": "lower", "skirt": "lower",
    "trousers": "lower", "leggings": "lower", "cargo-pants": "lower", "swim-trunks": "lower",
    "dress": "full-body", "gown": "full-body", "jumpsuit": "full-body",
    "romper": "full-body", "overalls": "full-body", "bra-panty-set": "full-body",
    "swimsuit": "full-body"
}

FULL_BODY_CATEGORIES = ["dress", "gown", "jumpsuit", "romper", "overalls", "bra-panty-set", "swimsuit"]

# =========================
# Event Presets
# =========================
EVENT_PRESETS = {
    "birthday": "Casual yet festive outfits suitable for photos, social gatherings, and dancing.",
    "wedding": "Elegant and sophisticated wedding attire. Prioritize gowns, dresses, suits, and Barong Tagalog.",
    "beach_party": "Relaxed, vibrant beach wear including swimsuits, bra-panty sets, floral dresses, shorts, and swim trunks."
}

# =========================
# Event-specific category rules
# =========================
EVENT_CATEGORY_RULES = {
    "wedding": ["suit", "shirt", "blouse", "jacket", "blazer", "coat", "vest",
                "pants", "jeans", "trousers", "dress", "gown", "jumpsuit", "romper", "overalls", "cardigan", "polo"],
    "birthday": CATEGORIES,
    "beach_party": ["t-shirt", "shorts", "dress", "swimsuit", "bra-panty-set", "floral-polo", "tank-top", "skirt", "swim-trunks"]
}

# =========================
# Style Blacklists
# =========================
STYLE_BLACKLIST = {
    "masculine": ["crop-top", "bra-panty-set", "swimsuit", "romper", "jumpsuit","dress", "gown", "skirt", "leggings","tank-top","blouse","cardigan"],
    "feminine": ["suit", "shirt", "t-shirt", "jacket", "blazer", "coat", "vest", "hoodie", "sweater","pants","jeans","shorts","trousers","cargo-pants","swim-trunks","barong"],
    "androgynous": [],
    "gender_neutral": []
}

GLOBAL_BLACKLIST = ["bra-panty-set", "swimsuit"]

# =========================
# Misclassification Corrections
# =========================
MISCLASS_CORRECTIONS = {
    "suit": ["bra-panty-set", "swimsuit"],
    "skirt": ["gown", "dress"],
    "shirt": ["tank-top","bra-panty-set", "swimsuit"],
    "t-shirt": ["tank-top","bra-panty-set", "swimsuit"],
    "blouse": ["tank-top","bra-panty-set", "swimsuit"],
    "crop-top": ["bra-panty-set", "swimsuit"]
}
# =========================
# Style-aware look-alike corrections
# =========================
LOOKALIKE_MAP = {
    "feminine": {
        "cardigan": "vest",
        "jacket": "blazer",
        "tank-top": "blouse",
        "floral-polo": "polo",
        "polo": "suit",
        "shorts":"pants"  # <-- fix for misclassified suit
    },
    "masculine": {
        "vest": "suit",
        "blazer": "jacket",
        "t-shirt": "shirt",
        "shorts":"pants",
        "shirt": "crop-top"  # <-- fix for misclassified bra-panty-set
    },
    "androgynous": {},
    "gender_neutral": {}
}


# =========================
# Helper Functions
# =========================
def get_allowed_categories(style, event):
    allowed = [c for c in CATEGORIES if c not in STYLE_BLACKLIST.get(style, [])]
    if event != "beach_party":
        allowed = [c for c in allowed if c not in GLOBAL_BLACKLIST]
    # Event-specific enforcement
    event_allowed = EVENT_CATEGORY_RULES.get(event, allowed)
    allowed = [c for c in allowed if c in event_allowed]
    return allowed

def apply_post_corrections(predicted_label, detected_type, style, event):
    # General misclassification corrections
    if predicted_label in MISCLASS_CORRECTIONS:
        for correct_label in MISCLASS_CORRECTIONS[predicted_label]:
            if correct_label not in STYLE_BLACKLIST.get(style, []) and (event == "beach_party" or correct_label not in GLOBAL_BLACKLIST):
                return correct_label, CATEGORY_TO_TYPE.get(correct_label, detected_type)

    # Style-aware look-alike corrections
    lookalikes = LOOKALIKE_MAP.get(style, {})
    if predicted_label in lookalikes:
        corrected_label = lookalikes[predicted_label]
        # Event enforcement: make sure the corrected label is allowed for this event
        allowed_categories = EVENT_CATEGORY_RULES.get(event, CATEGORIES)
        if corrected_label in allowed_categories:
            return corrected_label, CATEGORY_TO_TYPE.get(corrected_label, detected_type)

    return predicted_label, detected_type

# =========================
# Recommendation Endpoint
# =========================
@app.route("/recommend/<mode>", methods=["POST"])
def recommend(mode):
    event = request.form.get("event", "").lower().replace(" ", "_")
    style = request.form.get("style", "").lower()
    motif = request.form.get("motif", "").strip()
    palette = request.form.get("palette", "").strip()

    wardrobe_type = request.form.get("wardrobe_type", None)

    event_description = EVENT_PRESETS.get(event, f"General outfit suggestion for {event}")
    style_text = {
        "feminine": "in feminine style",
        "masculine": "in masculine style",
        "androgynous": "in androgynous style",
        "gender_neutral": "in gender-neutral style"
    }.get(style, "")

    allowed_categories = get_allowed_categories(style, event)

    files = list(request.files.values())
    items = []

    for f in files:
        filename = f"{int(time.time()*1000000)}__{f.filename}"
        file_path = os.path.join(UPLOAD_FOLDER, filename)
        f.save(file_path)
        image = Image.open(file_path).convert("RGB")

        # text_prompts = [f"{c} {style_text} for {event_description}".strip() for c in allowed_categories]
        motif_text = f" with {motif} motif" if motif else ""
        palette_text = f" in {palette} color palette" if palette else ""

        text_prompts = [
            f"{c} {style_text}{motif_text}{palette_text} for {event_description}".strip()
            for c in allowed_categories
        ]
        inputs = processor(text=text_prompts, images=image, return_tensors="pt", padding=True)
        outputs = model(**inputs)

        image_features = torch.nn.functional.normalize(outputs.image_embeds, p=2, dim=-1)
        text_features = torch.nn.functional.normalize(outputs.text_embeds, p=2, dim=-1)
        sims = torch.matmul(image_features, text_features.T)

        best_idx = sims.argmax().item()
        predicted_label = allowed_categories[best_idx]
        similarity = sims[0, best_idx].item()

        # Correct type
        detected_type = "full-body" if predicted_label in FULL_BODY_CATEGORIES else CATEGORY_TO_TYPE.get(predicted_label, "unknown")

        # Post-correction
        predicted_label, detected_type = apply_post_corrections(predicted_label, detected_type, style, event)

        # Apply blacklist and event restrictions
        if predicted_label in GLOBAL_BLACKLIST and event != "beach_party":
            continue
        if predicted_label in STYLE_BLACKLIST.get(style, []):
            continue
        if predicted_label not in allowed_categories:
            continue

        # Manual mode enforcement
        if mode == "manual" and wardrobe_type in ["upper", "lower", "full-body"]:
            if detected_type != wardrobe_type:
                continue

        items.append({
            "filename": filename,
            "path": file_path,
            "label": predicted_label,
            "detected_type": detected_type,
            "similarity": similarity
        })

   # =========================
    # Recommendation Logic
    # =========================
    SIMILARITY_THRESHOLD = 0.25 # Minimum similarity to consider
    top_match, best_upper, best_lower = None, None, None

    if items:
        valid_items = [i for i in items if i["similarity"] >= SIMILARITY_THRESHOLD]

        if valid_items:
            if mode == "manual":
                candidate = max(valid_items, key=lambda x: x["similarity"])
                top_match = {**candidate, "recommendation": f"✅ Best {wardrobe_type} match for {event.replace('_',' ')}"}
            else:  # automatic mode
                upper_items = [i for i in valid_items if i["detected_type"] == "upper"]
                lower_items = [i for i in valid_items if i["detected_type"] == "lower"]
                full_items = [i for i in valid_items if i["detected_type"] == "full-body"]

                best_upper = max(upper_items, key=lambda x: x["similarity"]) if upper_items else None
                best_lower = max(lower_items, key=lambda x: x["similarity"]) if lower_items else None
                best_full  = max(full_items, key=lambda x: x["similarity"]) if full_items else None

               # ✅ Full-body allowed on its own ONLY if no upper and no lower
                if best_full and (
                    (not best_upper and not best_lower) or
                    (
                        best_upper and best_lower and
                        best_full["similarity"] >= max(best_upper["similarity"], best_lower["similarity"])
                    )
                ):
                    top_match = best_full
                    top_match["recommendation"] = f"👗 Best full-body match for {event.replace('_',' ')}"
                    best_upper = best_lower = None
                else:
                    # ✅ Require both upper & lower for automatic
                    if best_upper and best_lower:
                        best_upper["recommendation"] = f"👕 Best upper-body match for {event.replace('_',' ')}"
                        best_lower["recommendation"] = f"👖 Best lower-body match for {event.replace('_',' ')}"
                        # Pick the stronger one as top
                        if best_upper["similarity"] >= best_lower["similarity"]:
                            top_match = best_upper
                        else:
                            top_match = best_lower
                    else:
                        # ❌ If only upper OR only lower → no recommendation
                        top_match = {"recommendation": "❌ No recommendation"}

            # ✅ Add motif and palette here if available
    if top_match:
        top_match["motif"] = motif
        top_match["palette"] = palette
    else:
        top_match = {"recommendation": "❌ No recommendation", "motif": motif, "palette": palette}

    return jsonify({
        "mode": mode,
        "event": event,
        "style": style,
        "motif": motif,
        "palette": palette,
        "event_description": event_description,
        "wardrobe_type": top_match.get("detected_type") if top_match else None,
        "items": items,
        "top_match": top_match,
        "best_upper": best_upper,
        "best_lower": best_lower
    })



if __name__ == "__main__":
    app.run(debug=True)
