import asyncio
from io import BytesIO
from types import SimpleNamespace

import httpx
from fastapi.testclient import TestClient
from PIL import Image

from api import main
from api.detector import FireSmokeDetector


client = TestClient(main.app)


def configure_api(monkeypatch) -> None:
    monkeypatch.setenv("SUPABASE_URL", "https://example.supabase.co")
    monkeypatch.setenv("SUPABASE_SERVICE_ROLE_KEY", "test-service-role-key")
    monkeypatch.setenv("SUPABASE_STORAGE_BUCKET", "camera-snapshots")
    monkeypatch.setenv("CAMERA_API_KEY", "test-camera-api-key")


def image_bytes(image_format: str = "JPEG") -> bytes:
    image_data = BytesIO()

    with Image.new("RGB", (4, 4), color="forestgreen") as image:
        image.save(image_data, format=image_format)

    return image_data.getvalue()


def test_health_reports_detector_and_supabase_readiness(monkeypatch) -> None:
    monkeypatch.delenv("SUPABASE_URL", raising=False)
    monkeypatch.delenv("SUPABASE_SERVICE_ROLE_KEY", raising=False)
    monkeypatch.delenv("SUPABASE_STORAGE_BUCKET", raising=False)
    monkeypatch.delenv("CAMERA_API_KEY", raising=False)
    monkeypatch.setenv("FIRE_MODEL_PATH", "missing-best.pt")

    response = client.get("/health")

    assert response.status_code == 200
    assert response.json()["status"] == "ok"
    assert response.json()["detection_configured"] is False
    assert response.json()["supabase_configured"] is False


def test_detection_requires_a_model_file(monkeypatch, tmp_path) -> None:
    monkeypatch.setenv("FIRE_MODEL_PATH", str(tmp_path / "missing-best.pt"))

    response = client.post(
        "/api/v1/cameras/cam-01/detect",
        files={"file": ("frame.jpg", image_bytes(), "image/jpeg")},
    )

    assert response.status_code == 503
    assert "model YOLO belum ditemukan" in response.json()["detail"]


def test_detection_returns_model_boxes(monkeypatch) -> None:
    class FakeDetector:
        def predict(self, image_bytes: bytes, confidence: float) -> dict[str, object]:
            assert image_bytes
            assert confidence == 0.3

            return {
                "width": 4,
                "height": 4,
                "inference_time_ms": 8.5,
                "has_fire": True,
                "has_smoke": False,
                "detections": [
                    {
                        "class_id": 0,
                        "class_name": "fire",
                        "confidence": 0.94,
                        "x1": 0.0,
                        "y1": 0.0,
                        "x2": 3.0,
                        "y2": 3.0,
                    }
                ],
            }

    monkeypatch.setattr(main, "get_detector", lambda: FakeDetector())

    response = client.post(
        "/api/v1/cameras/cam-01/detect?confidence=0.3",
        files={"file": ("frame.jpg", image_bytes(), "image/jpeg")},
    )

    assert response.status_code == 200
    assert response.json()["has_fire"] is True
    assert response.json()["detections"][0]["class_name"] == "fire"
    assert "bukan jaminan" in response.json()["warning"]


def test_detector_returns_fire_and_smoke_boxes() -> None:
    class FakeTensor:
        def __init__(self, values: list[list[float]] | list[float]) -> None:
            self.values = values

        def cpu(self) -> FakeTensor:
            return self

        def tolist(self) -> list[list[float]] | list[float]:
            return self.values

    class FakeBoxes:
        xyxy = FakeTensor([[0, 0, 2, 2], [1, 1, 4, 4]])
        conf = FakeTensor([0.9, 0.8])
        cls = FakeTensor([0, 1])

    class FakeModel:
        names = {0: "fire", 1: "smoke"}

        def predict(self, **kwargs: object) -> list[SimpleNamespace]:
            assert kwargs["source"].size == (4, 4)
            assert kwargs["conf"] == 0.25
            assert kwargs["imgsz"] == 640
            assert kwargs["verbose"] is False

            return [SimpleNamespace(boxes=FakeBoxes())]

    detector = FireSmokeDetector.__new__(FireSmokeDetector)
    detector._model = FakeModel()
    detector._image_size = 640

    prediction = detector.predict(image_bytes(), confidence=0.25)

    assert prediction["has_fire"] is True
    assert prediction["has_smoke"] is True
    assert [box["class_name"] for box in prediction["detections"]] == ["fire", "smoke"]
    assert prediction["width"] == 4
    assert prediction["height"] == 4


def test_snapshot_requires_api_key(monkeypatch) -> None:
    configure_api(monkeypatch)

    response = client.post(
        "/api/v1/cameras/cam-01/snapshots",
        files={"file": ("snapshot.jpg", image_bytes(), "image/jpeg")},
    )

    assert response.status_code == 401


def test_snapshot_rejects_non_image_files(monkeypatch) -> None:
    configure_api(monkeypatch)

    response = client.post(
        "/api/v1/cameras/cam-01/snapshots",
        headers={"X-API-Key": "test-camera-api-key"},
        files={"file": ("snapshot.jpg", b"not an image", "image/jpeg")},
    )

    assert response.status_code == 415


def test_snapshot_uploads_valid_image_to_private_storage(monkeypatch) -> None:
    configure_api(monkeypatch)
    uploaded: dict[str, object] = {}

    async def fake_upload_to_supabase(**kwargs) -> None:
        uploaded.update(kwargs)

    monkeypatch.setattr(main, "upload_to_supabase", fake_upload_to_supabase)

    response = client.post(
        "/api/v1/cameras/cam-01/snapshots",
        headers={"X-API-Key": "test-camera-api-key"},
        files={"file": ("snapshot.jpg", image_bytes(), "image/jpeg")},
    )

    assert response.status_code == 201
    assert response.json()["camera_id"] == "cam-01"
    assert response.json()["bucket"] == "camera-snapshots"
    assert response.json()["path"].startswith("cam-01/")
    assert uploaded["content_type"] == "image/jpeg"
    assert uploaded["service_role_key"] == "test-service-role-key"
    assert uploaded["upsert"] is False


def test_live_frame_overwrites_latest_image_in_storage(monkeypatch) -> None:
    configure_api(monkeypatch)
    uploaded: dict[str, object] = {}

    async def fake_upload_to_supabase(**kwargs) -> None:
        uploaded.update(kwargs)

    monkeypatch.setattr(main, "upload_to_supabase", fake_upload_to_supabase)

    response = client.post(
        "/api/v1/cameras/cam-01/live-frame",
        headers={"X-API-Key": "test-camera-api-key"},
        files={"file": ("frame.jpg", image_bytes(), "image/jpeg")},
    )

    assert response.status_code == 200
    assert response.json()["path"] == "cam-01/latest.jpg"
    assert response.json()["content_type"] == "image/jpeg"
    assert uploaded["object_path"] == "cam-01/latest.jpg"
    assert uploaded["upsert"] is True


def test_supabase_upload_sends_service_role_authorization(monkeypatch) -> None:
    requests: list[httpx.Request] = []
    real_async_client = httpx.AsyncClient

    async def capture_request(request: httpx.Request) -> httpx.Response:
        requests.append(request)

        return httpx.Response(200)

    class MockAsyncClient:
        def __init__(self, timeout: float) -> None:
            self.client = real_async_client(
                transport=httpx.MockTransport(capture_request),
                timeout=timeout,
            )

        async def __aenter__(self) -> httpx.AsyncClient:
            return await self.client.__aenter__()

        async def __aexit__(self, *args: object) -> None:
            await self.client.__aexit__(*args)

        async def post(self, *args: object, **kwargs: object) -> httpx.Response:
            return await self.client.post(*args, **kwargs)

    monkeypatch.setattr(main.httpx, "AsyncClient", MockAsyncClient)

    asyncio.run(
        main.upload_to_supabase(
            supabase_url="https://example.supabase.co",
            service_role_key="test-service-role-key",
            storage_bucket="camera-snapshots",
            object_path="cam-01/latest.jpg",
            content=image_bytes(),
            content_type="image/jpeg",
            upsert=True,
        )
    )

    assert len(requests) == 1
    assert requests[0].headers["Authorization"] == "Bearer test-service-role-key"
    assert requests[0].headers["x-upsert"] == "true"
