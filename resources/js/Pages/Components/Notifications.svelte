<!-- Notifications -->
<button class="kt-btn kt-btn-ghost kt-btn-icon size-8 hover:bg-background hover:[&_i]:text-primary" data-kt-drawer-toggle="#notifications_drawer">
    <i class="ki-filled ki-notification-status text-lg">
    </i>
</button>

    <!--Notifications Drawer-->
    <div class="hidden kt-drawer kt-drawer-end card flex-col max-w-[90%] w-[450px] top-5 bottom-5 end-5 rounded-xl border border-border" data-kt-drawer="true" data-kt-drawer-container="body" id="notifications_drawer">
            <div class="flex items-center justify-between gap-2.5 text-sm text-mono font-semibold px-5 py-2.5 border-b border-b-border" id="notifications_header">
                Notifications
                <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-dim shrink-0" data-kt-drawer-dismiss="true">
                <i class="ki-filled ki-cross">
                </i>
                </button>
            </div>
            <div class="kt-tabs kt-tabs-line justify-between px-5 mb-2" data-kt-tabs="true" id="notifications_tabs">
                <div class="flex items-center gap-5">
                    <button class="kt-tab-toggle py-3 active" data-kt-tab-toggle="#notifications_tab_all">
                        All
                    </button>
                </div>
                <div class="kt-menu" data-kt-menu="true">
                    <div class="kt-menu-item" data-kt-menu-item-offset="0,10px" data-kt-menu-item-placement="bottom-end" data-kt-menu-item-placement-rtl="bottom-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click|lg:hover">
                        <button class="kt-menu-toggle kt-btn kt-btn-icon kt-btn-ghost">
                        <i class="ki-filled ki-setting-2">
                        </i>
                        </button>
                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]" data-kt-menu-dismiss="true">
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="#">
                                <span class="kt-menu-icon">
                                <i class="ki-filled ki-document">
                                </i>
                                </span>
                                <span class="kt-menu-title">
                                View
                                </span>
                                </a>
                            </div>
                            <div class="kt-menu-item" data-kt-menu-item-offset="-15px, 0" data-kt-menu-item-placement="right-start" data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click|lg:hover">
                                <div class="kt-menu-link">
                                    <span class="kt-menu-icon">
                                    <i class="ki-filled ki-notification-status">
                                    </i>
                                    </span>
                                    <span class="kt-menu-title">
                                    Export
                                    </span>
                                    <span class="kt-menu-arrow">
                                    <i class="ki-filled ki-right text-xs rtl:transform rtl:rotate-180">
                                    </i>
                                    </span>
                                </div>
                                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px]">
                                    <div class="kt-menu-item">
                                        <a class="kt-menu-link" href="html/demo10/account/home/settings-sidebar.html">
                                        <span class="kt-menu-icon">
                                        <i class="ki-filled ki-sms">
                                        </i>
                                        </span>
                                        <span class="kt-menu-title">
                                        Email
                                        </span>
                                        </a>
                                    </div>
                                    <div class="kt-menu-item">
                                        <a class="kt-menu-link" href="html/demo10/account/home/settings-sidebar.html">
                                        <span class="kt-menu-icon">
                                        <i class="ki-filled ki-message-notify">
                                        </i>
                                        </span>
                                        <span class="kt-menu-title">
                                        SMS
                                        </span>
                                        </a>
                                    </div>
                                    <div class="kt-menu-item">
                                        <a class="kt-menu-link" href="html/demo10/account/home/settings-sidebar.html">
                                        <span class="kt-menu-icon">
                                        <i class="ki-filled ki-notification-status">
                                        </i>
                                        </span>
                                        <span class="kt-menu-title">
                                        Push
                                        </span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="#">
                                <span class="kt-menu-icon">
                                <i class="ki-filled ki-pencil">
                                </i>
                                </span>
                                <span class="kt-menu-title">
                                Edit
                                </span>
                                </a>
                            </div>
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="#">
                                <span class="kt-menu-icon">
                                <i class="ki-filled ki-trash">
                                </i>
                                </span>
                                <span class="kt-menu-title">
                                Delete
                                </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grow flex flex-col" id="notifications_tab_all">
                <div class="grow kt-scrollable-y-auto" data-kt-scrollable="true" data-kt-scrollable-dependencies="#header" data-kt-scrollable-max-height="auto" data-kt-scrollable-offset="150px">
                    <div class="grow flex flex-col gap-5 pt-3 pb-4 divider-y divider-border">
                        <div class="flex grow gap-2.5 px-5">
                            <div class="kt-avatar size-8">
                                <div class="kt-avatar-image">
                                    <img alt="avatar" src="assets/media/avatars/300-11.png"/>
                                </div>
                                <div class="kt-avatar-indicator -end-2 -bottom-2">
                                    <div class="kt-avatar-status kt-avatar-status-online size-2.5">
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <div class="text-sm font-medium mb-px">
                                    <a class="hover:text-primary text-mono font-semibold" href="#">
                                    Raymond Pawell
                                    </a>
                                    <span class="text-secondary-foreground">
                                    posted a new article
                                    </span>
                                    <a class="hover:text-primary text-primary" href="#">
                                    2024 Roadmap
                                    </a>
                                </div>
                                <span class="flex items-center text-xs font-medium text-muted-foreground">
                                1 hour ago
                                <span class="rounded-full size-1 bg-mono/30 mx-1.5">
                                </span>
                                Roadmap
                                </span>
                            </div>
                        </div>
                        <div class="border-b border-b-border">
                        </div>
                    </div>
                </div>
                <div class="border-b border-b-border">
                </div>
                <div class="grid grid-cols-2 p-5 gap-2.5" id="notifications_all_footer">
                    <button class="kt-btn kt-btn-outline justify-center">
                    Archive all
                    </button>
                    <button class="kt-btn kt-btn-outline justify-center">
                    Mark all as read
                    </button>
                </div>
            </div>

    </div>
    <!--End of Notifications Drawer-->

 <!-- End of Notifications -->