<template>
  <Head title="Enviar Mídias" />
  <AppLayout>
    <div class="relative flex size-full flex-col overflow-x-hidden">
      <div class="px-40 flex flex-1 justify-center py-5">
        <div class="layout-content-container flex flex-col max-w-[960px] flex-1">
          <div class="flex flex-wrap justify-between gap-3 p-4">
            <div class="flex min-w-72 flex-col gap-3">
              <p class="text-[#0d141c] tracking-light text-[32px] font-bold leading-tight">Enviar Mídias</p>
              <p class="text-[#49709c] text-sm">Selecione fotos e vídeos do seu dispositivo para enviar para PhoTool.</p>
            </div>
          </div>

          <div class="flex flex-col p-4">
            <Dropzone @files-selected="onFilesSelected" />
          </div>

          <!-- Previews -->
          <div v-if="form.files && form.files.length" class="px-4">
            <h2 class="font-semibold text-lg mb-2">Arquivos Selecionados</h2>
            <ul class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <li v-for="(file, index) in form.files" :key="index" class="flex flex-col items-center">
                <div v-if="file.type?.startsWith('image')">
                  <img :src="createObjectURL(file)" alt="prévia" class="h-24 w-24 object-cover rounded-lg" />
                </div>
                <div v-else-if="file.type?.startsWith('video')">
                  <video :src="createObjectURL(file)" class="h-24 w-24 object-cover rounded-lg" controls />
                </div>
                <span class="text-center text-xs mt-2 break-all">{{ file.name }}</span>
              </li>
            </ul>
          </div>

          <!-- Progresso -->
          <div class="flex flex-col gap-3 p-4" v-if="isUploading">
            <div class="flex gap-6 justify-between">
              <p class="text-[#0d141c] text-base font-medium">Progresso do Envio</p>
              <p class="text-[#49709c] text-sm">{{ progress }}%</p>
            </div>
            <ProgressBar :value="progress" />
          </div>

          <div class="p-4 flex justify-end">
            <button
              type="button"
              class="flex min-w-[84px] max-w-[240px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-[#0d78f2] text-white text-sm font-bold"
              :disabled="processing || !form.files.length"
              @click="submit"
            >
              {{ processing ? 'Enviando...' : 'Enviar' }}
            </button>
          </div>

          <!-- Feedback -->
          <div v-if="$page?.props?.flash?.success" class="px-4 text-green-600">
            {{ $page.props.flash.success }}
          </div>
          <div v-if="error" class="px-4 text-red-600">
            {{ error }}
          </div>
          <Toaster ref="toaster" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import Dropzone from '@/Components/Upload/Dropzone.vue'
import ProgressBar from '@/Components/Upload/ProgressBar.vue'
import Toaster from '@/Components/Toaster.vue'

const form = useForm({ files: [] })

const processing = ref(false)
const error = ref(null)
const toaster = ref(null)
const progress = ref(0)
const isUploading = ref(false)

function onFilesSelected(files) {
  form.files = files
}

function createObjectURL(file) {
  return URL.createObjectURL(file)
}

function submit() {
  if (!form.files.length) {
    error.value = 'Selecione ao menos um arquivo antes de enviar.'
    toaster.value?.showError(error.value)
    return
  }

  processing.value = true
  error.value = null
  isUploading.value = true
  progress.value = 0

  form.post(route('media.store'), {
    forceFormData: true,
    onProgress: (event) => {
      // Axios UploadProgressEvent: event.percentage já vem normalizado 0-100 por Inertia
      if (typeof event?.percentage === 'number') {
        progress.value = Math.round(event.percentage)
      }
    },
    onFinish: () => {
      processing.value = false
      isUploading.value = false
      progress.value = 0
      form.reset()
    },
    onError: () => {
      processing.value = false
      isUploading.value = false
      error.value = 'Ocorreu um erro ao enviar os arquivos.'
      toaster.value?.showError(error.value)
    },
  })
}
</script>
