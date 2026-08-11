/**
 * Shared alert state for the wizards: success banners clear after 5s, errors
 * linger for 8s. Returned object is reactive, so components can read
 * `alert.type` / `alert.message` directly in markup.
 */
export function createAlerts() {
    let type = $state('success');
    let message = $state('');
    let timer;

    function show(nextType, nextMessage, ms) {
        window.clearTimeout(timer);
        type = nextType;
        message = nextMessage;
        timer = window.setTimeout(() => (message = ''), ms);
    }

    return {
        get type() {
            return type;
        },
        get message() {
            return message;
        },
        success: (text) => show('success', text, 5000),
        error: (text) => show('error', text, 8000),
        clear: () => {
            window.clearTimeout(timer);
            message = '';
        },
    };
}

/**
 * Laravel returns either {field: [msg]} or [{field, message}]; flatten both to
 * a plain {field: msg} map.
 */
export function normaliseErrors(errors) {
    if (!errors) {
        return {};
    }

    if (Array.isArray(errors)) {
        return Object.fromEntries(errors.map((error) => [error.field, error.message]));
    }

    return Object.fromEntries(
        Object.entries(errors).map(([field, value]) => [field, Array.isArray(value) ? value[0] : value])
    );
}
