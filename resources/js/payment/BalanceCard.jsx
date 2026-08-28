import { peso } from '../utils/format';

export default function BalanceCard({ settled, breakdown, hasPendingGateway, checkoutUrl, verifyUrl, csrfToken }) {
    const b = breakdown ?? {};
    const hasDiscount = (b.discount_amount ?? 0) > 0;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800 flex justify-between items-center">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-wallet mr-2" />Account Balance
                </span>
                <span className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">Accounting Office</span>
            </div>
            <div className="p-6 lg:p-8 flex flex-col md:flex-row items-start justify-between gap-6">
                <div className="space-y-2 text-center md:text-left">
                    {settled ? (
                        <>
                            <p className="text-[10px] font-bold text-brandGreen uppercase tracking-widest">Outstanding Balance</p>
                            <h3 className="text-4xl font-black text-brandGreen">₱ 0.00</h3>
                            <p className="text-xs text-brandNavy/60 dark:text-slate-400">Your account has been fully settled with the Accounting Office.</p>
                        </>
                    ) : (
                        <>
                            <p className="text-[10px] font-bold text-brandGold uppercase tracking-widest">Outstanding Balance</p>
                            <h3 className="text-4xl font-black text-brandGold dark:text-amber-400">{peso(b.balance)}</h3>
                            <p className="text-xs text-brandNavy/60 dark:text-slate-400">Settle your balance to clear the cashier hold before enrollment.</p>
                        </>
                    )}

                    <div className="mt-4 bg-lightBg dark:bg-slate-900/40 border border-brandNavy/5 dark:border-slate-800 rounded-xl p-4 text-xs space-y-1.5 w-full md:w-80">
                        <p className="text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest mb-2">Assessment Breakdown</p>
                        <div className="flex justify-between">
                            <span className="text-brandNavy/60 dark:text-slate-400">Tuition ({b.units} units × {peso(b.rate)})</span>
                            <span className="font-bold text-brandNavy dark:text-slate-200">{peso(b.tuition)}</span>
                        </div>
                        {hasDiscount && (
                            <div className="flex justify-between text-brandGreen">
                                <span>{b.discount_name} (−{b.discount_percent}% tuition)</span>
                                <span className="font-bold">− {peso(b.discount_amount)}</span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-brandNavy/60 dark:text-slate-400">Miscellaneous Fee</span>
                            <span className="font-bold text-brandNavy dark:text-slate-200">{peso(b.misc)}</span>
                        </div>
                        {/* NEW: show the reservation fee as its own line, separate from tuition, so it's
                            clear it's already been paid and not being billed again inside "Total Assessment" */}
                        {(b.reservation_fee ?? 0) > 0 && (
                            <div className="flex justify-between">
                                <span className="text-brandNavy/60 dark:text-slate-400">Slot Reservation Fee <span className="text-brandGreen/80">(already paid)</span></span>
                                <span className="font-bold text-brandNavy dark:text-slate-200">{peso(b.reservation_fee)}</span>
                            </div>
                        )}
                        <div className="flex justify-between pt-1.5 border-t border-brandNavy/10 dark:border-slate-800">
                            <span className="text-brandNavy/60 dark:text-slate-400">Total Assessment</span>
                            <span className="font-bold text-brandNavy dark:text-slate-200">{peso(b.assessment)}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-brandNavy/60 dark:text-slate-400">Payments Made</span>
                            <span className="font-bold text-brandGreen">− {peso(b.paid)}</span>
                        </div>
                    </div>
                </div>
                <div className="flex flex-col gap-3 w-full md:w-auto">
                    {settled ? (
                        <button disabled className="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-lightBg text-brandNavy/40 dark:bg-slate-800 dark:text-slate-500 font-bold rounded-xl text-xs uppercase tracking-wider cursor-not-allowed border border-brandNavy/10 dark:border-slate-700">
                            <i className="fa-solid fa-circle-check" />Account Settled
                        </button>
                    ) : (
                        !hasPendingGateway && (
                            <form action={checkoutUrl} method="POST">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-brandGreen hover:bg-emerald-600 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md hover:shadow-brandGreen/25 hover:-translate-y-0.5 active:translate-y-0">
                                    <i className="fa-solid fa-credit-card" />Pay {peso(b.balance)} via PayMongo
                                </button>
                            </form>
                        )
                    )}
                    {hasPendingGateway && (
                        <>
                            <form action={verifyUrl} method="POST">
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-brandGold/10 hover:bg-brandGold text-brandGold hover:text-white border border-brandGold/30 font-bold rounded-xl text-xs uppercase tracking-wider transition-colors">
                                    <i className="fa-solid fa-rotate" />Verify Payment
                                </button>
                            </form>
                            <p className="text-[10px] text-brandNavy/50 dark:text-slate-500 text-center max-w-48">Finished paying on the gateway but the balance did not update? Verify here.</p>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}