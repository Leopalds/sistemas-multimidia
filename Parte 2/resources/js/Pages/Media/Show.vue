<template>
    <AppLayout>
        <Head title="Visualizar Mídia" />

        <div class="max-w-5xl mx-auto p-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Cabeçalho com informações da mídia -->
                        <div class="mb-6">
                            <h1 class="text-2xl font-bold mb-2">{{ media.path.split('/').pop() }}</h1>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                    {{ media.type === 'photo' ? 'Imagem' : 'Vídeo' }}
                                </span>
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full">
                                    {{ media.status }}
                                </span>
                                <span>{{ new Date(media.created_at).toLocaleDateString('pt-BR') }}</span>
                            </div>
                        </div>

                        <!-- Visualização da mídia -->
                        <div class="mb-6">
                            <div v-if="media.type === 'photo'" class="relative inline-block">
                                <img 
                                    :src="`/storage/${media.path}`" 
                                    :alt="media.path"
                                    class="max-w-full h-auto rounded-lg shadow-lg"
                                />
                                
                                <!-- Overlay com bounding boxes para pessoas -->
                                <div 
                                    v-for="(detection, index) in detections" 
                                    :key="index"
                                    class="absolute border-2 border-red-500 bg-red-500 bg-opacity-20"
                                    :style="{
                                        left: `${detection.bbox?.left || 0}%`,
                                        top: `${detection.bbox?.top || 0}%`,
                                        width: `${(detection.bbox?.right || 0) - (detection.bbox?.left || 0)}%`,
                                        height: `${(detection.bbox?.bottom || 0) - (detection.bbox?.top || 0)}%`
                                    }"
                                >
                                    <div class="absolute -top-8 left-0 bg-red-500 text-white px-2 py-1 rounded text-xs">
                                        {{ detection.person_name || 'Pessoa Desconhecida' }}
                                    </div>
                                </div>
                            </div>

                            <div v-else-if="media.type === 'video'" class="space-y-4">
                                <video 
                                    :src="`/storage/${media.path}`" 
                                    controls
                                    class="w-full rounded-lg shadow-lg"
                                    @timeupdate="onVideoTimeUpdate"
                                ></video>
                                
                                <!-- Controles de frame para vídeo -->
                                <div class="flex items-center gap-4">
                                    <label class="text-sm font-medium">Frame atual:</label>
                                    <input 
                                        type="range" 
                                        :min="0" 
                                        :max="getMaxFrame()" 
                                        v-model="currentFrame"
                                        @input="onFrameChange"
                                        class="flex-1"
                                    />
                                    <span class="text-sm text-gray-600">{{ currentFrame || 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Lista de pessoas identificadas -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-4">Pessoas Identificadas</h3>
                            
                            <div v-if="media.type === 'photo'" class="space-y-3">
                                <div 
                                    v-for="(detection, index) in detections" 
                                    :key="index"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                            {{ detection.person_name ? detection.person_name.charAt(0) : '?' }}
                                        </div>
                                        <div>
                                            <div class="font-medium">
                                                <span v-if="!detection.editing">
                                                    {{ detection.person_name || 'Pessoa Desconhecida' }}
                                                    <button 
                                                        @click="startEditing(index)"
                                                        class="ml-2 text-blue-500 hover:text-blue-700 text-sm"
                                                    >
                                                        <i class="fa-solid fa-edit"></i>
                                                    </button>
                                                </span>
                                                <div v-else class="flex items-center gap-2">
                                                    <input 
                                                        v-model="detection.editName"
                                                        type="text"
                                                        class="px-2 py-1 border border-gray-300 rounded text-sm"
                                                        placeholder="Nome da pessoa"
                                                        @keyup.enter="savePersonName(detection)"
                                                        @keyup.esc="cancelEditing(index)"
                                                    />
                                                    <button 
                                                        @click="savePersonName(detection)"
                                                        class="text-green-500 hover:text-green-700"
                                                    >
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                    <button 
                                                        @click="cancelEditing(index)"
                                                        class="text-red-500 hover:text-red-700"
                                                    >
                                                        <i class="fa-solid fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                Confiança: {{ (100 - (detection.distance || 0)).toFixed(1) }}%
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: {{ detection.person_id }}
                                    </div>
                                </div>
                                
                                <div v-if="detections.length === 0" class="text-center py-8 text-gray-500">
                                    Nenhuma pessoa identificada nesta imagem
                                </div>
                            </div>

                            <div v-else-if="media.type === 'video'" class="space-y-3">
                                <div 
                                    v-for="(hits, frameIndex) in getCurrentFrameHits()" 
                                    :key="frameIndex"
                                    class="p-3 bg-gray-50 rounded-lg"
                                >
                                    <div class="font-medium mb-2">Frame {{ frameIndex }}</div>
                                    <div class="space-y-2">
                                        <div 
                                            v-for="hit in hits" 
                                            :key="hit.id"
                                            class="flex items-center justify-between p-2 bg-white rounded border"
                                        >
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                                    {{ hit.person?.name ? hit.person.name.charAt(0) : '?' }}
                                                </div>
                                                <div>
                                                    <div class="font-medium">{{ hit.person?.name || 'Pessoa Desconhecida' }}</div>
                                                    <div class="text-sm text-gray-600">
                                                        Confiança: {{ (100 - (hit.distance || 0)).toFixed(1) }}%
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ (hit.timestamp_s || 0).toFixed(2) }}s
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="Object.keys(getCurrentFrameHits()).length === 0" class="text-center py-8 text-gray-500">
                                    Nenhuma pessoa identificada no frame atual
                                </div>
                            </div>
                        </div>

                        <!-- Botões de ação -->
                        <div class="flex gap-4">
                            <Link 
                                :href="route('media.index')"
                                class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                            >
                                Voltar
                            </Link>
                            
                            <button 
                                v-if="media.status === 'pending'"
                                @click="enqueueProcessing"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                            >
                                Processar Mídia
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    media: Object,
    detections: Array,
    videoHits: Object
})

const currentFrame = ref(0)

const getMaxFrame = () => {
    if (props.media.type === 'video' && props.videoHits) {
        const frames = Object.keys(props.videoHits).map(Number)
        return frames.length > 0 ? Math.max(...frames) : 0
    }
    return 0
}

const getCurrentFrameHits = () => {
    if (props.media.type === 'video' && props.videoHits) {
        const frameHits = props.videoHits[currentFrame.value] || []
        return frameHits.length > 0 ? { [currentFrame.value]: frameHits } : {}
    }
    return {}
}

const onVideoTimeUpdate = (event) => {
    // Atualiza o frame baseado no tempo do vídeo
    const video = event.target
    const fps = props.media.meta?.fps || 30
    const frameIndex = Math.floor(video.currentTime * fps)
    currentFrame.value = frameIndex
}

const onFrameChange = () => {
    // Atualiza o tempo do vídeo baseado no frame selecionado
    const video = document.querySelector('video')
    if (video) {
        const fps = props.media.meta?.fps || 30
        video.currentTime = currentFrame.value / fps
    }
}

const enqueueProcessing = async () => {
    try {
        const response = await fetch(`/enqueue/${props.media.id}`)
        if (response.ok) {
            window.location.reload()
        }
    } catch (error) {
        console.error('Erro ao enfileirar processamento:', error)
    }
}

const startEditing = (index) => {
    props.detections[index].editing = true
    props.detections[index].editName = props.detections[index].person_name
}

const savePersonName = async (detection) => {
    try {
        const response = await fetch(`/api/people/${detection.person_id}/name`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ name: detection.editName })
        })

        if (response.ok) {
            detection.person_name = detection.editName
            detection.editing = false
        } else {
            alert('Erro ao salvar o nome da pessoa')
        }
    } catch (error) {
        console.error('Erro ao salvar nome:', error)
        alert('Erro ao salvar o nome da pessoa')
    }
}

const cancelEditing = (index) => {
    props.detections[index].editing = false
    props.detections[index].editName = props.detections[index].person_name
}
</script>
