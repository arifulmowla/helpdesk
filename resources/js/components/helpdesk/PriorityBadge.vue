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

// Define props
const props = defineProps<{
  priority: 'low' | 'medium' | 'high';
  size?: 'sm' | 'md';
}>();

// Default props
withDefaults(defineProps<{
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

// Priority classes for styling
const priorityClasses = {
  low: 'bg-blue-100 text-blue-800',
  medium: 'bg-orange-100 text-orange-800',
  high: 'bg-red-100 text-red-800'
};
</script>
