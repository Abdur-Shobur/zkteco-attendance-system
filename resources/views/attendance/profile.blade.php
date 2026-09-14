@extends('layouts.app')

@section('title', $isOwnProfile ? 'My Profile' : 'Edit User')
@section('page_title', $isOwnProfile ? 'My Profile' : 'Edit User')
@section('page_subtitle', $isOwnProfile ? 'Update your admin account' : 'Update profile for ' . $user->name)

@section('header_actions')
    <a href="{{ route('attendance.users.page') }}"
        class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-200 bg-white text-sm hover:bg-slate-50">
        <i class="fas fa-arrow-left mr-2"></i> Back to users
    </a>
@endsection

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 text-teal-800 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 px-4 py-3 text-sm">
            <ul class="list-disc pl-4 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
        action="{{ $isOwnProfile && request()->routeIs('attendance.profile*') ? route('attendance.profile.update') : route('attendance.users.update', $user) }}"
        class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Basic info</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Device user ID</label>
                    <input type="text" name="device_user_id" value="{{ old('device_user_id', $user->device_user_id) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="e.g. 1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Employee ID</label>
                    <input type="text" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="e.g. EMP001">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-900 mb-1">Password</h2>
            <p class="text-sm text-slate-500 mb-4">Leave blank to keep the current password.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                    <input type="password" name="password"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                    <input type="password" name="password_confirmation"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h2 class="font-semibold text-slate-900 mb-4">Access</h2>
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 cursor-pointer hover:bg-slate-50">
                <input type="hidden" name="is_admin" value="0">
                <input type="checkbox" name="is_admin" value="1" class="rounded border-slate-300 text-accent focus:ring-accent"
                    @checked(old('is_admin', $user->is_admin))>
                <span>
                    <span class="block text-sm font-medium text-slate-900">Admin access</span>
                    <span class="block text-xs text-slate-500">Can log in to the admin panel</span>
                </span>
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-xl bg-accent hover:bg-accent-dark text-white px-5 py-2.5 text-sm font-medium">
                <i class="fas fa-floppy-disk mr-2"></i>Save profile
            </button>
            <a href="{{ route('attendance.users.page') }}" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
        </div>
    </form>
@endsection
