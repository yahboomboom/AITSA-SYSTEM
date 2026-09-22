export default function StatusBadge({ status }) {
    if (status === 'Approved') {
        return <span className="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-brandGreen/10 text-brandGreen border border-brandGreen/20 uppercase tracking-wide">Approved</span>;
    }
    if (status === 'Hold') {
        return <span className="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-red-500/10 text-red-500 border border-red-500/20 uppercase tracking-wide">Hold</span>;
    }
    return <span className="inline-block px-1.5 py-0.5 text-[9px] font-bold rounded bg-brandGold/10 text-brandGold border border-brandGold/20 uppercase tracking-wide">Pending</span>;
}
