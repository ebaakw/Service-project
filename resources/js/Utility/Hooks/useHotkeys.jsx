import {useEffect, useRef} from "react";

const normalizeKey = (key) => {
    const k = String(key || "").toLowerCase();
    if (k === "esc") return "escape";
    if (k === " ") return "space";
    return k;
};

const parseCombo = (combo) => {
    const raw = String(combo || "");
    const parts = raw
        .split("+")
        .map(p => p.trim().toLowerCase())
        .filter(Boolean);

    const has = (name) => parts.includes(name);

    const key = normalizeKey(parts[parts.length - 1] || "");

    return {
        key,
        ctrl: has("ctrl") || has("control"),
        alt: has("alt") || has("option"),
        shift: has("shift"),
        meta: has("meta") || has("cmd") || has("command"),
    };
};

const isEditableTarget = (target) => {
    if (!target) return false;
    const el = target;
    const tagName = el.tagName?.toLowerCase();
    if (el.isContentEditable) return true;
    if (tagName === "input" || tagName === "textarea" || tagName === "select") return true;
    return false;
};

/**
 * Global hotkeys hook.
 *
 * @param {Array<{
 *  combo: string,
 *  handler: (event: KeyboardEvent) => void,
 *  enabled?: boolean,
 *  preventDefault?: boolean,
 *  stopPropagation?: boolean,
 *  allowInInputs?: boolean,
 *  when?: (event: KeyboardEvent) => boolean,
 * }>} bindings
 * @param {{enabled?: boolean, target?: 'window'|'document'}} options
 */
export const useHotkeys = (bindings = [], options = {}) => {
    const {enabled = true, target = "window"} = options;

    const bindingsRef = useRef(bindings);
    useEffect(() => {
        bindingsRef.current = bindings;
    }, [bindings]);

    useEffect(() => {
        if (!enabled) return;

        const targetEl = target === "document" ? document : window;
        const listener = (event) => {
            const list = bindingsRef.current || [];

            for (const b of list) {
                if (!b || typeof b.handler !== "function") continue;
                if (b.enabled === false) continue;

                // Ignore while typing in inputs unless explicitly allowed.
                if (!b.allowInInputs && isEditableTarget(event.target)) continue;

                if (typeof b.when === "function" && b.when(event) === false) continue;

                const parsed = parseCombo(b.combo);
                const eventKey = normalizeKey(event.key);

                const modsMatch =
                    (!!parsed.ctrl === !!event.ctrlKey) &&
                    (!!parsed.alt === !!event.altKey) &&
                    (!!parsed.shift === !!event.shiftKey) &&
                    (!!parsed.meta === !!event.metaKey);

                if (!modsMatch) continue;
                if (parsed.key !== eventKey) continue;

                if (b.preventDefault) event.preventDefault();
                if (b.stopPropagation) event.stopPropagation();

                b.handler(event);
                break;
            }
        };

        targetEl.addEventListener("keydown", listener, {capture: true});
        return () => targetEl.removeEventListener("keydown", listener, {capture: true});
    }, [enabled, target]);
};

