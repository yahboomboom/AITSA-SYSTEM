import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import AddAnnouncementForm from './admin-announcements/AddAnnouncementForm';
import AnnouncementsList from './admin-announcements/AnnouncementsList';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return { announcements: Array.isArray(parsed.announcements) ? parsed.announcements : [] };
    } catch {
        return { announcements: [] };
    }
}

const el = document.getElementById('admin-announcements-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const storeUrl = el.dataset.storeUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-6">
                <AddAnnouncementForm csrfToken={csrfToken} actionUrl={storeUrl} />
                <AnnouncementsList announcements={context.announcements} csrfToken={csrfToken} />
            </div>
        </ErrorBoundary>
    );
}
