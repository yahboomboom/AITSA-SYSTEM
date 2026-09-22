function StatusRow({ cleared, unclearedColor, label }) {
    return (
        <div className="flex items-center space-x-3">
            <i className={`fa-solid ${cleared ? 'fa-circle-check text-brandGreen' : `fa-circle-xmark ${unclearedColor}`} text-sm`} />
            <p className="text-brandNavy/70 dark:text-slate-400">{label}</p>
        </div>
    );
}

export default function ClearanceStatusCard({ cashierCleared, registrarCleared, chairCleared }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm">
            <div className="bg-lightBg dark:bg-slate-800/60 px-6 py-3.5 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-file-invoice mr-2" />Clearance Status Overview
                </span>
            </div>
            <div className="p-6 space-y-3 text-xs">
                <StatusRow cleared={cashierCleared} unclearedColor="text-brandGold" label="Accounting Office — Balance Assessment" />
                <div className="flex items-center space-x-3">
                    <i className="fa-solid fa-circle-check text-brandGreen text-sm" />
                    <p className="text-brandNavy/70 dark:text-slate-400">Library — No pending borrowed items on record</p>
                </div>
                <StatusRow cleared={registrarCleared} unclearedColor="text-red-500" label="Registrar — Administrative document verification" />
                <StatusRow cleared={chairCleared} unclearedColor="text-brandGold" label="Department Head — Curriculum evaluation" />
            </div>
        </div>
    );
}
