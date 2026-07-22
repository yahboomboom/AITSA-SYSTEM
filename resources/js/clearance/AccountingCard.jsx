const ACTION_ITEMS = [
    { label: 'Reservation Fee', paid: true, amount: '500.00' },
    { label: 'Tuition Fee — 1st Payment', paid: true, amount: '3,500.00' },
    { label: 'Tuition Fee — 2nd Payment', paid: false, amount: '3,500.00' },
    { label: 'Tuition Fee — 3rd Payment', paid: false, amount: '3,500.00' },
    { label: 'Tuition Fee — 4th Payment', paid: false, amount: '3,500.00' },
    { label: 'Tuition Fee — 5th Payment', paid: false, amount: '3,500.00' },
    { label: 'Acquaintance Party', paid: true, amount: '150.00' },
    { label: 'SportsFest', paid: false, amount: '200.00' },
    { label: 'Grad Ball (Graduating)', paid: false, amount: '500.00' },
];

export default function AccountingCard({ cashierCleared }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center text-xs">
                <span className="font-bold text-brandNavy dark:text-slate-300"><i className="fa-solid fa-credit-card mr-2" />Account Status</span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
            </div>
            <div id="accountingCardBody" className="p-6 space-y-5">
                {cashierCleared ? (
                    <div className="text-center space-y-1">
                        <h3 className="text-xl font-black text-brandGreen">Balance: ₱ 0.00</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">Your account balance has been fully settled.</p>
                    </div>
                ) : (
                    <div className="text-center space-y-2">
                        <h3 className="text-xl font-black text-brandGold">Balance: Pending Assessment</h3>
                        <p className="text-xs text-brandNavy/60 dark:text-slate-400">You have pending tuition or institutional fee obligations. Settle the items below to complete your clearance.</p>
                    </div>
                )}

                <div className="border border-brandNavy/8 dark:border-slate-700 rounded-xl overflow-hidden">
                    <div className="px-4 py-2.5 bg-lightBg dark:bg-slate-800/50 border-b border-brandNavy/8 dark:border-slate-700 flex items-center justify-between">
                        <span className="text-[10px] font-bold text-brandNavy/60 dark:text-slate-400 uppercase tracking-wider">Clearance Action Items</span>
                        <span className="text-[9px] font-black text-brandNavy/40 dark:text-slate-500">A.Y. 2025–2026</span>
                    </div>
                    <div className="divide-y divide-brandNavy/5 dark:divide-slate-800">
                        {ACTION_ITEMS.map((item) => (
                            <div key={item.label} className="flex items-center justify-between px-4 py-3 text-xs">
                                <div className="flex items-center gap-3">
                                    {item.paid ? (
                                        <>
                                            <div className="w-5 h-5 rounded-full bg-brandGreen/10 flex items-center justify-center flex-shrink-0" />
                                            <span className="font-medium text-brandNavy/60 dark:text-slate-400 line-through">{item.label}</span>
                                        </>
                                    ) : (
                                        <>
                                            <div className="w-5 h-5 rounded-full bg-brandGold/10 border border-brandGold/30 flex items-center justify-center flex-shrink-0" />
                                            <span className="font-semibold text-brandNavy dark:text-slate-200">{item.label}</span>
                                        </>
                                    )}
                                </div>
                                <div className="text-right flex-shrink-0 ml-4">
                                    {item.paid ? (
                                        <span className="text-[10px] font-bold text-brandGreen">Settled</span>
                                    ) : (
                                        <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400">₱ {item.amount}</span>
                                    )}
                                </div>
                            </div>
                        ))}
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
