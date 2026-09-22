const ROLE_BADGE_STYLES = {
    chair: 'border-blue-500 text-blue-600',
    cashier: 'border-brandGold text-brandGold',
    registrar: 'border-purple-500 text-purple-600',
};

export default function SystemAccountsTable({ accounts }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-brandNavy/10 dark:border-slate-800">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">System accounts</h3>
                <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">Quick oversight of operational node access tiers.</p>
            </div>
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-500">
                            <th className="border-brandNavy/8 dark:border-slate-800">Official name</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Email</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">System ID</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        {accounts.length === 0 ? (
                            <tr>
                                <td colSpan={4} className="border-brandNavy/8 dark:border-slate-800 py-8 text-center text-brandNavy/40 dark:text-slate-500">
                                    No accounts found for this role.
                                </td>
                            </tr>
                        ) : (
                            accounts.map((account) => (
                                <tr key={account.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-medium text-brandNavy dark:text-white">{account.name}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{account.email}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 font-mono text-xs text-brandNavy/40 dark:text-slate-500">{account.loginId}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <span className={`ui-badge-outline ${ROLE_BADGE_STYLES[account.role] ?? 'border-brandNavy/20 text-brandNavy/50'}`}>
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
