export default function AdmissionStats({ total, approvedToday, pending }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                <p className="text-xs text-brandNavy/50 dark:text-slate-400">Total applications</p>
                <h3 className="font-heading text-2xl font-semibold text-blue-600 dark:text-blue-400 mt-2">{total}</h3>
                <p className="text-xs text-brandNavy/50 dark:text-slate-500 mt-1">Registration verification.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                <p className="text-xs text-brandNavy/50 dark:text-slate-400">Approved / cleared today</p>
                <h3 className="font-heading text-2xl font-semibold text-brandGreen mt-2">{approvedToday}</h3>
                <p className="text-xs text-brandNavy/50 dark:text-slate-500 mt-1">Students cleared.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
                <p className="text-xs text-brandNavy/50 dark:text-slate-400">Pending decisions</p>
                <h3 className="font-heading text-2xl font-semibold text-brandGold mt-2">{pending}</h3>
                <p className="text-xs text-brandNavy/50 dark:text-slate-500 mt-1">Pending requests.</p>
            </div>
        </div>
    );
}
