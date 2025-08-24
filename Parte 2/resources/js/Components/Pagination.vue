<template>
    <div v-if="pagination && pagination.last_page > 1" class="mt-6">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} resultados
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Botão Anterior -->
                <Link 
                    v-if="pagination.prev_page_url"
                    :href="getPageUrl(pagination.current_page - 1)"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                >
                    <i class="fa-solid fa-chevron-left mr-1"></i>
                    Anterior
                </Link>
                
                <!-- Páginas -->
                <div class="flex items-center gap-1">
                    <Link 
                        v-for="page in getPageNumbers()"
                        :key="page"
                        :href="getPageUrl(page)"
                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="{
                            'text-blue-600 bg-blue-50 border border-blue-300': page === pagination.current_page,
                            'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50': page !== pagination.current_page
                        }"
                    >
                        {{ page }}
                    </Link>
                </div>
                
                <!-- Botão Próximo -->
                <Link 
                    v-if="pagination.next_page_url"
                    :href="getPageUrl(pagination.current_page + 1)"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                >
                    Próximo
                    <i class="fa-solid fa-chevron-right ml-1"></i>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    pagination: Object,
    baseUrl: String,
    queryParams: {
        type: Object,
        default: () => ({})
    }
})

const getPageUrl = (page) => {
    const params = new URLSearchParams({
        page: page.toString(),
        ...props.queryParams
    })
    
    return `${props.baseUrl}?${params.toString()}`
}

const getPageNumbers = () => {
    const current = props.pagination.current_page
    const last = props.pagination.last_page
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
