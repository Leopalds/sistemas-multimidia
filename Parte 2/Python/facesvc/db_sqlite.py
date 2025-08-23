# facesvc/db_sqlite.py
from __future__ import annotations
from typing import List, Optional, Tuple
import os
import sqlite3
import numpy as np

def enc_to_blob(encoding: np.ndarray) -> bytes:
    enc32 = encoding.astype(np.float32, copy=False)
    return enc32.tobytes(order="C")

def blob_to_enc(blob: bytes) -> np.ndarray:
    arr = np.frombuffer(blob, dtype=np.float32)
    return arr.reshape((128,))

class FaceRepo:
    """
    Repositório usando as tabelas do Laravel em SQLite:
      - people (id, name, created_at, updated_at)
      - faces  (id, person_id, encoding BLOB, source, created_at, updated_at)
      - video_hits (opcional)
    """
    def __init__(self, db_path):
        print(f"SQLite path: {db_path}")
        self.conn = sqlite3.connect(db_path, check_same_thread=False)
        self.conn.execute("PRAGMA journal_mode=WAL;")
        self.conn.execute("PRAGMA synchronous=NORMAL;")

    # --- people ---
    def add_person(self, name: Optional[str]) -> int:
        cur = self.conn.cursor()
        cur.execute(
            "INSERT INTO people (name, created_at, updated_at) VALUES (?, datetime('now'), datetime('now'))",
            (name,)
        )
        self.conn.commit()
        pid = cur.lastrowid
        cur.close()
        return int(pid)

    def person_name(self, person_id: int) -> Optional[str]:
        row = self.conn.execute("SELECT name FROM people WHERE id=?", (person_id,)).fetchone()
        return row[0] if row else None

    def update_person_name(self, person_id: int, name: str) -> None:
        self.conn.execute(
            "UPDATE people SET name=?, updated_at=datetime('now') WHERE id=?",
            (name, person_id)
        )
        self.conn.commit()

    # --- faces ---
    def add_embedding(self, person_id: int, encoding: np.ndarray, source: Optional[str]) -> int:
        cur = self.conn.cursor()
        cur.execute(
            "INSERT INTO faces (person_id, encoding, source, created_at, updated_at) "
            "VALUES (?, ?, ?, datetime('now'), datetime('now'))",
            (person_id, enc_to_blob(encoding), source)
        )
        self.conn.commit()
        fid = cur.lastrowid
        cur.close()
        return int(fid)

    def load_all_encodings(self) -> Tuple[np.ndarray, List[int]]:
        rows = self.conn.execute("SELECT encoding, person_id FROM faces").fetchall()
        if not rows:
            return np.empty((0, 128), dtype=np.float32), []
        encs = np.vstack([blob_to_enc(b) for (b, _) in rows])
        pids = [int(pid) for (_, pid) in rows]
        return encs, pids

    # --- video_hits (opcional) ---
    def record_video_hit(
        self,
        media_id: int,
        person_id: int,
        frame_index: int,
        timestamp_s: float,
        bbox: Tuple[int, int, int, int],  # (top, right, bottom, left)
        distance: float,
    ) -> None:
        # Só insere se a tabela existir
        try:
            t, r, b, l = map(int, bbox)
            self.conn.execute(
                "INSERT INTO video_hits (media_id, person_id, frame_index, timestamp_s, left, top, right, bottom, distance, created_at, updated_at) "
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))",
                (media_id, person_id, frame_index, float(timestamp_s), l, t, r, b, float(distance))
            )
            self.conn.commit()
        except sqlite3.OperationalError:
            # tabela não existe — ignore
            pass
