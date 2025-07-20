<template>
  <span 
    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
    :class="[
      size === 'sm' ? 'px-1.5 py-0.5 text-xs' : 'px-2 py-1 text-xs',
      getStatusClass(status)
    ]"
  >
    {{ getStatusLabel(status) }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Define props with defaults
const props = withDefaults(defineProps<{
  status: string; // Using string instead of enum to avoid type errors
  size?: 'sm' | 'md';
}>(), {
  size: 'md'
});

// Validate status
if (!['open', 'pending', 'closed', 'resolved'].includes(props.status)) {
  console.warn(`StatusBadge received invalid status: ${props.status}`);
}

// Status labels with index signature for string keys
const statusLabels: { [key: string]: string } = {
  open: 'Open',
  pending: 'Pending',
  closed: 'Closed',
  resolved: 'Resolved'
};

// Status classes for styling with updated color palette
const statusClasses: { [key: string]: string } = {
  open: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
  pending: 'bg-amber-50 text-amber-700 border border-amber-200',
  resolved: 'bg-blue-50 text-blue-700 border border-blue-200',
  closed: 'bg-zinc-100 text-zinc-700 border border-zinc-200'
};

// Fallback for unknown status
const getStatusLabel = (status: string): string => {
  return statusLabels[status] || status;
};

const getStatusClass = (status: string): string => {
  return statusClasses[status] || 'bg-zinc-100 text-zinc-700 border border-zinc-200';
};
</script>
