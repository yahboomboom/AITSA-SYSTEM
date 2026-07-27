export default function DayCard({ label, sections }) {
    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-calendar-day mr-2 text-brandGreen" />{label}
                </span>
            </div>
            <div className="divide-y divide-slate-100 dark:divide-slate-800">
                {sections.map((section, i) => (
                    <div key={i} className="px-5 py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div>
                            <span className="font-bold font-mono">{section.subjectCode}</span>
                            <span className="text-brandNavy/70 dark:text-slate-400"> — {section.subjectTitle}</span>
                            <span className="ml-2 text-xs text-brandNavy/50 dark:text-slate-500">Block {section.blockLabel}</span>
                        </div>
                        <div className="flex items-center gap-3 text-xs">
                            <span className="font-semibold">{section.timeRange}</span>
                            <span className="text-brandNavy/60 dark:text-slate-400">{section.roomLabel}</span>
                            {section.isOnline && (
                                <span className="px-1.5 py-0.5 rounded bg-brandGold/15 text-brandGold font-bold text-[10px] uppercase">Online</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
