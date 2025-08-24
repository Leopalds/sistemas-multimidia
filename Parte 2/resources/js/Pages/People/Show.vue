<template>
    <AppLayout>
        <Head :title="`Pessoa: ${person.name || `Pessoa #${person.id}`}`" />
        
        <div class="max-w-7xl mx-auto p-6">
            <div class="mb-6">
                <Link 
                    :href="route('people.index')"
                    class="text-blue-500 hover:text-blue-700 flex items-center gap-2"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar para Pessoas
                </Link>
            </div>

            <!-- Cabeçalho da pessoa -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center gap-6">
                    <!-- Avatar -->
                    <div class="w-24 h-24 bg-blue-500 rounded-full flex items-center justify-center text-white text-4xl font-bold">
                        {{ person.name ? person.name.charAt(0).toUpperCase() : '?' }}
                    </div>
                    
                    <!-- Informações -->
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">
                            {{ person.name || `Pessoa #${person.id}` }}
                        </h1>
                        <div class="flex items-center gap-4 text-sm text-gray-600">
                            <span>ID: {{ person.id }}</span>
                            <span>Criada em: {{ new Date(person.created_at).toLocaleDateString('pt-BR') }}</span>
                            <span>Total de detecções: {{ mediaDetections.total || mediaDetections.length }}</span>
                        </div>
                    </div>
                    
                    <!-- Ações -->
                    <div class="flex gap-2">
                        <Link 
                            :href="route('people.edit', { person: person.id })"
                            class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition-colors"
                        >
                            <i class="fa-solid fa-edit mr-2"></i>
                            Editar
                        </Link>
                        
                        <button 
                            @click="deletePerson"
                            class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors"
                        >
                            <i class="fa-solid fa-trash mr-2"></i>
                            Excluir
                        </button>
                    </div>
                </div>
            </div>

            <!-- Lista de mídias onde a pessoa aparece -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Mídias onde aparece</h2>
                    
                    <!-- Seletor de itens por página -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Itens por página:</label>
                        <select 
                            @change="changePerPage"
                            v-model="perPage"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
                
                <div v-if="mediaDetections.data && mediaDetections.data.length === 0" class="text-center py-8 text-gray-500">
                    <i class="fa-solid fa-photo-film text-4xl mb-4 text-gray-300"></i>
                    <p>Esta pessoa ainda não foi detectada em nenhuma mídia.</p>
                </div>
                
                <div v-else-if="mediaDetections.data" class="space-y-4">
                    <div 
                        v-for="detection in mediaDetections.data" 
                        :key="detection.media.id"
                        class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-photo-film text-gray-500"></i>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900">
                                        {{ detection.media.path.split('/').pop() }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ detection.media.type === 'photo' ? 'Imagem' : 'Vídeo' }} • <span v-if="detection.media.type === 'video'">{{ detection.total_frames }} frame{{ detection.total_frames !== 1 ? 's' : '' }}</span>
                                    </p>
                                </div>
                            </div>
                            
                            <Link 
                                :href="route('media.show', { media: detection.media.id })"
                                class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition-colors"
                            >
                                Ver Mídia
                            </Link>
                        </div>
                        
                        <!-- Estatísticas -->
                        <div class="grid grid-cols-3 gap-4 text-sm text-gray-600">
                            <div>
                                <span class="font-medium">Primeira detecção:</span><br>
                                {{ new Date(detection.first_seen).toLocaleString('pt-BR') }}
                            </div>
                            <div>
                                <span class="font-medium">Última detecção:</span><br>
                                {{ new Date(detection.last_seen).toLocaleString('pt-BR') }}
                            </div>
                            <div>
                                <span class="font-medium">Status:</span><br>
                                <span class="px-2 py-1 rounded-full text-xs" :class="{
                                    'bg-green-100 text-green-800': detection.media.status === 'processed',
                                    'bg-yellow-100 text-yellow-800': detection.media.status === 'pending',
                                    'bg-red-100 text-red-800': detection.media.status === 'failed'
                                }">
                                    {{ detection.media.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paginação -->
                <div v-if="mediaDetections.data && mediaDetections.last_page > 1" class="mt-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Mostrando {{ mediaDetections.from }} a {{ mediaDetections.to }} de {{ mediaDetections.total }} resultados
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <!-- Botão Anterior -->
                            <Link 
                                v-if="mediaDetections.prev_page_url"
                                :href="`${route('people.show', { person: person.id })}?page=${mediaDetections.current_page - 1}&per_page=${perPage}`"
                                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                <i class="fa-solid fa-chevron-left mr-1"></i>
                                Anterior
                            </Link>
                            
                            <!-- Páginas -->
                            <div class="flex items-center gap-1">
                                <Link 
                                    v-for="page in getPageNumbers()"
                                    :key="page"
                                    :href="`${route('people.show', { person: person.id })}?page=${page}&per_page=${perPage}`"
                                    class="px-3 py-2 text-sm font-medium rounded-md"
                                    :class="{
                                        'text-blue-600 bg-blue-50 border border-blue-300': page === mediaDetections.current_page,
                                        'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50': page !== mediaDetections.current_page
                                    }"
                                >
                                    {{ page }}
                                </Link>
                            </div>
                            
                            <!-- Botão Próximo -->
                            <Link 
                                v-if="mediaDetections.next_page_url"
                                :href="`${route('people.show', { person: person.id })}?page=${mediaDetections.current_page + 1}&per_page=${perPage}`"
                                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                            >
                                Próximo
                                <i class="fa-solid fa-chevron-right ml-1"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    person: Object,
    mediaDetections: Object,
})

const perPage = ref(10)

onMounted(() => {
    // Inicializa o perPage com o valor atual da URL ou padrão
    const urlParams = new URLSearchParams(window.location.search)
    const perPageParam = urlParams.get('per_page')
    if (perPageParam) {
        perPage.value = parseInt(perPageParam)
    }
})

const deletePerson = () => {
    if (confirm('Tem certeza que deseja excluir esta pessoa? Todas as detecções relacionadas serão removidas.')) {
        router.delete(route('people.destroy', { person: props.person.id }))
    }
}

const changePerPage = () => {
    // Redireciona para a primeira página com o novo perPage
    router.get(route('people.show', { person: props.person.id }), { 
        per_page: perPage.value,
        page: 1 
    })
}

const getPageNumbers = () => {
    const current = props.mediaDetections.current_page
    const last = props.mediaDetections.last_page
    const delta = 2
    
    const range = []
    const rangeWithDots = []
    
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i)
    }
    
    if (current - delta > 2) {
        rangeWithDots.push(1, '...')
    } else {
        rangeWithDots.push(1)
    }
    
    rangeWithDots.push(...range)
    
    if (current + delta < last - 1) {
        rangeWithDots.push('...', last)
    } else {
        rangeWithDots.push(last)
    }
    
    return rangeWithDots.filter(page => page !== 1 || current !== 1)
}
</script>
