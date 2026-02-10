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

            @php
                $waApiOpen = request()->routeIs('whatsapp-api.*');
                $emailOpen = request()->routeIs('email-marketing.*');
                $taskOpen = request()->routeIs('memos.*') || request()->routeIs('tasktemplates.*');
                $socialOpen = request()->routeIs('social-media.*');
            @endphp

            @if(config('modules.task_management.enabled') || config('modules.whatsapp_bro.enabled') || config('modules.whatsapp_api.enabled') || config('modules.contacts.enabled') || config('modules.shortlink.enabled') || config('modules.email_marketing.enabled'))
            <li class="nav-item mt-2">
                <div class="text-uppercase text-secondary fw-bold small px-3">Modules</div>
            </li>
            @endif
            @if(config('modules.social_media.enabled'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $socialOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="#" data-bs-toggle="dropdown" aria-expanded="{{ $socialOpen ? 'true' : 'false' }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M4 4h16v16H4z" />
                            <path d="M9 8h6" />
                            <path d="M9 12h6" />
                            <path d="M9 16h6" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Social Media</span>
                </a>
                <div class="dropdown-menu position-static border-0 shadow-none px-0 py-1 ms-4 {{ $socialOpen ? 'show' : '' }}">
                    <a class="dropdown-item px-3 {{ request()->routeIs('social-media.index') ? 'active' : '' }}" href="{{ route('social-media.index') }}">Instagram / Facebook DM</a>
                </div>
            </li>
            @endif

            {{-- Conversations hub (gabungan internal/WA/social) --}}
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('conversations.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('conversations.index') }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M8 9h8" />
                            <path d="M8 13h6" />
                            <path d="M7 4h10a2 2 0 0 1 2 2v11l-4 -3l-4 3l-4 -3l-4 3v-11a2 2 0 0 1 2 -2z" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Conversations</span>
                </a>
            </li>

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
                <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $emailOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="#" data-bs-toggle="dropdown" aria-expanded="{{ $emailOpen ? 'true' : 'false' }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            <path d="M3 7l9 6l9 -6" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Email Marketing</span>
                </a>
                <div class="dropdown-menu position-static border-0 shadow-none px-0 py-1 ms-4 {{ $emailOpen ? 'show' : '' }}">
                    <a class="dropdown-item px-3 {{ request()->routeIs('email-marketing.index') ? 'active' : '' }}" href="{{ route('email-marketing.index') }}">Campaign</a>
                    <a class="dropdown-item px-3 {{ request()->routeIs('email-marketing.templates.*') ? 'active' : '' }}" href="{{ route('email-marketing.templates.index') }}">Attachment</a>
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

            @if(config('modules.whatsapp_api.enabled'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $waApiOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="#" data-bs-toggle="dropdown" aria-expanded="{{ $waApiOpen ? 'true' : 'false' }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 256 256" fill="currentColor">
                            <path d="M128 24C69.8 24 22.7 70.4 22.7 128c0 18.3 4.6 36.1 13.3 51.9L24 232l54.9-11.5C93.9 230 111 232 128 232c58.2 0 105.3-46.4 105.3-104S186.2 24 128 24Zm0 184c-15.8 0-31-3.8-44.7-11.2l-3.2-1.7-32.5 6.8 6.7-31.8-1.8-3.1C44 154.8 40.7 141.6 40.7 128 40.7 80.7 79.9 40 128 40s87.3 40.7 87.3 88-39.2 80-87.3 80Zm52.5-60.6c-2.9-1.4-17.3-8.5-20-9.5-2.7-1-4.7-1.4-6.7 1.5-2 2.9-7.7 9.5-9.4 11.5-1.7 2-3.4 2.2-6.3.8-2.9-1.4-12.3-4.5-23.4-14.4-8.6-7.6-14.4-17-16.1-19.9-1.7-2.9-.2-4.5 1.3-5.9 1.4-1.4 2.9-3.4 4.3-5.1 1.4-1.7 1.9-2.9 2.9-4.8s.5-3.6-.3-5.1c-.8-1.4-6.7-16.1-9.2-22.1-2.4-5.8-4.9-5-6.7-5.1-1.7-.1-3.6-.1-5.5-.1a10.6 10.6 0 0 0-7.6 3.5c-2.6 2.8-9.8 9.6-9.8 23.5 0 13.9 10 27.3 11.4 29.2 1.4 1.9 19.6 31.4 47.5 43 6.6 2.9 11.8 4.7 15.8 6 6.6 2.1 12.6 1.8 17.3 1.1 5.3-.8 17.3-7 19.8-13.8 2.5-6.9 2.5-12.9 1.7-14.1-.7-1.2-2.6-1.9-5.5-3.3Z"/>
                        </svg>
                    </span>
                    <span class="nav-link-title">WhatsApp API</span>
                </a>
                <div class="dropdown-menu position-static border-0 shadow-none px-0 py-1 ms-4 {{ $waApiOpen ? 'show' : '' }}">
                    <a class="dropdown-item px-3 {{ request()->routeIs('whatsapp-api.inbox') ? 'active' : '' }}" href="{{ route('whatsapp-api.inbox') }}">Inbox</a>
                    @role('Super-admin')
                    <a class="dropdown-item px-3 {{ request()->routeIs('whatsapp-api.instances.*') ? 'active' : '' }}" href="{{ route('whatsapp-api.instances.index') }}">Instances</a>
                    @endrole
                </div>
            </li>
            @endif

            @if(config('modules.task_management.enabled'))
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $taskOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="#" data-bs-toggle="dropdown" aria-expanded="{{ $taskOpen ? 'true' : 'false' }}">
                    <span class="nav-link-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M5 5a2 2 0 0 1 2 -2h8l4 4v10a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                            <path d="M11 11l5 -5" />
                            <path d="M13 13h-2v-2" />
                        </svg>
                    </span>
                    <span class="nav-link-title">Task Management</span>
                </a>
                <div class="dropdown-menu position-static border-0 shadow-none px-0 py-1 ms-4 {{ $taskOpen ? 'show' : '' }}">
                    <a class="dropdown-item px-3 {{ request()->routeIs('memos.*') ? 'active' : '' }}" href="{{ route('memos.index') }}">Internal Memo</a>
                    <a class="dropdown-item px-3 {{ request()->routeIs('tasktemplates.*') ? 'active' : '' }}" href="{{ route('tasktemplates.index') }}">Task Templates</a>
                </div>
            </li>
            @endif
        </ul>
    </div>
</aside>
