function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

function confirmDelete(e, title) {
    if (!window.confirm(`Delete the announcement "${title}"? Students will stop seeing it on their dashboard.`)) {
        e.preventDefault();
        return false;
    }
    return lockSubmit(e.currentTarget, 'Deleting…');
}

export default function AnnouncementsList({ announcements, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm divide-y divide-brandNavy/8 dark:divide-slate-800">
            {announcements.length === 0 && (
                <p className="p-8 text-center text-sm text-brandNavy/40 dark:text-slate-500">No announcements yet. Post one above.</p>
            )}
            {announcements.map((announcement) => (
                <div key={announcement.id} className="p-5 flex items-start justify-between gap-4">
                    <div className="min-w-0">
                        <p className="font-medium text-brandNavy dark:text-white">{announcement.title}</p>
                        <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1 whitespace-pre-wrap">{announcement.body}</p>
                        <p className="text-xs text-brandNavy/40 dark:text-slate-600 mt-2">Posted by {announcement.postedBy} &middot; {announcement.postedAt}</p>
                    </div>
                    <form action={announcement.deleteUrl} method="POST" onSubmit={(e) => confirmDelete(e, announcement.title)} className="flex-shrink-0">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <button type="submit" className="text-xs font-medium text-red-500/70 hover:text-red-600 underline underline-offset-2 transition-colors">
                            Delete
                        </button>
                    </form>
                </div>
            ))}
        </div>
    );
}
