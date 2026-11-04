from flask import Flask, request, jsonify
from flask_cors import CORS

import cv2
import numpy as np

from detector import JalasDetector


app = Flask(__name__)

CORS(app)


# Load model SATU KALI ketika Flask dijalankan
detector = JalasDetector()


@app.route("/", methods=["GET"])
def home():

    return jsonify({
        "success": True,
        "message": "JALAS Flask API is running",
        "service": "JALAS AI API"
    })


@app.route("/api/health", methods=["GET"])
def health():

    return jsonify({
        "success": True,
        "status": "healthy",
        "model": "YOLO11n JALAS"
    })


@app.route("/api/predict", methods=["POST"])
def predict():

    # ==========================================
    # 1. CEK FILE
    # ==========================================

    if "image" not in request.files:

        return jsonify({
            "success": False,
            "message": "File image tidak ditemukan"
        }), 400


    file = request.files["image"]


    # ==========================================
    # 2. BACA IMAGE
    # ==========================================

    image_bytes = file.read()

    np_array = np.frombuffer(
        image_bytes,
        np.uint8
    )

    image = cv2.imdecode(
        np_array,
        cv2.IMREAD_COLOR
    )


    if image is None:

        return jsonify({
            "success": False,
            "message": "Image tidak dapat dibaca"
        }), 400


    # ==========================================
    # 3. PARAMETER CONFIDENCE & IMGSZ
    # ==========================================

    confidence = request.form.get(
        "confidence",
        default=0.25,
        type=float
    )

    imgsz = request.form.get(
        "imgsz",
        default=320,
        type=int
    )


    # ==========================================
    # 4. PREDIKSI YOLO
    # ==========================================

    detections = detector.predict(
        image=image,
        confidence=confidence,
        imgsz=imgsz
    )


    # ==========================================
    # 5. HITUNG HASIL
    # ==========================================

    fire_count = 0
    smoke_count = 0

    for detection in detections:

        if detection["class_name"] == "fire":
            fire_count += 1

        elif detection["class_name"] == "smoke":
            smoke_count += 1


    # ==========================================
    # 6. TENTUKAN STATUS
    # ==========================================

    if fire_count > 0:

        status = "fire"

    elif smoke_count > 0:

        status = "smoke"

    else:

        status = "safe"


    # ==========================================
    # 7. RESPONSE
    # ==========================================

    return jsonify({

        "success": True,

        "status": status,

        "summary": {

            "fire": fire_count,

            "smoke": smoke_count,

            "total": len(detections)

        },

        "detections": detections

    })


if __name__ == "__main__":

    app.run(
        host="0.0.0.0",
        port=5000,
        debug=False,
        threaded=True
    )
