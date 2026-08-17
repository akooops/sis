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
        $permissions = [
            // Index permissions that gate the admin page shells (routes/web.php).
            ['code' => 'dashboards.index', 'name' => 'View dashboard', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'users.index', 'name' => 'View users', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'roles.index', 'name' => 'View roles', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'permissions.index', 'name' => 'View permissions', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'api-keys.index', 'name' => 'View API keys', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'media.index', 'name' => 'View media', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'media.detach', 'name' => 'Detach media', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'media.destroy', 'name' => 'Delete media', 'supports_web' => true, 'supports_api' => true],
            ['code' => 'activities.index', 'name' => 'View activity log', 'supports_web' => true, 'supports_api' => true],

            ['code' => 'integrations.index', 'name' => 'View integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.store', 'name' => 'Create integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.update', 'name' => 'Update integrations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'integrations.destroy', 'name' => 'Delete integrations', 'supports_web' => true, 'supports_api' => false],

            // Notification groups + members (the inbox itself is auth-only, no permission).
            ['code' => 'notification-groups.index', 'name' => 'View notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.store', 'name' => 'Create notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.update', 'name' => 'Update notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-groups.destroy', 'name' => 'Delete notification groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'notification-group-users.index', 'name' => 'View group members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-group-users.store', 'name' => 'Add group members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'notification-group-users.destroy', 'name' => 'Remove group members', 'supports_web' => true, 'supports_api' => false],

            // Languages + translations. The read-only key registry is gated by
            // translations.index — it has no codes of its own.
            ['code' => 'languages.index', 'name' => 'View languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.store', 'name' => 'Create languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.update', 'name' => 'Update languages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'languages.destroy', 'name' => 'Delete languages', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'translations.index', 'name' => 'View translations', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'translations.update', 'name' => 'Update translations', 'supports_web' => true, 'supports_api' => false],

            // Settings are seeded from config, so a value is the only thing an
            // admin writes — there is no store or destroy code to grant.
            ['code' => 'settings.index', 'name' => 'View settings', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'settings.update', 'name' => 'Update settings', 'supports_web' => true, 'supports_api' => false],

            // Content pages.
            ['code' => 'pages.index', 'name' => 'View pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.store', 'name' => 'Create pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.update', 'name' => 'Update pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'pages.destroy', 'name' => 'Delete pages', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'articles.index', 'name' => 'View articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.store', 'name' => 'Create articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.update', 'name' => 'Update articles', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'articles.destroy', 'name' => 'Delete articles', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'albums.index', 'name' => 'View albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.store', 'name' => 'Create albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.update', 'name' => 'Update albums', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'albums.destroy', 'name' => 'Delete albums', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'brands.index', 'name' => 'View brands', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brands.store', 'name' => 'Create brands', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brands.update', 'name' => 'Update brands', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brands.destroy', 'name' => 'Delete brands', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'brand-asset-groups.index', 'name' => 'View brand asset groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-asset-groups.store', 'name' => 'Create brand asset groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-asset-groups.update', 'name' => 'Update brand asset groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-asset-groups.reorder', 'name' => 'Reorder brand asset groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-asset-groups.destroy', 'name' => 'Delete brand asset groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'brand-assets.index', 'name' => 'View brand assets', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-assets.store', 'name' => 'Create brand assets', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-assets.update', 'name' => 'Update brand assets', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-assets.reorder', 'name' => 'Reorder brand assets', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'brand-assets.destroy', 'name' => 'Delete brand assets', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'events.index', 'name' => 'View events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.store', 'name' => 'Create events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.update', 'name' => 'Update events', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'events.destroy', 'name' => 'Delete events', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'categories.index', 'name' => 'View categories', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'categories.store', 'name' => 'Create categories', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'categories.update', 'name' => 'Update categories', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'categories.destroy', 'name' => 'Delete categories', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'achievements.index', 'name' => 'View achievements', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'achievements.store', 'name' => 'Create achievements', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'achievements.update', 'name' => 'Update achievements', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'achievements.destroy', 'name' => 'Delete achievements', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'partners.index', 'name' => 'View partners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'partners.store', 'name' => 'Create partners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'partners.update', 'name' => 'Update partners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'partners.reorder', 'name' => 'Reorder partners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'partners.destroy', 'name' => 'Delete partners', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'documents.index', 'name' => 'View documents', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'documents.store', 'name' => 'Create documents', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'documents.update', 'name' => 'Update documents', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'documents.destroy', 'name' => 'Delete documents', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'banners.index', 'name' => 'View banners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'banners.store', 'name' => 'Create banners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'banners.update', 'name' => 'Update banners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'banners.reorder', 'name' => 'Reorder banners', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'banners.destroy', 'name' => 'Delete banners', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'calendars.index', 'name' => 'View calendars', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'calendars.store', 'name' => 'Create calendars', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'calendars.update', 'name' => 'Update calendars', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'calendars.destroy', 'name' => 'Delete calendars', 'supports_web' => true, 'supports_api' => false],

            // The read-only type registry is gated by contact-details.index — it
            // has no code of its own.
            ['code' => 'contact-details.index', 'name' => 'View contact details', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'contact-details.store', 'name' => 'Create contact details', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'contact-details.update', 'name' => 'Update contact details', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'contact-details.reorder', 'name' => 'Reorder contact details', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'contact-details.destroy', 'name' => 'Delete contact details', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'newsletters.index', 'name' => 'View newsletters', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletters.store', 'name' => 'Create newsletters', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletters.update', 'name' => 'Update newsletters', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletters.destroy', 'name' => 'Delete newsletters', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'newsletter-groups.index', 'name' => 'View newsletter groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-groups.store', 'name' => 'Create newsletter groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-groups.update', 'name' => 'Update newsletter groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-groups.destroy', 'name' => 'Delete newsletter groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'newsletter-group-subscribers.index', 'name' => 'View newsletter subscribers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-group-subscribers.store', 'name' => 'Create newsletter subscribers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-group-subscribers.update', 'name' => 'Update newsletter subscribers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'newsletter-group-subscribers.destroy', 'name' => 'Delete newsletter subscribers', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'programs.index', 'name' => 'View programs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'programs.store', 'name' => 'Create programs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'programs.update', 'name' => 'Update programs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'programs.reorder', 'name' => 'Reorder programs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'programs.destroy', 'name' => 'Delete programs', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'streams.index', 'name' => 'View streams', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'streams.store', 'name' => 'Create streams', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'streams.update', 'name' => 'Update streams', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'streams.reorder', 'name' => 'Reorder streams', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'streams.destroy', 'name' => 'Delete streams', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'grades.index', 'name' => 'View grades', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'grades.store', 'name' => 'Create grades', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'grades.update', 'name' => 'Update grades', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'grades.reorder', 'name' => 'Reorder grades', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'grades.destroy', 'name' => 'Delete grades', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'job-offers.index', 'name' => 'View job offers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offers.store', 'name' => 'Create job offers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offers.update', 'name' => 'Update job offers', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offers.destroy', 'name' => 'Delete job offers', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'job-applications.index', 'name' => 'View job applications', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-applications.show', 'name' => 'View a job application', 'supports_web' => true, 'supports_api' => false],
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

            ['code' => 'candidates.index', 'name' => 'View candidates', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'candidates.show', 'name' => 'View a candidate', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'candidates.update', 'name' => 'Update candidates', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'candidates.destroy', 'name' => 'Delete candidates', 'supports_web' => true, 'supports_api' => false],
            // The CV is personal data behind a signed, short-lived link — gated
            // separately so a screener can triage a list without pulling files.
            ['code' => 'candidates.cv', 'name' => 'Download candidate CVs', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'candidate-matches.index', 'name' => 'View candidate matches', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'clusters.index', 'name' => 'View talent pools', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'clusters.update', 'name' => 'Rename talent pools', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'clusters.destroy', 'name' => 'Delete talent pools', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'candidate-clusters.index', 'name' => 'View pool members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'candidate-clusters.store', 'name' => 'Add pool members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'candidate-clusters.destroy', 'name' => 'Remove pool members', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offer-clusters.index', 'name' => 'View posting pools', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offer-clusters.store', 'name' => 'Add a posting to a pool', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'job-offer-clusters.destroy', 'name' => 'Remove a posting from a pool', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'countries.index', 'name' => 'View countries', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'countries.store', 'name' => 'Create countries', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'countries.update', 'name' => 'Update countries', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'menus.index', 'name' => 'View menus', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menus.store', 'name' => 'Create menus', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menus.update', 'name' => 'Update menus', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menus.destroy', 'name' => 'Delete menus', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'menu-items.index', 'name' => 'View menu items', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menu-items.store', 'name' => 'Create menu items', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menu-items.update', 'name' => 'Update menu items', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menu-items.reorder', 'name' => 'Reorder menu items', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'menu-items.destroy', 'name' => 'Delete menu items', 'supports_web' => true, 'supports_api' => false],

            // Forms. The builder has no code of its own — it is gated by the
            // page/field permissions, matching the <module>.<route-action> shape
            // every other module uses.
            ['code' => 'forms.index', 'name' => 'View forms', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'forms.show', 'name' => 'View a form', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'forms.store', 'name' => 'Create forms', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'forms.update', 'name' => 'Update forms', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'forms.destroy', 'name' => 'Delete forms', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-pages.index', 'name' => 'View form pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-pages.store', 'name' => 'Create form pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-pages.update', 'name' => 'Update form pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-pages.reorder', 'name' => 'Reorder form pages', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-pages.destroy', 'name' => 'Delete form pages', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-fields.index', 'name' => 'View form fields', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-fields.store', 'name' => 'Create form fields', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-fields.update', 'name' => 'Update form fields', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-fields.reorder', 'name' => 'Reorder form fields', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-fields.destroy', 'name' => 'Delete form fields', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-field-options.index', 'name' => 'View field options', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-field-options.store', 'name' => 'Create field options', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-field-options.update', 'name' => 'Update field options', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-field-options.reorder', 'name' => 'Reorder field options', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-field-options.destroy', 'name' => 'Delete field options', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-webhooks.index', 'name' => 'View form webhooks', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-webhooks.store', 'name' => 'Create form webhooks', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-webhooks.update', 'name' => 'Update form webhooks', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-webhooks.destroy', 'name' => 'Delete form webhooks', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-notification-groups.index', 'name' => 'View form notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-notification-groups.store', 'name' => 'Add form notification groups', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-notification-groups.destroy', 'name' => 'Remove form notification groups', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-blocked-countries.index', 'name' => 'View blocked countries', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-blocked-countries.store', 'name' => 'Block a country', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-blocked-countries.destroy', 'name' => 'Unblock a country', 'supports_web' => true, 'supports_api' => false],

            ['code' => 'form-blocked-ips.index', 'name' => 'View blocked IPs', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-blocked-ips.store', 'name' => 'Block an IP', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-blocked-ips.destroy', 'name' => 'Unblock an IP', 'supports_web' => true, 'supports_api' => false],

            // Read-only: a submission is never deleted, so there is no
            // form-submissions.destroy code to grant.
            ['code' => 'form-submissions.index', 'name' => 'View form submissions', 'supports_web' => true, 'supports_api' => false],
            ['code' => 'form-submissions.show', 'name' => 'View a form submission', 'supports_web' => true, 'supports_api' => false],
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
