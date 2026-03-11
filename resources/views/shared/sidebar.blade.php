@php
    $moduleManager = app(\App\Support\ModuleManager::class);
    $currentRouteName = optional(request()->route())->getName();
    $conversationUnreadTotal = 0;

    if (auth()->check()
        && class_exists(\App\Modules\Conversations\Models\Conversation::class)
        && \Illuminate\Support\Facades\Schema::hasTable('conversations')) {
        $conversationQuery = \App\Modules\Conversations\Models\Conversation::query()
            ->where('unread_count', '>', 0);

        $authUser = auth()->user();
        if (!$authUser->hasRole('Super-admin')) {
            $conversationQuery->where(function ($q) use ($authUser) {
                $q->where('owner_id', $authUser->id)
                    ->orWhereHas('participants', fn ($p) => $p->where('user_id', $authUser->id));

                if (\Illuminate\Support\Facades\Schema::hasTable('whatsapp_instances')
                    && \Illuminate\Support\Facades\Schema::hasTable('whatsapp_instance_user')) {
                    $instanceIds = \Illuminate\Support\Facades\DB::table('whatsapp_instance_user')
                        ->where('user_id', $authUser->id)
                        ->pluck('instance_id')
                        ->all();

                    if (!empty($instanceIds)) {
                        $q->orWhere(function ($waQ) use ($instanceIds) {
                            $waQ->where('channel', 'wa_api')
                                ->whereIn('instance_id', $instanceIds);
                        });
                    }
                }
            });
        }

        $conversationUnreadTotal = (int) $conversationQuery->sum('unread_count');
    }

    $moduleMenus = collect($moduleManager->all())
        ->filter(fn ($module) => $module['installed'] && $module['active'])
        ->map(function ($module) {
            $items = collect($module['navigation'] ?? [])
                ->filter(function ($item) {
                    if (empty($item['route']) || !Route::has($item['route'])) {
                        return false;
                    }

                    $role = $item['role'] ?? null;
                    if (!$role) {
                        return true;
                    }

                    return auth()->check() && auth()->user()->hasRole($role);
                })
                ->values();

            return [
                'slug' => $module['slug'],
                'name' => $module['name'],
                'items' => $items,
            ];
        })
        ->filter(fn ($module) => $module['items']->isNotEmpty())
        ->values();
@endphp

<aside class="navbar navbar-vertical navbar-expand-lg border-end">
    <div class="container-fluid">
        <div class="sidebar-brand-wrap d-none d-lg-flex align-items-center justify-content-between w-100 px-1 py-3 border-bottom">
            <a href="{{ route('dashboard') }}" class="navbar-brand sidebar-brand mb-0 text-decoration-none">MyApp</a>
        </div>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
                @php $conversationBadgeRendered = false; @endphp
                @include('shared.sidebar-menu')
            </ul>
        </div>
    </div>
</aside>
