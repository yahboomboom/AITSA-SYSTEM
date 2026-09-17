export default function AgreementsSummary({ agreements }) {
    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl border border-brandNavy/10 dark:border-slate-800 shadow-sm overflow-hidden">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-file-signature mr-2 text-brandGreen" />Enrollment Agreements (DocuSign)
                </span>
            </div>
            <div className="grid grid-cols-3 divide-x divide-brandNavy/8 dark:divide-slate-700">
                <div className="p-5 text-center">
                    <p className="text-[9px] font-bold text-brandGreen/80 uppercase tracking-wider mb-1">Signed</p>
                    <p className="text-3xl font-black text-brandGreen">{agreements.signed}</p>
                    <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Completed via DocuSign</p>
                </div>
                <div className="p-5 text-center">
                    <p className="text-[9px] font-bold text-brandGold/80 uppercase tracking-wider mb-1">Awaiting Signature</p>
                    <p className="text-3xl font-black text-brandGold">{agreements.awaiting}</p>
                    <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Sent, not yet completed</p>
                </div>
                <div className="p-5 text-center">
                    <p className="text-[9px] font-bold text-red-500/80 uppercase tracking-wider mb-1">Declined / Voided</p>
                    <p className="text-3xl font-black text-red-500">{agreements.declinedOrVoided}</p>
                    <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Needs follow-up</p>
                </div>
            </div>
        </div>
    );
}
