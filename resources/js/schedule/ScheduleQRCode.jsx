import { useEffect, useRef, useState } from 'react';

const ORDINAL_SEMESTER = { 1: '1st', 2: '2nd' };
const RULE = '------------------------------';

// The text a phone shows when it scans the code: a fixed template so every
// student's schedule reads the same way. Kept to plain ASCII (see below).
export function scheduleQrText({ subjects, studentName, studentId, studentProgram, schoolYear, semester }) {
    const semesterLabel = ORDINAL_SEMESTER[semester] ?? semester;
    const units = subjects.reduce((sum, s) => sum + (Number(s.units) || 0), 0);

    const lines = [
        'AITSA CLASS SCHEDULE',
        RULE,
        `Name    : ${studentName}`,
        `ID No.  : ${studentId}`,
        `Program : ${studentProgram}`,
        `Term    : ${semesterLabel} Sem, A.Y. ${schoolYear}`,
        RULE,
    ];
    if (subjects.length === 0) {
        lines.push('No enrolled subjects yet.');
    }
    subjects.forEach((s, i) => {
        lines.push(`${i + 1}. ${s.code} - ${s.desc}`);
        lines.push(`   ${s.days}  ${s.time}`);
        lines.push(`   ${s.room || 'TBA'} | ${s.type || 'Face-to-Face'}`);
    });
    lines.push(RULE, `Subjects: ${subjects.length}   Units: ${units}`);

    // qrcode.min.js's mode-8bit-byte encoder reuses one scratch array across
    // characters without resetting it, so any non-ASCII byte (e.g. the en-dash
    // in `time`) leaks stale bytes into every following ASCII character and
    // wildly inflates the encoded length, throwing "code length overflow" for
    // even a handful of subjects. Normalize to plain ASCII before encoding.
    return lines.join('\n').replace(/[^\x00-\x7F]/g, '-');
}

function QrCanvas({ text, size }) {
    const ref = useRef(null);

    useEffect(() => {
        if (!ref.current || typeof window.QRCode === 'undefined') return;
        ref.current.innerHTML = '';
        try {
            new window.QRCode(ref.current, {
                text,
                width: size,
                height: size,
                colorDark: '#1D506D',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel.M,
            });
        } catch {
            ref.current.innerHTML = '<p class="text-[10px] text-brandNavy/40 p-1 text-center">QR unavailable</p>';
        }
    }, [text, size]);

    return <div ref={ref} />;
}

// Small QR beside the page title; tap it to show a large one for scanning.
export default function ScheduleQRCode(props) {
    const [open, setOpen] = useState(false);
    const text = scheduleQrText(props);

    return (
        <>
            <button type="button" onClick={() => setOpen(true)} title="Tap to enlarge and scan"
                className="shrink-0 p-1.5 bg-white rounded-md border border-brandNavy/15 shadow-sm hover:ring-2 hover:ring-brandGreen/40 transition">
                <QrCanvas text={text} size={64} />
            </button>
            {open && (
                <div className="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 print:hidden" onClick={() => setOpen(false)}>
                    <div className="bg-white dark:bg-panelDark rounded-lg shadow-lg p-5 text-center" onClick={(e) => e.stopPropagation()}>
                        <div className="p-3 bg-white rounded inline-block"><QrCanvas text={text} size={240} /></div>
                        <p className="text-xs text-brandNavy/50 dark:text-slate-400 mt-2">Scan to view schedule</p>
                        <button type="button" onClick={() => setOpen(false)}
                            className="ui-btn-primary w-full justify-center mt-3 bg-brandGreen hover:bg-[#247039] text-white">Close</button>
                    </div>
                </div>
            )}
        </>
    );
}
