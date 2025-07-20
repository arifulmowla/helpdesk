<template>
  <span 
    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
    :class="[
      size === 'sm' ? 'px-1.5 py-0.5 text-xs' : 'px-2 py-1 text-xs',
      priorityClasses[priority]
    ]"
  >
    {{ priorityLabels[priority] }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Define props with defaults
const props = withDefaults(defineProps<{
  priority: 'low' | 'medium' | 'high';
  size?: 'sm' | 'md';
}>(), {
  size: 'md'
});

// Priority labels
const priorityLabels = {
  low: 'Low',
  medium: 'Medium',
  high: 'High'
};

// Priority classes for styling with updated color palette
const priorityClasses = {
  low: 'bg-sky-50 text-sky-700 border border-sky-200',
  medium: 'bg-violet-50 text-violet-700 border border-violet-200',
  high: 'bg-rose-50 text-rose-700 border border-rose-200'
};
</script>
