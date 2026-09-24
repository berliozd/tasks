import {ref} from 'vue';

// Shared across the Tasks/Future/Completed pages — a single localStorage key
// so the preference is consistent everywhere rather than per-page.
const STORAGE_KEY = 'tasks-compact-mode';

const loadCompactMode = () => {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        // Default on when no preference has been stored yet.
        return stored === null ? true : stored === '1';
    } catch (e) {
        return true;
    }
};

const compactMode = ref(loadCompactMode());

export const useCompactMode = () => {
    const toggleCompactMode = () => {
        compactMode.value = !compactMode.value;
        try {
            localStorage.setItem(STORAGE_KEY, compactMode.value ? '1' : '0');
        } catch (e) {
            // localStorage unavailable (private mode, quota, ...) — toggle still works for this session.
        }
    };

    return {compactMode, toggleCompactMode};
};
