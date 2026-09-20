import { useMemo, useState } from 'react';

const APPLICANT_TYPE_STYLES = {
    NEW: 'border-brandGreen text-brandGreen',
    TRANSFEREE: 'border-blue-500 text-blue-600',
    RETURNEE: 'border-amber-500 text-amber-600',
};

// Disables the submit button right after a confirmed action so a slow
// request can't be triggered twice by an impatient double-click.
function disableSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
}

export default function ApplicantQueueTable({ applicants, csrfToken }) {
    const [search, setSearch] = useState('');
    const query = search.trim().toLowerCase();
    const filteredApplicants = useMemo(() => query
        ? applicants.filter((applicant) => `${applicant.name} ${applicant.email}`.toLowerCase().includes(query))
        : [], [applicants, query]);

    return (
        <div className="bg-white dark:bg-panelDark border border-brandGold/30 dark:border-amber-500/20 rounded-lg shadow-sm overflow-hidden">
            <div className="px-6 py-4 border-b border-brandGold/20 dark:border-amber-500/20 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div className="flex items-center gap-3">
                    <span className="w-2 h-2 rounded-full bg-amber-500 animate-pulse" />
                    <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Pending admission applications</h3>
                    <span className="ui-badge-outline border-amber-500 text-amber-600">{applicants.length} new</span>
                </div>
                <input
                    type="text"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Search applicant…"
                    className="w-full sm:w-64 bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                />
            </div>
            <div className="overflow-x-auto">
                <table className="ui-table">
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                            <th className="border-brandNavy/8 dark:border-slate-800">Applicant name</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Contact</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Program applied</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Type</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Last school</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Reservation</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Agreement</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Date applied</th>
                            <th className="border-brandNavy/8 dark:border-slate-800 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {filteredApplicants.length === 0 ? (
                            <tr><td colSpan={9} className="border-brandNavy/8 dark:border-slate-800 p-8 text-center text-brandNavy/40 dark:text-slate-500">{query ? 'No matching applicants.' : 'Search to view admission applications.'}</td></tr>
                        ) : filteredApplicants.map((applicant) => (
                            <tr key={applicant.id}>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <p className="font-medium text-brandNavy dark:text-white">{applicant.name}</p>
                                    <p className="text-brandNavy/50 dark:text-slate-500 text-xs">{applicant.email}</p>
                                    {applicant.dob && (
                                        <p className="text-brandNavy/40 dark:text-slate-600 text-xs">{applicant.sex ?? ''} · {applicant.dob}</p>
                                    )}
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">
                                    <p>{applicant.contactNumber ?? '—'}</p>
                                    <p className="text-xs text-brandNavy/40 dark:text-slate-600 mt-0.5 max-w-36 truncate">{applicant.address ?? ''}</p>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <p className="font-medium text-brandNavy dark:text-slate-200">{applicant.major ?? '—'}</p>
                                    <p className="text-xs text-brandNavy/40 dark:text-slate-600">{applicant.programLevel ?? ''}</p>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800">
                                    <span className={`ui-badge-outline ${APPLICANT_TYPE_STYLES[applicant.applicantType] ?? 'border-brandNavy/20 text-brandNavy/50'}`}>
                                        {applicant.applicantType ?? '—'}
                                    </span>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">
                                    <p>{applicant.lastSchool ?? '—'}</p>
                                    <p className="text-xs text-brandNavy/40 dark:text-slate-600">Grad: {applicant.yearGraduated ?? '—'}</p>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                    <div className="flex flex-col items-center gap-1.5">
                                        {applicant.isReserved ? (
                                            // Green badge: fee has been paid, slot is secured.
                                            <span className="ui-badge-outline border-brandGreen text-brandGreen">
                                                <i className="fa-solid fa-circle-check" />Reserved
                                            </span>
                                        ) : applicant.wantsReservation ? (
                                            // Gold badge: they said they want to reserve, but haven't paid yet.
                                            <span className="ui-badge-outline border-brandGold text-brandGold">
                                                <i className="fa-solid fa-clock" />Wants to reserve
                                            </span>
                                        ) : (
                                            // Gray badge: no interest indicated at all.
                                            <span className="ui-badge-outline border-brandNavy/20 text-brandNavy/50 dark:border-slate-700 dark:text-slate-500">
                                                Not reserved
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
                                                    return;
                                                }
                                                disableSubmit(e.currentTarget, 'Please wait…');
                                            }}
                                        >
                                            <input type="hidden" name="_token" value={csrfToken} />
                                            <button
                                                type="submit"
                                                className="text-xs font-medium text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-slate-300 underline underline-offset-2 transition-colors"
                                            >
                                                {applicant.isReserved ? 'Unmark paid' : 'Mark as paid'}
                                            </button>
                                        </form>

                                        {/* For applicants who never opted into an online/counter reservation
                                            payment at all — the only path left to give them a student account.
                                            Styled as a solid button (not an underlined text link) since this is
                                            an irreversible action that creates real login credentials and emails
                                            them out — it should not read the same as the cleanup actions nearby. */}
                                        {!applicant.isReserved && !applicant.wantsReservation && (
                                            <form
                                                action={applicant.activateApplicantUrl}
                                                method="POST"
                                                onSubmit={(e) => {
                                                    if (!window.confirm(`Create a student account for ${applicant.name} now? This immediately generates login credentials and emails them to the applicant.`)) {
                                                        e.preventDefault();
                                                        return;
                                                    }
                                                    disableSubmit(e.currentTarget, 'Activating…');
                                                }}
                                            >
                                                <input type="hidden" name="_token" value={csrfToken} />
                                                <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors">
                                                    Activate account
                                                </button>
                                            </form>
                                        )}
                                    </div>
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                    {applicant.agreementSigned ? (
                                        <a href={applicant.agreementViewUrl} target="_blank" rel="noopener noreferrer"
                                           className="inline-flex flex-col items-center gap-0.5 text-brandGreen hover:text-brandGreen/80 transition-colors">
                                            <span className="ui-badge-outline border-brandGreen text-brandGreen">
                                                <i className="fa-solid fa-signature" />Signed
                                            </span>
                                            <span className="text-xs font-medium underline underline-offset-2">{applicant.agreementSignedAt}</span>
                                        </a>
                                    ) : (
                                        <span className="ui-badge-outline border-brandNavy/20 text-brandNavy/40 dark:border-slate-700 dark:text-slate-500">
                                            Not signed
                                        </span>
                                    )}
                                </td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-center text-brandNavy/50 dark:text-slate-500">{applicant.createdAtFormatted}</td>
                                <td className="border-brandNavy/8 dark:border-slate-800 text-center">
                                    {/* Archives a stale application (never paid, never followed up) so it
                                        stops cluttering the queue. Not a decline decision — just cleanup. */}
                                    <form
                                        action={applicant.archiveApplicantUrl}
                                        method="POST"
                                        onSubmit={(e) => {
                                            if (!window.confirm(`Archive ${applicant.name}'s application? This removes them from the pending queue.`)) {
                                                e.preventDefault();
                                                return;
                                            }
                                            disableSubmit(e.currentTarget, 'Archiving…');
                                        }}
                                    >
                                        <input type="hidden" name="_token" value={csrfToken} />
                                        <button
                                            type="submit"
                                            className="text-xs font-medium text-red-500/70 hover:text-red-600 underline underline-offset-2 transition-colors"
                                        >
                                            Archive
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
