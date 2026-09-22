export default function AdmissionPipeline({ pipeline }) {
    return (
        <div className="bg-white dark:bg-panelDark rounded-2xl border border-brandNavy/10 dark:border-slate-800 shadow-sm overflow-hidden">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-arrows-turn-right mr-2 text-brandGreen" />Admission Pipeline
                </span>
            </div>
            <div className="grid grid-cols-3 divide-x divide-brandNavy/8 dark:divide-slate-700">
                <div className="p-5 text-center">
                    <p className="text-[9px] font-bold text-brandGold/80 uppercase tracking-wider mb-1">Pending Review</p>
                    <p className="text-3xl font-black text-brandGold">{pipeline.pendingApplicants}</p>
                    <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Awaiting Registrar</p>
                </div>
                <div className="p-5 text-center">
                    <p className="text-[9px] font-bold text-blue-500/80 uppercase tracking-wider mb-1">Verified</p>
                    <p className="text-3xl font-black text-blue-600 dark:text-blue-400">{pipeline.verifiedApplicants}</p>
                    <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Awaiting Account</p>
                </div>
                <div className="p-5 text-center">
                    <p className="text-[9px] font-bold text-brandGreen/80 uppercase tracking-wider mb-1">Enrolled</p>
                    <p className="text-3xl font-black text-brandGreen">{pipeline.totalStudents}</p>
                    <p className="text-[9px] text-brandNavy/40 dark:text-slate-500 mt-1">Active student accounts</p>
                </div>
            </div>
        </div>
    );
}
