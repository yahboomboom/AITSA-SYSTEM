export default function AdmissionStats({ total, approvedToday, pending }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div className="bg-white dark:bg-panelDark/40 p-6 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg">
                <p className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Total Applications</p>
                <h3 className="text-3xl font-black text-blue-600 dark:text-blue-400 mt-2">{total}</h3>
                <p className="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1">Registration Verification.</p>
            </div>

            <div className="bg-white dark:bg-panelDark/40 p-6 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg">
                <p className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Approved / Cleared Today</p>
                <h3 className="text-3xl font-black text-brandGreen dark:text-emerald-400 mt-2">{approvedToday}</h3>
                <p className="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1">Students cleared.</p>
            </div>

            <div className="bg-white dark:bg-panelDark/40 p-6 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg">
                <p className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Pending Decisions</p>
                <h3 className="text-3xl font-black text-brandGold dark:text-amber-400 mt-2">{pending}</h3>
                <p className="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1">Pending Requests.</p>
            </div>
        </div>
    );
}
