import ProgramSlotCard, { blockGap } from './ProgramSlotCard';

const LEVELS = [
    ['BACHELOR', "Bachelor's degrees"],
    ['ASSOCIATE', 'Associate degrees'],
    ['TESDA', 'TESDA programs'],
];

export default function SlotsTab({ curricula, schoolYear, csrfToken }) {
    const total = curricula.reduce((sum, c) => sum + c.totalSlots, 0);
    const taken = curricula.reduce((sum, c) => sum + c.taken, 0);
    const full = curricula.filter((c) => c.slotsLeft <= 0).length;
    const almost = curricula.filter((c) => c.slotsLeft > 0 && c.totalSlots > 0 && c.taken / c.totalSlots >= 0.85).length;
    const mismatched = curricula.filter((c) => { const g = blockGap(c); return g.count === 0 || g.over > 0; }).length;
    const used = total > 0 ? Math.round((taken / total) * 100) : 0;

    return (
        <div className="space-y-6">
            <div className="rounded-lg border border-brandNavy/10 dark:border-slate-700 bg-white dark:bg-panelDark shadow-sm p-5 flex flex-wrap items-center gap-x-8 gap-y-3">
                <div>
                    <p className="text-xs text-brandNavy/50 dark:text-slate-400">A.Y. {schoolYear}</p>
                    <p className="text-2xl font-bold text-brandNavy dark:text-white">
                        {taken.toLocaleString()} <span className="text-sm font-medium text-brandNavy/50 dark:text-slate-400">/ {total.toLocaleString()} slots taken</span>
                    </p>
                </div>
                <div className="flex-1 min-w-[10rem]">
                    <div className="h-2 rounded-full bg-brandNavy/10 dark:bg-slate-700 overflow-hidden">
                        <div className="h-2 rounded-full bg-brandGreen" style={{ width: `${used}%` }} />
                    </div>
                    <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-1.5">
                        {full} full · {almost} almost full
                        {mismatched > 0 && <span className="font-semibold text-brandGold"> · {mismatched} need Chair blocks</span>}
                    </p>
                </div>
            </div>

            {LEVELS.map(([level, label]) => {
                const programs = curricula.filter((c) => String(c.level).toUpperCase() === level);
                if (programs.length === 0) return null;
                return (
                    <section key={level}>
                        <h2 className="text-[11px] font-bold uppercase tracking-wider text-brandNavy/50 dark:text-slate-400 mb-2">{label}</h2>
                        <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                            {programs.map((program) => <ProgramSlotCard key={program.id} program={program} csrfToken={csrfToken} />)}
                        </div>
                    </section>
                );
            })}
        </div>
    );
}
