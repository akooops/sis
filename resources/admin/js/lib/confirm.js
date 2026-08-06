/**
 * Promise-based confirm dialog — replaces native confirm(). Mount
 * <ConfirmDialog/> once (in a layout), then:
 *
 *   import { confirm } from '@/lib/confirm';
 *   if (await confirm({ title: 'Delete?', body: '…', variant: 'destructive' })) { … }
 */
import { writable } from 'svelte/store';

export const confirmState = writable(null);

export function confirm({ title = null, body = null, confirmLabel = null, cancelLabel = null, variant = 'primary' } = {}) {
    return new Promise((resolve) => {
        confirmState.set({ title, body, confirmLabel, cancelLabel, variant, resolve });
    });
}

export function resolveConfirm(value) {
    confirmState.update((state) => {
        state?.resolve(value);
        return null;
    });
}
