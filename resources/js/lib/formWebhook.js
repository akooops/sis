/**
 * Form webhooks — the labels and small helpers the drawer and its form share.
 *
 * Every list here mirrors something the server enforces (FormWebhook::METHODS /
 * AUTH_TYPES), the same way FORM_STATUS_LABELS mirrors FormStatus: the client
 * offers the choices, the server is what refuses the rest.
 *
 * There is nothing here about the payload — the body is fixed (see
 * WebhookPayload), so the only thing a webhook configures is where it goes and
 * how it authenticates.
 */

export const WEBHOOK_METHODS = ['POST', 'PUT', 'PATCH'];

/** Mirrors FormWebhook::AUTH_TYPES. */
export const WEBHOOK_AUTH_TYPES = [
    { value: 'none', label: 'None' },
    { value: 'bearer', label: 'Bearer token' },
    { value: 'headers', label: 'Static headers' },
];

export const WEBHOOK_AUTH_LABELS = {
    none: 'None',
    bearer: 'Bearer token',
    headers: 'Static headers',
};

/*
 * There is no delivery-health helper. The row carries `last_delivered_at` and
 * nothing else about a delivery — no status code, no failure counter — so "when
 * did this last fire" is the whole signal, and both the drawer column and the
 * form strip render that timestamp directly. Why a delivery failed is in the
 * `integrations` log, which is where that question gets answered.
 */

/** Whether an auth type needs a secret from the admin at all. */
export function needsAuthSecret(authType) {
    return authType === 'bearer' || authType === 'headers';
}
