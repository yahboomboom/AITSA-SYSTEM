@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto py-10">
	<a href="{{ $homeUrl }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white mb-4 transition-colors">
        <i class="fa-solid fa-arrow-left"></i> Back to Home
    </a>
    <h1 class="text-lg font-bold text-slate-800 dark:text-white mb-4">My Signature</h1>

    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 text-xs border border-green-200 dark:border-green-500/20">
            {{ session('success') }}
        </div>
    @endif

    @if ($user->signature_path)
        <div class="mb-4 p-4 border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800">
            <p class="text-[10px] uppercase text-slate-400 mb-2 font-bold tracking-wider">Current Signature</p>
            <img src="{{ Storage::url($user->signature_path) }}" alt="Signature" class="h-16">
        </div>
    @endif

    <form action="{{ route('signature.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <label class="block cursor-pointer">
            <div class="w-full text-xs border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl p-6 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-center transition-colors">
                <p class="font-semibold text-slate-600 dark:text-slate-300" id="fileLabel">Click to upload signature image</p>
                <p class="text-slate-400 mt-1">PNG or JPG, max 1MB</p>
            </div>
            <input type="file" name="signature" accept="image/png,image/jpeg" required
                   class="hidden"
                   onchange="document.getElementById('fileLabel').textContent = this.files[0] ? this.files[0].name : 'Click to upload signature image'">
        </label>
        @error('signature')
            <p class="text-red-500 text-xs">{{ $message }}</p>
        @enderror
        <button type="submit" class="w-full px-5 py-3 bg-slate-800 dark:bg-slate-700 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl transition-colors">
            Save Signature
        </button>
    </form>
</div>
@endsection