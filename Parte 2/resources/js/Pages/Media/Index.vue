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
        <div v-for="(item, index) in media" :key="index" class="flex flex-col items-center relative" @mouseenter="hover = index" @mouseleave="hover = null">
          <template v-if="item.type === 'photo'">
            <img :src="`/storage/${item.path}`" alt="Imagem" class="h-48 w-full object-cover rounded-lg" />
          </template>
          <template v-else-if="item.type === 'video'">
            <video :src="`/storage/${item.path}`" class="h-48 w-full object-cover rounded-lg" controls />
          </template>
          <p class="mt-2 text-xs text-center break-all">{{ item.path }}</p>
          
          <div class="absolute top-1 right-1" v-if="hover === index">
            <button class="bg-gray-200 text-white px-2 py-1 rounded-md" @click="deleteMedia(item.id)">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
    </AppLayout>
    
  </template>
  
  <script setup>
  import { Head, router } from '@inertiajs/vue3'
  import AppLayout from '@/Layouts/AppLayout.vue'
  import { defineProps, ref } from 'vue'
  import axios from 'axios'
  
  const props = defineProps({
    media: Array,
  })

  const hover = ref(null)

  const deleteMedia = (id) => {
    axios.delete(route('media.destroy', { media: id }), { headers: { Accept: 'application/json' } })
    .then((res) => {
      // Recarrega a página atual para atualizar a lista, preservando scroll
      router.reload({ only: ['media'], preserveScroll: true })
    })
    .catch(error => {
      console.error(error)
    })
  }
</script>
  
<style scoped>
</style>
  