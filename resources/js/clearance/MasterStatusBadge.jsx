import DocCheck from '../components/DocCheck';

export default function MasterStatusBadge({ isCleared, cashierCleared, registrarCleared, chairCleared, items }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <div className="flex items-center gap-3 pb-4 border-b border-brandNavy/10 dark:border-slate-700 mb-1">
                <span className={`w-8 h-8 rounded-full border-2 flex items-center justify-center flex-shrink-0 ${isCleared ? 'border-brandGreen text-brandGreen' : 'border-brandGold text-brandGold'}`}>
                    {isCleared ? (
                        <svg viewBox="0 0 16 16" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M3 8.5 6.5 12 13 4" />
                        </svg>
                    ) : (
                        <span className="w-2 h-2 rounded-full bg-brandGold" />
                    )}
                </span>
                <div>
                    <p className="font-heading text-lg font-semibold text-brandNavy dark:text-white">
                        {isCleared ? 'Officially cleared' : 'Pending sign-off'}
                    </p>
                    <p className="text-xs text-brandNavy/50 dark:text-slate-400">
                        {isCleared ? 'Every department has approved your clearance.' : 'One or more departments still need to approve your clearance.'}
                    </p>
                </div>
            </div>

            <div className="ui-doc-list border-brandNavy/8 dark:border-slate-800">
                <div className="ui-doc-row border-brandNavy/8 dark:border-slate-800">
                    <DocCheck tone={cashierCleared ? 'done' : 'pending'} />
                    <p className="text-sm text-brandNavy/70 dark:text-slate-400">
                        Accounting Office — {cashierCleared ? 'balance assessment cleared' : 'balance assessment pending'}
                    </p>
                </div>
                <div className="ui-doc-row border-brandNavy/8 dark:border-slate-800">
                    <DocCheck tone={registrarCleared ? 'done' : 'hold'} />
                    <p className="text-sm text-brandNavy/70 dark:text-slate-400">
                        Registrar — {registrarCleared ? 'administrative documents cleared' : 'administrative document hold'}
                    </p>
                </div>
                <div className="ui-doc-row border-brandNavy/8 dark:border-slate-800">
                    <DocCheck tone={chairCleared ? 'done' : 'pending'} />
                    <p className="text-sm text-brandNavy/70 dark:text-slate-400">
                        Department Head — {chairCleared ? 'curriculum evaluation cleared' : 'curriculum evaluation sign-off pending'}
                    </p>
                </div>
                {items.map((item, i) => (
                    <div key={i} className="ui-doc-row border-brandNavy/8 dark:border-slate-800">
                        <DocCheck tone={item.status === 'Approved' ? 'done' : item.status === 'Hold' ? 'hold' : 'pending'} />
                        <p className="text-sm text-brandNavy/70 dark:text-slate-400">
                            {item.departmentName} —{' '}
                            {item.status === 'Approved' ? 'cleared' : item.status === 'Hold' ? `on hold: ${item.remarks}` : 'pending review'}
                        </p>
                    </div>
                ))}
            </div>
        </div>
    );
}
