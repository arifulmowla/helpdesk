declare namespace App.Data {
export type ArticleDetailData = {
id: string;
title: string;
slug: string;
excerpt: string | null;
is_published: boolean;
published_at: string | null;
created_at: string | null;
updated_at: string | null;
deleted_at: string | null;
view_count: number;
tag_names: Array<string>;
body: Array<any>;
created_by: App.Data.UserData | null;
updated_by: App.Data.UserData | null;
tags: any | Array<any>;
previous_article: Array<any> | null;
next_article: Array<any> | null;
};
export type ArticleListItemData = {
id: string;
title: string;
slug: string;
excerpt: string | null;
is_published: boolean;
published_at: string | null;
created_at: string | null;
updated_at: string | null;
deleted_at: string | null;
view_count: number;
tag_names: Array<string>;
created_by: App.Data.UserData | null;
updated_by: App.Data.UserData | null;
highlighted_title: string | null;
highlighted_excerpt: string | null;
};
export type ContactData = {
id: string;
name: string;
email: string;
company: Array<any> | null;
};
export type ConversationData = {
id: string;
case_number: string;
subject: string;
status: Array<any>;
priority: Array<any>;
last_activity_at: string | null;
created_at: string;
unread: boolean;
read_at: string | null;
contact: App.Data.ContactData;
assigned_to: Array<any> | null;
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
export type TagData = {
id: string;
name: string;
slug: string;
articles_count: number;
created_at: string | null;
};
export type UserData = {
id: string;
name: string;
email: string;
};
}
declare namespace App.Data.Admin {
export type AdminArticleData = {
id: string;
title: string;
slug: string;
excerpt: string | null;
is_published: boolean;
published_at: string | null;
created_at: string | null;
updated_at: string | null;
deleted_at: string | null;
view_count: number;
tag_ids: Array<number>;
tag_names: Array<string>;
body: Array<any>;
created_by: Array<any> | null;
updated_by: Array<any> | null;
tags: Array<App.Data.TagData>;
};
}
declare namespace App.Enums {
export type Priority = 'low' | 'medium' | 'high' | 'urgent';
export type Status = 'open' | 'closed' | 'awaiting_customer' | 'awaiting_agent' | 'resolved' | 'cancelled';
export type Type = 'agent' | 'customer' | 'internal';
}
