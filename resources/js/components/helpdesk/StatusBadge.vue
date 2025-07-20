<template>
  <span 
    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
    :class="[
      size === 'sm' ? 'px-1.5 py-0.5 text-xs' : 'px-2 py-1 text-xs',
      statusClasses[status]
    ]"
  >
    {{ statusLabels[status] }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Define props with defaults
const props = withDefaults(defineProps<{
  status: 'open' | 'pending' | 'closed';
  size?: 'sm' | 'md';
}>(), {
  size: 'md'
});

// Status labels
const statusLabels = {
  open: 'Open',
  pending: 'Pending',
  closed: 'Closed'
};

// Status classes for styling
const statusClasses = {
  open: 'bg-green-100 text-green-800',
  pending: 'bg-yellow-100 text-yellow-800',
  closed: 'bg-gray-100 text-gray-800'
};
</script>
