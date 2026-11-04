from __future__ import annotations

from io import BytesIO
from pathlib import Path
from time import perf_counter
from typing import TypedDict

from PIL import Image


class Detection(TypedDict):
    class_id: int
    class_name: str
    confidence: float
    x1: float
    y1: float
    x2: float
    y2: float


class Prediction(TypedDict):
    width: int
    height: int
    inference_time_ms: float
    has_fire: bool
    has_smoke: bool
    detections: list[Detection]


class FireSmokeDetector:
    def __init__(self, model_path: Path, image_size: int = 640) -> None:
        from ultralytics import YOLO

        self._model = YOLO(str(model_path))
        self._image_size = image_size

    def predict(self, image_bytes: bytes, confidence: float) -> Prediction:
        with Image.open(BytesIO(image_bytes)) as source_image:
            image = source_image.convert("RGB")

        started_at = perf_counter()
        result = self._model.predict(
            source=image,
            conf=confidence,
            imgsz=self._image_size,
            verbose=False,
        )[0]
        inference_time_ms = (perf_counter() - started_at) * 1000
        detections: list[Detection] = []
        has_fire = False
        has_smoke = False

        if result.boxes is not None:
            boxes = result.boxes
            coordinates = boxes.xyxy.cpu().tolist()
            confidences = boxes.conf.cpu().tolist()
            class_ids = boxes.cls.cpu().tolist()

            for coordinates_for_box, box_confidence, raw_class_id in zip(
                coordinates,
                confidences,
                class_ids,
                strict=True,
            ):
                class_id = int(raw_class_id)
                class_name = str(self._model.names[class_id])
                normalized_class_name = class_name.casefold()
                has_fire = has_fire or "fire" in normalized_class_name or "flame" in normalized_class_name
                has_smoke = has_smoke or "smoke" in normalized_class_name

                detections.append(
                    {
                        "class_id": class_id,
                        "class_name": class_name,
                        "confidence": float(box_confidence),
                        "x1": float(coordinates_for_box[0]),
                        "y1": float(coordinates_for_box[1]),
                        "x2": float(coordinates_for_box[2]),
                        "y2": float(coordinates_for_box[3]),
                    }
                )

        return {
            "width": image.width,
            "height": image.height,
            "inference_time_ms": inference_time_ms,
            "has_fire": has_fire,
            "has_smoke": has_smoke,
            "detections": detections,
        }
