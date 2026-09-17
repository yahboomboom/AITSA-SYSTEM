import React, { useState } from 'react';
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

function SignatureApp({ context }) {
    const { signaturePath, hasSignature, updateUrl, csrfToken, errors = {} } = context;
    const [fileName, setFileName] = useState(null);

    const defaultLabel = hasSignature ? 'Click to re-upload a new signature image' : 'Click to upload signature image';

    return (
        <div className="space-y-5">
            {hasSignature ? (
                <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6">
                    <p className="text-[10px] uppercase text-brandNavy/40 dark:text-slate-500 mb-2 font-bold tracking-wider">
                        Current Signature
                    </p>
                    <img src={signaturePath} alt="Signature" className="h-16 bg-white rounded p-1 border border-brandNavy/10" />
                    <p className="text-[11px] text-brandNavy/50 dark:text-slate-400 mt-3">
                        You already have a signature saved. Uploading a new one below will replace it.
                    </p>
                </div>
            ) : (
                <div className="p-3.5 rounded-lg bg-brandGold/10 border border-brandGold/25 text-brandGold text-xs font-semibold">
                    <i className="fa-solid fa-triangle-exclamation mr-2" />
                    You haven't uploaded a signature yet. Please upload one below.
                </div>
            )}

            <form
                action={updateUrl}
                method="POST"
                encType="multipart/form-data"
                className="space-y-4 bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6"
                onSubmit={(e) => lockSubmit(e.currentTarget, 'Uploading…')}
            >
                <input type="hidden" name="_token" value={csrfToken} />

                <label className="block cursor-pointer">
                    <div className="w-full text-xs border-2 border-dashed border-brandNavy/20 dark:border-slate-700 rounded-xl p-6 bg-lightBg dark:bg-darkBg hover:bg-brandNavy/5 dark:hover:bg-slate-800 text-center transition-colors">
                        <i className="fa-solid fa-signature text-brandNavy/30 dark:text-slate-600 text-xl mb-2" />
                        <p className="font-semibold text-brandNavy dark:text-slate-200">{fileName ?? defaultLabel}</p>
                        <p className="text-brandNavy/40 dark:text-slate-500 mt-1">PNG or JPG, max 1MB</p>
                    </div>
                    <input
                        type="file"
                        name="signature"
                        accept="image/png,image/jpeg"
                        required
                        className="hidden"
                        onChange={(e) => setFileName(e.target.files[0]?.name ?? null)}
                    />
                </label>
                {errors?.signature?.length ? <p className="text-red-500 text-xs">{errors.signature[0]}</p> : null}

                <button
                    type="submit"
                    className="w-full px-5 py-3 bg-brandNavy hover:bg-brandGreen text-white text-xs font-bold rounded-xl transition-colors"
                >
                    {hasSignature ? 'Re-upload Signature' : 'Save Signature'}
                </button>
            </form>
        </div>
    );
}

const el = document.getElementById('signature-root');
if (el) {
    const context = parseContext(el.dataset.context);
    createRoot(el).render(
        <ErrorBoundary>
            <SignatureApp context={context} />
        </ErrorBoundary>
    );
}
