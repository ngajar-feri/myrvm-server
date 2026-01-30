<template>
  <div class="result-screen kiosk-content">
    <Transition name="scale" appear>
      <div class="result-card kiosk-card" :class="resultClass">
        <!-- Result Icon -->
        <div class="result-icon">
          {{ accepted ? '✓' : '✗' }}
        </div>
        
        <!-- Result Message -->
        <h2 class="result-title">
          {{ accepted ? 'Diterima!' : 'Ditolak' }}
        </h2>
        
        <p class="result-type" v-if="itemType">
          {{ itemType }}
        </p>
        
        <!-- Points (if accepted) -->
        <div class="result-points" v-if="accepted && points">
          <span class="points-label">Poin Diterima</span>
          <span class="points-value">+Rp {{ points }}</span>
        </div>
        
        <!-- Rejection Reason -->
        <p class="result-reason" v-if="!accepted">
          Item tidak dapat diproses. Pastikan botol bersih dan tidak rusak.
        </p>
        
        <!-- Actions -->
        <div class="result-actions">
          <button 
            class="kiosk-btn kiosk-btn--primary kiosk-btn--large ripple"
            @click="$emit('continue')"
          >
            {{ accepted ? 'Lanjutkan' : 'Coba Lagi' }}
          </button>
          <button 
            class="kiosk-btn kiosk-btn--secondary"
            @click="$emit('end')"
          >
            Selesai
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  accepted: {
    type: Boolean,
    default: false,
  },
  itemType: {
    type: String,
    default: '',
  },
  points: {
    type: Number,
    default: 0,
  },
});

defineEmits(['continue', 'end']);

const resultClass = computed(() => ({
  'result-card--accepted': props.accepted,
  'result-card--rejected': !props.accepted,
}));
</script>

<style scoped>
.result-screen {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
}

.result-card {
  text-align: center;
  max-width: 400px;
  padding: var(--spacing-2xl);
}

.result-icon {
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--spacing-xl);
  font-size: 48px;
  border-radius: 50%;
  color: white;
}

.result-card--accepted .result-icon {
  background: var(--accent-success);
}

.result-card--rejected .result-icon {
  background: var(--accent-danger);
}

.result-title {
  font-size: 32px;
  font-weight: 700;
  margin-bottom: var(--spacing-sm);
}

.result-card--accepted .result-title {
  color: var(--accent-success);
}

.result-card--rejected .result-title {
  color: var(--accent-danger);
}

.result-type {
  font-size: 18px;
  color: var(--text-secondary);
  margin-bottom: var(--spacing-lg);
}

.result-points {
  background: var(--accent-primary-light);
  border-radius: var(--border-radius);
  padding: var(--spacing-lg);
  margin-bottom: var(--spacing-xl);
}

.points-label {
  display: block;
  font-size: 14px;
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.points-value {
  font-size: 36px;
  font-weight: 700;
  color: var(--accent-primary);
}

.result-reason {
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xl);
  line-height: 1.6;
}

.result-actions {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
</style>
