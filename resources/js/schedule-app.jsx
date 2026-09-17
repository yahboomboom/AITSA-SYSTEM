import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
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

function ScheduleApp({ subjects, studentName, studentId, studentProgram, schoolYear, semester }) {
    return (
        <div className="space-y-6">
            <CorHeader studentId={studentId} studentProgram={studentProgram} schoolYear={schoolYear} semester={semester} />
            <WeeklyTimetable subjects={subjects} />
            <ScheduleQRCode
                subjects={subjects}
                studentName={studentName}
                studentId={studentId}
                studentProgram={studentProgram}
                schoolYear={schoolYear}
                semester={semester}
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
    const schoolYear = el.dataset.schoolYear ?? '2026-2027';
    const semester = el.dataset.semester ?? '1';
    createRoot(el).render(
        <ErrorBoundary>
            <ScheduleApp
                subjects={subjects}
                studentName={studentName}
                studentId={studentId}
                studentProgram={studentProgram}
                schoolYear={schoolYear}
                semester={semester}
            />
        </ErrorBoundary>
    );
}
