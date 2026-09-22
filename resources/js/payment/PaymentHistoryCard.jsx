import { peso } from '../utils/format';

const STATUS_STYLES = {
    Settled: 'bg-brandGreen/10 text-brandGreen border-brandGreen/20',
    Pending: 'bg-brandGold/10 text-brandGold border-brandGold/20',
    Failed: 'bg-red-600/10 text-red-600 border-red-600/20',
};

export default function PaymentHistoryCard({ history }) {
    if (!history || history.length === 0) return null;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-clock-rotate-left mr-2" />Payment History
                </span>
            </div>
            <div className="divide-y divide-brandNavy/5 dark:divide-slate-800/60">
                {history.map((row) => (
                    <div key={row.referenceNo} className="px-6 py-3.5 flex flex-wrap items-center justify-between gap-2 text-xs">
                        <div>
                            <span className="font-mono font-bold text-brandNavy dark:text-slate-200 block">{row.referenceNo}</span>
                            <span className="text-brandNavy/50 dark:text-slate-500">{row.gatewayLabel} · {row.createdAtFormatted}</span>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-black text-brandNavy dark:text-slate-200">{peso(row.amount)}</span>
                            <span className={`inline-flex items-center px-3 py-1 rounded text-[10px] font-bold border uppercase tracking-wider ${STATUS_STYLES[row.status] ?? 'bg-slate-500/10 text-slate-500 border-slate-500/20'}`}>
                                {row.status}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
