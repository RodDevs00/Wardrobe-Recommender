from flask import Flask, request, jsonify
from transformers import CLIPProcessor, CLIPModel
from PIL import Image
import torch
import os
import time

app = Flask(__name__)

# Load FashionCLIP
model_name = "patrickjohncyh/fashion-clip"
model = CLIPModel.from_pretrained(model_name)
processor = CLIPProcessor.from_pretrained(model_name)

UPLOAD_FOLDER = "../uploads"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# =========================
# Wardrobe Categories
# =========================
CATEGORIES = [
    # Upper body
    "suit", "shirt", "t-shirt", "blouse", "jacket", "blazer", "coat", "vest",
    "hoodie", "sweater", "cardigan", "tank-top", "crop-top", "polo",

    # Lower body
    "pants", "jeans", "shorts", "skirt", "trousers", "leggings", "cargo-pants",

    # Full-body
    "dress", "gown", "jumpsuit", "romper", "overalls", "bra-panty-set", "swimsuit"
]
CATEGORY_TO_TYPE = {
    # Upper
    "suit": "upper", "shirt": "upper", "t-shirt": "upper", "blouse": "upper",
    "jacket": "upper", "blazer": "upper", "coat": "upper", "vest": "upper",
    "hoodie": "upper", "sweater": "upper", "cardigan": "upper", "tank-top": "upper",
    "crop-top": "upper", "polo": "upper", "floral-polo": "upper", "barong": "upper",

    # Lower
    "pants": "lower", "jeans": "lower", "shorts": "lower", "skirt": "lower",
    "trousers": "lower", "leggings": "lower", "cargo-pants": "lower", "swim-trunks": "lower",

    # Full-body
    "dress": "full-body", "gown": "full-body", "jumpsuit": "full-body",
    "romper": "full-body", "overalls": "full-body", "bra-panty-set": "full-body",
    "swimsuit": "full-body"
}


# =========================
# Restricted Event Presets
# =========================
EVENT_PRESETS = {
    "birthday": (
        "Cheerful and stylish birthday celebration outfit. "
        "Prioritize casual yet festive clothing such as colorful dresses, "
        "stylish casual shirts, skirts, or trendy tops paired with jeans or trousers. "
        "The style should balance comfort with playful elegance, making it perfect for photos, "
        "social gatherings, and dancing."
    ),
    "wedding": (
        "Elegant and sophisticated wedding attire. "
        "Prioritize gowns, dresses, suits (including tuxedos and blazers), and the Barong Tagalog "
        "for a formal yet culturally authentic look. "
        "Focus on refined outfits suitable for ceremonies and receptions, "
        "with an emphasis on polished details and timeless style."
    ),
    "beach_party": (
        "Relaxed and vibrant beach party wear. "
        "Prioritize swimsuits, bra-panty sets styled as beachwear, floral dresses, "
        "shorts, swim trunks, floral polo shirts, and crisp white polos. "
        "Lightweight fabrics, playful tropical patterns, and carefree styling are ideal "
        "to stay cool and stylish under the sun."
    )
}


# =========================
# Recommendation Endpoint
# =========================
@app.route("/recommend/<mode>", methods=["POST"])
def recommend(mode):
    event = request.form.get("event", "").lower().replace(" ", "_")
    wardrobe_type = request.form.get("wardrobe_type", None)
    style = request.form.get("style", "").lower()

    # =========================
    # Style-based category filtering
    # =========================
    if style == "masculine":
        allowed_categories = [
            "suit", "shirt", "t-shirt", "jacket", "blazer", "coat", "vest",
            "hoodie", "sweater", "cardigan", "tank-top", "polo", "floral-polo", "barong",
            "pants", "jeans", "shorts", "trousers", "cargo-pants", "swim-trunks"
        ]
    elif style == "feminine":
        allowed_categories = [
            "dress", "gown", "blouse", "skirt", "leggings", "crop-top",
            "bra-panty-set", "swimsuit", "romper", "jumpsuit", "floral-polo"
        ]
    elif style in ["androgynous", "gender_neutral"]:
        allowed_categories = [
            "shirt", "t-shirt", "jacket", "hoodie", "sweater", "cardigan", "tank-top", "polo",
            "pants", "jeans", "shorts", "trousers", "cargo-pants", "swim-trunks",
            "overalls", "jumpsuit"
        ]
    else:
        allowed_categories = CATEGORIES  # fallback: all

    # =========================
    # Event + Style descriptions
    # =========================
    event_key = f"{event}_{style}" if f"{event}_{style}" in EVENT_PRESETS else event
    event_description = EVENT_PRESETS.get(event_key, f"General outfit suggestion for {event}")

    style_text = {
        "feminine": "in feminine style",
        "masculine": "in masculine style",
        "androgynous": "in androgynous style",
        "gender_neutral": "in gender-neutral style"
    }.get(style, "")

    files = list(request.files.values())
    items = []

    # =========================
    # Process Uploaded Images
    # =========================
    for f in files:
        filename = f"{int(time.time()*1000000)}__{f.filename}"
        file_path = os.path.join(UPLOAD_FOLDER, filename)
        f.save(file_path)

        image = Image.open(file_path).convert("RGB")

        # ✅ Only generate prompts for allowed categories
        text_prompts = [
            f"{c} {style_text} for {event_description}".strip()
            for c in allowed_categories
        ]

        inputs = processor(text=text_prompts, images=image, return_tensors="pt", padding=True)
        outputs = model(**inputs)

        image_features = outputs.image_embeds
        text_features = outputs.text_embeds
        sims = torch.matmul(image_features, text_features.T)

        best_idx = sims.argmax().item()
        predicted_label = allowed_categories[best_idx]
        similarity = sims[0, best_idx].item()
        detected_type = CATEGORY_TO_TYPE.get(predicted_label, "unknown")

        # 🚫 Skip shoes/accessories always
        if detected_type in ["shoes", "accessory"]:
            continue

        # 🚫 Manual mode: enforce strict wardrobe_type
        if mode == "manual" and wardrobe_type in ["upper", "lower", "full-body"]:
            if detected_type != wardrobe_type:
                continue

        # ✅ Add valid item
        items.append({
            "filename": filename,
            "path": file_path,
            "label": predicted_label,
            "detected_type": detected_type,
            "similarity": similarity,
            "details": {
                "style": style or "unspecified",
                "detected_type": detected_type,
                "category": predicted_label
            }
        })

    # =========================
    # Recommendation Logic
    # =========================
    SIMILARITY_THRESHOLD = 0.28
    top_match, best_upper, best_lower = None, None, None

    if items:
        valid_items = [i for i in items if i["similarity"] >= SIMILARITY_THRESHOLD]

        if valid_items:
            if mode == "manual":
                # strict one pick only
                candidate = max(valid_items, key=lambda x: x["similarity"])
                top_match = {
                    **candidate,
                    "recommendation": f"✅ Best {wardrobe_type} match for {event.replace('_',' ')}"
                }
            else:  # automatic mode
                # pick best overall
                candidate = max(valid_items, key=lambda x: x["similarity"])
                top_match = {
                    **candidate,
                    "recommendation": f"✅ Best overall fit for {event.replace('_',' ')}"
                }

                # 👗 If top match is full-body, skip upper/lower pairing
                if candidate["detected_type"] != "full-body":
                    upper_items = [i for i in valid_items if i["detected_type"] == "upper"]
                    lower_items = [i for i in valid_items if i["detected_type"] == "lower"]

                    if upper_items:
                        best_upper = max(upper_items, key=lambda x: x["similarity"])
                        best_upper["recommendation"] = f"👕 Best upper-body match for {event.replace('_',' ')}"

                    if lower_items:
                        best_lower = max(lower_items, key=lambda x: x["similarity"])
                        best_lower["recommendation"] = f"👖 Best lower-body match for {event.replace('_',' ')}"
        # else: leave top_match = None
    # else: leave top_match = None if no items

    return jsonify({
        "mode": mode,
        "event": event,
        "style": style,
        "event_description": event_description,
        "wardrobe_type": top_match.get("detected_type") if top_match else None,
        "items": items,
        "top_match": top_match,
        "best_upper": best_upper,
        "best_lower": best_lower
    })


if __name__ == "__main__":
    app.run(debug=True)
