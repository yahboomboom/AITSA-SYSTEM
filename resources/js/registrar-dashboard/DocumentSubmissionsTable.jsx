const STATUS_STYLES = {
    pending: 'border-brandGold text-brandGold',
    accepted: 'border-brandGreen text-brandGreen',
    rejected: 'border-red-500 text-red-600',
};

const STATUS_LABELS = {
    pending: 'Pending',
    accepted: 'Accepted',
    rejected: 'Rejected',
};

export default function DocumentSubmissionsTable({ documents, csrfToken }) {
    const pendingCount = documents.filter((doc) => doc.status === 'pending').length;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Student document submissions</h3>
                {pendingCount > 0 && (
                    <span className="ui-badge-outline border-brandGold text-brandGold">{pendingCount} pending</span>
                )}
            </div>
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Document</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Submitted</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {documents.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 py-12 text-center text-brandNavy/40 dark:text-slate-500">
                                    <div className="flex flex-col items-center justify-center space-y-2">
                                        <i className="fa-solid fa-folder-open text-2xl text-brandNavy/20 dark:text-slate-600" />
                                        <span>No document submissions yet.</span>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            documents.map((doc) => (
                                <tr key={doc.id} className="align-top">
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <span className="font-medium text-brandNavy dark:text-white block">{doc.studentName}</span>
                                        <span className="font-mono text-brandNavy/60 dark:text-slate-400">{doc.studentId}</span>
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <span className="font-medium text-brandNavy dark:text-slate-200 block">{doc.typeLabel}</span>
                                        <a href={doc.documentUrl} target="_blank" rel="noreferrer" className="text-brandNavy dark:text-brandGold hover:underline font-mono text-xs">
                                            <i className="fa-solid fa-paperclip mr-1" />{doc.originalName} ({doc.sizeKb} KB)
                                        </a>
                                        {doc.notes && (
                                            <p className="text-xs text-brandNavy/50 dark:text-slate-500 mt-1 italic">"{doc.notes}"</p>
                                        )}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{doc.createdAtFormatted}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                        <span className={`ui-badge-outline ${STATUS_STYLES[doc.status] ?? 'border-brandNavy/20 text-brandNavy/50'}`}>
                                            {STATUS_LABELS[doc.status] ?? doc.status}
                                        </span>
                                        {doc.status === 'rejected' && doc.remarks && (
                                            <p className="text-xs text-red-500/80 mt-1 max-w-40 mx-auto">{doc.remarks}</p>
                                        )}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                        {doc.status === 'pending' ? (
                                            <div className="flex flex-col items-end gap-2">
                                                <form action={doc.acceptUrl} method="POST">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">
                                                        <i className="fa-solid fa-check" />Accept
                                                    </button>
                                                </form>
                                                <form action={doc.rejectUrl} method="POST" className="flex items-center gap-2">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        required
                                                        maxLength={500}
                                                        placeholder="Reason for rejection"
                                                        className="w-44 bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 px-3 py-2 rounded focus:outline-none focus:border-red-400"
                                                    />
                                                    <button type="submit" className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        ) : (
                                            <span className="text-xs text-brandNavy/40 dark:text-slate-500">Reviewed</span>
                                        )}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
