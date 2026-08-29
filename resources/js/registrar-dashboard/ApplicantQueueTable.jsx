const APPLICANT_TYPE_STYLES = {
    NEW: 'bg-brandGreen/10 text-brandGreen',
    TRANSFEREE: 'bg-blue-500/10 text-blue-600',
    RETURNEE: 'bg-amber-500/10 text-amber-600',
};

export default function ApplicantQueueTable({ applicants, csrfToken }) {
    return (
        <div className="bg-white dark:bg-panelDark/40 border border-brandGold/30 dark:border-amber-500/20 rounded-lg overflow-hidden">
            <div className="px-6 py-4 border-b border-brandGold/20 dark:border-amber-500/20 bg-brandGold/5 dark:bg-amber-500/5 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                    <h3 className="text-sm font-bold text-brandNavy dark:text-white">Pending Admission Applications</h3>
                    <span className="px-2 py-0.5 text-[9px] font-black bg-amber-500 text-white rounded-full">{applicants.length} new</span>
                </div>
                <p className="text-[10px] text-brandNavy/50 dark:text-slate-400">A student account is created automatically once the reservation fee is confirmed paid.</p>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                    <thead>
                        <tr className="border-b border-brandNavy/10 dark:border-slate-800 bg-lightBg dark:bg-slate-900/40 text-[10px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-widest">
                            <th className="py-3.5 px-6">Applicant Name</th>
                            <th className="py-3.5 px-6">Contact</th>
                            <th className="py-3.5 px-6">Program Applied</th>
                            <th className="py-3.5 px-6">Type</th>
                            <th className="py-3.5 px-6">Last School</th>
                            <th className="py-3.5 px-6 text-center">Reservation</th>/** the resrvation fee check boxs*/
                            <th className="py-3.5 px-6 text-center">Date Applied</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-brandNavy/5 dark:divide-slate-800/40 text-xs">
                        {applicants.map((applicant) => (
                            <tr key={applicant.id} className="hover:bg-amber-50/50 dark:hover:bg-amber-500/5 transition-colors">
                                <td className="py-4 px-6">
                                    <p className="font-bold text-brandNavy dark:text-white">{applicant.name}</p>
                                    <p className="text-brandNavy/50 dark:text-slate-500 text-[11px]">{applicant.email}</p>
                                    {applicant.dob && (
                                        <p className="text-brandNavy/40 dark:text-slate-600 text-[10px]">{applicant.sex ?? ''} · {applicant.dob}</p>
                                    )}
                                </td>
                                <td className="py-4 px-6 text-brandNavy/60 dark:text-slate-400">
                                    <p>{applicant.contactNumber ?? '—'}</p>
                                    <p className="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-0.5 max-w-[140px] truncate">{applicant.address ?? ''}</p>
                                </td>
                                <td className="py-4 px-6">
                                    <p className="font-semibold text-brandNavy dark:text-slate-200">{applicant.major ?? '—'}</p>
                                    <p className="text-[10px] text-brandNavy/40 dark:text-slate-600">{applicant.programLevel ?? ''}</p>
                                </td>
                                <td className="py-4 px-6">
                                    <span className={`px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wide ${APPLICANT_TYPE_STYLES[applicant.applicantType] ?? 'bg-slate-100 dark:bg-slate-800 text-brandNavy/50'}`}>
                                        {applicant.applicantType ?? '—'}
                                    </span>
                                </td>
                                <td className="py-4 px-6 text-brandNavy/60 dark:text-slate-400">
                                    <p>{applicant.lastSchool ?? '—'}</p>
                                    <p className="text-[10px] text-brandNavy/40 dark:text-slate-600">Grad: {applicant.yearGraduated ?? '—'}</p>
                                </td>
                                <td className="py-4 px-6 text-center">
                                    <div className="flex flex-col items-center gap-1.5">
                                        {applicant.isReserved ? (
                                            // Green badge: fee has been paid, slot is secured.
                                            <span className="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wide bg-brandGreen/10 text-brandGreen">
                                                <i className="fa-solid fa-circle-check mr-1" />Reserved
                                            </span>
                                        ) : applicant.wantsReservation ? (
                                            // Gold badge: they said they want to reserve, but haven't paid yet.
                                            <span className="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wide bg-brandGold/10 text-brandGold">
                                                <i className="fa-solid fa-clock mr-1" />Wants to Reserve
                                            </span>
                                        ) : (
                                            // Gray badge: no interest indicated at all.
                                            <span className="px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-wide bg-slate-100 dark:bg-slate-800 text-brandNavy/50 dark:text-slate-500">
                                                Not Reserved
                                            </span>
                                        )}

                                        {/* Admission staff can manually flip is_reserved once payment is confirmed
                                            (e.g. paid in cash at the counter). */}
                                        <form
                                            action={applicant.toggleReservationUrl}
                                            method="POST"
                                            onSubmit={(e) => {
                                                const action = applicant.isReserved ? 'Unmark' : 'Mark';
                                                if (!window.confirm(`${action} ${applicant.name} as having paid the ₱500 reservation fee?`)) {
                                                    e.preventDefault();
                                                }
                                            }}
                                        >
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <button
                                                type="submit"
                                                className="text-[9px] font-bold text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-slate-300 underline underline-offset-2 transition-colors"
                                            >
                                                {applicant.isReserved ? 'Unmark Paid' : 'Mark as Paid'}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <td className="py-4 px-6 text-center text-brandNavy/50 dark:text-slate-500">{applicant.createdAtFormatted}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
