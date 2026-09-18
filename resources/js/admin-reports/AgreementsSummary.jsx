export default function AgreementsSummary({ agreements }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-brandNavy/10 dark:border-slate-800">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                    <i className="fa-solid fa-file-signature mr-2 text-brandGreen" />Enrollment agreements (DocuSign)
                </h3>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-brandNavy/8 dark:divide-slate-700">
                <div className="p-5 text-center space-y-1">
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Signed</p>
                    <p className="font-heading text-2xl font-semibold text-brandGreen">{agreements.signed}</p>
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Completed via DocuSign.</p>
                </div>
                <div className="p-5 text-center space-y-1">
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Awaiting signature</p>
                    <p className="font-heading text-2xl font-semibold text-brandGold">{agreements.awaiting}</p>
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Sent, not yet completed.</p>
                </div>
                <div className="p-5 text-center space-y-1">
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Declined / voided</p>
                    <p className="font-heading text-2xl font-semibold text-red-500">{agreements.declinedOrVoided}</p>
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Needs follow-up.</p>
                </div>
            </div>
        </div>
    );
}
