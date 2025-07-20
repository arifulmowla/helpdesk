<template>
  <span 
    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
    :class="[
      size === 'sm' ? 'px-1.5 py-0.5 text-xs' : 'px-2 py-1 text-xs',
      getPriorityClass(priority)
    ]"
  >
    {{ getPriorityLabel(priority) }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Define props with defaults
const props = withDefaults(defineProps<{
  priority: string; // Using string instead of enum to avoid type errors
  size?: 'sm' | 'md';
}>(), {
  size: 'md'
});

// Validate priority
if (!['low', 'medium', 'high', 'urgent'].includes(props.priority)) {
  console.warn(`PriorityBadge received invalid priority: ${props.priority}`);
}

// Priority labels with index signature for string keys
const priorityLabels: { [key: string]: string } = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  urgent: 'Urgent'
};

// Priority classes for styling with updated color palette
const priorityClasses: { [key: string]: string } = {
  low: 'bg-sky-50 text-sky-700 border border-sky-200',
  medium: 'bg-violet-50 text-violet-700 border border-violet-200',
  high: 'bg-rose-50 text-rose-700 border border-rose-200',
  urgent: 'bg-red-50 text-red-700 border border-red-200'
};

// Fallback for unknown priority
const getPriorityLabel = (priority: string): string => {
  return priorityLabels[priority] || priority;
};

const getPriorityClass = (priority: string): string => {
  return priorityClasses[priority] || 'bg-zinc-100 text-zinc-700 border border-zinc-200';
};
</script>
