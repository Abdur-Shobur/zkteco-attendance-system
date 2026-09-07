@extends('layouts.app')

@section('title', 'Attendance Logs')
@section('page_title', 'Attendance Logs')
@section('page_subtitle', 'Raw punch records from the device')

@section('header_actions')
    <a href="{{ route('attendance.export', request()->query()) }}"
        class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm hover:bg-slate-50">
        <i class="fas fa-download mr-2"></i> Export
    </a>
@endsection

@section('content')
    <form method="GET" action="{{ route('attendance.logs.page') }}"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
            <input type="date" name="start_date" value="{{ $startDate }}"
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
            <input type="date" name="end_date" value="{{ $endDate }}"
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Device user ID</label>
            <input type="text" name="device_user_id" value="{{ $deviceUserId }}" placeholder="e.g. 1"
                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex items-end">
            <button class="w-full bg-accent hover:bg-accent-dark text-white rounded-lg px-4 py-2 text-sm">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </div>
    </form>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Employee</th>
                        <th class="text-left font-medium px-5 py-3">Device ID</th>
                        <th class="text-left font-medium px-5 py-3">Punch time</th>
                        <th class="text-left font-medium px-5 py-3">Type</th>
                        <th class="text-left font-medium px-5 py-3">Verification</th>
                        <th class="text-left font-medium px-5 py-3">Device IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900">{{ $log->user?->name ?? 'Unknown' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user?->email ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $log->device_user_id }}</td>
                            <td class="px-5 py-3 text-slate-700 whitespace-nowrap">
                                {{ $log->punch_time->format('M d, Y h:i:s A') }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $log->punch_type === 'check_out' ? 'bg-rose-50 text-rose-700' : 'bg-teal-50 text-teal-700' }}">
                                    {{ str_replace('_', ' ', $log->punch_type) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $log->verification_type ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-600">{{ $log->device_ip }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">No logs for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">{{ $logs->withQueryString()->links() }}</div>
        @endif
    </div>
@endsection
