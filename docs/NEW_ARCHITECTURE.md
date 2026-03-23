# New Architecture — Dynamic Flow Chatbot Platform

This document describes the **target architecture** for the WhatsApp chatbot after refactoring. The existing admissions bot is migrated into a database-driven flow; the platform supports multiple journeys and human intervention. **No implementation until system analysis is confirmed.**

---

## 1. Core Concepts

The chatbot operates as a **node-to-node conversation graph**.

| Concept | Meaning |
|--------|---------|
| **Flow** | A journey (e.g. Admissions, Student Support, Teacher Support, Staff Support, Parent Support, Alumni Support). One flow = one conversation path. Admins can create **unlimited** journeys; they must NOT be hardcoded. |
| **Node** | A chatbot message or question. Has type (text, buttons, list), message content, and position for the visual editor. |
| **Edge** | A user response option that leads to another node. Defines option label, option value, and target node. Enables branching. |

Example structure:

```
ENTRY
  ↓
ROUTER (if multiple flows)
  ├ Admissions
  ├ Student Support
  ├ Teacher Support
  └ Staff Support
```

Each journey is represented as a **FLOW**.

---

## 2. Database Structure (Target)

Reuse existing tables where possible; create new ones only when necessary.

### 2.1 bot_flows

Represents chatbot journeys (e.g. Admissions). The existing admissions chatbot must be migrated into this table.

| Column | Purpose |
|--------|---------|
| id | PK |
| name | Display name (e.g. "Admissions", "Student Support") |
| description | Optional description |
| entry_node_id | FK to bot_nodes — where new conversations in this flow start |
| show_in_router | Boolean — include in main menu when multiple flows exist |
| display_order | Order in router menu |
| is_active | Whether the flow can be started |
| created_at | timestamp |
| updated_at | timestamp |

### 2.2 bot_nodes

Represents chatbot questions or messages. Existing admissions questions must be migrated into this table.

| Column | Purpose |
|--------|---------|
| id | PK |
| flow_id | FK to bot_flows |
| node_key | Unique key within flow (e.g. "entry", "role") |
| type | text \| buttons \| list — determines WhatsApp message format |
| message | Bot message/question text |
| position_x | For visual flow builder (Vue Flow) |
| position_y | For visual flow builder (Vue Flow) |
| is_entry | True for the flow's entry node |
| is_active | Whether the node is used |
| created_at | timestamp |
| updated_at | timestamp |

### 2.3 bot_edges

Represents response options. Existing admissions answer options must be migrated here.

| Column | Purpose |
|--------|---------|
| id | PK |
| source_node_id | FK to bot_nodes — from which node this option is shown |
| target_node_id | FK to bot_nodes — where the user goes when they choose this option |
| option_label | Display text (e.g. "Apply Now") |
| option_value | Value used for matching (e.g. "apply_now") |
| order | Display order of options |
| created_at | timestamp |
| updated_at | timestamp |

### 2.4 bot_conversations

Tracks where a user is in a journey and supports human takeover.

| Column | Purpose |
|--------|---------|
| id | PK |
| phone_number | User identifier (E.164) |
| flow_id | FK to bot_flows — which journey they are in |
| current_node_id | FK to bot_nodes — current step |
| bot_active | Boolean — whether the bot may send automated responses |
| assigned_agent_id | FK to users (nullable) — support agent handling this conversation |
| human_intervened_at | timestamp nullable — when a human first replied |
| last_user_message_at | timestamp nullable |
| last_bot_message_at | timestamp nullable |
| last_human_message_at | timestamp nullable |
| created_at | timestamp |
| updated_at | timestamp |

### 2.5 bot_messages

Stores message history.

| Column | Purpose |
|--------|---------|
| id | PK |
| phone_number | User identifier |
| direction | incoming \| outgoing |
| node_id | FK to bot_nodes nullable — which node sent this (for outgoing) |
| message | Body text |
| created_at | timestamp |

### 2.6 bot_settings

Stores chatbot configuration. AI must be optional.

| Column | Purpose |
|--------|---------|
| id | PK |
| setting_key | Key name (see below) |
| setting_value | Value (string/number/boolean) |
| created_at | timestamp |
| updated_at | timestamp |

**AI master switch (on its own):**

| setting_key | Purpose | Example values |
|-------------|---------|----------------|
| ai_enabled | Master toggle: whether AI is used at all. When false, no AI runs regardless of ai_mode. | `0`, `1` or `false`, `true` |

**AI mode (on its own, with all potential options):**

| setting_key | Purpose | Allowed values |
|-------------|---------|----------------|
| ai_mode | How AI participates when ai_enabled is true. One value only. | `off` — no AI (same as ai_enabled off for mode) |
| | | `intent_detection` — AI only detects which option the user meant (maps free text to option_value) |
| | | `response_interpretation` — AI interprets open-ended responses (e.g. sentiment or clarification) |
| | | `full` — both intent detection and response interpretation |
| | | (Add more options as needed, e.g. `minimal`, `strict`.) |

Example rows in bot_settings:

- ai_enabled = 1  
- ai_mode = intent_detection  

The bot runs AI only when `ai_enabled` is true; when true, it uses the current `ai_mode` to decide which AI behavior to apply.

---

## 3. Bot Engine Service

**Create:** `app/Services/BotEngineService.php`

**Responsibilities:**

- Process incoming WhatsApp messages  
- Retrieve conversation state  
- Load nodes and edges  
- Move conversation between nodes  
- Send WhatsApp responses  

**Bot engine flow:**

```
Incoming message
    ↓
Find conversation by phone number
    ↓
If conversation does not exist:
    If only one flow exists (e.g. Admissions) → start that flow automatically.
    If multiple flows exist → display router menu.
    ↓
If conversation exists: load current node and its edges.
    ↓
Resolve user text to an edge (exact match or AIIntentService).
    ↓
If no match → send fallback or resend current node.
    ↓
If match → set current_node_id = edge.target_node_id; send target node's message.
```

---

## 4. Router System

The router must **dynamically** load journeys.

**Query (example):**

```php
BotFlow::where('show_in_router', true)
       ->where('is_active', true)
       ->orderBy('display_order')
```

**Router message example:**

```
Welcome 👋
How can we help you today?
```

Options must be generated dynamically from `bot_flows` (e.g. Admissions, Student Support, Teacher Support, Staff Support). User choice creates a conversation for the chosen flow and sends its entry node.

---

## 5. WhatsApp Service

Use the **existing** WhatsApp sending implementation. If needed, refactor into:

**Create/refactor:** `app/Services/WhatsAppService.php`

**Methods:**

- sendTextMessage()  
- sendButtonMessage()  
- sendListMessage()  

**Node type** determines which method to use (text → sendTextMessage; buttons → sendButtonMessage; list → sendListMessage).

---

## 6. AI Support

**Create:** `app/Services/AIIntentService.php`

**AI must only assist with:**

- Intent detection  
- Interpreting free text  

**AI must run only if:**

- `bot_settings.ai_enabled` is true (master switch). When true, **ai_mode** decides behavior: e.g. `intent_detection`, `response_interpretation`, `full`, or `off`  

**AI must NEVER control conversation flow.** It only suggests which edge (option) matches the user message for the **current node**. The engine chooses from existing edges and moves to the edge’s target node.

Admins set **ai_enabled** (on/off) and **ai_mode** (one of the allowed options) separately.

---

## 7. Human Intervention Logic

The chatbot must support **human takeover**.

- When a **human agent** replies to a conversation:  
  - **bot_active** must become **false**.

- When **bot_active = false**:  
  - The bot must **NOT** send automated responses.

- **Automation resumes** when:  
  - A new day starts, **OR**  
  - The user restarts the conversation (e.g. defined by product: new session or explicit “Start over” flow).

- Support agents must be able to:  
  - **Pause bot** (set bot_active = false)  
  - **Resume bot** (set bot_active = true)  

Use `assigned_agent_id`, `human_intervened_at`, `last_human_message_at`, and `last_bot_message_at` as needed to implement this behavior.

---

## 8. Admin Management API

All under existing auth (e.g. JWT + permissions).

### Flow management

- GET /admin/bot/flows  
- POST /admin/bot/flows  
- PUT /admin/bot/flows/{id}  
- DELETE /admin/bot/flows/{id}  

### Node management

- GET /admin/bot/nodes  
- POST /admin/bot/nodes  
- PUT /admin/bot/nodes/{id}  
- DELETE /admin/bot/nodes/{id}  

### Edge management

- GET /admin/bot/edges  
- POST /admin/bot/edges  
- PUT /admin/bot/edges/{id}  
- DELETE /admin/bot/edges/{id}  

### Bot settings

- GET /admin/bot/settings  
- PUT /admin/bot/settings  

Admins must be able to set **ai_enabled** (master on/off) and **ai_mode** (one of the allowed options: off, intent_detection, response_interpretation, full, etc.).

---

## 9. Vue Dashboard Implementation

The project already contains a Vue dashboard. **Extend** it to manage chatbot flows.

**Add a new section:** **Chatbot Management**

### Flow management page

**Path:** Dashboard → Chatbot → Flows  

Admins must be able to:

- Create journeys  
- Edit journeys  
- Activate or deactivate journeys  
- Toggle router visibility  
- Set display order  

### Node management UI

Admins must be able to:

- Create nodes  
- Edit node messages  
- Set node type (text, buttons, list)  
- Mark entry node  
- Activate or deactivate nodes  

### Edge management UI

Admins must be able to:

- Create response options  
- Connect nodes (source → target)  
- Edit labels  
- Reorder options  

### Visual flow builder

Implement a **drag-and-drop** flow editor using **Vue Flow**: https://vueflow.dev  

Admins must be able to:

- Drag nodes  
- Connect nodes  
- Edit node messages  
- Add options  
- Save node positions  

Node coordinates must be stored in:

- position_x  
- position_y  

### State management

Use a **Pinia** store: **botFlowStore**

The store must manage:

- flows  
- nodes  
- edges  
- active flow (for the editor)  

---

## 10. Expected Result

- The **existing admissions chatbot** must continue to function, but as a **dynamic journey** inside the new flow system (same behavior, data-driven).

- Admins must be able to:  
  - Create new journeys  
  - Design chatbot flows visually  
  - Connect nodes  
  - Modify router menu  
  - Enable or disable AI  
  - Pause / resume bot (human intervention)

- The platform must support **unlimited future journeys** (Admissions, Student Support, Teacher Support, Staff Support, Parent Support, Alumni Support, etc.) **without modifying code**.

---

## 11. One-Page Diagram (Conceptual)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         WhatsApp Webhook                                 │
└─────────────────────────────────┬───────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────────┐
│  BotEngineService                                                        │
│  • Find/create conversation (phone → flow_id, current_node_id)           │
│  • If no conversation → one flow? start it : show router                 │
│  • If bot_active = false → do not send automated responses               │
│  • Load current node + edges                                             │
│  • Resolve input → edge (exact or AIIntentService)                       │
│  • current_node_id = edge.target_node_id                                 │
│  • Send node message via WhatsAppService                                │
└─────────────────────────────────┬───────────────────────────────────────┘
                                  │
          ┌───────────────────────┼───────────────────────┐
          ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  bot_flows      │    │  bot_nodes      │    │  bot_edges      │
│  entry_node_id  │◄───│  flow_id        │◄───│  source_node_id │
│  show_in_router │    │  type, message  │    │  target_node_id │
│  display_order  │    │  position_x/y   │    │  option_label   │
└─────────────────┘    └─────────────────┘    │  option_value   │
                                               └─────────────────┘
```

---

**Related:** Current system analysis and migration strategy → **`docs/SYSTEM_ANALYSIS_REPORT.md`**.  
**Do not implement until confirmation.**
