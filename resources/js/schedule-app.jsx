import React from 'react';
import { createRoot } from 'react-dom/client';
import CorHeader from './schedule/CorHeader';
import WeeklyTimetable from './schedule/WeeklyTimetable';
import ScheduleQRCode from './schedule/ScheduleQRCode';

function parseSubjects(raw) {
    try {
        const parsed = JSON.parse(raw ?? '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

function ScheduleApp({ subjects, studentName, studentId, studentProgram }) {
    return (
        <div className="space-y-6">
            <CorHeader studentId={studentId} studentProgram={studentProgram} />
            <WeeklyTimetable subjects={subjects} />
            <ScheduleQRCode
                subjects={subjects}
                studentName={studentName}
                studentId={studentId}
                studentProgram={studentProgram}
            />
        </div>
    );
}

const el = document.getElementById('schedule-root');
if (el) {
    const subjects = parseSubjects(el.dataset.subjects);
    const studentName = el.dataset.studentName ?? 'Student';
    const studentId = el.dataset.studentId ?? 'N/A';
    const studentProgram = el.dataset.studentProgram ?? 'BSIT - Web Development';
    createRoot(el).render(
        <ScheduleApp
            subjects={subjects}
            studentName={studentName}
            studentId={studentId}
            studentProgram={studentProgram}
        />
    );
}
