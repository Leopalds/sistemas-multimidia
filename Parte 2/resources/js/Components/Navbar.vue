<template>
    <nav class="bg-gray-800 text-white px-4 py-3 flex justify-between items-center">
      <!-- Logo -->
      <inertia-link :href="route('media.index')">
         <div class="flex items-center gap-2 text-lg font-bold">
            <img src="/logo.svg" alt="PhoTool" class="w-10 h-10 text-white">
            <h1 class="text-2xl font-bold">PhoTool</h1>
        </div>
    </inertia-link>
  
      <!-- Links -->
      <div class="space-x-4">
        <inertia-link :href="route('media.create')" class="hover:underline">Upload</inertia-link>
        <inertia-link :href="route('media.index')" class="hover:underline">Mídias</inertia-link>
        <inertia-link :href="route('people.index')" class="hover:underline">Pessoas</inertia-link>
      </div>
    </nav>
  </template>
  
  <script setup>
  import { Link as InertiaLink } from '@inertiajs/vue3'
  
  // Para usar <inertia-link> como no exemplo
  const inertiaLink = InertiaLink
  </script>
  