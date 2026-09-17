export default function DayCard({ label, sections }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-slate-300 mb-1">
                <i className="fa-solid fa-calendar-day mr-2 text-brandGreen" />{label}
            </h3>
            <div className="divide-y divide-brandNavy/8 dark:divide-slate-800">
                {sections.map((section, i) => (
                    <div key={i} className="py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div>
                            <span className="font-medium font-mono">{section.subjectCode}</span>
                            <span className="text-brandNavy/70 dark:text-slate-400"> — {section.subjectTitle}</span>
                            <span className="ml-2 text-xs text-brandNavy/50 dark:text-slate-500">Block {section.blockLabel}</span>
                        </div>
                        <div className="flex items-center gap-3 text-xs">
                            <span className="font-medium text-brandNavy dark:text-slate-200">{section.timeRange}</span>
                            <span className="text-brandNavy/60 dark:text-slate-400">{section.roomLabel}</span>
                            {section.isOnline && (
                                <span className="ui-badge-outline border-brandGold text-brandGold">Online</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
