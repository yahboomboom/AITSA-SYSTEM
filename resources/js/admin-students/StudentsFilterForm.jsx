const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

export default function StudentsFilterForm({ filters, programs, actionUrl }) {
    const hasActiveFilter = filters.q || filters.program || filters.year_level;

    return (
        <form
            method="GET"
            action={actionUrl}
            className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl p-6 flex flex-wrap gap-4 items-end"
        >
            <div className="flex-1 min-w-[200px]">
                <label className="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Search</label>
                <input
                    type="text" name="q" defaultValue={filters.q} placeholder="Name or Student ID"
                    className="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors"
                />
            </div>
            <div className="min-w-[180px]">
                <label className="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Program</label>
                <select
                    name="program" defaultValue={filters.program}
                    className="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors"
                >
                    <option value="">All Programs</option>
                    {programs.map((code) => (
                        <option key={code} value={code}>{code}</option>
                    ))}
                </select>
            </div>
            <div className="min-w-[160px]">
                <label className="block text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider mb-1.5">Year Level</label>
                <select
                    name="year_level" defaultValue={filters.year_level}
                    className="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-brandGreen transition-colors"
                >
                    <option value="">All Years</option>
                    {YEAR_LEVELS.map((level) => (
                        <option key={level} value={level}>{level}</option>
                    ))}
                </select>
            </div>
            <button type="submit" className="px-6 py-2.5 bg-brandGreen hover:bg-emerald-700 text-white text-sm font-bold rounded-xl transition-colors">
                Filter
            </button>
            {hasActiveFilter && (
                <a href={actionUrl} className="px-6 py-2.5 text-brandNavy/50 dark:text-slate-400 text-sm font-bold">
                    Clear
                </a>
            )}
        </form>
    );
}
