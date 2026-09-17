export default function SubmittedDocumentsList({ submissions }) {
    if (submissions.length === 0) return null;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-2">My submitted documents</h3>
            <div className="divide-y divide-brandNavy/8 dark:divide-slate-800">
                {submissions.map((doc, i) => (
                    <div key={i} className="py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div className="min-w-0">
                            <span className="font-medium text-brandNavy dark:text-slate-200 block">{doc.typeLabel}</span>
                            <a href={doc.documentsShowUrl} target="_blank" rel="noreferrer" className="text-brandNavy dark:text-brandGold hover:underline font-mono text-xs">
                                <i className="fa-solid fa-paperclip mr-1" />{doc.originalName}
                            </a>
                            <span className="text-brandNavy/50 dark:text-slate-500 ml-2 text-xs">{doc.createdAtFormatted}</span>
                            {doc.status === 'rejected' && doc.remarks && (
                                <p className="text-xs text-red-500 mt-1"><i className="fa-solid fa-comment-dots mr-1" />Registrar: {doc.remarks}</p>
                            )}
                        </div>
                        <div className="flex-shrink-0">
                            {doc.status === 'pending' && (
                                <span className="ui-badge-outline border-brandGold text-brandGold">Pending</span>
                            )}
                            {doc.status === 'accepted' && (
                                <span className="ui-badge-outline border-brandGreen text-brandGreen">Accepted</span>
                            )}
                            {doc.status !== 'pending' && doc.status !== 'accepted' && (
                                <span className="ui-badge-outline border-red-500 text-red-600">Rejected</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
