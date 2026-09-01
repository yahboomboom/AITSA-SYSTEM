@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto py-10">
    <a href="{{ $homeUrl }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800 mb-4 transition-colors">
        <i class="fa-solid fa-arrow-left"></i> Back to Home
    </a>
    <h1 class="text-lg font-bold text-slate-800 mb-4">My Signature</h1>
    {{-- Display a success message if the signature was successfully updated --}}
    @if (session('success'))
        <div class="mb-4 p-3 rounded bg-green-50 text-green-700 text-xs border border-green-200">
            {{ session('success') }}
        </div>
    @endif
        {{-- Display the current signature if it exists, or a message prompting the user to upload one if it doesn't --}}
    @if ($user->signature_path)
        <div class="mb-4 p-4 border border-slate-200 rounded-xl bg-white">
            <p class="text-[10px] uppercase text-slate-400 mb-2 font-bold tracking-wider">Current Signature</p>
            <img src="{{ Storage::url($user->signature_path) }}" alt="Signature" class="h-16">
            <p class="text-[11px] text-slate-500 mt-3">You already have a signature saved. Uploading a new one below will replace it.</p>
        </div>
    @else
        <div class="mb-4 p-3 rounded bg-amber-50 text-amber-700 text-xs border border-amber-200">
            You haven't uploaded a signature yet. Please upload one below.
        </div>
    @endif
        {{-- Form to upload or re-upload the user's signature image, with a file input and a submit button. The form uses POST method and multipart/form-data encoding for file upload. --}}
    <form action="{{ route('signature.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <label class="block cursor-pointer">
            <div class="w-full text-xs border-2 border-dashed border-slate-300 rounded-xl p-6 bg-white hover:bg-slate-50 text-center transition-colors">
                <p class="font-semibold text-slate-600" id="fileLabel">
                    {{ $user->signature_path ? 'Click to re-upload a new signature image' : 'Click to upload signature image' }}
                </p>
                <p class="text-slate-400 mt-1">PNG or JPG, max 1MB</p>
            </div>
            <input type="file" name="signature" accept="image/png,image/jpeg" required
                   class="hidden"
                   onchange="document.getElementById('fileLabel').textContent = this.files[0] ? this.files[0].name : '{{ $user->signature_path ? 'Click to re-upload a new signature image' : 'Click to upload signature image' }}'">
        </label>
        @error('signature')
            <p class="text-red-500 text-xs">{{ $message }}</p>
        @enderror
        <button type="submit" class="w-full px-5 py-3 bg-slate-800 hover:bg-emerald-600 text-white text-xs font-bold rounded-xl transition-colors">
            {{ $user->signature_path ? 'Re-upload Signature' : 'Save Signature' }}
        </button>
    </form>
</div>
@endsection