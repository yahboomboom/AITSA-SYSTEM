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
        <p class="text-brandGold text-xs font-black uppercase tracking-widest mb-3">Academic Year 2025–2026 Enrollment</p>
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
@if(session('success'))
<div class="bg-brandGreen/10 border-b border-brandGreen/20 px-6 lg:px-16 py-5 flex items-start gap-4">
    <i class="fa-solid fa-circle-check text-brandGreen text-2xl mt-0.5"></i>
    <div>
        <p class="font-bold text-brandGreen">Application Successfully Submitted!</p>
        <p class="text-sm text-brandGreen/80 mt-0.5">{{ session('success') }}</p>
    </div>
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
                    ['id'=>'bk3',  'name'=>'Bookkeeping NC III',       'icon'=>'fa-book-bookmark', 'duration'=>'6 months'],
                    ['id'=>'em3',  'name'=>'Events Management NC III',  'icon'=>'fa-calendar-star', 'duration'=>'6 months'],
                    ['id'=>'fb3',  'name'=>'Food & Beverages NC III',   'icon'=>'fa-utensils',      'duration'=>'6 months'],
                ] as $prog)
                <div class="prog-card bg-white border border-amber-200 rounded-2xl p-5 shadow-sm"
                     data-prog="{{ $prog['id'] }}" data-level="TESDA" data-name="{{ $prog['name'] }}"
                     onclick="selectProgram('{{ $prog['id'] }}', 'TESDA', '{{ $prog['name'] }}')">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center mb-3">
                        <i class="fa-solid {{ $prog['icon'] }} text-amber-600"></i>
                    </div>
                    <h3 class="text-xs font-extrabold text-brandNavy leading-snug">{{ $prog['name'] }}</h3>
                    <p class="text-[10px] text-amber-600 font-semibold mt-1">{{ $prog['duration'] }} · TESDA NC</p>
                    <p class="text-[10px] text-brandNavy/40 mt-2">TESDA-certified vocational qualification recognized nationwide.</p>
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
                <div class="prog-card bg-white border border-blue-200 rounded-2xl p-5 shadow-sm"
                     data-prog="{{ $prog['id'] }}" data-level="ASSOCIATE" data-name="{{ $prog['name'] }}"
                     onclick="selectProgram('{{ $prog['id'] }}', 'ASSOCIATE', '{{ $prog['name'] }}')">
                    <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center mb-3">
                        <i class="fa-solid {{ $prog['icon'] }} text-blue-600"></i>
                    </div>
                    <span class="inline-block text-[9px] font-black text-blue-600 bg-blue-500/10 px-2 py-0.5 rounded-full mb-2">{{ $prog['abbr'] }}</span>
                    <h3 class="text-xs font-extrabold text-brandNavy leading-snug">{{ $prog['name'] }}</h3>
                    <p class="text-[10px] text-blue-600 font-semibold mt-1">2 years · Associate Degree</p>
                    <p class="text-[10px] text-brandNavy/40 mt-2">CHED-recognized 2-year college associate program.</p>
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
                <div class="prog-card bg-white border border-brandNavy/15 rounded-2xl p-5 shadow-sm"
                     data-prog="{{ $prog['id'] }}" data-level="BACHELOR" data-name="{{ $prog['name'] }}"
                     onclick="selectProgram('{{ $prog['id'] }}', 'BACHELOR', '{{ $prog['name'] }}')">
                    <div class="w-10 h-10 rounded-xl bg-brandNavy/8 flex items-center justify-center mb-3">
                        <i class="fa-solid {{ $prog['icon'] }} text-brandNavy"></i>
                    </div>
                    <span class="inline-block text-[9px] font-black text-brandNavy bg-brandNavy/8 px-2 py-0.5 rounded-full mb-2">{{ $prog['abbr'] }}</span>
                    <h3 class="text-xs font-extrabold text-brandNavy leading-snug">{{ $prog['name'] }}</h3>
                    <p class="text-[10px] text-brandNavy/60 font-semibold mt-1">4 years · Bachelor's Degree</p>
                    <p class="text-[10px] text-brandNavy/40 mt-2">{{ $prog['desc'] }}</p>
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

        <form action="{{ route('apply.store') }}" method="POST" class="bg-white border border-brandNavy/10 rounded-2xl shadow-sm overflow-hidden">
            @csrf
            <input type="hidden" name="program_key" id="programKeyInput">
            <input type="hidden" name="program_level" id="programLevelInput">
            <input type="hidden" name="program_name" id="programNameInput">

            <div class="p-6 lg:p-8 space-y-6">

                {{-- Personal Info --}}
                <div>
                    <h3 class="text-xs font-black text-brandNavy uppercase tracking-wider mb-4 pb-2 border-b border-brandNavy/8">Personal Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Last Name, First Name Middle Name"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email') }}" required placeholder="your@email.com"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Contact Number <span class="text-red-500">*</span></label>
                            <input type="text" name="contact_number" value="{{ old('contact_number') }}" required placeholder="09XX-XXX-XXXX"
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
                            <input type="text" name="address" value="{{ old('address') }}" required placeholder="Street, Barangay, City/Municipality, Province"
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
                            <input type="text" name="last_school" value="{{ old('last_school') }}" required placeholder="e.g. Cabuyao National High School"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Year Graduated / Last Attended <span class="text-red-500">*</span></label>
                            <input type="text" name="year_graduated" value="{{ old('year_graduated') }}" required placeholder="e.g. 2024"
                                class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy placeholder-brandNavy/30 focus:outline-none focus:border-brandGreen transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Applicant Type <span class="text-red-500">*</span></label>
                            <select name="applicant_type" required class="w-full border border-brandNavy/15 rounded-xl px-4 py-3 text-sm text-brandNavy focus:outline-none focus:border-brandGreen transition-colors">
                                <option value="" disabled selected>Select type</option>
                                <option value="NEW">New Student</option>
                                <option value="TRANSFEREE">Transferee</option>
                                <option value="RETURNEE">Returnee</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-[10px] font-bold text-brandNavy/60 uppercase tracking-wider mb-1.5">Additional Remarks <span class="text-brandNavy/30 font-normal normal-case">(Optional)</span></label>
                    <textarea name="remarks" rows="3" placeholder="Any additional information you'd like to share with our admissions team…"
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
                <button type="submit"
                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-8 py-3.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-black rounded-xl transition-all shadow-md hover:-translate-y-0.5 active:translate-y-0">
                    <i class="fa-solid fa-paper-plane"></i>Submit Application
                </button>
            </div>
        </form>
    </div>

</div>

{{-- Footer --}}
<footer class="border-t border-brandNavy/10 mt-10 py-8 px-6 text-center text-[10px] text-brandNavy/40">
    <p class="font-bold">Asian Institute of Technology, Science and Arts</p>
    <p>3F Don Onofre St., Brgy. Sala, City of Cabuyao, Laguna · A.Y. 2025–2026</p>
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
</body>
</html>
