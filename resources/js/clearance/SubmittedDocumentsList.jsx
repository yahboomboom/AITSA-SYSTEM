export default function SubmittedDocumentsList({ submissions }) {
    if (submissions.length === 0) return null;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                <span className="font-bold text-brandNavy dark:text-slate-300"><i className="fa-solid fa-folder-open mr-2" />My Submitted Documents</span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Registrar Review</span>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-800">
                {submissions.map((doc, i) => (
                    <div key={i} className="px-5 py-3 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div className="min-w-0">
                            <span className="font-bold text-brandNavy dark:text-slate-200 block">{doc.typeLabel}</span>
                            <a href={doc.documentsShowUrl} target="_blank" rel="noreferrer" className="text-blue-600 dark:text-blue-400 hover:underline font-mono text-[11px]">
                                <i className="fa-solid fa-paperclip mr-1" />{doc.originalName}
                            </a>
                            <span className="text-brandNavy/50 dark:text-slate-500 ml-2">{doc.createdAtFormatted}</span>
                            {doc.status === 'rejected' && doc.remarks && (
                                <p className="text-[11px] text-red-500 mt-1"><i className="fa-solid fa-comment-dots mr-1" />Registrar: {doc.remarks}</p>
                            )}
                        </div>
                        <div className="flex-shrink-0">
                            {doc.status === 'pending' && (
                                <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wider">Pending</span>
                            )}
                            {doc.status === 'accepted' && (
                                <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wider">Accepted</span>
                            )}
                            {doc.status !== 'pending' && doc.status !== 'accepted' && (
                                <span className="inline-flex items-center px-3 py-1 rounded text-[10px] font-bold bg-red-600/10 text-red-600 border border-red-600/20 uppercase tracking-wider">Rejected</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
