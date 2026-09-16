const peso = (n) => `₱ ${Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

export default function AccountingCard({ cashierCleared, breakdown, schoolYear }) {
    const b = breakdown ?? {};
    const hasDiscount = (b.discount_amount ?? 0) > 0;

    const lineItems = [
        { label: `Tuition Fee (${b.units ?? 0} units × ${peso(b.rate)})`, amount: b.tuition },
        { label: 'Miscellaneous Fee', amount: b.misc },
        ...(hasDiscount ? [{ label: `Discount — ${b.discount_name ?? 'Applied'}`, amount: -b.discount_amount }] : []),
    ];

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                <span className="font-bold text-brandNavy dark:text-slate-300"><i className="fa-solid fa-credit-card mr-2" />Account Status</span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
            </div>
            <div id="accountingCardBody" className="p-6 space-y-5">
                {cashierCleared ? (
                    <div className="text-center space-y-1">
                        <h3 className="text-xl font-black text-brandGreen">Balance: {peso(b.balance)}</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">
                            {b.fully_paid
                                ? 'Your account balance has been fully settled.'
                                : 'Your account has been cleared by the Accounting Office.'}
                        </p>
                    </div>
                ) : (
                    <div className="text-center space-y-2">
                        <h3 className="text-xl font-black text-brandGold">Balance: {peso(b.balance)}</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">You have pending tuition or institutional fee obligations. Settle your balance below to complete your clearance.</p>
                    </div>
                )}

                <div className="border border-brandNavy/8 dark:border-slate-700 rounded-xl overflow-hidden">
                    <div className="px-4 py-2.5 bg-lightBg dark:bg-slate-800/50 border-b border-brandNavy/8 dark:border-slate-700 flex items-center justify-between">
                        <span className="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider">Assessment Breakdown</span>
                        <span className="text-[9px] font-black text-brandNavy/40 dark:text-slate-500">A.Y. {schoolYear}</span>
                    </div>
                    <div className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                        {lineItems.map((item) => (
                            <div key={item.label} className="flex items-center justify-between px-4 py-3 text-xs">
                                <span className="font-semibold text-brandNavy dark:text-slate-200">{item.label}</span>
                                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400">{peso(item.amount)}</span>
                            </div>
                        ))}
                        <div className="flex items-center justify-between px-4 py-3 text-xs bg-lightBg/60 dark:bg-slate-800/30">
                            <span className="font-bold text-brandNavy dark:text-slate-100">Total Assessment</span>
                            <span className="text-[10px] font-black text-brandNavy dark:text-slate-100">{peso(b.assessment)}</span>
                        </div>
                        <div className="flex items-center justify-between px-4 py-3 text-xs">
                            <span className="font-semibold text-brandNavy/60 dark:text-slate-400">Amount Paid</span>
                            <span className="text-[10px] font-bold text-brandGreen">{peso(b.paid)}</span>
                        </div>
                        <div className="flex items-center justify-between px-4 py-3 text-xs">
                            <span className="font-bold text-brandNavy dark:text-slate-100">Remaining Balance</span>
                            <span className={`text-[10px] font-black ${b.fully_paid ? 'text-brandGreen' : 'text-brandGold'}`}>{peso(b.balance)}</span>
                        </div>
                    </div>
                </div>

                {!cashierCleared && (
                    <div className="flex justify-end">
                        <a href="/ledger" className="inline-flex items-center gap-2 text-xs font-bold bg-brandGold hover:bg-yellow-500 text-brandNavy px-5 py-2.5 rounded-xl border border-brandGold/30 transition-all shadow-sm">
                            <i className="fa-solid fa-wallet" />Go to Payment
                        </a>
                    </div>
                )}
            </div>
        </div>
    );
}
