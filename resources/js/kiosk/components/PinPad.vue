<template>
  <div class="pinpad-screen kiosk-content">
    <div class="pinpad kiosk-card">
      <h2 class="pinpad-title">Masukkan PIN Teknisi</h2>
      
      <!-- PIN Display -->
      <div class="pinpad-display">
        <span 
          v-for="i in 6" 
          :key="i" 
          class="pinpad-dot"
          :class="{ 'pinpad-dot--filled': pin.length >= i }"
        ></span>
      </div>
      
      <!-- Error Message -->
      <Transition name="fade">
        <p v-if="error" class="pinpad-error">
          {{ error }}
          <span v-if="remainingAttempts !== null">
            ({{ remainingAttempts }} percobaan tersisa)
          </span>
        </p>
      </Transition>
      
      <!-- Keypad Grid -->
      <div class="pinpad-grid">
        <button 
          v-for="num in [1,2,3,4,5,6,7,8,9]" 
          :key="num"
          class="pinpad-key ripple"
          @click="addDigit(num)"
          :disabled="isLoading"
        >
          {{ num }}
        </button>
        <button 
          class="pinpad-key pinpad-key--danger"
          @click="$emit('cancel')"
          :disabled="isLoading"
        >
          ✕
        </button>
        <button 
          class="pinpad-key ripple"
          @click="addDigit(0)"
          :disabled="isLoading"
        >
          0
        </button>
        <button 
          class="pinpad-key"
          @click="removeDigit"
          :disabled="isLoading || pin.length === 0"
        >
          ⌫
        </button>
      </div>
      
      <!-- Submit Button -->
      <button 
        class="kiosk-btn kiosk-btn--primary kiosk-btn--large"
        :disabled="pin.length !== 6 || isLoading"
        @click="verifyPin"
      >
        <span v-if="isLoading" class="loading-spinner"></span>
        <span v-else>Masuk</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useKioskStore } from '../stores/kioskStore';

const emit = defineEmits(['success', 'cancel']);

const kioskStore = useKioskStore();

const pin = ref('');
const error = ref('');
const remainingAttempts = ref(null);
const isLoading = ref(false);

// Add digit to PIN
const addDigit = (digit) => {
  if (pin.value.length < 6) {
    pin.value += digit.toString();
    error.value = '';
    
    // Auto-submit when 6 digits entered
    if (pin.value.length === 6) {
      verifyPin();
    }
  }
};

// Remove last digit
const removeDigit = () => {
  pin.value = pin.value.slice(0, -1);
  error.value = '';
};

// Verify PIN with server
const verifyPin = async () => {
  if (pin.value.length !== 6) return;
  
  isLoading.value = true;
  error.value = '';
  
  try {
    const response = await fetch(`${kioskStore.apiBaseUrl}/auth/pin`, {
      method: 'POST',
      headers: {
        'X-Machine-UUID': kioskStore.machineUuid,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ pin: pin.value }),
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Haptic feedback on success (if available)
      if (navigator.vibrate) {
        navigator.vibrate([100, 50, 100]);
      }
      
      emit('success', data.data);
    } else {
      error.value = data.message || 'PIN tidak valid';
      remainingAttempts.value = data.remaining_attempts ?? null;
      pin.value = '';
      
      // Haptic feedback on error
      if (navigator.vibrate) {
        navigator.vibrate([200, 100, 200]);
      }
    }
  } catch (err) {
    error.value = 'Gagal terhubung ke server';
    pin.value = '';
  } finally {
    isLoading.value = false;
  }
};
</script>

<style scoped>
.pinpad-screen {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-xl);
}

.pinpad {
  text-align: center;
  max-width: 360px;
}

.pinpad-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: var(--spacing-xl);
  color: var(--text-primary);
}

.pinpad-display {
  display: flex;
  justify-content: center;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-lg);
}

.pinpad-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: var(--bg-tertiary);
  border: 2px solid var(--text-muted);
  transition: all var(--transition-fast);
}

.pinpad-dot--filled {
  background: var(--accent-primary);
  border-color: var(--accent-primary);
  transform: scale(1.15);
}

.pinpad-error {
  color: var(--accent-danger);
  font-size: 14px;
  margin-bottom: var(--spacing-md);
  min-height: 20px;
}

.pinpad-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xl);
}

.pinpad-key {
  width: 72px;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  font-weight: 600;
  background: var(--bg-secondary);
  border: none;
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all var(--transition-fast);
  color: var(--text-primary);
}

.pinpad-key:hover:not(:disabled) {
  background: var(--bg-tertiary);
}

.pinpad-key:active:not(:disabled) {
  transform: scale(0.95);
  background: var(--accent-primary-light);
}

.pinpad-key:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pinpad-key--danger {
  background: rgba(239, 83, 80, 0.15);
  color: var(--accent-danger);
}

.loading-spinner {
  width: 24px;
  height: 24px;
  border: 3px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-height: 700px) {
  .pinpad-key {
    width: 64px;
    height: 64px;
    font-size: 24px;
  }
}
</style>
