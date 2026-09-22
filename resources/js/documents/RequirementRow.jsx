const STATUS_STYLES = {
    missing: { label: 'Missing', className: 'bg-slate-100 dark:bg-slate-800 text-brandNavy/50 dark:text-slate-500' },
    pending: { label: 'Pending', className: 'bg-brandGold/10 text-brandGold border border-brandGold/20' },
    accepted: { label: 'Verified', className: 'bg-brandGreen/10 text-brandGreen border border-brandGreen/20' },
    rejected: { label: 'Rejected', className: 'bg-red-600/10 text-red-600 border border-red-600/20' },
};

export default function RequirementRow({ requirement, onUpload }) {
    const status = STATUS_STYLES[requirement.status] ?? STATUS_STYLES.missing;
    const buttonLabel = requirement.status === 'missing' ? 'Upload' : requirement.status === 'rejected' ? 'Resubmit' : 'Replace';

    return (
        <div className="px-5 py-4 flex flex-wrap items-center justify-between gap-3">
            <div className="min-w-0">
                <p className="text-xs font-bold text-brandNavy dark:text-slate-200">{requirement.label}</p>
                {requirement.originalName && (
                    <p className="text-[11px] text-brandNavy/50 dark:text-slate-500 font-mono mt-0.5 truncate max-w-[260px]">
                        <i className="fa-solid fa-paperclip mr-1" />{requirement.originalName} · {requirement.createdAt}
                    </p>
                )}
                {requirement.status === 'rejected' && requirement.remarks && (
                    <p className="text-[11px] text-red-500 mt-1"><i className="fa-solid fa-comment-dots mr-1" />Registrar: {requirement.remarks}</p>
                )}
            </div>
            <div className="flex items-center gap-3 flex-shrink-0">
                <span className={`inline-flex items-center px-3 py-1 rounded text-[10px] font-bold uppercase tracking-wider ${status.className}`}>
                    {status.label}
                </span>
                <button
                    type="button"
                    onClick={() => onUpload(requirement.type, requirement.label)}
                    className="px-3 py-1.5 text-[11px] font-bold text-white bg-brandNavy hover:bg-brandGreen rounded-lg transition-colors whitespace-nowrap"
                >
                    <i className="fa-solid fa-upload mr-1.5" />{buttonLabel}
                </button>
            </div>
        </div>
    );
}
