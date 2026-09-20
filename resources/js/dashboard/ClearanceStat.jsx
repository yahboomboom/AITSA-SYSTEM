export default function ClearanceStat({ percent }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 mb-6">
            <div className="ui-headline-stat border-brandNavy/10 dark:border-slate-700">
                <span className="ui-headline-num font-heading text-brandNavy dark:text-white">{percent}%</span>
                <span className="text-sm text-brandNavy/60 dark:text-slate-400 pb-1.5">cleared this term</span>
            </div>
            <a href="/clearance" className="text-sm font-medium text-brandNavy dark:text-brandGold hover:underline">
                View clearance status
            </a>
        </div>
    );
}
