<template>
  <div class="kiosk-container" :data-theme="themeStore.appliedTheme">
    <!-- Screen Router based on current state -->
    <Transition name="fade" mode="out-in">
      <!-- IDLE: QR Code Display -->
      <IdleScreen 
        v-if="kioskStore.currentScreen === 'idle'"
        @guest-mode="activateGuestMode"
        @maintenance="showMaintenanceLogin"
      />
      
      <!-- ACTIVE: User Session -->
      <ActiveSession
        v-else-if="kioskStore.currentScreen === 'active'"
        :user="kioskStore.sessionUser"
        :session-balance="kioskStore.sessionBalance"
      />
      
      <!-- PROCESSING: AI Analysis -->
      <ProcessingScreen
        v-else-if="kioskStore.currentScreen === 'processing'"
      />
      
      <!-- RESULT: Accept/Reject -->
      <ResultScreen
        v-else-if="kioskStore.currentScreen === 'result'"
        :accepted="kioskStore.lastResult?.accepted"
        :item-type="kioskStore.lastResult?.itemType"
        :points="kioskStore.lastResult?.points"
        @continue="kioskStore.continueSession"
        @end="kioskStore.endSession"
      />
      
      <!-- MAINTENANCE LOGIN: PIN Pad -->
      <PinPad
        v-else-if="kioskStore.currentScreen === 'maintenance_login'"
        @success="enterMaintenance"
        @cancel="kioskStore.setScreen('idle')"
      />
      
      <!-- MAINTENANCE DASHBOARD: Control Panel -->
      <MaintenancePanel
        v-else-if="kioskStore.currentScreen === 'maintenance'"
        :technician="kioskStore.technician"
        @exit="exitMaintenance"
      />
      
      <!-- OFFLINE: Connection Lost -->
      <OfflineScreen
        v-else-if="kioskStore.currentScreen === 'offline'"
      />
    </Transition>
    
    <!-- Footer with machine info -->
    <footer class="kiosk-footer">
      <span>{{ config.machine_name }}</span>
      <span class="status-dot" :class="connectionStatus"></span>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { useKioskStore } from './stores/kioskStore';
import { useThemeStore } from './stores/themeStore';

// Components
import IdleScreen from './components/IdleScreen.vue';
import ActiveSession from './components/ActiveSession.vue';
import ProcessingScreen from './components/ProcessingScreen.vue';
import ResultScreen from './components/ResultScreen.vue';
import PinPad from './components/PinPad.vue';
import MaintenancePanel from './components/MaintenancePanel.vue';
import OfflineScreen from './components/OfflineScreen.vue';

// Stores
const kioskStore = useKioskStore();
const themeStore = useThemeStore();

// Config from server
const config = window.KIOSK_CONFIG || {};

// Connection status
const connectionStatus = computed(() => ({
  'status-dot--online': kioskStore.isConnected,
  'status-dot--offline': !kioskStore.isConnected,
}));

// Actions
const activateGuestMode = async () => {
  await kioskStore.activateGuestMode();
};

const showMaintenanceLogin = () => {
  kioskStore.setScreen('maintenance_login');
};

const enterMaintenance = (technicianData) => {
  kioskStore.technician = technicianData;
  kioskStore.setScreen('maintenance');
};

const exitMaintenance = () => {
  kioskStore.technician = null;
  kioskStore.setScreen('idle');
};

// Lifecycle
onMounted(() => {
  // Initialize stores with config
  kioskStore.initialize(config);
  themeStore.initialize(config);
  
  // Setup WebSocket listeners
  kioskStore.setupWebSocket();
  
  // Secret maintenance gesture: tap logo 5 times
  let tapCount = 0;
  let tapTimer = null;
  
  document.addEventListener('click', (e) => {
    if (e.target.closest('.kiosk-logo')) {
      tapCount++;
      clearTimeout(tapTimer);
      tapTimer = setTimeout(() => tapCount = 0, 2000);
      
      if (tapCount >= 5) {
        tapCount = 0;
        showMaintenanceLogin();
      }
    }
  });
});

onUnmounted(() => {
  kioskStore.cleanup();
});
</script>

<style scoped>
.kiosk-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.kiosk-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: var(--spacing-md) var(--spacing-md) var(--spacing-xl);
  color: var(--text-secondary);
  font-size: 14px;
}
</style>
