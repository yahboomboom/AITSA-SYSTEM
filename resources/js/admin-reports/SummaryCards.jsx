export default function SummaryCards({ summary }) {
    return (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-white dark:bg-panelDark rounded-2xl p-5 border border-brandNavy/10 dark:border-slate-800 shadow-sm">
                <p className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-wider">Enrolled Students</p>
                <p className="text-3xl font-black text-brandNavy dark:text-white mt-1">{summary.total}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Active clearance records</p>
            </div>
            <div className="bg-white dark:bg-panelDark rounded-2xl p-5 border border-brandGreen/20 dark:border-slate-800 shadow-sm">
                <p className="text-[10px] font-bold text-brandGreen/70 uppercase tracking-wider">Fully Cleared</p>
                <p className="text-3xl font-black text-brandGreen mt-1">{summary.cleared}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">All offices signed off</p>
            </div>
            <div className="bg-white dark:bg-panelDark rounded-2xl p-5 border border-brandGold/20 dark:border-slate-800 shadow-sm">
                <p className="text-[10px] font-bold text-brandGold/70 uppercase tracking-wider">Pending Clearance</p>
                <p className="text-3xl font-black text-brandGold mt-1">{summary.pending}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Still in process</p>
            </div>
            <div className="bg-white dark:bg-panelDark rounded-2xl p-5 border border-blue-500/20 dark:border-slate-800 shadow-sm">
                <p className="text-[10px] font-bold text-blue-500/70 uppercase tracking-wider">Payment Settled</p>
                <p className="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">{summary.cashierOk}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Cashier approved</p>
            </div>
        </div>
    );
}
