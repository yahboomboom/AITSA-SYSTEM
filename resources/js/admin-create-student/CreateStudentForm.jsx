import { useMemo, useState } from 'react';

const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
const APPLICANT_TYPES = [
    { value: 'NEW', label: 'New Student' },
    { value: 'TRANSFEREE', label: 'Transferee' },
    { value: 'RETURNEE', label: 'Returnee' },
];

function generatePassword() {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789@#!';
    let pw = 'Aitsa@';
    for (let i = 0; i < 6; i++) pw += chars[Math.floor(Math.random() * chars.length)];
    return pw;
}

function lockSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Creating…';
    }
    return true;
}

const inputClass = 'w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 transition-colors';
const readOnlyClass = 'bg-lightBg/60 dark:bg-slate-800/50 cursor-not-allowed';
const labelClass = 'block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1';

export default function CreateStudentForm({ applicant, programs, old, csrfToken, actionUrl }) {
    const programLevelByCode = useMemo(() => {
        const map = {};
        programs.forEach((group) => group.programs.forEach((p) => { map[p.code] = p.level.toUpperCase(); }));
        return map;
    }, [programs]);

    const [major, setMajor] = useState(old.major ?? applicant?.major ?? '');
    const [programLevel, setProgramLevel] = useState(old.program_level ?? applicant?.programLevel ?? '');
    const [password, setPassword] = useState(old.password ?? '');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');

    const handleGeneratePassword = () => {
        const pw = generatePassword();
        setPassword(pw);
        setPasswordConfirmation(pw);
    };

    return (
        <div className="max-w-3xl mx-auto space-y-6">

            <div>
                <h1 className="font-heading text-lg font-semibold text-brandNavy dark:text-white">
                    {applicant ? 'Activate student account' : 'New student account'}
                </h1>
                <p className="text-sm text-brandNavy/50 dark:text-slate-400 mt-0.5">
                    {applicant
                        ? 'Assign login credentials and academic details for the verified applicant below.'
                        : 'Manually register a student and initialize their clearance record.'}
                </p>
            </div>

            {applicant && (
                <div className="flex items-center gap-4 p-4 bg-brandGreen/8 dark:bg-brandGreen/10 border border-brandGreen/25 rounded-lg">
                    <div className="w-10 h-10 rounded-full bg-brandGreen/15 flex items-center justify-center text-brandGreen font-semibold text-sm flex-shrink-0">
                        {applicant.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="flex-1 min-w-0">
                        <p className="text-xs font-medium text-brandGreen">From admission queue</p>
                        <p className="font-medium text-brandNavy dark:text-white text-sm">{applicant.name}</p>
                        <p className="text-xs text-brandNavy/50 dark:text-slate-400">{applicant.email} &nbsp;·&nbsp; {applicant.major ?? '—'}</p>
                    </div>
                    <span className="ui-badge-outline border-brandGold text-brandGold flex-shrink-0">Verified</span>
                </div>
            )}

            <form
                action={actionUrl}
                method="POST"
                className="space-y-6"
                onSubmit={(e) => lockSubmit(e.currentTarget)}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                {applicant && <input type="hidden" name="applicant_id" value={applicant.id} />}

                {/* Account credentials */}
                <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800">
                        <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                            <i className="fa-solid fa-key text-brandGreen mr-2" />Login credentials
                        </h3>
                    </div>
                    <div className="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label className={labelClass}>Student ID <span className="text-red-500">*</span></label>
                            <input
                                type="text" name="login_id" defaultValue={old.login_id} required placeholder="e.g. 2026-10001"
                                className={`${inputClass} font-mono`}
                            />
                            <p className="text-xs text-brandNavy/40 dark:text-slate-500 mt-1">Used as login username.</p>
                        </div>
                        <div>
                            <label className={labelClass}>Password <span className="text-red-500">*</span></label>
                            <div className="relative">
                                <input
                                    type="text" name="password" required placeholder="Min. 8 characters"
                                    value={password} onChange={(e) => setPassword(e.target.value)}
                                    className={`${inputClass} font-mono pr-20`}
                                />
                                <button type="button" onClick={handleGeneratePassword} className="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-brandGreen hover:text-emerald-700 transition-colors">Generate</button>
                            </div>
                        </div>
                        <div className="sm:col-span-2">
                            <label className={labelClass}>Confirm password <span className="text-red-500">*</span></label>
                            <input
                                type="text" name="password_confirmation" required placeholder="Re-enter password"
                                value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)}
                                className={`${inputClass} font-mono`}
                            />
                        </div>
                    </div>
                </div>

                {/* Academic info */}
                <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800">
                        <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                            <i className="fa-solid fa-graduation-cap text-brandGreen mr-2" />Academic information
                        </h3>
                    </div>
                    <div className="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label className={labelClass}>Program / course <span className="text-red-500">*</span></label>
                            <select
                                name="major" required value={major}
                                onChange={(e) => {
                                    setMajor(e.target.value);
                                    setProgramLevel(programLevelByCode[e.target.value] ?? '');
                                }}
                                className={inputClass}
                            >
                                <option value="" disabled>Select program</option>
                                {programs.map((group) => (
                                    <optgroup key={group.level} label={group.level.charAt(0).toUpperCase() + group.level.slice(1)}>
                                        {group.programs.map((program) => (
                                            <option key={program.code} value={program.code}>
                                                {program.code} — {program.name}{program.isEnrollable ? '' : ' (manual enrollment)'}
                                            </option>
                                        ))}
                                    </optgroup>
                                ))}
                            </select>
                            <input type="hidden" name="program_level" value={programLevel} />
                        </div>
                        <div>
                            <label className={labelClass}>Year level <span className="text-red-500">*</span></label>
                            <select
                                name="year_level" required defaultValue={old.year_level ?? ''}
                                className={inputClass}
                            >
                                <option value="" disabled>Select year level</option>
                                {YEAR_LEVELS.map((level) => <option key={level} value={level}>{level}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className={labelClass}>Section <span className="text-brandNavy/30 dark:text-slate-600 font-normal">(optional)</span></label>
                            <input
                                type="text" name="section" defaultValue={old.section} placeholder="e.g. BSOA-1A"
                                className={inputClass}
                            />
                        </div>
                        <div>
                            <label className={labelClass}>Applicant type</label>
                            <select
                                name="applicant_type" defaultValue={old.applicant_type ?? applicant?.applicantType ?? ''}
                                className={inputClass}
                            >
                                <option value="">Not specified</option>
                                {APPLICANT_TYPES.map((t) => <option key={t.value} value={t.value}>{t.label}</option>)}
                            </select>
                        </div>
                    </div>
                </div>

                {/* Personal info */}
                <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
                    <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800">
                        <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                            <i className="fa-solid fa-user text-brandGreen mr-2" />Personal information
                        </h3>
                    </div>
                    <div className="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div className="sm:col-span-2">
                            <label className={labelClass}>Full name <span className="text-red-500">*</span></label>
                            <input
                                type="text" name="name" defaultValue={old.name ?? applicant?.name ?? ''} required
                                placeholder="Last Name, First Name Middle Name" readOnly={!!applicant}
                                className={`${inputClass} ${applicant ? readOnlyClass : ''}`}
                            />
                        </div>
                        <div>
                            <label className={labelClass}>Email address <span className="text-red-500">*</span></label>
                            <input
                                type="email" name="email" defaultValue={old.email ?? applicant?.email ?? ''} required
                                placeholder="student@email.com" readOnly={!!applicant}
                                className={`${inputClass} ${applicant ? readOnlyClass : ''}`}
                            />
                        </div>
                        <div>
                            <label className={labelClass}>Contact number</label>
                            <input
                                type="text" name="contact_number" defaultValue={old.contact_number ?? applicant?.contactNumber ?? ''} placeholder="09XX-XXX-XXXX"
                                className={inputClass}
                            />
                        </div>
                        <div>
                            <label className={labelClass}>Date of birth</label>
                            <input
                                type="date" name="date_of_birth" defaultValue={old.date_of_birth ?? applicant?.dateOfBirth ?? ''}
                                className={inputClass}
                            />
                        </div>
                        <div>
                            <label className={labelClass}>Sex</label>
                            <select
                                name="sex" defaultValue={old.sex ?? applicant?.sex ?? ''}
                                className={inputClass}
                            >
                                <option value="">Not specified</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div className="sm:col-span-2">
                            <label className={labelClass}>Home address</label>
                            <input
                                type="text" name="address" defaultValue={old.address ?? applicant?.address ?? ''} placeholder="Street, Barangay, City/Municipality, Province"
                                className={inputClass}
                            />
                        </div>
                    </div>
                </div>

                {/* Submit */}
                <div className="flex items-center justify-between gap-4 pb-6">
                    <p className="text-xs text-brandNavy/40 dark:text-slate-500">Fields marked <span className="text-red-500">*</span> are required. A clearance record will be automatically created for this student.</p>
                    <button
                        type="submit"
                        className="ui-btn-primary flex-shrink-0 bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i className="fa-solid fa-user-plus" />Create student account
                    </button>
                </div>
            </form>
        </div>
    );
}
