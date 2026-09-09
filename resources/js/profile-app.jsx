import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';

function parseContext(raw) {
    try {
        return JSON.parse(raw ?? '{}');
    } catch {
        return {};
    }
}

function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

function FieldError({ errors, field }) {
    if (!errors?.[field]?.length) return null;
    return <p className="text-red-500 text-xs mt-1">{errors[field][0]}</p>;
}

function ProfileApp({ context }) {
    const { user = {}, errors = {}, updateUrl, csrfToken } = context;

    return (
        <div className="space-y-5">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
                <p className="text-[10px] uppercase text-brandNavy/40 dark:text-slate-500 mb-2 font-bold tracking-wider">
                    Official Record
                </p>
                <p className="text-sm font-bold text-brandNavy dark:text-slate-100">{user.name}</p>
                <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-0.5">{user.loginId ?? '—'}</p>
                <p className="text-[11px] text-brandNavy/40 dark:text-slate-500 mt-2">
                    Your name and student number are managed by the Registrar's Office. Contact them if these need
                    correcting.
                </p>
            </div>

            <form
                action={updateUrl}
                method="POST"
                className="space-y-4 bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6"
                onSubmit={(e) => lockSubmit(e.currentTarget, 'Saving…')}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <input type="hidden" name="_method" value="PUT" />

                <div>
                    <label className="block text-[11px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        defaultValue={user.email}
                        className="w-full text-sm bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-100 border border-brandNavy/15 dark:border-slate-700 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brandGreen/30 transition-colors"
                    />
                    <FieldError errors={errors} field="email" />
                </div>

                <div>
                    <label className="block text-[11px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Contact Number
                    </label>
                    <input
                        type="text"
                        name="contact_number"
                        defaultValue={user.contactNumber}
                        placeholder="09XXXXXXXXX"
                        className="w-full text-sm bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-100 border border-brandNavy/15 dark:border-slate-700 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brandGreen/30 transition-colors"
                    />
                    <FieldError errors={errors} field="contact_number" />
                </div>

                <div>
                    <label className="block text-[11px] font-bold text-brandNavy/50 dark:text-slate-400 uppercase tracking-wider mb-1">
                        Address
                    </label>
                    <textarea
                        name="address"
                        rows={2}
                        defaultValue={user.address}
                        className="w-full text-sm bg-lightBg dark:bg-darkBg text-brandNavy dark:text-slate-100 border border-brandNavy/15 dark:border-slate-700 rounded-xl px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-brandGreen/30 transition-colors"
                    />
                    <FieldError errors={errors} field="address" />
                </div>

                <button
                    type="submit"
                    className="w-full px-5 py-3 bg-brandNavy hover:bg-brandGreen text-white text-xs font-bold rounded-xl transition-colors"
                >
                    Save Changes
                </button>
            </form>
        </div>
    );
}

const el = document.getElementById('profile-root');
if (el) {
    const context = parseContext(el.dataset.context);
    createRoot(el).render(
        <ErrorBoundary>
            <ProfileApp context={context} />
        </ErrorBoundary>
    );
}
