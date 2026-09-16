export default function SummaryCards({ summary }) {
    return (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandNavy/10 dark:border-slate-800">
                <p className="text-[10px] font-bold text-brandNavy/50 uppercase tracking-wider">Total Students</p>
                <p className="text-3xl font-black text-brandNavy dark:text-white mt-1">{summary.total}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Active clearance records</p>
            </div>
            <div className="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandGreen/20 dark:border-slate-800">
                <p className="text-[10px] font-bold text-brandGreen/70 uppercase tracking-wider">Registrar Signed</p>
                <p className="text-3xl font-black text-brandGreen mt-1">{summary.registrarSigned}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Clearance signed off</p>
            </div>
            <div className="bg-white dark:bg-panelDark rounded-lg p-5 border border-brandGold/20 dark:border-slate-800">
                <p className="text-[10px] font-bold text-brandGold/70 uppercase tracking-wider">Awaiting Signature</p>
                <p className="text-3xl font-black text-brandGold mt-1">{summary.registrarPending}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Still pending</p>
            </div>
            <div className="bg-white dark:bg-panelDark rounded-lg p-5 border border-blue-500/20 dark:border-slate-800">
                <p className="text-[10px] font-bold text-blue-500/70 uppercase tracking-wider">Fully Cleared</p>
                <p className="text-3xl font-black text-blue-600 dark:text-blue-400 mt-1">{summary.fullyCleared}</p>
                <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">All offices approved</p>
            </div>
        </div>
    );
}
