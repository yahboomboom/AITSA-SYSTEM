function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

function statusClass(status) {
    if (status === 'Passed') return 'text-brandGreen';
    if (status === 'Failed') return 'text-red-500';
    return 'text-brandNavy/30 dark:text-slate-600';
}

function officeLabel(rejectedBy) {
    return rejectedBy === 'registrar' ? 'the Registrar' : 'the Department Chair';
}

function StatusBanner({ submissionStatus, rejectedBy, remarks }) {
    if (rejectedBy) {
        return (
            <div className="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm">
                <p className="font-bold"><i className="fa-solid fa-circle-exclamation mr-2" />Returned by {officeLabel(rejectedBy)}</p>
                {remarks && <p className="mt-1">{remarks}</p>}
            </div>
        );
    }
    if (submissionStatus === 'pending_chair') {
        return (
            <div className="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-sm font-bold">
                <i className="fa-solid fa-hourglass-half mr-2" />Submitted — awaiting Department Chair approval.
            </div>
        );
    }
    if (submissionStatus === 'pending_registrar') {
        return (
            <div className="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300 text-sm font-bold">
                <i className="fa-solid fa-hourglass-half mr-2" />Chair-approved — awaiting Registrar approval.
            </div>
        );
    }
    if (submissionStatus === 'approved') {
        return (
            <div className="p-4 rounded-lg bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-sm font-bold">
                <i className="fa-solid fa-circle-check mr-2" />Finalized and posted to student records.
            </div>
        );
    }
    return null;
}

export default function GradesForm({ students, csrfToken, actionUrl, submitUrl, submissionStatus, rejectedBy, remarks }) {
    const locked = submissionStatus !== 'draft';

    return (
        <div className="space-y-4">
            <StatusBanner submissionStatus={submissionStatus} rejectedBy={rejectedBy} remarks={remarks} />

            <form
                action={actionUrl}
                method="POST"
                className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden"
                onSubmit={(e) => lockSubmit(e.currentTarget, 'Saving…')}
            >
                <input type="hidden" name="_token" value={csrfToken} />

                {students.length === 0 ? (
                    <p className="text-sm text-brandNavy/50 dark:text-slate-400 p-6">No students are enrolled in this section yet.</p>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <table className="ui-table">
                                <thead>
                                    <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                                        <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                                        <th className="border-brandNavy/8 dark:border-slate-800">Student No.</th>
                                        <th className="border-brandNavy/8 dark:border-slate-800 w-32">Grade</th>
                                        <th className="border-brandNavy/8 dark:border-slate-800 w-24">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.map((student) => (
                                        <tr key={student.id}>
                                            <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-slate-200">{student.name}</td>
                                            <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-xs text-brandNavy/60 dark:text-slate-400">{student.loginId}</td>
                                            <td className="border-brandNavy/8 dark:border-slate-800">
                                            <input
                                                type="number" name={`grades[${student.id}]`}
                                                defaultValue={student.grade ?? ''}
                                                min="0" max="100" step="1" placeholder="—"
                                                disabled={locked}
                                                className="w-20 text-center font-mono text-sm rounded py-1.5 px-2 border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 text-brandNavy dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brandGreen/30 disabled:opacity-50 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                            />
                                        </td>
                                        <td className="border-brandNavy/8 dark:border-slate-800">
                                            <span className={`text-xs font-medium ${statusClass(student.status)}`}>
                                                {student.status ?? 'Not taken'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        </div>

                        {!locked && (
                            <div className="px-6 py-4 border-t border-brandNavy/10 dark:border-slate-800 flex justify-end">
                                <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i className="fa-solid fa-floppy-disk" />Save grades
                                </button>
                            </div>
                        )}
                    </>
                )}
            </form>

            {!locked && students.length > 0 && (
                <form
                    action={submitUrl}
                    method="POST"
                    onSubmit={(e) => lockSubmit(e.currentTarget, 'Submitting…')}
                >
                    <input type="hidden" name="_token" value={csrfToken} />
                    <button type="submit" className="ui-btn-primary w-full justify-center bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i className="fa-solid fa-paper-plane" />Submit for approval
                    </button>
                </form>
            )}
        </div>
    );
}
