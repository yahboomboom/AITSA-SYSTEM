export default function ProgramBreakdown({ programBreakdown }) {
    if (programBreakdown.length === 0) {
        return null;
    }

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-brandNavy/10 dark:border-slate-800">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                    <i className="fa-solid fa-chart-bar mr-2 text-brandGreen" />Students by program
                </h3>
            </div>
            <div className="p-5 space-y-3">
                {programBreakdown.map((prog) => (
                    <div key={prog.major}>
                        <div className="flex items-center justify-between mb-1">
                            <span className="text-sm font-medium text-brandNavy dark:text-slate-200">{prog.major}</span>
                            <span className="text-xs font-medium text-brandNavy/50 dark:text-slate-400">{prog.count} <span className="font-normal">({prog.pct}%)</span></span>
                        </div>
                        <div className="h-1.5 bg-brandNavy/8 dark:bg-slate-700 rounded-full overflow-hidden">
                            <div className="h-full bg-brandGreen rounded-full transition-all" style={{ width: `${prog.pct}%` }} />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
