export default function PasswordResetRequestsCard({ rows, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mt-8">
            <h2 className="text-lg font-bold text-brandNavy dark:text-slate-100 mb-4">
                <i className="fa-solid fa-key mr-2 text-brandGreen" />Password Reset Requests
            </h2>

            {rows.length === 0 ? (
                <p className="text-sm text-slate-400">No students waiting on a password reset.</p>
            ) : (
                rows.map((row) => (
                    <div key={row.id} className="flex items-center justify-between flex-wrap gap-3 border border-slate-200 dark:border-slate-700 rounded-xl p-4 mb-3">
                        <div>
                            <p className="font-semibold text-brandNavy dark:text-slate-100">{row.name}</p>
                            <p className="text-xs text-slate-500">{row.loginId} — requested {row.requestedAtFormatted}</p>
                        </div>
                        <form method="POST" action={row.resetUrl}>
                            <input type="hidden" name="_token" value={csrfToken} />
                            <button className="px-4 py-2 rounded-lg bg-brandGreen text-white text-sm font-semibold hover:opacity-90">
                                Reset Password
                            </button>
                        </form>
                    </div>
                ))
            )}
        </div>
    );
}
