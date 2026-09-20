export default function SectionsTable({ sections }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            {sections.length === 0 ? (
                <p className="text-sm text-brandNavy/50 dark:text-slate-400 p-6">You are not assigned to any sections this term.</p>
            ) : (
                <div className="overflow-x-auto">
                    <table className="ui-table">
                        <thead>
                            <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                                <th className="border-brandNavy/8 dark:border-slate-800">Subject</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Block</th>
                                <th className="border-brandNavy/8 dark:border-slate-800">Enrolled</th>
                                <th className="border-brandNavy/8 dark:border-slate-800"></th>
                            </tr>
                        </thead>
                        <tbody>
                            {sections.map((section) => (
                                <tr key={section.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <span className="font-mono font-medium text-brandNavy dark:text-slate-200">{section.subjectCode}</span>
                                        <span className="block text-xs text-brandNavy/50 dark:text-slate-400">{section.subjectTitle}</span>
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{section.blockLabel}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/80 dark:text-slate-300">{section.enrolledCount}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                        <a href={section.gradesUrl} className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                                            <i className="fa-solid fa-pen-to-square" />Enter grades
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}
