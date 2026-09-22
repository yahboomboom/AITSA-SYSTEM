<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Now | Asian Institute of Technology, Science and Arts</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme-init')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-lightBg text-brandNavy font-sans antialiased">

{{-- ═══ HEADER ═══ --}}
<header class="bg-white border-b border-brandNavy/10 px-6 lg:px-16 h-16 flex items-center justify-between sticky top-0 z-30 shadow-sm">
    <div class="flex items-center gap-3">
        <img src="{{ asset('assets/bg_aitsa.jpg') }}" alt="AITSA" class="w-8 h-8 rounded-lg object-cover">
        <div>
            <p class="text-xs font-black text-brandNavy leading-none">AITSA</p>
            <p class="text-[9px] text-brandNavy/50 leading-none">Asian Institute of Technology, Science and Arts</p>
        </div>
    </div>
    <a href="{{ route('login') }}" class="text-xs font-bold text-brandNavy/60 hover:text-brandNavy transition-colors flex items-center gap-1.5">
        <i class="fa-solid fa-right-to-bracket text-xs"></i>Already enrolled? Sign In
    </a>
</header>

{{-- ═══ HERO ═══ --}}
<section class="relative text-white px-6 lg:px-16 py-14 lg:py-20 overflow-hidden"
    style="background-image: url('{{ asset('assets/aitsa_banner.jpg') }}'); background-size: 130%; background-position: left center;">
    {{-- gradient: near-opaque on the left where text lives, fades right --}}
    <div class="absolute inset-0" style="background: linear-gradient(to right, rgba(11,60,93,0.92) 0%, rgba(11,60,93,0.80) 50%, rgba(11,60,93,0.45) 100%);"></div>
    <div class="relative max-w-5xl mx-auto">
        <p class="text-brandGold text-xs font-black uppercase tracking-widest mb-3">Academic Year {{ $schoolYear }} Enrollment</p>
        <h1 class="text-3xl lg:text-5xl font-black leading-tight mb-4" style="text-shadow:0 2px 16px rgba(0,0,0,0.45);">Start Your Journey<br>at <span class="text-brandGold">AITSA</span></h1>
        <p class="text-white/85 text-sm max-w-xl leading-relaxed">
            Apply for admission to any of our TESDA, Associate, or Bachelor programs.
            Once your application is reviewed, our admin team will send your login credentials directly to your email.
        </p>
        <div class="flex flex-wrap gap-4 mt-6 text-xs font-semibold text-white/85">
            <span class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-brandGold"></i>TESDA Accredited Programs</span>
            <span class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-brandGold"></i>CHED Recognized Degrees</span>
            <span class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-brandGold"></i>Brgy. Sala, Cabuyao, Laguna</span>
        </div>
    </div>
</section>


{{-- ═══ SUCCESS BANNER ═══ --}}
@if(session('success') && !session('receipt'))
<div class="bg-brandGreen/10 border-b border-brandGreen/20 px-6 lg:px-16 py-5 flex items-start gap-4">
    <i class="fa-solid fa-circle-check text-brandGreen text-2xl mt-0.5"></i>
    <div>
        <p class="font-bold text-brandGreen">Application Successfully Submitted!</p>
        <p class="text-sm text-brandGreen/80 mt-0.5">{{ session('success') }}</p>
    </div>
</div>
@endif

{{-- NEW: SESSION ERROR BANNER (e.g. reservation payment failed/cancelled) --}}
@if(session('error'))
<div class="bg-red-500/10 border-b border-red-500/20 px-6 lg:px-16 py-4 flex items-start gap-4">
    <i class="fa-solid fa-triangle-exclamation text-red-600 mt-0.5"></i>
    <p class="text-sm text-red-600 font-semibold">{{ session('error') }}</p>
</div>
@endif

{{-- ═══ ERROR BANNER ═══ --}}
@if ($errors->any())
<div class="bg-red-500/10 border-b border-red-500/20 px-6 lg:px-16 py-4 text-xs text-red-600 space-y-1 font-semibold">
    <p class="font-bold flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i>Please fix the following before submitting:</p>
    <ul class="list-disc pl-6 space-y-0.5">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="max-w-5xl mx-auto px-6 lg:px-0 py-12 space-y-12">

    {{-- ═══ STEP 1: PROGRAM SELECTION ═══ --}}
    <div>
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-full bg-brandNavy text-white flex items-center justify-center text-xs font-black">1</div>
            <div>
                <h2 class="text-lg font-extrabold text-brandNavy">Choose Your Program</h2>
                <p class="text-xs text-brandNavy/50">Select the program you want to apply for — click a card to continue.</p>
            </div>
        </div>

        {{-- TESDA --}}
        <div class="mb-6">
            <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest mb-3 flex items-center gap-2">
                <span class="inline-block w-5 h-px bg-amber-500"></span>TESDA Short-Term Programs
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach([
                    ['id'=>'bk3',  'name'=>'Bookkeeping NC III',       'icon'=>'fa-book-bookmark', 'duration'=>'292 training hours'],
                    ['id'=>'em3',  'name'=>'Events Management NC III',  'icon'=>'fa-calendar-days', 'duration'=>'108 training hours'],
                    ['id'=>'fb3',  'name'=>'Food & Beverages NC III',   'icon'=>'fa-utensils',      'duration'=>'230 training hours'],
                ] as $prog)
                @php $slot = $slots[$prog['id']] ?? null; @endphp
                <div class="prog-card bg-white border border-amber-200 rounded-2xl p-5 shadow-sm {{ $slot && $slot['isFull'] ? 'opacity-50 cursor-not-allowed' : '' }}"
                     data-prog="{{ $prog['id'] }}" data-level="TESDA" data-name="{{ $prog['name'] }}"
                     @if(!$slot || !$slot['isFull']) onclick="selectProgram('{{ $prog['id'] }}', 'TESDA', '{{ $prog['name'] }}')" @endif>
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center mb-3">
                        <i class="fa-solid {{ $prog['icon'] }} text-amber-600"></i>
                    </div>
                    <h3 class="text-xs font-extrabold text-brandNavy leading-snug">{{ $prog['name'] }}</h3>
                    <p class="text-[10px] text-amber-600 font-semibold mt-1">{{ $prog['duration'] }} · TESDA NC</p>
                    <p class="text-[10px] text-brandNavy/40 mt-2">TESDA-certified vocational qualification recognized nationwide.</p>
                    @if($slot)
                        <p class="text-[10px] font-bold mt-2 {{ $slot['isFull'] ? 'text-red-500' : 'text-brandNavy/50' }}">
                            @if($slot['isFull']) <i class="fa-solid fa-circle-xmark"></i> Slots full
                            @else <i class="fa-solid fa-users"></i> {{ $slot['slotsLeft'] }} / {{ $slot['totalSlots'] }} slots left
                            @endif
                        </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- ASSOCIATE --}}
        <div class="mb-6">
            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-2">
                <span class="inline-block w-5 h-px bg-blue-500"></span>Associate Courses
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    ['id'=>'bom', 'name'=>'Business Office Management',  'icon'=>'fa-briefcase',  'abbr'=>'BoM'],
                    ['id'=>'fsm', 'name'=>'Food Service Management',      'icon'=>'fa-bowl-food',  'abbr'=>'FSM'],
                ] as $prog)
                @php $slot = $slots[$prog['id']] ?? null; @endphp
                <div class="prog-card bg-white border border-blue-200 rounded-2xl p-5 shadow-sm {{ $slot && $slot['isFull'] ? 'opacity-50 cursor-not-allowed' : '' }}"
                     data-prog="{{ $prog['id'] }}" data-level="ASSOCIATE" data-name="{{ $prog['name'] }}"
                     @if(!$slot || !$slot['isFull']) onclick="selectProgram('{{ $prog['id'] }}', 'ASSOCIATE', '{{ $prog['name'] }}')" @endif>
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center mb-3">
                       <i class="fa-solid {{ $prog['icon'] }} text-blue-600"></i>
                    </div>
                    <span class="inline-block text-[9px] font-black text-blue-600 bg-blue-500/10 px-2 py-0.5 rounded-full mb-2">{{ $prog['abbr'] }}</span>
                    <h3 class="text-xs font-extrabold text-brandNavy leading-snug">{{ $prog['name'] }}</h3>
                    <p class="text-[10px] text-blue-600 font-semibold mt-1">2 years · Associate Degree</p>
                    <p class="text-[10px] text-brandNavy/40 mt-2">CHED-recognized 2-year college associate program.</p>
                    @if($slot)
                        <p class="text-[10px] font-bold mt-2 {{ $slot['isFull'] ? 'text-red-500' : 'text-brandNavy/50' }}">
                            @if($slot['isFull']) <i class="fa-solid fa-circle-xmark"></i> Slots full
                            @else <i class="fa-solid fa-users"></i> {{ $slot['slotsLeft'] }} / {{ $slot['totalSlots'] }} slots left
                            @endif
                        </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- BACHELOR --}}
        <div>
            <p class="text-[10px] font-black text-brandNavy uppercase tracking-widest mb-3 flex items-center gap-2">
                <span class="inline-block w-5 h-px bg-brandNavy/40"></span>Bachelor's Degree Programs
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach([
                    ['id'=>'bsoa',   'name'=>'Bachelor in Science Office Administration',          'icon'=>'fa-landmark-flag',  'abbr'=>'BSOA',   'desc'=>'Administrative management and business operations.'],
                    ['id'=>'btvted', 'name'=>'Bachelor in Technical Vocational Teacher Education', 'icon'=>'fa-chalkboard-user','abbr'=>'BTVTED', 'desc'=>'Prepare graduates to teach technical-vocational subjects.'],
                ] as $prog)
                @php $slot = $slots[$prog['id']] ?? null; @endphp
                <div class="prog-card bg-white border border-brandNavy/15 rounded-2xl p-5 shadow-sm {{ $slot && $slot['isFull'] ? 'opacity-50 cursor-not-allowed' : '' }}"
                     data-prog="{{ $prog['id'] }}" data-level="BACHELOR" data-name="{{ $prog['name'] }}"
                     @if(!$slot || !$slot['isFull']) onclick="selectProgram('{{ $prog['id'] }}', 'BACHELOR', '{{ $prog['name'] }}')" @endif>
                    <div class="w-10 h-10 rounded-xl bg-brandNavy/8 flex items-center justify-center mb-3">
                        <i class="fa-solid {{ $prog['icon'] }} text-brandNavy"></i>
                    </div>
                    <span class="inline-block text-[9px] font-black text-brandNavy bg-brandNavy/8 px-2 py-0.5 rounded-full mb-2">{{ $prog['abbr'] }}</span>
                    <h3 class="text-xs font-extrabold text-brandNavy leading-snug">{{ $prog['name'] }}</h3>
                    <p class="text-[10px] text-brandNavy/60 font-semibold mt-1">4 years · Bachelor's Degree</p>
                    <p class="text-[10px] text-brandNavy/40 mt-2">{{ $prog['desc'] }}</p>
                    @if($slot)
                        <p class="text-[10px] font-bold mt-2 {{ $slot['isFull'] ? 'text-red-500' : 'text-brandNavy/50' }}">
                        @if($slot['isFull']) <i class="fa-solid fa-circle-xmark"></i> Slots full
                        @else <i class="fa-solid fa-users"></i> {{ $slot['slotsLeft'] }} / {{ $slot['totalSlots'] }} slots left
                        @endif
                        </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══ STEP 2: APPLICATION FORM ═══ --}}
    <div id="formSection" class="form-slide hidden-anim">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-full bg-brandGreen text-white flex items-center justify-center text-xs font-black">2</div>
            <div>
                <h2 class="text-lg font-extrabold text-brandNavy">Your Information</h2>
                <p class="text-xs text-brandNavy/50">Fill in your personal details to complete your application.</p>
            </div>
        </div>

        {{-- Selected program display --}}
        <div id="selectedProgBanner" class="mb-6 flex items-center gap-4 p-4 bg-brandGreen/5 border border-brandGreen/20 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-brandGreen/10 flex items-center justify-center flex-shrink-0">
            </div>
            <div>
                <p class="text-[10px] font-bold text-brandGreen uppercase tracking-wider">Selected Program</p>
                <p id="selectedProgName" class="text-sm font-extrabold text-brandNavy"></p>
            </div>
            <button type="button" onclick="clearProgram()" class="ml-auto text-[10px] font-bold text-brandNavy/40 hover:text-red-500 transition-colors flex items-center gap-1">
                <i class="fa-solid fa-xmark"></i>Change
            </button>
        </div>

        <form id="applicationForm" action="{{ route('apply.store') }}" method="POST" class="bg-white border border-brandNavy/10 rounded-2xl shadow-sm overflow-hidden">
            @csrf
            <input type="hidden" name="program_key" id="programKeyInput">
            <input type="hidden" name="program_level" id="programLevelInput">
            <input type="hidden" name="program_name" id="programNameInput">

            <div class="p-6 lg:p-8 space-y-6">

                {{-- Personal Info --}}
                <div>
                    <h3 class="text-xs font-black text-brandNavy uppercase tracking-wider mb-4 pb-2 border-b border-brandNavy/8">Personal Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Last Name <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required placeholder="Dela Cruz" onpaste="return false;"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">First Name <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required placeholder="Juan" onpaste="return false;"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Middle Name <span class="text-brandNavy/30 font-normal normal-case">(Optional)</span></label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}" placeholder="Santos" onpaste="return false;"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="your@email.com" onpaste="return false;"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" name="contact_number" value="{{ old('contact_number') }}" required placeholder="09XXXXXXXXX"
                                onpaste="return false;" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Sex <span class="text-red-500">*</span></label>
                            <select name="sex" required class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy focus:outline-none focus:border-brandGreen transition-colors">
                                <option value="" disabled selected>Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Home Address <span class="text-red-500">*</span></label>
                            <input type="text" name="address" value="{{ old('address') }}" required placeholder="Street, Barangay, City/Municipality, Province" onpaste="return false;"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                    </div>
                </div>

                {{-- Academic Background --}}
                <div>
                    <h3 class="text-xs font-black text-brandNavy uppercase tracking-wider mb-4 pb-2 border-b border-brandNavy/8">Academic Background</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Last School Attended <span class="text-red-500">*</span></label>
                            <input type="text" name="last_school" value="{{ old('last_school') }}" required placeholder="e.g. Cabuyao National High School" onpaste="return false;"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Year Graduated / Last Attended <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" name="year_graduated" value="{{ old('year_graduated') }}" required placeholder="e.g. 2024"
                                onpaste="return false;" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Applicant Type <span class="text-red-500">*</span></label>
                            <select name="applicant_type" id="applicantTypeInput" required onchange="toggleYearLevelField()" class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy focus:outline-none focus:border-brandGreen transition-colors">
                                <option value="" disabled {{ old('applicant_type') ? '' : 'selected' }}>Select type</option>
                                <option value="NEW" {{ old('applicant_type') === 'NEW' ? 'selected' : '' }}>New Student</option>
                                <option value="TRANSFEREE" {{ old('applicant_type') === 'TRANSFEREE' ? 'selected' : '' }}>Transferee</option>
                                <option value="RETURNEE" {{ old('applicant_type') === 'RETURNEE' ? 'selected' : '' }}>Returnee</option>
                            </select>
                        </div>
                        <div id="yearLevelField" class="hidden">
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Year Level You're Applying For <span class="text-red-500">*</span></label>
                            <select name="year_level" class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy focus:outline-none focus:border-brandGreen transition-colors">
                                <option value="" disabled {{ old('year_level') ? '' : 'selected' }}>Select year level</option>
                                @foreach(['1st Year', '2nd Year', '3rd Year', '4th Year'] as $yl)
                                    <option value="{{ $yl }}" {{ old('year_level') === $yl ? 'selected' : '' }}>{{ $yl }}</option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-brandNavy/40 mt-1.5">Used to place you at the right standing while your prior credentials are evaluated.</p>
                        </div>
                    </div>
                </div>
                {{-- Slot Reservation --}}
                    <div class="p-4 bg-brandGreen/5 border border-brandGreen/20 rounded-xl">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="wants_reservation" value="1" {{ old('wants_reservation') ? 'checked' : '' }}
                                class="mt-1 w-4 h-4 rounded border-brandNavy/30 text-brandGreen focus:ring-brandGreen">
                            <span>
                                <span class="block text-sm font-bold text-brandNavy">I would like to reserve my slot (₱{{ number_format($reservationFee) }} reservation fee)</span>
                                <span class="block text-xs text-brandNavy/60 mt-0.5">Optional. You'll be sent straight to our secure online payment page after you submit this form to pay the ₱{{ number_format($reservationFee) }} — this guarantees your spot in your chosen program before regular enrollment opens.</span>
                            </span>
                        </label>
                    </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Additional Remarks <span class="text-brandNavy/30 font-normal normal-case">(Optional)</span></label>
                    <textarea name="remarks" rows="3" placeholder="Any additional information you'd like to share with our admissions team…" onpaste="return false;"
                        class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors resize-none">{{ old('remarks') }}</textarea>
                </div>

                {{-- Notice --}}
                <div class="flex items-start gap-3 p-4 bg-brandGold/5 border border-brandGold/20 rounded-xl">
                    <i class="fa-solid fa-circle-info text-brandGold mt-0.5 flex-shrink-0"></i>
                    <p class="text-xs text-brandNavy/70 leading-relaxed">
                        After your application is reviewed and approved, our admin team will create your student account and send your
                        <strong>login credentials to the email address you provided</strong>. Please use a valid, accessible email.
                    </p>
                </div>
            </div>

            <div class="px-6 lg:px-8 py-5 border-t border-brandNavy/8 bg-lightBg flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-[10px] text-brandNavy/40">Fields marked <span class="text-red-500">*</span> are required.</p>
                {{-- type="button" (not "submit") — opens the React review modal (apply-app.jsx) via the
                     window.openApplicationReview() bridge it exposes. The form is only actually submitted
                     from inside that modal, after the applicant confirms.
                     Fallback: if the React island failed to load for any reason (build not run yet,
                     JS error, etc.), submit the form directly instead of silently doing nothing. --}}
                <button type="button" onclick="window.openApplicationReview ? window.openApplicationReview() : document.getElementById('applicationForm').requestSubmit()"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-3.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-black rounded-xl transition-all shadow-md hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fa-solid fa-paper-plane"></i>Submit Application
                </button>
            </div>
        </form>
    </div>

</div>

{{-- ═══ REVIEW & CONFIRM MODAL — React island (resources/js/apply-app.jsx) ═══
     Reads the plain <form id="applicationForm"> via the DOM (FormData), shows a
     summary, and only calls form.requestSubmit() once the applicant confirms. --}}
@viteReactRefresh
@vite('resources/js/apply-app.jsx')
<div id="apply-review-root" data-reservation-fee="{{ $reservationFee }}"></div>

{{-- Footer --}}
<footer class="border-t border-brandNavy/10 mt-10 py-8 px-6 text-center text-[10px] text-brandNavy/40">
    <p class="font-bold">Asian Institute of Technology, Science and Arts</p>
    <p>3F Don Onofre St., Brgy. Sala, City of Cabuyao, Laguna · A.Y. {{ $schoolYear }}</p>
</footer>

<script>
let selectedProg = null;

function selectProgram(id, level, name) {
    selectedProg = { id, level, name };

    // Highlight selected card
    document.querySelectorAll('.prog-card').forEach(card => {
        card.classList.remove('selected');
        if (card.dataset.prog === id) card.classList.add('selected');
    });

    // Fill hidden inputs
    document.getElementById('programKeyInput').value   = id;
    document.getElementById('programLevelInput').value = level;
    document.getElementById('programNameInput').value  = name;

    // Update banner
    document.getElementById('selectedProgName').textContent = name;

    // Show form
    const section = document.getElementById('formSection');
    section.classList.remove('hidden-anim');
    section.classList.add('visible-anim');

    // Scroll to form
    setTimeout(() => section.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
}

function toggleYearLevelField() {
    const type = document.getElementById('applicantTypeInput').value;
    document.getElementById('yearLevelField').classList.toggle('hidden', !['TRANSFEREE', 'RETURNEE'].includes(type));
}
toggleYearLevelField();

function clearProgram() {
    selectedProg = null;
    document.querySelectorAll('.prog-card').forEach(c => c.classList.remove('selected'));
    const section = document.getElementById('formSection');
    section.classList.add('hidden-anim');
    section.classList.remove('visible-anim');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Re-select if validation failed (old input)
@if(old('program_key'))
selectProgram('{{ old('program_key') }}', '{{ old('program_level') }}', '{{ old('program_name') }}');
@endif
</script>

{{-- ═══ RECEIPT / SUCCESSFUL PAYMENT POPUP ═══
     Shown right after the applicant returns from a successfully paid
     reservation fee — doubles as their proof of payment (statement of
     account) and the "application submitted" confirmation. --}}
@if(session('receipt'))
@php $receipt = session('receipt'); @endphp
<div id="receiptModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60">
    <div class="receipt-modal-enter bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">

        {{-- Success header --}}
        <div class="bg-gradient-to-r from-brandGreen to-emerald-500 px-6 pt-4 pb-5 text-center relative">
            <div class="w-11 h-11 rounded-full bg-white flex items-center justify-center mx-auto text-xl text-brandGreen shadow-lg">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="px-6 pb-6 -mt-1">
            <div class="bg-white rounded-xl pt-3 text-center mb-5">
                <h3 class="text-lg font-extrabold text-brandNavy mb-1">Application Successfully Submitted!</h3>
                <p class="text-xs text-brandNavy/60">Your reservation fee payment was successful and your slot is now reserved.</p>
            </div>

            {{-- Receipt / Statement of Account --}}
            <div class="border border-dashed border-brandNavy/20 rounded-xl p-4 mb-5" id="printableReceipt">
                <div class="flex items-center justify-between mb-3 pb-3 border-b border-brandNavy/10">
                    <div>
                        <p class="text-[10px] font-black text-brandNavy uppercase tracking-widest">Official Receipt</p>
                        <p class="text-[9px] text-brandNavy/40">AITSA · Slot Reservation Fee</p>
                    </div>
                    <span class="text-[9px] font-black text-brandGreen bg-brandGreen/10 px-2 py-1 rounded-full">PAID</span>
                </div>

                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between"><dt class="text-brandNavy/50">Reference No.</dt><dd class="font-bold text-brandNavy font-mono">{{ $receipt['reference_no'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-brandNavy/50">Applicant</dt><dd class="font-bold text-brandNavy text-right">{{ $receipt['applicant_name'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-brandNavy/50">Program</dt><dd class="font-bold text-brandNavy text-right">{{ $receipt['program_name'] ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-brandNavy/50">Date Paid</dt><dd class="font-bold text-brandNavy text-right">{{ $receipt['paid_at'] ?? '—' }}</dd></div>
                    <div class="flex justify-between pt-2 border-t border-brandNavy/10"><dt class="text-brandNavy/70 font-bold">Amount Paid</dt><dd class="font-black text-brandGreen text-sm">₱{{ number_format((float) ($receipt['amount'] ?? 0), 2) }}</dd></div>
                </dl>
            </div>

            @if($receipt['email_sent'] ?? true)
            <div class="bg-brandGold/10 border border-brandGold/20 rounded-xl p-3 mb-5 flex gap-2.5">
                <i class="fa-solid fa-envelope-open-text text-brandGold mt-0.5 text-sm"></i>
                <p class="text-[11px] text-brandNavy/70 leading-relaxed">
                    Your student account is ready! Your Student ID and password were sent to
                    <span class="font-bold">{{ $receipt['email'] ?? 'your email' }}</span>. Can't find it? Check your spam/junk folder.
                </p>
            </div>
            @else
            <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-5 flex gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-red-500 mt-0.5 text-sm"></i>
                <p class="text-[11px] text-brandNavy/70 leading-relaxed">
                    Your student account is ready, but we couldn't email your credentials to
                    <span class="font-bold">{{ $receipt['email'] ?? 'your email' }}</span>. Your Student ID is
                    <span class="font-bold font-mono">{{ $receipt['login_id'] ?? '—' }}</span> — use
                    <a href="{{ route('password.request') }}" class="font-bold underline">Forgot Password</a> on the login page to set your password.
                </p>
            </div>
            @endif

            <div class="flex gap-2">
                <button onclick="window.print()" class="flex-1 py-2.5 rounded-xl border border-brandNavy/15 text-brandNavy font-bold text-xs hover:bg-brandNavy/5 transition-colors">
                    <i class="fa-solid fa-print mr-1.5"></i>Print Receipt
                </button>
                <a href="{{ route('login') }}" class="flex-1 py-2.5 rounded-xl bg-brandNavy text-white font-bold text-xs text-center hover:opacity-90 transition-opacity">
                    Go to Login
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .receipt-modal-enter { animation: receiptModalIn 0.3s cubic-bezier(0.16,1,0.3,1) forwards; }
    @keyframes receiptModalIn {
        from { opacity: 0; transform: scale(0.95) translateY(12px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    @media print {
        body > *:not(#receiptModal) { display: none !important; }
        #receiptModal { position: static !important; background: none !important; }
        #receiptModal > div { box-shadow: none !important; }
    }
</style>
@endif

</body>
</html>
