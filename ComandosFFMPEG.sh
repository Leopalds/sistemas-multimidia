# Instalação e configuração do FFmpeg
ffmpeg -version

# Captura e Codificação do Áudio e Vídeo em tempo real
ffmpeg \
    -f avfoundation -framerate 30 -video_size 1280x720 -i "0:1" \
    -t 00:03:00 -map 0:v:0 -c:v libx265 -crf 28 video_h265.mp4 \
    -t 00:03:00 -map 0:a:0 -c:a aac -b:a 192k -ac 2 audio_aac.m4a


# Transcodificação do vídeo
ffmpeg -i video_h265.mp4 -c:v libx264 video_h264.mp4


# Multiplexação de Conteúdo em MPEG-2 TS
ffmpeg -i video_h264.mp4 -i audio_aac.m4a -c:v copy -c:a copy -f mpegts video_audio_multiplexed.ts

# Multiplexação de Conteúdo em DASH com Alternativas de Qualidade de Vídeo e Áudio
# 1080p
ffmpeg -i video_h265.mp4 -c:v libx265 -preset fast -s 1920x1080 -an saida_1080p.mp4

#720i
ffmpeg -i video_h265.mp4 -c:v libx265 -preset fast -s 1280x720 -flags +ilme+ildct -an saida_720i.mp4

# 480p 
ffmpeg -i video_h265.mp4 -c:v libx265 -preset fast -s 720x480 -an saida_480p.mp4

# Estéreo (cópia)
ffmpeg -i audio_aac.m4a -c:a aac -b:a 192k -ac 2 audio_stereo.m4a
# Mono
ffmpeg -i audio_aac.m4a -c:a aac -b:a 96k -ac 1 audio_mono.m4a

ffmpeg -y -i audio_mono.m4a -i audio_stereo.m4a  -i saida_480p.mp4  -i saida_720i.mp4  -i saida_1080p.mp4 -c copy -map 0:a:0 -map 1:a:0 -map 2:v:0 -map 3:v:0 -map 4:v:0 -f dash -single_file 1 -single_file_name stream-\$RepresentationID\$.m4s -hls_playlist 1 -adaptation_sets "id=0,streams=0 id=1,streams=1 id=2,streams=2 id=3,streams=3 id=4,streams=4" dash/manifest.mpd 

# Streaming de Conteúdo
# RTP
ffmpeg -re -i video_audio_multiplexed.ts -c copy -f rtp_mpegts rtp://127.0.0.1:1234

# HTTP
cd dash
python3 -m http.server 8080

# Teste do Conteúdo e do Streaming
# RTP
ffplay rtp://127.0.0.1:1234 


# 7.2 HTTP
# Acessar http://localhost:8080/manifest.mpd pelo VLC
