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
<li class="nav-item">
    <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ request()->routeIs('modules.*') ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route('modules.index') }}">
        <span class="nav-link-icon">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 4h6v6h-6z" />
                <path d="M14 4h6v6h-6z" />
                <path d="M4 14h6v6h-6z" />
                <path d="M14 14h6v6h-6z" />
            </svg>
        </span>
        <span class="nav-link-title">Modules</span>
    </a>
</li>
@endrole

@if($moduleMenus->isNotEmpty())
<li class="nav-item mt-2">
    <div class="text-uppercase text-secondary fw-bold small px-3">Modules</div>
</li>
@endif

@foreach($moduleMenus as $menu)
    @php
        $routes = $menu['items']->pluck('route')->all();
        $isOpen = in_array($currentRouteName, $routes, true);
        $single = $menu['items']->count() === 1;
    @endphp
    @if($single)
        @php
            $item = $menu['items']->first();
            $isConversationRoute = str_starts_with((string) $item['route'], 'conversations.');
        @endphp
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $isOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="{{ route($item['route']) }}">
                <span class="nav-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M6 6h12v12h-12z" />
                    </svg>
                </span>
                <span class="nav-link-title">{{ $menu['name'] }}</span>
                @if($isConversationRoute)
                    @php
                        $useConversationBadgeId = !$conversationBadgeRendered;
                        $conversationBadgeRendered = true;
                    @endphp
                    <span
                        @if($useConversationBadgeId) id="sidebar-conv-unread-badge" @endif
                        data-count="{{ $conversationUnreadTotal }}"
                        class="badge bg-red-lt text-red ms-auto {{ $conversationUnreadTotal > 0 ? '' : 'd-none' }}">
                        {{ $conversationUnreadTotal }}
                    </span>
                @endif
            </a>
        </li>
    @else
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center justify-content-start gap-2 px-3 py-2 rounded-2 text-start w-100 {{ $isOpen ? 'active bg-primary-lt text-primary' : 'bg-body' }}" href="#" data-bs-toggle="dropdown" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                <span class="nav-link-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M6 6h12v12h-12z" />
                    </svg>
                </span>
                <span class="nav-link-title">{{ $menu['name'] }}</span>
                @if(collect($routes)->contains(fn($r) => str_starts_with((string) $r, 'conversations.')))
                    @php
                        $useConversationBadgeId = !$conversationBadgeRendered;
                        $conversationBadgeRendered = true;
                    @endphp
                    <span
                        @if($useConversationBadgeId) id="sidebar-conv-unread-badge" @endif
                        data-count="{{ $conversationUnreadTotal }}"
                        class="badge bg-red-lt text-red ms-auto {{ $conversationUnreadTotal > 0 ? '' : 'd-none' }}">
                        {{ $conversationUnreadTotal }}
                    </span>
                @endif
            </a>
            <div class="dropdown-menu position-static border-0 shadow-none px-0 py-1 ms-4 {{ $isOpen ? 'show' : '' }}">
                @foreach($menu['items'] as $item)
                    <a class="dropdown-item px-3 {{ $currentRouteName === $item['route'] ? 'active' : '' }}" href="{{ route($item['route']) }}">{{ $item['label'] }}</a>
                @endforeach
            </div>
        </li>
    @endif
@endforeach
