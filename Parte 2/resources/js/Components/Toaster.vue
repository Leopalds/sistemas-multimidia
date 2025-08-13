<template>
  <div v-if="isVisible" class="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50">
    <div class="bg-red-600 text-white px-6 py-3 rounded-lg shadow-md flex items-center space-x-3">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12" />
      </svg>
      <span>{{ message }}</span>
      <button @click="close" class="ml-4 text-white hover:text-gray-200">&times;</button>
    </div>
  </div>
</template>

<script setup>
import { ref, defineExpose } from 'vue'

const isVisible = ref(false)
const message = ref('')

function showError(msg) {
  message.value = msg
  isVisible.value = true

  // Fechar automaticamente após 4 segundos
  setTimeout(() => {
    isVisible.value = false
  }, 4000)
}

function close() {
  isVisible.value = false
}

defineExpose({ showError, close })
</script>

<style scoped>
/* Pode adicionar animações ou outros estilos conforme necessário */
</style>
