// Conversation collapse state types
export interface ConversationCollapseState {
  filterCollapsed: boolean;
  replyFormCollapsed: boolean;
}

export interface CollapseStatesStore {
  [conversationId: string]: ConversationCollapseState;
}

// Navigation types
export interface BreadcrumbItemType {
  label: string;
  href: string;
}

export interface NavItem {
  title: string;
  href: string;
  icon?: any;
}

// Extend Inertia page props to include collapse states
declare global {
  namespace App {
    interface PageProps {
      collapseStates?: CollapseStatesStore;
    }
  }
}

// Re-export generated types
export * from '../types/generated.d';
