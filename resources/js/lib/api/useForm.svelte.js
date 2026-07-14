/**
 * Form composable — local form state + submit against the JSON API with
 * automatic 422 error mapping onto fields.
 *
 * Usage (in a component <script>):
 *   const form = useForm({ email: '', password: '' });
 *   async function submit() {
 *       const data = await form.submit('post', route('api.v1.auth.login'));
 *       if (data) router.visit(...);   // returns null on validation error
 *   }
 *   // form.data.email (bindable), form.errors.email, form.processing
 *
 * Sends JSON by default; pass a FormData body via `transform` for file uploads
 * (though the standard flow submits only media ids, so JSON is usually enough).
 * Must be called during component init (uses $state internally).
 */

import { api, ApiError } from './client';

export function useForm(initial = {}) {
    const data = $state({ ...initial });
    let errors = $state({});
    let processing = $state(false);
    let message = $state(null);

    function reset(...fields) {
        if (fields.length === 0) {
            for (const key of Object.keys(data)) data[key] = initial[key];
        } else {
            for (const key of fields) data[key] = initial[key];
        }
        errors = {};
        message = null;
    }

    function clearErrors(...fields) {
        if (fields.length === 0) {
            errors = {};
        } else {
            const next = { ...errors };
            for (const key of fields) delete next[key];
            errors = next;
        }
    }

    function setError(field, msg) {
        errors = { ...errors, [field]: msg };
    }

    /**
     * Submit the form. Resolves with the response payload on success, or `null`
     * when a validation error occurred (errors are populated). Other errors
     * (network, 500) re-throw so the caller can surface them.
     *
     * @param {'post'|'put'|'patch'|'delete'} method
     * @param {string} url
     * @param {{ transform?: (data:any)=>any }} [opts]
     */
    async function submit(method, url, opts = {}) {
        processing = true;
        errors = {};
        message = null;
        const body = opts.transform ? opts.transform({ ...data }) : { ...data };
        try {
            const result = await api[method](url, body);
            return result ?? true;
        } catch (err) {
            if (err instanceof ApiError) {
                message = err.message;
                if (err.status === 422) {
                    // Normalize Laravel's { field: [messages] } to { field: message }.
                    const mapped = {};
                    for (const [field, msgs] of Object.entries(err.errors ?? {})) {
                        mapped[field] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    errors = mapped;
                    return null;
                }
            }
            throw err;
        } finally {
            processing = false;
        }
    }

    return {
        data,
        get errors() {
            return errors;
        },
        get processing() {
            return processing;
        },
        get message() {
            return message;
        },
        hasErrors() {
            return Object.keys(errors).length > 0;
        },
        reset,
        clearErrors,
        setError,
        submit,
        post: (url, opts) => submit('post', url, opts),
        put: (url, opts) => submit('put', url, opts),
        patch: (url, opts) => submit('patch', url, opts),
        delete: (url, opts) => submit('delete', url, opts),
    };
}
