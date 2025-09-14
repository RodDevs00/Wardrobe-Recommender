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

UPLOAD_FOLDER = "../uploads"  # relative to python_api folder
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
    "crop-top": "upper", "polo": "upper",

    # Lower
    "pants": "lower", "jeans": "lower", "shorts": "lower", "skirt": "lower",
    "trousers": "lower", "leggings": "lower", "cargo-pants": "lower",

    # Full-body
    "dress": "full-body", "gown": "full-body", "jumpsuit": "full-body",
    "romper": "full-body", "overalls": "full-body", "bra-panty-set": "full-body",
    "swimsuit": "full-body"
}

# =========================
# Event Presets
# =========================
EVENT_PRESETS = {
    "formal_wedding": "Elegant formal wedding outfit: gowns or suits/tuxedos. Prioritize full-body coverage, no footwear or accessories.",
    "casual_wedding": "Casual outdoor wedding attire: dresses, shirts, or light pants. Prioritize comfort and soft colors. No footwear or accessories.",
    "summer_party": "Light summer outfit. For women, prioritize floral dresses; for men, prioritize floral polos or light shirts. Include skirts or shorts if desired. No footwear or accessories.",
    "beach_party": "Swimming and beach attire. Prioritize bikinis or bra/panty sets for women, swim trunks or shorts for men. Cover-ups or rompers optional. No footwear or accessories.",
    "casual_office": "Smart casual office wear: shirts, trousers, blazers. Neutral and professional colors. No footwear or accessories.",
    "formal_office": "Formal office attire: suits, dresses, or blazers. Clean and polished look for meetings. No footwear or accessories.",
    "streetwear": "Trendy streetwear: hoodies, jeans, t-shirts, or crop tops. Casual and fashionable. No footwear or accessories.",
    "gym": "Activewear: leggings, tank tops, sports bras, or sports tops. Comfortable and flexible for workouts. No footwear or accessories.",
    "winter_casual": "Warm casual wear: sweaters, coats, jackets, trousers. Layering encouraged for cold weather. No footwear or accessories.",
    "travel": "Comfortable travel outfit: casual tops, pants, light jackets. Prioritize ease of movement. No footwear or accessories.",
    "date_night": "Stylish outfits for night out: dresses, chic shirts, tailored pants. Elegant yet comfortable. No footwear or accessories.",
    "music_festival": "Festival-ready outfits: crop tops, shorts, jumpsuits, loose dresses. Bold patterns and vibrant colors encouraged. No footwear or accessories.",
    "business_meeting": "Professional look: suits, blazers, formal dresses. Conservative and polished. No footwear or accessories.",
    "graduation": "Formal but youthful attire: dresses, gowns, blazers. Bright and celebratory colors recommended. No footwear or accessories.",
    "funeral": "Respectful attire: dark or muted colors like suits, dresses, coats. Conservative style required. No footwear or accessories.",
    "sports_event": "Casual sporty outfit: t-shirts, shorts, jeans, track pants. Comfortable for movement. No footwear or accessories."
}
# =========================
# Smarter Scoring System (Style > Event > Type)
# =========================
def score_item(similarity, predicted_label, event_prompt, style):
    """
    Compute a final score:
    - Style preference has the strongest influence
    - Event relevance is secondary
    - CLIP similarity and category type contribute baseline
    """
    base_score = similarity * 0.6  # start with CLIP similarity
    label = predicted_label.lower()
    event_text = event_prompt.lower()
    style = (style or "").lower()

    # ---- STYLE PRIORITY ----
    if style == "feminine":
        if label in ["dress", "skirt", "blouse", "gown", "crop-top"]:
            base_score += 0.35
    elif style == "masculine":
        if label in ["suit", "trousers", "shirt", "blazer", "polo"]:
            base_score += 0.35
    elif style == "androgynous":
        if label in ["jeans", "jacket", "t-shirt", "hoodie", "vest"]:
            base_score += 0.35
    elif style == "gender_neutral":
        if label in ["t-shirt", "jeans", "sweater", "hoodie", "pants"]:
            base_score += 0.3

    # ---- EVENT PRIORITY ----
    # Weddings / Formal
    if "wedding" in event_text or "formal" in event_text:
        if label in ["suit", "gown", "dress", "blazer", "coat"]:
            base_score += 0.25
        elif label in ["t-shirt", "tank-top", "shorts", "swimsuit"]:
            base_score -= 0.25

    # Casual
    if "casual" in event_text:
        if label in ["t-shirt", "jeans", "polo", "shorts"]:
            base_score += 0.2
        elif label in ["gown", "suit"]:
            base_score -= 0.2

    # Party / Night
    if "party" in event_text or "night" in event_text:
        if label in ["dress", "blazer", "shirt", "romper"]:
            base_score += 0.2
        elif label in ["leggings", "pajamas", "sportswear"]:
            base_score -= 0.2

    # Funeral
    if "funeral" in event_text:
        if label in ["suit", "coat", "dress"]:
            base_score += 0.2
        elif label in ["t-shirt", "shorts", "swimsuit"]:
            base_score -= 0.3

    # Gym / Sports
    if "gym" in event_text or "sports" in event_text:
        if label in ["leggings", "tank-top", "t-shirt", "shorts"]:
            base_score += 0.25
        elif label in ["gown", "suit", "coat"]:
            base_score -= 0.3

    # ---- TYPE BALANCE (least weight, just to stabilize auto upper/lower) ----
    if label in ["shirt", "t-shirt", "blouse", "jacket", "hoodie"]:
        base_score += 0.05  # slight bonus for versatile upper-body
    if label in ["pants", "jeans", "skirt", "shorts"]:
        base_score += 0.05  # slight bonus for versatile lower-body

    return max(0, min(1, base_score))  # clamp 0–1


@app.route("/recommend/<mode>", methods=["POST"])
def recommend(mode):
    event = request.form.get("event", "").lower().replace(" ", "_")
    wardrobe_type = request.form.get("wardrobe_type", None)
    style = request.form.get("style", "").lower()

    style_map = {
        "feminine": "in feminine style",
        "masculine": "in masculine style",
        "androgynous": "in androgynous style",
        "gender_neutral": "in gender-neutral style",
    }
    style_text = style_map.get(style, "")

    files = list(request.files.values())
    event_description = EVENT_PRESETS.get(event, f"Outfit suitable for {event}")

    items = []

    # Process uploaded files
    for f in files:
        filename = f"{int(time.time()*1000000)}__{f.filename}"
        file_path = os.path.join(UPLOAD_FOLDER, filename)
        f.save(file_path)

        image = Image.open(file_path).convert("RGB")

        text_prompts = [
            f"{c} {style_text} for {event_description}".strip()
            for c in CATEGORIES
        ]

        inputs = processor(
            text=text_prompts,
            images=image,
            return_tensors="pt",
            padding=True
        )
        outputs = model(**inputs)

        image_features = outputs.image_embeds
        text_features = outputs.text_embeds
        sims = torch.matmul(image_features, text_features.T)
        best_idx = sims.argmax().item()

        predicted_label = CATEGORIES[best_idx]
        similarity = sims[0, best_idx].item()
        detected_type = CATEGORY_TO_TYPE.get(predicted_label, "unknown")

        # Apply smarter scoring
        final_score = score_item(similarity, predicted_label, event_description, style)

        items.append({
            "filename": filename,
            "path": f"/ai-wardrobe/uploads/{filename}",
            "label": predicted_label,
            "detected_type": detected_type,
            "similarity": similarity,
            "final_score": final_score,
            "details": {
                "style": style or "unspecified",
                "detected_type": detected_type,
                "category": predicted_label
            }
        })

    # Pick best recommendation
    valid_items = [i for i in items if "warning" not in i]
    top_match = {"items": []}  # Initialize as empty list

    if not valid_items:
        top_match = {"recommendation": "❌ No valid items to recommend."}
    else:
        # --- inside /recommend/<mode> ---
        top_item = max(valid_items, key=lambda x: x["final_score"])
        top_match = {"items": []}

        if mode == "automatic":
            if top_item["detected_type"] == "full-body":
                top_item["recommendation"] = f"✅ Best overall fit for {event.replace('_',' ')}"
                top_match["items"] = [top_item]
            else:
                upper_items = [i for i in valid_items if i["detected_type"] == "upper"]
                lower_items = [i for i in valid_items if i["detected_type"] == "lower"]
                if upper_items:
                    best_upper = max(upper_items, key=lambda x: x["final_score"])
                    best_upper["recommendation"] = f"👕 Best upper-body match for {event.replace('_',' ')}"
                    top_match["items"].append(best_upper)
                if lower_items:
                    best_lower = max(lower_items, key=lambda x: x["final_score"])
                    best_lower["recommendation"] = f"👖 Best lower-body match for {event.replace('_',' ')}"
                    top_match["items"].append(best_lower)

        else:  # Manual mode: strict type enforcement
            # Pick top full-body if exists
            full_body_items = [i for i in valid_items if i["detected_type"] == "full-body"]
            if full_body_items:
                top_full = max(full_body_items, key=lambda x: x["final_score"])
                top_full["recommendation"] = f"✅ Strict full-body match for {event.replace('_',' ')}"
                top_match["items"] = [top_full]
            else:
                # Pick top upper and top lower separately
                upper_items = [i for i in valid_items if i["detected_type"] == "upper"]
                lower_items = [i for i in valid_items if i["detected_type"] == "lower"]
                if upper_items:
                    top_upper = max(upper_items, key=lambda x: x["final_score"])
                    top_upper["recommendation"] = f"👕 Strict upper-body match for {event.replace('_',' ')}"
                    top_match["items"].append(top_upper)
                if lower_items:
                    top_lower = max(lower_items, key=lambda x: x["final_score"])
                    top_lower["recommendation"] = f"👖 Strict lower-body match for {event.replace('_',' ')}"
                    top_match["items"].append(top_lower)



    return jsonify({
        "mode": mode,
        "event": event,
        "style": style or "unspecified",
        "event_description": event_description,
        "wardrobe_type": (
            top_match["items"][0].get("detected_type", "unknown")
            if isinstance(top_match, dict) and top_match.get("items")
            else "unknown"
        ),
        "items": items,
        "top_match": top_match
    })


if __name__ == "__main__":
    app.run(debug=True)