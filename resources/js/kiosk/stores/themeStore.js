/**
 * Theme Store - Light/Dark/Auto Mode Management
 * 
 * Handles theme switching based on user preference or time of day
 */

import { defineStore } from 'pinia';
import { ref, computed, watch } from 'vue';

export const useThemeStore = defineStore('theme', () => {
    // ==========================================================================
    // STATE
    // ==========================================================================

    const themeMode = ref('auto'); // auto, light, dark
    const timezone = ref('Asia/Jakarta');
    const machineUuid = ref('');

    // ==========================================================================
    // GETTERS
    // ==========================================================================

    /**
     * Calculate the actual theme to apply
     */
    const appliedTheme = computed(() => {
        if (themeMode.value !== 'auto') {
            return themeMode.value;
        }

        // Auto mode: dark between 18:00 - 06:00
        const now = new Date();
        const hour = now.getHours();
        return (hour >= 18 || hour < 6) ? 'dark' : 'light';
    });

    // ==========================================================================
    // ACTIONS
    // ==========================================================================

    /**
     * Initialize theme from server config
     */
    function initialize(config) {
        themeMode.value = config.theme_mode || 'auto';
        timezone.value = config.timezone || 'Asia/Jakarta';
        machineUuid.value = config.machine_uuid || '';

        applyTheme();

        // Check time every minute for auto theme
        if (themeMode.value === 'auto') {
            setInterval(() => {
                applyTheme();
            }, 60000);
        }
    }

    /**
     * Apply theme to document
     */
    function applyTheme() {
        document.documentElement.setAttribute('data-theme', appliedTheme.value);
    }

    /**
     * Set theme mode and save to server
     */
    async function setThemeMode(mode) {
        themeMode.value = mode;
        applyTheme();

        // Save to server
        try {
            await fetch('/api/v1/kiosk/config/theme', {
                method: 'POST',
                headers: {
                    'X-Machine-UUID': machineUuid.value,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme_mode: mode }),
            });
        } catch (error) {
            console.error('Failed to save theme preference:', error);
        }
    }

    /**
     * Toggle between light and dark (skip auto)
     */
    function toggleTheme() {
        const newMode = appliedTheme.value === 'light' ? 'dark' : 'light';
        setThemeMode(newMode);
    }

    /**
     * Cycle through all modes: auto -> light -> dark -> auto
     */
    function cycleThemeMode() {
        const modes = ['auto', 'light', 'dark'];
        const currentIndex = modes.indexOf(themeMode.value);
        const nextIndex = (currentIndex + 1) % modes.length;
        setThemeMode(modes[nextIndex]);
    }

    // Watch for theme changes
    watch(appliedTheme, () => {
        applyTheme();
    });

    return {
        // State
        themeMode,
        timezone,

        // Getters
        appliedTheme,

        // Actions
        initialize,
        setThemeMode,
        toggleTheme,
        cycleThemeMode,
    };
});
