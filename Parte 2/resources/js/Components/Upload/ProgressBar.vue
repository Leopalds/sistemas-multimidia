<template>
  <div class="flex flex-col gap-2">
    <div class="rounded bg-[#cedae8]">
      <div
        class="h-2 rounded bg-[#0d78f2] transition-all duration-300"
        :style="{ width: `${clamped}%` }"
      />
    </div>
    <p class="text-[#49709c] text-sm">{{ clamped }}% complete</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: { type: Number, default: 0 },
})

const clamped = computed(() => {
  if (props.value < 0) return 0
  if (props.value > 100) return 100
  return Math.round(props.value)
})
</script> 