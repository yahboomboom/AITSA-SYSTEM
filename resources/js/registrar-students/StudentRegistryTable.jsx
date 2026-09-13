import { useEffect, useRef, useState } from 'react';

const STATUS_STYLES = {
    Cleared: 'bg-brandGreen/10 text-brandGreen border-brandGreen/20',
    Pending: 'bg-brandGold/10 text-brandGold border-brandGold/20',
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
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg overflow-hidden">
            <div className="px-5 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <span className="text-xs font-bold text-brandNavy dark:text-white uppercase tracking-wider">Student Records</span>
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <div className="relative w-full sm:w-72">
                        <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search student name or ID..."
                            className="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 pl-9 pr-4 py-2 rounded focus:outline-none focus:border-brandGreen transition-colors"
                        />
                    </div>
                    <select
                        value={statusFilter}
                        onChange={(event) => setStatusFilter(event.target.value)}
                        className="w-full sm:w-auto bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen transition-colors"
                    >
                        <option value="">Status</option>
                        <option value="all">View all students</option>
                        <option value="hold">On Hold</option>
                        <option value="cleared">Cleared</option>
                    </select>
                </div>
            </div>
            <div className="w-full overflow-hidden">
                <table className="w-full table-auto text-sm">
                <thead>
                    <tr className="border-b border-brandNavy/8 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40">
                            <th className="text-center px-3 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider">Student</th>
                            <th className="text-center px-3 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden sm:table-cell">Student ID</th>
                            <th className="text-center px-3 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden md:table-cell">Program</th>
                            <th className="text-center px-3 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider hidden md:table-cell">Year</th>
                            <th className="text-center px-3 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider">Admin Status</th>
                            <th className="text-center px-3 py-3 text-[10px] font-bold text-brandNavy/50 dark:text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={6} className="py-16 text-center text-brandNavy/40 dark:text-slate-500">
                                <i className="fa-solid fa-users text-3xl mb-3 block opacity-40" />
                                <p className="text-sm font-semibold">{loading ? 'Searching…' : (query || statusFilter) ? 'No matching student records.' : 'Search or filter to view student records.'}</p>
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
            <tr className="hover:bg-lightBg dark:hover:bg-slate-800/30 transition-colors">
                <td className="px-3 py-3.5">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-brandNavy/10 dark:bg-slate-700 flex items-center justify-center text-xs font-black text-brandNavy dark:text-slate-300 flex-shrink-0">{student.name.charAt(0).toUpperCase()}</div>
                        <div><p className="font-bold text-brandNavy dark:text-slate-200 text-sm leading-tight">{student.name}</p><p className="text-xs text-brandNavy/40 dark:text-slate-500 break-words">{student.email}</p></div>
                    </div>
                </td>
                <td className="px-3 py-3.5 hidden sm:table-cell"><span className="font-mono text-xs text-brandNavy/70 dark:text-slate-400 break-words">{student.loginId}</span></td>
                <td className="px-3 py-3.5 hidden md:table-cell"><span className="text-xs text-brandNavy/60 dark:text-slate-400 break-words">{student.major ?? '—'}</span></td>
                <td className="px-3 py-3.5 hidden md:table-cell"><div className="flex flex-wrap items-center gap-2"><span className="text-xs text-brandNavy/60 dark:text-slate-400">{student.yearLevel ?? '—'}</span>{student.isIrregular ? <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-500/10 text-amber-600">Irregular</span> : <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-500/10 text-blue-600">Regular</span>}</div></td>
                <td className="px-3 py-3.5">
                    <span className={`inline-flex items-center px-2 py-1 rounded text-[10px] font-bold border uppercase tracking-wider ${STATUS_STYLES[status] ?? STATUS_STYLES.Pending}`}>{status}</span>
                </td>
                <td className="px-3 py-3.5">
                    <div className="flex items-center justify-center gap-1.5">
                        <button
                            type="button"
                            title="View submitted documents"
                            onClick={() => setShowDocuments((shown) => !shown)}
                            className="inline-flex items-center gap-1 px-2 py-1.5 text-[10px] font-bold rounded border border-brandNavy/15 dark:border-slate-700 text-brandNavy dark:text-slate-200 hover:bg-brandNavy/5 dark:hover:bg-slate-800"
                        >
                            <i className="fa-solid fa-eye" />View
                        </button>
                        {student.signUrl && !student.needsAttention && status !== 'Cleared' && (
                            <form action={student.signUrl} method="POST">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" title="Sign and approve clearance" className="inline-flex items-center gap-1 px-2 py-1.5 text-[10px] font-bold rounded bg-brandNavy hover:bg-brandGreen text-white">
                                    <i className="fa-solid fa-signature" />Sign
                                </button>
                            </form>
                        )}
                    </div>
                </td>
            </tr>
            {showDocuments && <tr><td colSpan={6} className="px-4 py-4 bg-lightBg/60 dark:bg-slate-900/30">
                <div className="flex items-center justify-between mb-3"><span className="text-xs font-bold text-brandNavy dark:text-slate-200">Submitted documents</span><button type="button" onClick={() => setShowDocuments(false)} className="text-xs text-brandNavy/50 hover:text-brandNavy">Close</button></div>
                {student.documents?.length ? <div className="space-y-2">{student.documents.map((document) => <div key={document.id} className="flex flex-wrap items-center gap-2 text-xs"><span className={`font-bold uppercase ${document.status === 'rejected' ? 'text-red-600' : document.status === 'accepted' ? 'text-brandGreen' : 'text-brandGold'}`}>{document.status}</span><a href={document.documentUrl} target="_blank" rel="noreferrer" className="text-blue-600 dark:text-blue-400 hover:underline">{document.typeLabel}</a><span className="text-brandNavy/50">{document.originalName}</span>{document.remarks && <span className="text-red-600">{document.remarks}</span>}</div>)}</div> : <p className="text-xs text-brandNavy/50">No submitted documents.</p>}
            </td></tr>}
        </>
    );
}
