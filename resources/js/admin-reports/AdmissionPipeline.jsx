export default function AdmissionPipeline({ pipeline }) {
    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-brandNavy/10 dark:border-slate-800">
                <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                    <i className="fa-solid fa-arrows-turn-right mr-2 text-brandGreen" />Admission pipeline
                </h3>
            </div>
            <div className="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-brandNavy/8 dark:divide-slate-700">
                <div className="p-5 text-center space-y-1">
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Pending review</p>
                    <p className="font-heading text-2xl font-semibold text-brandGold">{pipeline.pendingApplicants}</p>
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Awaiting registrar.</p>
                </div>
                <div className="p-5 text-center space-y-1">
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Verified</p>
                    <p className="font-heading text-2xl font-semibold text-blue-600 dark:text-blue-400">{pipeline.verifiedApplicants}</p>
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Awaiting account.</p>
                </div>
                <div className="p-5 text-center space-y-1">
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Enrolled</p>
                    <p className="font-heading text-2xl font-semibold text-brandGreen">{pipeline.totalStudents}</p>
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Active student accounts.</p>
                </div>
            </div>
        </div>
    );
}
