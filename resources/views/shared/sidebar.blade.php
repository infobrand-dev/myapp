<aside class="navbar navbar-vertical navbar-expand-lg border-end" style="min-height: 100vh;">
    <div class="container-fluid">
        <h1 class="navbar-brand mb-0">MyApp</h1>
        <ul class="navbar-nav pt-lg-3">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('dashboard') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('dashboard') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                            <path d="M5 12v7a2 2 0 0 0 2 2h3v-5a2 2 0 0 1 2 -2h0a2 2 0 0 1 2 2v5h3a2 2 0 0 0 2 -2v-7" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('profile.edit') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('profile.edit') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                            <path d="M6 20a6 6 0 0 1 12 0" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Profile</span>
                </a>
            </li>
            @role('Super-admin')
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('users.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('users.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                            <path d="M3 21v-2a4 4 0 0 1 4 -4h4" />
                            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                            <path d="M21 21v-2a4 4 0 0 0 -3 -3.85" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('roles.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('roles.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 7a2 2 0 0 1 2 -2h4l2 2h6a2 2 0 0 1 2 2v1a2 2 0 0 1 -2 2h-6l-2 2h-4a2 2 0 0 1 -2 -2z" />
                            <path d="M8 13v4a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2v-4" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Roles</span>
                </a>
            </li>
            @endrole
            @if(config('modules.task_management.enabled') || config('modules.whatsapp_bro.enabled') || config('modules.contacts.enabled') || config('modules.shortlink.enabled') || config('modules.email_marketing.enabled'))
            <li class="nav-item mt-2">
                <div class="text-uppercase text-secondary fw-bold small px-3">Modules</div>
            </li>
            @endif
            @if(config('modules.shortlink.enabled'))
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('shortlinks.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('shortlinks.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 12l5 5l10 -10" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Shortlink</span>
                </a>
            </li>
            @endif
            @if(config('modules.contacts.enabled'))
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('contacts.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('contacts.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Contacts</span>
                </a>
            </li>
            @endif
            @if(config('modules.email_marketing.enabled'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('email-marketing.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M3 7l9 6l9 -6" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Email Marketing</span>
                </a>
                <div class="dropdown-menu show position-static border-0 shadow-none px-0 py-1">
                    <a class="dropdown-item {{ request()->routeIs('email-marketing.index') ? 'active' : '' }}" href="{{ route('email-marketing.index') }}">Campaign</a>
                    <a class="dropdown-item {{ request()->routeIs('email-marketing.templates.*') ? 'active' : '' }}" href="{{ route('email-marketing.templates.index') }}">Attachment</a>
                </div>
            </li>
            @endif
            @if(config('modules.whatsapp_bro.enabled'))
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('whatsappbro.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('whatsappbro.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 3.4l-4.05 .4" />
                            <path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1z" />
                            <path d="M13 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1z" />
                            <path d="M9 14a3.5 3.5 0 0 0 6 0" />
                        </svg>
                    </span>
                    <span class="nav-link-title">WhatsApp Bro</span>
                </a>
            </li>
            @endif
            @if(config('modules.task_management.enabled'))
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('memos.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('memos.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 5a2 2 0 0 1 2 -2h8l4 4v10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                            <path d="M11 11l5 -5" />
                            <path d="M13 13h-2v-2" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Internal Memo</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('tasktemplates.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('tasktemplates.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 5l0 14" />
                            <path d="M18 5l0 14" />
                            <path d="M6 5l0 14" />
                            <path d="M3 5h18" />
                            <path d="M3 19h18" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Task Templates</span>
                </a>
            </li>
            @endif
        </ul>
    </div>
</aside>
