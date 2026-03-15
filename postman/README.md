# Postman

## Steps to execute and test

### 1. Start the stack

From the project root:

```bash
docker compose up -d
```

Wait until MySQL is ready (e.g. 10–20 seconds), then run migrations and seed:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=SuperAdminSeeder
```

### 2. Import the collection in Postman

- Open Postman → **Import** → **Upload files** → select `postman/Angaza-API.postman_collection.json`.
- Ensure **base_url** is `http://localhost:8000` (or your ngrok URL if you use it).  
  Edit collection → **Variables** → set `base_url` if needed.

### 3. Test Auth (login + OTP)

1. **Auth → Login**  
   - Body: `email`: `admin@example.com`, `password`: `Admin123!`  
   - Send. Expect **200** and `"message": "OTP sent to your email..."`.

2. **Get the OTP**  
   With `MAIL_MAILER=log` (default), the OTP is not emailed; the job runs in the **queue** container and, when `APP_DEBUG=true`, the plain OTP is written to the Laravel log. After **Login**, run:
   ```bash
   docker compose exec queue tail -50 /var/www/html/storage/logs/laravel.log
   ```
   Look for a line like `OTP for admin@example.com: 123456` and use that 6-digit code in the next step.  
   Alternatively, use a real mail driver (e.g. Mailtrap) in `.env` and check the inbox for the OTP.

3. **Auth → Verify OTP**  
   - Body: `email`: `admin@example.com`, `code`: `<the 6-digit OTP>`  
   - Send. Expect **200** with `token` and `user` in the response.

4. **Save the token**  
   - Copy the `token` value from the response.  
   - Edit collection → **Variables** → paste into **token** → Save.  
   - All **Admin** requests will now send `Authorization: Bearer <token>`.

### 4. Test Admin endpoints

- **Admin - Users → List users**  
  Expect **200** and a paginated list including `admin@example.com`.
- **Admin - Roles → List roles**  
  Expect **200** and at least `super_admin`, `customer_support`.
- **Admin - Permissions → List permissions**  
  Expect **200** and e.g. `users.create`, `users.update`, `roles.assign`, etc.
- **Admin - Users → Create user**  
  Use a new email and password; expect **201** and verification email queued.
- **Admin - Users → Invite user**  
  Use an email and `role_id: 1`; expect **201** and invitation email queued.

### 5. Test other endpoints (optional)

- **Misc → API root**  
  GET `/api` → **200** with app name and message.
- **Conversations**  
  List conversations, get messages, send message (requires WhatsApp config and existing conversations).
- **Push**  
  Get VAPID public key; subscribe/unsubscribe (requires a valid push subscription payload from the dashboard).

---

## Angaza API collection

Import **`Angaza-API.postman_collection.json`** in Postman (Import → Upload file).

### Collection variables

| Variable     | Example / description |
|-------------|------------------------|
| `base_url`  | `http://localhost:8000` (or your ngrok URL) |
| `token`     | JWT from **Auth → Verify OTP** response; used as Bearer for Admin requests |
| `phone`     | WhatsApp phone (e.g. `254740857767`) for conversation requests |
| `user_id`   | User ID for Admin user actions (assign roles, permissions, block) |
| `role_id`   | Role ID for Admin role actions |
| `permission_id` | Permission ID for Admin permission actions |

### Using Admin endpoints

1. **Auth → Login** with `admin@example.com` / `Admin123!` (after seeding).
2. Get the OTP from logs (if `MAIL_MAILER=log`) or from your mail.
3. **Auth → Verify OTP** with the same email and the 6-digit code.
4. Copy the `token` from the response into the collection variable **token** (Edit collection → Variables → token).
5. Use **Admin - Users**, **Admin - Roles**, **Admin - Permissions**; they send `Authorization: Bearer {{token}}` automatically.

### Folders

- **Auth** – login, verify OTP, password reset, verify email, accept invite (no Bearer).
- **Admin - Users** – list, create, invite, assign roles/permissions, block (Bearer).
- **Admin - Roles** – list, create, assign permissions (Bearer).
- **Admin - Permissions** – list, create (Bearer).
- **Conversations (WhatsApp)** – list conversations, get/send messages, mark read.
- **Push** – VAPID public key, subscribe, unsubscribe.
- **Misc** – API root, webhook verify (GET for Meta).
