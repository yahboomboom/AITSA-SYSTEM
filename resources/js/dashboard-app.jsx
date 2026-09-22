import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import WelcomeBanner from './dashboard/WelcomeBanner';
import DateCard from './dashboard/DateCard';
import WeatherCard from './dashboard/WeatherCard';
import QuickLinksCard from './dashboard/QuickLinksCard';
import AnnouncementsPanel from './dashboard/AnnouncementsPanel';

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return { announcements: Array.isArray(parsed.announcements) ? parsed.announcements : [] };
    } catch {
        return { announcements: [] };
    }
}

function DashboardApp({ announcements }) {
    return (
        <>
            <WelcomeBanner />
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
                <DateCard />
                <WeatherCard />
                <QuickLinksCard />
            </div>
            <AnnouncementsPanel announcements={announcements} />
        </>
    );
}

const el = document.getElementById('dashboard-root');
if (el) {
    const context = parseContext(el.dataset.context);
    createRoot(el).render(<ErrorBoundary><DashboardApp announcements={context.announcements} /></ErrorBoundary>);
}
