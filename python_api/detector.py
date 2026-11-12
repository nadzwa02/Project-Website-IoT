from pathlib import Path
import numpy as np
import torch
from ultralytics import YOLO


BASE_DIR = Path(__file__).resolve().parent
MODEL_PATH = BASE_DIR / "models" / "fire-smoke-y11n.pt"


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

        self.device = 0 if torch.cuda.is_available() else "cpu"
        self.model = YOLO(str(MODEL_PATH))

        print("Model berhasil dimuat.")
        print(f"Device: {self.device}")
        print(f"Classes: {self.model.names}")

        # Warm-up model agar inferensi pertama tidak lag/stuttering
        print("Menjalankan warm-up model...")
        dummy = np.zeros((320, 320, 3), dtype=np.uint8)
        self.model.predict(source=dummy, imgsz=320, device=self.device, verbose=False)
        print("Warm-up selesai. Siap melakukan deteksi real-time.")
        print("===================================")

    def predict(self, image, confidence=0.25, imgsz=320):

        # Gunakan imgsz 320 untuk inferensi CPU yang optimal dan responsif
        results = self.model.predict(
            source=image,
            conf=confidence,
            imgsz=imgsz,
            device=self.device,
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
