<template>
  <AppLayout title="Conversation">
    <template #header>
      <div class="flex justify-between items-center">
        <Breadcrumb :items="[
          { label: 'Helpdesk', href: '/helpdesk' },
          ...(conversation ? [{ label: conversation.subject, href: '#' }] : [])
        ]" />
      </div>
    </template>

    <div class="flex h-screen">
      <!-- Sidebar with conversation list -->
      <div class="w-80 border-r border-gray-200 h-full flex flex-col overflow-hidden">
        <div class="p-4 border-b border-gray-200 shrink-0">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">Conversations</h2>
            <!-- Collapse controls moved here -->
            <div class="flex items-center gap-2">
              <button
                @click="toggleFilterCollapse"
                class="p-2 rounded-md hover:bg-gray-100 transition-colors"
                :title="filterCollapsed ? 'Show Filters' : 'Hide Filters'"
              >
                <svg v-if="filterCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707v4.586a1 1 0 01-.553.894l-4 2A1 1 0 019 20v-6.586a1 1 0 00-.293-.707L2.293 7.293A1 1 0 012 6.586V4z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <!-- Reply Form collapse toggle - only show if conversation is selected -->
              <button
                v-if="conversation"
                @click="toggleReplyFormCollapse"
                class="p-2 rounded-md hover:bg-gray-100 transition-colors"
                :title="replyFormCollapsed ? 'Expand Reply Form' : 'Collapse Reply Form'"
              >
                <svg v-if="replyFormCollapsed" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Filter section - now collapsible from sidebar header -->
        <div v-if="!filterCollapsed" class="border-b border-gray-200 shrink-0">
          <ConversationFilter
            :currentFilters="filters.current"
            :filterOptions="filters.options"
            :filteredCount="conversations.total"
            :conversationId="conversation?.id"
            :hideCollapseButton="true"
          />
        </div>

        <!-- Conversation list - now gets full remaining space -->
        <div class="overflow-y-auto flex-1">
          <div v-if="conversations.data.length === 0" class="p-4 text-center text-gray-500">
            No conversations found
          </div>
          <div v-else>
            <ConversationListItem
              v-for="conv in conversations.data"
              :key="conv.id"
              :conversation="conv"
              :is-active="conversation?.id === conv.id"
              @click="navigateToConversationPage(conv)"
            />
            
            <!-- Infinite Scroll Trigger -->
            <div
              v-if="hasMorePages"
              ref="loadTrigger"
              class="flex items-center justify-center p-4"
            >
              <div v-if="isLoadingMore" class="flex items-center space-x-2">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-gray-600"></div>
                <span class="text-sm text-gray-500">Loading more conversations...</span>
              </div>
              <div v-else class="text-xs text-gray-400">
                Scroll to load more...
              </div>
            </div>
            <div
              v-else-if="conversations.data.length > 0"
              class="text-center py-6 text-gray-600"
            >
              You've reached the end!
            </div>
          </div>
        </div>
      </div>

      <!-- Main content area with conversation details -->
      <div class="flex-1 h-full overflow-hidden">
        <div v-if="!conversation" class="h-full flex items-center justify-center text-gray-500">
          <div class="text-center">
            <h3 class="text-lg font-medium mb-2">No conversation selected</h3>
            <p>Select a conversation from the list to view details</p>
          </div>
        </div>
        <ConversationView
          v-else
          class="h-full"
          :conversation="conversation"
          :messages="messages"
          @message-sent="handleMessageSent"
          @status-updated="handleStatusUpdated"
          @priority-updated="handlePriorityUpdated"
          @conversation-updated="handleConversationUpdated"
          :users="users"
        />
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, watch, ref, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { navigateToConversation, markConversationAsRead } from '@/utils/inertiaNavigation';
import { useConversationCollapseState } from '@/composables/useConversationCollapseState';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumb from '@/components/ui/breadcrumb/Breadcrumb.vue';
import ConversationView from "@/components/helpdesk/ConversationView.vue";
import ConversationListItem from "@/components/helpdesk/ConversationListItem.vue";
import ConversationFilter from "@/components/helpdesk/ConversationFilter.vue";
// Import types from the generated location
import "../../../types/generated.d";

const page = usePage();
const loadTrigger = ref<HTMLElement | null>(null);
const isLoadingMore = ref(false);

// Define props
const props = defineProps<{
  conversation?: App.Data.ConversationData & { messages?: App.Data.MessageData[] };
  messages: Array<App.Data.MessageData & {
    message_owner_name?: string;
    agent_name?: string;
  }>;
  conversations: {
    data: Array<App.Data.ConversationData>;
    links: any;
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
    first_page_url?: string;
    last_page_url?: string;
    next_page_url?: string;
    prev_page_url?: string;
    path?: string;
  };
  filters: {
    current: Record<string, any>;
    options: App.Data.ConversationFilterData;
  };
  users: Array<{
    id: string;
    name: string;
    email: string;
  }>;
}>();

// Initialize collapse state
const { filterCollapsed, toggleFilterCollapse, replyFormCollapsed, toggleReplyFormCollapse } = useConversationCollapseState(props.conversation?.id);

// Computed property to check if there are more pages
const hasMorePages = computed(() => {
  return props.conversations.current_page < props.conversations.last_page;
});

// Load more conversations function
const loadMoreConversations = () => {
  if (isLoadingMore.value || !hasMorePages.value) return;
  
  isLoadingMore.value = true;
  const nextPage = props.conversations.current_page + 1;
  
  console.log('Loading page:', nextPage);
  
  router.get('/helpdesk', {
    page: nextPage,
    ...props.filters.current
  }, {
    only: ['conversations'],
    preserveState: true,
    preserveScroll: true,
    preserveUrl: true, // This prevents URL updates
    onSuccess: () => {
      isLoadingMore.value = false;
      console.log('Successfully loaded page:', nextPage);
    },
    onError: (error) => {
      isLoadingMore.value = false;
      console.error('Failed to load more conversations:', error);
    }
  });
};

// Watch for changes in conversations data and merge new pages
watch(() => page.props.conversations, (newConversations, oldConversations) => {
  if (!newConversations || !oldConversations) return;
  
  // Check if this is an infinite scroll load (new page > old page)
  if (newConversations.current_page > oldConversations.current_page) {
    console.log('Merging conversations:', {
      oldPage: oldConversations.current_page,
      newPage: newConversations.current_page,
      oldDataLength: oldConversations.data?.length || 0,
      newDataLength: newConversations.data?.length || 0
    });
    
    // Merge the new conversations with the existing ones
    const existingIds = new Set((oldConversations.data || []).map(c => c.id));
    const newData = (newConversations.data || []).filter(c => !existingIds.has(c.id));
    
    if (newData.length > 0) {
      // Update the conversations data by merging
      const mergedConversations = {
        ...newConversations,
        data: [...(oldConversations.data || []), ...newData]
      };
      
      // Update the page props directly to trigger reactivity
      Object.assign(page.props.conversations, mergedConversations);
    }
  }
}, { deep: true });

// Intersection Observer for infinite scroll
let observer: IntersectionObserver | null = null;

onMounted(() => {
  if (loadTrigger.value) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && hasMorePages.value && !isLoadingMore.value) {
            loadMoreConversations();
          }
        });
      },
      {
        rootMargin: '100px', // Load when 100px away from trigger
        threshold: 0.1
      }
    );
    
    observer.observe(loadTrigger.value);
  }
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
  }
});

// Watch for loadTrigger changes to re-observe
watch(loadTrigger, (newTrigger, oldTrigger) => {
  if (observer) {
    if (oldTrigger) observer.unobserve(oldTrigger);
    if (newTrigger) observer.observe(newTrigger);
  }
});

// Function to navigate to a conversation and mark it as read
function navigateToConversationPage(conversation: any) {
  // Optimistically mark the conversation as read in the UI
  if (conversation.unread) {
    // Find the conversation in the list and update it
    const conversationInList = props.conversations.data.find(c => c.id === conversation.id);
    if (conversationInList) {
      conversationInList.unread = false;
      conversationInList.read_at = new Date().toISOString().slice(0, 19).replace('T', ' ');
    }

    // Mark conversation as read on the server
    markConversationAsRead(conversation.id, {
      onError: (error) => {
        console.error('Failed to mark conversation as read:', error);
        // Revert optimistic update on error
        if (conversationInList) {
          conversationInList.unread = true;
          conversationInList.read_at = null;
        }
      }
    });
  }

  // Navigate to the conversation
  navigateToConversation(conversation.id);
}

// Event handlers
function handleMessageSent(data: { type: string; content: string; conversation_id: string }) {
  console.log('Message sent:', data);
  // You can add additional logic here if needed
}

function handleStatusUpdated(data: { status: string; conversation_id: string }) {
  console.log('Status updated:', data);
  // You can add additional logic here if needed
}

function handlePriorityUpdated(data: { priority: string; conversation_id: string }) {
  console.log('Priority updated:', data);
  // You can add additional logic here if needed
}

function handleConversationUpdated(data: { conversation: App.Data.ConversationData }) {
  console.log('Conversation updated:', data);

  // Find the conversation in the list and update it
  const conversationInList = props.conversations.data.find(c => c.id === data.conversation.id);
  if (conversationInList) {
    // Update the conversation in the sidebar list
    Object.assign(conversationInList, data.conversation);
  }

  // Update the current conversation if it matches
  if (props.conversation && props.conversation.id === data.conversation.id) {
    Object.assign(props.conversation, data.conversation);
  }
}
</script>
