<template>
    <AppLayout>
        <Head title="Pessoas Identificadas" />
        
        <div class="max-w-7xl mx-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Pessoas Identificadas</h1>
                <Link 
                    :href="route('people.create')"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors"
                >
                    <i class="fa-solid fa-plus mr-2"></i>
                    Adicionar Pessoa
                </Link>
            </div>

            <!-- Mensagens de sucesso -->
            <div v-if="$page?.props?.flash?.success" class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                {{ $page.props.flash.success }}
            </div>

            <!-- Barra de pesquisa -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="relative flex-1 max-w-md">
                        <input 
                            type="text" 
                            v-model="searchQuery"
                            @input="searchPeople"
                            placeholder="Buscar pessoas por nome..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <i class="fa-solid fa-search absolute right-3 top-3 text-gray-400"></i>
                    </div>
                    
                    <!-- Seletor de itens por página -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Pessoas por página:</label>
                        <select 
                            @change="changePerPage"
                            v-model="perPage"
                            class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="6">6</option>
                            <option value="12">12</option>
                            <option value="24">24</option>
                            <option value="48">48</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Lista de pessoas -->
            <div v-if="people.data && people.data.length === 0" class="text-center py-12 text-gray-500">
                <i class="fa-solid fa-users text-6xl mb-4 text-gray-300"></i>
                <p class="text-xl">Nenhuma pessoa foi identificada ainda.</p>
                <p class="text-sm mt-2">Faça upload de mídias para começar a identificar pessoas.</p>
            </div>

            <div v-else-if="people.data" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div 
                    v-for="person in people.data" 
                    :key="person.id"
                    class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6"
                >
                    <!-- Avatar da pessoa -->
                    <div class="flex justify-center mb-4">
                        <div class="w-20 h-20 bg-blue-500 rounded-full flex items-center justify-center text-white text-2xl font-bold">
                            {{ person.name ? person.name.charAt(0).toUpperCase() : '?' }}
                        </div>
                    </div>

                    <!-- Informações da pessoa -->
                    <div class="text-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ person.name || `Pessoa #${person.id}` }}
                        </h3>
                        <p class="text-sm text-gray-600">
                            {{ person.total_detections }} detecç{{ person.total_detections !== 1 ? 'ões' : 'ão' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Criada em {{ new Date(person.created_at).toLocaleDateString('pt-BR') }}
                        </p>
                    </div>

                    <!-- Ações -->
                    <div class="flex gap-2">
                        <Link 
                            :href="route('people.show', { person: person.id })"
                            class="flex-1 bg-blue-500 text-white px-3 py-2 rounded-md hover:bg-blue-600 transition-colors text-center"
                        >
                            <i class="fa-solid fa-eye mr-1"></i>
                            Ver
                        </Link>
                        
                        <Link 
                            :href="route('people.edit', { person: person.id })"
                            class="flex-1 bg-yellow-500 text-white px-3 py-2 rounded-md hover:bg-yellow-600 transition-colors text-center"
                        >
                            <i class="fa-solid fa-edit mr-1"></i>
                            Editar
                        </Link>
                        
                        <button 
                            @click="deletePerson(person.id)"
                            class="flex-1 bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 transition-colors"
                        >
                            <i class="fa-solid fa-trash mr-1"></i>
                            Excluir
                        </button>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <div v-if="people.data && people.last_page > 1" class="mt-8">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Mostrando {{ people.from }} a {{ people.to }} de {{ people.total }} pessoas
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <!-- Botão Anterior -->
                        <Link 
                            v-if="people.prev_page_url"
                            :href="`${route('people.index')}?page=${people.current_page - 1}&per_page=${perPage}`"
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
                                :href="`${route('people.index')}?page=${page}&per_page=${perPage}`"
                                class="px-3 py-2 text-sm font-medium rounded-md"
                                :class="{
                                    'text-blue-600 bg-blue-50 border border-blue-300': page === people.current_page,
                                    'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50': page !== people.current_page
                                }"
                            >
                                {{ page }}
                            </Link>
                        </div>
                        
                        <!-- Botão Próximo -->
                        <Link 
                            v-if="people.next_page_url"
                            :href="`${route('people.index')}?page=${people.current_page + 1}&per_page=${perPage}`"
                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            Próximo
                            <i class="fa-solid fa-chevron-right ml-1"></i>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    people: Object,
})

const hover = ref(null)
const searchQuery = ref('')
const perPage = ref(12) // Default per page

onMounted(() => {
    // Inicializa o perPage com o valor atual da URL ou padrão
    const urlParams = new URLSearchParams(window.location.search)
    const perPageParam = urlParams.get('per_page')
    if (perPageParam) {
        perPage.value = parseInt(perPageParam)
    }
})

const searchPeople = () => {
    if (searchQuery.value.trim()) {
        router.get(route('people.search'), { q: searchQuery.value, per_page: perPage.value }, {
            preserveState: true,
            preserveScroll: true,
        })
    } else {
        router.get(route('people.index'), { per_page: perPage.value }, {
            preserveState: true,
            preserveScroll: true,
        })
    }
}

const changePerPage = () => {
    // Redireciona para a primeira página com o novo perPage
    router.get(route('people.index'), { per_page: perPage.value, page: 1 })
}

const getPageNumbers = () => {
    const current = props.people.current_page
    const last = props.people.last_page
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

const deletePerson = (personId) => {
    if (confirm('Tem certeza que deseja excluir esta pessoa? Todas as detecções relacionadas serão removidas.')) {
        router.delete(route('people.destroy', { person: personId }))
    }
}
</script>
