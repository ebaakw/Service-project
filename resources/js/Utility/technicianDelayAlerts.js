export const TECHNICIAN_DELAY_SOUND_KEY = 'technician_delay_sound_enabled';

let loopAudio = null;
let loopTimer = null;

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

    if (!enabled) {
        stopLoopingAlertSound();
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

export function startLoopingAlertSound(intervalMs = 4000) {
    if (!isDelaySoundEnabled()) {
        stopLoopingAlertSound();
        return;
    }

    if (loopTimer !== null) {
        return;
    }

    const playOnce = () => {
        if (!isDelaySoundEnabled()) {
            stopLoopingAlertSound();
            return;
        }

        try {
            if (loopAudio) {
                loopAudio.pause();
                loopAudio.currentTime = 0;
            }
            loopAudio = new Audio('/sounds/technician-delay-alert.wav');
            loopAudio.volume = 0.7;
            void loopAudio.play().catch(() => {
                // ignore autoplay blocks
            });
        } catch {
            // ignore audio failures
        }
    };

    playOnce();
    loopTimer = window.setInterval(playOnce, intervalMs);
}

export function stopLoopingAlertSound() {
    if (loopTimer !== null) {
        window.clearInterval(loopTimer);
        loopTimer = null;
    }

    if (loopAudio) {
        try {
            loopAudio.pause();
            loopAudio.currentTime = 0;
        } catch {
            // ignore
        }
        loopAudio = null;
    }
}
