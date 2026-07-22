export default function CorHeader({ studentId, studentProgram }) {
    return (
        <div className="print-area">
            <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-2 mb-1">
                <div>
                    <h1 className="text-3xl font-black text-brandNavy dark:text-white">Certificate of Registration</h1>
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1">
                        Academic Year 2025–2026 &nbsp;—&nbsp; 1st Semester
                        &nbsp;<span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brandGreen/10 text-brandGreen text-[10px] font-bold border border-brandGreen/20 uppercase tracking-wider">
                            <i className="fa-solid fa-circle-check text-[8px]" />Enrolled
                        </span>
                    </p>
                </div>
                <div className="text-left md:text-right text-xs text-brandNavy/50 dark:text-slate-500">
                    <p className="font-mono font-bold">Student No: {studentId}</p>
                    <p>{studentProgram}</p>
                </div>
            </div>
        </div>
    );
}
