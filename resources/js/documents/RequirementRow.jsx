import DocCheck from '../components/DocCheck';

const STATUS_STYLES = {
    missing: { label: 'Missing', tone: 'pending', badge: 'border-brandNavy/20 text-brandNavy/50 dark:border-slate-600 dark:text-slate-500' },
    pending: { label: 'Awaiting review', tone: 'pending', badge: 'border-brandGold text-brandGold' },
    accepted: { label: 'Verified', tone: 'done', badge: 'border-brandGreen text-brandGreen' },
    rejected: { label: 'Rejected', tone: 'hold', badge: 'border-red-500 text-red-600' },
};

export default function RequirementRow({ requirement, onUpload }) {
    const status = STATUS_STYLES[requirement.status] ?? STATUS_STYLES.missing;
    const buttonLabel = requirement.status === 'missing' ? 'Upload' : requirement.status === 'rejected' ? 'Resubmit' : 'Replace';

    return (
        <div className="ui-doc-row border-brandNavy/8 dark:border-slate-800 justify-between">
            <div className="flex items-center gap-3 min-w-0">
                <DocCheck tone={status.tone} />
                <div className="min-w-0">
                    <p className="text-sm font-medium text-brandNavy dark:text-slate-200">{requirement.label}</p>
                    {requirement.originalName && (
                        <p className="text-xs text-brandNavy/50 dark:text-slate-500 font-mono mt-0.5 truncate max-w-64">
                            <i className="fa-solid fa-paperclip mr-1" />{requirement.originalName} · {requirement.createdAt}
                        </p>
                    )}
                    {requirement.status === 'rejected' && requirement.remarks && (
                        <p className="text-xs text-red-500 mt-1"><i className="fa-solid fa-comment-dots mr-1" />Registrar: {requirement.remarks}</p>
                    )}
                </div>
            </div>
            <div className="flex items-center gap-3 flex-shrink-0">
                <span className={`ui-badge-outline ${status.badge}`}>{status.label}</span>
                <button type="button" onClick={() => onUpload(requirement.type, requirement.label)} className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                    <i className="fa-solid fa-upload" />{buttonLabel}
                </button>
            </div>
        </div>
    );
}
