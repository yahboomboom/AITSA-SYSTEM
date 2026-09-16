import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import AddDepartmentForm from './admin-departments/AddDepartmentForm';
import DepartmentsTable from './admin-departments/DepartmentsTable';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return { departments: Array.isArray(parsed.departments) ? parsed.departments : [] };
    } catch {
        return { departments: [] };
    }
}

const el = document.getElementById('admin-departments-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const storeUrl = el.dataset.storeUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-6">
                <AddDepartmentForm csrfToken={csrfToken} actionUrl={storeUrl} />
                <DepartmentsTable departments={context.departments} csrfToken={csrfToken} />
            </div>
        </ErrorBoundary>
    );
}
