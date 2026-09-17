const peso = (n) => `₱ ${Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function AccountingCard({ cashierCleared, breakdown, schoolYear }) {
    const b = breakdown ?? {};
    const hasDiscount = (b.discount_amount ?? 0) > 0;

    const lineItems = [
        { label: `Tuition fee (${b.units ?? 0} units × ${peso(b.rate)})`, amount: b.tuition },
        { label: 'Miscellaneous fee', amount: b.misc },
        ...(hasDiscount ? [{ label: `Discount — ${b.discount_name ?? 'applied'}`, amount: -b.discount_amount }] : []),
    ];

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between mb-1">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Accounting Office</h3>
                {schoolYear && <span className="text-xs text-brandNavy/40 dark:text-slate-500">A.Y. {schoolYear}</span>}
            </div>

            <div className="ui-headline-stat border-brandNavy/10 dark:border-slate-700">
                <span className={`ui-headline-num font-heading ${cashierCleared ? 'text-brandGreen' : 'text-brandNavy'} dark:text-white`}>
                    {peso(b.balance)}
                </span>
                <span className="text-sm text-brandNavy/60 dark:text-slate-400 pb-1.5">
                    {cashierCleared
                        ? (b.fully_paid ? 'balance fully settled' : 'cleared by the Accounting Office')
                        : 'balance due'}
                </span>
            </div>

            <div className="divide-y divide-brandNavy/8 dark:divide-slate-800">
                {lineItems.map((item) => (
                    <div key={item.label} className="flex items-center justify-between py-2.5 text-sm">
                        <span className="text-brandNavy/80 dark:text-slate-300">{item.label}</span>
                        <span className="text-brandNavy/60 dark:text-slate-400">{peso(item.amount)}</span>
                    </div>
                ))}
                <div className="flex items-center justify-between py-2.5 text-sm">
                    <span className="font-medium text-brandNavy dark:text-slate-100">Total assessment</span>
                    <span className="font-medium text-brandNavy dark:text-slate-100">{peso(b.assessment)}</span>
                </div>
                <div className="flex items-center justify-between py-2.5 text-sm">
                    <span className="text-brandNavy/60 dark:text-slate-400">Amount paid</span>
                    <span className="text-brandGreen">{peso(b.paid)}</span>
                </div>
                <div className="flex items-center justify-between py-2.5 text-sm">
                    <span className="font-medium text-brandNavy dark:text-slate-100">Remaining balance</span>
                    <span className={`font-medium ${b.fully_paid ? 'text-brandGreen' : 'text-brandGold'}`}>{peso(b.balance)}</span>
                </div>
            </div>

            {!cashierCleared && (
                <div className="flex justify-end mt-4">
                    <a href="/ledger" className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                        Go to payment
                    </a>
                </div>
            )}
        </div>
    );
}
