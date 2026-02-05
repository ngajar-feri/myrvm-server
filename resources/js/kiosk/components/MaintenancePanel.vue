<template>
  <Transition name="fade-scale" appear>
    <div class="maintenance-screen kiosk-content">
      <!-- Background Elements (Biophilic) -->
      <div class="bio-bg-leaf"></div>
      <div class="bio-bg-circle"></div>

      <div class="maintenance-columns">
        <!-- Column 1: Identity & Status -->
        <aside class="maintenance-col col-identity animate-slide-up delay-1">
          <div class="tech-card glass-panel">
            <div class="tech-avatar-wrapper">
               <span class="tech-avatar">🛠️</span>
               <div class="tech-pulse"></div>
            </div>
            <div class="tech-info">
              <h2 class="animate-slide-down">Mode Maintenance</h2>
              <p class="technician-name animate-fade-in delay-2">{{ technician?.technician_name || 'Teknisi' }}</p>
            </div>
            <div class="header-status animate-fade-in delay-2">
                <span class="status-indicator"></span>
                System Live
            </div>
          </div>
          
          <button class="exit-btn kiosk-btn kiosk-btn--outline" @click="$emit('exit')">
            Keluar Maintenance
          </button>
        </aside>

        <!-- Column 2: Information Message -->
        <main class="maintenance-col col-info animate-slide-up delay-2">
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

        <!-- Column 3: Donation Bridge -->
        <aside class="maintenance-col col-action animate-slide-up delay-3">
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
        </aside>
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
// kioskStore and other diagnostic refs removed as they are no longer needed for the informational view
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
  padding: 60px 40px;
  background-color: var(--bio-bg);
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
  display: flex;
  flex-direction: column;
  justify-content: center;
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

/* 3-Column Layout */
.maintenance-columns {
  display: flex;
  justify-content: center;
  align-items: stretch;
  gap: 30px;
  max-width: 1200px;
  margin: 0 auto;
  position: relative;
  z-index: 1;
}

.maintenance-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Column 1: Identity Card */
.tech-card {
  padding: 40px 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 25px;
  height: 100%;
}

.tech-avatar-wrapper {
    position: relative;
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.tech-avatar { font-size: 50px; }

/* Pulse Fix for Light Mode */
.tech-pulse {
    position: absolute;
    top: -5px; right: -5px;
    width: 20px; height: 20px;
    background: var(--bio-green);
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: pulse-green 2s infinite;
}

/* High Contrast Indicator for Light Mode */
[data-theme="light"] .tech-pulse {
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.9), 0 4px 10px rgba(0,0,0,0.1);
}

.tech-info h2 {
  font-size: 24px;
  font-weight: 800;
  margin: 0;
  color: var(--text-primary);
}

.technician-name {
  font-size: 18px;
  color: var(--text-secondary);
  margin: 8px 0 0;
  font-weight: 500;
}

.header-status {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: rgba(16, 185, 129, 0.15);
    color: #047857; /* Darker green for contrast */
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 15px;
}

[data-theme="light"] .header-status {
    background: #dcfce7;
    border: 1px solid #bbf7d0;
}

.status-indicator {
    width: 10px; height: 10px;
    background: #059669;
    border-radius: 50%;
    animation: pulse-green 2s infinite;
}

.exit-btn {
  margin-top: auto;
  opacity: 0.6;
  transition: opacity 0.3s;
}
.exit-btn:hover { opacity: 1; }

/* Column 2: Maintenance Info */
.info-content {
  padding: 50px 40px;
  text-align: center;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  border: 2px solid rgba(245, 158, 11, 0.2);
}

.warning-icon-wrapper {
  margin-bottom: 30px;
}
.warning-icon { 
  font-size: 80px; 
  display: inline-block;
  filter: drop-shadow(0 10px 15px rgba(245, 158, 11, 0.3));
}

.info-title {
  font-size: 28px;
  font-weight: 800;
  color: #b45309;
  margin-bottom: 20px;
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

/* Column 3: Donation Mode */
.action-card {
  padding: 50px 40px;
  text-align: center;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 2px solid rgba(16, 185, 129, 0.2);
}

.action-icon {
  font-size: 70px;
  margin-bottom: 30px;
  animation: float 3s ease-in-out infinite;
}

.action-text {
  font-size: 20px;
  line-height: 1.5;
  margin-bottom: 20px;
  color: var(--text-primary);
}

.action-text strong {
  color: var(--bio-green);
  text-decoration: underline;
}

.action-instruction {
  font-size: 16px;
  color: var(--text-secondary);
  margin-bottom: 35px;
}

.donasi-btn {
  width: 100%;
  font-size: 18px;
  padding: 20px !important;
  box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3) !important;
}

/* Animations */
@keyframes pulse-green {
  0% { transform: scale(0.95); opacity: 0.8; }
  50% { transform: scale(1.05); opacity: 1; }
  100% { transform: scale(0.95); opacity: 0.8; }
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
