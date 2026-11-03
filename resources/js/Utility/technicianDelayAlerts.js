export const TECHNICIAN_DELAY_SOUND_KEY = 'technician_delay_sound_enabled';

export function isDelaySoundEnabled() {
    try {
        const raw = window.localStorage.getItem(TECHNICIAN_DELAY_SOUND_KEY);
        if (raw === null) {
            return true;
        }
        return raw === '1' || raw === 'true';
    } catch {
        return true;
    }
}

export function setDelaySoundEnabled(enabled) {
    try {
        window.localStorage.setItem(TECHNICIAN_DELAY_SOUND_KEY, enabled ? '1' : '0');
    } catch {
        // ignore storage failures
    }
}

export function playDelayAlertSound() {
    if (!isDelaySoundEnabled()) {
        return;
    }

    try {
        const audio = new Audio('/sounds/technician-delay-alert.wav');
        audio.volume = 0.7;
        void audio.play().catch(() => {
            // Browsers may block autoplay until a user gesture; ignore.
        });
    } catch {
        // ignore audio failures
    }
}
