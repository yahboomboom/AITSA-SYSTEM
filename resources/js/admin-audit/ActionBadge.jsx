const ACTION_STYLE = {
    'Account Created': { badge: 'border-brandGreen text-brandGreen', icon: 'fa-user-plus' },
    'Applicant Verified': { badge: 'border-blue-500 text-blue-600', icon: 'fa-user-check' },
    'Applicant Declined': { badge: 'border-red-500 text-red-500', icon: 'fa-user-xmark' },
    'Clearance Signed': { badge: 'border-brandNavy/20 text-brandNavy dark:border-slate-600 dark:text-slate-300', icon: 'fa-pen-nib' },
    'Payment Approved': { badge: 'border-orange-500 text-orange-600', icon: 'fa-cash-register' },
    'Admission Approved': { badge: 'border-purple-500 text-purple-600', icon: 'fa-circle-check' },
};

const DEFAULT_STYLE = { badge: 'border-brandNavy/10 text-brandNavy/50 dark:border-slate-700 dark:text-slate-400', icon: 'fa-bolt' };

export default function ActionBadge({ action }) {
    const style = ACTION_STYLE[action] ?? DEFAULT_STYLE;

    return (
        <span className={`ui-badge-outline ${style.badge}`}>
            <i className={`fa-solid ${style.icon}`} />
            {action}
        </span>
    );
}
