export default function AnnouncementsPanel({ announcements }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
            <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-4">
                <i className="fa-solid fa-bullhorn text-brandGreen mr-2" />Announcements
            </h2>
            {announcements.length === 0 ? (
                <div className="text-center text-brandNavy/40 dark:text-slate-500 py-4">
                    <p className="text-sm">No announcements at this time.</p>
                </div>
            ) : (
                <ul className="divide-y divide-brandNavy/8 dark:divide-slate-800">
                    {announcements.map((announcement, index) => (
                        <li key={index} className="py-3 first:pt-0 last:pb-0">
                            <p className="font-medium text-brandNavy dark:text-white">{announcement.title}</p>
                            <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1 whitespace-pre-wrap">{announcement.body}</p>
                            <p className="text-xs text-brandNavy/40 dark:text-slate-600 mt-1.5">{announcement.postedAt}</p>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
