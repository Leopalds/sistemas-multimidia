from dataclasses import dataclass

@dataclass
class RecognizerConfig:
    threshold: float = 0.6
    model: str = "hog"   # "hog" ou "cnn"
    upsample: int = 1
