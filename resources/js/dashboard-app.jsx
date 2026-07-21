import React from 'react';
import { createRoot } from 'react-dom/client';
import WelcomeBanner from './dashboard/WelcomeBanner';
import AnnouncementsPanel from './dashboard/AnnouncementsPanel';

function DashboardApp() {
    return (
        <>
            <WelcomeBanner />
            <AnnouncementsPanel />
        </>
    );
}

const el = document.getElementById('dashboard-root');
if (el) createRoot(el).render(<DashboardApp />);
