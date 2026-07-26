/**
 * Admin sidebar config — single source for the Metronic accordion menu.
 * A node is either a link ({ label, icon, route }) or an accordion group
 * ({ label, icon, children: [...] }). Children are { label, route, permission? }.
 * Items are permission-gated; empty groups are hidden.
 *
 * Ordered by proximity to the signed-in admin: their own stuff (Personal),
 * then the languages the content is written in (Localisation), then what the
 * app holds (Content), then who can do what (Access), then how it is wired and
 * watched (System).
 */
export const adminMenu = [
    {
        label: 'Personal',
        icon: 'ki-filled ki-user',
        children: [
            { label: 'Dashboard', route: 'web.admin.dashboard' },
            { label: 'Notifications', route: 'web.admin.notifications.index' },
        ],
    },
    {
        label: 'Localisation',
        icon: 'ki-filled ki-flag',
        children: [
            { label: 'Languages', route: 'web.admin.languages.index', permission: 'languages.index' },
            { label: 'Translations', route: 'web.admin.translations.index', permission: 'translations.index' },
        ],
    },
    {
        label: 'Content',
        icon: 'ki-filled ki-picture',
        children: [
            { label: 'Media Library', route: 'web.admin.media.index', permission: 'media.index' },
            { label: 'Pages', route: 'web.admin.pages.index', permission: 'pages.index' },
            // Categories first: it is what the two below are filed under.
            { label: 'Categories', route: 'web.admin.categories.index', permission: 'categories.index' },
            { label: 'Articles', route: 'web.admin.articles.index', permission: 'articles.index' },
            { label: 'Achievements', route: 'web.admin.achievements.index', permission: 'achievements.index' },
            { label: 'Albums', route: 'web.admin.albums.index', permission: 'albums.index' },
            { label: 'Events', route: 'web.admin.events.index', permission: 'events.index' },
        ],
    },
    {
        label: 'Access',
        icon: 'ki-filled ki-shield-tick',
        children: [
            { label: 'Permissions', route: 'web.admin.permissions.index', permission: 'permissions.index' },
            { label: 'Roles', route: 'web.admin.roles.index', permission: 'roles.index' },
            { label: 'Users', route: 'web.admin.users.index', permission: 'users.index' },
            { label: 'API Keys', route: 'web.admin.api-keys.index', permission: 'api-keys.index' },
        ],
    },
    {
        label: 'System',
        icon: 'ki-filled ki-setting-2',
        children: [
            { label: 'Notification Groups', route: 'web.admin.notification-groups.index', permission: 'notification-groups.index' },
            { label: 'Integrations', route: 'web.admin.integrations.index', permission: 'integrations.index' },
            { label: 'Activity Log', route: 'web.admin.activities.index', permission: 'activities.index' },
        ],
    },
];
