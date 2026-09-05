export default function SystemStatsCards({ totalActiveUsers, clearancesSettled, pendingQueues }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/8 dark:border-slate-800">
                <p className="text-brandNavy/50 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Total Active Users</p>
                <h4 className="text-2xl font-black text-brandNavy dark:text-white">{totalActiveUsers.toLocaleString()}</h4>
                <p className="text-[11px] text-brandNavy/40 dark:text-slate-500 mt-1">Across all role profiles</p>
            </div>

            <div className="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/8 dark:border-slate-800">
                <p className="text-brandNavy/50 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Clearances Settled</p>
                <h4 className="text-2xl font-black text-brandNavy dark:text-white">{clearancesSettled.toLocaleString()}</h4>
                <p className="text-[11px] text-brandGreen font-medium mt-1">This term</p>
            </div>

            <div className="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/8 dark:border-slate-800">
                <p className="text-brandNavy/50 dark:text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-2">Pending Queues</p>
                <h4 className="text-2xl font-black text-brandNavy dark:text-white">{pendingQueues.toLocaleString()}</h4>
                <p className="text-[11px] text-brandNavy/40 dark:text-slate-500 mt-1">Awaiting staff signature reviews</p>
            </div>
        </div>
    );
}
