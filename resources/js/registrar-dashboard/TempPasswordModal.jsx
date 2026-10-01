import { useState } from 'react';

// Temporary passwords the Registrar just generated. Stays open (and survives
// a reload) until Done, so the password can't vanish before it's handed over.
export default function TempPasswordModal({ items, csrfToken }) {
    const item = items[items.length - 1];
    const [copied, setCopied] = useState(false);

    const copy = () => {
        navigator.clipboard?.writeText(item.password).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    };

    return (
        <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm p-6 text-center">
                <div className="w-12 h-12 rounded-full bg-brandGreen/10 text-brandGreen flex items-center justify-center mx-auto mb-4">
                    <i className="fa-solid fa-key" />
                </div>
                <p className="font-heading text-base font-semibold text-brandNavy dark:text-white">Temporary password</p>
                <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">{item.name} · {item.loginId} · {item.createdAtFormatted}</p>

                <div className="mt-4 flex items-center gap-2 rounded-md border border-brandNavy/15 dark:border-slate-600 bg-lightBg dark:bg-slate-900 px-3 py-2.5">
                    <code className="flex-1 text-lg font-bold tracking-wider text-brandNavy dark:text-white select-all">{item.password}</code>
                    <button type="button" onClick={copy} aria-label="Copy password"
                        className="ui-btn-primary py-1 bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/70 dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800">
                        <i className={`fa-solid ${copied ? 'fa-check' : 'fa-copy'}`} />{copied ? 'Copied' : 'Copy'}
                    </button>
                </div>
                <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-3">Give it to the student in person. They'll set a new one at login.</p>
                {items.length > 1 && <p className="text-xs font-semibold text-brandGold mt-2">{items.length - 1} more after this</p>}

                <form method="POST" action={item.dismissUrl} className="mt-5">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button className="ui-btn-primary w-full justify-center bg-brandGreen hover:bg-[#247039] text-white">Done</button>
                </form>
            </div>
        </div>
    );
}
