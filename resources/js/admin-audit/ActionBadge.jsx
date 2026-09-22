const ACTION_STYLE = {
    'Account Created': { badge: 'bg-brandGreen/10 text-brandGreen border-brandGreen/20', icon: 'fa-user-plus' },
    'Applicant Verified': { badge: 'bg-blue-500/10 text-blue-600 border-blue-500/20', icon: 'fa-user-check' },
    'Applicant Declined': { badge: 'bg-red-500/10 text-red-500 border-red-500/20', icon: 'fa-user-xmark' },
    'Clearance Signed': { badge: 'bg-brandNavy/10 text-brandNavy border-brandNavy/20 dark:bg-slate-700 dark:text-slate-300 dark:border-slate-600', icon: 'fa-pen-nib' },
    'Payment Approved': { badge: 'bg-orange-500/10 text-orange-600 border-orange-500/20', icon: 'fa-cash-register' },
    'Admission Approved': { badge: 'bg-purple-500/10 text-purple-600 border-purple-500/20', icon: 'fa-circle-check' },
};

const DEFAULT_STYLE = { badge: 'bg-brandNavy/5 text-brandNavy/50 border-brandNavy/10 dark:bg-slate-700 dark:text-slate-300', icon: 'fa-bolt' };

export default function ActionBadge({ action }) {
    const style = ACTION_STYLE[action] ?? DEFAULT_STYLE;

    return (
        <span className={`inline-flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-black rounded border uppercase tracking-wider ${style.badge}`}>
            <i className={`fa-solid ${style.icon} text-[8px]`} />
            {action}
        </span>
    );
}
