from __future__ import annotations
from dataclasses import dataclass
from typing import List, Optional, Tuple
from pydantic import BaseModel, Field

class BBox(BaseModel):
    top: int
    right: int
    bottom: int
    left: int

class MatchResult(BaseModel):
    person_id: int
    name: Optional[str]
    distance: float
    bbox: BBox

class DetectionResult(BaseModel):
    media_id: int
    media_path: str
    detections: List[MatchResult]

class VideoHit(BaseModel):
    media_id: int
    frame_index: int
    timestamp_s: float
    match: MatchResult

class VideoProcessingResult(BaseModel):
    media_id: int
    media_path: str
    fps: float
    frame_skip: int
    hits: List[VideoHit] = Field(default_factory=list)
