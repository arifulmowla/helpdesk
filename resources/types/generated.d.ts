declare namespace App.Data {
export type ContactData = {
id: string;
name: string;
email: string;
company: string | null;
created_at: string;
};
export type ConversationData = {
id: string;
subject: string;
status: Array<any>;
priority: Array<any>;
last_activity_at: string | null;
created_at: string;
unread: boolean;
read_at: string | null;
contact: App.Data.ContactData;
messages: any | Array<any>;
};
export type ConversationFilterData = {
statuses: Array<any>;
priorities: Array<any>;
stats: App.Data.ConversationStatsData;
};
export type ConversationStatsData = {
total: number;
unread: number;
by_status: Array<any>;
by_priority: Array<any>;
};
export type EmailAttachmentDto = {
name: string;
content_type: string;
content_length: number;
content: string;
content_id: string;
};
export type MessageData = {
id: string;
conversation_id: string;
type: App.Enums.Type;
content: string;
created_at: string;
message_owner_name: string | null;
};
export type SentEmailDto = {
message_id: string;
thread_id: string | null;
timestamp: string;
};
}
declare namespace App.Enums {
export type Priority = 'low' | 'medium' | 'high' | 'urgent';
export type Status = 'open' | 'closed' | 'awaiting_customer' | 'awaiting_agent' | 'resolved' | 'cancelled';
export type Type = 'agent' | 'customer';
}
