export default function AnnouncementsPanel() {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
            <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-4">
                <i className="fa-solid fa-bullhorn text-brandGreen mr-2" />Announcements
            </h2>
            <div className="text-center text-brandNavy/40 dark:text-slate-500 py-4">
                <p className="text-sm">No announcements at this time.</p>
            </div>
        </div>
    );
}
