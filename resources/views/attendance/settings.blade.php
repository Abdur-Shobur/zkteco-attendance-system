@extends('layouts.app')

@section('title', 'Office Settings')
@section('page_title', 'Office Settings')
@section('page_subtitle', 'Work hours, off days, holidays, and late rules')

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 text-teal-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('attendance.settings.update') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-semibold text-slate-900 mb-1">Office time</h2>
                    <p class="text-sm text-slate-500 mb-5">Used for late detection and worked-hour reports.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Work start</label>
                            <input type="time" name="work_start" value="{{ old('work_start', $office->work_start) }}" required
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Work end</label>
                            <input type="time" name="work_end" value="{{ old('work_end', $office->work_end) }}" required
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Late grace (minutes)</label>
                            <input type="number" min="0" max="240" name="late_grace_minutes"
                                value="{{ old('late_grace_minutes', $office->late_grace_minutes) }}" required
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-semibold text-slate-900 mb-1">Working days</h2>
                    <p class="text-sm text-slate-500 mb-5">Unchecked days are treated as weekly off days (no absent).</p>

                    @php $selected = old('working_days', $office->working_days ?? [1,2,3,4,5]); @endphp
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach ($dayNames as $value => $label)
                            <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" name="working_days[]" value="{{ $value }}"
                                    class="rounded border-slate-300 text-accent focus:ring-accent"
                                    @checked(in_array($value, array_map('intval', (array) $selected), true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="font-semibold text-slate-900 mb-1">Holiday / off dates</h2>
                    <p class="text-sm text-slate-500 mb-5">One date per line (`YYYY-MM-DD`). These count as off days.</p>
                    <textarea name="holidays" rows="8"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-mono"
                        placeholder="2026-12-16&#10;2026-12-25">{{ old('holidays', implode("\n", $office->holidays ?? [])) }}</textarea>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-ink-900 text-white rounded-2xl p-6 shadow-sm">
                    <h2 class="font-semibold mb-4">Current summary</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3 border-b border-white/10 pb-2">
                            <dt class="text-slate-400">Hours</dt>
                            <dd>{{ $office->workHoursLabel() }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-white/10 pb-2">
                            <dt class="text-slate-400">Late after</dt>
                            <dd>{{ $office->late_grace_minutes }} min</dd>
                        </div>
                        <div class="flex justify-between gap-3 border-b border-white/10 pb-2">
                            <dt class="text-slate-400">Weekly off</dt>
                            <dd class="text-right">{{ implode(', ', $office->offDayLabels()) ?: 'None' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-400">Holidays</dt>
                            <dd>{{ count($office->holidays ?? []) }}</dd>
                        </div>
                    </dl>
                </div>

                <button type="submit"
                    class="w-full rounded-xl bg-accent hover:bg-accent-dark text-white font-medium py-3">
                    <i class="fas fa-floppy-disk mr-2"></i>Save settings
                </button>
                <a href="{{ route('attendance.report') }}"
                    class="block w-full text-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 py-3 text-sm">
                    Open reports
                </a>
            </div>
        </div>
    </form>
@endsection
