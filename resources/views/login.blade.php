<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login · ZKTeco Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: { 950: '#0b1220', 900: '#111827' },
                        accent: { DEFAULT: '#0d9488', dark: '#0f766e', soft: '#ccfbf1' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 font-sans antialiased">
    <div class="min-h-screen grid lg:grid-cols-2">
        <div class="hidden lg:flex relative overflow-hidden bg-ink-950 text-white p-12 flex-col justify-between">
            <div class="absolute inset-0 opacity-40"
                style="background: radial-gradient(circle at 20% 20%, #0d9488 0%, transparent 40%), radial-gradient(circle at 80% 70%, #0369a1 0%, transparent 35%);"></div>
            <div class="relative">
                <div class="inline-flex items-center gap-3">
                    <div class="h-11 w-11 rounded-xl bg-accent flex items-center justify-center">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <div>
                        <div class="font-semibold text-lg">ZKTeco Attendance</div>
                        <div class="text-sm text-slate-400">Admin panel</div>
                    </div>
                </div>
            </div>
            <div class="relative max-w-md">
                <h1 class="text-4xl font-semibold leading-tight">Secure access to your biometric attendance system</h1>
                <p class="mt-4 text-slate-300">Sign in to manage users, attendance logs, reports, and device sync.</p>
            </div>
            <p class="relative text-sm text-slate-500">ADMS device endpoints stay public · Admin UI requires login</p>
        </div>

        <div class="flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold text-slate-900">Sign in</h2>
                    <p class="text-sm text-slate-500 mt-1">Use your admin account to continue</p>
                </div>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 px-4 py-3 text-sm">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 rounded-xl border border-teal-200 bg-teal-50 text-teal-700 px-4 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent"
                            placeholder="admin@company.com">
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" id="password" name="password" required
                            class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent"
                            placeholder="••••••••">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="remember" value="1"
                            class="rounded border-slate-300 text-accent focus:ring-accent"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label for="remember" class="text-sm text-slate-600">Remember me</label>
                    </div>
                    <button type="submit"
                        class="w-full rounded-xl bg-accent hover:bg-accent-dark text-white font-medium py-2.5 transition">
                        <i class="fas fa-right-to-bracket mr-2"></i>Login
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
