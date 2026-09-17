export default function SummaryCards({ summary }) {
    return (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Enrolled students</p>
                <h3 className="font-heading text-2xl font-semibold text-brandNavy dark:text-white">{summary.total}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Active clearance records.</p>
            </div>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Fully cleared</p>
                <h3 className="font-heading text-2xl font-semibold text-brandGreen">{summary.cleared}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">All offices signed off.</p>
            </div>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Pending clearance</p>
                <h3 className="font-heading text-2xl font-semibold text-brandGold">{summary.pending}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Still in process.</p>
            </div>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Payment settled</p>
                <h3 className="font-heading text-2xl font-semibold text-blue-600 dark:text-blue-400">{summary.cashierOk}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Cashier approved.</p>
            </div>
        </div>
    );
}
