import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      // Homepage layout is managed from printmont-backend (printmont_db.home_sections),
      // which is what home-layout-manager.php edits. Kept on its own prefix so the
      // existing /api routes below are untouched.
      '/backend-api': {
        target: 'http://localhost/printmont/api',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/backend-api/, '')
      },
      '/api': {
        target: 'http://localhost/printmont/api',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    }
  }
})
