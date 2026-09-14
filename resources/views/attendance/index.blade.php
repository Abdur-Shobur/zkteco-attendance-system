@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Live overview of device attendance')

@section('header_actions')
    <a href="{{ route('attendance.settings.page') }}"
        class="hidden sm:inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm hover:bg-slate-50">
        <i class="fas fa-business-time mr-2 text-accent"></i> Office time
    </a>
    <a href="{{ route('attendance.report') }}"
        class="inline-flex items-center px-3 py-2 rounded-lg bg-accent hover:bg-accent-dark text-white text-sm">
        <i class="fas fa-chart-column mr-2"></i> Reports
    </a>
@endsection

@section('content')
<div x-data="dashboardPage()" x-init="init()">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-500">Device</p>
                    <p class="mt-1 text-xl font-semibold capitalize"
                        :class="connectionStatus === 'connected' ? 'text-teal-600' : 'text-red-600'"
                        x-text="connectionStatus"></p>
                </div>
                <div class="h-10 w-10 rounded-xl flex items-center justify-center"
                    :class="connectionStatus === 'connected' ? 'bg-teal-50 text-teal-600' : 'bg-red-50 text-red-600'">
                    <i class="fas fa-wifi"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-500">Office hours</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900">{{ $office->workHoursLabel() }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-500">Late grace</p>
                    <p class="mt-1 text-xl font-semibold text-slate-900">{{ $office->late_grace_minutes }} min</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm text-slate-500">Weekly off</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ implode(', ', $office->offDayLabels()) ?: 'None' }}</p>
                </div>
                <div class="h-10 w-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                    <i class="fas fa-calendar-xmark"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total punches</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($stats['total_logs']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Today</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($stats['today_logs']) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Users</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($stats['users']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="font-semibold text-slate-900">Recent punches</h2>
                <a href="{{ route('attendance.logs.page') }}" class="text-sm text-accent hover:underline">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left font-medium px-5 py-3">Employee</th>
                            <th class="text-left font-medium px-5 py-3">Time</th>
                            <th class="text-left font-medium px-5 py-3">Type</th>
                            <th class="text-left font-medium px-5 py-3">Verify</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentLogs as $log)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3">
                                    <div class="font-medium text-slate-900">{{ $log->user?->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-slate-500">ID {{ $log->device_user_id }}</div>
                                </td>
                                <td class="px-5 py-3 text-slate-700 whitespace-nowrap">
                                    {{ $log->punch_time->format('M d, Y h:i A') }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700">
                                        {{ str_replace('_', ' ', $log->punch_type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $log->verification_type ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-slate-500">No punches yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-900">Office rules</h2>
                    <a href="{{ route('attendance.settings.page') }}" class="text-sm text-accent hover:underline">Edit</a>
                </div>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Work time</dt>
                        <dd class="font-medium">{{ $office->workHoursLabel() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Late after</dt>
                        <dd class="font-medium">{{ $office->late_grace_minutes }} minutes</dd>
                    </div>
                    <div class="flex justify-between gap-3 border-b border-slate-100 pb-2">
                        <dt class="text-slate-500">Weekly off</dt>
                        <dd class="font-medium text-right">{{ implode(', ', $office->offDayLabels()) ?: 'None' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Holidays set</dt>
                        <dd class="font-medium">{{ count($office->holidays ?? []) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h2 class="font-semibold text-slate-900 mb-4">Quick actions</h2>
                <div class="space-y-2">
                    <button type="button" @click="testConnection()"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50 text-left">
                        <i class="fas fa-plug text-accent"></i>
                        <span>Test device connection</span>
                    </button>
                    <a href="{{ route('attendance.settings.page') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                        <i class="fas fa-business-time text-teal-600"></i>
                        <span>Office time & off days</span>
                    </a>
                    <a href="{{ route('attendance.report') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                        <i class="fas fa-file-lines text-amber-600"></i>
                        <span>Open attendance report</span>
                    </a>
                    <a href="{{ route('attendance.device.page') }}"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-slate-50">
                        <i class="fas fa-server text-slate-600"></i>
                        <span>Device settings</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardPage() {
    return {
        connectionStatus: 'unknown',
        async init() {
            await this.testConnection(false);
        },
        async testConnection(showToast = true) {
            try {
                const data = await apiFetch('/attendance/test-connection');
                this.connectionStatus = data.status === 'success' ? 'connected' : 'disconnected';
                if (showToast) notify(data.status === 'success' ? 'success' : 'error', 'Connection', data.message);
            } catch (e) {
                this.connectionStatus = 'disconnected';
                if (showToast) notify('error', 'Connection', e.message);
            }
        }
    }
}
</script>
@endpush
