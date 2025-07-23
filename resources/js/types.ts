// Conversation collapse state types
export interface ConversationCollapseState {
  filterCollapsed: boolean;
  replyFormCollapsed: boolean;
}

export interface CollapseStatesStore {
  [conversationId: string]: ConversationCollapseState;
}

// Extend Inertia page props to include collapse states
declare module '@inertiajs/vue3' {
  interface PageProps {
    collapseStates?: CollapseStatesStore;
  }
}

// This file serves as a bridge to re-export types from resources/types
// Import all types from resources/types
import '../types/generated.d';
import '../types/globals.d';
import '../types/index.d';
import '../types/ziggy.d';

// No need to export anything as the types are declared globally
