#!/usr/bin/env python3
import os
import json
import time
from typing import Any, Dict

import redis
import requests
from loguru import logger

from facesvc.config import RecognizerConfig
from facesvc.db_sqlite import FaceRepo
from facesvc.service import FaceService

from pathlib import Path
from dotenv import load_dotenv

import math
from typing import Any

def _json_safe(value: Any):
    """Converte floats inválidos (NaN/Inf) para None; processa recursivamente dict/list."""
    if isinstance(value, float):
        return value if math.isfinite(value) else None
    if isinstance(value, dict):
        return {k: _json_safe(v) for k, v in value.items()}
    if isinstance(value, list):
        return [_json_safe(v) for v in value]
    return value



# Descobrir caminhos base
BASE_DIR = Path(__file__).resolve().parent       # .../Laravel/Python
PROJECT_ROOT = BASE_DIR.parent                   # .../Laravel

# Carregar .env localizado em Python/.env
load_dotenv(BASE_DIR / ".env")

def resolve_sqlite_path() -> str:
    raw = os.getenv("SQLITE_PATH", "").strip()
    # Remove aspas acidentais no .env
    if (raw.startswith('"') and raw.endswith('"')) or (raw.startswith("'") and raw.endswith("'")):
        raw = raw[1:-1].strip()

    if not raw:
        # fallback para database/database.sqlite dentro do Laravel
        return str((PROJECT_ROOT / "database" / "database.sqlite").resolve())

    p = Path(raw)
    if p.is_absolute():
        return str(p)
    # se vier relativo, resolvemos relativo à raiz do Laravel
    return str((PROJECT_ROOT / p).resolve())


# ------------------------
# Configuração via env
# ------------------------
REDIS_URL = os.getenv("REDIS_URL", "redis://127.0.0.1:6379/0")
QUEUE_KEY = os.getenv("LARAVEL_QUEUE_KEY", "queues:face")  # ex.: queues:default
LARAVEL_API_BASE = os.getenv("LARAVEL_API_BASE", "http://localhost:8000/api")
CALLBACK_TOKEN = os.getenv("CALLBACK_TOKEN")  # se precisar autenticar

THRESHOLD = float(os.getenv("FACE_THRESHOLD", "0.6"))
MODEL = os.getenv("FACE_MODEL", "hog")
UPSAMPLE = int(os.getenv("FACE_UPSAMPLE", "1"))

FRAME_SKIP_DEFAULT = int(os.getenv("FRAME_SKIP", "5"))

# Endpoints REST (ajuste conforme suas rotas)
API_MEDIA_SHOW = f"{LARAVEL_API_BASE}/media/{{media_id}}"
API_MEDIA_UPDATE = f"{LARAVEL_API_BASE}/media/{{media_id}}/processed"

session = requests.Session()
session.headers.update({"Accept": "application/json"})
if CALLBACK_TOKEN:
    session.headers.update({"Authorization": f"Bearer {CALLBACK_TOKEN}"})

# ------------------------
# Helpers
# ------------------------

def decode_laravel_job(raw_payload: str) -> Dict[str, Any]:
    """
    Decodifica payload padrão da Queue do Laravel (driver Redis).
    Espera um JSON com chaves como: 'uuid', 'displayName', 'job', 'data'...
    Dentro de 'data', você define algo como: {'media_id': 123}
    """
    job = json.loads(raw_payload)
    data = job.get("data") or {}
    # Alguns formatos trazem 'command' serializado com a classe do Job;
    # se for o caso, você pode extrair 'media_id' de lá. Aqui assumo 'data.media_id'.
    return data

def fetch_media(media_id: int) -> Dict[str, Any]:
    url = API_MEDIA_SHOW.format(media_id=media_id)
    r = session.get(url, timeout=30)
    r.raise_for_status()
    return r.json()

def post_processed(media_id: int, payload: dict) -> None:
    url = f"{LARAVEL_API_BASE}/media/{media_id}/processed"
    safe_payload = _json_safe(payload)
    print(safe_payload)
    # opcional: garanta Content-Type json (requests define se usar json=)
    r = session.post(url, json=safe_payload, timeout=60)


    try:
        detail = r.json()
    except Exception:
        detail = r.text

    print(detail)
    
    if r.status_code >= 400:
        # log útil para debug
        raise RuntimeError(f"Callback falhou {r.status_code}: {detail}")

# ------------------------
# Loop principal
# ------------------------

def main() -> None:
    r = redis.from_url(REDIS_URL, decode_responses=True)

    sqlite_path = resolve_sqlite_path()
    logger.info(f"Usando SQLite em: {sqlite_path}")
    repo = FaceRepo(sqlite_path)
    svc = FaceService(repo, RecognizerConfig(threshold=THRESHOLD, model=MODEL, upsample=UPSAMPLE))

    logger.info(f"Worker iniciado | Redis={REDIS_URL} | QueueKey={QUEUE_KEY}")

    while True:
        # BRPOP retorna (key, value); bloqueia até 5s pra permitir sinais/graceful shutdown
        item = r.brpop(QUEUE_KEY, timeout=5)
        if item is None:
            continue

        key, raw = item
        try:
            data = json.loads(raw)
            media_id = int(data["media_id"])
            logger.info(f"Job recebido | media_id={media_id}")

            media = fetch_media(media_id)
            # Espera JSON em algo como:
            # { id, type: 'image'|'video', path, meta: {frame_skip?} }
            mtype = media["type"]
            path = media["path"]
            meta = media.get("meta") or {}
            frame_skip = int(meta.get("frame_skip", FRAME_SKIP_DEFAULT))

            if mtype == "photo":
                res = svc.process_image(media_id, path)
                out = {
                    "status": "processed",
                    "detections": [d.model_dump() for d in res.detections],
                }
            elif mtype == "video":
                res = svc.process_video(media_id, path, frame_skip=frame_skip)
                out = {
                    "status": "processed",
                    "fps": res.fps,
                    "frame_skip": res.frame_skip,
                    "hits": [h.model_dump() for h in res.hits],
                }
            else:
                raise ValueError(f"Tipo de mídia não suportado: {mtype}")

            post_processed(media_id, out)
            logger.info(f"Processado com sucesso | media_id={media_id} tipo={mtype}")

        except Exception as e:
            logger.exception(f"Falha ao processar job: {e}")
            # (opcional) notificar Laravel sobre falha
            try:
                # tente extrair media_id se possível
                media_id = int(json.loads(raw).get("data", {}).get("media_id", 0))
                if media_id:
                    post_processed(media_id, {"status": "failed", "error": str(e)})
            except Exception:
                pass

        # pequeno respiro
        time.sleep(0.1)

if __name__ == "__main__":
    main()
