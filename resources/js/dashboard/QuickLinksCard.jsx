// Add more entries here as AITSA's official links grow — nothing else about
// this card needs to change.
const LINKS = [
    { label: 'AITSA Facebook Page', url: 'https://www.facebook.com/aitsacabuyao', icon: 'fa-brands fa-facebook' },
];

export default function QuickLinksCard() {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
            <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-3">
                <i className="fa-solid fa-link text-brandGreen mr-2" />School Links
            </h2>
            <ul className="space-y-2">
                {LINKS.map((link) => (
                    <li key={link.url}>
                        <a
                            href={link.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-2 text-sm font-medium text-brandNavy dark:text-brandGold hover:underline"
                        >
                            <i className={`${link.icon} w-4 text-center`} />{link.label}
                        </a>
                    </li>
                ))}
            </ul>
        </div>
    );
}
