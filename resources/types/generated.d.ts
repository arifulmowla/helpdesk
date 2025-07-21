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
status: string;
priority: string;
last_activity_at: string | null;
created_at: string;
contact: App.Data.ContactData;
messages: any | Array<any>;
};
export type MessageData = {
id: string;
conversation_id: string;
type: string;
content: string;
created_at: string;
};
}
