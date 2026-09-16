const ROLE_BADGE_STYLES = {
    chair: 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400',
    cashier: 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400',
    registrar: 'bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400',
};

export default function SystemAccountsTable({ accounts }) {
    return (
        <div className="bg-white dark:bg-panelDark rounded-lg border border-brandNavy/8 dark:border-slate-800 overflow-hidden">
            <div className="mb-4 px-5 pt-5">
                <h4 className="text-base font-bold text-brandNavy dark:text-white">System Accounts</h4>
                <p className="text-xs text-brandNavy/50 dark:text-slate-400">Quick oversight of operational node access tiers.</p>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="bg-lightBg dark:bg-slate-800/40 text-brandNavy/40 dark:text-slate-500 text-[10px] font-bold uppercase tracking-widest border-b border-brandNavy/8 dark:border-slate-800">
                            <th className="py-3.5 px-5">Official Name</th>
                            <th className="py-3.5 px-5">Email</th>
                            <th className="py-3.5 px-5">System ID</th>
                            <th className="py-3.5 px-5">Role</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/60 text-sm">
                        {accounts.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="py-8 px-5 text-center text-brandNavy/40 dark:text-slate-500">
                                    No accounts found for this role.
                                </td>
                            </tr>
                        ) : (
                            accounts.map((account) => (
                                <tr key={account.id} className="hover:bg-lightBg/40 dark:hover:bg-slate-800/20 transition-colors">
                                    <td className="py-4 px-5 font-bold text-brandNavy dark:text-white">{account.name}</td>
                                    <td className="py-4 px-5 text-brandNavy/50 dark:text-slate-400">{account.email}</td>
                                    <td className="py-4 px-5 font-mono text-xs text-brandNavy/40 dark:text-slate-500">{account.loginId}</td>
                                    <td className="py-4 px-5">
                                        <span className={`px-2 py-0.5 rounded text-xs font-medium ${ROLE_BADGE_STYLES[account.role] ?? 'bg-slate-100 dark:bg-slate-800 text-brandNavy/50'}`}>
                                            {account.roleLabel}
                                        </span>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
