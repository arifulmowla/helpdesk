<template>
  <span 
    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium border"
    :class="[
      size === 'sm' ? 'px-1.5 py-0.5 text-xs' : 'px-2 py-1 text-xs',
      tagColor ? '' : tagCssClasses
    ]"
    :style="tagColor ? getColorStyles(tagColor) : {}"
  >
    {{ tagLabel }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

// Define the tag data interface
interface TagData {
  value: string;
  label: string;
  color?: string;
}

// Define props with defaults
const props = withDefaults(defineProps<{
  value: string | TagData; // Can be either a string or an object with label, color, and value
  color?: string; // Optional color override (e.g., from backend)
  size?: 'sm' | 'md';
}>(), {
  size: 'md'
});

// Computed properties to extract values from the prop
const tagValue = computed(() => {
  if (typeof props.value === 'object' && props.value !== null) {
    return props.value.value;
  }
  return props.value;
});

const tagLabel = computed(() => {
  if (typeof props.value === 'object' && props.value !== null) {
    return props.value.label;
  }
  return getTagLabel(props.value);
});

const tagColor = computed(() => {
  // Priority: explicit color prop > object color > computed color
  if (props.color) {
    return props.color;
  }
  if (typeof props.value === 'object' && props.value !== null && props.value.color) {
    // Check if the color is a hex color or CSS classes
    if (props.value.color.startsWith('#') || props.value.color.startsWith('rgb')) {
      return props.value.color;
    }
    // If it's CSS classes, return null to use them in the class binding instead
    return null;
  }
  return null; // Will use computed classes instead
});

const tagCssClasses = computed(() => {
  // If we have an object with CSS classes in the color property, use those
  if (typeof props.value === 'object' && props.value !== null && props.value.color) {
    if (!props.value.color.startsWith('#') && !props.value.color.startsWith('rgb')) {
      return props.value.color;
    }
  }
  // Otherwise, compute classes based on the value
  return getTagClass(tagValue.value);
});

// Priority labels and classes
const priorityLabels: { [key: string]: string } = {
  low: 'Low',
  medium: 'Medium', 
  high: 'High',
  urgent: 'Urgent'
};

const priorityClasses: { [key: string]: string } = {
  low: 'bg-sky-50 text-sky-700 border border-sky-200',
  medium: 'bg-violet-50 text-violet-700 border border-violet-200',
  high: 'bg-rose-50 text-rose-700 border border-rose-200',
  urgent: 'bg-red-50 text-red-700 border border-red-200'
};

// Status labels and classes
const statusLabels: { [key: string]: string } = {
  open: 'Open',
  closed: 'Closed',
  awaiting_customer: 'Awaiting Customer',
  awaiting_agent: 'Awaiting Agent',
  resolved: 'Resolved',
  cancelled: 'Cancelled'
};

const statusClasses: { [key: string]: string } = {
  open: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
  closed: 'bg-zinc-100 text-zinc-700 border border-zinc-200',
  awaiting_customer: 'bg-yellow-50 text-yellow-700 border border-yellow-200',
  awaiting_agent: 'bg-blue-50 text-blue-700 border border-blue-200',
  resolved: 'bg-purple-50 text-purple-700 border border-purple-200',
  cancelled: 'bg-red-100 text-red-700 border border-red-200'
};

// Get appropriate label for the value
const getTagLabel = (value: string): string => {
  // Check if it's a priority first
  if (priorityLabels[value]) {
    return priorityLabels[value];
  }
  // Check if it's a status
  if (statusLabels[value]) {
    return statusLabels[value];
  }
  // Fallback to capitalized value
  return value.charAt(0).toUpperCase() + value.slice(1).replace('_', ' ');
};

// Get appropriate styling class for the value
const getTagClass = (value: string): string => {
  // Check if it's a priority first
  if (priorityClasses[value]) {
    return priorityClasses[value];
  }
  // Check if it's a status
  if (statusClasses[value]) {
    return statusClasses[value];
  }
  // Fallback styling
  return 'bg-zinc-100 text-zinc-700 border-zinc-200';
};

// Convert hex color to RGB
const hexToRgb = (hex: string) => {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result ? {
    r: parseInt(result[1], 16),
    g: parseInt(result[2], 16),
    b: parseInt(result[3], 16)
  } : null;
};

// Generate dynamic styles from color prop
const getColorStyles = (color: string) => {
  const rgb = hexToRgb(color);
  if (!rgb) {
    // Fallback if color parsing fails
    return {
      backgroundColor: color,
      color: 'white',
      borderColor: color
    };
  }
  
  return {
    backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.1)`, // Light background
    color: color, // Text color
    borderColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.3)` // Border color
  };
};
</script>
