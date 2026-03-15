# System Analysis Report — Existing WhatsApp Admissions Bot

**Purpose:** Understand the current chatbot implementation before refactoring into a dynamic flow platform.  
**Status:** Analysis only — **no implementation changes have been made**.  
**Next step:** Wait for confirmation before proceeding with implementation.

---

## STEP 1 — SCAN CODEBASE

Structured list of all files related to the WhatsApp chatbot, with file path, purpose, and how each interacts with the chatbot.

### Controllers

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `app/Http/Controllers/WhatsAppWebhookController.php` | Webhook entry point for Meta WhatsApp. | Verifies webhook (GET). Receives incoming WhatsApp messages (POST). Extracts phone, message text (or button/list reply ID). Delegates to `WhatsAppBotService::handleIncomingMessage()`. This is the **only** entry point for live chatbot messages. |
| `app/Http/Controllers/Api/ConversationController.php` | Dashboard API for staff to view and reply to chats. | Lists conversations, fetches messages, marks read, sends messages via `WhatsAppMessageService`. Does **not** drive the automated bot flow; used only when a human views/replies from the dashboard. |

### Services

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `app/Services/WhatsAppBotService.php` | Core admissions bot engine. | Receives every incoming message from the webhook. Saves incoming message, loads/creates `Conversation` by phone, resolves user text to an option ID (exact match or `OpenAIInterpreter`), advances `Conversation.stage`, sends next question via `WhatsAppMessageService`. Contains the **entire** admissions flow logic (single linear journey). |
| `app/Services/WhatsAppMessageService.php` | Sends messages via WhatsApp Cloud API. | Used by the bot to send text, buttons (max 3), and list messages. No conversation or flow logic; only API calls. Used by both the bot and the dashboard when staff send messages. |
| `app/Services/OpenAIInterpreter.php` | Optional AI for free-text interpretation. | When `settings.openai_enabled` is true, classifies user message into funnel categories and maps to the current stage’s option ID. Used by `WhatsAppBotService` only when the user’s text does not exactly match a button/list option. Does not control flow; only suggests which option was meant. |

### Webhook handlers

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `app/Http/Controllers/WhatsAppWebhookController.php` | Meta webhook verify + handle. | Same as Controllers above. This is the **webhook handler** for incoming WhatsApp events. |

### Models

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `app/Models/Conversation.php` | One row per phone; stores current step. | Holds `phone`, `stage`, `last_read_at`. `stage` is the current step in the admissions flow. `getCurrentFlow()` returns the `ConversationFlow` row for that stage. The bot reads/updates this to know where the user is and where to go next. |
| `app/Models/Message.php` | All messages (incoming and outgoing). | Stores `phone`, `direction`, `body`, `wamid`, `status`. The bot saves every incoming and outgoing message here. No link to nodes or flows; used for history and unread count. |
| `app/Models/ConversationFlow.php` | One row per admissions step. | Holds `stage`, `question`, `options` (JSON), `next_stage`. The bot loads the current step’s row to get the question and options, and uses `next_stage` to advance. Defines the **entire** admissions journey (8 steps). |
| `app/Models/Setting.php` | Key-value app settings. | Used for `openai_enabled`. The bot checks this before calling the AI interpreter. |

### Jobs

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `app/Jobs/SendPushNotificationJob.php` | Sends browser push to dashboard. | Dispatched by `WhatsAppBotService` when an incoming message is received. Notifies staff; does not affect conversation flow or responses. |

### Events

- **None.** No Laravel events are used for the admissions chatbot.

### Routes

| File path | Route | Purpose | How it interacts with the chatbot |
|-----------|--------|---------|-----------------------------------|
| `routes/api.php` | GET `/api/webhook/whatsapp` | Meta webhook verification. | Meta calls this to verify the webhook; must return `hub_challenge`. |
| `routes/api.php` | POST `/api/webhook/whatsapp` | Receive WhatsApp events. | Meta sends every incoming message here. This triggers the full bot flow via `WhatsAppWebhookController::handle` → `WhatsAppBotService::handleIncomingMessage`. |

All other routes in `routes/api.php` (conversations, dashboard, leads, auth, admin) are for the Vue dashboard or auth; they do not process incoming WhatsApp bot messages.

### Helpers

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `app/Helpers/ApiResponse.php` | Standard API response format. | Used by dashboard/admin APIs only; not used by the webhook or bot service. |
| `app/Helpers/AuditLogger.php` | Audit logging for admin actions. | Not used by the chatbot. |

**No helpers are used by the admissions bot.**

### Database migrations (chatbot-related)

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `database/migrations/2025_03_14_100001_create_settings_table.php` | Creates `settings` table. | Stores `openai_enabled`; bot checks this before using AI. |
| `database/migrations/2025_03_14_100002_create_conversations_table.php` | Creates `conversations` table. | Stores one row per phone with `stage`; bot reads/updates this for conversation state. |
| `database/migrations/2025_03_15_100001_add_last_read_at_to_conversations.php` | Adds `last_read_at` to `conversations`. | Used by dashboard for unread count; not used by bot flow logic. |
| `database/migrations/2025_03_14_100003_create_messages_table.php` | Creates `messages` table. | Bot saves every incoming and outgoing message here. |
| `database/migrations/2025_03_14_100004_create_conversation_flows_table.php` | Creates `conversation_flows` table. | Stores the 8 admissions steps (question, options, next_stage); bot loads this to decide what to send next. |
| `database/migrations/2025_03_14_100005_seed_openai_enabled_setting.php` | Seeds `openai_enabled` in `settings`. | Ensures AI can be enabled for the bot. |

### Seeders

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `database/seeders/ConversationFlowSeeder.php` | Seeds the admissions flow. | Inserts the 8 stages (entry → role → commitment → experience → pricing → education → conversion → complete) into `conversation_flows`. This **is** the current definition of the admissions journey. |

### Config

| File path | Purpose | How it interacts with the chatbot |
|-----------|---------|-----------------------------------|
| `config/services.php` | WhatsApp, OpenAI, webpush config. | `whatsapp`: access_token, phone_number_id, verify_token — used by webhook and message service. `openai`: api_key — used by OpenAIInterpreter when AI is enabled. |

---

## STEP 2 — ANALYZE DATABASE STRUCTURE

For each table used by the admissions chatbot: table name, columns, relationships, purpose.

### Table: `conversations`

| Column | Type | Purpose |
|--------|------|---------|
| id | bigint PK | — |
| phone | string unique | User identifier (E.164). |
| stage | string default 'entry' | Current step in the single admissions flow. Values: entry, role, commitment, experience, pricing, education, conversion, complete. |
| last_read_at | timestamp nullable | When dashboard last read this conversation (unread count). |
| created_at, updated_at | timestamps | — |

**Relationships:** None (no FKs). Conceptually: `stage` matches a row in `conversation_flows.stage`.

**Purpose in the chatbot:** Tracks **where** each user is in the admissions journey. One row per phone. The bot reads `stage`, loads the corresponding `conversation_flows` row, and after a valid response updates `stage` to `next_stage`.

---

### Table: `conversation_flows`

| Column | Type | Purpose |
|--------|------|---------|
| id | bigint PK | — |
| stage | string unique | Step identifier (e.g. 'entry', 'role'). |
| question | text | Bot message/question for this step. |
| options | json nullable | Array of `{ "id": "option_id", "title": "Label" }`. Buttons (≤3) or list rows. |
| next_stage | string nullable | The single next step after any valid option. Linear: one next_stage per row. |
| created_at, updated_at | timestamps | — |

**Relationships:** None. `Conversation.stage` references this table’s `stage` by value (no FK).

**Purpose in the chatbot:** Defines the **entire** admissions flow: one row per step. The bot uses the current row to get the question and options to send, and uses `next_stage` to advance. Acts as both “questions” and “answers” (options) in one table; there are no separate `questions` or `answers` tables.

---

### Table: `messages`

| Column | Type | Purpose |
|--------|------|---------|
| id | bigint PK | — |
| phone | string | Conversation (no FK). |
| direction | string | 'incoming' or 'outgoing'. |
| body | text | Message content. |
| wamid | string nullable | WhatsApp message ID. |
| status | string nullable | — |
| created_at, updated_at | timestamps | — |

**Relationships:** None. Linked to a conversation only by `phone`.

**Purpose in the chatbot:** Stores full message history. The bot writes every incoming and every outgoing message. Used for display in the dashboard and for unread count. No `node_id` or flow reference; we cannot tell which “question” (step) an outgoing message came from.

---

### Table: `settings`

| Column | Type | Purpose |
|--------|------|---------|
| id | bigint PK | — |
| key | string unique | e.g. 'openai_enabled'. |
| value | text nullable | e.g. '1'. |
| created_at, updated_at | timestamps | — |

**Relationships:** None.

**Purpose in the chatbot:** Feature flags. The bot uses `openai_enabled` to decide whether to call the AI interpreter when the user’s text does not exactly match an option. No other bot-specific keys exist yet.

---

**Tables not used by the chatbot:** users, passwords, roles, permissions, otp_codes, password_resets, user_invitations, audit_logs, push_subscriptions, jobs (table) — these are for auth, admin, push, and queue only.

---

## STEP 3 — TRACE CURRENT ADMISSIONS FLOW

Step-by-step how the admissions chatbot works today.

### Where incoming WhatsApp messages are processed

1. Meta sends a POST to `/api/webhook/whatsapp`.
2. `WhatsAppWebhookController::handle()` runs. It reads `entry.0.changes.0.value`, ignores statuses, takes the first item in `value.messages`.
3. It extracts **phone** (`message.from`), **text** (from `message.text.body` or `message.interactive.button_reply.id` or `message.interactive.list_reply.id`), and **wamid**.
4. It calls `WhatsAppBotService::handleIncomingMessage($phone, $text, $wamid)`.

So: **incoming WhatsApp messages are processed only in `WhatsAppWebhookController::handle` and `WhatsAppBotService::handleIncomingMessage`.**

### How responses are selected

1. **Save incoming message** to `messages` (direction = incoming).
2. **Load or create conversation:** `Conversation::firstOrCreate(['phone' => $phone], ['stage' => 'entry'])`.
3. **Load current step:** `$conversation->getCurrentFlow()` → `ConversationFlow::where('stage', $conversation->stage)->first()`. This gives the current **question** and **options**.
4. **Resolve user text to an option ID:**
   - **Exact match:** Normalize text; if it equals one of the flow row’s option `id`s, that option is selected.
   - **AI (if `openai_enabled`):** Call `OpenAIInterpreter::interpret($text)` to get a classification (role, program_interest, etc.). Then `classificationToOptionId($stage, $classification)` maps to one option ID for **this stage only** (hardcoded map in OpenAIInterpreter). If the result is in the current step’s option IDs, that option is selected.
   - Otherwise → **no match**.
5. **If no match:** If we have never sent an outgoing message to this phone, send the current step’s question (first contact). Otherwise send “Please choose one of the options above.” and stop.
6. **If match:** Call `advanceStage($conversation)` then `sendNextQuestion($conversation)`.

So: **responses are selected by matching user input to the current step’s option IDs (exact or via AI), then advancing to the single `next_stage`.**

### Where conversation state is stored

- **Only in `conversations` table:** column `stage` (string). One row per phone.
- There is no `flow_id`, no `current_node_id`, no `bot_active`, no `assigned_agent_id`. The only state is “which of the 8 steps is this user on.”
- **Message history** is in `messages`; it is not used to derive state, only for display and unread count.

### How the next question is determined

1. After a valid option, `advanceStage()` sets `conversation.stage = $flow->next_stage` (one value per row; all options at a step lead to the same next step).
2. `sendNextQuestion()` loads the **new** `ConversationFlow` row where `stage` = updated `conversation.stage`.
3. It sends that row’s `question` as text, or as **buttons** (if ≤3 options), or as **list** (if >3 options), via `WhatsAppMessageService`.
4. That outgoing message is saved to `messages`.

So: **the next question is always the `question` of the row whose `stage` equals `conversation.stage` after advancing. The flow is strictly linear (one next_stage per step).**

### Full stage sequence (from ConversationFlowSeeder)

| Order | stage | next_stage | Options (id → title) |
|-------|--------|------------|----------------------|
| 1 | entry | role | student, graduate, professional, parent |
| 2 | role | commitment | software_engineering, data_analytics, cloud_computing, cybersecurity, not_sure |
| 3 | commitment | experience | commit_high, commit_medium, exploring |
| 4 | experience | pricing | programming, data_analysis, cloud_platforms, none |
| 5 | pricing | education | yes, installment, not_currently |
| 6 | education | conversion | yes, tell_me_more |
| 7 | conversion | complete | apply_now, attend_info_session |
| 8 | complete | null | (none) |

---

## STEP 4 — MIGRATION STRATEGY

Mapping the existing admissions bot structure to the new architecture.

| Existing | New architecture | Notes |
|----------|-------------------|------|
| **Admissions journey (8 steps in conversation_flows)** | **bot_flows** | One flow row, e.g. name = "Admissions", `entry_node_id` = the node for "entry". |
| **Each row in conversation_flows (one step)** | **bot_nodes** | One node per step. `node_key` = stage (entry, role, …), `message` = question, `type` = text / buttons / list from option count. `is_entry` = true only for entry. |
| **Options on a conversation_flows row** | **bot_edges** | For each option: one edge from this node to the node for `next_stage`; `option_label` = title, `option_value` = id. Current flow has one next_stage for all options; in the new model all edges from a step can point to the same target node. |
| **conversations (phone + stage)** | **bot_conversations** (or extended conversations) | `phone_number`, `flow_id` = Admissions flow, `current_node_id` = node whose `node_key` = current stage. Add `bot_active`, `assigned_agent_id`, `human_intervened_at`, `last_*_message_at` per new spec. |
| **messages** | **bot_messages** (or extended messages) | Add optional `node_id` for outgoing messages so we know which node sent them. |
| **settings (openai_enabled)** | **bot_settings** | New table or prefixed keys: `ai_enabled`, `ai_intent_detection`, `ai_response_interpretation`. AI only runs when `ai_enabled` = true. |
| **WhatsAppBotService** | **BotEngineService** | Replace with engine that uses bot_flows, bot_nodes, bot_edges; same entry point from webhook. |
| **OpenAIInterpreter** | **AIIntentService** | Only intent detection and free-text interpretation for current node’s edges; never controls flow. |
| **WhatsAppMessageService** | **WhatsAppService** (optional refactor) | Reuse; methods: sendTextMessage(), sendButtonMessage(), sendListMessage(). |

**Summary mapping:**

- **Existing Question** (one row in conversation_flows) → **bot_nodes**
- **Existing Answer** (one option in that row’s options JSON) → **bot_edges**
- **Admissions Journey** (the full 8-step flow) → **bot_flows** (one record) + **bot_nodes** (8) + **bot_edges** (one per option)

---

## Report summary

1. **Relevant backend files** are listed in STEP 1 (controllers, services, webhook handler, models, jobs, routes, migrations, seeders, config). No events or bot-specific helpers.
2. **Database tables** used by the chatbot are in STEP 2: `conversations`, `conversation_flows`, `messages`, `settings`. No separate questions, answers, chat_logs, or leads tables.
3. **Current admissions flow** is traced in STEP 3: webhook → controller → WhatsAppBotService; state in `conversations.stage`; next question from `conversation_flows` by stage; options matched by exact ID or AI.
4. **Migration strategy** is in STEP 4: map flows → bot_flows, steps → bot_nodes, options → bot_edges; extend or replace conversations/messages; introduce BotEngineService, AIIntentService, and optional WhatsAppService refactor.

---

**Do not implement until confirmation.**  
Target architecture (new system design): **`docs/NEW_ARCHITECTURE.md`**.
