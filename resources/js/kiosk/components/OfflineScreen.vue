<template>
  <div class="offline-screen kiosk-content">
    <div class="offline-content">
      <div class="offline-icon">📡</div>
      <h2>Koneksi Terputus</h2>
      <p>Maaf, mesin sedang mengalami gangguan koneksi.</p>
      
      <div class="retry-animation">
        <span class="retry-dot"></span>
        <span class="retry-dot"></span>
        <span class="retry-dot"></span>
      </div>
      
      <button class="kiosk-btn kiosk-btn--secondary" @click="retryConnection" :disabled="isRetrying">
        {{ isRetrying ? 'Menghubungkan...' : 'Coba Sekarang' }}
      </button>
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

onMounted(() => {
  retryInterval = setInterval(retryConnection, 10000);
});

onUnmounted(() => {
  clearInterval(retryInterval);
});
</script>

<style scoped>
.offline-screen { display: flex; align-items: center; justify-content: center; }
.offline-content { text-align: center; padding: var(--spacing-2xl); }
.offline-icon { font-size: 80px; margin-bottom: var(--spacing-xl); animation: pulse 2s ease-in-out infinite; }
.offline-content h2 { font-size: 32px; font-weight: 700; margin-bottom: var(--spacing-md); }
.offline-content p { font-size: 18px; color: var(--text-secondary); margin-bottom: var(--spacing-xl); }
.retry-animation { display: flex; justify-content: center; gap: 8px; margin-bottom: var(--spacing-xl); }
.retry-dot { width: 12px; height: 12px; background: var(--accent-primary); border-radius: 50%; animation: bounce 1.4s ease-in-out infinite; }
.retry-dot:nth-child(2) { animation-delay: 0.2s; }
.retry-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
@keyframes bounce { 0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; } 40% { transform: scale(1); opacity: 1; } }
</style>
