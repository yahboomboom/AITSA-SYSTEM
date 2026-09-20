export default function SummaryCards({ summary }) {
    return (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5">
                <p className="text-xs text-brandNavy/50 dark:text-slate-400">Total students</p>
                <p className="font-heading text-2xl font-semibold text-brandNavy dark:text-white mt-1">{summary.total}</p>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500 mt-1">Active clearance records</p>
            </div>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5">
                <p className="text-xs text-brandGreen/70">Registrar signed</p>
                <p className="font-heading text-2xl font-semibold text-brandGreen mt-1">{summary.registrarSigned}</p>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500 mt-1">Clearance signed off</p>
            </div>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5">
                <p className="text-xs text-brandGold/80">Awaiting signature</p>
                <p className="font-heading text-2xl font-semibold text-brandGold mt-1">{summary.registrarPending}</p>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500 mt-1">Still pending</p>
            </div>
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5">
                <p className="text-xs text-blue-600/80">Fully cleared</p>
                <p className="font-heading text-2xl font-semibold text-blue-600 dark:text-blue-400 mt-1">{summary.fullyCleared}</p>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500 mt-1">All offices approved</p>
            </div>
        </div>
    );
}
