import { peso } from '../utils/format';

const STATUS_STYLES = {
    Settled: 'border-brandGreen text-brandGreen',
    Pending: 'border-brandGold text-brandGold',
    Failed: 'border-red-500 text-red-600',
};

export default function PaymentHistoryCard({ history }) {
    if (!history || history.length === 0) return null;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-2">Payment history</h3>
            <div className="divide-y divide-brandNavy/8 dark:divide-slate-800">
                {history.map((row) => (
                    <div key={row.referenceNo} className="py-3 flex flex-wrap items-center justify-between gap-2 text-sm">
                        <div>
                            <span className="font-mono font-medium text-brandNavy dark:text-slate-200 block">{row.referenceNo}</span>
                            <span className="text-xs text-brandNavy/50 dark:text-slate-500">{row.gatewayLabel} · {row.createdAtFormatted}</span>
                        </div>
                        <div className="flex items-center gap-3">
                            <span className="font-medium text-brandNavy dark:text-slate-200">{peso(row.amount)}</span>
                            <span className={`ui-badge-outline ${STATUS_STYLES[row.status] ?? 'border-brandNavy/20 text-brandNavy/50'}`}>
                                {row.status}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
