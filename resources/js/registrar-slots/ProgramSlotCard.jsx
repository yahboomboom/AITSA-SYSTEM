import { useState } from 'react';

// The Chair's 1st-year blocks for this term vs. the slots the Registrar opened.
// Amber when there are no blocks yet or more slots than block seats, since
// students past the block seats can't be placed when they enroll.
export function blockGap(program) {
    const blocks = program.blocks ?? { count: 0, seats: 0 };
    return { ...blocks, over: Math.max(0, program.totalSlots - blocks.seats) };
}

function BlocksLine({ blocks, over }) {
    if (blocks.count === 0) {
        return <p className="text-xs font-semibold text-brandGold"><i className="fa-solid fa-triangle-exclamation mr-1" />No 1st-year blocks from Chair yet</p>;
    }
    return (
        <p className="text-xs text-brandNavy/50 dark:text-slate-400">
            <i className="fa-solid fa-table-cells-large mr-1" />Chair: {blocks.count} {blocks.count === 1 ? 'block' : 'blocks'} · {blocks.seats} seats
            {over > 0 && <span className="font-semibold text-brandGold"> · {over} over</span>}
        </p>
    );
}

// One program's slots: usage bar, "N left", the Chair's block seats, and an
// inline editor for the total.
export default function ProgramSlotCard({ program, csrfToken }) {
    const [editing, setEditing] = useState(false);
    const [total, setTotal] = useState(program.totalSlots);
    const [saving, setSaving] = useState(false);

    const used = program.totalSlots > 0 ? Math.min(100, Math.round((program.taken / program.totalSlots) * 100)) : 100;
    const full = program.slotsLeft <= 0;
    const almost = !full && used >= 85;
    const tone = full ? 'red' : almost ? 'amber' : 'green';
    const barColor = { red: 'bg-red-500', amber: 'bg-brandGold', green: 'bg-brandGreen' }[tone];
    const leftColor = { red: 'text-red-600 dark:text-red-400', amber: 'text-brandGold', green: 'text-brandGreen dark:text-[#7FD39A]' }[tone];

    const gap = blockGap({ ...program, totalSlots: Number(total) || 0 });
    const warn = !editing && (gap.count === 0 || gap.over > 0);

    const cancel = () => { setEditing(false); setTotal(program.totalSlots); };

    return (
        <div className={`rounded-lg border p-4 bg-white dark:bg-panelDark shadow-sm ${editing ? 'border-brandGreen ring-1 ring-brandGreen/40' : full ? 'border-red-300 dark:border-red-500/40' : (almost || warn) ? 'border-brandGold/50' : 'border-brandNavy/10 dark:border-slate-700'}`}>
            <p className="font-semibold text-brandNavy dark:text-white leading-snug">{program.programName}</p>

            {editing ? (
                <form action={program.updateUrl} method="POST" onSubmit={() => setSaving(true)} className="mt-3 space-y-3">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <label className="block text-xs text-brandNavy/60 dark:text-slate-400">
                        Total slots
                        <input type="number" name="total_slots" min="1" required autoFocus value={total} onChange={(e) => setTotal(e.target.value)}
                            className="mt-1 w-full bg-lightBg dark:bg-slate-900/60 border border-brandNavy/10 dark:border-slate-700 rounded px-2 py-1.5 text-sm text-brandNavy dark:text-slate-200" />
                    </label>
                    <BlocksLine blocks={gap} over={gap.over} />
                    {gap.seats > 0 && Number(total) !== gap.seats && (
                        <button type="button" onClick={() => setTotal(gap.seats)} className="text-xs text-brandGreen dark:text-[#7FD39A] underline underline-offset-2">
                            Match block seats ({gap.seats})
                        </button>
                    )}
                    {Number(total) > 0 && Number(total) < program.taken && (
                        <p className="text-xs font-semibold text-brandGold">Below the {program.taken} already taken</p>
                    )}
                    <div className="flex gap-2">
                        <button type="submit" disabled={saving} className="ui-btn-primary bg-brandGreen hover:bg-[#247039] text-white disabled:opacity-50">
                            {saving ? 'Saving…' : 'Save'}
                        </button>
                        <button type="button" onClick={cancel} disabled={saving} className="ui-btn-primary bg-transparent text-brandNavy/60 dark:text-slate-400 hover:bg-brandNavy/5 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                    </div>
                </form>
            ) : (
                <>
                    <p className="mt-2">
                        <span className="text-2xl font-bold text-brandNavy dark:text-white">{program.taken}</span>
                        <span className="text-xs text-brandNavy/50 dark:text-slate-400"> / {program.totalSlots} taken</span>
                    </p>
                    <div className="h-2 rounded-full bg-brandNavy/10 dark:bg-slate-700 overflow-hidden my-2">
                        <div className={`h-2 rounded-full ${barColor}`} style={{ width: `${used}%` }} />
                    </div>
                    <div className="flex items-end justify-between gap-2">
                        <div className="space-y-1">
                            <p className={`text-xs font-semibold ${leftColor}`}>{full ? 'Full' : `${program.slotsLeft} left`}{almost ? ' · almost full' : ''}</p>
                            <BlocksLine blocks={gap} over={gap.over} />
                        </div>
                        <button type="button" onClick={() => setEditing(true)}
                            className="ui-btn-primary bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy/70 dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800">
                            <i className="fa-solid fa-pen" />Edit
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}
