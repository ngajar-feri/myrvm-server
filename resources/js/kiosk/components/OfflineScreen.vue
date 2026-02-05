<template>
  <div class="offline-screen kiosk-content">
    <div class="offline-content">
      <!-- Status Icon & Message -->
      <div class="offline-icon">📡</div>
      <h2>Koneksi Terputus</h2>
      <p>Maaf, mesin sedang mengalami gangguan koneksi.</p>
      
      <div class="retry-animation">
        <span class="retry-dot"></span>
        <span class="retry-dot"></span>
        <span class="retry-dot"></span>
      </div>
      
      <p class="reconnect-text">Menghubungkan...</p>
      
      <!-- Donation Card -->
      <div class="donation-card glass-panel">
        <div class="donation-icon">🎁</div>
        <p class="donation-text">
          Anda tetap bisa melakukan Transaksi dalam Mode <strong>"DONASI"</strong> (Tidak Perlu Login).
        </p>
        <p class="donation-instruction">
          Klik Tombol di Bawah untuk Melanjutkan Transaksi Donasi.
        </p>
        
        <button 
          class="donasi-btn kiosk-btn kiosk-btn--primary kiosk-btn--large ripple"
          @click="startDonation"
        >
          Mulai Transaksi Donasi
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useKioskStore } from '../stores/kioskStore';

const kioskStore = useKioskStore();
const isRetrying = ref(false);
let retryInterval = null;

const retryConnection = async () => {
  isRetrying.value = true;
  try {
    const response = await fetch(`${kioskStore.apiBaseUrl}/config`, {
      headers: { 'X-Machine-UUID': kioskStore.machineUuid },
    });
    if (response.ok) {
      kioskStore.isConnected = true;
      kioskStore.setScreen('idle');
    }
  } catch (error) {
    // Still offline
  } finally {
    isRetrying.value = false;
  }
};

const startDonation = () => {
  // Start donation mode even when offline (uses guest mode)
  kioskStore.activateGuestMode();
};

onMounted(() => {
  retryInterval = setInterval(retryConnection, 10000);
});

onUnmounted(() => {
  clearInterval(retryInterval);
});
</script>

<style scoped>
.offline-screen { 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  min-height: 100vh;
  padding: var(--spacing-xl);
}

.offline-content { 
  text-align: center; 
  max-width: 500px;
}

.offline-icon { 
  font-size: 60px; 
  margin-bottom: var(--spacing-lg); 
  animation: pulse 2s ease-in-out infinite; 
}

.offline-content h2 { 
  font-size: 28px; 
  font-weight: 700; 
  margin-bottom: var(--spacing-sm); 
  color: var(--text-primary);
}

.offline-content p { 
  font-size: 16px; 
  color: var(--text-secondary); 
  margin-bottom: var(--spacing-md); 
}

.reconnect-text {
  font-size: 14px;
  color: var(--text-tertiary);
  margin-bottom: var(--spacing-xl);
}

.retry-animation { 
  display: flex; 
  justify-content: center; 
  gap: 8px; 
  margin-bottom: var(--spacing-sm); 
}

.retry-dot { 
  width: 10px; 
  height: 10px; 
  background: var(--accent-primary); 
  border-radius: 50%; 
  animation: bounce 1.4s ease-in-out infinite; 
}

.retry-dot:nth-child(2) { animation-delay: 0.2s; }
.retry-dot:nth-child(3) { animation-delay: 0.4s; }

/* Donation Card */
.donation-card {
  margin-top: var(--spacing-xl);
  padding: 25px 30px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  border: 2px solid rgba(16, 185, 129, 0.2);
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.9);
}

.donation-icon {
  font-size: 40px;
}

.donation-text {
  font-size: 14px;
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.5;
}

.donation-instruction {
  font-size: 13px;
  color: var(--text-tertiary);
  margin: 0 0 8px 0;
}

.donasi-btn {
  width: 100%;
  max-width: 280px;
  font-size: 16px;
  font-weight: 600;
  padding: 15px 25px !important;
  box-shadow: 0 8px 15px rgba(16, 185, 129, 0.2) !important;
  transition: transform 0.2s, box-shadow 0.2s;
}

.donasi-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 20px rgba(16, 185, 129, 0.3) !important;
}

.donasi-btn:active {
  transform: translateY(0);
}

@keyframes pulse { 
  0%, 100% { opacity: 1; } 
  50% { opacity: 0.5; } 
}

@keyframes bounce { 
  0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; } 
  40% { transform: scale(1); opacity: 1; } 
}
</style>
