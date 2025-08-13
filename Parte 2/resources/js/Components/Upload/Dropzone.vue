<template>
  <div
    class="flex flex-col items-center gap-6 rounded-lg border-2 border-dashed border-[#cedae8] px-6 py-14 cursor-pointer"
    @drop.prevent="handleDrop"
    @dragover.prevent
    @click="triggerFileInputClick"
  >
    <div class="flex max-w-[480px] flex-col items-center gap-2">
      <p class="text-[#0d141c] text-lg font-bold leading-tight tracking-[-0.015em] max-w-[480px] text-center">
        Arraste e solte arquivos aqui
      </p>
      <p class="text-[#0d141c] text-sm font-normal leading-normal max-w-[480px] text-center">
        Ou clique para selecionar arquivos do seu computador
      </p>
    </div>
    <button
      type="button"
      class="flex min-w-[84px] max-w-[480px] items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-[#e7edf4] text-[#0d141c] text-sm font-bold leading-normal tracking-[0.015em]"
      @click.stop="triggerFileInputClick"
    >
      <span class="truncate">Selecionar Arquivos</span>
    </button>

    <input
      ref="fileInput"
      type="file"
      class="hidden"
      multiple
      accept="image/*,video/*"
      @change="handleFileChange"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['files-selected'])

const fileInput = ref(null)

function triggerFileInputClick() {
  fileInput.value?.click()
}

function handleFileChange(event) {
  const files = Array.from(event.target.files || [])
  emit('files-selected', files)
}

function handleDrop(event) {
  const files = Array.from(event.dataTransfer?.files || [])
  emit('files-selected', files)
}
</script> 