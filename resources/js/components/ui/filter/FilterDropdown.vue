<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="outline" class="w-full justify-between">
        <span v-if="!selectedItems || selectedItems.length === 0">{{ placeholder }}</span>
        <span v-else-if="selectedItems.length === 1">
          <Tag :value="getItemObject(selectedItems[0])" size="sm" class="mr-2" />
          {{ getItemLabel(selectedItems[0]) }}
        </span>
        <span v-else>{{ selectedItems.length }} selected</span>
        <Icon name="chevron-down" class="h-4 w-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent class="w-56">
      <DropdownMenuItem
        v-for="item in options"
        :key="item.value"
        @click="toggleItem(item.value)"
        class="flex items-center cursor-pointer"
      >
        <div class="flex items-center w-4 h-4 mr-2">
          <svg v-if="selectedItems?.includes(item.value)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <Tag :value="item" size="sm" class="mr-2" />
        {{ item.label }}
        <span class="ml-auto text-xs text-gray-500">({{ stats?.[item.value] || 0 }})</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Icon from '@/components/Icon.vue';
import { Tag } from '@/components/ui/tag';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem,
} from '@/components/ui/dropdown-menu';

// Define props
const props = defineProps<{
  selectedItems?: string[];
  options: Array<{ value: string; label: string; color?: string }>;
  placeholder: string;
  stats?: Record<string, number>;
}>();

// Define emits
const emit = defineEmits<{
  (e: 'toggle', value: string): void;
}>();

// Methods
function toggleItem(value: string) {
  emit('toggle', value);
}

function getItemObject(value: string) {
  return props.options.find(item => item.value === value) || { value, label: value };
}

function getItemLabel(value: string) {
  return getItemObject(value).label;
}
</script>
