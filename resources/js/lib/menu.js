/**
 * Admin sidebar config — single source for the Metronic accordion menu.
 * A node is either a link ({ labelKey, icon, route }) or an accordion group
 * ({ labelKey, icon, children: [...] }). Children are { labelKey, route, permission? }.
 * `labelKey` is an i18n dot-path resolved with $t in the Sidebar, so labels are
 * translated (EN/AR). Items are permission-gated; empty groups are hidden.
 */
export const adminMenu = [
    { labelKey: 'common.nav.dashboard', icon: 'ki-filled ki-element-11', route: 'web.home' },
    {
        labelKey: 'common.nav.access_control',
        icon: 'ki-filled ki-shield-tick',
        children: [
            { labelKey: 'common.nav.users', route: 'web.admin.users.index', permission: 'users.index' },
            { labelKey: 'common.nav.roles', route: 'web.admin.roles.index', permission: 'roles.index' },
            { labelKey: 'common.nav.permissions', route: 'web.admin.permissions.index', permission: 'permissions.index' },
            { labelKey: 'common.nav.api_keys', route: 'web.admin.api-keys.index', permission: 'api-keys.index' },
        ],
    },
    {
        labelKey: 'common.nav.content',
        icon: 'ki-filled ki-picture',
        children: [
            { labelKey: 'common.nav.media', route: 'web.admin.media.index', permission: 'media.index' },
        ],
    },
];
