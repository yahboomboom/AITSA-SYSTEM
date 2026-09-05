import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import SystemStatsCards from './admin-dashboard/SystemStatsCards';
import SystemAccountsTable from './admin-dashboard/SystemAccountsTable';

const EMPTY_CONTEXT = {
    stats: { totalActiveUsers: 0, clearancesSettled: 0, pendingQueues: 0 },
    accounts: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            stats: parsed.stats ?? EMPTY_CONTEXT.stats,
            accounts: Array.isArray(parsed.accounts) ? parsed.accounts : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

const el = document.getElementById('admin-dashboard-root');
if (el) {
    const context = parseContext(el.dataset.context);

    createRoot(el).render(
        <ErrorBoundary>
            <div className="space-y-6">
                <SystemStatsCards
                    totalActiveUsers={context.stats.totalActiveUsers}
                    clearancesSettled={context.stats.clearancesSettled}
                    pendingQueues={context.stats.pendingQueues}
                />
                <SystemAccountsTable accounts={context.accounts} />
            </div>
        </ErrorBoundary>
    );
}
