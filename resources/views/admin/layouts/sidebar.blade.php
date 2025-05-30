<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <a href="{{ route('admin.dashboard') }}" class="logo mt-8">
            <span class="logo-sm mt-8">
                <img src="{{ URL::asset('assets/admin/images/logo.png') }}" alt="" height="64">
            </span>
            <span class="logo-lg mt-8">
                <img src="{{ URL::asset('assets/admin/images/logo.png') }}" alt="" height="64">
            </span>
        </a>

        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">
                
                <!-- Dashboard -->
                <li class="menu-title"><span>Dashboard</span></li>
                @haspermission('admin.dashboard.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                       href="{{ route('admin.dashboard') }}">
                        <i class="ri-dashboard-2-fill"></i> <span>Dashboard</span>
                    </a>
                </li>
                @endhaspermission

                <!-- Content Management -->
                <li class="menu-title"><span>Content Management</span></li>
                
                @haspermission('admin.pages.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.pages.*') ? 'active' : '' 
                    }}" href="{{ route('admin.pages.index') }}">
                        <i class="ri-pages-fill"></i> <span>Pages</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.articles.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.articles.*') ? 'active' : '' 
                    }}" href="{{ route('admin.articles.index') }}">
                        <i class="ri-article-fill"></i> <span>Articles</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.albums.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.albums.*') ? 'active' : '' 
                    }}" href="{{ route('admin.albums.index') }}">
                        <i class="ri-image-2-fill"></i> <span>Albums</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.events.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.events.*') ? 'active' : '' 
                    }}" href="{{ route('admin.events.index') }}">
                        <i class="ri-calendar-event-fill"></i> <span>Events</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.banners.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.banners.*') ? 'active' : '' 
                    }}" href="{{ route('admin.banners.index') }}">
                        <i class="ri-image-fill"></i> <span>Banners</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.media.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.media.*') ? 'active' : '' 
                    }}" href="{{ route('admin.media.index') }}">
                        <i class="ri-folder-2-fill"></i> <span>Media</span>
                    </a>
                </li>
                @endhaspermission

                <!-- Academic Management -->
                <li class="menu-title"><span>Academic Management</span></li>
                
                @haspermission('admin.programs.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.programs.*') ? 'active' : '' 
                    }}" href="{{ route('admin.programs.index') }}">
                        <i class="ri-book-2-fill"></i> <span>Programs</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.grades.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.grades.*') ? 'active' : '' 
                    }}" href="{{ route('admin.grades.index') }}">
                        <i class="ri-node-tree"></i> <span>Grades</span>
                    </a>
                </li>
                @endhaspermission

                <!-- Navigation Management -->
                <li class="menu-title"><span>Navigation</span></li>
                
                @haspermission('admin.menus.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.menus.*') ? 'active' : '' 
                    }}" href="{{ route('admin.menus.index') }}">
                        <i class="ri-menu-2-fill"></i> <span>Menus</span>
                    </a>
                </li>
                @endhaspermission

                <!-- Localization -->
                <li class="menu-title"><span>Localization</span></li>
                
                @haspermission('admin.languages.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.languages.*') ? 'active' : '' 
                    }}" href="{{ route('admin.languages.index') }}">
                        <i class="ri-global-fill"></i> <span>Languages</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.language-keys.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.language-keys.*') ? 'active' : '' 
                    }}" href="{{ route('admin.language-keys.index') }}">
                        <i class="ri-translate-2"></i> <span>Language Keys</span>
                    </a>
                </li>
                @endhaspermission

                <!-- User Management -->
                <li class="menu-title"><span>User Management</span></li>

                @haspermission('admin.users.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.users.*') ? 'active' : '' 
                    }}" href="{{ route('admin.users.index') }}">
                        <i class="ri-user-fill"></i> <span>Users</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.roles.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.roles.*') ? 'active' : '' 
                    }}" href="{{ route('admin.roles.index') }}">
                        <i class="ri-shield-user-fill"></i> <span>Roles</span>
                    </a>
                </li>
                @endhaspermission

                @haspermission('admin.permissions.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.permissions.*') ? 'active' : '' 
                    }}" href="{{ route('admin.permissions.index') }}">
                        <i class="ri-lock-fill"></i> <span>Permissions</span>
                    </a>
                </li>
                @endhaspermission

                <!-- System Settings -->
                <li class="menu-title"><span>System</span></li>
                
                @haspermission('admin.settings.index')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ 
                        request()->routeIs('admin.settings.*') ? 'active' : '' 
                    }}" href="{{ route('admin.settings.index') }}">
                        <i class="ri-settings-3-fill"></i> <span>Settings</span>
                    </a>
                </li>
                @endhaspermission

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
