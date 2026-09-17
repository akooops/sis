<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    /**
     * Seed the application's permissions.
     */
    public function run(): void
    {
        /*
         * supports_web is true for every code: this catalogue exists to be granted
         * to a role, and a role is a web thing.
         *
         * supports_api splits on ONE line, and it is a line about blast radius
         * rather than about any individual module: an API key MAY READ and MUST
         * NOT WRITE. So `.index` and `.show` are api-grantable everywhere - the
         * dashboard and the settings catalogue included - and every other code is
         * web-only, whether it creates, edits, reorders, deletes, moves a state
         * machine or revokes a session. A leaked key is then a disclosure and
         * never a defacement, and nothing about the rule has to be re-litigated
         * per module the way "should an integration be allowed to approve a user"
         * would be.
         *
         * Two consequences worth knowing before you read the list:
         *
         * 1. The rule keys off the code's LAST SEGMENT, so five read-shaped codes
         *    that are not spelled index/show sit on the write side and stay
         *    web-only: `candidates.cv` and the four `*.export` codes. That is the
         *    conservative answer in each case - a CV is personal data behind a
         *    signed link, and an export is the whole table in one file - but it is
         *    a side effect of the spelling, not a separate decision. Renaming one
         *    of them to a `.show`-shaped code would silently open it to keys.
         *
         * 2. Read access is still per code. Granting a key `candidates.index`
         *    hands it applicant records; the rule says a key CAN hold that, not
         *    that it should. Scope the key.
         */
        $permissions = [
            // Index permissions that gate the admin page shells (routes/web.php).
            ['code' => 'dashboards.index', 'name' => 'View dashboard', 'supports_web' => true, 'supports_api' => true],

            // The traffic and activity panels ON the dashboard. Separate from
            // dashboards.index, which gates the page: an editor should reach their
            // landing page without necessarily reading the school's numbers.
            ['code' => 'analytics.index', 'name' => 'View site analytics', 'supports_web' => true, 'supports_api' => true],

            /*
             * Identity + RBAC.
             *
             * Note POST media (the upload endpoint) is deliberately ungated in
             * routes/api.php, so there is no media.store code to seed.
             */
            ['code' => 'users.index', 'name' => 'View users', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'users.show', 'name' => 'View a user', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'users.store', 'name' => 'Create users', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'users.update', 'name' => 'Update users', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'users.destroy', 'name' => 'Delete users', 'supports_web' => true, 'supports_api' => false],

            // One code per transition of App\States\User\UserStatus, so a role can
            // verify an identity without admitting it, or reject without being able
            // to approve. Nothing ever returns to pending, so pending has no code.
            ['code' => 'users.verify', 'name' => 'Verify users', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'users.approve', 'name' => 'Approve users', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'users.reject', 'name' => 'Reject users', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'users.logout-devices', 'name' => 'Sign a user out of every device', 'supports_web' => true, 'supports_api' => false],

            // Sessions are read per user, and deleting the row IS the logout. Both
            // codes only ever REMOVE access, so an API key may safely hold them.
            ['code' => 'sessions.index', 'name' => 'View user sessions', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'sessions.destroy', 'name' => 'Revoke a session', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'user-roles.index', 'name' => 'View user roles', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'user-roles.store', 'name' => 'Assign roles to users', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'user-roles.destroy', 'name' => 'Remove roles from users', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'roles.index', 'name' => 'View roles', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'roles.show', 'name' => 'View a role', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'roles.store', 'name' => 'Create roles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'roles.update', 'name' => 'Update roles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'roles.destroy', 'name' => 'Delete roles', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'role-permissions.index', 'name' => 'View role permissions', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'role-permissions.store', 'name' => 'Grant permissions to a role', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'role-permissions.destroy', 'name' => 'Revoke permissions from a role', 'supports_web' => true, 'supports_api' => false],

            // Read-only: the catalogue IS this file, so there is no store, update
            // or destroy code to grant.
            ['code' => 'permissions.index', 'name' => 'View permissions', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'permissions.show', 'name' => 'View a permission', 'supports_web' => true, 'supports_api' => true],

            ['code' => 'api-keys.index', 'name' => 'View API keys', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'api-keys.show', 'name' => 'View an API key', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'api-keys.store', 'name' => 'Create API keys', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'api-keys.update', 'name' => 'Update API keys', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'api-keys.rotate', 'name' => 'Rotate an API key secret', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'api-keys.revoke', 'name' => 'Revoke an API key', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'api-keys.destroy', 'name' => 'Delete API keys', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'api-key-permissions.index', 'name' => 'View API key permissions', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'api-key-permissions.store', 'name' => 'Grant permissions to an API key', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'api-key-permissions.destroy', 'name' => 'Revoke permissions from an API key', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'media.index', 'name' => 'View media', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'media.detach', 'name' => 'Detach media', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'media.destroy', 'name' => 'Delete media', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'activities.index', 'name' => 'View activity log', 'supports_web' => true, 'supports_api' => true],

            ['code' => 'integrations.index', 'name' => 'View integrations', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'integrations.store', 'name' => 'Create integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.update', 'name' => 'Update integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.destroy', 'name' => 'Delete integrations', 'supports_web' => true, 'supports_api' => false],

            // Notification groups + members (the inbox itself is auth-only, no permission).
            ['code' => 'notification-groups.index', 'name' => 'View notification groups', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'notification-groups.store', 'name' => 'Create notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.update', 'name' => 'Update notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.destroy', 'name' => 'Delete notification groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'notification-group-users.index', 'name' => 'View group members', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'notification-group-users.store', 'name' => 'Add group members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-group-users.destroy', 'name' => 'Remove group members', 'supports_web' => true, 'supports_api' => false],

            // Languages + translations. The read-only key registry is gated by
            // translations.index — it has no codes of its own.
            ['code' => 'languages.index', 'name' => 'View languages', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'languages.store', 'name' => 'Create languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.update', 'name' => 'Update languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.destroy', 'name' => 'Delete languages', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'translations.index', 'name' => 'View translations', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'translations.update', 'name' => 'Update translations', 'supports_web' => true, 'supports_api' => false],

            // Settings are seeded from config, so a value is the only thing an
            // admin writes — there is no store or destroy code to grant.
            ['code' => 'settings.index', 'name' => 'View settings', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'settings.update', 'name' => 'Update settings', 'supports_web' => true, 'supports_api' => false],

            // Content pages.
            ['code' => 'pages.index', 'name' => 'View pages', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'pages.store', 'name' => 'Create pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.update', 'name' => 'Update pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.destroy', 'name' => 'Delete pages', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'articles.index', 'name' => 'View articles', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'articles.store', 'name' => 'Create articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.update', 'name' => 'Update articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.destroy', 'name' => 'Delete articles', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'albums.index', 'name' => 'View albums', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'albums.store', 'name' => 'Create albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.update', 'name' => 'Update albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.destroy', 'name' => 'Delete albums', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'brands.index', 'name' => 'View brands', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'brands.store', 'name' => 'Create brands', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brands.update', 'name' => 'Update brands', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brands.destroy', 'name' => 'Delete brands', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'brand-asset-groups.index', 'name' => 'View brand asset groups', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'brand-asset-groups.store', 'name' => 'Create brand asset groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-asset-groups.update', 'name' => 'Update brand asset groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-asset-groups.reorder', 'name' => 'Reorder brand asset groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-asset-groups.destroy', 'name' => 'Delete brand asset groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'brand-assets.index', 'name' => 'View brand assets', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'brand-assets.store', 'name' => 'Create brand assets', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-assets.update', 'name' => 'Update brand assets', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-assets.reorder', 'name' => 'Reorder brand assets', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-assets.destroy', 'name' => 'Delete brand assets', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'events.index', 'name' => 'View events', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'events.store', 'name' => 'Create events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.update', 'name' => 'Update events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.destroy', 'name' => 'Delete events', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'categories.index', 'name' => 'View categories', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'categories.store', 'name' => 'Create categories', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'categories.update', 'name' => 'Update categories', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'categories.destroy', 'name' => 'Delete categories', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'achievements.index', 'name' => 'View achievements', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'achievements.store', 'name' => 'Create achievements', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'achievements.update', 'name' => 'Update achievements', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'achievements.destroy', 'name' => 'Delete achievements', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'partners.index', 'name' => 'View partners', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'partners.store', 'name' => 'Create partners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'partners.update', 'name' => 'Update partners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'partners.reorder', 'name' => 'Reorder partners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'partners.destroy', 'name' => 'Delete partners', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'documents.index', 'name' => 'View documents', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'documents.store', 'name' => 'Create documents', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'documents.update', 'name' => 'Update documents', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'documents.destroy', 'name' => 'Delete documents', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'banners.index', 'name' => 'View banners', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'banners.store', 'name' => 'Create banners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'banners.update', 'name' => 'Update banners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'banners.reorder', 'name' => 'Reorder banners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'banners.destroy', 'name' => 'Delete banners', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'calendars.index', 'name' => 'View calendars', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'calendars.store', 'name' => 'Create calendars', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'calendars.update', 'name' => 'Update calendars', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'calendars.destroy', 'name' => 'Delete calendars', 'supports_web' => true, 'supports_api' => false],

            // The read-only type registry is gated by contact-details.index — it
            // has no code of its own.
            ['code' => 'contact-details.index', 'name' => 'View contact details', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'contact-details.store', 'name' => 'Create contact details', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'contact-details.update', 'name' => 'Update contact details', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'contact-details.reorder', 'name' => 'Reorder contact details', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'contact-details.destroy', 'name' => 'Delete contact details', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'newsletters.index', 'name' => 'View newsletters', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'newsletters.store', 'name' => 'Create newsletters', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletters.update', 'name' => 'Update newsletters', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletters.destroy', 'name' => 'Delete newsletters', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'newsletter-groups.index', 'name' => 'View newsletter groups', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'newsletter-groups.store', 'name' => 'Create newsletter groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-groups.update', 'name' => 'Update newsletter groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-groups.destroy', 'name' => 'Delete newsletter groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'newsletter-group-subscribers.index', 'name' => 'View newsletter subscribers', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'newsletter-group-subscribers.store', 'name' => 'Create newsletter subscribers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-group-subscribers.update', 'name' => 'Update newsletter subscribers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-group-subscribers.destroy', 'name' => 'Delete newsletter subscribers', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'programs.index', 'name' => 'View programs', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'programs.store', 'name' => 'Create programs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'programs.update', 'name' => 'Update programs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'programs.reorder', 'name' => 'Reorder programs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'programs.destroy', 'name' => 'Delete programs', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'streams.index', 'name' => 'View streams', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'streams.store', 'name' => 'Create streams', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'streams.update', 'name' => 'Update streams', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'streams.reorder', 'name' => 'Reorder streams', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'streams.destroy', 'name' => 'Delete streams', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'grades.index', 'name' => 'View grades', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'grades.store', 'name' => 'Create grades', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'grades.update', 'name' => 'Update grades', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'grades.reorder', 'name' => 'Reorder grades', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'grades.destroy', 'name' => 'Delete grades', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'job-offers.index', 'name' => 'View job offers', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'job-offers.store', 'name' => 'Create job offers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offers.update', 'name' => 'Update job offers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offers.destroy', 'name' => 'Delete job offers', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'job-applications.index', 'name' => 'View job applications', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'job-applications.show', 'name' => 'View a job application', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'job-applications.destroy', 'name' => 'Delete job applications', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-applications.export', 'name' => 'Export job applications', 'supports_web' => true, 'supports_api' => false],
            /*
             * One permission PER TRANSITION, not a single "update".
             *
             * Moving an application forward and rejecting one are different acts
             * with different consequences, and a school will want a screener who
             * can shortlist but not reject, or a manager who can hire but does not
             * touch the queue. A blanket update permission cannot express either.
             */
            ['code' => 'job-applications.shortlist', 'name' => 'Shortlist applicants', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-applications.contact', 'name' => 'Mark applicants contacted', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-applications.call', 'name' => 'Mark applicants called', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-applications.hire', 'name' => 'Mark applicants hired', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-applications.reject', 'name' => 'Reject applicants', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'candidates.index', 'name' => 'View candidates', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'candidates.show', 'name' => 'View a candidate', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'candidates.update', 'name' => 'Update candidates', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'candidates.destroy', 'name' => 'Delete candidates', 'supports_web' => true, 'supports_api' => false],
            // The CV is personal data behind a signed, short-lived link — gated
            // separately so a screener can triage a list without pulling files.
            ['code' => 'candidates.cv', 'name' => 'Download candidate CVs', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'candidate-matches.index', 'name' => 'View candidate matches', 'supports_web' => true, 'supports_api' => true],

            ['code' => 'clusters.index', 'name' => 'View talent pools', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'clusters.update', 'name' => 'Rename talent pools', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'clusters.destroy', 'name' => 'Delete talent pools', 'supports_web' => true, 'supports_api' => false],
            // Membership is EMERGENT - RebuildClusters writes it nightly in bulk -
            // so a person only ever reads a pool or evicts a row from one. Neither
            // pivot has a store route or a store method, and the codes that used
            // to claim otherwise were removed.
            ['code' => 'candidate-clusters.index', 'name' => 'View pool members', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'candidate-clusters.destroy', 'name' => 'Remove pool members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offer-clusters.index', 'name' => 'View posting pools', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'job-offer-clusters.destroy', 'name' => 'Remove a posting from a pool', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'visit-services.index', 'name' => 'View visit services', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'visit-services.store', 'name' => 'Create visit services', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-services.update', 'name' => 'Update visit services', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-services.destroy', 'name' => 'Delete visit services', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'visit-slots.index', 'name' => 'View visit time slots', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'visit-slots.store', 'name' => 'Create visit time slots', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-slots.update', 'name' => 'Update visit time slots', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-slots.destroy', 'name' => 'Delete visit time slots', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'visit-reservations.index', 'name' => 'View visit reservations', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'visit-reservations.show', 'name' => 'View a visit reservation', 'supports_web' => true, 'supports_api' => true],
            /* The desk's internal note. Every other change to a reservation is a
               transition below, with its own permission and its own audit row. */
            ['code' => 'visit-reservations.update', 'name' => 'Write reservation notes', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-reservations.destroy', 'name' => 'Delete visit reservations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-reservations.export', 'name' => 'Export visit reservations', 'supports_web' => true, 'supports_api' => false],
            /*
             * One permission PER TRANSITION, the same reasoning the job-application
             * block above spells out.
             *
             * Confirming a booking and cancelling one are different acts with
             * different consequences for a family who has arranged their day around
             * it. A school will want a receptionist who can ring round and confirm
             * but cannot cancel, and somebody marking the register on the morning
             * who does neither. A blanket update permission cannot express either.
             */
            ['code' => 'visit-reservations.contact', 'name' => 'Mark reservations contacted', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-reservations.confirm', 'name' => 'Confirm reservations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-reservations.attend', 'name' => 'Mark reservations attended', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-reservations.no-show', 'name' => 'Mark reservations a no-show', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-reservations.cancel', 'name' => 'Cancel reservations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'visit-reservations.reopen', 'name' => 'Reopen cancelled reservations', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'facilities.index', 'name' => 'View facilities', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'facilities.store', 'name' => 'Create facilities', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facilities.update', 'name' => 'Update facilities', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facilities.destroy', 'name' => 'Delete facilities', 'supports_web' => true, 'supports_api' => false],

            /* No `.update` on either pivot: the link is two foreign keys, so
               attaching and detaching is the whole vocabulary. */
            ['code' => 'facility-articles.index', 'name' => 'View facility news', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'facility-articles.store', 'name' => 'Attach news to a facility', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-articles.destroy', 'name' => 'Detach news from a facility', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'facility-albums.index', 'name' => 'View facility albums', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'facility-albums.store', 'name' => 'Attach albums to a facility', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-albums.destroy', 'name' => 'Detach albums from a facility', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'facility-slots.index', 'name' => 'View facility time slots', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'facility-slots.store', 'name' => 'Create facility time slots', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-slots.update', 'name' => 'Update facility time slots', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-slots.destroy', 'name' => 'Delete facility time slots', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'facility-reservations.index', 'name' => 'View facility bookings', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'facility-reservations.show', 'name' => 'View a facility booking', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'facility-reservations.update', 'name' => 'Write facility booking notes', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-reservations.destroy', 'name' => 'Delete facility bookings', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-reservations.export', 'name' => 'Export facility bookings', 'supports_web' => true, 'supports_api' => false],
            /* One permission PER TRANSITION, the same reasoning the job-application
               and visit-reservation blocks above spell out. */
            ['code' => 'facility-reservations.contact', 'name' => 'Mark facility bookings contacted', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-reservations.confirm', 'name' => 'Confirm facility bookings', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-reservations.attend', 'name' => 'Mark facility bookings attended', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-reservations.no-show', 'name' => 'Mark facility bookings a no-show', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-reservations.cancel', 'name' => 'Cancel facility bookings', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'facility-reservations.reopen', 'name' => 'Reopen cancelled facility bookings', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'countries.index', 'name' => 'View countries', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'countries.store', 'name' => 'Create countries', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'countries.update', 'name' => 'Update countries', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'menus.index', 'name' => 'View menus', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'menus.store', 'name' => 'Create menus', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menus.update', 'name' => 'Update menus', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menus.destroy', 'name' => 'Delete menus', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'menu-items.index', 'name' => 'View menu items', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'menu-items.store', 'name' => 'Create menu items', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menu-items.update', 'name' => 'Update menu items', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menu-items.reorder', 'name' => 'Reorder menu items', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menu-items.destroy', 'name' => 'Delete menu items', 'supports_web' => true, 'supports_api' => false],

            // Forms. The builder has no code of its own — it is gated by the
            // page/field permissions, matching the <module>.<route-action> shape
            // every other module uses.
            ['code' => 'forms.index', 'name' => 'View forms', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'forms.show', 'name' => 'View a form', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'forms.store', 'name' => 'Create forms', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'forms.update', 'name' => 'Update forms', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'forms.destroy', 'name' => 'Delete forms', 'supports_web' => true, 'supports_api' => false],

            /*
             * A form's pages, fields and options have NO codes of their own,
             * because they have no endpoints of their own: the builder saves the
             * whole tree through one PUT forms/{form}/builder, gated on
             * forms.update. There were once form-pages.*, form-fields.{store,
             * update,reorder,destroy} and form-field-options.* here; every one of
             * them gated nothing, which in a roles UI reads as a promise that
             * granting "Create form fields" is what lets somebody add a field.
             *
             * form-fields.index survives because it does gate something - the
             * read-only field TYPE registry that the builder palette renders from.
             */
            ['code' => 'form-fields.index', 'name' => 'View form field types', 'supports_web' => true, 'supports_api' => true],

            ['code' => 'form-webhooks.index', 'name' => 'View form webhooks', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'form-webhooks.store', 'name' => 'Create form webhooks', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-webhooks.update', 'name' => 'Update form webhooks', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-webhooks.destroy', 'name' => 'Delete form webhooks', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-notification-groups.index', 'name' => 'View form notification groups', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'form-notification-groups.store', 'name' => 'Add form notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-notification-groups.destroy', 'name' => 'Remove form notification groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-blocked-countries.index', 'name' => 'View blocked countries', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'form-blocked-countries.store', 'name' => 'Block a country', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-blocked-countries.destroy', 'name' => 'Unblock a country', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-blocked-ips.index', 'name' => 'View blocked IPs', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'form-blocked-ips.store', 'name' => 'Block an IP', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-blocked-ips.destroy', 'name' => 'Unblock an IP', 'supports_web' => true, 'supports_api' => false],

            // Read-only: a submission is never deleted, so there is no
            // form-submissions.destroy code to grant.
            ['code' => 'form-submissions.index', 'name' => 'View form submissions', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'form-submissions.show', 'name' => 'View a form submission', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'form-submissions.export', 'name' => 'Export form submissions', 'supports_web' => true, 'supports_api' => false],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'code' => $permission['code'],
                ],
                [
                    'name' => $permission['name'],
                    'supports_web' => $permission['supports_web'],
                    'supports_api' => $permission['supports_api'],
                ]
            );
        }
    }
}
