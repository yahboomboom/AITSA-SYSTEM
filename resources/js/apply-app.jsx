import React, { useState, useCallback, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';

// Formats a raw <input type="date"> value ("YYYY-MM-DD") into something readable,
// e.g. "2007-03-14" -> "March 14, 2007".
function formatReviewDate(raw) {
    if (!raw) return '—';
    const d = new Date(raw + 'T00:00:00');
    if (isNaN(d)) return raw;
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

const APPLICANT_TYPE_LABELS = { NEW: 'New Student', TRANSFEREE: 'Transferee', RETURNEE: 'Returnee' };

/**
 * "Recheck the data before you pay" modal for the public application form.
 *
 * The application form itself (#applicationForm) stays plain Blade/HTML —
 * this component doesn't own or duplicate any of its fields. It only reads
 * the form's current values via the DOM (FormData) when opened, shows a
 * read-only summary, and — only once the applicant confirms — calls the
 * form's own requestSubmit(), which does a normal HTML POST exactly as
 * before (including the eventual redirect to the PayMongo checkout page).
 */
function ApplicationReviewModal({ reservationFee }) {
    const [open, setOpen] = useState(false);
    const [data, setData] = useState(null);
    const [submitting, setSubmitting] = useState(false);

    const openModal = useCallback(() => {
        const form = document.getElementById('applicationForm');
        if (!form) return;

        // Let the browser run its own required-field / format validation
        // first (tooltips on empty/invalid fields). Only open the review
        // modal once everything actually passes.
        if (!form.reportValidity()) return;

        const fd = new FormData(form);
        const get = (name) => (fd.get(name) ?? '').toString().trim();
        const progNameEl = document.getElementById('selectedProgName');

        setData({
            program: get('program_name') || (progNameEl ? progNameEl.textContent : '') || '—',
            name: get('name') || '—',
            email: get('email') || '—',
            contact: get('contact_number') || '—',
            dob: formatReviewDate(get('date_of_birth')),
            sex: get('sex') || '—',
            address: get('address') || '—',
            lastSchool: get('last_school') || '—',
            yearGraduated: get('year_graduated') || '—',
            applicantType: APPLICANT_TYPE_LABELS[get('applicant_type')] || '—',
            wantsReservation: fd.get('wants_reservation') === '1',
            remarks: get('remarks'),
        });
        setSubmitting(false);
        setOpen(true);
        document.body.style.overflow = 'hidden';
    }, []);

    const closeModal = useCallback(() => {
        if (submitting) return; // don't let Esc/backdrop close it mid-submit
        setOpen(false);
        document.body.style.overflow = '';
    }, [submitting]);

    // Expose an imperative bridge so the plain-Blade "Submit Application"
    // button (outside this React tree) can open the modal.
    useEffect(() => {
        window.openApplicationReview = openModal;
        return () => { delete window.openApplicationReview; };
    }, [openModal]);

    useEffect(() => {
        if (!open) return undefined;
        const onKey = (e) => { if (e.key === 'Escape') closeModal(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [open, closeModal]);

    const handleConfirm = () => {
        setSubmitting(true);
        // requestSubmit() (not submit()) keeps the form's normal submit
        // event/validation intact, then performs a real HTML POST — no
        // fetch/AJAX involved, so the existing server-side flow (including
        // the redirect to PayMongo checkout) works exactly as before.
        document.getElementById('applicationForm').requestSubmit();
    };

    if (!open || !data) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="absolute inset-0 bg-brandNavy/60 backdrop-blur-sm" onClick={closeModal} />

            <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
                <div className="px-6 py-5 border-b border-brandNavy/10 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 className="text-base font-black text-brandNavy">Review Your Application</h3>
                        <p className="text-xs text-brandNavy/50 mt-0.5">Please check that everything below is correct before submitting.</p>
                    </div>
                    <button type="button" onClick={closeModal} disabled={submitting}
                        className="w-8 h-8 rounded-lg text-brandNavy/40 hover:text-brandNavy hover:bg-lightBg flex items-center justify-center transition-colors disabled:opacity-40">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>

                <div className="px-6 py-5 overflow-y-auto space-y-5 text-sm">
                    <div>
                        <p className="text-[10px] font-bold text-brandNavy/40 uppercase tracking-widest mb-1">Program</p>
                        <p className="font-bold text-brandNavy">{data.program}</p>
                    </div>

                    <div>
                        <p className="text-[10px] font-bold text-brandNavy/40 uppercase tracking-widest mb-2">Personal Information</p>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2.5">
                            <div><dt className="text-[10px] text-brandNavy/40">Full Name</dt><dd className="font-semibold text-brandNavy">{data.name}</dd></div>
                            <div><dt className="text-[10px] text-brandNavy/40">Email Address</dt><dd className="font-semibold text-brandNavy break-all">{data.email}</dd></div>
                            <div><dt className="text-[10px] text-brandNavy/40">Contact Number</dt><dd className="font-semibold text-brandNavy">{data.contact}</dd></div>
                            <div><dt className="text-[10px] text-brandNavy/40">Date of Birth</dt><dd className="font-semibold text-brandNavy">{data.dob}</dd></div>
                            <div><dt className="text-[10px] text-brandNavy/40">Sex</dt><dd className="font-semibold text-brandNavy">{data.sex}</dd></div>
                            <div className="sm:col-span-2"><dt className="text-[10px] text-brandNavy/40">Home Address</dt><dd className="font-semibold text-brandNavy">{data.address}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <p className="text-[10px] font-bold text-brandNavy/40 uppercase tracking-widest mb-2">Academic Background</p>
                        <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2.5">
                            <div className="sm:col-span-2"><dt className="text-[10px] text-brandNavy/40">Last School Attended</dt><dd className="font-semibold text-brandNavy">{data.lastSchool}</dd></div>
                            <div><dt className="text-[10px] text-brandNavy/40">Year Graduated / Last Attended</dt><dd className="font-semibold text-brandNavy">{data.yearGraduated}</dd></div>
                            <div><dt className="text-[10px] text-brandNavy/40">Applicant Type</dt><dd className="font-semibold text-brandNavy">{data.applicantType}</dd></div>
                        </dl>
                    </div>

                    <div className="p-3 rounded-xl bg-brandGreen/5 border border-brandGreen/20">
                        <p className="text-[10px] font-bold text-brandNavy/40 uppercase tracking-widest mb-1">Slot Reservation</p>
                        <p className="font-semibold text-brandNavy text-xs">
                            {data.wantsReservation
                                ? `Yes — you'll pay a ₱${reservationFee.toLocaleString()} reservation fee right after submitting.`
                                : "No — you're skipping the slot reservation fee for now."}
                        </p>
                    </div>

                    {data.remarks && (
                        <div>
                            <p className="text-[10px] font-bold text-brandNavy/40 uppercase tracking-widest mb-1">Additional Remarks</p>
                            <p className="text-brandNavy/80 text-xs whitespace-pre-line">{data.remarks}</p>
                        </div>
                    )}
                </div>

                <div className="px-6 py-4 border-t border-brandNavy/10 bg-lightBg flex flex-col-reverse sm:flex-row items-center justify-end gap-3 flex-shrink-0">
                    <button type="button" onClick={closeModal} disabled={submitting}
                        className="w-full sm:w-auto px-5 py-2.5 text-xs font-bold text-brandNavy/60 hover:text-brandNavy transition-colors disabled:opacity-50">
                        <i className="fa-solid fa-pen mr-1.5" />Go Back &amp; Edit
                    </button>
                    <button type="button" onClick={handleConfirm} disabled={submitting}
                        className="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 bg-brandGreen hover:bg-emerald-700 text-white text-xs font-black rounded-xl transition-colors disabled:opacity-70">
                        {submitting ? (
                            <><i className="fa-solid fa-spinner fa-spin" />Submitting…</>
                        ) : (
                            <><i className="fa-solid fa-check" />Submit</>
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}

const el = document.getElementById('apply-review-root');
if (el) {
    const reservationFee = Number(el.dataset.reservationFee || 0);
    createRoot(el).render(
        <ErrorBoundary>
            <ApplicationReviewModal reservationFee={reservationFee} />
        </ErrorBoundary>
    );
}