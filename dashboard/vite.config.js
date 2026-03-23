import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// Production: VITE_BASE_PATH=/dashboard/ npm run build → assets under /dashboard/
const base = process.env.VITE_BASE_PATH || '/'

export default defineConfig({
  base,
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5174,
    strictPort: true,
    proxy: {
      // Forward /api to Laravel (localhost when dev on host; use VITE_API_PROXY_TARGET for Docker)
      '/api': {
        target: process.env.VITE_API_PROXY_TARGET || 'http://localhost:8000',
        changeOrigin: true,
      },
    },
    watch: {
      usePolling: true,
    },
    hmr: {
      clientPort: 5174,
    },
  },
})
