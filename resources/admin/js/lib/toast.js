/**
 * Toast notifications — store + helper, replacing the old `window.toast`.
 * Mount <Toasts/> once (in a layout) and call `toast(...)` from anywhere.
 *
 *   import { toast } from '@/lib/toast';
 *   toast.success('Saved');
 *   toast.error('Something went wrong');
 */
import { writable } from 'svelte/store';

let seq = 0;
export const toasts = writable([]);

export function toast(message, { variant = 'info', duration = 4000 } = {}) {
    const id = ++seq;
    toasts.update((list) => [...list, { id, message, variant }]);
    if (duration) setTimeout(() => dismissToast(id), duration);
    return id;
}

export function dismissToast(id) {
    toasts.update((list) => list.filter((t) => t.id !== id));
}

toast.success = (message, opts) => toast(message, { ...opts, variant: 'success' });
toast.error = (message, opts) => toast(message, { ...opts, variant: 'destructive' });
toast.warning = (message, opts) => toast(message, { ...opts, variant: 'warning' });
toast.info = (message, opts) => toast(message, { ...opts, variant: 'info' });
