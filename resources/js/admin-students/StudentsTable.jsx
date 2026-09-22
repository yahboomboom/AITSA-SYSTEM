export default function StudentsTable({ students, onDeleteClick }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <table className="w-full text-sm">
                <thead className="bg-lightBg dark:bg-slate-900/40 border-b border-brandNavy/8 dark:border-slate-800">
                    <tr className="text-[10px] font-black text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider">
                        <th className="text-left px-6 py-3">Student ID</th>
                        <th className="text-left px-6 py-3">Name</th>
                        <th className="text-left px-6 py-3">Program</th>
                        <th className="text-left px-6 py-3">Year Level</th>
                        <th className="text-left px-6 py-3">Education Level</th>
                        <th className="text-right px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                    {students.length === 0 && (
                        <tr>
                            <td colSpan={6} className="px-6 py-10 text-center text-brandNavy/40 dark:text-slate-500">No students found.</td>
                        </tr>
                    )}
                    {students.map((student) => (
                        <tr key={student.id}>
                            <td className="px-6 py-3 font-mono text-brandNavy dark:text-slate-200">{student.loginId}</td>
                            <td className="px-6 py-3 font-semibold text-brandNavy dark:text-white">{student.name}</td>
                            <td className="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{student.major ?? '—'}</td>
                            <td className="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{student.yearLevel ?? '—'}</td>
                            <td className="px-6 py-3 text-brandNavy/70 dark:text-slate-300">{student.programLevel ?? '—'}</td>
                            <td className="px-6 py-3 text-right">
                                <button
                                    type="button"
                                    onClick={() => onDeleteClick(student)}
                                    className="text-red-500 hover:text-red-700 font-bold text-xs"
                                >
                                    <i className="fa-solid fa-trash mr-1" />Delete
                                </button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
