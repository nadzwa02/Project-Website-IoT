from __future__ import annotations

import hmac
import importlib.util
import logging
import os
import warnings
from datetime import datetime, timezone
from io import BytesIO
from pathlib import Path
from urllib.parse import quote
from uuid import uuid4

import httpx
from dotenv import load_dotenv
from fastapi import FastAPI, File, Header, HTTPException, Path as ApiPath, UploadFile
from fastapi import Query
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from PIL import Image, UnidentifiedImageError
from pydantic import BaseModel
from starlette.concurrency import run_in_threadpool

from api.detector import FireSmokeDetector, Prediction

load_dotenv(Path(__file__).with_name(".env"))

MAX_UPLOAD_BYTES = int(os.getenv("MAX_UPLOAD_BYTES", str(5 * 1024 * 1024)))
MAX_IMAGE_PIXELS = int(os.getenv("MAX_IMAGE_PIXELS", "40000000"))
MODEL_IMAGE_SIZE = int(os.getenv("MODEL_IMAGE_SIZE", "640"))
SUPPORTED_IMAGE_TYPES = {
    "JPEG": ("image/jpeg", "jpg"),
    "PNG": ("image/png", "png"),
    "WEBP": ("image/webp", "webp"),
}
logger = logging.getLogger(__name__)
detector: FireSmokeDetector | None = None


class DetectionBox(BaseModel):
    class_id: int
    class_name: str
    confidence: float
    x1: float
    y1: float
    x2: float
    y2: float


class DetectionResponse(BaseModel):
    camera_id: str
    width: int
    height: int
    inference_time_ms: float
    has_fire: bool
    has_smoke: bool
    detections: list[DetectionBox]
    warning: str


class SnapshotResponse(BaseModel):
    camera_id: str
    bucket: str
    path: str
    content_type: str
    size_bytes: int
    uploaded_at: datetime


def supabase_configuration() -> tuple[str, str, str] | None:
    supabase_url = os.getenv("SUPABASE_URL", "").strip().rstrip("/")
    service_role_key = os.getenv("SUPABASE_SERVICE_ROLE_KEY", "").strip()
    storage_bucket = os.getenv("SUPABASE_STORAGE_BUCKET", "").strip()

    if not supabase_url or not service_role_key or not storage_bucket:
        return None

    return supabase_url, service_role_key, storage_bucket


def allowed_origins() -> list[str]:
    configured_origins = os.getenv("CORS_ORIGINS", "")
    origins = [origin.strip() for origin in configured_origins.split(",") if origin.strip()]

    return origins or [
        "http://localhost",
        "http://127.0.0.1",
        "http://localhost:8000",
        "http://127.0.0.1:8000",
        "http://127.0.0.1:8138",
        "http://127.0.0.1:8139",
    ]


app = FastAPI(
    title="JALAS Camera API",
    description=(
        "API untuk menerima gambar kamera berkala dan menyimpannya ke Supabase Storage."
    ),
    version="0.1.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=allowed_origins(),
    allow_credentials=False,
    allow_methods=["GET", "POST"],
    allow_headers=["Content-Type", "X-API-Key"],
)


@app.get("/health")
async def health() -> JSONResponse:
    model_path = configured_model_path()
    detection_configured = (
        model_path.is_file() and importlib.util.find_spec("ultralytics") is not None
    )

    return JSONResponse(
        status_code=200,
        content={
            "status": "ok",
            "service": "jalas-camera-api",
            "detection_configured": detection_configured,
            "supabase_configured": supabase_configuration() is not None,
        },
    )


def configured_model_path() -> Path:
    model_path = Path(os.getenv("FIRE_MODEL_PATH", "models/best.pt")).expanduser()

    if not model_path.is_absolute():
        model_path = Path(__file__).parent / model_path

    return model_path


def get_detector() -> FireSmokeDetector:
    global detector

    if detector is not None:
        return detector

    model_path = configured_model_path()

    if not model_path.is_file():
        raise HTTPException(
            status_code=503,
            detail=(
                "File model YOLO belum ditemukan. Letakkan best.pt di api/models/ "
                "atau atur FIRE_MODEL_PATH di api/.env."
            ),
        )

    if importlib.util.find_spec("ultralytics") is None:
        raise HTTPException(
            status_code=503,
            detail="Dependensi YOLO belum dipasang. Pasang api/requirements-detection.txt.",
        )

    try:
        detector = FireSmokeDetector(model_path, image_size=MODEL_IMAGE_SIZE)
    except Exception as error:
        logger.exception("Gagal memuat model deteksi kebakaran dari %s.", model_path)
        raise HTTPException(
            status_code=503,
            detail="Model YOLO gagal dimuat. Periksa file model dan dependensinya.",
        ) from error

    return detector


async def upload_to_supabase(
    *,
    supabase_url: str,
    service_role_key: str,
    storage_bucket: str,
    object_path: str,
    content: bytes,
    content_type: str,
    upsert: bool = False,
) -> None:
    encoded_bucket = quote(storage_bucket, safe="")
    encoded_path = quote(object_path, safe="/")
    storage_url = (
        f"{supabase_url}/storage/v1/object/{encoded_bucket}/{encoded_path}"
    )
    headers = {
        "apikey": service_role_key,
        "Authorization": "Bearer " + service_role_key,
        "Content-Type": content_type,
        "x-upsert": str(upsert).lower(),
    }

    try:
        async with httpx.AsyncClient(timeout=20.0) as client:
            response = await client.post(storage_url, content=content, headers=headers)
    except httpx.RequestError as error:
        raise HTTPException(
            status_code=502,
            detail="Tidak dapat menghubungi Supabase Storage.",
        ) from error

    if response.status_code not in (200, 201):
        raise HTTPException(
            status_code=502,
            detail="Supabase Storage menolak unggahan gambar.",
        )


def require_camera_api_configuration(x_api_key: str | None) -> tuple[str, str, str]:
    configured_api_key = os.getenv("CAMERA_API_KEY", "").strip()
    configuration = supabase_configuration()

    if configuration is None or not configured_api_key:
        raise HTTPException(
            status_code=503,
            detail="API belum dikonfigurasi untuk Supabase Storage.",
        )

    if x_api_key is None or not hmac.compare_digest(x_api_key, configured_api_key):
        raise HTTPException(status_code=401, detail="API key tidak valid.")

    return configuration


async def validate_image_upload(file: UploadFile) -> tuple[bytes, str, str]:
    content = await file.read(MAX_UPLOAD_BYTES + 1)

    if not content:
        raise HTTPException(status_code=400, detail="Berkas gambar kosong.")

    if len(content) > MAX_UPLOAD_BYTES:
        raise HTTPException(
            status_code=413,
            detail=f"Ukuran gambar melebihi batas {MAX_UPLOAD_BYTES} byte.",
        )

    try:
        with warnings.catch_warnings():
            warnings.simplefilter("error", Image.DecompressionBombWarning)
            with Image.open(BytesIO(content)) as image:
                image_format = image.format
                width, height = image.size

                if width * height > MAX_IMAGE_PIXELS:
                    raise HTTPException(
                        status_code=413,
                        detail=f"Resolusi gambar melebihi batas {MAX_IMAGE_PIXELS} piksel.",
                    )

                image.verify()
    except HTTPException:
        raise
    except (
        Image.DecompressionBombError,
        Image.DecompressionBombWarning,
        UnidentifiedImageError,
        OSError,
        SyntaxError,
    ) as error:
        raise HTTPException(
            status_code=415,
            detail="Berkas harus berupa gambar JPEG, PNG, atau WebP yang valid.",
        ) from error

    image_metadata = SUPPORTED_IMAGE_TYPES.get(image_format or "")

    if image_metadata is None:
        raise HTTPException(
            status_code=415,
            detail="Format gambar yang didukung: JPEG, PNG, dan WebP.",
        )

    content_type, extension = image_metadata

    if file.content_type not in (None, "application/octet-stream", content_type):
        raise HTTPException(
            status_code=415,
            detail="Tipe konten tidak sesuai dengan format gambar.",
        )

    return content, content_type, extension


@app.post(
    "/api/v1/cameras/{camera_id}/snapshots",
    response_model=SnapshotResponse,
    status_code=201,
)
async def upload_camera_snapshot(
    camera_id: str = ApiPath(pattern=r"^[A-Za-z0-9_-]{1,64}$"),
    file: UploadFile = File(...),
    x_api_key: str | None = Header(default=None, alias="X-API-Key"),
) -> SnapshotResponse:
    supabase_url, service_role_key, storage_bucket = require_camera_api_configuration(x_api_key)
    content, content_type, extension = await validate_image_upload(file)
    object_path = f"{camera_id}/{uuid4().hex}.{extension}"

    await upload_to_supabase(
        supabase_url=supabase_url,
        service_role_key=service_role_key,
        storage_bucket=storage_bucket,
        object_path=object_path,
        content=content,
        content_type=content_type,
        upsert=False,
    )

    return SnapshotResponse(
        camera_id=camera_id,
        bucket=storage_bucket,
        path=object_path,
        content_type=content_type,
        size_bytes=len(content),
        uploaded_at=datetime.now(timezone.utc),
    )


@app.post(
    "/api/v1/cameras/{camera_id}/detect",
    response_model=DetectionResponse,
)
async def detect_camera_frame(
    camera_id: str = ApiPath(pattern=r"^[A-Za-z0-9_-]{1,64}$"),
    file: UploadFile = File(...),
    confidence: float = Query(default=0.25, ge=0.05, le=0.95),
) -> DetectionResponse:
    content, _, _ = await validate_image_upload(file)
    active_detector = get_detector()
    prediction: Prediction = await run_in_threadpool(
        active_detector.predict,
        content,
        confidence,
    )

    return DetectionResponse(
        camera_id=camera_id,
        **prediction,
        warning=(
            "Deteksi hanya berdasarkan frame ini dan bukan jaminan bahwa area aman."
        ),
    )


@app.post(
    "/api/v1/cameras/{camera_id}/live-frame",
    response_model=SnapshotResponse,
)
async def upload_live_camera_frame(
    camera_id: str = ApiPath(pattern=r"^[A-Za-z0-9_-]{1,64}$"),
    file: UploadFile = File(...),
    x_api_key: str | None = Header(default=None, alias="X-API-Key"),
) -> SnapshotResponse:
    supabase_url, service_role_key, storage_bucket = require_camera_api_configuration(x_api_key)
    content, content_type, extension = await validate_image_upload(file)
    object_path = f"{camera_id}/latest.{extension}"

    await upload_to_supabase(
        supabase_url=supabase_url,
        service_role_key=service_role_key,
        storage_bucket=storage_bucket,
        object_path=object_path,
        content=content,
        content_type=content_type,
        upsert=True,
    )

    return SnapshotResponse(
        camera_id=camera_id,
        bucket=storage_bucket,
        path=object_path,
        content_type=content_type,
        size_bytes=len(content),
        uploaded_at=datetime.now(timezone.utc),
    )
