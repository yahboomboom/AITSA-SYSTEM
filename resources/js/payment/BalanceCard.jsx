import { peso } from '../utils/format';

// Disables the submit button on click so a slow gateway round-trip can't be
// double-clicked into a second checkout session / pending ledger row.
function disableSubmit(e, busyLabel) {
    const btn = e.currentTarget.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
}

export default function BalanceCard({ settled, breakdown, hasPendingGateway, checkoutUrl, verifyUrl, csrfToken }) {
    const b = breakdown ?? {};
    const hasDiscount = (b.discount_amount ?? 0) > 0;

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-1">Accounting Office</h3>

            <div className="ui-headline-stat border-brandNavy/10 dark:border-slate-700">
                <span className={`ui-headline-num font-heading ${settled ? 'text-brandGreen' : 'text-brandGold'} dark:text-white`}>
                    {settled ? peso(0) : peso(b.balance)}
                </span>
                <span className="text-sm text-brandNavy/60 dark:text-slate-400 pb-1.5">
                    {settled ? 'fully settled with the Accounting Office' : 'outstanding balance'}
                </span>
            </div>

            <div className="flex flex-col md:flex-row items-start justify-between gap-6">
                <div className="divide-y divide-brandNavy/8 dark:divide-slate-800 text-sm w-full md:max-w-md">
                    <div className="flex justify-between py-2">
                        <span className="text-brandNavy/70 dark:text-slate-400">Tuition ({b.units} units × {peso(b.rate)})</span>
                        <span className="text-brandNavy dark:text-slate-200">{peso(b.tuition)}</span>
                    </div>
                    {hasDiscount && (
                        <div className="flex justify-between py-2 text-brandGreen">
                            <span>{b.discount_name} (−{b.discount_percent}% tuition)</span>
                            <span>− {peso(b.discount_amount)}</span>
                        </div>
                    )}
                    <div className="flex justify-between py-2">
                        <span className="text-brandNavy/70 dark:text-slate-400">Miscellaneous fee</span>
                        <span className="text-brandNavy dark:text-slate-200">{peso(b.misc)}</span>
                    </div>
                    {(b.reservation_fee ?? 0) > 0 && (
                        <div className="flex justify-between py-2">
                            <span className="text-brandNavy/70 dark:text-slate-400">Slot reservation fee <span className="text-brandGreen/80">(already paid)</span></span>
                            <span className="text-brandNavy dark:text-slate-200">{peso(b.reservation_fee)}</span>
                        </div>
                    )}
                    <div className="flex justify-between py-2">
                        <span className="font-medium text-brandNavy dark:text-slate-100">Total assessment</span>
                        <span className="font-medium text-brandNavy dark:text-slate-100">{peso(b.assessment)}</span>
                    </div>
                    <div className="flex justify-between py-2">
                        <span className="text-brandNavy/70 dark:text-slate-400">Payments made</span>
                        <span className="text-brandGreen">− {peso(b.paid)}</span>
                    </div>
                </div>

                <div className="flex flex-col gap-3 w-full md:w-auto md:min-w-64">
                    {settled ? (
                        <span className="ui-badge-outline border-brandGreen text-brandGreen justify-center py-2">
                            <i className="fa-solid fa-circle-check" />Account settled
                        </span>
                    ) : (
                        !hasPendingGateway && (
                            <form action={checkoutUrl} method="POST" onSubmit={(e) => disableSubmit(e, 'Redirecting to PayMongo…')}>
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="ui-btn-primary w-full justify-center bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                    <i className="fa-solid fa-credit-card" />Pay {peso(b.balance)} via PayMongo
                                </button>
                            </form>
                        )
                    )}
                    {hasPendingGateway && (
                        <>
                            <form action={verifyUrl} method="POST" onSubmit={(e) => disableSubmit(e, 'Verifying…')}>
                                <input type="hidden" name="_token" value={csrfToken} />
                                <button type="submit" className="ui-btn-primary w-full justify-center bg-transparent border border-brandGold text-brandGold hover:bg-brandGold hover:text-white transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                                    <i className="fa-solid fa-rotate" />Verify payment
                                </button>
                            </form>
                            <p className="text-xs text-brandNavy/50 dark:text-slate-500">Finished paying on the gateway but the balance did not update? Verify here.</p>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
