@extends('layouts.app')

@section('title', 'Users')
@section('page_title', 'Users')
@section('page_subtitle', 'Employees mapped from the biometric device')

@section('header_actions')
    <a href="{{ route('attendance.users.create') }}"
        class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm hover:bg-slate-50">
        <i class="fas fa-user-plus mr-2"></i> Add user
    </a>
    <div x-data>
        <button type="button" @click="
            apiFetch('/attendance/sync-device-users', { method: 'POST', headers: { 'Content-Type': 'application/json' } })
                .then(d => { notify('success', 'Sync', d.message || 'Done'); setTimeout(() => location.reload(), 1200); })
                .catch(e => notify('error', 'Sync', e.message));
        "
            class="inline-flex items-center px-3 py-2 rounded-lg bg-accent hover:bg-accent-dark text-white text-sm">
            <i class="fas fa-sync mr-2"></i> Sync from device
        </button>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 text-teal-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Total users</p>
            <p class="text-2xl font-semibold mt-1">{{ $users->total() }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">With device ID</p>
            <p class="text-2xl font-semibold mt-1">{{ $mappedCount }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <p class="text-sm text-slate-500">Punches linked</p>
            <p class="text-2xl font-semibold mt-1">{{ $punchLinked }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Name</th>
                        <th class="text-left font-medium px-5 py-3">Email</th>
                        <th class="text-left font-medium px-5 py-3">Device ID</th>
                        <th class="text-left font-medium px-5 py-3">Employee ID</th>
                        <th class="text-left font-medium px-5 py-3">Role</th>
                        <th class="text-left font-medium px-5 py-3">Last punch</th>
                        <th class="text-right font-medium px-5 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-accent-soft text-accent-dark flex items-center justify-center text-sm font-semibold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-slate-900">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $user->email }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $user->device_user_id ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $user->employee_id ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @if ($user->is_admin)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700">Admin</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">User</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                {{ optional($user->latestAttendanceLog?->punch_time)->format('M d, Y h:i A') ?? '—' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('attendance.users.edit', $user) }}"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm">
                                    <i class="fas fa-pen mr-1.5"></i> Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                No users yet. Click <strong>Sync from device</strong> or <strong>Add user</strong>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="px-5 py-4 border-t border-slate-100">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
