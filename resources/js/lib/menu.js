/**
 * Admin sidebar config — single source for the Metronic accordion menu.
 * A node is either a link ({ label, icon, route }) or an accordion group
 * ({ label, icon, children: [...] }). Children are { label, route, permission? }.
 * Items are permission-gated; empty groups are hidden.
 */
export const adminMenu = [
    { label: 'Dashboard', icon: 'ki-filled ki-element-11', route: 'web.admin.dashboard' },
    {
        label: 'Access',
        icon: 'ki-filled ki-shield-tick',
        // Ordered the way access is built up: a permission is granted to a role,
        // a role to a user, and an API key is the non-human holder of the same
        // permissions.
        children: [
            { label: 'Permissions', route: 'web.admin.permissions.index', permission: 'permissions.index' },
            { label: 'Roles', route: 'web.admin.roles.index', permission: 'roles.index' },
            { label: 'Users', route: 'web.admin.users.index', permission: 'users.index' },
            { label: 'API Keys', route: 'web.admin.api-keys.index', permission: 'api-keys.index' },
        ],
    },
    {
        label: 'Content',
        icon: 'ki-filled ki-picture',
        children: [
            { label: 'Media Library', route: 'web.admin.media.index', permission: 'media.index' },
        ],
    },
    {
        label: 'System',
        icon: 'ki-filled ki-setting-2',
        children: [
            { label: 'Integrations', route: 'web.admin.integrations.index', permission: 'integrations.index' },
            { label: 'Activity Log', route: 'web.admin.activities.index', permission: 'activities.index' },
        ],
    },
];
