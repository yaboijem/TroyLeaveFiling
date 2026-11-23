import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  base: '/TroyLeaveFiling/admin/',
  plugins: [react(), tailwindcss()],
  build: {
    outDir: '../admin',
    assetsDir: 'assets',
    emptyOutDir: true,
  },
  server: {
    port: 5174,
    proxy: {
      '/TroyLeaveFiling/portal': {
        target: 'http://localhost',
        changeOrigin: true,
        cookieDomainRewrite: '',
      },
    },
  },
});
