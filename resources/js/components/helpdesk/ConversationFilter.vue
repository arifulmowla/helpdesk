<template>
  <div class="p-4 space-y-4 bg-gray-50 border-b border-gray-200">
    <!-- Collapsible header -->
    <button @click="toggleCollapse" class="w-full flex justify-between items-center bg-gray-100 p-2 font-medium">
      Filters
      <Icon :name="collapsed ? 'chevron-down' : 'chevron-up'" class="h-4 w-4" />
    </button>
    <div v-show="!collapsed" class="space-y-4">
    
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
        <DropdownMenuTrigger asChild>
          <Button variant="outline" class="w-full justify-between">
            {{ getStatusLabel() }}
            <Icon name="chevron-down" class="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-full">
          <DropdownMenuCheckboxItem
            :checked="!filters.status || filters.status.length === 0"
            @click="clearStatusFilter"
          >
            All Statuses
          </DropdownMenuCheckboxItem>
          <DropdownMenuSeparator />
          <DropdownMenuCheckboxItem
            v-for="status in availableStatuses"
            :key="status"
            :checked="filters.status?.includes(status)"
            @click="toggleStatus(status)"
            :class="{ 'bg-blue-50 font-medium': filters.status?.includes(status) }"
          >
            <StatusBadge :status="status" size="sm" class="mr-2" />
            {{ capitalizeFirst(status) }}
            <span class="ml-auto text-xs text-gray-500">({{ stats?.by_status?.[status] || 0 }})</span>
          </DropdownMenuCheckboxItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>

    <!-- Priority Filter -->
    <div class="space-y-2">
      <label class="text-sm font-medium text-gray-700">Priority</label>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="outline" class="w-full justify-between">
            {{ getPriorityLabel() }}
            <Icon name="chevron-down" class="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-full">
          <DropdownMenuCheckboxItem
            :checked="!filters.priority || filters.priority.length === 0"
            @click="clearPriorityFilter"
          >
            All Priorities
          </DropdownMenuCheckboxItem>
          <DropdownMenuSeparator />
          <DropdownMenuCheckboxItem
            v-for="priority in availablePriorities"
            :key="priority"
            :checked="filters.priority?.includes(priority)"
            @click="togglePriority(priority)"
            :class="{ 'bg-blue-50 font-medium': filters.priority?.includes(priority) }"
          >
            <PriorityBadge :priority="priority" size="sm" class="mr-2" />
            {{ capitalizeFirst(priority) }}
            <span class="ml-auto text-xs text-gray-500">({{ stats?.by_priority?.[priority] || 0 }})</span>
          </DropdownMenuCheckboxItem>
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

// Add ref for collapsing
const collapsed = ref(true);
function toggleCollapse() {
  collapsed.value = !collapsed.value;
}
import { computed, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Icon from '@/components/Icon.vue';
import StatusBadge from './StatusBadge.vue';
import PriorityBadge from './PriorityBadge.vue';
import DropdownMenu from '@/components/ui/dropdown-menu/DropdownMenu.vue';
import DropdownMenuTrigger from '@/components/ui/dropdown-menu/DropdownMenuTrigger.vue';
import DropdownMenuContent from '@/components/ui/dropdown-menu/DropdownMenuContent.vue';
import DropdownMenuCheckboxItem from '@/components/ui/dropdown-menu/DropdownMenuCheckboxItem.vue';
import DropdownMenuSeparator from '@/components/ui/dropdown-menu/DropdownMenuSeparator.vue';
// Import generated types
import '@types/generated.d';

// Define props
const props = defineProps<{
  currentFilters: Record<string, any>;
  filterOptions: App.Data.ConversationFilterData;
  filteredCount: number;
}>();

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
const availableStatuses = computed(() => props.filterOptions?.statuses || []);
const availablePriorities = computed(() => props.filterOptions?.priorities || []);
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

function getStatusLabel(): string {
  if (!filters.status || filters.status.length === 0) {
    return 'All Statuses';
  }
  if (filters.status.length === 1) {
    return capitalizeFirst(filters.status[0]);
  }
  return `${filters.status.length} statuses selected`;
}

function getPriorityLabel(): string {
  if (!filters.priority || filters.priority.length === 0) {
    return 'All Priorities';
  }
  if (filters.priority.length === 1) {
    return capitalizeFirst(filters.priority[0]);
  }
  return `${filters.priority.length} priorities selected`;
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

function toggleStatus(status: string) {
  if (!filters.status) filters.status = [];
  
  if (filters.status.includes(status)) {
    filters.status = filters.status.filter(s => s !== status);
    if (filters.status.length === 0) filters.status = undefined;
  } else {
    filters.status.push(status);
  }
  updateFilters();
}

function togglePriority(priority: string) {
  if (!filters.priority) filters.priority = [];
  
  if (filters.priority.includes(priority)) {
    filters.priority = filters.priority.filter(p => p !== priority);
    if (filters.priority.length === 0) filters.priority = undefined;
  } else {
    filters.priority.push(priority);
  }
  updateFilters();
}

function clearStatusFilter() {
  filters.status = undefined;
  updateFilters();
}

function clearPriorityFilter() {
  filters.priority = undefined;
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
  // Clean undefined values
  const cleanFilters = Object.fromEntries(
    Object.entries(filters).filter(([_, v]) => v !== undefined && v !== '' && (Array.isArray(v) ? v.length > 0 : true))
  );

  // Navigate with new filters
  router.get('/helpdesk', cleanFilters, {
    preserveState: true,
    preserveScroll: true,
  });
}

// Watch for prop changes to sync filters
watch(() => props.currentFilters, (newFilters) => {
  Object.assign(filters, newFilters);
  searchQuery.value = filters.search || '';
}, { deep: true });
</script>
