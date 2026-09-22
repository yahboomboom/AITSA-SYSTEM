function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function AddAnnouncementForm({ csrfToken, actionUrl }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-4">Post announcement</h3>
            <form
                action={actionUrl}
                method="POST"
                className="space-y-3"
                onSubmit={(e) => lockSubmit(e.currentTarget, 'Posting…')}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <input
                    type="text" name="title" required maxLength={150} placeholder="Title, e.g. Enrollment period open"
                    className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                />
                <textarea
                    name="body" required rows={3} placeholder="Announcement details…"
                    className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 transition-colors resize-none"
                />
                <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Post
                </button>
            </form>
        </div>
    );
}
