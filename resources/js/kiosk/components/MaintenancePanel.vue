<template>
  <Transition name="fade-scale" appear>
    <div class="maintenance-screen kiosk-content">
      <!-- Background Elements (Biophilic) -->
      <div class="bio-bg-leaf"></div>
      <div class="bio-bg-circle"></div>

      <div class="maintenance-rows">
        <!-- Row 1: System Status (Top) -->
        <header class="maintenance-row row-identity animate-slide-down delay-1">
          <div class="status-card glass-panel">
            <div class="header-status">
                <span class="status-indicator"></span>
                System Live
            </div>
          </div>
        </header>

        <!-- Row 2: Information Message (Center) -->
        <main class="maintenance-row row-info animate-slide-up delay-2">
          <div class="info-content glass-panel">
            <div class="warning-icon-wrapper">
              <span class="warning-icon animate-bounce">⚠️</span>
            </div>
            <h3 class="info-title">Info Maintenance</h3>
            <p class="info-text">
              Halo pelanggan, Dalam rangka meningkatkan layanan saat ini kami sedang melakukan peningkatan sistem.
            </p>
            <p class="info-thanks">Terima kasih.</p>
          </div>
        </main>

        <!-- Row 3: Donation Bridge (Bottom) -->
        <footer class="maintenance-row row-action animate-slide-up delay-3">
          <div class="action-card glass-panel">
            <div class="action-icon">🎁</div>
            <p class="action-text">
              Anda tetap bisa melakukan Transaksi dalam Mode <strong>"DONASI"</strong> (Tidak Perlu Login).
            </p>
            <p class="action-instruction">
              Klik Tombol di Bawah untuk Melanjutkan Transaksi Donasi.
            </p>
            
            <button 
              class="donasi-btn kiosk-btn kiosk-btn--primary kiosk-btn--large ripple"
              @click="$emit('start-donation')"
            >
              Mulai Transaksi Donasi
            </button>
          </div>
        </footer>
      </div>

      <!-- Footer Info -->
      <div class="maintenance-footer animate-fade-in delay-4">
        <p>Managed by Admin via Remote Monitor</p>
      </div>
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

defineEmits(['exit', 'start-donation']);

const themeStore = useThemeStore();
const config = window.KIOSK_CONFIG || {};

const displayName = computed(() => {
  return props.technician?.technician_name || config.assigned_technician || 'Teknisi Terdaftar';
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
  padding: 30px 40px;
  background-color: var(--bio-bg);
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
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
    border-radius: 30px;
}

/* Vertical Row Layout */
.maintenance-rows {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  align-items: center;
  gap: 12px;
  max-width: 900px;
  width: 100%;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

.maintenance-row {
  width: 100%;
}

/* Row 1: Status Card */
.status-card {
  padding: 15px 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  width: auto;
  margin: 0 auto;
}

.header-status {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(16, 185, 129, 0.15);
    color: #047857; 
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 15px;
}

[data-theme="light"] .header-status {
    background: #dcfce7;
    border: 2px solid #86efac;
    color: #065f46;
}

.status-indicator {
    width: 10px; height: 10px;
    background: #059669;
    border-radius: 50%;
    animation: pulse-green 2s infinite;
}

[data-theme="light"] .status-indicator {
    background: #047857;
    box-shadow: 0 0 8px rgba(4, 120, 87, 0.4);
}

.exit-btn {
  position: absolute;
  bottom: 40px;
  right: 40px;
  opacity: 0.4;
  transition: opacity 0.3s;
}
.exit-btn:hover { opacity: 1; }

/* Row 2: Maintenance Info */
.info-content {
  padding: 30px 40px;
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  border: 2px solid rgba(245, 158, 11, 0.2);
}

.warning-icon-wrapper {
  margin-bottom: 15px;
}
.warning-icon { 
  font-size: 50px; 
  display: inline-block;
  filter: drop-shadow(0 10px 15px rgba(245, 158, 11, 0.3));
}

.info-title {
  font-size: 24px;
  font-weight: 800;
  color: #b45309;
  margin-bottom: 10px;
}

.info-text {
  font-size: 20px;
  line-height: 1.6;
  color: var(--text-primary);
  margin-bottom: 15px;
}

.info-thanks {
  font-size: 18px;
  font-weight: 600;
  color: #b45309;
}

/* Row 3: Donation Mode */
.action-card {
  padding: 30px 40px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 2px solid rgba(16, 185, 129, 0.2);
}

.action-icon {
  font-size: 50px;
  margin-bottom: 15px;
  animation: float 3s ease-in-out infinite;
}

.action-text {
  font-size: 18px;
  line-height: 1.4;
  margin-bottom: 10px;
  color: var(--text-primary);
}

.action-text strong {
  color: var(--bio-green);
  text-decoration: underline;
}

.action-instruction {
  font-size: 15px;
  color: var(--text-secondary);
  margin-bottom: 20px;
}

.donasi-btn {
  width: 100%;
  max-width: 400px;
  font-size: 18px;
  padding: 16px !important;
  box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3) !important;
}

/* Animations */
@keyframes pulse-green {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

@keyframes heartbeat {
  0% { transform: scale(1); }
  14% { transform: scale(1.1); }
  28% { transform: scale(1); }
  42% { transform: scale(1.1); }
  70% { transform: scale(1); }
}

.animate-heartbeat {
  animation: heartbeat 1.5s ease-in-out infinite;
  display: inline-block;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

@keyframes bounce {
  0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-20px); }
  60% { transform: translateY(-10px); }
}

@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.animate-bounce {
  animation: bounce 2s infinite;
}

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
