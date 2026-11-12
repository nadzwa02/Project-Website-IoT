import os

# Matikan hardware transform MSMF agar USB Camera eksternal cepat dibuka & tidak layar gelap/hitam
os.environ["OPENCV_VIDEOIO_MSMF_ENABLE_HW_TRANSFORMS"] = "0"

import threading
import time
from datetime import datetime

import cv2
import numpy as np
from flask import Flask, Response, jsonify, request
from flask_cors import CORS

from detector import JalasDetector


app = Flask(__name__)
CORS(app)

# Load model SATU KALI ketika Flask dijalankan
detector = JalasDetector()


class SharedLiveStream:
    """
    Mengelola kamera server & inferensi YOLO secara terpusat
    dengan smoothing persistence agar bounding box stabil dan tidak flickering/hilang-timbul.
    """

    def __init__(self, model_detector: JalasDetector):
        self.detector = model_detector
        self.lock = threading.Lock()
        self.frame_cond = threading.Condition(self.lock)

        self.running = False
        self.camera_index = 0
        self.confidence = 0.20  # Diturunkan dari 0.30 agar api kecil/bergoyang tetap terdeteksi
        self.imgsz = 320        # 320x320 optimal untuk inferensi cepat (~30ms) tanpa lag pada CPU

        self.cap = None
        self.raw_frame = None
        self.jpeg_bytes = None
        self.frame_id = 0

        self.detections = []
        self.status = "safe"
        self.summary = {"fire": 0, "smoke": 0, "total": 0}
        self.updated_at = None
        self.error_message = None

        # Mekanisme persistence (bounding box smoothing)
        self.missed_detection_count = 0
        self.max_missed_frames = 4  # Menahan box selama 4 siklus inferensi saat api berkedip/flicker

        self._capture_thread = None
        self._infer_thread = None

    def _try_open_backend(self, index: int, backend: int):
        cap = cv2.VideoCapture(index, backend)
        if not cap or not cap.isOpened():
            if cap:
                cap.release()
            return None, None

        if backend == cv2.CAP_DSHOW:
            cap.set(cv2.CAP_PROP_FOURCC, cv2.VideoWriter_fourcc(*"MJPG"))
        cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
        cap.set(cv2.CAP_PROP_FPS, 30)

        valid_frame = None
        for _ in range(12):
            ret, frame = cap.read()
            if ret and frame is not None and frame.size > 0:
                valid_frame = frame
                if float(np.mean(frame)) > 5.0:
                    break
            time.sleep(0.01)

        if valid_frame is None:
            cap.release()
            return None, None

        return cap, valid_frame

    def _open_camera(self, requested_index: int):
        candidates = [
            (requested_index, cv2.CAP_DSHOW),
            (requested_index, cv2.CAP_MSMF),
            (requested_index, cv2.CAP_ANY),
        ]

        alt_index = 0 if requested_index != 0 else 1
        candidates.extend([
            (alt_index, cv2.CAP_DSHOW),
            (alt_index, cv2.CAP_MSMF),
        ])

        for idx, backend in candidates:
            cap, first_frame = self._try_open_backend(idx, backend)
            if cap is not None and first_frame is not None:
                print(f"[INFO] Kamera berhasil dibuka pada index={idx} (backend={backend})")
                return cap, first_frame

        return None, None

    def list_cameras(self):
        return [
            {"index": 0, "label": "Kamera 0 - Kamera Utama / USB Terdeteksi"},
            {"index": 1, "label": "Kamera 1 - Kamera Sekunder"},
        ]

    def start(self, camera_index: int = 0):
        with self.lock:
            if self.running and self.camera_index == camera_index and self.jpeg_bytes is not None:
                return True, "Live stream sudah berjalan"

        self.stop()

        cap, first_frame = self._open_camera(camera_index)
        if cap is None or first_frame is None:
            msg = (
                f"Tidak dapat membuka Kamera {camera_index}. "
                "Pastikan kamera terhubung dan tidak sedang dipakai aplikasi lain."
            )
            with self.lock:
                self.error_message = msg
            return False, msg

        with self.lock:
            self.cap = cap
            self.camera_index = camera_index
            self.running = True
            self.error_message = None
            self.raw_frame = first_frame.copy()
            self.detections = []
            self.missed_detection_count = 0
            self.status = "safe"
            self.summary = {"fire": 0, "smoke": 0, "total": 0}
            self.updated_at = datetime.now().strftime("%H:%M:%S")

            annotated = self._draw_boxes(first_frame.copy(), [])
            ok, buf = cv2.imencode(".jpg", annotated, [int(cv2.IMWRITE_JPEG_QUALITY), 70])
            if ok:
                self.jpeg_bytes = buf.tobytes()
                self.frame_id += 1

        self._capture_thread = threading.Thread(target=self._capture_loop, daemon=True)
        self._infer_thread = threading.Thread(target=self._inference_loop, daemon=True)
        self._capture_thread.start()
        self._infer_thread.start()

        return True, f"Live stream Kamera {camera_index} aktif"

    def stop(self):
        with self.lock:
            self.running = False
            self.frame_cond.notify_all()

        if self._capture_thread and self._capture_thread.is_alive():
            self._capture_thread.join(timeout=1.5)
        if self._infer_thread and self._infer_thread.is_alive():
            self._infer_thread.join(timeout=1.5)

        with self.lock:
            if self.cap is not None:
                try:
                    self.cap.release()
                except Exception:
                    pass
                self.cap = None
            self.raw_frame = None
            self.jpeg_bytes = None
            self.detections = []
            self.missed_detection_count = 0
            self.status = "safe"
            self.summary = {"fire": 0, "smoke": 0, "total": 0}

    def _draw_boxes(self, frame, detections):
        for det in detections:
            bbox = det["bbox"]
            x1, y1, x2, y2 = bbox["x1"], bbox["y1"], bbox["x2"], bbox["y2"]
            class_name = str(det["class_name"]).upper()
            conf = float(det["confidence"])

            if "FIRE" in class_name:
                color = (0, 34, 255)  # Merah terang (BGR)
            elif "SMOKE" in class_name:
                color = (0, 165, 255)  # Oranye (BGR)
            else:
                color = (0, 200, 83)

            cv2.rectangle(frame, (x1, y1), (x2, y2), color, 3)

            label = f"{class_name} {int(conf * 100)}%"
            (tw, th), baseline = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.55, 2)
            top = max(0, y1 - th - baseline - 6)

            cv2.rectangle(frame, (x1, top), (x1 + tw + 10, top + th + baseline + 6), color, -1)
            cv2.putText(
                frame,
                label,
                (x1 + 5, top + th + 2),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.55,
                (255, 255, 255),
                2,
                cv2.LINE_AA,
            )
        return frame

    def _capture_loop(self):
        while True:
            with self.lock:
                if not self.running or self.cap is None:
                    break
                cap = self.cap

            ret, frame = cap.read()
            if not ret or frame is None:
                time.sleep(0.01)
                continue

            with self.lock:
                self.raw_frame = frame.copy()
                current_detections = list(self.detections)

            annotated = self._draw_boxes(frame, current_detections)
            ok, buf = cv2.imencode(".jpg", annotated, [int(cv2.IMWRITE_JPEG_QUALITY), 70])
            if ok:
                with self.frame_cond:
                    self.jpeg_bytes = buf.tobytes()
                    self.frame_id += 1
                    self.frame_cond.notify_all()

            time.sleep(0.005)

    def _inference_loop(self):
        while True:
            with self.lock:
                if not self.running:
                    break
                frame = self.raw_frame.copy() if self.raw_frame is not None else None
                conf = self.confidence
                imgsz = self.imgsz

            if frame is None:
                time.sleep(0.01)
                continue

            try:
                detections = self.detector.predict(image=frame, confidence=conf, imgsz=imgsz)

                with self.lock:
                    if len(detections) > 0:
                        # Ada api/asap terdeteksi -> simpan langsung & reset counter
                        self.detections = detections
                        self.missed_detection_count = 0
                    else:
                        # Jika frame ini tidak mendeteksi (misal karena api bergoyang/berkedip),
                        # tahan bounding box sebelumnya selama beberapa frame agar tidak hilang-timbul
                        self.missed_detection_count += 1
                        if self.missed_detection_count > self.max_missed_frames:
                            self.detections = []

                    fire_count = sum(1 for d in self.detections if "fire" in str(d["class_name"]).lower())
                    smoke_count = sum(1 for d in self.detections if "smoke" in str(d["class_name"]).lower())

                    if fire_count > 0:
                        self.status = "fire"
                    elif smoke_count > 0:
                        self.status = "smoke"
                    else:
                        self.status = "safe"

                    self.summary = {
                        "fire": fire_count,
                        "smoke": smoke_count,
                        "total": len(self.detections),
                    }
                    self.updated_at = datetime.now().strftime("%H:%M:%S")
            except Exception as exc:
                print(f"[ERROR] Inference loop: {exc}")

            time.sleep(0.005)

    def get_status(self):
        with self.lock:
            return {
                "success": True,
                "running": self.running,
                "camera_index": self.camera_index,
                "status": self.status if self.running else "offline",
                "summary": dict(self.summary),
                "detections": list(self.detections),
                "updated_at": self.updated_at,
                "error": self.error_message,
            }

    def generate_mjpeg(self):
        last_sent_id = -1
        while True:
            with self.frame_cond:
                while self.running and (self.jpeg_bytes is None or self.frame_id == last_sent_id):
                    self.frame_cond.wait(timeout=1.0)

                if not self.running or self.jpeg_bytes is None:
                    break

                frame_bytes = self.jpeg_bytes
                last_sent_id = self.frame_id

            yield (
                b"--frame\r\n"
                b"Content-Type: image/jpeg\r\n\r\n" + frame_bytes + b"\r\n"
            )


stream_manager = SharedLiveStream(detector)


@app.route("/", methods=["GET"])
def home():
    return jsonify({
        "success": True,
        "message": "JALAS Flask API is running",
        "service": "JALAS AI API",
    })


@app.route("/api/health", methods=["GET"])
def health():
    return jsonify({
        "success": True,
        "status": "healthy",
        "model": "YOLO11n JALAS",
    })


@app.route("/api/cameras", methods=["GET"])
def get_cameras():
    return jsonify({
        "success": True,
        "cameras": stream_manager.list_cameras(),
        "active_camera": stream_manager.camera_index,
        "running": stream_manager.running,
    })


@app.route("/api/stream/start", methods=["POST"])
def start_stream():
    payload = request.get_json(silent=True) or {}
    camera_index = int(payload.get("camera_index", request.form.get("camera_index", 0)))
    ok, message = stream_manager.start(camera_index=camera_index)
    status_data = stream_manager.get_status()
    status_data["message"] = message
    status_data["success"] = ok
    return jsonify(status_data), (200 if ok else 400)


@app.route("/api/stream/stop", methods=["POST"])
def stop_stream():
    stream_manager.stop()
    status_data = stream_manager.get_status()
    status_data["message"] = "Live stream dihentikan"
    return jsonify(status_data)


@app.route("/api/stream/status", methods=["GET"])
def stream_status():
    return jsonify(stream_manager.get_status())


@app.route("/api/stream", methods=["GET"])
def video_stream():
    if not stream_manager.running:
        return jsonify({
            "success": False,
            "message": "Live stream belum aktif",
        }), 404

    response = Response(
        stream_manager.generate_mjpeg(),
        mimetype="multipart/x-mixed-replace; boundary=frame",
    )
    response.headers["Cache-Control"] = "no-cache, no-store, must-revalidate"
    response.headers["Pragma"] = "no-cache"
    response.headers["Expires"] = "0"
    return response


@app.route("/api/predict", methods=["POST"])
def predict():
    if "image" not in request.files:
        return jsonify({
            "success": False,
            "message": "File image tidak ditemukan",
        }), 400

    file = request.files["image"]
    image_bytes = file.read()
    np_array = np.frombuffer(image_bytes, np.uint8)
    image = cv2.imdecode(np_array, cv2.IMREAD_COLOR)

    if image is None:
        return jsonify({
            "success": False,
            "message": "Image tidak dapat dibaca",
        }), 400

    confidence = request.form.get("confidence", default=0.20, type=float)
    imgsz = request.form.get("imgsz", default=320, type=int)

    detections = detector.predict(image=image, confidence=confidence, imgsz=imgsz)

    fire_count = sum(1 for d in detections if d["class_name"] == "fire")
    smoke_count = sum(1 for d in detections if d["class_name"] == "smoke")

    if fire_count > 0:
        status = "fire"
    elif smoke_count > 0:
        status = "smoke"
    else:
        status = "safe"

    summary = {
        "fire": fire_count,
        "smoke": smoke_count,
        "total": len(detections),
    }

    return jsonify({
        "success": True,
        "status": status,
        "summary": summary,
        "detections": detections,
    })


if __name__ == "__main__":
    app.run(
        host="0.0.0.0",
        port=5000,
        debug=False,
        threaded=True,
    )
