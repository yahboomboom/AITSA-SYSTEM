import { useState } from 'react';

// Subject credits the Registrar submitted for transferees/returnees. Nothing
// counts as passed until the Chair approves here.
export default function SubjectCreditQueue({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-file-circle-check mr-2 text-brandGreen" />Subject Credits
                {rows.length > 0 && <span className="ml-2 ui-badge-outline border-brandGold text-brandGold">{rows.length}</span>}
            </h2>
            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No subject credits waiting.</p>
            ) : (
                <div className="space-y-4">
                    {rows.map((row) => <CreditRow key={row.id} row={row} csrfToken={csrfToken} />)}
                </div>
            )}
        </div>
    );
}

function CreditRow({ row, csrfToken }) {
    const [rejecting, setRejecting] = useState(false);
    const [busy, setBusy] = useState(false);

    return (
        <div className="border border-brandNavy/10 dark:border-slate-700 rounded-lg p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-brandNavy dark:text-slate-100">
                        {row.studentName} <span className="text-xs font-normal text-brandNavy/50 dark:text-slate-400">{row.loginId}</span>
                    </p>
                    <p className="text-xs text-brandNavy/50 dark:text-slate-400">
                        {row.program} · <span className="font-semibold text-brandGold">{row.type}</span> · by {row.requestedBy}, {row.requestedAgo}
                    </p>
                    {row.note && <p className="text-xs text-brandNavy/70 dark:text-slate-300 mt-1"><i className="fa-regular fa-note-sticky mr-1" />{row.note}</p>}
                </div>
                <div className="flex gap-2">
                    <form method="POST" action={row.approveUrl} onSubmit={() => setBusy(true)}>
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button disabled={busy} className="ui-btn-primary bg-brandGreen hover:bg-[#247039] text-white disabled:opacity-50">
                            <i className="fa-solid fa-check" />Approve {row.items.length}
                        </button>
                    </form>
                    <button type="button" disabled={busy} onClick={() => setRejecting((v) => !v)}
                        className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white disabled:opacity-50">
                        Reject
                    </button>
                </div>
            </div>

            <ul className="mt-3 divide-y divide-brandNavy/8 dark:divide-slate-800 text-sm">
                {row.items.map((item) => (
                    <li key={item.code} className="py-1.5 flex items-center justify-between gap-3">
                        <span className="text-brandNavy dark:text-slate-200"><span className="font-mono text-xs mr-2">{item.code}</span>{item.title}</span>
                        <span className="text-xs text-brandNavy/60 dark:text-slate-400">{item.grade ?? 'No grade'}</span>
                    </li>
                ))}
            </ul>

            {rejecting && (
                <form method="POST" action={row.rejectUrl} onSubmit={() => setBusy(true)} className="mt-3 flex gap-2">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input name="remarks" required maxLength={500} autoFocus placeholder="Reason"
                        className="min-w-0 flex-1 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-1.5 text-sm text-brandNavy dark:text-slate-200" />
                    <button disabled={busy} className="ui-btn-primary bg-red-600 hover:bg-red-700 text-white disabled:opacity-50">Confirm</button>
                </form>
            )}
        </div>
    );
}
