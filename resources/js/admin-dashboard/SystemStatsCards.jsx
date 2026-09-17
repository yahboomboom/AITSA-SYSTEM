export default function SystemStatsCards({ totalActiveUsers, clearancesSettled, pendingQueues }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Total active users</p>
                <h3 className="font-heading text-2xl font-semibold text-brandNavy dark:text-white">{totalActiveUsers.toLocaleString()}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Across all role profiles.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Clearances settled</p>
                <h3 className="font-heading text-2xl font-semibold text-brandGreen">{clearancesSettled.toLocaleString()}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">This term.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Pending queues</p>
                <h3 className="font-heading text-2xl font-semibold text-brandNavy dark:text-slate-200">{pendingQueues.toLocaleString()}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Awaiting staff signature reviews.</p>
            </div>
        </div>
    );
}
