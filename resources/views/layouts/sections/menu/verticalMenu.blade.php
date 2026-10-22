@php
    use Illuminate\Support\Facades\Route;
    use Illuminate\Support\Facades\Auth;
    $configData = Helper::appClasses();
    $rules = Auth::user()->role->tipo_usuario;
    $tenantResolved = app(\App\Support\TenantContext::class)->isResolved();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu">

    <!-- ! Hide app brand if navbar-full -->
    @if (!isset($navbarFull))
        <div class="app-brand demo">
            @if (Helper::temaEmpresa() === 'licas')
                <a href="{{ url('/') }}" class="app-brand-link licas-wordmark" aria-label="Licas Consultoria, início">
                    <span class="licas-wordmark__mono" aria-hidden="true">M</span>
                    <span class="licas-wordmark__name menu-text">Licas</span>
                </a>
            @else
                <a href="{{ url('/') }}" class="app-brand-link">
                    <span class="app-brand-logo demo me-1">
                        @include('_partials.macros', ['height' => 20])
                    </span>
                    <span
                        class="app-brand-text demo menu-text fw-semibold ms-2">M &amp; L</span>
                </a>
            @endif

            <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                <i class="menu-toggle-icon d-xl-block align-middle"></i>
            </a>
        </div>
    @endif

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        {{-- ADVOGADA: acesso restrito — vê somente o kanban de liminar --}}
        @if (Auth::user()->user_role_id === \App\Enums\UserRole::ADVOGADA)
            @php $currentRouteName = Route::currentRouteName(); @endphp
            <li class="menu-item {{ str_starts_with($currentRouteName ?? '', 'backoffice.liminar') ? 'active' : '' }}">
                <a href="{{ url('/back-office/liminar') }}" class="menu-link">
                    <i class="menu-icon tf-icons ri-scales-3-line"></i>
                    <div>{{ __('Cancelamento via Liminar') }}</div>
                </a>
            </li>
        @else
        @foreach ($menuData[0]->menu as $menu)
            {{-- adding active and open class if child is active --}}

            {{-- Gate por usuário (além do papel): usado por features liberadas individualmente --}}
            @php
                $passaGate = true;
                if (isset($menu->gate) && $menu->gate === 'escola') {
                    $u = Auth::user();
                    $passaGate = in_array($u->user_role_id, [\App\Enums\UserRole::ADMINISTRATIVO, \App\Enums\UserRole::DEVELOPER])
                        || (bool) $u->escola_habilitada;
                } elseif (isset($menu->gate) && $menu->gate === 'platform_admin') {
                    $passaGate = Auth::user()->isPlatformAdmin();
                }
                if (!$tenantResolved && empty($menu->tenantOptional)) {
                    $passaGate = false;
                }
            @endphp

            {{-- Check if the current user's role is in the menu rules --}}
            @if ((!isset($menu->rules) || in_array($rules, $menu->rules)) && $passaGate)
                {{-- menu headers --}}
                @if (isset($menu->menuHeader))
                    <li class="menu-header mt-7">
                        <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
                    </li>
                @else
                    {{-- active menu method --}}
                    @php
                        $activeClass = null;
                        $currentRouteName = Route::currentRouteName();

                        if ($currentRouteName === $menu->slug) {
                            $activeClass = 'active';
                        } elseif (isset($menu->submenu)) {
                            if (gettype($menu->slug) === 'array') {
                                foreach ($menu->slug as $slug) {
                                    if (str_contains($currentRouteName, $slug) and strpos($currentRouteName, $slug) === 0) {
                                        $activeClass = 'active open';
                                    }
                                }
                            } else {
                                if (
                                    str_contains($currentRouteName, $menu->slug) and
                                    strpos($currentRouteName, $menu->slug) === 0
                                ) {
                                    $activeClass = 'active open';
                                }
                            }
                        }
                    @endphp

                    {{-- main menu --}}
                    <li class="menu-item {{ $activeClass }}">
                        <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
                            class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
                            @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
                            @isset($menu->icon)
                                <i class="{{ $menu->icon }}"></i>
                            @endisset
                            <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
                            @isset($menu->badge)
                                <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
                            @endisset
                        </a>

                        {{-- submenu --}}
                        @isset($menu->submenu)
                            @include('layouts.sections.menu.submenu', ['menu' => $menu->submenu])
                        @endisset
                    </li>
                @endif
            @endif
        @endforeach
        @endif
    </ul>

    @if (Helper::temaEmpresa() === 'licas')
        @php
            $usuarioMenu = Auth::user();
            $iniciaisMenu = collect(preg_split('/\s+/', trim($usuarioMenu->name)))
                ->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('');
        @endphp
        <div class="licas-menu-user">
            <span class="licas-avatar" aria-hidden="true">{{ mb_strtoupper($iniciaisMenu) }}</span>
            <span class="licas-menu-user__text menu-text">
                <span class="licas-menu-user__name">{{ \Illuminate\Support\Str::title(mb_strtolower($usuarioMenu->name)) }}</span>
                <span class="licas-menu-user__role">{{ \Illuminate\Support\Str::title(mb_strtolower($rules)) }}</span>
            </span>
        </div>
    @endif

</aside>
