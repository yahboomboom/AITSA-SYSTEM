export default function StudentsTable({ students, onDeleteClick }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Name</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Program</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Year level</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Education level</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {students.length === 0 && (
                            <tr>
                                <td colSpan={6} className="border-brandNavy/8 dark:border-slate-800 py-10 text-center text-brandNavy/40 dark:text-slate-500">No students found.</td>
                            </tr>
                        )}
                        {students.map((student) => (
                            <tr key={student.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-brandNavy/60 dark:text-slate-400">{student.loginId}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{student.name}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/70 dark:text-slate-300">{student.major ?? '—'}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/70 dark:text-slate-300">{student.yearLevel ?? '—'}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/70 dark:text-slate-300">{student.programLevel ?? '—'}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                    <button
                                        type="button"
                                        onClick={() => onDeleteClick(student)}
                                        className="text-red-500 hover:text-red-700 font-medium text-xs"
                                    >
                                        <i className="fa-solid fa-trash mr-1" />Delete
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
