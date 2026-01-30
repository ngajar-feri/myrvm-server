/**
 * Kiosk Store - Main State Management
 * 
 * Handles screen states, session data, and WebSocket communication
 */

import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useKioskStore = defineStore('kiosk', () => {
    // ==========================================================================
    // STATE
    // ==========================================================================

    const config = ref({});
    const currentScreen = ref('idle'); // idle, active, processing, result, maintenance_login, maintenance, offline
    const isConnected = ref(true);
    const isLoading = ref(false);

    // Session state
    const sessionToken = ref(null);
    const sessionUser = ref(null);
    const sessionBalance = ref(0);
    const sessionItems = ref([]);

    // Maintenance state
    const technician = ref(null);

    // Last result (for result screen)
    const lastResult = ref(null);

    // ==========================================================================
    // GETTERS
    // ==========================================================================

    const machineUuid = computed(() => config.value.machine_uuid || '');
    const apiBaseUrl = computed(() => config.value.api_base_url || '/api/v1/kiosk');

    // ==========================================================================
    // ACTIONS
    // ==========================================================================

    /**
     * Initialize store with server config
     */
    function initialize(serverConfig) {
        config.value = serverConfig;
        fetchSessionToken();
    }

    /**
     * Set current screen
     */
    function setScreen(screen) {
        currentScreen.value = screen;
    }

    /**
     * Fetch new session token for QR display
     */
    async function fetchSessionToken() {
        try {
            isLoading.value = true;

            const response = await fetch(`${apiBaseUrl.value}/session/token`, {
                headers: {
                    'X-Machine-UUID': machineUuid.value,
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                sessionToken.value = data.data;

                // Schedule token refresh
                const expiresIn = data.data.expires_in || 300;
                setTimeout(() => fetchSessionToken(), (expiresIn - 30) * 1000);
            }
        } catch (error) {
            console.error('Failed to fetch session token:', error);
            setScreen('offline');
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Activate guest mode (donation without account)
     */
    async function activateGuestMode() {
        try {
            isLoading.value = true;

            const response = await fetch(`${apiBaseUrl.value}/session/guest`, {
                method: 'POST',
                headers: {
                    'X-Machine-UUID': machineUuid.value,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                sessionUser.value = {
                    name: data.data.display_name,
                    isGuest: true,
                };
                sessionBalance.value = 0;
                setScreen('active');
            }
        } catch (error) {
            console.error('Failed to activate guest mode:', error);
        } finally {
            isLoading.value = false;
        }
    }

    /**
     * Handle session authorization (from QR scan)
     */
    function handleSessionAuthorized(payload) {
        sessionUser.value = payload.user;
        sessionBalance.value = payload.user.balance || 0;
        setScreen('active');
    }

    /**
     * Handle item processed event
     */
    function handleItemProcessed(payload) {
        lastResult.value = {
            accepted: payload.accepted,
            itemType: payload.item_type,
            points: payload.points_awarded,
            reason: payload.rejection_reason,
        };

        if (payload.accepted) {
            sessionBalance.value += payload.points_awarded;
            sessionItems.value.push({
                type: payload.item_type,
                points: payload.points_awarded,
                time: new Date(),
            });
        }

        setScreen('result');
    }

    /**
     * Continue session after result
     */
    function continueSession() {
        setScreen('active');
    }

    /**
     * End current session
     */
    function endSession() {
        sessionUser.value = null;
        sessionBalance.value = 0;
        sessionItems.value = [];
        lastResult.value = null;
        setScreen('idle');
        fetchSessionToken();
    }

    /**
     * Setup WebSocket listeners
     */
    function setupWebSocket() {
        // TODO: Implement Laravel Echo when Reverb is configured
        // For now, we'll use polling or manual refresh

        // Simulate connection check
        setInterval(() => {
            checkConnection();
        }, 30000);
    }

    /**
     * Check server connection
     */
    async function checkConnection() {
        try {
            const response = await fetch(`${apiBaseUrl.value}/config`, {
                headers: {
                    'X-Machine-UUID': machineUuid.value,
                },
            });

            isConnected.value = response.ok;

            if (!response.ok && currentScreen.value !== 'offline') {
                setScreen('offline');
            } else if (response.ok && currentScreen.value === 'offline') {
                setScreen('idle');
            }
        } catch {
            isConnected.value = false;
            if (currentScreen.value !== 'offline') {
                setScreen('offline');
            }
        }
    }

    /**
     * Cleanup on unmount
     */
    function cleanup() {
        // Cleanup intervals, WebSocket, etc.
    }

    return {
        // State
        config,
        currentScreen,
        isConnected,
        isLoading,
        sessionToken,
        sessionUser,
        sessionBalance,
        sessionItems,
        technician,
        lastResult,

        // Getters
        machineUuid,
        apiBaseUrl,

        // Actions
        initialize,
        setScreen,
        fetchSessionToken,
        activateGuestMode,
        handleSessionAuthorized,
        handleItemProcessed,
        continueSession,
        endSession,
        setupWebSocket,
        cleanup,
    };
});
