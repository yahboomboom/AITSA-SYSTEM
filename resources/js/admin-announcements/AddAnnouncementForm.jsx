import React, { useRef, useState } from 'react';

function lockSubmit(form, busyLabel) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = busyLabel;
    }
    return true;
}

export default function AddAnnouncementForm({ csrfToken, actionUrl }) {
    const [preview, setPreview] = useState(null); // null | { url, name, isImage }
    const fileInputRef = useRef(null);

    const pickFile = (e) => {
        const file = e.target.files[0];
        if (preview) URL.revokeObjectURL(preview.url);
        if (!file) { setPreview(null); return; }
        setPreview({ url: URL.createObjectURL(file), name: file.name, isImage: file.type.startsWith('image/') });
    };

    const clearFile = () => {
        if (preview) URL.revokeObjectURL(preview.url);
        setPreview(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm p-6">
            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white mb-4">Post announcement</h3>
            <form
                action={actionUrl}
                method="POST"
                encType="multipart/form-data"
                className="space-y-3"
                onSubmit={(e) => lockSubmit(e.currentTarget, 'Posting…')}
            >
                <input type="hidden" name="_token" value={csrfToken} />
                <input
                    type="text" name="title" required maxLength={150} placeholder="Title, e.g. Enrollment period open"
                    className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 transition-colors"
                />
                <textarea
                    name="body" required rows={3} placeholder="Announcement details…"
                    className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-brandGreen/40 transition-colors resize-none"
                />
                <div>
                    <label className="block text-xs font-medium text-brandNavy/60 dark:text-slate-400 mb-1">
                        Attach an image or PDF (optional)
                    </label>
                    <input
                        ref={fileInputRef} onChange={pickFile}
                        type="file" name="attachment" accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                        className="w-full text-sm text-brandNavy/70 dark:text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-brandNavy/5 dark:file:bg-slate-800 file:text-brandNavy dark:file:text-slate-200 hover:file:bg-brandNavy/10 dark:hover:file:bg-slate-700 transition-colors"
                    />
                    {preview && (
                        <div className="mt-2.5 flex items-start gap-3 bg-lightBg dark:bg-slate-900 border border-brandNavy/10 dark:border-slate-700 rounded p-2.5">
                            {preview.isImage ? (
                                <img src={preview.url} alt={preview.name} className="max-h-24 rounded border border-brandNavy/10 dark:border-slate-700 object-contain" />
                            ) : (
                                <div className="w-12 h-12 rounded bg-brandNavy/5 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                                    <i className="fa-solid fa-file-pdf text-brandGreen text-lg" />
                                </div>
                            )}
                            <div className="min-w-0 flex-1">
                                <p className="text-xs font-medium text-brandNavy dark:text-slate-200 truncate">{preview.name}</p>
                                <button type="button" onClick={clearFile}
                                    className="text-xs text-red-500/70 hover:text-red-600 underline underline-offset-2 mt-1">
                                    Remove
                                </button>
                            </div>
                        </div>
                    )}
                </div>
                <button type="submit" className="ui-btn-primary bg-brandGreen hover:bg-brandGreen/90 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Post
                </button>
            </form>
        </div>
    );
}
