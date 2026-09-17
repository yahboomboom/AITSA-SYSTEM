import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import WelcomeBanner from './dashboard/WelcomeBanner';
import ClearanceStat from './dashboard/ClearanceStat';
import AnnouncementsPanel from './dashboard/AnnouncementsPanel';

const EMPTY_CONTEXT = { clearancePercent: 0 };

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            clearancePercent: Number.isFinite(parsed.clearancePercent) ? parsed.clearancePercent : 0,
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function DashboardApp({ clearancePercent }) {
    return (
        <>
            <WelcomeBanner />
            <ClearanceStat percent={clearancePercent} />
            <AnnouncementsPanel />
        </>
    );
}

const el = document.getElementById('dashboard-root');
if (el) {
    const context = parseContext(el.dataset.context);
    createRoot(el).render(<ErrorBoundary><DashboardApp clearancePercent={context.clearancePercent} /></ErrorBoundary>);
}
