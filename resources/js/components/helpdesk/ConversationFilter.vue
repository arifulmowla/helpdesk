<template>
  <div class="p-4 space-y-4 bg-gray-50">
    <!-- Collapsible header - only show if not controlled externally -->
    <button 
      v-if="!hideCollapseButton" 
      @click="toggleFilterCollapse" 
      class="w-full flex justify-between items-center bg-gray-100 p-2 font-medium"
    >
      Filters
      <Icon :name="filterCollapsed ? 'chevron-down' : 'chevron-up'" class="h-4 w-4" />
    </button>
    <div v-show="!filterCollapsed || hideCollapseButton" class="space-y-4">
    
    <!-- Search Input -->
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-700">Search</label>
      <Input
        v-model="searchQuery"
        type="text"
        placeholder="Search conversations..."
        class="w-full"
        @input="debouncedSearch"
      />
    </div>

    <!-- Quick Filters -->
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-700">Quick Filters</label>
      <div class="flex flex-wrap gap-2">
        <Button
          variant="outline"
          size="sm"
          :class="{ 'bg-blue-50 border-blue-200': filters.unread }"
          @click="toggleUnread"
        >
          <div class="w-2 h-2 bg-blue-500 rounded-full mr-1"></div>
          Unread
          <span class="ml-1 text-xs text-gray-500">({{ stats?.unread || 0 }})</span>
        </Button>
        
        <Button
          variant="outline"
          size="sm"
          :class="{ 'bg-red-50 border-red-200': filters.priority?.includes('urgent') }"
          @click="toggleUrgent"
        >
          Urgent
          <span class="ml-1 text-xs text-gray-500">({{ stats?.by_priority?.urgent || 0 }})</span>
        </Button>
      </div>
    </div>

    <!-- Status Filter -->
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-700">Status</label>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button variant="outline" class="w-full justify-between">
            <span v-if="!filters.status || filters.status.length === 0">Select Status</span>
            <span v-else-if="filters.status.length === 1">
              <Tag :value="getStatusObject(filters.status[0])" size="sm" class="mr-2" />
              {{ getStatusLabel(filters.status[0]) }}
            </span>
            <span v-else>{{ filters.status.length }} selected</span>
            <Icon name="chevron-down" class="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56">
          <DropdownMenuItem
            v-for="status in filterOptions?.statuses || []"
            :key="status.value"
            @click="toggleStatus(status.value)"
            class="flex items-center cursor-pointer"
          >
            <div class="flex items-center w-4 h-4 mr-2">
              <svg v-if="filters.status?.includes(status.value)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <Tag :value="status" size="sm" class="mr-2" />
            {{ status.label }}
            <span class="ml-auto text-xs text-gray-500">({{ stats?.by_status?.[status.value] || 0 }})</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <!-- Priority Filter -->
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-700">Priority</label>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button variant="outline" class="w-full justify-between">
            <span v-if="!filters.priority || filters.priority.length === 0">Select Priority</span>
            <span v-else-if="filters.priority.length === 1">
              <Tag :value="getPriorityObject(filters.priority[0])" size="sm" class="mr-2" />
              {{ getPriorityLabel(filters.priority[0]) }}
            </span>
            <span v-else>{{ filters.priority.length }} selected</span>
            <Icon name="chevron-down" class="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56">
          <DropdownMenuItem
            v-for="priority in filterOptions?.priorities || []"
            :key="priority.value"
            @click="togglePriority(priority.value)"
            class="flex items-center cursor-pointer"
          >
            <div class="flex items-center w-4 h-4 mr-2">
              <svg v-if="filters.priority?.includes(priority.value)" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <Tag :value="priority" size="sm" class="mr-2" />
            {{ priority.label }}
            <span class="ml-auto text-xs text-gray-500">({{ stats?.by_priority?.[priority.value] || 0 }})</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <!-- Clear All Filters -->
    <div v-if="hasActiveFilters">
      <Button
        variant="ghost"
        size="sm"
        class="w-full"
        @click="clearAllFilters"
      >
        Clear All Filters
      </Button>
    </div>

    <!-- Stats Summary -->
    <div class="text-xs text-gray-500 pt-2 border-t border-gray-200">
      Showing {{ filteredCount || 0 }} of {{ stats?.total || 0 }} conversations
    </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { debounce } from 'lodash';
import { useConversationCollapseState } from '@/composables/useConversationCollapseState';
import { navigateWithFilters } from '@/utils/inertiaNavigation';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Icon from '@/components/Icon.vue';
import { Tag } from '@/components/ui/tag';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
// Import generated types
import '@types/generated.d';

// Define props
const props = defineProps<{
  currentFilters: Record<string, any>;
  filterOptions: App.Data.ConversationFilterData;
  filteredCount: number;
  conversationId?: string; // Optional conversation ID for state persistence
  hideCollapseButton?: boolean; // Hide internal collapse button when controlled externally
}>();

// Initialize collapse state
const { filterCollapsed, toggleFilterCollapse, persistState } = useConversationCollapseState(props.conversationId);

// Reactive filters state
const filters = reactive<{
  search?: string;
  status?: string[];
  priority?: string[];
  unread?: boolean;
}>({ ...props.currentFilters });

// Search query for debouncing
const searchQuery = ref(filters.search || '');

// Computed properties
const stats = computed(() => props.filterOptions?.stats || { total: 0, unread: 0, by_status: {}, by_priority: {} });

const hasActiveFilters = computed(() => {
  return !!(
    filters.search ||
    (filters.status && filters.status.length > 0) ||
    (filters.priority && filters.priority.length > 0) ||
    filters.unread
  );
});

// Debounced search
const debouncedSearch = debounce(() => {
  filters.search = searchQuery.value;
  updateFilters();
}, 300);

// Methods
function capitalizeFirst(str: string): string {
  return str.charAt(0).toUpperCase() + str.slice(1).replace('_', ' ');
}


function toggleUnread() {
  filters.unread = filters.unread ? undefined : true;
  updateFilters();
}

function toggleUrgent() {
  if (!filters.priority) filters.priority = [];
  if (filters.priority.includes('urgent')) {
    filters.priority = filters.priority.filter(p => p !== 'urgent');
    if (filters.priority.length === 0) filters.priority = undefined;
  } else {
    filters.priority.push('urgent');
  }
  updateFilters();
}

function toggleStatus(statusValue: string) {
  if (!filters.status) filters.status = [];
  if (filters.status.includes(statusValue)) {
    filters.status = filters.status.filter(s => s !== statusValue);
    if (filters.status.length === 0) filters.status = undefined;
  } else {
    filters.status.push(statusValue);
  }
  updateFilters();
}

function togglePriority(priorityValue: string) {
  if (!filters.priority) filters.priority = [];
  if (filters.priority.includes(priorityValue)) {
    filters.priority = filters.priority.filter(p => p !== priorityValue);
    if (filters.priority.length === 0) filters.priority = undefined;
  } else {
    filters.priority.push(priorityValue);
  }
  updateFilters();
}


function clearAllFilters() {
  filters.search = undefined;
  filters.status = undefined;
  filters.priority = undefined;
  filters.unread = undefined;
  searchQuery.value = '';
  updateFilters();
}

function updateFilters() {
  // Use the navigation utility that handles state persistence
  navigateWithFilters('/helpdesk', filters);
}

// Helper methods to get objects for tags
function getStatusObject(statusValue: string) {
  return props.filterOptions?.statuses?.find(s => s.value === statusValue) || { value: statusValue, label: capitalizeFirst(statusValue), color: null };
}

function getPriorityObject(priorityValue: string) {
  return props.filterOptions?.priorities?.find(p => p.value === priorityValue) || { value: priorityValue, label: capitalizeFirst(priorityValue), color: null };
}

function getStatusLabel(statusValue: string): string {
  return getStatusObject(statusValue).label;
}

function getPriorityLabel(priorityValue: string): string {
  return getPriorityObject(priorityValue).label;
}

// Watch for prop changes to sync filters
watch(() => props.currentFilters, (newFilters) => {
  Object.assign(filters, newFilters);
  searchQuery.value = filters.search || '';
}, { deep: true });
</script>
