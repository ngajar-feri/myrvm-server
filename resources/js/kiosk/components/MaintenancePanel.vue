<template>
  <div class="maintenance-screen kiosk-content">
    <!-- Header -->
    <div class="maintenance-header">
      <div class="tech-info">
        <span class="tech-avatar">🔧</span>
        <div>
          <h2>Mode Maintenance</h2>
          <p>{{ technician?.technician_name || 'Teknisi' }}</p>
        </div>
      </div>
      <button class="kiosk-btn kiosk-btn--secondary" @click="$emit('exit')">
        Keluar
      </button>
    </div>
    
    <!-- Status Cards -->
    <div class="status-grid">
      <div class="status-card">
        <div class="status-label">Server</div>
        <div class="status-value" :class="connectionClass('server')">
          {{ status.connections?.server ? 'Online' : 'Offline' }}
        </div>
      </div>
      <div class="status-card">
        <div class="status-label">Edge Daemon</div>
        <div class="status-value" :class="connectionClass('edge')">
          {{ status.connections?.edge_daemon ? 'Online' : 'Offline' }}
        </div>
      </div>
      <div class="status-card">
        <div class="status-label">AI Model</div>
        <div class="status-value">{{ status.ai_model?.version || 'N/A' }}</div>
      </div>
      <div class="status-card">
        <div class="status-label">Bin Level</div>
        <div class="status-value">{{ status.hardware?.bin_level || 0 }}%</div>
      </div>
    </div>
    
    <!-- Control Buttons -->
    <div class="maintenance-panel">
      <button 
        v-for="action in actions" 
        :key="action.command"
        class="maintenance-btn"
        :disabled="isExecuting"
        @click="executeCommand(action.command)"
      >
        <span class="maintenance-btn__icon">{{ action.icon }}</span>
        <span class="maintenance-btn__label">{{ action.label }}</span>
      </button>
    </div>
    
    <!-- Theme Toggle -->
    <div class="theme-section">
      <h4>Pengaturan Tema</h4>
      <div class="theme-options">
        <button 
          v-for="mode in ['auto', 'light', 'dark']"
          :key="mode"
          class="theme-btn"
          :class="{ 'theme-btn--active': themeStore.themeMode === mode }"
          @click="themeStore.setThemeMode(mode)"
        >
          {{ themeLabels[mode] }}
        </button>
      </div>
    </div>
    
    <!-- Log Viewer -->
    <div class="log-section">
      <div class="log-header">
        <h4>Log Aktivitas</h4>
        <button class="kiosk-btn kiosk-btn--ghost" @click="fetchLogs">
          ↻ Refresh
        </button>
      </div>
      <div class="log-viewer">
        <div 
          v-for="log in logs" 
          :key="log.id" 
          class="log-entry"
        >
          <span class="log-entry__icon">{{ log.level_icon }}</span>
          <span class="log-entry__time">{{ formatTime(log.timestamp) }}</span>
          <span class="log-entry__message">{{ log.message }}</span>
        </div>
        <p v-if="logs.length === 0" class="log-empty">
          Tidak ada log untuk ditampilkan
        </p>
      </div>
    </div>
    
    <!-- Command Feedback Toast -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div v-if="toast" class="toast" :class="toastClass">
          {{ toast.message }}
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useKioskStore } from '../stores/kioskStore';
import { useThemeStore } from '../stores/themeStore';

const props = defineProps({
  technician: {
    type: Object,
    default: null,
  },
});

defineEmits(['exit']);

const kioskStore = useKioskStore();
const themeStore = useThemeStore();

const status = ref({});
const logs = ref([]);
const isExecuting = ref(false);
const toast = ref(null);

const themeLabels = {
  auto: '🌓 Auto',
  light: '☀️ Light',
  dark: '🌙 Dark',
};

const actions = [
  { command: 'test_motor', icon: '⚙️', label: 'Test Motor' },
  { command: 'open_door', icon: '🚪', label: 'Buka Pintu' },
  { command: 'close_door', icon: '🔒', label: 'Tutup Pintu' },
  { command: 'test_led', icon: '💡', label: 'Test LED' },
  { command: 'test_sensor', icon: '📡', label: 'Test Sensor' },
  { command: 'check_model_update', icon: '🤖', label: 'Update AI Model' },
];

// Connection status class
const connectionClass = (type) => {
  const isOnline = type === 'server' 
    ? status.value.connections?.server 
    : status.value.connections?.edge_daemon;
  return isOnline ? 'status-online' : 'status-offline';
};

const toastClass = computed(() => ({
  'toast--success': toast.value?.type === 'success',
  'toast--error': toast.value?.type === 'error',
}));

// Fetch machine status
const fetchStatus = async () => {
  try {
    const response = await fetch(`${kioskStore.apiBaseUrl}/maintenance/status`, {
      headers: {
        'X-Machine-UUID': kioskStore.machineUuid,
      },
    });
    const data = await response.json();
    if (data.success) {
      status.value = data.data;
    }
  } catch (error) {
    console.error('Failed to fetch status:', error);
  }
};

// Fetch logs
const fetchLogs = async () => {
  try {
    const response = await fetch(`${kioskStore.apiBaseUrl}/logs?limit=20`, {
      headers: {
        'X-Machine-UUID': kioskStore.machineUuid,
      },
    });
    const data = await response.json();
    if (data.success) {
      logs.value = data.data.logs;
    }
  } catch (error) {
    console.error('Failed to fetch logs:', error);
  }
};

// Execute maintenance command
const executeCommand = async (command) => {
  isExecuting.value = true;
  
  try {
    const response = await fetch(`${kioskStore.apiBaseUrl}/maintenance/command`, {
      method: 'POST',
      headers: {
        'X-Machine-UUID': kioskStore.machineUuid,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        command,
        assignment_id: props.technician?.assignment_id,
      }),
    });
    
    const data = await response.json();
    
    showToast(data.message, data.success ? 'success' : 'error');
    
    // Haptic feedback
    if (navigator.vibrate) {
      navigator.vibrate(data.success ? [100] : [100, 50, 100]);
    }
    
    // Refresh status after command
    setTimeout(fetchStatus, 1000);
  } catch (error) {
    showToast('Gagal mengirim perintah', 'error');
  } finally {
    isExecuting.value = false;
  }
};

// Show toast notification
const showToast = (message, type = 'success') => {
  toast.value = { message, type };
  setTimeout(() => {
    toast.value = null;
  }, 3000);
};

// Format timestamp
const formatTime = (timestamp) => {
  const date = new Date(timestamp);
  return date.toLocaleTimeString('id-ID', { 
    hour: '2-digit', 
    minute: '2-digit' 
  });
};

onMounted(() => {
  fetchStatus();
  fetchLogs();
  
  // Auto-refresh status every 30 seconds
  const statusInterval = setInterval(fetchStatus, 30000);
  
  return () => clearInterval(statusInterval);
});
</script>

<style scoped>
.maintenance-screen {
  padding: var(--spacing-lg);
  overflow-y: auto;
}

.maintenance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-xl);
}

.tech-info {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
}

.tech-avatar {
  font-size: 36px;
}

.tech-info h2 {
  font-size: 20px;
  margin: 0;
}

.tech-info p {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0;
}

.status-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
}

.status-card {
  background: var(--bg-card);
  border-radius: var(--border-radius);
  padding: var(--spacing-md);
  text-align: center;
  box-shadow: var(--shadow-soft);
}

.status-label {
  font-size: 12px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: var(--spacing-xs);
}

.status-value {
  font-size: 16px;
  font-weight: 600;
}

.status-online {
  color: var(--accent-success);
}

.status-offline {
  color: var(--accent-danger);
}

.maintenance-panel {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
}

.maintenance-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-lg);
  background: var(--bg-card);
  border: var(--border-light);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-normal);
}

.maintenance-btn:hover:not(:disabled) {
  transform: translateY(-4px);
  box-shadow: var(--shadow-medium);
}

.maintenance-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.maintenance-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.maintenance-btn__icon {
  font-size: 32px;
}

.maintenance-btn__label {
  font-size: 13px;
  font-weight: 500;
  color: var(--text-primary);
}

.theme-section {
  margin-bottom: var(--spacing-xl);
}

.theme-section h4 {
  font-size: 14px;
  color: var(--text-secondary);
  margin-bottom: var(--spacing-sm);
}

.theme-options {
  display: flex;
  gap: var(--spacing-sm);
}

.theme-btn {
  flex: 1;
  padding: var(--spacing-md);
  background: var(--bg-secondary);
  border: 2px solid transparent;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.theme-btn--active {
  border-color: var(--accent-primary);
  background: var(--accent-primary-light);
}

.log-section {
  flex: 1;
}

.log-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-sm);
}

.log-header h4 {
  font-size: 14px;
  color: var(--text-secondary);
}

.log-viewer {
  max-height: 200px;
  overflow-y: auto;
  background: var(--bg-secondary);
  border-radius: var(--border-radius);
  padding: var(--spacing-sm);
}

.log-entry {
  display: flex;
  align-items: flex-start;
  gap: var(--spacing-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  font-size: 12px;
  border-bottom: 1px solid var(--bg-tertiary);
}

.log-entry:last-child {
  border-bottom: none;
}

.log-entry__icon {
  flex-shrink: 0;
}

.log-entry__time {
  color: var(--text-muted);
  flex-shrink: 0;
  width: 50px;
}

.log-entry__message {
  color: var(--text-primary);
  word-break: break-word;
}

.log-empty {
  text-align: center;
  color: var(--text-secondary);
  padding: var(--spacing-lg);
}

/* Toast */
.toast {
  position: fixed;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%);
  padding: var(--spacing-md) var(--spacing-xl);
  border-radius: var(--border-radius-full);
  font-weight: 500;
  z-index: 1000;
}

.toast--success {
  background: var(--accent-success);
  color: white;
}

.toast--error {
  background: var(--accent-danger);
  color: white;
}

/* Responsive */
@media (max-width: 800px) {
  .status-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .maintenance-panel {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
