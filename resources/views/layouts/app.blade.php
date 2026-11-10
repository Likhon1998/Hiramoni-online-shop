<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Hira Moni') }}</title>
    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}

        /* Beauty admin — calm rose palette, no motion */
        body.admin-panel{
            color:#3a1f2e;
            font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif;
        }
        body.admin-panel h1,body.admin-panel h2,body.admin-panel .font-display{
            font-family:'DM Sans',ui-sans-serif,system-ui,sans-serif;font-weight:700;letter-spacing:-.01em
        }
        body.admin-panel .rounded-xl{border-radius:12px}
        body.admin-panel .rounded-2xl{border-radius:16px}
        body.admin-panel .shadow-sm{box-shadow:0 1px 2px rgba(58,31,46,.04),0 4px 12px -6px rgba(136,14,79,.08)}
        body.admin-panel .shadow{box-shadow:0 2px 4px rgba(58,31,46,.04),0 8px 18px -8px rgba(136,14,79,.1)}
        body.admin-panel input:not([type=checkbox]):not([type=radio]):focus,
        body.admin-panel select:focus,
        body.admin-panel textarea:focus{
            border-color:#E91E63;--tw-ring-color:rgba(233,30,99,.18);outline:none
        }
        body.admin-panel ::selection{background:#FCE4EC;color:#3a1f2e}
        .admin-scroll-hide{
            background:
                radial-gradient(900px 380px at 100% -8%, rgba(252,228,236,.55), transparent 62%),
                radial-gradient(720px 360px at -8% 100%, rgba(255,245,248,.7), transparent 60%),
                #FFF8FB
        }

        .powered-by-admin{
            flex-shrink:0;
            padding:10px 16px 14px;
            text-align:center;
            font-size:11px;
            font-weight:600;
            letter-spacing:.03em;
            color:#8A6B7A;
            border-top:1px solid #F5D6E5;
            background:transparent;
        }
        .powered-by-admin strong{color:#3a1f2e;font-weight:700}
        .powered-by-admin a,.powered-by-link{color:inherit;text-decoration:none}
        .powered-by-admin a:hover,.powered-by-admin .powered-by-link:hover{color:#C2185B;text-decoration:underline}

        :root{
            --sb-bg:#ffffff;
            --sb-line:#F5D6E5;
            --sb-text:#7A5566;
            --sb-text-strong:#3a1f2e;
            --sb-muted:#9A6B7D;
            --sb-hover:#FFF5F8;
            --sb-active:#FCE4EC;
            --sb-accent:#C2185B;
            --sb-width:244px;
            --sb-rail:68px;
            --sb-top:3.5rem
        }
        .admin-sidebar{
            position:relative;z-index:50;
            display:flex;flex-direction:column;
            width:var(--sb-width);min-width:var(--sb-width);max-width:var(--sb-width);
            flex:0 0 var(--sb-width);height:100vh;
            background:#fff;
            border-right:1px solid var(--sb-line);
            color:var(--sb-text);overflow:hidden
        }
        .admin-sidebar.is-collapsed{
            width:var(--sb-rail);min-width:var(--sb-rail);max-width:var(--sb-rail);flex:0 0 var(--sb-rail)
        }
        .admin-sidebar-backdrop{display:none}
        .admin-mobile-menu-btn{display:none}
        .admin-sidebar-close{display:none}
        .sidebar-collapse-btn{
            display:inline-flex;align-items:center;justify-content:center;
            width:26px;height:26px;border-radius:8px;border:1px solid var(--sb-line);
            color:var(--sb-muted);background:#fff
        }
        .sidebar-collapse-btn:hover{
            color:var(--sb-accent);background:var(--sb-hover);border-color:#F8BBD0
        }
        .admin-topbar{
            position:fixed;top:0;right:0;left:var(--sb-width);height:var(--sb-top);z-index:30;
            display:flex;align-items:center;gap:.75rem;padding:0 1.15rem;
            background:rgba(255,248,251,.92);backdrop-filter:blur(10px);
            border-bottom:1px solid var(--sb-line)
        }
        .admin-topbar.is-rail{left:var(--sb-rail)}
        .admin-main{
            margin-top:var(--sb-top);
            padding:.4rem .75rem .9rem;
        }
        .admin-main.has-page-header{margin-top:0;padding-top:.5rem}
        @media (min-width:640px){
            .admin-main{padding:.45rem 1rem 1rem}
        }
        @media (min-width:1024px){
            .admin-main{padding:.5rem 1.15rem 1.15rem}
        }

        @media (max-width:767px){
            .admin-sidebar{
                position:fixed;top:0;left:0;bottom:0;height:auto;
                width:min(288px,88vw);min-width:0;max-width:min(288px,88vw);flex:none;
                transform:translateX(-105%);
                box-shadow:16px 0 40px rgba(58,20,40,.18)
            }
            .admin-sidebar.is-collapsed{width:min(288px,88vw);min-width:0;max-width:min(288px,88vw);flex:none}
            .admin-sidebar.is-mobile-open{transform:translateX(0)}
            .admin-sidebar-backdrop{
                display:none;position:fixed;inset:0;z-index:40;
                background:rgba(58,20,40,.35)
            }
            .admin-sidebar-backdrop.is-visible{display:block}
            .admin-mobile-menu-btn{display:inline-flex}
            .admin-sidebar-close{display:inline-flex}
            .sidebar-collapse-btn{display:none !important}
            .admin-topbar{left:0;padding:0 1rem}
            .admin-topbar.is-rail{left:0}
        }

        .admin-sidebar .sidebar-head{
            height:var(--sb-top);padding:0 .75rem 0 .9rem;border-bottom:1px solid var(--sb-line)
        }
        .sidebar-brand-link{color:var(--sb-text-strong) !important;text-decoration:none}
        .sidebar-brand-text{font-size:15px;font-weight:700;letter-spacing:-.01em;line-height:1.15}
        .sidebar-brand-mark{border-radius:999px;background:#FFF5F8;border:1px solid #F8BBD0}
        .admin-sidebar-nav{
            padding:.55rem .55rem 1.1rem;
            scrollbar-width:thin;scrollbar-color:#F8BBD0 transparent
        }
        .nav-divider{height:0;margin:.55rem .35rem .5rem;border-top:1px solid var(--sb-line)}

        .nav-link,.nav-group{
            display:flex;align-items:center;width:100%;gap:10px;
            padding:7px 8px;margin:1px 0;border-radius:10px;
            font-size:13px;font-weight:600;letter-spacing:0;
            color:var(--sb-text);text-align:left;background:transparent;border:0;
            cursor:pointer;text-decoration:none;line-height:1.2
        }
        .nav-group{justify-content:space-between}
        .nav-group-main{display:flex;align-items:center;gap:10px;min-width:0}
        .nav-label{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .nav-link:hover,.nav-group:hover{background:var(--sb-hover);color:var(--sb-text-strong)}
        .nav-link--badge{justify-content:flex-start}
        .nav-link--badge .nav-label{flex:1;min-width:0}

        .nav-ico-wrap{
            width:28px;height:28px;flex-shrink:0;border-radius:8px;
            display:inline-flex;align-items:center;justify-content:center;
            color:#9A6B7D;background:#FFF5F8
        }
        .nav-ico{width:15px;height:15px;stroke-width:1.9}

        .nav-link.is-active,.nav-group.is-open{font-weight:700;color:var(--sb-accent)}
        .nav-link.is-active{background:var(--sb-active)}
        .nav-group.is-open{background:var(--sb-hover)}
        .nav-link.is-active .nav-ico-wrap,.nav-group.is-open .nav-ico-wrap{
            color:#fff;background:var(--sb-accent)
        }

        .nav-chevron{width:11px;height:11px;flex-shrink:0;opacity:.4}
        .nav-group:hover .nav-chevron,.nav-group.is-open .nav-chevron{opacity:.75}

        .nav-submenu{
            margin:2px 0 6px 18px;padding:2px 0 2px 10px;
            border-left:2px solid var(--sb-line);
            display:flex;flex-direction:column;gap:1px
        }
        .nav-sub{
            display:flex;align-items:center;gap:8px;
            padding:6px 9px;border-radius:8px;
            font-size:12.5px;font-weight:600;color:#7A5566;
            text-decoration:none;letter-spacing:0;line-height:1.2
        }
        .nav-sub:hover{background:var(--sb-hover);color:var(--sb-text-strong)}
        .nav-sub.is-active{
            background:#fff;color:var(--sb-accent);font-weight:700;
            border:1px solid #F8BBD0
        }
        .nav-sub-ico{width:14px;height:14px;flex-shrink:0;opacity:.55;stroke-width:1.8}
        .nav-sub:hover .nav-sub-ico{opacity:.9}
        .nav-sub.is-active .nav-sub-ico{opacity:1;color:var(--sb-accent)}
        .nav-badge{
            display:inline-flex;align-items:center;justify-content:center;
            min-width:18px;height:18px;padding:0 5px;margin-left:auto;
            border-radius:999px;background:#C2185B;color:#fff;
            font-size:9.5px;font-weight:700;line-height:1
        }

        .sidebar-foot{flex-shrink:0;padding:.55rem .65rem .75rem}
        .sidebar-store-card{
            display:flex;align-items:center;gap:10px;
            padding:10px 12px;border-radius:12px;text-decoration:none;
            background:#FFF5F8;border:1px solid #F8BBD0;color:#3a1f2e
        }
        .sidebar-store-card:hover{background:#FCE4EC}
        .sidebar-store-ico{
            width:32px;height:32px;flex-shrink:0;border-radius:999px;
            display:grid;place-items:center;background:#fff;color:#C2185B;border:1px solid #F8BBD0
        }
        .sidebar-store-ico svg{width:15px;height:15px}
        .sidebar-store-card strong{display:block;font-size:13px;font-weight:700;line-height:1.15}
        .sidebar-store-card small{display:block;font-size:11px;font-weight:500;color:#8A6B7A}

        .admin-sidebar.is-collapsed .nav-label,
        .admin-sidebar.is-collapsed .nav-chevron,
        .admin-sidebar.is-collapsed .nav-badge,
        .admin-sidebar.is-collapsed .nav-divider,
        .admin-sidebar.is-collapsed .sidebar-store-copy{display:none !important}
        .admin-sidebar.is-collapsed .sidebar-store-card{justify-content:center;padding:8px}
        .admin-sidebar.is-collapsed .sidebar-foot{padding:.5rem .4rem .7rem}
        .admin-sidebar.is-collapsed .sidebar-brand-link{justify-content:center;flex:0 0 auto !important;width:100%}
        .admin-sidebar.is-collapsed .sidebar-brand-mark{width:1.9rem !important;height:1.9rem !important}
        .admin-sidebar.is-collapsed .nav-link,
        .admin-sidebar.is-collapsed .nav-group{
            justify-content:center !important;padding:6px 0 !important;gap:0 !important;background:transparent !important;box-shadow:none !important
        }
        .admin-sidebar.is-collapsed .nav-group-main{justify-content:center;width:100%;gap:0}
        .admin-sidebar.is-collapsed .nav-ico-wrap{width:34px;height:34px}
        .admin-sidebar.is-collapsed .sidebar-head{
            flex-direction:column;align-items:center;justify-content:center;
            height:auto !important;min-height:var(--sb-top);padding:.65rem .3rem .5rem;gap:.35rem
        }
        .admin-sidebar.is-collapsed .admin-sidebar-nav{padding:.35rem .28rem 1rem}
        .admin-sidebar.is-collapsed .nav-submenu{display:none !important}

        .admin-topbar .topbar-pill{
            display:inline-flex;align-items:center;gap:6px;border-radius:999px;
            padding:7px 14px;font-size:12px;font-weight:700;line-height:1;color:#fff
        }
        .admin-topbar .topbar-pill--pos{background:#C2185B}
        .admin-topbar .topbar-pill--store{background:#AD1457}
        .admin-topbar .topbar-icon-btn{border-radius:10px !important;border-color:#F5D6E5 !important}
        .admin-topbar .topbar-icon-btn:hover{
            border-color:#F8BBD0 !important;background:#FFF5F8 !important;color:#C2185B !important
        }
        @media (max-width:639px){.admin-topbar .topbar-pill{padding:7px 11px;font-size:11px}}

        body.admin-panel header.bg-white\/80,
        body.admin-panel header[class*="bg-white"]{
            background:rgba(255,255,255,.92) !important;
            border-color:#F5D6E5 !important
        }
        body.admin-panel .bg-blue-600,
        body.admin-panel .bg-indigo-600,
        body.admin-panel .bg-violet-600{background-color:#C2185B !important}
        body.admin-panel .hover\:bg-blue-700:hover,
        body.admin-panel .hover\:bg-indigo-700:hover{background-color:#AD1457 !important}
        body.admin-panel .text-blue-600,
        body.admin-panel .text-indigo-600,
        body.admin-panel .text-violet-600{color:#C2185B !important}
        body.admin-panel .border-blue-200,
        body.admin-panel .border-indigo-200{border-color:#F8BBD0 !important}
        body.admin-panel .bg-blue-50,
        body.admin-panel .bg-indigo-50,
        body.admin-panel .hover\:bg-blue-50:hover{background-color:#FFF5F8 !important}
    </style>
</head>
<body class="admin-panel font-sans antialiased text-slate-900 bg-[#FFF8FB]">
    @php
        $adminFlash = array_filter([
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info'),
        ], fn ($v) => filled($v));
    @endphp
    <script>
        window.__ADMIN_FLASH__ = @json($adminFlash);
    </script>

    <div id="admin-progress" class="admin-progress" aria-hidden="true">
        <div id="admin-progress-bar" class="admin-progress__bar"></div>
        <div id="admin-progress-peg" class="admin-progress__peg"></div>
    </div>

    <div x-data="{
            sidebarOpen: false,
            sidebarCollapsed: false,
            catalogOpen: false,
            inventoryOpen: false,
            salesOpen: false,
            customersOpen: false,
            insightsOpen: false,
            websiteOpen: false,
            teamOpen: false,
            init() {
                localStorage.removeItem('adminSidebarCollapsed');
                localStorage.setItem('adminSidebarCollapseV', '4');
                this.sidebarCollapsed = false;
                this.sidebarOpen = false;
            },
            toggleSidebarCollapse() {
                if (window.matchMedia('(max-width: 767px)').matches) return;
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('adminSidebarCollapsed', this.sidebarCollapsed ? '1' : '0');
            },
            expandThen(key) {
                if (this.sidebarCollapsed) {
                    this.sidebarCollapsed = false;
                    localStorage.setItem('adminSidebarCollapsed', '0');
                    this[key] = true;
                    return;
                }
                this[key] = !this[key];
            }
         }"
         @keydown.escape.window="sidebarOpen = false"
         @resize.window="if (window.innerWidth < 1024) { sidebarCollapsed = false; }"
         class="flex h-screen overflow-hidden">

        @include('layouts.navigation')

        <div class="admin-scroll-hide relative flex flex-col flex-1 min-w-0 overflow-y-auto overflow-x-hidden">
            @isset($header)
                <header class="bg-white/80 backdrop-blur border-b border-slate-200" style="margin-top:var(--sb-top)">
                    <div class="max-w-[1800px] mx-auto py-2 px-3 sm:px-5 lg:px-6">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="admin-main w-full grow min-w-0 {{ isset($header) ? 'has-page-header' : '' }}">
                <div class="w-full max-w-[1800px] mx-auto min-w-0">
                    {{ $slot }}
                </div>
            </main>
            @include('partials.powered-by', ['variant' => 'admin'])
        </div>
    </div>

    <script>
        function staffAlertBell(feedUrl, readAllUrl) {
            const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
            const post = (url) => fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() },
                credentials: 'same-origin',
                keepalive: true,
            });
            const PANEL_ID = 'alerts';
            return {
                panelOpen: false,
                loading: false,
                unread: 0,
                items: [],
                init() {
                    this.load();
                    setInterval(() => this.load(), 60000);
                    window.addEventListener('admin-topbar-panel', (e) => {
                        if (e.detail?.id !== PANEL_ID && this.panelOpen) {
                            this.panelOpen = false;
                        }
                    });
                },
                async load() {
                    this.loading = true;
                    try {
                        const res = await fetch(feedUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.items = data.items || [];
                        this.unread = Number(data.unread || 0);
                    } catch (e) {
                    } finally {
                        this.loading = false;
                    }
                },
                toggle() {
                    this.panelOpen = !this.panelOpen;
                    if (this.panelOpen) {
                        window.dispatchEvent(new CustomEvent('admin-topbar-panel', { detail: { id: PANEL_ID } }));
                        this.load();
                    }
                },
                async open(item, event) {
                    event.preventDefault();
                    if (item.is_new) {
                        try { await post(item.read_url); } catch (e) {}
                    }
                    if (item.url) {
                        window.location.href = item.url;
                    } else {
                        this.load();
                    }
                },
                async readAll() {
                    try {
                        const res = await post(readAllUrl);
                        if (!res.ok) return;
                        this.items = this.items.map((item) => ({ ...item, is_new: false }));
                        this.unread = 0;
                    } catch (e) {}
                },
            };
        }

        function onlineOrderBell(listUrl, seenUrl) {
            const PANEL_ID = 'online-orders';
            return {
                panelOpen: false,
                loading: false,
                loaded: false,
                unread: 0,
                items: [],
                error: '',
                init() {
                    this.panelOpen = false;
                    this.fetchBadge();
                    setInterval(() => this.fetchBadge(), 60000);
                    window.addEventListener('admin-topbar-panel', (e) => {
                        if (e.detail?.id !== PANEL_ID && this.panelOpen) {
                            this.closePanel();
                        }
                    });
                },
                togglePanel(event) {
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    this.panelOpen = !this.panelOpen;
                    if (this.panelOpen) {
                        window.dispatchEvent(new CustomEvent('admin-topbar-panel', { detail: { id: PANEL_ID } }));
                        this.fetchList(true);
                    } else {
                        this.markSeen();
                    }
                },
                closePanel() {
                    if (!this.panelOpen) return;
                    this.panelOpen = false;
                    this.markSeen();
                },
                async openItem(item, event) {
                    if (event) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    // Clear badge before navigating so it does not stick after click-through.
                    await this.markSeen(true);
                    window.location.href = item.url;
                },
                async fetchBadge() {
                    try {
                        const res = await fetch(listUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        this.unread = Number(data.unread || 0);
                        this.error = '';
                    } catch (e) {
                        // Keep last known unread; avoid silently forcing 0 on network blips.
                    }
                },
                async fetchList(force = false) {
                    if (this.loading || (this.loaded && !force)) return;
                    this.loading = true;
                    this.error = '';
                    try {
                        const res = await fetch(listUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) {
                            this.error = 'Could not load notifications.';
                            return;
                        }
                        const data = await res.json();
                        this.items = data.items || [];
                        this.unread = Number(data.unread || 0);
                        this.loaded = true;
                    } catch (e) {
                        this.error = 'Could not load notifications.';
                    } finally {
                        this.loading = false;
                    }
                },
                async markSeen(force = false) {
                    if (!force && this.unread <= 0 && !this.items.some((item) => item.is_new)) {
                        return;
                    }
                    try {
                        const res = await fetch(seenUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            credentials: 'same-origin',
                            keepalive: true,
                        });
                        if (!res.ok) return;
                        this.items = this.items.map((item) => ({ ...item, is_new: false }));
                        this.unread = 0;
                    } catch (e) {}
                },
            };
        }

        setInterval(function () {
            if (typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken();
            } else {
                fetch('/refresh-session', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).catch(function () {});
            }
        }, 10 * 60 * 1000);

        window.addEventListener('pageshow', function () {
            if (typeof window.refreshCsrfToken === 'function') {
                window.refreshCsrfToken();
            }
        });


        /** Open POS in a large counter window (not a tiny popup/tab). */
        window.launchPosTerminal = function (url) {
            url = url || @json(route('pos.index'));
            var w = screen.availWidth || screen.width || 1280;
            var h = screen.availHeight || screen.height || 800;
            var features = 'popup=yes,width=' + w + ',height=' + h + ',left=0,top=0,resizable=yes,scrollbars=yes';
            var win = window.open(url, 'nexa_pos_terminal', features);
            if (!win) {
                window.location.href = url;
                return false;
            }
            try {
                win.focus();
                win.moveTo(0, 0);
                win.resizeTo(w, h);
            } catch (e) {}
            return false;
        };
    </script>
</body>
</html>
