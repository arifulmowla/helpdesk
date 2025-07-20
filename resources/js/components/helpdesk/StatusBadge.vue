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

// Status classes for styling with updated color palette
const statusClasses = {
  open: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
  pending: 'bg-amber-50 text-amber-700 border border-amber-200',
  closed: 'bg-zinc-100 text-zinc-700 border border-zinc-200'
};
</script>
