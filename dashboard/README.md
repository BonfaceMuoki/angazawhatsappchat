# Angaza Dashboard (Vue.js)

A separate Vue 3 dashboard with Vite, Vue Router, and Tailwind CSS.

## Setup

```bash
cd dashboard
npm install
```

## Run

```bash
npm run dev
```

Opens at **http://localhost:5174** (port 5174 to avoid clashing with Laravel or other Vite apps).

## Build

```bash
npm run build
```

Output is in `dist/`. You can deploy it to any static host or point your Laravel app to it via a subdomain/path if you prefer.

## Structure

- `src/views/` — Dashboard, Chats, Settings pages
- `src/components/` — AppSidebar, AppHeader
- `src/router/` — Vue Router config

To connect to your Laravel API, set the API base URL in **Settings** and use it in your views (e.g. with `fetch` or axios).
