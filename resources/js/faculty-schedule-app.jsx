import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import TeachingScheduleGrid from './faculty-schedule/TeachingScheduleGrid';

const EMPTY_CONTEXT = {
    teacherName: 'Faculty',
    schoolYear: '',
    days: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            teacherName: parsed.teacherName ?? EMPTY_CONTEXT.teacherName,
            schoolYear: parsed.schoolYear ?? EMPTY_CONTEXT.schoolYear,
            days: Array.isArray(parsed.days) ? parsed.days : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('faculty-schedule-root');
if (el) {
    const context = parseContext(el.dataset.context);
    createRoot(el).render(
        <ErrorBoundary>
            <TeachingScheduleGrid teacherName={context.teacherName} schoolYear={context.schoolYear} days={context.days} />
        </ErrorBoundary>
    );
}
