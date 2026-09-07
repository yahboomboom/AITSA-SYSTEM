import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import StudentsFilterForm from './admin-students/StudentsFilterForm';
import StudentsTable from './admin-students/StudentsTable';
import Pagination from './admin-students/Pagination';
import DeleteStudentModal from './admin-students/DeleteStudentModal';

const EMPTY_CONTEXT = {
    filters: { q: '', program: '', year_level: '' },
    programs: [],
    students: [],
    pagination: { currentPage: 1, lastPage: 1, total: 0, firstItem: 0, lastItem: 0, prevPageUrl: null, nextPageUrl: null },
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            filters: parsed.filters ?? EMPTY_CONTEXT.filters,
            programs: Array.isArray(parsed.programs) ? parsed.programs : [],
            students: Array.isArray(parsed.students) ? parsed.students : [],
            pagination: parsed.pagination ?? EMPTY_CONTEXT.pagination,
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function AdminStudentsApp({ context, csrfToken, indexUrl }) {
    const [pendingDelete, setPendingDelete] = useState(null);

    return (
        <div className="space-y-6">
            <StudentsFilterForm filters={context.filters} programs={context.programs} actionUrl={indexUrl} />
            <StudentsTable students={context.students} onDeleteClick={setPendingDelete} />
            <Pagination pagination={context.pagination} />
            <DeleteStudentModal student={pendingDelete} csrfToken={csrfToken} onClose={() => setPendingDelete(null)} />
        </div>
    );
}

const el = document.getElementById('admin-students-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const indexUrl = el.dataset.indexUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <AdminStudentsApp context={context} csrfToken={csrfToken} indexUrl={indexUrl} />
        </ErrorBoundary>
    );
}
