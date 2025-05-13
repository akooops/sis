<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <a href="index" class="logo mt-8">
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

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span >Dashboard</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="">
                        <i class="ri-dashboard-2-fill"></i> <span >Dashboard</span>
                    </a>
                </li> <!-- end Dashboard Menu -->

                <li class="menu-title"><span >CMS</span></li>

                <li class="menu-title"><span >Users managment</span></li>

                @canany(['admin.roles.index'])
                <li class="nav-item">
                    <a class="nav-link menu-link {{
                        (
                            request()->routeIs('admin.roles.index') || 
                            request()->routeIs('admin.roles.create') ||
                            request()->routeIs('admin.roles.edit') ||
                            request()->routeIs('admin.roles.show')
                        ) ? 'active' : ''}}" href="{{route('admin.roles.index')}}">
                        <i class="ri-forbid-2-fill"></i> <span >Roles</span>
                    </a>
                </li> <!-- end Dashboard Menu -->
                @endcanany

                @canany(['admin.permissions.index'])
                <li class="nav-item">
                    <a class="nav-link menu-link {{
                        (
                            request()->routeIs('admin.permissions.index') || 
                            request()->routeIs('admin.permissions.create') ||
                            request()->routeIs('admin.permissions.edit') ||
                            request()->routeIs('admin.permissions.show')
                        ) ? 'active' : ''}}" href="{{route('admin.permissions.index')}}">
                        <i class="ri-forbid-fill"></i> <span >Permissions</span>
                    </a>
                </li> <!-- end Dashboard Menu -->
                @endcanany
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
     <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
