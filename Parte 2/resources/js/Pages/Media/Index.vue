<template>
    <AppLayout>
        <Head title="Mídias" />
    <div class="max-w-5xl mx-auto p-6">
      <h1 class="text-3xl font-semibold text-center mb-6">Todas as Mídias Enviadas</h1>

      <div v-if="$page?.props?.flash?.success" class="mb-4 text-green-600">
        {{ $page.props.flash.success }}
      </div>
  
      <div v-if="media.length === 0" class="text-center text-gray-500">
        Nenhuma mídia foi enviada.
      </div>
  
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <div v-for="(item, index) in media" :key="index" class="flex flex-col items-center relative group" @mouseenter="hover = index" @mouseleave="hover = null">
          <template v-if="item.type === 'photo'">
            <img :src="`/storage/${item.path}`" alt="Imagem" class="h-48 w-full object-cover rounded-lg" />
          </template>
          <template v-else-if="item.type === 'video'">
            <video :src="`/storage/${item.path}`" class="h-48 w-full object-cover rounded-lg" />
          </template>
          
          <!-- Overlay com informações e ações -->
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 rounded-lg flex items-center justify-center">
            <div v-if="hover === index" class="flex gap-2">
              <Link 
                :href="route('media.show', { media: item.id })"
                class="bg-blue-500 text-white px-3 py-2 rounded-md hover:bg-blue-600 transition-colors"
              >
                <i class="fa-solid fa-eye mr-1"></i>
                Ver
              </Link>
              <button 
                class="bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 transition-colors"
                @click="deleteMedia(item.id)"
              >
                <i class="fa-solid fa-trash mr-1"></i>
                Excluir
              </button>
            </div>
          </div>
          
          <!-- Informações da mídia -->
          <div class="mt-2 text-center w-full">
            <p class="text-xs text-gray-600 break-all mb-1">{{ item.path.split('/').pop() }}</p>
            <div class="flex items-center justify-center gap-2 text-xs">
              <span class="px-2 py-1 rounded-full" :class="{
                'bg-blue-100 text-blue-800': item.type === 'photo',
                'bg-purple-100 text-purple-800': item.type === 'video'
              }">
                {{ item.type === 'photo' ? 'Imagem' : 'Vídeo' }}
              </span>
              <span class="px-2 py-1 rounded-full" :class="{
                'bg-green-100 text-green-800': item.status === 'processed',
                'bg-yellow-100 text-yellow-800': item.status === 'pending',
                'bg-red-100 text-red-800': item.status === 'failed'
              }">
                {{ item.status }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    </AppLayout>
    
  </template>
  
  <script setup>
  import { Head, router, Link } from '@inertiajs/vue3'
  import AppLayout from '@/Layouts/AppLayout.vue'
  import { defineProps, ref } from 'vue'
  import axios from 'axios'
  
  const props = defineProps({
    media: Array,
  })

  const hover = ref(null)

  const deleteMedia = (id) => {
    if (confirm('Tem certeza que deseja excluir esta mídia?')) {
      axios.delete(route('media.destroy', { media: id }), { headers: { Accept: 'application/json' } })
      .then((res) => {
        // Recarrega a página atual para atualizar a lista, preservando scroll
        router.reload({ only: ['media'], preserveScroll: true })
      })
      .catch(error => {
        console.error(error)
      })
    }
  }
</script>
  
<style scoped>
</style>
  