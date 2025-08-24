<template>
    <AppLayout>
        <Head title="Adicionar Pessoa" />
        
        <div class="max-w-2xl mx-auto p-6">
            <div class="mb-6">
                <Link 
                    :href="route('people.index')"
                    class="text-blue-500 hover:text-blue-700 flex items-center gap-2"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Voltar para Pessoas
                </Link>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Adicionar Nova Pessoa</h1>

                <form @submit.prevent="submitForm">
                    <!-- Nome -->
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Nome da Pessoa *
                        </label>
                        <input 
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Digite o nome da pessoa"
                        />
                        <div v-if="errors.name" class="text-red-500 text-sm mt-1">
                            {{ errors.name }}
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div class="mb-6">
                        <label for="thumbnail_path" class="block text-sm font-medium text-gray-700 mb-2">
                            Caminho da Thumbnail
                        </label>
                        <input 
                            id="thumbnail_path"
                            v-model="form.thumbnail_path"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Caminho para a imagem da pessoa (opcional)"
                        />
                        <p class="text-sm text-gray-500 mt-1">
                            Deixe em branco para usar apenas o nome
                        </p>
                        <div v-if="errors.thumbnail_path" class="text-red-500 text-sm mt-1">
                            {{ errors.thumbnail_path }}
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex gap-4">
                        <button 
                            type="submit"
                            :disabled="processing"
                            class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50"
                        >
                            <span v-if="processing">
                                <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                                Salvando...
                            </span>
                            <span v-else>
                                <i class="fa-solid fa-save mr-2"></i>
                                Salvar Pessoa
                            </span>
                        </button>
                        
                        <Link 
                            :href="route('people.index')"
                            class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors"
                        >
                            Cancelar
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    errors: Object,
})

const processing = ref(false)

const form = useForm({
    name: '',
    thumbnail_path: '',
})

const submitForm = () => {
    processing.value = true
    
    form.post(route('people.store'), {
        onSuccess: () => {
            processing.value = false
        },
        onError: () => {
            processing.value = false
        },
    })
}
</script>
