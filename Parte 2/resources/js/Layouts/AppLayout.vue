<template>
    <div class="min-h-screen flex flex-col">
      <Navbar />
      <main class="flex-1 p-6">
        <slot />
      </main>
    </div>
</template>
  
<script setup>
  import Navbar from '@/Components/Navbar.vue'
</script>
  