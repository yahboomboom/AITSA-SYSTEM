export default function MasterStatusBadge({ isCleared, cashierCleared, registrarCleared, chairCleared, items }) {
    return (
        <div id="masterBadgeCard" className={`bg-white dark:bg-panelDark border ${isCleared ? 'border-brandGreen/30' : 'border-brandGold/20'} rounded-xl p-6 flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-8`}>
            <div id="masterStatusBadge" className="flex flex-col items-center text-center justify-center md:border-r border-brandNavy/10 dark:border-slate-800 pr-0 md:pr-8 flex-shrink-0 w-full md:w-44">
                {isCleared ? (
                    <>
                        <div className="w-12 h-12 rounded-full bg-brandGreen/10 text-brandGreen flex items-center justify-center text-2xl mb-2">
                            <i className="fa-solid fa-circle-check" />
                        </div>
                        <span className="text-sm font-black text-brandGreen uppercase tracking-wider">Officially Cleared</span>
                    </>
                ) : (
                    <>
                        <div className="w-12 h-12 rounded-full bg-brandGold/10 text-brandGold flex items-center justify-center text-2xl mb-2 animate-pulse">
                            <i className="fa-solid fa-circle-exclamation" />
                        </div>
                        <span className="text-sm font-black text-brandGold uppercase tracking-wider">Pending Sign-off</span>
                    </>
                )}
            </div>
            {!isCleared && (
                <div className="flex-1 text-xs space-y-2 w-full">
                    {!cashierCleared && (
                        <div className="flex items-start space-x-2">
                            <i id="checkIconAccounting" className="fa-solid fa-circle-xmark text-brandGold mt-0.5" />
                            <p className="text-brandNavy/70 dark:text-slate-400">Accounting Office — Balance assessment verification.</p>
                        </div>
                    )}
                    {!registrarCleared && (
                        <div className="flex items-start space-x-2">
                            <i id="checkIconRegistrar" className="fa-solid fa-circle-xmark text-red-500 mt-0.5" />
                            <p className="text-brandNavy/70 dark:text-slate-400">Registrar — On-hold administrative document verification.</p>
                        </div>
                    )}
                    {!chairCleared && (
                        <div className="flex items-start space-x-2">
                            <i id="checkIconChair" className="fa-solid fa-circle-xmark text-brandGold mt-0.5" />
                            <p className="text-brandNavy/70 dark:text-slate-400">Department Head — Curriculum evaluation sign-off.</p>
                        </div>
                    )}
                    {items.filter((item) => item.status !== 'Approved').map((item, i) => (
                        <div key={i} className="flex items-start space-x-2">
                            <i className={`fa-solid ${item.status === 'Hold' ? 'fa-circle-xmark text-red-500' : 'fa-circle-xmark text-brandGold'} mt-0.5`} />
                            <p className="text-brandNavy/70 dark:text-slate-400">
                                {item.departmentName} —{' '}
                                {item.status === 'Hold' ? `On hold: ${item.remarks}` : 'Pending review.'}
                            </p>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}