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
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="bg-white dark:bg-panelDark rounded-2xl shadow-2xl max-w-sm w-full p-6">
                <div className="w-12 h-12 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center mb-4">
                    <i className="fa-solid fa-triangle-exclamation text-lg" />
                </div>
                <h3 className="text-base font-black text-brandNavy dark:text-white mb-1">Delete student account?</h3>
                <p className="text-xs text-brandNavy/60 dark:text-slate-400 mb-4">
                    This permanently deletes <strong className="text-brandNavy dark:text-white">{student.name}</strong>'s
                    account and cannot be undone. Type the student's name to confirm.
                </p>
                <input
                    type="text"
                    value={typedName}
                    onChange={(e) => setTypedName(e.target.value)}
                    placeholder="Type the student's full name"
                    className="w-full border border-brandNavy/15 dark:border-slate-700 bg-white dark:bg-slate-900/60 rounded-xl px-4 py-2.5 text-sm text-brandNavy dark:text-slate-200 focus:outline-none focus:border-red-500 transition-colors mb-4"
                />
                <form ref={formRef} action={student.deleteUrl} method="POST" className="hidden">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="_method" value="DELETE" />
                </form>
                <div className="flex gap-3">
                    <button
                        type="button" onClick={onClose}
                        className="flex-1 py-2.5 rounded-xl text-sm font-bold text-brandNavy/70 dark:text-slate-300 bg-brandNavy/5 dark:bg-slate-800 hover:bg-brandNavy/10 transition-colors"
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
                        className="flex-1 py-2.5 rounded-xl text-sm font-bold text-white bg-red-500 hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        {submitting ? 'Deleting…' : 'Delete'}
                    </button>
                </div>
            </div>
        </div>
    );
}
