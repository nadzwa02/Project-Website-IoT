from pathlib import Path
from ultralytics import YOLO


BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "models" / "best.pt"


class JalasDetector:

    def __init__(self):

        print("===================================")
        print("JALAS AI DETECTOR")
        print("===================================")
        print(f"Loading model: {MODEL_PATH}")

        if not MODEL_PATH.exists():
            raise FileNotFoundError(
                f"Model tidak ditemukan: {MODEL_PATH}"
            )

        self.model = YOLO(str(MODEL_PATH))

        print("Model berhasil dimuat.")
        print(f"Classes: {self.model.names}")
        print("===================================")

    def predict(self, image, confidence=0.25, imgsz=320):

        # Gunakan imgsz 320 untuk inferensi CPU yang jauh lebih cepat
        results = self.model.predict(
            source=image,
            conf=confidence,
            imgsz=imgsz,
            verbose=False
        )

        result = results[0]

        detections = []

        if result.boxes is None:
            return detections

        for box in result.boxes:

            class_id = int(box.cls[0])
            conf = float(box.conf[0])

            x1, y1, x2, y2 = box.xyxy[0].tolist()

            class_name = self.model.names.get(
                class_id,
                str(class_id)
            )

            detections.append({
                "class_id": class_id,
                "class_name": class_name,
                "confidence": round(conf, 4),
                "bbox": {
                    "x1": round(x1),
                    "y1": round(y1),
                    "x2": round(x2),
                    "y2": round(y2)
                }
            })

        return detections
