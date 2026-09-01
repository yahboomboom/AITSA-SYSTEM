@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto py-10">
    <a href="{{ $homeUrl }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 mb-4 transition-colors">
        <i class="fa-solid fa-arrow-left"></i> Back to Home
    </a>
    <h1 class="text-lg font-bold text-slate-800 mb-4">My Profile</h1>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-50 text-green-700 text-xs border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 p-4 border border-slate-200 rounded-xl bg-white">
        <p class="text-[10px] uppercase text-slate-400 mb-2 font-bold tracking-wider">Official Record</p>
        <p class="text-sm font-bold text-slate-700">{{ $user->name }}</p>
        <p class="text-xs text-slate-500 mt-0.5">{{ $user->login_id ?? '—' }}</p>
        <p class="text-[11px] text-slate-400 mt-2">Your name and student number are managed by the Registrar's Office. Contact them if these need correcting.</p>
    </div>

    <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="w-full text-sm border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Contact Number</label>
            <input type="text" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}"
                   placeholder="09XXXXXXXXX"
                   class="w-full text-sm border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
            @error('contact_number')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Address</label>
            <textarea name="address" rows="2"
                      class="w-full text-sm border border-slate-300 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">{{ old('address', $user->address) }}</textarea>
            @error('address')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full px-5 py-3 bg-slate-800 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl transition-colors">
            Save Changes
        </button>
    </form>
</div>
@endsection
