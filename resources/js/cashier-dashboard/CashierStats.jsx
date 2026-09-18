import { peso } from '../utils/format';

export default function CashierStats({ totalOutstanding, settledBase, settledCount, pendingActions }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Total outstanding</p>
                <h3 className="font-heading text-2xl font-semibold text-brandGold">{peso(totalOutstanding)}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Estimated value across remaining clear routes.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Settled base</p>
                <h3 className="font-heading text-2xl font-semibold text-brandGreen">{peso(settledBase)}</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">{settledCount} verified gateway updates logged.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-5 space-y-2">
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Pending actions</p>
                <h3 className="font-heading text-2xl font-semibold text-brandNavy dark:text-slate-200">{pendingActions} students</h3>
                <p className="text-xs text-brandNavy/40 dark:text-slate-500">Awaiting clearance validation.</p>
            </div>
        </div>
    );
}
