const TONE_CLASSES = {
    done: 'border-brandGreen text-brandGreen',
    hold: 'border-red-500 text-red-500',
    pending: 'border-brandNavy/20 dark:border-slate-600 text-transparent',
};

export default function DocCheck({ tone = 'pending' }) {
    return (
        <span className={`ui-doc-check ${TONE_CLASSES[tone] ?? TONE_CLASSES.pending}`}>
            {tone === 'done' && (
                <svg viewBox="0 0 16 16" className="w-2.5 h-2.5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M3 8.5 6.5 12 13 4" />
                </svg>
            )}
            {tone === 'hold' && (
                <svg viewBox="0 0 16 16" className="w-2.5 h-2.5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M4 4l8 8M12 4l-8 8" />
                </svg>
            )}
        </span>
    );
}
