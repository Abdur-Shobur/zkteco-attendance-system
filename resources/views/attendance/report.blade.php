@extends('layouts.app')

@section('title', 'Reports')
@section('page_title', 'Attendance Report')
@section('page_subtitle', 'Office hours ' . \App\Models\OfficeSetting::current()->workHoursLabel() . ' · Late after ' . $lateGrace . ' min grace')

@section('header_actions')
    <a href="{{ route('attendance.report.export', request()->query()) }}"
        class="inline-flex items-center px-3 py-2 rounded-lg bg-accent hover:bg-accent-dark text-white text-sm">
        <i class="fas fa-download mr-2"></i> Export CSV
    </a>
@endsection

@section('content')
    <form method="GET" action="{{ route('attendance.report') }}"
        class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select name="status" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    @foreach (['all' => 'All', 'present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'incomplete' => 'Incomplete'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Employee</label>
                <select name="user_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">All employees</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>
                            {{ $user->name }} ({{ $user->device_user_id }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button class="w-full bg-ink-900 hover:bg-ink-800 text-white rounded-lg px-4 py-2 text-sm">
                    <i class="fas fa-filter mr-2"></i>Apply
                </button>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ route('attendance.report', ['start_date' => now()->toDateString(), 'end_date' => now()->toDateString(), 'status' => $status, 'user_id' => $userId]) }}"
                class="text-xs px-3 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700">Today</a>
            <a href="{{ route('attendance.report', ['start_date' => now()->startOfWeek()->toDateString(), 'end_date' => now()->toDateString(), 'status' => $status, 'user_id' => $userId]) }}"
                class="text-xs px-3 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700">This week</a>
            <a href="{{ route('attendance.report', ['start_date' => now()->startOfMonth()->toDateString(), 'end_date' => now()->toDateString(), 'status' => $status, 'user_id' => $userId]) }}"
                class="text-xs px-3 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700">This month</a>
        </div>
    </form>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Present</p>
            <p class="text-2xl font-bold text-teal-600">{{ $report['summary']['present'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Late</p>
            <p class="text-2xl font-bold text-amber-600">{{ $report['summary']['late'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Absent</p>
            <p class="text-2xl font-bold text-rose-600">{{ $report['summary']['absent'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Incomplete</p>
            <p class="text-2xl font-bold text-sky-600">{{ $report['summary']['incomplete'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="font-semibold text-slate-900">
                Daily report
                <span class="text-sm font-normal text-slate-500">({{ $report['summary']['total_rows'] }} rows)</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Date</th>
                        <th class="text-left font-medium px-5 py-3">Employee</th>
                        <th class="text-left font-medium px-5 py-3">Clock In</th>
                        <th class="text-left font-medium px-5 py-3">Clock Out</th>
                        <th class="text-left font-medium px-5 py-3">Hours</th>
                        <th class="text-left font-medium px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                        @forelse ($report['rows'] as $row)
                        @if (in_array($row['status'], ['weekend', 'off_day'], true))
                            @continue
                        @endif
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-800">
                                {{ \Carbon\Carbon::parse($row['date'])->format('M d, Y') }}
                                <span class="text-slate-400">({{ $row['day_name'] }})</span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900">{{ $row['user_name'] }}</div>
                                <div class="text-xs text-slate-500">ID {{ $row['device_user_id'] }}</div>
                            </td>
                            <td class="px-5 py-3">{{ $row['clock_in'] ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $row['clock_out'] ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $row['worked_hours'] !== null ? $row['worked_hours'] . 'h' : '—' }}</td>
                            <td class="px-5 py-3">
                                @php
                                    $badge = match ($row['status']) {
                                        'present' => 'bg-teal-50 text-teal-700',
                                        'late' => 'bg-amber-50 text-amber-700',
                                        'absent' => 'bg-rose-50 text-rose-700',
                                        'incomplete' => 'bg-sky-50 text-sky-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $badge }}">
                                    {{ ucfirst($row['status']) }}
                                    @if ($row['status'] === 'late' && $row['late_by_minutes'])
                                        ({{ $row['late_by_minutes'] }}m)
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                No rows for this filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
