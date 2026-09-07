{{-- partials/theme-fonts.blade.php --}}
{{-- Loads the UI-redesign typefaces (Public Sans for headings/labels, DM Sans for body/UI text).
     Included in every in-scope internal page's <head>, alongside partials.theme-init.
     The four auth pages (login/forgot-password/reset-password/apply) are out of scope and load
     their own separate font set — do not include this partial there. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@500;600;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
