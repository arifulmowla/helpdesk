# Postmark Inbound Email Webhook Setup

This document explains how to configure Postmark to send inbound emails to your Laravel helpdesk application.

## What's Implemented

The `PostmarkWebhookController` handles inbound emails from Postmark and automatically:

1. **Creates or finds contacts** based on the sender's email address
2. **Creates new conversations** or adds to existing ones based on email subject
3. **Creates messages** with the email content (HTML or text)
4. **Handles email threading** by detecting reply prefixes (Re:, Fwd:, etc.)
5. **Sanitizes HTML content** to prevent security issues
6. **Logs all activity** for debugging and monitoring

## Webhook Endpoint

Your webhook URL is:
```
https://your-domain.ngrok.app/webhooks/postmark/inbound
```

**Note:** Replace `your-domain.ngrok.app` with your actual domain.

## Postmark Configuration

### Step 1: Set up Inbound Domain

1. Go to your Postmark server dashboard
2. Navigate to "Settings" → "Inbound"
3. Click "Add inbound rule"
4. Set up a domain or use the provided Postmark domain
5. Configure forwarding to your webhook URL

### Step 2: Configure Webhook

1. In the inbound rule settings, set:
   - **URL**: `https://your-domain.ngrok.app/webhooks/postmark/inbound`
   - **Method**: POST
   - **Include raw email**: Optional (not required for basic functionality)

### Step 3: Test the Integration

You can test the webhook with curl:

```bash
curl -X POST https://your-domain.ngrok.app/webhooks/postmark/inbound \
  -H "Content-Type: application/json" \
  -d '{
    "MessageID": "test-123",
    "From": "customer@example.com",
    "Subject": "Test Support Request",
    "TextBody": "I need help with my account.",
    "HtmlBody": "<p>I need help with my <strong>account</strong>.</p>",
    "To": "support@yourapp.com",
    "Date": "2024-01-15T10:30:00Z"
  }'
```

## Email Processing Logic

### Contact Creation
- Extracts email and name from the "From" header
- Creates new contact if one doesn't exist
- Uses email as name fallback if no name is provided

### Conversation Threading
- Removes reply prefixes (Re:, RE:, Fwd:, FWD:, Fw:) from subject
- Searches for existing conversations with same contact + subject
- Only considers open/pending conversations (not closed ones)
- Creates new conversation if none found

### Message Content
- Prefers HTML content over text content
- Sanitizes HTML to remove dangerous elements (scripts, styles, etc.)
- Converts text content to HTML with proper line breaks
- Stores as 'customer' type message

## Security Features

### CSRF Protection
The webhook endpoint is excluded from CSRF protection in `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'webhooks/*',
]);
```

### HTML Sanitization
The controller sanitizes HTML content by removing:
- `<script>` tags and content
- `<style>` tags and content  
- `<link>` tags
- `javascript:` URLs
- `data:` URLs

### Error Handling
- All errors are logged but webhook returns 200 OK to prevent retries
- Validates required Postmark fields before processing
- Graceful fallbacks for missing data

## Monitoring and Debugging

### Log Files
All webhook activity is logged to `storage/logs/laravel.log`:

- Incoming webhook payloads
- Contact creation/lookup
- Conversation creation/matching  
- Message creation
- Any errors that occur

### Log Examples

**Successful processing:**
```
[2025-07-22 10:57:28] local.INFO: Successfully processed inbound email 
{
  "contact_id": "01k0rwg0mbdb4aqsdqg96d32vc",
  "conversation_id": "01k0rwg0mptnhtmqjg2qt7yjq8", 
  "message_id": "01k0rwg0mt8g4yajkd8rybj9tj",
  "subject": "Working Test Email"
}
```

**Error example:**
```
[2025-07-22 10:56:33] local.ERROR: Failed to process Postmark inbound webhook 
{
  "error": "SQLSTATE[23000]: Integrity constraint violation...",
  "payload": {...}
}
```

## Database Schema

The webhook creates records in these tables:

### Contacts
- `id` (ULID)
- `name` (from email sender)
- `email` (from email sender)
- `company` (currently null, could be enhanced)

### Conversations  
- `id` (ULID)
- `contact_id` (references contacts)
- `subject` (email subject, cleaned)
- `status` (open/pending/closed)
- `priority` (low/medium/high)
- `last_activity_at` (updated on new messages)

### Messages
- `id` (ULID)  
- `conversation_id` (references conversations)
- `type` (customer/support/internal)
- `content` (email body, HTML or text)

## Next Steps

1. **Set up your inbound domain** in Postmark
2. **Configure the webhook URL** to point to your application
3. **Test with real emails** to your configured address
4. **Monitor the logs** to ensure emails are being processed correctly
5. **Customize the logic** as needed for your specific requirements

## Potential Enhancements

- **Attachment handling**: Save email attachments to storage
- **Better HTML cleaning**: Use HTMLPurifier library
- **Email signatures**: Strip common email signatures
- **Auto-assignment**: Route to specific teams based on email address
- **Priority detection**: Set priority based on keywords or sender
- **Webhook authentication**: Verify requests are from Postmark
- **Rate limiting**: Prevent webhook abuse
