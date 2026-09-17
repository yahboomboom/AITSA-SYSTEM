import { useEffect, useMemo, useRef, useState } from 'react';

const STATUS_STYLES = {
    pending: 'border-brandGold text-brandGold',
    accepted: 'border-brandGreen text-brandGreen',
    rejected: 'border-red-500 text-red-600',
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

    // A status filter (e.g. "4 rejected") spans every student, so the flat
    // list repeats each student's name/ID once per document and turns into
    // an unscannable wall of rows. Grouping by student and showing the
    // name/ID only once per group (ordered by that student's most recent
    // document, since `documents` arrives newest-first) keeps the same
    // table but cuts that repetition down.
    const groups = useMemo(() => {
        const byStudent = new Map();
        for (const doc of documents) {
            if (!byStudent.has(doc.studentId)) {
                byStudent.set(doc.studentId, { studentId: doc.studentId, studentName: doc.studentName, docs: [] });
            }
            byStudent.get(doc.studentId).docs.push(doc);
        }
        return Array.from(byStudent.values());
    }, [documents]);

    return (
        <div id="student-submitted-documents" className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div className="flex items-center gap-3">
                    <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                        {(query || statusFilter) && !viewAll ? `Student ${statusFilter === 'rejected' ? 'rejected' : 'pending'} documents` : 'Student document submissions'}
                    </h3>
                    {pendingCount > 0 && (
                        <button
                            type="button"
                            title="Show pending documents"
                            onClick={() => toggleStatus('pending')}
                            className={`ui-badge-outline cursor-pointer transition-colors ${statusFilter === 'pending' ? 'border-brandNavy bg-brandNavy text-white' : 'border-brandGold text-brandGold hover:bg-brandGold hover:text-white'}`}
                        >
                            {pendingCount} pending
                        </button>
                    )}
                    {rejectedCount > 0 && (
                        <button
                            type="button"
                            title="Show rejected documents"
                            onClick={() => toggleStatus('rejected')}
                            className={`ui-badge-outline cursor-pointer transition-colors ${statusFilter === 'rejected' ? 'border-brandNavy bg-brandNavy text-white' : 'border-red-500 text-red-600 hover:bg-red-500 hover:text-white'}`}
                        >
                            {rejectedCount} rejected
                        </button>
                    )}
                </div>
                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <div className="relative w-full sm:w-72">
                        <i className="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-brandNavy/40 dark:text-slate-500 text-xs" />
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                                setViewAll(false);
                            }}
                            placeholder="Search student name or ID"
                            className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded pl-9 pr-4 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                        />
                    </div>
                    <select
                        value={statusFilter ?? ''}
                        onChange={(event) => {
                            setStatusFilter(event.target.value || null);
                            setViewAll(false);
                        }}
                        className="w-full sm:w-auto bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 text-sm text-brandNavy dark:text-slate-200 px-3 py-2 rounded outline-none focus:border-brandGreen/40 transition-colors"
                    >
                        <option value="">Status</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Reject</option>
                    </select>
                </div>
            </div>
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Document</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Submitted</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Status</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {documents.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="border-brandNavy/8 dark:border-slate-800 py-12 text-center text-brandNavy/40 dark:text-slate-500">
                                    <div className="flex flex-col items-center justify-center space-y-2">
                                        <i className="fa-solid fa-folder-open text-2xl text-brandNavy/20 dark:text-slate-600" />
                                        <span>{loading ? 'Searching…' : (query || statusFilter) ? 'No matching documents.' : 'Search or choose a status to view submitted documents.'}</span>
                                    </div>
                                </td>
                            </tr>
                        ) : (
                            groups.map((group) => group.docs.map((doc, index) => (
                                <tr
                                    key={doc.id}
                                    className={`align-top ${index === 0 && group !== groups[0] ? 'border-t-2 border-t-brandNavy/10 dark:border-t-slate-700' : ''}`}
                                >
                                    {index === 0 && (
                                        <td className="border-brandNavy/8 dark:border-slate-800" rowSpan={group.docs.length}>
                                            <span className="font-medium text-brandNavy dark:text-white block">{group.studentName}</span>
                                            <span className="font-mono text-brandNavy/60 dark:text-slate-400">{group.studentId}</span>
                                        </td>
                                    )}
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <span className="font-medium text-brandNavy dark:text-slate-200 block">{doc.typeLabel}</span>
                                        <a href={doc.documentUrl} target="_blank" rel="noreferrer" className="text-brandNavy dark:text-brandGold hover:underline font-mono text-xs">
                                            <i className="fa-solid fa-paperclip mr-1" />{doc.originalName} ({doc.sizeKb} KB)
                                        </a>
                                        {doc.notes && (
                                            <p className="text-xs text-brandNavy/50 dark:text-slate-500 mt-1 italic">"{doc.notes}"</p>
                                        )}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{doc.createdAtFormatted}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                        {['pending', 'rejected'].includes(doc.status) ? (
                                            <form action={doc.reminderUrl} method="POST">
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <button
                                                    type="submit"
                                                    title="Send a reminder to the student"
                                                    className={`ui-badge-outline cursor-pointer hover:opacity-80 transition-opacity ${STATUS_STYLES[doc.status]}`}
                                                >
                                                    {STATUS_LABELS[doc.status]}
                                                </button>
                                            </form>
                                        ) : (
                                            <span className={`ui-badge-outline ${STATUS_STYLES[doc.status] ?? 'border-brandNavy/20 text-brandNavy/50'}`}>
                                                {STATUS_LABELS[doc.status] ?? doc.status}
                                            </span>
                                        )}
                                        {doc.status === 'rejected' && doc.remarks && (
                                            <p className="text-xs text-red-500/80 mt-1 max-w-40 mx-auto">{doc.remarks}</p>
                                        )}
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-right">
                                        {doc.status === 'pending' ? (
                                            <div className="flex flex-col items-end gap-2">
                                                <form action={doc.acceptUrl} method="POST">
                                                    <input type="hidden" name="_token" value={csrfToken} />
                                                    <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">
                                                        <i className="fa-solid fa-check" />Accept
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
                                                        className="w-44 bg-white dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-xs text-brandNavy dark:text-slate-200 placeholder-brandNavy/40 dark:placeholder-slate-500 px-3 py-2 rounded focus:outline-none focus:border-red-400"
                                                    />
                                                    <button type="submit" className="ui-btn-primary bg-transparent border border-red-500 text-red-600 hover:bg-red-500 hover:text-white transition-colors">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        ) : (
                                            <span className="text-xs text-brandNavy/40 dark:text-slate-500">Reviewed</span>
                                        )}
                                    </td>
                                </tr>
                            )))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
