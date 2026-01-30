<template>
  <div class="idle-screen kiosk-content">
    <!-- Logo & Welcome -->
    <div class="kiosk-header">
      <div class="kiosk-logo" aria-label="MyRVM Logo">🌿</div>
      <h1 class="welcome-title">Selamat Datang</h1>
      <p class="welcome-subtitle">Scan QR Code untuk memulai daur ulang</p>
    </div>
    
    <!-- QR Code Display -->
    <div class="qr-container">
      <div class="qr-box" v-if="sessionToken">
        <QRCode 
          :value="sessionToken.qr_content" 
          :size="qrSize"
          level="M"
          render-as="svg"
        />
      </div>
      <div class="qr-box qr-loading" v-else>
        <div class="wave-loader">
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
      
      <!-- Token expiry indicator -->
      <div class="qr-expiry" v-if="sessionToken">
        <span class="expiry-dot"></span>
        QR akan diperbarui dalam {{ timeRemaining }}
      </div>
    </div>
    
    <!-- Guest Mode Button -->
    <div class="guest-action">
      <p class="text-muted mb-md">Tidak punya akun?</p>
      <button 
        class="kiosk-btn kiosk-btn--primary kiosk-btn--large ripple"
        @click="$emit('guest-mode')"
        :disabled="isLoading"
      >
        <span class="btn-icon">🎁</span>
        Mulai Mode Donasi
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { useKioskStore } from '../stores/kioskStore';
import QRCode from 'qrcode.vue';

const emit = defineEmits(['guest-mode', 'maintenance']);

const kioskStore = useKioskStore();

// QR Size based on screen
const qrSize = computed(() => {
  return window.innerHeight < 700 ? 200 : 280;
});

// Session token from store
const sessionToken = computed(() => kioskStore.sessionToken);
const isLoading = computed(() => kioskStore.isLoading);

// Time remaining calculation
const timeRemaining = ref('5:00');
let timerInterval = null;

function updateTimeRemaining() {
  if (!sessionToken.value?.expires_at) {
    timeRemaining.value = '5:00';
    return;
  }
  
  const expiresAt = new Date(sessionToken.value.expires_at);
  const now = new Date();
  const diff = Math.max(0, Math.floor((expiresAt - now) / 1000));
  
  const minutes = Math.floor(diff / 60);
  const seconds = diff % 60;
  timeRemaining.value = `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

onMounted(() => {
  timerInterval = setInterval(updateTimeRemaining, 1000);
});

onUnmounted(() => {
  clearInterval(timerInterval);
});
</script>

<style scoped>
.idle-screen {
  text-align: center;
  padding: var(--spacing-lg);
}

.kiosk-logo {
  font-size: 72px;
  margin-bottom: var(--spacing-sm);
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.welcome-title {
  font-size: 36px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.welcome-subtitle {
  font-size: 20px;
  color: var(--text-secondary);
}

.qr-container {
  margin: var(--spacing-lg) 0;
}

.qr-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: white;
  padding: var(--spacing-md);
  border-radius: var(--border-radius-lg);
  box-shadow: var(--shadow-medium);
  min-width: 280px;
  min-height: 280px;
}

.qr-loading {
  background: var(--bg-secondary);
}

.qr-expiry {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin-top: var(--spacing-sm);
  font-size: 14px;
  color: var(--text-secondary);
}

.expiry-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--accent-primary);
  animation: pulse-dot 2s ease-in-out infinite;
}

.guest-action {
  margin-top: var(--spacing-md);
}

.btn-icon {
  font-size: 24px;
}

/* Responsive */
@media (max-height: 700px) {
  .welcome-title {
    font-size: 28px;
  }
  
  .kiosk-logo {
    font-size: 56px;
  }
  
  .qr-container {
    margin: var(--spacing-lg) 0;
  }
}
</style>
