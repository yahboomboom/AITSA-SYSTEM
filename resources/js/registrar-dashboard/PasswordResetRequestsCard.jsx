export default function PasswordResetRequestsCard({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-key mr-2 text-brandGreen" />Password Reset Requests
            </h2>
            {rows.length > 0 && (
                <p className="text-xs text-brandNavy/50 dark:text-slate-400 -mt-2 mb-4"><i className="fa-solid fa-id-card mr-1" />Check the student's ID in person before approving.</p>
            )}

            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No students waiting on a password reset.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="flex items-center justify-between flex-wrap gap-3 border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
                        <div>
                            <p className="font-semibold text-brandNavy dark:text-slate-100">{row.name}</p>
                            <p className="text-xs text-slate-500">{row.loginId} — requested {row.requestedAtFormatted}</p>
                        </div>
                        <div className="flex gap-2">
                            <form method="POST" action={row.resetUrl}>
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button className="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">
                                    Approve &amp; Reset
                                </button>
                            </form>
                            {row.declineUrl && (
                                <form method="POST" action={row.declineUrl}
                                    onSubmit={(e) => { if (!window.confirm(`Decline ${row.name}'s password reset request?`)) e.preventDefault(); }}>
                                    <input type="hidden" name="_token" value={csrfToken} />
                                    <button className="px-4 py-2 rounded-lg border border-red-500 text-red-600 text-sm font-semibold hover:bg-red-500 hover:text-white transition-colors">
                                        Decline
                                    </button>
                                </form>
                            )}
                        </div>
                    </div>
                ))
            )}
        </div>
    );
}
