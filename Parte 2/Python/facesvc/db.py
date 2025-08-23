from __future__ import annotations
from typing import List, Optional, Tuple
import numpy as np
import os
import mysql.connector  # ou psycopg2, conforme seu Laravel

# Helpers p/ BLOB <-> numpy
def enc_to_blob(encoding: np.ndarray) -> bytes:
    enc32 = encoding.astype(np.float32, copy=False)
    return enc32.tobytes(order="C")

def blob_to_enc(blob: bytes) -> np.ndarray:
    arr = np.frombuffer(blob, dtype=np.float32)
    return arr.reshape((128,))

class FaceRepo:
    """
    Repositório que fala diretamente com as tabelas do Laravel:
      - people (id, name)
      - faces  (id, person_id, encoding, source)
      - video_hits (opcional)
    """
    def __init__(self):
        self.cnx = mysql.connector.connect(
            host=os.getenv("DB_HOST", "127.0.0.1"),
            port=int(os.getenv("DB_PORT", "3306")),
            user=os.getenv("DB_USERNAME", "root"),
            password=os.getenv("DB_PASSWORD", ""),
            database=os.getenv("DB_DATABASE", "laravel"),
        )
        self.cnx.autocommit = True

    # --- people ---
    def add_person(self, name: Optional[str]) -> int:
        cur = self.cnx.cursor()
        cur.execute("INSERT INTO people (name, created_at, updated_at) VALUES (%s, NOW(), NOW())", (name,))
        pid = cur.lastrowid
        cur.close()
        return int(pid)

    def person_name(self, person_id: int) -> Optional[str]:
        cur = self.cnx.cursor()
        cur.execute("SELECT name FROM people WHERE id=%s", (person_id,))
        row = cur.fetchone()
        cur.close()
        return row[0] if row else None

    def update_person_name(self, person_id: int, name: str) -> None:
        cur = self.cnx.cursor()
        cur.execute("UPDATE people SET name=%s, updated_at=NOW() WHERE id=%s", (name, person_id))
        cur.close()

    # --- faces (embeddings) ---
    def add_embedding(self, person_id: int, encoding: np.ndarray, source: Optional[str]) -> int:
        cur = self.cnx.cursor()
        cur.execute(
            "INSERT INTO faces (person_id, encoding, source, created_at, updated_at) VALUES (%s, %s, %s, NOW(), NOW())",
            (person_id, enc_to_blob(encoding), source)
        )
        fid = cur.lastrowid
        cur.close()
        return int(fid)

    def load_all_encodings(self) -> Tuple[np.ndarray, List[int]]:
        cur = self.cnx.cursor()
        cur.execute("SELECT encoding, person_id FROM faces")
        rows = cur.fetchall()
        cur.close()
        if not rows:
            return np.empty((0, 128), dtype=np.float32), []
        encs = np.vstack([blob_to_enc(b) for (b, _) in rows])
        pids = [int(pid) for (_, pid) in rows]
        return encs, pids

    # --- video hits (opcional) ---
    def record_video_hit(
        self,
        media_id: int,
        person_id: int,
        frame_index: int,
        timestamp_s: float,
        bbox: Tuple[int, int, int, int],  # (top, right, bottom, left)
        distance: float,
    ) -> None:
        # Assumindo tabela video_hits como sugeri
        cur = self.cnx.cursor()
        t, r, b, l = map(int, bbox)
        cur.execute(
            """INSERT INTO video_hits
               (media_id, person_id, frame_index, timestamp_s, left, top, right, bottom, distance, created_at, updated_at)
               VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s, NOW(), NOW())""",
            (media_id, person_id, frame_index, float(timestamp_s), l, t, r, b, float(distance))
        )
        cur.close()
