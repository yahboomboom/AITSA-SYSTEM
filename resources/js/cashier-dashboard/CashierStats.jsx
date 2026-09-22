import { peso } from '../utils/format';

export default function CashierStats({ totalOutstanding, settledBase, settledCount, pendingActions }) {
    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-5 space-y-2">
                <p className="text-[10px] font-bold uppercase tracking-wider text-brandNavy/40 dark:text-slate-500">Total Outstanding</p>
                <h3 className="text-2xl font-black text-brandGold">{peso(totalOutstanding)}</h3>
                <p className="text-[10px] text-brandNavy/40 dark:text-slate-500">Estimated value across remaining clear routes.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-5 space-y-2">
                <p className="text-[10px] font-bold uppercase tracking-wider text-brandNavy/40 dark:text-slate-500">Settled Base</p>
                <h3 className="text-2xl font-black text-brandGreen">{peso(settledBase)}</h3>
                <p className="text-[10px] text-brandNavy/40 dark:text-slate-500">{settledCount} verified gateway updates logged.</p>
            </div>

            <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-5 space-y-2">
                <p className="text-[10px] font-bold uppercase tracking-wider text-brandNavy/40 dark:text-slate-500">Pending Actions</p>
                <h3 className="text-2xl font-black text-brandNavy dark:text-slate-200">{pendingActions} Students</h3>
                <p className="text-[10px] text-brandNavy/40 dark:text-slate-500">Awaiting clearance validation.</p>
            </div>
        </div>
    );
}
