import { useEffect, useMemo, useState } from 'react';

const SLOT_H = 34;
const GRID_START = 6 * 60;
const GRID_END = 21 * 60;
const NUM_SLOTS = (GRID_END - GRID_START) / 30;
const TOTAL_H = NUM_SLOTS * SLOT_H;
const TIME_W = 108;
const DAY_NAMES = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

const COLOR_MAP = {
    'bg-blue-600':    '#2563EB',
    'bg-emerald-600': '#059669',
    'bg-violet-600':  '#7C3AED',
    'bg-orange-500':  '#F97316',
    'bg-cyan-600':    '#0891B2',
    'bg-teal-600':    '#0D9488',
    'bg-rose-500':    '#F43F5E',
    'bg-amber-500':   '#F59E0B',
    'bg-brandGreen':  '#1D7A46',
    'bg-indigo-600':  '#4F46E5',
    'bg-pink-500':    '#EC4899',
};

function parseDays(dayStr) {
    const map = { Sun: 6, Sat: 5, Th: 3, M: 0, T: 1, W: 2, F: 4 };
    const priority = ['Sun', 'Sat', 'Th', 'M', 'T', 'W', 'F'];
    const result = [];
    let s = dayStr;
    while (s.length > 0) {
        let matched = false;
        for (const key of priority) {
            if (s.startsWith(key)) {
                result.push(map[key]);
                s = s.slice(key.length);
                matched = true;
                break;
            }
        }
        if (!matched) s = s.slice(1);
    }
    return result;
}

function parseTimePart(t) {
    const m = t.trim().match(/(\d+):(\d+)\s*(AM|PM)?/i);
    if (!m) return null;
    let h = parseInt(m[1], 10);
    const min = parseInt(m[2], 10);
    const ap = m[3] ? m[3].toUpperCase() : null;
    if (ap === 'PM' && h !== 12) h += 12;
    if (ap === 'AM' && h === 12) h = 0;
    return h * 60 + min;
}

function parseTimeRange(range) {
    const parts = range.split(/\s*[—–\-]\s*/);
    if (parts.length < 2) return null;

    let startStr = parts[0].trim();
    const endStr = parts[1].trim();

    if (!/AM|PM/i.test(startStr)) {
        const m = endStr.match(/(AM|PM)/i);
        if (m) startStr += ' ' + m[0];
    }

    const start = parseTimePart(startStr);
    const end = parseTimePart(endStr);
    if (start === null || end === null) return null;
    return { start, end };
}

function useIsDarkMode() {
    const [isDark, setIsDark] = useState(
        () => typeof document !== 'undefined' && document.documentElement.classList.contains('dark')
    );

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setIsDark(document.documentElement.classList.contains('dark'));
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        return () => observer.disconnect();
    }, []);

    return isDark;
}

export default function WeeklyTimetable({ subjects }) {
    const isDark = useIsDarkMode();

    const colors = useMemo(() => ({
        border: isDark ? '#2d3748' : '#e5e7eb',
        timeClr: isDark ? 'rgba(255,255,255,0.38)' : '#9ca3af',
        headClr: isDark ? 'rgba(255,255,255,0.85)' : '#111827',
        cellBg: isDark ? '#1e1e1e' : '#ffffff',
    }), [isDark]);

    const blocks = useMemo(() => {
        const result = [];
        subjects.forEach((subj) => {
            const tr = parseTimeRange(subj.time);
            if (!tr || tr.start < GRID_START || tr.end > GRID_END) return;

            const days = parseDays(subj.days);
            const color = COLOR_MAP[subj.color] || '#0B3C5D';
            const topPx = ((tr.start - GRID_START) / 30) * SLOT_H;
            const htPx = ((tr.end - tr.start) / 30) * SLOT_H;

            days.forEach((dayIdx) => {
                if (dayIdx >= DAY_NAMES.length) return;
                result.push({ key: `${subj.code}-${dayIdx}`, dayIdx, topPx, htPx, color, subj });
            });
        });
        return result;
    }, [subjects]);

    if (subjects.length === 0) {
        return (
            <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm print-area">
                <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                    <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                        <i className="fa-solid fa-table-cells-large mr-2 text-brandGreen" />Weekly Timetable
                    </span>
                </div>
                <div className="p-4 lg:p-6 overflow-x-auto">
                    <p className="text-sm text-brandNavy/50 dark:text-slate-400 py-6 text-center">
                        No enrolled subjects yet — complete your enrollment first.
                    </p>
                </div>
            </div>
        );
    }

    const slots = Array.from({ length: NUM_SLOTS }, (_, slot) => {
        const mins = GRID_START + slot * 30;
        const endMins = mins + 30;
        const sH = String(Math.floor(mins / 60)).padStart(2, '0');
        const sM = String(mins % 60).padStart(2, '0');
        const eH = String(Math.floor(endMins / 60)).padStart(2, '0');
        const eM = String(endMins % 60).padStart(2, '0');
        return { slot, yPx: slot * SLOT_H, label: `${sH}:${sM} - ${eH}:${eM}` };
    });

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/10 dark:border-slate-800 rounded-2xl overflow-hidden shadow-sm print-area">
            <div className="bg-lightBg dark:bg-slate-800/60 px-5 py-3 border-b border-brandNavy/10 dark:border-slate-800">
                <span className="text-xs font-bold text-brandNavy dark:text-slate-300 uppercase tracking-wider">
                    <i className="fa-solid fa-table-cells-large mr-2 text-brandGreen" />Weekly Timetable
                </span>
            </div>
            <div className="p-4 lg:p-6 overflow-x-auto">
                <div className="min-w-[520px]">
                    <div style={{ border: `1px solid ${colors.border}`, overflow: 'hidden', fontFamily: 'inherit' }}>
                        <div style={{ display: 'flex', borderBottom: `1px solid ${colors.border}`, background: colors.cellBg }}>
                            <div style={{ width: TIME_W, flexShrink: 0, padding: '10px 10px', borderRight: `1px solid ${colors.border}`, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                <span style={{ fontSize: 9.5, fontWeight: 900, letterSpacing: '0.1em', color: colors.headClr }}>TIME</span>
                            </div>
                            {DAY_NAMES.map((day, i) => (
                                <div key={day} style={{ flex: 1, textAlign: 'center', padding: '10px 2px', borderLeft: i > 0 ? `1px solid ${colors.border}` : undefined, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                                    <span style={{ fontSize: 9, fontWeight: 900, letterSpacing: '0.07em', color: colors.headClr }}>{day}</span>
                                </div>
                            ))}
                        </div>

                        <div style={{ display: 'flex', height: TOTAL_H, background: colors.cellBg }}>
                            <div style={{ width: TIME_W, flexShrink: 0, position: 'relative', borderRight: `1px solid ${colors.border}`, background: colors.cellBg }}>
                                {slots.map(({ slot, yPx, label }) => (
                                    <div key={slot} style={{ position: 'absolute', top: yPx, left: 0, right: 0, height: SLOT_H, display: 'flex', alignItems: 'center', justifyContent: 'flex-end', paddingRight: 10, borderTop: slot > 0 ? `1px solid ${colors.border}` : undefined }}>
                                        <span style={{ fontSize: 8.5, color: colors.timeClr, fontWeight: 600, whiteSpace: 'nowrap' }}>{label}</span>
                                    </div>
                                ))}
                            </div>

                            <div style={{ flex: 1, display: 'flex' }}>
                                {DAY_NAMES.map((day, dayIdx) => (
                                    <div key={day} style={{ flex: 1, position: 'relative', borderLeft: dayIdx > 0 ? `1px solid ${colors.border}` : undefined }}>
                                        {slots.filter(({ slot }) => slot > 0).map(({ slot, yPx }) => (
                                            <div key={slot} style={{ position: 'absolute', top: yPx, left: 0, right: 0, height: 1, background: colors.border }} />
                                        ))}
                                        {blocks.filter((b) => b.dayIdx === dayIdx).map((b) => (
                                            <div
                                                key={b.key}
                                                style={{
                                                    position: 'absolute',
                                                    top: b.topPx,
                                                    left: 0,
                                                    right: 0,
                                                    height: b.htPx,
                                                    background: b.color,
                                                    padding: '6px 8px',
                                                    overflow: 'hidden',
                                                    cursor: 'default',
                                                    boxSizing: 'border-box',
                                                }}
                                            >
                                                <div style={{ fontSize: 10, fontWeight: 900, color: '#fff', lineHeight: 1.25, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                                                    {b.subj.code}
                                                </div>
                                                {b.htPx >= 46 && (
                                                    <div style={{ fontSize: 8, color: 'rgba(255,255,255,0.72)', marginTop: 3, fontWeight: 500 }}>
                                                        {b.subj.type || 'Lecture'}
                                                    </div>
                                                )}
                                                {b.htPx >= 62 && (
                                                    <div style={{ fontSize: 8, color: 'rgba(255,255,255,0.72)', marginTop: 1 }}>
                                                        {b.subj.room}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
