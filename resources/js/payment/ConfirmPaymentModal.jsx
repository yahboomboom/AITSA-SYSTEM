import { useEffect, useRef, useState } from 'react';
import { peso } from '../utils/format';

// Pop-up shown before the student is sent to PayMongo. Clicking a Pay button
// only ever opens this card; nothing is submitted until "Confirm and pay".
// `payment` = { type: 'full' | 'down_payment', amount: number } or null.
export default function ConfirmPaymentModal({ payment, checkoutUrl, csrfToken, onClose }) {
    const [submitting, setSubmitting] = useState(false);
    const formRef = useRef(null);

    // Escape closes the pop-up (unless we are already redirecting).
    useEffect(() => {
        if (!payment) return undefined;
        const onKey = (e) => { if (e.key === 'Escape' && !submitting) onClose(); };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [payment, submitting, onClose]);

    // If the student presses Back from PayMongo, the browser may restore this
    // page from cache with the button still stuck on "Redirecting…".
    useEffect(() => {
        const onShow = (e) => { if (e.persisted) setSubmitting(false); };
        window.addEventListener('pageshow', onShow);
        return () => window.removeEventListener('pageshow', onShow);
    }, []);

    if (!payment) return null;

    const isDown = payment.type === 'down_payment';

    const confirm = () => {
        setSubmitting(true);
        formRef.current.submit();
    };

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget && !submitting) onClose(); }}
        >
            <div
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-payment-title"
                className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm max-h-[90vh] overflow-y-auto"
            >
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span id="confirm-payment-title" className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                        Confirm payment
                    </span>
                    <button
                        type="button" onClick={onClose} disabled={submitting} aria-label="Close"
                        className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white disabled:opacity-40"
                    >
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>

                <div className="p-6 space-y-4">
                    <div className="text-center">
                        <span className="font-heading text-3xl font-semibold text-brandNavy dark:text-white">{peso(payment.amount)}</span>
                    </div>

                    <div className="divide-y divide-brandNavy/8 dark:divide-slate-800 text-sm">
                        <div className="flex justify-between py-2">
                            <span className="text-brandNavy/70 dark:text-slate-400">Payment for</span>
                            <span className="text-brandNavy dark:text-slate-200">{isDown ? 'Down payment' : 'Full outstanding balance'}</span>
                        </div>
                        <div className="flex justify-between py-2">
                            <span className="text-brandNavy/70 dark:text-slate-400">Pay through</span>
                            <span className="text-brandNavy dark:text-slate-200">PayMongo (online)</span>
                        </div>
                    </div>

                    <p className="text-xs text-brandNavy/60 dark:text-slate-400 leading-relaxed">
                        You'll be taken to PayMongo's secure page to finish paying. Your balance updates once the payment is confirmed.
                    </p>

                    <form ref={formRef} action={checkoutUrl} method="POST" className="hidden">
                        <input type="hidden" name="_token" value={csrfToken} />
                        {isDown && <input type="hidden" name="type" value="down_payment" />}
                    </form>

                    <div className="flex gap-2">
                        <button
                            type="button" autoFocus onClick={onClose} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors disabled:opacity-40"
                        >
                            Cancel
                        </button>
                        <button
                            type="button" onClick={confirm} disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                        >
                            {submitting ? 'Redirecting to PayMongo…' : 'Confirm and pay'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}