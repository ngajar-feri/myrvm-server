<template>
  <Transition name="fade-scale" appear>
    <div class="maintenance-screen kiosk-content">
      <!-- Background Elements (Biophilic) -->
      <div class="bio-bg-leaf"></div>
      <div class="bio-bg-circle"></div>

      <!-- Header -->
      <header class="maintenance-header">
        <div class="tech-info">
          <div class="tech-avatar-wrapper">
             <span class="tech-avatar">🛠️</span>
             <div class="tech-pulse"></div>
          </div>
          <div>
            <h2 class="animate-slide-down">Mode Maintenance</h2>
            <p class="animate-fade-in delay-1">{{ technician?.technician_name || 'Teknisi' }}</p>
          </div>
        </div>
        <div class="header-status animate-fade-in delay-2">
            <span class="status-indicator"></span>
            System Live
        </div>
      </header>
      
      <!-- Status Cards (Premium Glass) -->
      <div class="status-grid animate-slide-up delay-2">
        <div class="status-card glass-panel">
          <div class="status-label">Server Connection</div>
          <div class="status-value" :class="connectionClass('server')">
            {{ status.connections?.server ? 'Online' : 'Offline' }}
          </div>
          <div class="status-icon"><i class="ti tabler-server"></i></div>
        </div>
        <div class="status-card glass-panel">
          <div class="status-label">Edge Daemon</div>
          <div class="status-value" :class="connectionClass('edge')">
            {{ status.connections?.edge_daemon ? 'Running' : 'Stopped' }}
          </div>
           <div class="status-icon"><i class="ti tabler-cpu"></i></div>
        </div>
        <div class="status-card glass-panel">
          <div class="status-label">AI Model</div>
          <div class="status-value">{{ status.ai_model?.version || 'v1.0' }}</div>
           <div class="status-icon"><i class="ti tabler-brain"></i></div>
        </div>
        <div class="status-card glass-panel">
          <div class="status-label">Bin Level</div>
          <div class="status-value highlight">{{ status.hardware?.bin_level || 0 }}<span class="unit">%</span></div>
           <div class="status-icon"><i class="ti tabler-trash"></i></div>
        </div>
      </div>
      
      <!-- Control Panel -->
      <div class="control-section animate-slide-up delay-3">
        <h3 class="section-title">Diagnostic Controls</h3>
        <div class="maintenance-panel">
            <button 
                v-for="(action, index) in actions" 
                :key="action.command"
                class="maintenance-btn glass-panel"
                :class="{'is-executing': isExecuting && activeCommand === action.command}"
                :disabled="isExecuting"
                @click="executeCommand(action.command)"
                :style="`animation-delay: ${index * 50 + 300}ms`"
            >
                <div class="btn-content">
                    <span class="maintenance-btn__icon">{{ action.icon }}</span>
                    <span class="maintenance-btn__label">{{ action.label }}</span>
                </div>
                <div class="btn-loader" v-if="isExecuting && activeCommand === action.command"></div>
            </button>
        </div>
      </div>
      
      <!-- Footer Info -->
      <div class="maintenance-footer animate-fade-in delay-4">
        <p>Managed by Admin via Remote Monitor</p>
      </div>
      
      <!-- Command Feedback Toast -->
      <Teleport to="body">
        <Transition name="slide-up">
          <div v-if="toast" class="toast glass-toast" :class="toastClass">
            <i class="ti" :class="toast.type === 'success' ? 'tabler-check' : 'tabler-alert-circle'"></i>
            {{ toast.message }}
          </div>
        </Transition>
      </Teleport>
    </div>
  </Transition>
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
const isExecuting = ref(false);
const activeCommand = ref(null);
const toast = ref(null);

const actions = [
  { command: 'test_motor', icon: '⚙️', label: 'Test Motor' },
  { command: 'open_door', icon: '🚪', label: 'Buka Pintu' },
  { command: 'close_door', icon: '🔒', label: 'Tutup Pintu' },
  { command: 'test_led', icon: '💡', label: 'Test LED' },
  { command: 'test_sensor', icon: '📡', label: 'Test Sensor' },
  { command: 'check_model_update', icon: '🤖', label: 'Update AI' },
];

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
      headers: { 'X-Machine-UUID': kioskStore.machineUuid },
    });
    const data = await response.json();
    if (data.success) status.value = data.data;
  } catch (error) {
    console.error('Failed to fetch status:', error);
  }
};

const executeCommand = async (command) => {
  isExecuting.value = true;
  activeCommand.value = command;
  
  try {
    // Simulate delay for effect
    await new Promise(r => setTimeout(r, 600));

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
    
    if (navigator.vibrate) navigator.vibrate(data.success ? [50] : [100, 50, 100]);
    setTimeout(fetchStatus, 1000);
  } catch (error) {
    showToast('Gagal mengirim perintah', 'error');
  } finally {
    isExecuting.value = false;
    activeCommand.value = null;
  }
};

const showToast = (message, type = 'success') => {
  toast.value = { message, type };
  setTimeout(() => { toast.value = null; }, 3000);
};

onMounted(() => {
  fetchStatus();
  const statusInterval = setInterval(fetchStatus, 10000); // Polling status
  return () => clearInterval(statusInterval);
});
</script>

<style scoped>
/* Biophilic Colors & Variables */
:root {
  --bio-green: #10b981;
  --bio-green-light: #d1fae5;
  --bio-bg: #f8fafc;
  --glass-bg: rgba(255, 255, 255, 0.7);
  --glass-border: rgba(255, 255, 255, 0.5);
  --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.05);
  --text-primary: #1e293b;
  --text-secondary: #64748b;
}

.maintenance-screen {
  padding: 40px;
  background-color: #f1f5f9;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
}

/* Biophilic Background */
.bio-bg-leaf {
    position: absolute;
    top: -50px;
    right: -50px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
    border-radius: 50%;
    z-index: 0;
}
.bio-bg-circle {
    position: absolute;
    bottom: -100px;
    left: -100px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
    border-radius: 50%;
    z-index: 0;
}

/* Glassmorphism Panel */
.glass-panel {
    background: var(--glass-bg);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--glass-border);
    box-shadow: var(--glass-shadow);
    border-radius: 20px;
}

/* Header */
.maintenance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 40px;
  position: relative;
  z-index: 1;
}

.tech-info {
  display: flex;
  align-items: center;
  gap: 20px;
}

.tech-avatar-wrapper {
    position: relative;
    width: 60px;
    height: 60px;
    background: white;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

.tech-avatar { font-size: 32px; }

.tech-pulse {
    position: absolute;
    top: -4px; right: -4px;
    width: 12px; height: 12px;
    background: var(--bio-green);
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulse-green 2s infinite;
}

.maintenance-header h2 {
  font-size: 28px;
  font-weight: 700;
  margin: 0;
  background: linear-gradient(135deg, #0f172a 0%, #334155 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.maintenance-header p {
  font-size: 16px;
  color: var(--text-secondary);
  margin: 4px 0 0;
}

.header-status {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    padding: 8px 16px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 14px;
}

.status-indicator {
    width: 8px; height: 8px;
    background: #059669;
    border-radius: 50%;
    animation: pulse-green 2s infinite;
}

/* Status Grid */
.status-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 40px;
  position: relative;
  z-index: 1;
}

.status-card {
  padding: 24px;
  text-align: left;
  position: relative;
  overflow: hidden;
  transition: transform 0.3s ease;
}

.status-card:hover { transform: translateY(-5px); }

.status-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.status-value {
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
}

.status-value.highlight { font-size: 32px; color: #3b82f6; }
.status-value.highlight .unit { font-size: 16px; color: var(--text-secondary); margin-left: 2px; }

.status-online { color: #10b981; }
.status-offline { color: #ef4444; }

.status-icon {
    position: absolute;
    bottom: -10px;
    right: -10px;
    font-size: 64px;
    color: rgba(0,0,0,0.03);
    transform: rotate(-15deg);
}

/* Control Panel */
.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    color: var(--text-primary);
    position: relative; z-index: 1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title::after {
    content: ''; flex: 1; height: 1px; background: rgba(0,0,0,0.05);
}

.maintenance-panel {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 40px;
  position: relative; z-index: 1;
}

.maintenance-btn {
  padding: 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 15px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255,255,255,0.6);
  position: relative;
}

.maintenance-btn:hover:not(:disabled) {
  transform: translateY(-4px);
  background: rgba(255,255,255,0.9);
  border-color: rgba(59, 130, 246, 0.3);
}

.maintenance-btn:active:not(:disabled) { transform: scale(0.98); }

.maintenance-btn__icon { font-size: 36px; transition: transform 0.3s; }
.maintenance-btn:hover .maintenance-btn__icon { transform: scale(1.1); }

.maintenance-btn__label { font-weight: 500; font-size: 15px; }

/* Loader */
.is-executing .btn-content { opacity: 0; }
.btn-loader {
    position: absolute;
    width: 24px; height: 24px;
    border: 3px solid rgba(59, 130, 246, 0.2);
    border-radius: 50%;
    border-top-color: #3b82f6;
    animation: spin 1s linear infinite;
}

/* Footer */
.maintenance-footer {
    text-align: center;
    color: var(--text-secondary);
    font-size: 13px;
    opacity: 0.7;
}

/* Toast */
.toast {
  position: fixed;
  bottom: 40px;
  left: 50%;
  transform: translateX(-50%);
  padding: 12px 24px;
  border-radius: 50px;
  font-weight: 500;
  z-index: 1000;
  display: flex;
  align-items: center;
  gap: 10px;
}

.glass-toast {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    border: 1px solid rgba(255,255,255,0.5);
}

.toast--success { color: #059669; border-left: 4px solid #10b981; }
.toast--error { color: #dc2626; border-left: 4px solid #ef4444; }

/* Animations */
@keyframes pulse-green {
  0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
  70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
  100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Entry Transitions */
.fade-scale-enter-active, .fade-scale-leave-active { transition: all 0.5s ease; }
.fade-scale-enter-from, .fade-scale-leave-to { opacity: 0; transform: scale(0.95) translateY(20px); }

.animate-slide-down { animation: slideDown 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
.animate-slide-up { animation: slideUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }
.animate-fade-in { animation: fadeIn 0.8s ease forwards; opacity: 0; }

@keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.delay-1 { animation-delay: 0.1s; }
.delay-2 { animation-delay: 0.2s; }
.delay-3 { animation-delay: 0.3s; }
.delay-4 { animation-delay: 0.4s; }
</style>
