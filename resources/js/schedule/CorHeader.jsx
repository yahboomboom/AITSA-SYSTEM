const ORDINAL_SEMESTER = { 1: '1st', 2: '2nd' };

export default function CorHeader({ studentId, studentProgram, schoolYear, semester }) {
    const semesterLabel = ORDINAL_SEMESTER[semester] ?? semester;

    return (
        <div className="print-area">
            <div className="flex flex-col md:flex-row md:items-end md:justify-between gap-2 mb-1">
                <div>
                    <h1 className="font-heading text-2xl font-semibold text-brandNavy dark:text-white">Certificate of Registration</h1>
                    <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1 flex items-center gap-2">
                        <span>A.Y. {schoolYear} &middot; {semesterLabel} Semester</span>
                        <span className="ui-badge-outline border-brandGreen text-brandGreen">
                            <i className="fa-solid fa-circle-check text-[8px]" />Enrolled
                        </span>
                    </p>
                </div>
                <div className="text-left md:text-right text-xs text-brandNavy/50 dark:text-slate-500">
                    <p className="font-medium">Student No. {studentId}</p>
                    <p>{studentProgram}</p>
                </div>
            </div>
        </div>
    );
}
