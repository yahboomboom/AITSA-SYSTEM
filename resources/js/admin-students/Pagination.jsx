export default function Pagination({ pagination }) {
    if (pagination.lastPage <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between">
            <p className="text-xs text-brandNavy/40 dark:text-slate-500">
                Showing {pagination.firstItem}–{pagination.lastItem} of {pagination.total} students
            </p>
            <div className="flex gap-1">
                {pagination.prevPageUrl ? (
                    <a href={pagination.prevPageUrl} className="px-3 py-1.5 text-xs font-medium rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Prev</a>
                ) : (
                    <span className="px-3 py-1.5 text-xs rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Prev</span>
                )}
                <span className="px-3 py-1.5 text-xs font-medium text-brandNavy/50 dark:text-slate-400">
                    Page {pagination.currentPage} of {pagination.lastPage}
                </span>
                {pagination.nextPageUrl ? (
                    <a href={pagination.nextPageUrl} className="px-3 py-1.5 text-xs font-medium rounded text-brandNavy dark:text-slate-300 border border-brandNavy/15 dark:border-slate-700 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">Next</a>
                ) : (
                    <span className="px-3 py-1.5 text-xs rounded text-brandNavy/25 dark:text-slate-600 border border-brandNavy/10 dark:border-slate-700 cursor-not-allowed">Next</span>
                )}
            </div>
        </div>
    );
}
