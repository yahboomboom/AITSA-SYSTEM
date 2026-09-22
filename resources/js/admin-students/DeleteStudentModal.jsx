import { useRef, useState } from 'react';

export default function DeleteStudentModal({ student, csrfToken, onClose }) {
    const [typedName, setTypedName] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const formRef = useRef(null);

    if (!student) {
        return null;
    }

    const canDelete = typedName.trim() === student.name;

    return (
        <div
            className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-700 rounded-lg shadow-lg w-full max-w-sm overflow-hidden">
                <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-white">Delete student account?</span>
                    <button type="button" onClick={onClose} className="text-brandNavy/40 dark:text-slate-500 hover:text-brandNavy dark:hover:text-white">
                        <i className="fa-solid fa-xmark" />
                    </button>
                </div>
                <div className="p-6 space-y-4">
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400">
                        This permanently deletes <strong className="text-brandNavy dark:text-white">{student.name}</strong>'s
                        account and cannot be undone. Type the student's name to confirm.
                    </p>
                    <input
                        type="text"
                        value={typedName}
                        onChange={(e) => setTypedName(e.target.value)}
                        placeholder="Type the student's full name"
                        className="w-full bg-lightBg dark:bg-slate-900 text-sm text-brandNavy dark:text-slate-200 placeholder-brandNavy/30 dark:placeholder-slate-600 border border-brandNavy/10 dark:border-slate-700 rounded px-3 py-2 outline-none focus:border-red-500 transition-colors"
                    />
                    <form ref={formRef} action={student.deleteUrl} method="POST" className="hidden">
                        <input type="hidden" name="_token" value={csrfToken} />
                        <input type="hidden" name="_method" value="DELETE" />
                    </form>
                    <div className="flex gap-2">
                        <button
                            type="button" onClick={onClose}
                            className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            disabled={!canDelete || submitting}
                            onClick={() => {
                                setSubmitting(true);
                                formRef.current.submit();
                            }}
                            className="ui-btn-primary flex-1 justify-center bg-red-500 hover:bg-red-600 text-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            {submitting ? 'Deleting…' : 'Delete'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
