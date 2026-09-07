@extends('layouts.app')

@section('title', 'Device')
@section('page_title', 'Device')
@section('page_subtitle', 'Connection and sync controls')

@section('content')
<div x-data="devicePage()" x-init="init()" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Connection status</h2>
            <div class="flex items-center gap-4 mb-6">
                <div class="h-14 w-14 rounded-2xl flex items-center justify-center"
                    :class="status === 'connected' ? 'bg-teal-50 text-teal-600' : 'bg-red-50 text-red-600'">
                    <i class="fas fa-wifi text-2xl"></i>
                </div>
                <div>
                    <p class="text-xl font-semibold capitalize" x-text="status"></p>
                    <p class="text-sm text-slate-500" x-text="message"></p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button type="button" @click="testConnection()"
                    class="px-4 py-3 rounded-xl bg-accent hover:bg-accent-dark text-white text-sm">
                    <i class="fas fa-plug mr-2"></i>Test connection
                </button>
                <button type="button" @click="syncUsers()"
                    class="px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm">
                    <i class="fas fa-users mr-2 text-sky-600"></i>Sync users
                </button>
                <button type="button" @click="fetchUsers()"
                    class="px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm">
                    <i class="fas fa-list mr-2 text-amber-600"></i>List device users
                </button>
                <a href="{{ route('attendance.export') }}"
                    class="px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-sm text-center">
                    <i class="fas fa-download mr-2 text-violet-600"></i>Export raw logs
                </a>
            </div>
        </div>

        <div class="bg-ink-900 text-white rounded-2xl p-6 shadow-sm">
            <h2 class="font-semibold mb-4">Cloud / ADMS</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-3 border-b border-white/10 pb-2">
                    <dt class="text-slate-400">Mode</dt>
                    <dd>{{ config('zkteco.mode') }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-white/10 pb-2">
                    <dt class="text-slate-400">Device IP</dt>
                    <dd>{{ config('zkteco.device_ip') }}</dd>
                </div>
                <div class="flex justify-between gap-3 border-b border-white/10 pb-2">
                    <dt class="text-slate-400">Server</dt>
                    <dd>192.168.68.56:8081</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-slate-400">Endpoints</dt>
                    <dd class="text-right text-teal-300">/iclock/cdata</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6" x-show="devices.length" x-cloak>
        <h2 class="font-semibold text-slate-900 mb-4">Seen devices</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left font-medium px-4 py-2">Serial</th>
                        <th class="text-left font-medium px-4 py-2">IP</th>
                        <th class="text-left font-medium px-4 py-2">Last seen</th>
                        <th class="text-left font-medium px-4 py-2">Online</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="d in devices" :key="d.sn">
                        <tr>
                            <td class="px-4 py-3 font-medium" x-text="d.sn"></td>
                            <td class="px-4 py-3" x-text="d.ip"></td>
                            <td class="px-4 py-3" x-text="d.last_seen"></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                    :class="d.online ? 'bg-teal-50 text-teal-700' : 'bg-slate-100 text-slate-600'"
                                    x-text="d.online ? 'online' : 'offline'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function devicePage() {
    return {
        status: 'unknown',
        message: 'Checking…',
        devices: [],
        async init() { await this.testConnection(false); },
        async testConnection(showToast = true) {
            try {
                const data = await apiFetch('/attendance/test-connection');
                this.status = data.status === 'success' ? 'connected' : 'disconnected';
                this.message = data.message || '';
                this.devices = data.devices || [];
                if (showToast) notify(data.status === 'success' ? 'success' : 'error', 'Device', data.message);
            } catch (e) {
                this.status = 'disconnected';
                this.message = e.message;
                if (showToast) notify('error', 'Device', e.message);
            }
        },
        async syncUsers() {
            try {
                const data = await apiFetch('/attendance/sync-device-users', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
                notify('success', 'Users', data.message || 'Synced');
            } catch (e) { notify('error', 'Users', e.message); }
        },
        async fetchUsers() {
            try {
                const data = await apiFetch('/attendance/device-users');
                const users = Array.isArray(data) ? data : (data.users || []);
                notify('success', 'Device users', `Found ${users.length} users` + (data.message ? ` — ${data.message}` : ''));
            } catch (e) { notify('error', 'Device users', e.message); }
        }
    }
}
</script>
@endpush
