<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Enrollment Agreement | AITSA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-brandNavy min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-8">
        <h1 class="text-lg font-extrabold text-brandNavy mb-1">Sign Your Enrollment Agreement</h1>
        <p class="text-sm text-brandNavy/60 mb-4">Please read the agreement below, then sign in the box to continue to your reservation payment.</p>

        @if ($errors->has('signature'))
            <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-600 text-xs font-semibold">
                {{ $errors->first('signature') }}
            </div>
        @endif

        <div class="border border-brandNavy/10 rounded-lg p-4 mb-4 max-h-56 overflow-y-auto text-xs text-brandNavy/80 bg-lightBg">
            @include('agreements.enrollment', ['student' => $student])
        </div>

        <form method="POST" action="{{ $submitUrl }}" id="agreement-form">
            @csrf
            <input type="hidden" name="signature" id="signature-input">

            <label class="block text-xs font-bold text-brandNavy/70 mb-1">Sign here</label>
            <canvas id="signature-pad" class="w-full h-40 border-2 border-dashed border-brandNavy/20 rounded-lg touch-none bg-white" width="600" height="200"></canvas>

            <div class="flex items-center justify-between mt-3">
                <button type="button" id="clear-signature" class="text-xs font-semibold text-brandNavy/50 hover:text-brandNavy transition-colors">
                    <i class="fa-solid fa-rotate-left mr-1"></i>Clear
                </button>
                <button type="submit" class="py-2.5 px-6 rounded-xl bg-brandNavy text-white font-bold text-sm hover:opacity-90 transition-opacity">
                    Sign &amp; Continue
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const canvas = document.getElementById('signature-pad');
            const ctx = canvas.getContext('2d');
            ctx.lineWidth = 2;
            ctx.lineCap = 'round';
            ctx.strokeStyle = '#0f172a';

            let drawing = false;
            let hasDrawn = false;

            function pointFromEvent(e) {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                const point = e.touches ? e.touches[0] : e;
                return {
                    x: (point.clientX - rect.left) * scaleX,
                    y: (point.clientY - rect.top) * scaleY,
                };
            }

            function start(e) {
                e.preventDefault();
                drawing = true;
                hasDrawn = true;
                const { x, y } = pointFromEvent(e);
                ctx.beginPath();
                ctx.moveTo(x, y);
            }

            function move(e) {
                if (!drawing) return;
                e.preventDefault();
                const { x, y } = pointFromEvent(e);
                ctx.lineTo(x, y);
                ctx.stroke();
            }

            function stop() {
                drawing = false;
            }

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', stop);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', stop);

            document.getElementById('clear-signature').addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasDrawn = false;
            });

            document.getElementById('agreement-form').addEventListener('submit', function (e) {
                if (!hasDrawn) {
                    e.preventDefault();
                    alert('Please draw your signature first.');
                    return;
                }
                document.getElementById('signature-input').value = canvas.toDataURL('image/png');
            });
        })();
    </script>
</body>
</html>
