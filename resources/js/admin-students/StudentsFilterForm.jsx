const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

export default function StudentsFilterForm({ filters, programs, actionUrl }) {
    const hasActiveFilter = filters.q || filters.program || filters.year_level;

    return (
        <form
            method="GET"
            action={actionUrl}
            className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6 flex flex-wrap gap-4 items-end"
        >
            <div className="flex-1 min-w-[200px]">
                <label className="block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1">Search</label>
                <input
                    type="text" name="q" defaultValue={filters.q} placeholder="Name or student ID"
                    className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                />
            </div>
            <div className="min-w-[180px]">
                <label className="block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1">Program</label>
                <select
                    name="program" defaultValue={filters.program}
                    className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 text-sm text-brandNavy dark:text-slate-200 px-3 py-2 rounded outline-none focus:border-brandGreen/40 transition-colors"
                >
                    <option value="">All programs</option>
                    {programs.map((code) => (
                        <option key={code} value={code}>{code}</option>
                    ))}
                </select>
            </div>
            <div className="min-w-[160px]">
                <label className="block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1">Year level</label>
                <select
                    name="year_level" defaultValue={filters.year_level}
                    className="w-full bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 text-sm text-brandNavy dark:text-slate-200 px-3 py-2 rounded outline-none focus:border-brandGreen/40 transition-colors"
                >
                    <option value="">All years</option>
                    {YEAR_LEVELS.map((level) => (
                        <option key={level} value={level}>{level}</option>
                    ))}
                </select>
            </div>
            <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">
                Filter
            </button>
            {hasActiveFilter && (
                <a href={actionUrl} className="text-sm font-medium text-brandNavy/50 dark:text-slate-400 hover:text-brandNavy dark:hover:text-white transition-colors px-2 py-2">
                    Clear
                </a>
            )}
        </form>
    );
}
