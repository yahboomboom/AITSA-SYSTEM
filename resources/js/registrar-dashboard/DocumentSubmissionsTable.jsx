import { useEffect, useRef, useState } from 'react';

const STATUS_STYLES = {
    pending: 'bg-brandGold/10 text-brandGold border-brandGold/20',
    accepted: 'bg-brandGreen/10 text-brandGreen border-brandGreen/20',
    rejected: 'bg-red-600/10 text-red-600 border-red-600/20',
};

const STATUS_LABELS = {
    pending: 'Pending',
    accepted: 'Accepted',
    rejected: 'Rejected',
};

export default function DocumentSubmissionsTable({ searchUrl, pendingCount, rejectedCount, csrfToken }) {
    const initialParams = new URLSearchParams(window.location.search);
    const [search, setSearch] = useState(() => initialParams.get('documents_search') ?? '');
    const [viewAll, setViewAll] = useState(() => initialParams.get('documents_view') === 'all');
    const [statusFilter, setStatusFilter] = useState(null);
    const [documents, setDocuments] = useState([]);
    const [loading, setLoading] = useState(false);
    const query = search.trim();
    const requestId = useRef(0);

    useEffect(() => {
        const url = new URL(window.location.href);
        if (url.searchParams.has('documents_search') || url.searchParams.has('documents_view')) {
            url.searchParams.delete('documents_search');
            url.searchParams.delete('documents_view');
            window.history.replaceState({}, '', `${url.pathname}${url.search}${url.hash}`);
        }
    }, []);

    useEffect(() => {
        if (!query && !statusFilter) {
            setDocuments([]);
            setLoading(false);
            return;
        }
        if (!searchUrl) return;

        const thisRequest = ++requestId.current;
        setLoading(true);
        const timer = setTimeout(() => {
            const params = new URLSearchParams();
            if (query) params.set('q', query);
            if (statusFilter) {
                params.set('status', statusFilter);
            } else if (viewAll) {
                params.set('all', '1');
            }

            fetch(`${searchUrl}?${params}`, { headers: { Accept: 'application/json' } })
                .then((res) => (res.ok ? res.json() : Promise.reject(res)))
                .then((data) => {
                    if (requestId.current === thisRequest) {
                        setDocuments(Array.isArray(data.documents) ? data.documents : []);
                        setLoading(false);
                    }
                })
                .catch(() => {
                    if (requestId.current === thisRequest) setLoading(false);
                });
        }, 300);

        return () => clearTimeout(timer);
    }, [query, viewAll, statusFilter, searchUrl]);

    const toggleStatus = (status) => {
        setStatusFilter((current) => current === status ? null : status);
        setViewAll(false);
    };

    return (
        <div id="student-submitted-documents" className="bg-white dark:bg-panelDark/40 border border-brandNavy/10 dark:border-slate-800/80 rounded-lg overflow-hidden">
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/20 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <h3 className="text-sm font-bold text-brandNavy dark:text-white tracking-wide">
                        {(query || statusFilter) && !viewAll ? `Student ${statusFilter === 'rejected' ? 'Rejected' : 'Pending'} Document/s` : 'Student Submitted Document/s'}
                    </h3>
                    {pendingCount > 0 && (
                        <button
                            type="button"
                            title="Show pending documents"
                            onClick={() => toggleStatus('pending')}
                            className={`px-2 py-0.5 text-[9px] font-black rounded-full cursor-pointer transition-colors ${statusFilter === 'pending' ? 'bg-brandNavy text-white' : 'bg-brandGold text-white hover:opacity-80'}`}
                        >
                            {pendingCount} pending
                        </button>
                    )}
                    {rejectedCount > 0 && (
                        <button
                            type="button"
                            title="Show rejected documents"
                            onClick={() => toggleStatus('rejected')}
                            className={`px-2 py-0.5 text-[9px] font-black rounded-full cursor-pointer transition-colors ${statusFilter === 'rejected' ? 'bg-brandNavy text-white' : 'bg-red-600 text-white hover:opacity-80'}`}
                        >
                            {rejectedCount} rejected
                        </button>
                    )}
                </div>
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <div className="relative w-full sm:w-72">
                        <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                                setViewAll(false);
                            }}
                            placeholder="Search student name or ID"
                            className="w-full bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 pl-9 pr-3 py-2 rounded focus:outline-none focus:border-brandGreen"
                        />
                    </div>
                    <select
                        value={statusFilter ?? ''}
                        onChange={(event) => {
                            setStatusFilter(event.target.value || null);
                            setViewAll(false);
                        }}
                        className="w-full sm:w-auto bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen transition-colors"
                    >
                        <option value="">Status</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                            <th className="py-4 px-6">Student</th>
                            <th className="py-4 px-6">Document</th>
                            <th className="py-4 px-6">Submitted</th>
                            <th className="py-4 px-6 text-center">Status</th>
                            <th className="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                        {documents.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="py-12 text-center text-brandNavy/40 dark:text-slate-500 font-medium">
                                    <div className="flex flex-col items-center justify-center space-y-2">
                                        <i className="fa-solid fa-folder-open text-2xl text-brandNavy/20 dark:text-slate-600" />
                                        <span>{loading ? 'Searching…' : (query || statusFilter) ? 'No matching documents.' : 'Search or choose a status to view submitted documents.'}</span>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            documents.map((doc) => (
                                <tr key={doc.id} className="hover:bg-lightBg dark:hover:bg-slate-800/20 transition-colors align-top">
                                    <td className="py-5 px-6">
                                        <span className="font-bold text-brandNavy dark:text-white block">{doc.studentName}</span>
                                        <span className="font-mono text-brandNavy/60 dark:text-slate-400">{doc.studentId}</span>
                                    </td>
                                    <td className="py-5 px-6">
                                        <span className="font-semibold text-brandNavy dark:text-slate-200 block">{doc.typeLabel}</span>
                                        <a href={doc.documentUrl} target="_blank" rel="noreferrer" className="text-blue-600 dark:text-blue-400 hover:underline font-mono text-[11px]">
                                            <i className="fa-solid fa-paperclip mr-1" />{doc.originalName} ({doc.sizeKb} KB)
                                        </a>
                                        {doc.notes && (
                                            <p className="text-[11px] text-brandNavy/50 dark:text-slate-500 mt-1 italic">"{doc.notes}"</p>
                                        )}
                                    </td>
                                    <td className="py-5 px-6 text-brandNavy/60 dark:text-slate-400">{doc.createdAtFormatted}</td>
                                    <td className="py-5 px-6 text-center">
                                        {['pending', 'rejected'].includes(doc.status) ? (
                                            <form action={doc.reminderUrl} method="POST">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <button
                                                    type="submit"
                                                    title="Send a reminder to the student"
                                                    className={`inline-flex items-center px-3 py-1 rounded text-[10px] font-bold border uppercase tracking-wider cursor-pointer hover:opacity-80 ${STATUS_STYLES[doc.status]}`}
                                                >
                                                    {STATUS_LABELS[doc.status]}
                                                </button>
                                            </form>
                                        ) : (
                                            <span className={`inline-flex items-center px-3 py-1 rounded text-[10px] font-bold border uppercase tracking-wider ${STATUS_STYLES[doc.status] ?? 'bg-slate-500/10 text-slate-500 border-slate-500/20'}`}>
                                                {STATUS_LABELS[doc.status] ?? doc.status}
                                            </span>
                                        )}
                                        {doc.status === 'rejected' && doc.remarks && (
                                            <p className="text-[10px] text-red-500/80 mt-1 max-w-40 mx-auto">{doc.remarks}</p>
                                        )}
                                    </td>
                                    <td className="py-5 px-6 text-right">
                                        {doc.status === 'pending' ? (
                                            <div className="flex flex-col items-end gap-2">
                                                <form action={doc.acceptUrl} method="POST">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <button type="submit" className="px-4 py-2 bg-brandGreen hover:bg-emerald-600 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        <i className="fa-solid fa-check mr-1" />Accept
                                                    </button>
                                                </form>
                                                <form action={doc.rejectUrl} method="POST" className="flex items-center gap-2">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        required
                                                        maxLength={500}
                                                        placeholder="Reason for rejection"
                                                        className="w-44 bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-[11px] text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 px-3 py-2 rounded focus:outline-none focus:border-red-400"
                                                    />
                                                    <button type="submit" className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-[11px] font-black rounded transition-colors tracking-wide">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        ) : (
                                            <span className="text-[10px] text-brandNavy/40 dark:text-slate-500 uppercase tracking-wider">Reviewed</span>
                                        )}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
