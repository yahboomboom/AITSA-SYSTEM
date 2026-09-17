import { useEffect, useRef } from 'react';

const ORDINAL_SEMESTER = { 1: '1st', 2: '2nd' };

export default function ScheduleQRCode({ subjects, studentName, studentId, studentProgram, schoolYear, semester }) {
    const qrRef = useRef(null);
    const semesterLabel = ORDINAL_SEMESTER[semester] ?? semester;

    useEffect(() => {
        if (!qrRef.current || typeof window.QRCode === 'undefined') return;

        let text = `AITSA SCHEDULE\n`;
        text += `Student: ${studentName}\n`;
        text += `ID: ${studentId} | ${studentProgram}\n`;
        text += `AY ${schoolYear} | ${semesterLabel} Semester\n\n`;
        subjects.forEach((s) => {
            text += `${s.code} - ${s.desc}\n`;
            text += `${s.days} | ${s.time} | ${s.room} [${s.type}]\n\n`;
        });

        // qrcode.min.js's mode-8bit-byte encoder reuses one scratch array across
        // characters without resetting it, so any non-ASCII byte (e.g. the en-dash
        // in `time`) leaks stale bytes into every following ASCII character and
        // wildly inflates the encoded length, throwing "code length overflow" for
        // even a handful of subjects. Normalize to plain ASCII before encoding.
        text = text.trim().replace(/[^\x00-\x7F]/g, '-');

        qrRef.current.innerHTML = '';
        try {
            new window.QRCode(qrRef.current, {
                text,
                width: 176,
                height: 176,
                colorDark: '#0B3C5D',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel.M,
            });
        } catch {
            qrRef.current.innerHTML = '<p class="text-[10px] text-brandNavy/40 dark:text-slate-600 p-4 text-center">QR code unavailable for this schedule.</p>';
        }
    }, [subjects, studentName, studentId, studentProgram, schoolYear, semesterLabel]);

    return (
        <div className="flex justify-center print-area">
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm w-full max-w-sm">
                <div className="px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                    <span className="font-heading text-sm font-semibold text-brandNavy dark:text-slate-300">
                        <i className="fa-solid fa-qrcode mr-2 text-brandGreen" />Schedule QR code
                    </span>
                </div>
                <div className="p-6 flex flex-col items-center gap-4">
                    <div className="p-3 bg-white rounded border border-brandNavy/10 shadow-sm inline-block">
                        <div ref={qrRef} />
                    </div>
                    <div className="text-center space-y-1">
                        <p className="text-sm font-medium text-brandNavy dark:text-slate-200">{studentName}</p>
                        <p className="text-xs text-brandNavy/50 dark:text-slate-500">Scan to view schedule — A.Y. {schoolYear}, {semesterLabel} Sem</p>
                    </div>
                    <button onClick={() => window.print()} className="ui-btn-primary w-full justify-center bg-brandNavy hover:bg-brandGreen text-white transition-colors">
                        <i className="fa-solid fa-print" />Print or download COR
                    </button>
                </div>
            </div>
        </div>
    );
}
