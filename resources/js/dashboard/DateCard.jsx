export default function DateCard() {
    const today = new Date();
    const weekday = today.toLocaleDateString('en-US', { weekday: 'long' });
    const rest = today.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
            <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-3">
                <i className="fa-solid fa-calendar-day text-brandGreen mr-2" />Today
            </h2>
            <p className="text-2xl font-heading font-semibold text-brandNavy dark:text-white">{weekday}</p>
            <p className="text-sm text-brandNavy/50 dark:text-slate-400 mt-1">{rest}</p>
        </div>
    );
}
