<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Auth & Dashboard
            'admin.dashboard.index',
            
            // Permissions Management
            'admin.permissions.index',
            'admin.permissions.show',
            
            // Roles Management
            'admin.roles.index',
            'admin.roles.store',
            'admin.roles.show',
            'admin.roles.update',
            'admin.roles.destroy',
            
            // Users Management
            'admin.users.index',
            'admin.users.store',
            'admin.users.show',
            'admin.users.update',
            'admin.users.destroy',
            
            // Languages Management
            'admin.languages.index',
            'admin.languages.store',
            'admin.languages.show',
            'admin.languages.update',
            'admin.languages.destroy',
            
            // Language Keys Management
            'admin.language-keys.index',
            'admin.language-keys.store',
            'admin.language-keys.show',
            'admin.language-keys.update',
            'admin.language-keys.destroy',
            
            // Media Management
            'admin.media.index',
            'admin.media.store',
            'admin.media.show',
            'admin.media.update',
            'admin.media.destroy',
            
            // Pages Management
            'admin.pages.index',
            'admin.pages.store',
            'admin.pages.show',
            'admin.pages.update',
            'admin.pages.destroy',
            
            // Articles Management
            'admin.articles.index',
            'admin.articles.store',
            'admin.articles.show',
            'admin.articles.update',
            'admin.articles.destroy',
            
            // Programs Management
            'admin.programs.index',
            'admin.programs.store',
            'admin.programs.show',
            'admin.programs.update',
            'admin.programs.destroy',
            
            // Grades Management
            'admin.grades.index',
            'admin.grades.store',
            'admin.grades.show',
            'admin.grades.update',
            'admin.grades.destroy',
            
            // Albums Management
            'admin.albums.index',
            'admin.albums.store',
            'admin.albums.show',
            'admin.albums.update',
            'admin.albums.destroy',
            
            // Events Management
            'admin.events.index',
            'admin.events.store',
            'admin.events.show',
            'admin.events.update',
            'admin.events.destroy',
            
            // Menus Management
            'admin.menus.index',
            'admin.menus.store',
            'admin.menus.show',
            'admin.menus.update',
            'admin.menus.destroy',
            
            // Menu Items Management
            'admin.menu-items.index',
            'admin.menu-items.store',
            'admin.menu-items.show',
            'admin.menu-items.update',
            'admin.menu-items.destroy',
            'admin.menu-items.order',
            
            // Banners Management
            'admin.banners.index',
            'admin.banners.store',
            'admin.banners.show',
            'admin.banners.update',
            'admin.banners.destroy',
            'admin.banners.order',
            
            // Documents Management
            'admin.documents.index',
            'admin.documents.store',
            'admin.documents.show',
            'admin.documents.update',
            'admin.documents.destroy',
            
            // Visit Services Management
            'admin.visit-services.index',
            'admin.visit-services.store',
            'admin.visit-services.show',
            'admin.visit-services.update',
            'admin.visit-services.destroy',
            'admin.visit-services.order',
            
            // Visit Time Slots Management
            'admin.visit-time-slots.index',
            'admin.visit-time-slots.store',
            'admin.visit-time-slots.show',
            'admin.visit-time-slots.update',
            'admin.visit-time-slots.destroy',
            
            // Visit Bookings Management
            'admin.visit-bookings.index',
            'admin.visit-bookings.show',
            'admin.visit-bookings.destroy',
            
            // Inquiries Management
            'admin.inquiries.index',
            'admin.inquiries.show',
            'admin.inquiries.destroy',
            
            // Contact Submissions Management
            'admin.contact-submissions.index',
            'admin.contact-submissions.show',
            'admin.contact-submissions.destroy',
            
            // Job Postings Management
            'admin.job-postings.index',
            'admin.job-postings.store',
            'admin.job-postings.show',
            'admin.job-postings.update',
            'admin.job-postings.destroy',
            
            // Job Applications Management
            'admin.job-applications.index',
            'admin.job-applications.show',
            'admin.job-applications.destroy',
            
            // Files Management
            'admin.files.upload',
            
            // Settings Management
            'admin.settings.index',
            'admin.settings.show',
            'admin.settings.edit',
            'admin.settings.update',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Create default roles
        $superAdminRole = Role::updateOrCreate(
            ['name' => 'Super Admin'],
            ['is_default' => false]
        );

        $adminRole = Role::updateOrCreate(
            ['name' => 'Admin'],
            ['is_default' => false]
        );

        $editorRole = Role::updateOrCreate(
            ['name' => 'Editor'],
            ['is_default' => true]
        );

        $viewerRole = Role::updateOrCreate(
            ['name' => 'Viewer'],
            ['is_default' => false]
        );

        // Assign all permissions to Super Admin
        $allPermissions = Permission::all();
        $superAdminRole->permissions()->sync($allPermissions->pluck('id'));

        // Assign most permissions to Admin (excluding user/role management)
        $adminPermissions = Permission::whereNotIn('name', [
            'admin.users.store',
            'admin.users.update', 
            'admin.users.destroy',
            'admin.roles.store',
            'admin.roles.update',
            'admin.roles.destroy',
            'admin.permissions.show'
        ])->get();
        $adminRole->permissions()->sync($adminPermissions->pluck('id'));

        // Assign content management permissions to Editor
        $editorPermissions = Permission::where('name', 'like', '%articles%')
            ->orWhere('name', 'like', '%pages%')
            ->orWhere('name', 'like', '%albums%')
            ->orWhere('name', 'like', '%events%')
            ->orWhere('name', 'like', '%media%')
            ->orWhere('name', 'like', '%banners%')
            ->orWhere('name', 'like', '%documents%')
            ->orWhere('name', 'like', '%files%')
            ->get();
            
        $editorRole->permissions()->sync($editorPermissions->pluck('id'));

        // Assign view-only permissions to Viewer
        $viewerPermissions = Permission::where('name', 'like', '%.index')
            ->orWhere('name', 'like', '%.show')
            ->orWhere('name', 'like', '%inquiries%')
            ->orWhere('name', 'like', '%contact-submissions%')
            ->orWhere('name', 'like', '%visit-bookings%')
            ->orWhere('name', 'like', '%job-applications%')
            ->get();
        $viewerRole->permissions()->sync($viewerPermissions->pluck('id'));
    }
}
