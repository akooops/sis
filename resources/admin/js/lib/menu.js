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
            { label: 'Partners', route: 'web.admin.partners.index', permission: 'partners.index' },
            { label: 'Documents', route: 'web.admin.documents.index', permission: 'documents.index' },
            { label: 'Banners', route: 'web.admin.banners.index', permission: 'banners.index' },
            { label: 'Calendars', route: 'web.admin.calendars.index', permission: 'calendars.index' },
            { label: 'Contact Details', route: 'web.admin.contact-details.index', permission: 'contact-details.index' },
        ],
    },
    {
        label: 'Brand',
        icon: 'ki-filled ki-color-swatch',
        children: [
            // Brands first: groups hang off one, and the drill-downs land on the two below.
            { label: 'Brands', route: 'web.admin.brands.index', permission: 'brands.index' },
            { label: 'Asset Groups', route: 'web.admin.brand-asset-groups.index', permission: 'brand-asset-groups.index' },
            { label: 'Assets', route: 'web.admin.brand-assets.index', permission: 'brand-assets.index' },
        ],
    },
    {
        label: 'Academic',
        icon: 'ki-filled ki-teacher',
        children: [
            // Programs first: streams and grades both hang off one.
            { label: 'Programs', route: 'web.admin.programs.index', permission: 'programs.index' },
            { label: 'Streams', route: 'web.admin.streams.index', permission: 'streams.index' },
            { label: 'Grades', route: 'web.admin.grades.index', permission: 'grades.index' },
        ],
    },
    {
        label: 'Jobs',
        icon: 'ki-filled ki-briefcase',
        children: [
            // Offers first: an application is made against one, and candidates
            // and pools are what applications turn into.
            { label: 'Job Offers', route: 'web.admin.job-offers.index', permission: 'job-offers.index' },
            { label: 'Applications', route: 'web.admin.job-applications.index', permission: 'job-applications.index' },
            { label: 'Candidates', route: 'web.admin.candidates.index', permission: 'candidates.index' },
            { label: 'Talent Pools', route: 'web.admin.clusters.index', permission: 'clusters.index' },
        ],
    },
    {
        label: 'Visits',
        icon: 'ki-filled ki-calendar-tick',
        children: [
            // Same order as Jobs, and for the same reason: the visit is the thing
            // that exists first, its times hang off it, and a booking is what a
            // time turns into.
            { label: 'Visit Services', route: 'web.admin.visit-services.index', permission: 'visit-services.index' },
            { label: 'Time Slots', route: 'web.admin.visit-slots.index', permission: 'visit-slots.index' },
            { label: 'Bookings', route: 'web.admin.visit-reservations.index', permission: 'visit-reservations.index' },
        ],
    },
    {
        label: 'Forms',
        icon: 'ki-filled ki-questionnaire-tablet',
        children: [
            // Forms first: submissions hang off one, and the Submissions action drills in from there.
            { label: 'Forms', route: 'web.admin.forms.index', permission: 'forms.index' },
            { label: 'Submissions', route: 'web.admin.form-submissions.index', permission: 'form-submissions.index' },
            { label: 'Countries', route: 'web.admin.countries.index', permission: 'countries.index' },
        ],
    },
    {
        label: 'Newsletter',
        icon: 'ki-filled ki-sms',
        children: [
            { label: 'Newsletters', route: 'web.admin.newsletters.index', permission: 'newsletters.index' },
            // Groups first: subscribers hang off one, and the Subscribers action drills in from there.
            { label: 'Groups', route: 'web.admin.newsletter-groups.index', permission: 'newsletter-groups.index' },
            { label: 'Subscribers', route: 'web.admin.newsletter-group-subscribers.index', permission: 'newsletter-group-subscribers.index' },
        ],
    },
    {
        label: 'Navigation',
        icon: 'ki-filled ki-menu',
        children: [
            // Menus first: items hang off one, and the Items action drills in from there.
            { label: 'Menus', route: 'web.admin.menus.index', permission: 'menus.index' },
            { label: 'Menu Items', route: 'web.admin.menu-items.index', permission: 'menu-items.index' },
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
            { label: 'Settings', route: 'web.admin.settings.index', permission: 'settings.index' },
            { label: 'Notification Groups', route: 'web.admin.notification-groups.index', permission: 'notification-groups.index' },
            { label: 'Integrations', route: 'web.admin.integrations.index', permission: 'integrations.index' },
            { label: 'Activity Log', route: 'web.admin.activities.index', permission: 'activities.index' },
        ],
    },
];
