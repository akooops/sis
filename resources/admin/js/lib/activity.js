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

export const ACTIVITY_LOG_NAMES = ['users', 'roles', 'permissions', 'api-keys', 'media', 'integrations', 'notifications', 'notification-groups', 'languages', 'translations', 'settings', 'pages', 'articles', 'albums', 'brands', 'brand-asset-groups', 'brand-assets', 'events', 'achievements', 'categories', 'partners', 'documents', 'banners', 'calendars', 'contact-details', 'newsletters', 'newsletter-groups', 'newsletter-group-subscribers', 'programs', 'streams', 'grades', 'job-offers', 'job-applications', 'candidates', 'clusters', 'visit-services', 'visit-slots', 'visitors', 'visit-reservations', 'countries', 'menus', 'menu-items', 'forms', 'form-pages', 'form-fields', 'form-webhooks', 'form-submissions', 'auth'];

export const ACTIVITY_EVENTS = [
    'created',
    'updated',
    'deleted',
    'attached',
    'detached',
    'scanned-clean',
    'scanned-infected',
    'scan-failed',
    'exported',
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
    settings: 'Settings',
    pages: 'Pages',
    articles: 'Articles',
    albums: 'Albums',
    brands: 'Brands',
    'brand-asset-groups': 'Brand Asset Groups',
    'brand-assets': 'Brand Assets',
    events: 'Events',
    achievements: 'Achievements',
    partners: 'Partners',
    documents: 'Documents',
    banners: 'Banners',
    calendars: 'Calendars',
    'contact-details': 'Contact Details',
    newsletters: 'Newsletters',
    'newsletter-groups': 'Newsletter Groups',
    'newsletter-group-subscribers': 'Subscribers',
    programs: 'Programs',
    streams: 'Streams',
    grades: 'Grades',
    'job-offers': 'Job Offers',
    'job-applications': 'Job Applications',
    candidates: 'Candidates',
    clusters: 'Talent Pools',
    'visit-services': 'Visit Services',
    'visit-slots': 'Visit Time Slots',
    visitors: 'Visitors',
    'visit-reservations': 'Visit Reservations',
    countries: 'Countries',
    menus: 'Menus',
    'menu-items': 'Menu Items',
    categories: 'Categories',
    forms: 'Forms',
    'form-pages': 'Form Pages',
    'form-fields': 'Form Fields',
    'form-webhooks': 'Form Webhooks',
    'form-submissions': 'Form Submissions',
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
    setting: 'Setting',
    page: 'Page',
    article: 'Article',
    album: 'Album',
    brand: 'Brand',
    brand_asset_group: 'Brand asset group',
    brand_asset: 'Brand asset',
    event: 'Event',
    achievement: 'Achievement',
    partner: 'Partner',
    document: 'Document',
    banner: 'Banner',
    calendar: 'Calendar',
    contact_detail: 'Contact detail',
    newsletter: 'Newsletter',
    newsletter_group: 'Newsletter Group',
    newsletter_group_subscriber: 'Subscriber',
    program: 'Program',
    stream: 'Stream',
    grade: 'Grade',
    job_offer: 'Job Offer',
    job_application: 'Job application',
    candidate: 'Candidate',
    cluster: 'Talent pool',
    visit_service: 'Visit service',
    visit_slot: 'Visit time slot',
    visitor: 'Visitor',
    visit_reservation: 'Visit reservation',
    visit_attendee: 'Visit attendee',
    country: 'Country',
    menu: 'Menu',
    menu_item: 'Menu Item',
    category: 'Category',
    form: 'Form',
    form_page: 'Form page',
    form_field: 'Form field',
    form_webhook: 'Form webhook',
    form_blocked_ip: 'Blocked IP',
    form_submission: 'Form submission',
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
    exported: 'Exported',
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
    // `updated` is the only one the UI can cause — there is no store and no
    // destroy route. `created` still fires on the reseed that adds a setting;
    // `deleted` only if a row is removed by hand, since the seeder never sweeps
    // one. Both are here so those rows still read in English.
    settings: {
        created: 'Added the setting :name',
        updated: 'Updated the setting :name',
        deleted: 'Deleted the setting :name',
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
    brands: {
        created: 'Created the brand :name',
        updated: 'Updated the brand :name',
        deleted: 'Deleted the brand :name',
    },
    'brand-asset-groups': {
        created: 'Created the asset group :name',
        updated: 'Updated the asset group :name',
        deleted: 'Deleted the asset group :name',
    },
    'brand-assets': {
        created: 'Created the brand asset :name',
        updated: 'Updated the brand asset :name',
        deleted: 'Deleted the brand asset :name',
    },
    events: {
        created: 'Created the event :name',
        updated: 'Updated the event :name',
        deleted: 'Deleted the event :name',
    },
    achievements: {
        created: 'Created the achievement :name',
        updated: 'Updated the achievement :name',
        deleted: 'Deleted the achievement :name',
    },
    categories: {
        created: 'Created the category :name',
        updated: 'Updated the category :name',
        deleted: 'Deleted the category :name',
    },
    partners: {
        created: 'Created the partner :name',
        updated: 'Updated the partner :name',
        deleted: 'Deleted the partner :name',
    },
    documents: {
        created: 'Created the document :name',
        updated: 'Updated the document :name',
        deleted: 'Deleted the document :name',
    },
    banners: {
        created: 'Created the banner :name',
        updated: 'Updated the banner :name',
        deleted: 'Deleted the banner :name',
    },
    calendars: {
        created: 'Created the calendar :name',
        updated: 'Updated the calendar :name',
        deleted: 'Deleted the calendar :name',
    },
    'contact-details': {
        created: 'Created the contact detail :name',
        updated: 'Updated the contact detail :name',
        deleted: 'Deleted the contact detail :name',
    },
    newsletters: {
        created: 'Created the newsletter :name',
        updated: 'Updated the newsletter :name',
        deleted: 'Deleted the newsletter :name',
        attached: 'Added :name to a newsletter',
        detached: 'Removed :name from a newsletter',
    },
    'newsletter-groups': {
        created: 'Created the newsletter group :name',
        updated: 'Updated the newsletter group :name',
        deleted: 'Deleted the newsletter group :name',
    },
    'newsletter-group-subscribers': {
        created: 'Subscribed :name',
        updated: 'Updated the subscriber :name',
        deleted: 'Unsubscribed :name',
    },
    programs: {
        created: 'Created the program :name',
        updated: 'Updated the program :name',
        deleted: 'Deleted the program :name',
    },
    streams: {
        created: 'Created the stream :name',
        updated: 'Updated the stream :name',
        deleted: 'Deleted the stream :name',
    },
    grades: {
        created: 'Created the grade :name',
        updated: 'Updated the grade :name',
        deleted: 'Deleted the grade :name',
    },
    'job-offers': {
        created: 'Created the job offer :name',
        updated: 'Updated the job offer :name',
        deleted: 'Deleted the job offer :name',
    },
    // `updated` reads as the status move it almost always is — :status comes from
    // JobApplicationObserver::meta(). An application is never edited any other
    // way, so there is no second wording to fall back to.
    'job-applications': {
        created: 'Received an application from :name',
        updated: ':name is now :status',
        deleted: 'Deleted the application from :name',
        // The export writes this row by hand, and it has no subject: an export
        // spans postings, so there is no single record it was performed on.
        exported: 'Exported :count application(s)',
    },
    candidates: {
        created: 'Added the candidate :name',
        updated: 'Updated the candidate :name',
        deleted: 'Deleted the candidate :name',
    },
    // No `attached`: pool membership has no add path, because the nightly rebuild
    // clears the table before reassigning. `detached` is hand-rolled by the pivot
    // controllers, since the pivots themselves are unobserved.
    clusters: {
        created: 'Created the talent pool :name',
        updated: 'Renamed the talent pool :name',
        deleted: 'Deleted the talent pool :name',
        detached: 'Removed :name from the pool',
    },
    'visit-services': {
        created: 'Created the visit :name',
        updated: 'Updated the visit :name',
        deleted: 'Deleted the visit :name',
    },
    // :name is the slot's own start time — a time slot has no other name, and
    // reading "Closed 14 Oct 2026, 09:00" is what an audit row is for.
    'visit-slots': {
        created: 'Added the time slot :name',
        updated: 'Updated the time slot :name',
        deleted: 'Deleted the time slot :name',
    },
    visitors: {
        created: 'Added the visitor :name',
        updated: 'Updated the visitor :name',
        deleted: 'Deleted the visitor :name',
    },
    // `updated` reads as the status move it almost always is — :status comes
    // from VisitReservationObserver::meta().
    'visit-reservations': {
        created: 'Received a visit booking from :name',
        updated: ':name is now :status',
        deleted: 'Deleted the booking from :name',
        // Hand-rolled by CsvReservationWriter, and it has no subject: an
        // export spans visits, so there is no single record it was performed on.
        exported: 'Exported :count reservation(s)',
    },
    countries: {
        created: 'Created the country :name',
        updated: 'Updated the country :name',
        deleted: 'Deleted the country :name',
    },
    menus: {
        created: 'Created the menu :name',
        updated: 'Updated the menu :name',
        deleted: 'Deleted the menu :name',
    },
    'menu-items': {
        created: 'Created the menu item :name',
        updated: 'Updated the menu item :name',
        deleted: 'Deleted the menu item :name',
    },
    forms: {
        created: 'Created the form :name',
        updated: 'Updated the form :name',
        deleted: 'Deleted the form :name',
        // Blocked countries, blocked IPs and notification groups all log
        // against the form rather than against a link nobody opens.
        attached: 'Added :name to the form',
        detached: 'Removed :name from the form',
    },
    'form-pages': {
        created: 'Added the form page :name',
        updated: 'Updated the form page :name',
        deleted: 'Removed the form page :name',
    },
    'form-fields': {
        created: 'Added the field :name',
        updated: 'Updated the field :name',
        deleted: 'Removed the field :name',
    },
    'form-webhooks': {
        created: 'Added the webhook :name',
        updated: 'Updated the webhook :name',
        deleted: 'Removed the webhook :name',
    },
    'form-submissions': {
        // Submissions are not audited on create or update — the table is the
        // record. Only an admin destroying one is worth a row.
        deleted: 'Deleted the submission :name',
        // Reading data out changes no column, so there is nothing to diff — the
        // export writes this row by hand. The subject is the FORM.
        exported: 'Exported :count submission(s) from :name',
    },
    auth: {
        login: ':name signed in',
        logout: ':name signed out',
        'login-failed': 'Failed sign-in attempt for :name',
    },
};

/**
 * Wording that depends on the SUBJECT rather than the module, consulted before
 * ACTIVITY_MESSAGES and only where the module's own wording would be wrong.
 *
 * A blocked IP is logged under `forms` — blocking an address IS an activity on
 * the form, and the form is where it is read — but the subject of the row is the
 * blocked-IP record, not the form. On the (log_name, event) pair alone its
 * `created` row renders as "Created the form 203.0.113.5". Every other subject
 * falls straight through to the module wording below.
 */
const SUBJECT_TYPE_MESSAGES = {
    form_blocked_ip: {
        created: 'Blocked the address :name',
        deleted: 'Unblocked the address :name',
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
 *   subject.<subject_type>.<event>  ->  messages.<log_name>.<event>
 *   ->  events.<event>  ->  description
 */
export function activityMessage(row) {
    if (!row) return '';

    const meta = row.properties?.meta ?? {};

    // A subject that is not what its module logs about wins over the module —
    // see SUBJECT_TYPE_MESSAGES.
    const bySubject = SUBJECT_TYPE_MESSAGES[row.subject_type]?.[row.event];
    if (bySubject) return interpolate(bySubject, meta);

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
    if (value === null || value === undefined || value === '') return '';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
}
