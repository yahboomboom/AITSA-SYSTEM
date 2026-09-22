export default function SectionsTable({ sections }) {
    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl shadow-sm overflow-hidden">
            {sections.length === 0 ? (
                <p className="text-sm text-slate-500 dark:text-slate-400 p-6">You are not assigned to any sections this term.</p>
            ) : (
                <table className="w-full text-sm">
                    <thead className="bg-lightBg dark:bg-slate-900/40 text-left text-[10px] uppercase tracking-wider text-brandNavy/50 dark:text-slate-500">
                        <tr>
                            <th className="px-6 py-3">Subject</th>
                            <th className="px-6 py-3">Block</th>
                            <th className="px-6 py-3">Enrolled</th>
                            <th className="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                        {sections.map((section) => (
                            <tr key={section.id}>
                                <td className="px-6 py-3">
                                    <span className="font-mono font-bold text-brandNavy dark:text-slate-200">{section.subjectCode}</span>
                                    <span className="block text-xs text-slate-500 dark:text-slate-400">{section.subjectTitle}</span>
                                </td>
                                <td className="px-6 py-3 text-brandNavy/80 dark:text-slate-300">{section.blockLabel}</td>
                                <td className="px-6 py-3 text-brandNavy/80 dark:text-slate-300">{section.enrolledCount}</td>
                                <td className="px-6 py-3 text-right">
                                    <a
                                        href={section.gradesUrl}
                                        className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brandNavy hover:bg-brandGreen text-white text-xs font-bold transition-colors"
                                    >
                                        <i className="fa-solid fa-pen-to-square" />Enter Grades
                                    </a>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </div>
    );
}
