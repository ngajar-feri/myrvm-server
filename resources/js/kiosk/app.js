/**
 * Kiosk App - Entry Point
 * 
 * Vue.js 3 application for RVM Kiosk Interface
 * Bio-Digital Minimalism design philosophy
 */

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import KioskApp from './KioskApp.vue';

// Create Vue app
const app = createApp(KioskApp);

// Create Pinia store
const pinia = createPinia();
app.use(pinia);

// Mount app
app.mount('#kiosk-app');

// Log initialization
console.log('🌿 MyRVM Kiosk initialized', window.KIOSK_CONFIG);
