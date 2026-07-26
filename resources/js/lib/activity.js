/**
 * Shared bits for reading the activity log.
 *
 * The API returns a stored English `description` plus a stable (log_name, event)
 * pair. Messages are built from that pair rather than from a key stored in the
 * row: audit rows are immutable and outlive any translation catalogue, so a key
 * written into the database in 2026 would render as raw dotted text the day
 * someone renames it.
 */

/** Causer types, and the route each one's picker reads from. */
export const CAUSER_RESOURCES = {
    user: { route: 'api.v1.admin.users.index', labelKey: 'username' },
    api_key: { route: 'api.v1.admin.api-keys.index', labelKey: 'name' },
};

export const ACTIVITY_LOG_NAMES = ['users', 'roles', 'permissions', 'api-keys', 'media', 'integrations', 'notifications', 'notification-groups', 'languages', 'translations', 'pages', 'articles', 'albums', 'events', 'auth'];

export const ACTIVITY_EVENTS = [
    'created',
    'updated',
    'deleted',
    'attached',
    'detached',
    'scanned-clean',
    'scanned-infected',
    'scan-failed',
    'login',
    'logout',
    'login-failed',
];

/** Human label per module, for filters and the drawer. */
export const LOG_NAME_LABELS = {
    users: 'Users',
    roles: 'Roles',
    permissions: 'Permissions',
    'api-keys': 'API keys',
    media: 'Media',
    integrations: 'Integrations',
    notifications: 'Notifications',
    'notification-groups': 'Notification groups',
    languages: 'Languages',
    translations: 'Translations',
    pages: 'Pages',
    articles: 'Articles',
    albums: 'Albums',
    events: 'Events',
    auth: 'Authentication',
};

/** Human label per subject type (MorphType aliases, never class names). */
export const SUBJECT_TYPE_LABELS = {
    user: 'User',
    role: 'Role',
    permission: 'Permission',
    api_key: 'API key',
    media: 'File',
    integration: 'Integration',
    notification: 'Notification',
    notification_group: 'Notification group',
    notification_type: 'Notification type',
    language: 'Language',
    translation_key: 'Translation key',
    page: 'Page',
    article: 'Article',
    album: 'Album',
    event: 'Event',
};

/** Generic fallback wording, used when there is no per-module message below. */
export const EVENT_LABELS = {
    created: 'Created',
    updated: 'Updated',
    deleted: 'Deleted',
    attached: 'Added',
    detached: 'Removed',
    'scanned-clean': 'Scan passed',
    'scanned-infected': 'Malware found',
    'scan-failed': 'Scan failed',
    login: 'Signed in',
    logout: 'Signed out',
    'login-failed': 'Failed sign-in',
};

/** Preferred, per-module wording. `:name` comes from properties.meta. */
const ACTIVITY_MESSAGES = {
    users: {
        created: 'Created the user :name',
        updated: 'Updated the user :name',
        deleted: 'Deleted the user :name',
        attached: 'Granted the role :name',
        detached: 'Revoked the role :name',
    },
    roles: {
        created: 'Created the role :name',
        updated: 'Updated the role :name',
        deleted: 'Deleted the role :name',
        attached: 'Granted the permission :name',
        detached: 'Revoked the permission :name',
    },
    permissions: {
        created: 'Created the permission :name',
        updated: 'Updated the permission :name',
        deleted: 'Deleted the permission :name',
    },
    'api-keys': {
        created: 'Created the API key :name',
        updated: 'Updated the API key :name',
        deleted: 'Deleted the API key :name',
        attached: 'Granted the permission :name',
        detached: 'Revoked the permission :name',
    },
    media: {
        created: 'Uploaded :name',
        deleted: 'Deleted :name',
        attached: 'Attached :name to :collection',
        detached: 'Detached :name from :collection',
        'scanned-clean': ':name passed the malware scan',
        'scanned-infected': ':name was blocked: malware detected',
        'scan-failed': 'Could not scan :name',
    },
    integrations: {
        created: 'Added the provider :name',
        updated: 'Updated the provider :name',
        deleted: 'Deleted the provider :name',
    },
    notifications: {
        created: 'Created the notification :name',
        deleted: 'Deleted the notification :name',
        attached: 'Delivered to :name',
        detached: 'Removed from :name',
    },
    // attach/detach cover both pivots on a group (types and members).
    'notification-groups': {
        created: 'Created the group :name',
        updated: 'Updated the group :name',
        deleted: 'Deleted the group :name',
        attached: 'Added :name to the group',
        detached: 'Removed :name from the group',
    },
    languages: {
        created: 'Added the language :name',
        updated: 'Updated the language :name',
        deleted: 'Deleted the language :name',
    },
    // Subject is the TranslationKey, so one drawer shows a key's history across
    // every locale — :locale is what tells the rows apart.
    translations: {
        updated: 'Updated the :locale translation of :name',
    },
    pages: {
        created: 'Created the page :name',
        updated: 'Updated the page :name',
        deleted: 'Deleted the page :name',
    },
    articles: {
        created: 'Created the article :name',
        updated: 'Updated the article :name',
        deleted: 'Deleted the article :name',
    },
    albums: {
        created: 'Created the album :name',
        updated: 'Updated the album :name',
        deleted: 'Deleted the album :name',
    },
    events: {
        created: 'Created the event :name',
        updated: 'Updated the event :name',
        deleted: 'Deleted the event :name',
    },
    auth: {
        login: ':name signed in',
        logout: ':name signed out',
        'login-failed': 'Failed sign-in attempt for :name',
    },
};

/** Replace :name placeholders with values from `meta`. */
function interpolate(text, values) {
    if (typeof text !== 'string' || !values) return text;
    return text.replace(/:(\w+)/g, (match, name) =>
        Object.prototype.hasOwnProperty.call(values, name) ? String(values[name]) : match,
    );
}

/**
 * Most specific wording available, falling back to the stored English so a row
 * is never unreadable:
 *   messages.<log_name>.<event>  ->  events.<event>  ->  description
 */
export function activityMessage(row) {
    if (!row) return '';

    const meta = row.properties?.meta ?? {};
    const specific = ACTIVITY_MESSAGES[row.log_name]?.[row.event];
    if (specific) return interpolate(specific, meta);

    const generic = EVENT_LABELS[row.event];
    if (generic) return interpolate(generic, meta);

    return row.description ?? row.event ?? '';
}

/** Badge variant per event — destructive for anything that removes or fails. */
export function eventVariant(event) {
    switch (event) {
        case 'created':
        case 'scanned-clean':
        case 'login':
            return 'success';
        case 'deleted':
        case 'scanned-infected':
        case 'scan-failed':
        case 'login-failed':
            return 'destructive';
        case 'updated':
        case 'attached':
            return 'primary';
        default:
            return 'secondary';
    }
}

export function eventIcon(event) {
    switch (event) {
        case 'created':
            return 'ki-filled ki-plus-squared';
        case 'updated':
            return 'ki-filled ki-pencil';
        case 'deleted':
            return 'ki-filled ki-trash';
        case 'attached':
            return 'ki-filled ki-paper-clip';
        case 'detached':
            return 'ki-filled ki-disconnect';
        case 'login':
        case 'logout':
        case 'login-failed':
            return 'ki-filled ki-entrance-right';
        case 'scanned-clean':
            return 'ki-filled ki-shield-tick';
        case 'scanned-infected':
        case 'scan-failed':
            return 'ki-filled ki-shield-cross';
        default:
            return 'ki-filled ki-time';
    }
}

/**
 * The two sides of a change, aligned into rows for a diff table. `created` has
 * no `old` side; attach/detach carry no column diff at all.
 */
export function diffRows(properties) {
    const oldValues = properties?.old ?? {};
    const newValues = properties?.attributes ?? {};
    // Insertion order, NOT sorted: the observer already wrote these in the
    // model's own column order (its migration order), which reads the way the
    // record does. Re-sorting here would throw that away.
    const keys = [...new Set([...Object.keys(oldValues), ...Object.keys(newValues)])];

    return keys.map((key) => ({
        key,
        from: formatValue(oldValues[key]),
        to: formatValue(newValues[key]),
        changed: JSON.stringify(oldValues[key]) !== JSON.stringify(newValues[key]),
    }));
}

export function hasDiff(properties) {
    return diffRows(properties).length > 0;
}

function formatValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}
