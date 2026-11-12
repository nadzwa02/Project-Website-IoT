
"""
JALAS - YOLOv10 FIRE & SMOKE LIVE DETECTION

Model:
    YOLOv10-FireSmoke-M.pt

Kelas:
    Mengikuti nama kelas yang tersimpan dalam model.

Kamera:
    Default index 1 (kamera eksternal).

Penggunaan:
    python live_prediksi.py
    python live_prediksi.py --source 0
    python live_prediksi.py --source 1
    python live_prediksi.py --source video.mp4

Kontrol:
    Q / ESC = Keluar
    S       = Simpan screenshot
    + / =   = Naikkan confidence
    - / _   = Turunkan confidence
"""

import argparse
import time
from pathlib import Path

import cv2
import torch
from ultralytics import YOLO


# =========================================================
# 1. KONFIGURASI
# =========================================================
MODEL_PATH = Path(r"D:\GitHub Dekstop\Project-Website-IoT\python_api\models\fire-smoke-y11n.pt")

# Kamera eksternal
CAMERA_INDEX = 1

# Confidence awal
CONF_THRESHOLD = 0.25

# Ukuran input model
IMAGE_SIZE = 640

# Direktori screenshot
SCREENSHOT_DIR = Path("hasil_live_yolov10")

WINDOW_NAME = "JALAS - Fire Smoke Detection"


# =========================================================
# 2. LOAD MODEL
# =========================================================

def load_model():

    if not MODEL_PATH.exists():
        raise FileNotFoundError(
            f"File model tidak ditemukan:\n{MODEL_PATH}"
        )

    print("=" * 55)
    print("JALAS - FIRE & SMOKE DETECTION")
    print("=" * 55)

    device = 0 if torch.cuda.is_available() else "cpu"

    if device == 0:
        print("[INFO] GPU NVIDIA terdeteksi.")
        print(f"[INFO] GPU: {torch.cuda.get_device_name(0)}")
    else:
        print("[INFO] GPU tidak tersedia. Menggunakan CPU.")

    print("[INFO] Memuat model YOLO...")

    model = YOLO(str(MODEL_PATH))

    print("[INFO] Model berhasil dimuat!")
    print("[INFO] Nama kelas:", model.names)
    print("[INFO] Device:", device)

    return model, device


# =========================================================
# 3. MENGAMBIL NAMA KELAS
# =========================================================

def get_class_name(model, class_id):

    names = model.names

    if isinstance(names, dict):
        name = names.get(class_id, f"CLASS_{class_id}")
    else:
        name = (
            names[class_id]
            if class_id < len(names)
            else f"CLASS_{class_id}"
        )

    return str(name).upper()


# =========================================================
# 4. DRAW BOUNDING BOX
# =========================================================

def draw_predictions(frame, result, model):

    if result.boxes is None:
        return frame

    boxes = result.boxes

    for box in boxes:

        # Koordinat bounding box
        x1, y1, x2, y2 = (
            box.xyxy[0].cpu().numpy().astype(int)
        )

        # Confidence
        confidence = float(box.conf[0].cpu().item())

        # Class ID
        class_id = int(box.cls[0].cpu().item())

        # Nama kelas
        class_name = get_class_name(model, class_id)

        # Warna berdasarkan kelas
        if "FIRE" in class_name:
            color = (0, 0, 255)

        elif "SMOKE" in class_name:
            color = (255, 140, 0)

        else:
            color = (0, 255, 0)

        # Bounding box
        cv2.rectangle(
            frame,
            (x1, y1),
            (x2, y2),
            color,
            2
        )

        # Label
        label = f"{class_name} {confidence:.2f}"

        (text_width, text_height), baseline = (
            cv2.getTextSize(
                label,
                cv2.FONT_HERSHEY_SIMPLEX,
                0.6,
                2
            )
        )

        top = max(
            0,
            y1 - text_height - baseline - 6
        )

        # Background label
        cv2.rectangle(
            frame,
            (x1, top),
            (
                x1 + text_width + 6,
                top + text_height + baseline + 6
            ),
            color,
            -1
        )

        # Teks label
        cv2.putText(
            frame,
            label,
            (x1 + 3, top + text_height + 2),
            cv2.FONT_HERSHEY_SIMPLEX,
            0.6,
            (255, 255, 255),
            2,
            cv2.LINE_AA
        )

    return frame


# =========================================================
# 5. MEMBUKA KAMERA / VIDEO
# =========================================================

def open_source(source):

    if isinstance(source, int):

        print(f"[INFO] Membuka kamera index {source}...")

        # DirectShow untuk Windows
        cap = cv2.VideoCapture(
            source,
            cv2.CAP_DSHOW
        )

    else:

        print(f"[INFO] Membuka video: {source}")

        cap = cv2.VideoCapture(source)

    if not cap.isOpened():

        cap.release()

        raise RuntimeError(
            f"Tidak dapat membuka sumber: {source}\n"
            "Pastikan kamera eksternal terhubung dan "
            "index kamera sudah benar."
        )

    # Atur resolusi kamera
    if isinstance(source, int):

        cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1280)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 720)

    return cap


# =========================================================
# 6. LIVE PREDICTION
# =========================================================

def predict_live(model, device, source):

    global CONF_THRESHOLD

    cap = open_source(source)

    print("\n[INFO] Live detection aktif!")
    print("[INFO] Q / ESC : Keluar")
    print("[INFO] S       : Screenshot")
    print("[INFO] + / =   : Naikkan confidence")
    print("[INFO] - / _   : Turunkan confidence")

    fps_start_time = time.time()

    frame_count = 0
    fps = 0.0

    try:

        while True:

            # -------------------------------------
            # AMBIL FRAME
            # -------------------------------------

            success, frame = cap.read()

            if not success:

                print("[INFO] Frame tidak tersedia.")
                break

            # -------------------------------------
            # PREDIKSI YOLOv
            # -------------------------------------

            results = model.predict(
                source=frame,
                conf=CONF_THRESHOLD,
                imgsz=IMAGE_SIZE,
                device=device,
                verbose=False
            )

            # Ultralytics mengembalikan list Results
            result = results[0]

            # -------------------------------------
            # DRAW BOUNDING BOX
            # -------------------------------------

            frame = draw_predictions(
                frame,
                result,
                model
            )

            # -------------------------------------
            # HITUNG FPS
            # -------------------------------------

            frame_count += 1

            elapsed = time.time() - fps_start_time

            if elapsed >= 1.0:

                fps = frame_count / elapsed

                frame_count = 0
                fps_start_time = time.time()

            # -------------------------------------
            # INFORMASI LAYAR
            # -------------------------------------

            cv2.rectangle(
                frame,
                (8, 8),
                (410, 110),
                (25, 25, 25),
                -1
            )

            cv2.putText(
                frame,
                f"JALAS YOLO | FPS: {fps:.1f}",
                (18, 36),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.6,
                (255, 255, 255),
                2,
                cv2.LINE_AA
            )

            cv2.putText(
                frame,
                f"Confidence: {CONF_THRESHOLD:.2f}",
                (18, 65),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.55,
                (0, 255, 255),
                2,
                cv2.LINE_AA
            )

            cv2.putText(
                frame,
                f"Source: {source}",
                (18, 91),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                (255, 255, 255),
                1,
                cv2.LINE_AA
            )

            # -------------------------------------
            # TAMPILKAN FRAME
            # -------------------------------------

            cv2.imshow(
                WINDOW_NAME,
                frame
            )

            key = cv2.waitKey(1) & 0xFF

            # Keluar
            if key in (ord("q"), 27):
                break

            # Screenshot
            elif key == ord("s"):

                output = SCREENSHOT_DIR / (
                    f"hasil_{int(time.time())}.jpg"
                )

                cv2.imwrite(
                    str(output),
                    frame
                )

                print(f"[INFO] Screenshot: {output}")

            # Naikkan confidence
            elif key in (ord("+"), ord("=")):

                CONF_THRESHOLD = min(
                    0.95,
                    CONF_THRESHOLD + 0.05
                )

                print(
                    f"[INFO] Confidence: {CONF_THRESHOLD:.2f}"
                )

            # Turunkan confidence
            elif key in (ord("-"), ord("_")):

                CONF_THRESHOLD = max(
                    0.05,
                    CONF_THRESHOLD - 0.05
                )

                print(
                    f"[INFO] Confidence: {CONF_THRESHOLD:.2f}"
                )

    finally:

        cap.release()
        cv2.destroyAllWindows()

    print("[INFO] Live prediction dihentikan.")


# =========================================================
# 7. MAIN PROGRAM
# =========================================================

def main():

    parser = argparse.ArgumentParser(
        description="JALAS YOLO Live Detection"
    )

    parser.add_argument(
        "--source",
        default=str(CAMERA_INDEX),
        help="Index kamera, path video, atau file gambar."
    )

    args = parser.parse_args()

    SCREENSHOT_DIR.mkdir(
        parents=True,
        exist_ok=True
    )

    # Load model
    model, device = load_model()

    # Parse source
    source = (
        int(args.source)
        if args.source.isdigit()
        else args.source
    )

    image_extensions = {
        ".jpg",
        ".jpeg",
        ".png",
        ".bmp",
        ".webp"
    }

    # Mode gambar
    if (
        isinstance(source, str)
        and Path(source).suffix.lower() in image_extensions
    ):

        image = cv2.imread(source)

        if image is None:
            raise RuntimeError(
                f"Gambar tidak dapat dibaca: {source}"
            )

        results = model.predict(
            source=image,
            conf=CONF_THRESHOLD,
            imgsz=IMAGE_SIZE,
            device=device,
            verbose=False
        )

        result = results[0]

        image = draw_predictions(
            image,
            result,
            model
        )

        cv2.imshow(
            WINDOW_NAME,
            image
        )

        print("[INFO] Tekan Q atau ESC untuk keluar.")

        while True:
            if cv2.waitKey(0) & 0xFF in (ord("q"), 27):
                break

        cv2.destroyAllWindows()

    # Mode kamera / video
    else:

        predict_live(
            model,
            device,
            source
        )


# =========================================================
# 8. RUN
# =========================================================

if __name__ == "__main__":

    try:
        main()

    except KeyboardInterrupt:
        print("\n[INFO] Program dihentikan.")

    except Exception as error:
        print(f"\n[ERROR] {error}")
        raise
