import React, { useEffect, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';

function parseContext(raw) {
    try {
        return JSON.parse(raw ?? '{}');
    } catch {
        return {};
    }
}

function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

function SignatureApp({ context }) {
    const { signaturePath, hasSignature, updateUrl, csrfToken, errors = {} } = context;
    const canvasRef = useRef(null);
    const inputRef = useRef(null);
    const drawingRef = useRef(false);
    const hasDrawnRef = useRef(false);

    useEffect(() => {
        const canvas = canvasRef.current;
        const ctx = canvas.getContext('2d');
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#0f172a';

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
            drawingRef.current = true;
            hasDrawnRef.current = true;
            const { x, y } = pointFromEvent(e);
            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        function move(e) {
            if (!drawingRef.current) return;
            e.preventDefault();
            const { x, y } = pointFromEvent(e);
            ctx.lineTo(x, y);
            ctx.stroke();
        }

        function stop() {
            drawingRef.current = false;
        }

        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', stop);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', stop);

        return () => {
            canvas.removeEventListener('mousedown', start);
            canvas.removeEventListener('mousemove', move);
            window.removeEventListener('mouseup', stop);
            canvas.removeEventListener('touchstart', start);
            canvas.removeEventListener('touchmove', move);
            canvas.removeEventListener('touchend', stop);
        };
    }, []);

    const clearCanvas = () => {
        const canvas = canvasRef.current;
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        hasDrawnRef.current = false;
    };

    const handleSubmit = (e) => {
        if (!hasDrawnRef.current) {
            e.preventDefault();
            alert('Please draw your signature first.');
            return;
        }
        inputRef.current.value = canvasRef.current.toDataURL('image/png');
        lockSubmit(e.currentTarget, 'Saving…');
    };

    return (
        <div className="space-y-5">
            {hasSignature ? (
                <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
                    <p className="text-[10px] uppercase text-brandNavy/40 dark:text-slate-500 mb-2 font-bold tracking-wider">
                        Current Signature
                    </p>
                    <img src={signaturePath} alt="Signature" className="h-16 bg-white rounded p-1 border border-brandNavy/10" />
                    <p className="text-[11px] text-brandNavy/50 dark:text-slate-400 mt-3">
                        You already have a signature saved. Drawing a new one below will replace it.
                    </p>
                </div>
            ) : (
                <div className="p-3.5 rounded-lg bg-brandGold/10 border border-brandGold/25 text-brandGold text-xs font-semibold">
                    <i className="fa-solid fa-triangle-exclamation mr-2" />
                    You haven't set up a signature yet. Please draw one below.
                </div>
            )}

            <form
                action={updateUrl}
                method="POST"
                className="space-y-4 bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6"
                onSubmit={handleSubmit}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <input type="hidden" name="signature" ref={inputRef} />

                <label className="block text-xs font-bold text-brandNavy/70 dark:text-slate-300 mb-1">Sign here</label>
                <canvas
                    ref={canvasRef}
                    width={600}
                    height={200}
                    className="w-full h-40 border-2 border-dashed border-brandNavy/20 dark:border-slate-700 rounded-lg touch-none bg-white"
                />
                {errors?.signature?.length ? <p className="text-red-500 text-xs">{errors.signature[0]}</p> : null}

                <div className="flex items-center justify-between">
                    <button type="button" onClick={clearCanvas} className="text-xs font-semibold text-brandNavy/50 dark:text-slate-400 hover:text-brandNavy dark:hover:text-white transition-colors">
                        <i className="fa-solid fa-rotate-left mr-1" />Clear
                    </button>
                    <button
                        type="submit"
                        className="px-5 py-3 bg-brandNavy hover:bg-brandGreen text-white text-xs font-bold rounded-xl transition-colors"
                    >
                        {hasSignature ? 'Save New Signature' : 'Save Signature'}
                    </button>
                </div>
            </form>
        </div>
    );
}

const el = document.getElementById('signature-root');
if (el) {
    const context = parseContext(el.dataset.context);
    createRoot(el).render(
        <ErrorBoundary>
            <SignatureApp context={context} />
        </ErrorBoundary>
    );
}
