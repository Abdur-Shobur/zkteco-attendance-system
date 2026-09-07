<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Attendance') · ZKTeco</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: {
                            950: '#0b1220',
                            900: '#111827',
                            800: '#1f2937',
                            700: '#334155',
                        },
                        accent: {
                            DEFAULT: '#0d9488',
                            soft: '#ccfbf1',
                            dark: '#0f766e',
                        }
                    },
                    fontFamily: {
                        sans: ['Segoe UI', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .nav-link.active {
            background: rgba(13, 148, 136, 0.18);
            color: #5eead4;
        }
    </style>
    @stack('head')
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 bg-ink-950 text-slate-300 transform transition-transform duration-200 lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10">
                <div class="h-9 w-9 rounded-lg bg-accent flex items-center justify-center text-white">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <div>
                    <div class="text-white font-semibold leading-tight">ZKTeco</div>
                    <div class="text-[11px] text-slate-400">Attendance System</div>
                </div>
            </div>

            <nav class="p-3 space-y-1 text-sm">
                <p class="px-3 pt-3 pb-1 text-[11px] uppercase tracking-wider text-slate-500">Main</p>

                <a href="{{ route('attendance.index') }}"
                    class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 hover:text-white transition {{ request()->routeIs('attendance.index') ? 'active' : '' }}">
                    <i class="fas fa-gauge w-5 text-center"></i> Dashboard
                </a>
                <a href="{{ route('attendance.logs.page') }}"
                    class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 hover:text-white transition {{ request()->routeIs('attendance.logs.page') ? 'active' : '' }}">
                    <i class="fas fa-clock-rotate-left w-5 text-center"></i> Attendance Logs
                </a>
                <a href="{{ route('attendance.users.page') }}"
                    class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 hover:text-white transition {{ request()->routeIs('attendance.users.page') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 text-center"></i> Users
                </a>
                <a href="{{ route('attendance.report') }}"
                    class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 hover:text-white transition {{ request()->routeIs('attendance.report*') ? 'active' : '' }}">
                    <i class="fas fa-chart-column w-5 text-center"></i> Reports
                </a>

                <p class="px-3 pt-5 pb-1 text-[11px] uppercase tracking-wider text-slate-500">Device</p>
                <a href="{{ route('attendance.device.page') }}"
                    class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 hover:text-white transition {{ request()->routeIs('attendance.device.page') ? 'active' : '' }}">
                    <i class="fas fa-server w-5 text-center"></i> Device
                </a>
            </nav>

            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10 text-xs text-slate-500">
                ADMS · Port 8081
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 min-w-0 flex flex-col min-h-screen">
            <header class="h-16 bg-white border-b border-slate-200 sticky top-0 z-30">
                <div class="h-full px-4 sm:px-6 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="sidebarOpen = !sidebarOpen"
                            class="lg:hidden h-10 w-10 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
                            <i class="fas fa-bars"></i>
                        </button>
                        <div>
                            <h1 class="text-lg font-semibold text-slate-900">@yield('page_title', 'Dashboard')</h1>
                            @hasSection('page_subtitle')
                                <p class="text-xs text-slate-500">@yield('page_subtitle')</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @yield('header_actions')
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>

        {{-- Toast --}}
        <div x-data="toastBus()" x-cloak @toast.window="show($event.detail)"
            class="fixed top-4 right-4 z-[60] space-y-2 w-[min(100%-2rem,24rem)]">
            <template x-for="item in items" :key="item.id">
                <div class="bg-white rounded-xl shadow-lg border p-4"
                    :class="item.type === 'success' ? 'border-teal-200' : 'border-red-200'">
                    <div class="flex gap-3">
                        <i class="mt-0.5"
                            :class="item.type === 'success' ? 'fas fa-circle-check text-teal-600' : 'fas fa-circle-exclamation text-red-600'"></i>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-900" x-text="item.title"></p>
                            <p class="text-sm text-slate-600 break-words" x-text="item.message"></p>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-slate-600" @click="dismiss(item.id)">
                            <i class="fas fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        function toastBus() {
            return {
                items: [],
                show({ type = 'success', title = '', message = '' }) {
                    const id = Date.now() + Math.random();
                    this.items.push({ id, type, title, message });
                    setTimeout(() => this.dismiss(id), 5000);
                },
                dismiss(id) {
                    this.items = this.items.filter(i => i.id !== id);
                }
            }
        }

        function notify(type, title, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, title, message } }));
        }

        async function apiFetch(url, options = {}) {
            const res = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    ...(options.headers || {}),
                }
            });
            if (res.redirected && res.url.includes('/login')) {
                throw new Error('Please log in first');
            }
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        }
    </script>
    @stack('scripts')
</body>
</html>
