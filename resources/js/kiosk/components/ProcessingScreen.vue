<template>
  <div class="processing-screen kiosk-content">
    <div class="processing-visual">
      <!-- Animated Processing Icon -->
      <div class="processing-circle">
        <div class="circle-inner">
          <span class="processing-icon">🔍</span>
        </div>
        <svg class="progress-ring" viewBox="0 0 120 120">
          <circle 
            class="progress-ring__circle"
            cx="60" 
            cy="60" 
            r="54"
            fill="none"
            stroke="currentColor"
            stroke-width="4"
          />
        </svg>
      </div>
    </div>
    
    <div class="processing-text">
      <h2>Menganalisis Item...</h2>
      <p>{{ processingMessage }}</p>
    </div>
    
    <!-- Wave loader -->
    <div class="wave-loader">
      <span></span>
      <span></span>
      <span></span>
      <span></span>
      <span></span>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const messages = [
  'Memindai barcode...',
  'Mengidentifikasi jenis plastik...',
  'Memverifikasi kondisi botol...',
  'Menghitung nilai tukar...',
];

const processingMessage = ref(messages[0]);
let messageIndex = 0;
let messageInterval = null;

onMounted(() => {
  messageInterval = setInterval(() => {
    messageIndex = (messageIndex + 1) % messages.length;
    processingMessage.value = messages[messageIndex];
  }, 1500);
});

onUnmounted(() => {
  clearInterval(messageInterval);
});
</script>

<style scoped>
.processing-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: var(--spacing-2xl);
}

.processing-visual {
  margin-bottom: var(--spacing-2xl);
}

.processing-circle {
  position: relative;
  width: 150px;
  height: 150px;
}

.circle-inner {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg-card);
  border-radius: 50%;
  box-shadow: var(--shadow-soft);
}

.processing-icon {
  font-size: 48px;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.progress-ring {
  width: 150px;
  height: 150px;
  transform: rotate(-90deg);
  color: var(--accent-primary);
}

.progress-ring__circle {
  stroke-dasharray: 339.292;
  stroke-dashoffset: 339.292;
  stroke-linecap: round;
  animation: progress 2s ease-in-out infinite;
}

@keyframes progress {
  0% { stroke-dashoffset: 339.292; }
  50% { stroke-dashoffset: 0; }
  100% { stroke-dashoffset: -339.292; }
}

.processing-text h2 {
  font-size: 28px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: var(--spacing-sm);
}

.processing-text p {
  font-size: 18px;
  color: var(--text-secondary);
  min-height: 30px;
}

.wave-loader {
  margin-top: var(--spacing-2xl);
}
</style>
