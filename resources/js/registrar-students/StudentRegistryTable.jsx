import { useEffect, useRef, useState } from 'react';

const STATUS_STYLES = {
    Cleared: 'border-brandGreen text-brandGreen',
    Pending: 'border-brandGold text-brandGold',
};

export default function StudentRegistryTable({ searchUrl, csrfToken }) {
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [rows, setRows] = useState([]);
    const [loading, setLoading] = useState(false);
    const query = search.trim();
    const requestId = useRef(0);

    useEffect(() => {
        if (!query && !statusFilter) {
            setRows([]);
            setLoading(false);
            return;
        }
        if (!searchUrl) return;

        const thisRequest = ++requestId.current;
        setLoading(true);
        const timer = setTimeout(() => {
            const params = new URLSearchParams();
            if (query) params.set('q', query);
            if (statusFilter) params.set('status', statusFilter);

            fetch(`${searchUrl}?${params}`, { headers: { Accept: 'application/json' } })
                .then((res) => (res.ok ? res.json() : Promise.reject(res)))
                .then((data) => {
                    if (requestId.current === thisRequest) {
                        setRows(Array.isArray(data.rows) ? data.rows : []);
                        setLoading(false);
                    }
                })
                .catch(() => {
                    if (requestId.current === thisRequest) setLoading(false);
                });
        }, 300);

        return () => clearTimeout(timer);
    }, [query, statusFilter, searchUrl]);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Student records</h3>
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <div className="relative w-full sm:w-72">
                        <i className="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search student name or ID…"
                            className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                        />
                    </div>
                    <select
                        value={statusFilter}
                        onChange={(event) => setStatusFilter(event.target.value)}
                        className="w-full sm:w-auto bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 text-sm text-brandNavy dark:text-slate-200 px-3 py-2 rounded outline-none focus:border-brandGreen/40 transition-colors"
                    >
                        <option value="">Status</option>
                        <option value="all">View all students</option>
                        <option value="hold">On hold</option>
                        <option value="cleared">Cleared</option>
                    </select>
                </div>
            </div>
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 hidden sm:table-cell">Student ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell">Program</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell">Year</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Admin status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="border-brandNavy/8 dark:border-slate-800 py-16 text-center text-brandNavy/40 dark:text-slate-500">
                                    <i className="fa-solid fa-users text-3xl mb-3 block opacity-40" />
                                    <p className="text-sm font-medium">{loading ? 'Searching…' : (query || statusFilter) ? 'No matching student records.' : 'Search or filter to view student records.'}</p>
                                </td>
                            </tr>
                        ) : rows.map((student) => <StudentRow key={student.id} student={student} csrfToken={csrfToken} />)}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function StudentRow({ student, csrfToken }) {
    const [showDocuments, setShowDocuments] = useState(false);
    const status = student.adminStatus ?? 'Pending';

    return (
        <>
            <tr>
                <td className="border-brandNavy/8 dark:border-slate-800">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-brandNavy/10 dark:bg-slate-700 flex items-center justify-center text-xs font-semibold text-brandNavy dark:text-slate-300 flex-shrink-0">{student.name.charAt(0).toUpperCase()}</div>
                        <div>
                            <p className="font-medium text-brandNavy dark:text-slate-200 leading-tight">{student.name}</p>
                            <p className="text-xs text-brandNavy/40 dark:text-slate-500 break-words">{student.email}</p>
                        </div>
                    </div>
                </td>
                <td className="border-brandNavy/8 dark:border-slate-800 hidden sm:table-cell"><span className="font-mono text-xs text-brandNavy/70 dark:text-slate-400 break-words">{student.loginId}</span></td>
                <td className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell text-brandNavy/60 dark:text-slate-400 break-words">{student.major ?? '—'}</td>
                <td className="border-brandNavy/8 dark:border-slate-800 hidden md:table-cell">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="text-brandNavy/60 dark:text-slate-400">{student.yearLevel ?? '—'}</span>
                        {student.isIrregular ? (
                            <span className="ui-badge-outline border-brandGold text-brandGold">Irregular</span>
                        ) : (
                            <span className="ui-badge-outline border-blue-500 text-blue-600">Regular</span>
                        )}
                    </div>
                </td>
                <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                    <span className={`ui-badge-outline ${STATUS_STYLES[status] ?? STATUS_STYLES.Pending}`}>{status}</span>
                </td>
                <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                    <div className="flex items-center justify-center gap-1.5">
                        <button
                            type="button"
                            title="View submitted documents"
                            onClick={() => setShowDocuments((shown) => !shown)}
                            className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors"
                        >
                            <i className="fa-solid fa-eye" />View
                        </button>
                        {student.signUrl && !student.needsAttention && status !== 'Cleared' && (
                            <form action={student.signUrl} method="POST">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" title="Sign and approve clearance" className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                                    <i className="fa-solid fa-signature" />Sign
                                </button>
                            </form>
                        )}
                    </div>
                </td>
            </tr>
            {showDocuments && (
                <tr>
                    <td colSpan={6} className="border-brandNavy/8 dark:border-slate-800 bg-lightBg/60 dark:bg-slate-900/30">
                        <div className="flex items-center justify-between mb-3">
                            <span className="font-medium text-brandNavy dark:text-slate-200">Submitted documents</span>
                            <button type="button" onClick={() => setShowDocuments(false)} className="text-xs text-brandNavy/50 hover:text-brandNavy dark:hover:text-white">Close</button>
                        </div>
                        {student.documents?.length ? (
                            <div className="space-y-2">
                                {student.documents.map((document) => (
                                    <div key={document.id} className="flex flex-wrap items-center gap-2 text-sm">
                                        <span className={`ui-badge-outline ${document.status === 'rejected' ? 'border-red-500 text-red-600' : document.status === 'accepted' ? 'border-brandGreen text-brandGreen' : 'border-brandGold text-brandGold'}`}>{document.status}</span>
                                        <a href={document.documentUrl} target="_blank" rel="noreferrer" className="text-brandNavy dark:text-brandGold hover:underline">{document.typeLabel}</a>
                                        <span className="text-brandNavy/50 dark:text-slate-500">{document.originalName}</span>
                                        {document.remarks && <span className="text-red-600">{document.remarks}</span>}
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-brandNavy/50 dark:text-slate-500">No submitted documents.</p>
                        )}
                    </td>
                </tr>
            )}
        </>
    );
}
