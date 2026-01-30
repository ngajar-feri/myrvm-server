<template>
  <div class="active-session kiosk-content">
    <!-- User Info Header -->
    <div class="session-header">
      <div class="user-avatar">
        {{ userInitial }}
      </div>
      <div class="user-info">
        <h2 class="user-name">Halo, {{ user?.name || 'Pengguna' }}!</h2>
        <p class="user-type" v-if="user?.isGuest">
          <span class="badge badge--guest">Mode Donasi</span>
        </p>
      </div>
    </div>
    
    <!-- Balance Display -->
    <div class="balance-card kiosk-card">
      <div class="balance-label">Saldo Sesi Ini</div>
      <div class="balance-amount">
        <span class="currency">Rp</span>
        <span class="value">{{ formattedBalance }}</span>
      </div>
      <div class="balance-items" v-if="itemCount > 0">
        {{ itemCount }} item dimasukkan
      </div>
    </div>
    
    <!-- Instruction -->
    <div class="instruction-box">
      <div class="instruction-icon">📥</div>
      <h3>Masukkan Botol Plastik</h3>
      <p>Letakkan botol PET kosong ke dalam lubang mesin</p>
    </div>
    
    <!-- Recent Items -->
    <div class="recent-items" v-if="recentItems.length > 0">
      <h4>Item Terakhir</h4>
      <TransitionGroup name="list" tag="ul">
        <li v-for="item in recentItems" :key="item.time" class="recent-item">
          <span class="item-icon">✓</span>
          <span class="item-type">{{ item.type }}</span>
          <span class="item-points">+Rp {{ item.points }}</span>
        </li>
      </TransitionGroup>
    </div>
    
    <!-- End Session Button -->
    <div class="session-actions">
      <button 
        class="kiosk-btn kiosk-btn--secondary kiosk-btn--large"
        @click="confirmEndSession"
      >
        Selesai
      </button>
    </div>
    
    <!-- End Session Confirmation Modal -->
    <Teleport to="body">
      <Transition name="fade">
        <div v-if="showConfirmModal" class="modal-overlay" @click="showConfirmModal = false">
          <div class="modal-content kiosk-card" @click.stop>
            <h3>Akhiri Sesi?</h3>
            <p>Total saldo sesi ini: <strong>Rp {{ formattedBalance }}</strong></p>
            <div class="modal-actions">
              <button class="kiosk-btn kiosk-btn--secondary" @click="showConfirmModal = false">
                Lanjutkan
              </button>
              <button class="kiosk-btn kiosk-btn--primary" @click="endSession">
                Selesai
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useKioskStore } from '../stores/kioskStore';

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  sessionBalance: {
    type: Number,
    default: 0,
  },
});

const kioskStore = useKioskStore();
const showConfirmModal = ref(false);

// User initial for avatar
const userInitial = computed(() => {
  const name = props.user?.name || 'U';
  return name.charAt(0).toUpperCase();
});

// Formatted balance
const formattedBalance = computed(() => {
  return props.sessionBalance.toLocaleString('id-ID');
});

// Item count
const itemCount = computed(() => kioskStore.sessionItems.length);

// Recent items (last 3)
const recentItems = computed(() => {
  return kioskStore.sessionItems.slice(-3).reverse();
});

// Actions
const confirmEndSession = () => {
  showConfirmModal.value = true;
};

const endSession = () => {
  showConfirmModal.value = false;
  kioskStore.endSession();
};
</script>

<style scoped>
.active-session {
  padding: var(--spacing-xl);
}

.session-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xl);
}

.user-avatar {
  width: 64px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--accent-primary);
  color: white;
  font-size: 28px;
  font-weight: 700;
  border-radius: 50%;
}

.user-name {
  font-size: 28px;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
}

.badge--guest {
  display: inline-block;
  padding: 4px 12px;
  background: var(--accent-primary-light);
  color: var(--accent-primary);
  border-radius: var(--border-radius-full);
  font-size: 14px;
  font-weight: 500;
}

.balance-card {
  text-align: center;
  padding: var(--spacing-2xl);
  margin-bottom: var(--spacing-xl);
}

.balance-label {
  font-size: 16px;
  color: var(--text-secondary);
  margin-bottom: var(--spacing-sm);
}

.balance-amount {
  font-size: 48px;
  font-weight: 700;
  color: var(--accent-primary);
}

.balance-amount .currency {
  font-size: 24px;
  font-weight: 400;
  vertical-align: super;
}

.balance-items {
  margin-top: var(--spacing-sm);
  font-size: 14px;
  color: var(--text-secondary);
}

.instruction-box {
  text-align: center;
  padding: var(--spacing-xl);
  background: var(--bg-secondary);
  border-radius: var(--border-radius);
  margin-bottom: var(--spacing-xl);
}

.instruction-icon {
  font-size: 48px;
  margin-bottom: var(--spacing-md);
}

.instruction-box h3 {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
}

.instruction-box p {
  color: var(--text-secondary);
}

.recent-items {
  margin-bottom: var(--spacing-xl);
}

.recent-items h4 {
  font-size: 14px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: var(--spacing-sm);
}

.recent-items ul {
  list-style: none;
}

.recent-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md);
  background: var(--bg-secondary);
  border-radius: var(--border-radius-sm);
  margin-bottom: var(--spacing-xs);
}

.item-icon {
  color: var(--accent-success);
}

.item-type {
  flex: 1;
}

.item-points {
  color: var(--accent-primary);
  font-weight: 600;
}

.session-actions {
  text-align: center;
}

/* List animation */
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.list-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  max-width: 400px;
  text-align: center;
}

.modal-content h3 {
  font-size: 24px;
  margin-bottom: var(--spacing-md);
}

.modal-actions {
  display: flex;
  gap: var(--spacing-md);
  justify-content: center;
  margin-top: var(--spacing-xl);
}
</style>
