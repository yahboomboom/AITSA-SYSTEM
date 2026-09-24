import { useState } from 'react';

export default function StudentDiscountTable({ students, discountTypes, csrfToken, onAssigned }) {
    const [pendingId, setPendingId] = useState(null);
    const [errorId, setErrorId] = useState(null);
    const [savedId, setSavedId] = useState(null);

    const handleSubmit = async (e, student) => {
        e.preventDefault();
        const form = e.currentTarget;
        const value = new FormData(form).get('discount_type_id') || '';

        setPendingId(student.id);
        setErrorId(null);
        try {
            const res = await fetch(student.assignUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({ discount_type_id: value }),
            });

            if (!res.ok) {
                setErrorId(student.id);
                return;
            }

            const data = await res.json();
            onAssigned(data.id, data.discountTypeId);
            setSavedId(student.id);
            setTimeout(() => setSavedId((id) => (id === student.id ? null : id)), 2000);
        } catch {
            setErrorId(student.id);
        } finally {
            setPendingId(null);
        }
    };

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg shadow-sm overflow-hidden">
            <div className="p-5 border-b border-brandNavy/10 dark:border-slate-800">
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white"><i className="fa-solid fa-user-tag mr-2 text-brandNavy dark:text-slate-300" />Student discounts</h2>
            </div>
            <div className="overflow-x-auto">
                <table className="ui-table table-fixed w-full">
                    <colgroup>
                        <col className="w-[28%]" />
                        <col className="w-[22%]" />
                        <col className="w-[50%]" />
                    </colgroup>
                    <thead>
                        <tr className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/50 dark:text-slate-400">
                            <th className="border-brandNavy/8 dark:border-slate-800">Student</th>
                            <th className="border-brandNavy/8 dark:border-slate-800">Program / Year</th>
                            <th className="border-brandNavy/8 dark:border-slate-800" colSpan={2}>Discount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {students.length === 0 && (
                            <tr>
                                <td colSpan={4} className="border-brandNavy/8 dark:border-slate-800 py-10 text-center text-brandNavy/40 dark:text-slate-500">No student accounts found.</td>
                            </tr>
                        )}
                        {students.map((student) => {
                            const assignable = discountTypes.filter((type) =>
                                type.isActive || type.id === student.discountTypeId
                            );
                            const isPending = pendingId === student.id;

                            return (
                                <tr key={student.id}>
                                    <td className="border-brandNavy/8 dark:border-slate-800">
                                        <span className="font-medium text-brandNavy dark:text-white block">{student.name}</span>
                                        <span className="font-mono text-brandNavy/60 dark:text-slate-400">{student.loginId ?? '—'}</span>
                                    </td>
                                    <td className="border-brandNavy/8 dark:border-slate-800 text-brandNavy/60 dark:text-slate-400">{student.major ?? '—'} · {student.yearLevel ?? '—'}</td>
                                    <td className="border-brandNavy/8 dark:border-slate-800" colSpan={2}>
                                        <form
                                            className="flex items-center gap-2"
                                            onSubmit={(e) => handleSubmit(e, student)}
                                        >
                                            <select
                                                name="discount_type_id"
                                                defaultValue={student.discountTypeId ?? ''}
                                                disabled={isPending}
                                                className="flex-1 min-w-0 max-w-56 bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-800 text-brandNavy dark:text-slate-200 px-3 py-2 rounded focus:outline-none focus:border-brandGreen disabled:opacity-50 truncate"
                                            >
                                                <option value="">— None —</option>
                                                {assignable.map((type) => (
                                                    <option key={type.id} value={type.id}>
                                                        {type.name} ({type.percent}%){!type.isActive ? ' — inactive' : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <button
                                                type="submit"
                                                disabled={isPending}
                                                className="ui-btn-primary bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex-shrink-0"
                                            >
                                                {isPending ? 'Saving…' : 'Save'}
                                            </button>
                                            {savedId === student.id && (
                                                <i className="fa-solid fa-circle-check text-brandGreen flex-shrink-0" title="Saved" />
                                            )}
                                            {errorId === student.id && (
                                                <span className="text-red-600 text-xs flex-shrink-0">Failed to save.</span>
                                            )}
                                        </form>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </div>
    );
}