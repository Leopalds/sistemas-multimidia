from __future__ import annotations
from typing import List, Optional, Tuple
import numpy as np
import face_recognition
import cv2
from loguru import logger

from .config import RecognizerConfig
from .models import BBox, MatchResult, DetectionResult, VideoHit, VideoProcessingResult
from .db import FaceRepo


class FaceService:
    def __init__(self, repo: FaceRepo, cfg: RecognizerConfig):
        self.repo = repo
        self.cfg = cfg

    @staticmethod
    def _bgr_to_rgb(frame):
        """Converte frame de BGR (OpenCV) para RGB (face_recognition)"""
        if frame is None or frame.size == 0:
            return None
        
        # Verificar se o frame tem 3 canais
        if len(frame.shape) != 3 or frame.shape[2] != 3:
            return None
            
        # Converter BGR para RGB
        return cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

    @staticmethod
    def _locations_to_bboxes(face_locations):
        return [BBox(top=t, right=r, bottom=b, left=l) for (t, r, b, l) in face_locations]

    def _best_match(self, encoding: np.ndarray):
        db_encs, person_ids = self.repo.load_all_encodings()
        if db_encs.shape[0] == 0:
            return None, None, float("inf")
        dists = np.linalg.norm(db_encs - encoding.astype(np.float32), axis=1)
        idx = int(np.argmin(dists))
        best_dist = float(dists[idx])
        best_pid = person_ids[idx]
        name = self.repo.person_name(best_pid)
        if best_dist <= self.cfg.threshold:
            return best_pid, name, best_dist
        return None, None, best_dist

    def process_image(self, media_id: int, image_path: str) -> DetectionResult:
        image = face_recognition.load_image_file(f"../storage/app/public/{image_path}")
        
        # Verificar se a imagem foi carregada corretamente
        if image is None or image.size == 0:
            raise RuntimeError(f"Imagem não pôde ser carregada: {image_path}")
        
        # Garantir que a imagem está no formato correto (RGB)
        if len(image.shape) != 3 or image.shape[2] != 3:
            raise RuntimeError(f"Imagem deve ser RGB com 3 canais: {image_path}")
        
        try:
            # Debug: verificar formato da imagem
            logger.debug(f"Processando imagem: shape={image.shape}, dtype={image.dtype}")
            logger.debug(f"Configuração: model={self.cfg.model}, upsample={self.cfg.upsample}")
            
            # Validar modelo
            if self.cfg.model not in ["hog", "cnn"]:
                raise ValueError(f"Modelo inválido: {self.cfg.model}. Use 'hog' ou 'cnn'")
            
            # Validar upsample
            if not isinstance(self.cfg.upsample, int) or self.cfg.upsample < 1:
                raise ValueError(f"Upsample inválido: {self.cfg.upsample}. Deve ser um inteiro >= 1")
            
            # Garantir que a imagem está no formato correto para o dlib
            if image.dtype != np.uint8:
                image = image.astype(np.uint8)
                logger.debug(f"Convertido dtype para uint8: {image.dtype}")
            
            face_locations = face_recognition.face_locations(image, number_of_times_to_upsample=self.cfg.upsample, model=self.cfg.model)
            logger.debug(f"Faces encontradas: {len(face_locations)}")
            
            # Verificar se há faces antes de tentar gerar encodings
            if not face_locations:
                logger.info(f"Nenhuma face encontrada na imagem {image_path}")
                return DetectionResult(media_id=media_id, media_path=image_path, detections=[])
            
            # Verificar formato das localizações das faces
            valid_locations = []
            for i, loc in enumerate(face_locations):
                if isinstance(loc, tuple) and len(loc) == 4:
                    valid_locations.append(loc)
                else:
                    logger.warning(f"Formato inválido de localização da face {i}: {loc}")
            
            if not valid_locations:
                logger.info(f"Nenhuma localização válida de face na imagem {image_path}")
                return DetectionResult(media_id=media_id, media_path=image_path, detections=[])
            
            encodings = face_recognition.face_encodings(image, known_face_locations=valid_locations)
            logger.debug(f"Encodings gerados: {len(encodings)}")
            
        except Exception as e:
            logger.error(f"Erro ao processar faces na imagem {image_path}: {str(e)}")
            logger.error(f"Tipo de erro: {type(e).__name__}")
            raise RuntimeError(f"Erro ao processar faces na imagem {image_path}: {str(e)}")

        detections: List[MatchResult] = []
        for loc, enc in zip(valid_locations, encodings):
            person_id, name, dist = self._best_match(enc)
            source = f"image:{image_path}"
            if person_id is None:
                person_id = self.repo.add_person(None)
                self.repo.add_embedding(person_id, enc, source)
            else:
                self.repo.add_embedding(person_id, enc, source)

            bbox = self._locations_to_bboxes([loc])[0]
            detections.append(MatchResult(person_id=person_id, name=name, distance=dist, bbox=bbox))

        return DetectionResult(media_id=media_id, media_path=image_path, detections=detections)

    def process_video(self, media_id: int, video_path: str, frame_skip: int = 5) -> VideoProcessingResult:
        cap = cv2.VideoCapture(f"../storage/app/public/{video_path}")
        if not cap.isOpened():
            raise RuntimeError(f"Não foi possível abrir o vídeo: {video_path}")

        fps = cap.get(cv2.CAP_PROP_FPS) or 30.0
        result = VideoProcessingResult(media_id=media_id, media_path=video_path, fps=float(fps), frame_skip=int(frame_skip))

        frame_idx = -1
        while True:
            ok, frame_bgr = cap.read()
            if not ok:
                break
            frame_idx += 1
            if frame_skip > 0 and (frame_idx % (frame_skip + 1) != 0):
                continue

            frame_rgb = self._bgr_to_rgb(frame_bgr)
            
            # Verificar se o frame foi convertido corretamente
            if frame_rgb is None or frame_rgb.size == 0:
                continue
                
            # Garantir que o frame está no formato correto (RGB)
            if len(frame_rgb.shape) != 3 or frame_rgb.shape[2] != 3:
                continue
            
            try:
                # Debug: verificar formato do frame
                logger.debug(f"Processando frame {frame_idx}: shape={frame_rgb.shape}, dtype={frame_rgb.dtype}")
                
                # Validar modelo
                if self.cfg.model not in ["hog", "cnn"]:
                    logger.warning(f"Modelo inválido no frame {frame_idx}: {self.cfg.model}")
                    continue
                
                # Garantir que o frame está no formato correto para o dlib
                if frame_rgb.dtype != np.uint8:
                    frame_rgb = frame_rgb.astype(np.uint8)
                    logger.debug(f"Convertido dtype do frame {frame_idx} para uint8: {frame_rgb.dtype}")
                
                face_locations = face_recognition.face_locations(frame_rgb, number_of_times_to_upsample=self.cfg.upsample, model=self.cfg.model)
                if not face_locations:
                    continue

                logger.debug(f"Faces encontradas no frame {frame_idx}: {len(face_locations)}")
                
                # Verificar formato das localizações das faces
                valid_locations = []
                for i, loc in enumerate(face_locations):
                    if isinstance(loc, tuple) and len(loc) == 4:
                        valid_locations.append(loc)
                    else:
                        logger.warning(f"Formato inválido de localização da face {i} no frame {frame_idx}: {loc}")
                
                if not valid_locations:
                    logger.debug(f"Nenhuma localização válida de face no frame {frame_idx}")
                    continue
                
                encodings = face_recognition.face_encodings(frame_rgb, known_face_locations=valid_locations)
                logger.debug(f"Encodings gerados no frame {frame_idx}: {len(encodings)}")
                
            except Exception as e:
                logger.warning(f"Erro ao processar faces no frame {frame_idx} do vídeo {video_path}: {str(e)}")
                logger.warning(f"Tipo de erro: {type(e).__name__}")
                continue
            timestamp_s = frame_idx / fps

            for loc, enc in zip(valid_locations, encodings):
                person_id, name, dist = self._best_match(enc)
                source = f"video:{video_path}@{timestamp_s:.2f}s"
                if person_id is None:
                    person_id = self.repo.add_person(None)
                    self.repo.add_embedding(person_id, enc, source)
                else:
                    self.repo.add_embedding(person_id, enc, source)

                bbox = self._locations_to_bboxes([loc])[0]
                # persistir hit (se a tabela existir)
                try:
                    self.repo.record_video_hit(
                        media_id=media_id,
                        person_id=person_id,
                        frame_index=frame_idx,
                        timestamp_s=timestamp_s,
                        bbox=(bbox.top, bbox.right, bbox.bottom, bbox.left),
                        distance=dist,
                    )
                except Exception:
                    # tabela pode não existir; ignore se não quiser usar agora
                    pass

                result.hits.append(
                    VideoHit(
                        media_id=media_id,
                        frame_index=frame_idx,
                        timestamp_s=timestamp_s,
                        match=MatchResult(person_id=person_id, name=name, distance=dist, bbox=bbox),
                    )
                )

        cap.release()
        return result
