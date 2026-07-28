import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import CashierStats from './cashier-dashboard/CashierStats';
import ClearanceQueueTable from './cashier-dashboard/ClearanceQueueTable';
import ClearanceReviewModal from './cashier-dashboard/ClearanceReviewModal';

const EMPTY_CONTEXT = {
    stats: { totalOutstanding: 0, settledBase: 0, settledCount: 0, pendingActions: 0 },
    rows: [],
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            stats: parsed.stats ?? EMPTY_CONTEXT.stats,
            rows: Array.isArray(parsed.rows) ? parsed.rows : [],
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function CashierDashboardApp({ context, csrfToken, approveUrl, holdUrl }) {
    const [reviewing, setReviewing] = useState(null);

    return (
        <div className="space-y-5">
            <CashierStats
                totalOutstanding={context.stats.totalOutstanding}
                settledBase={context.stats.settledBase}
                settledCount={context.stats.settledCount}
                pendingActions={context.stats.pendingActions}
            />
            <ClearanceQueueTable rows={context.rows} onReview={setReviewing} />
            <ClearanceReviewModal
                open={reviewing !== null}
                student={reviewing}
                onClose={() => setReviewing(null)}
                csrfToken={csrfToken}
                approveUrl={approveUrl}
                holdUrl={holdUrl}
            />
        </div>
    );
}

const el = document.getElementById('cashier-dashboard-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const approveUrl = el.dataset.approveUrl ?? '';
    const holdUrl = el.dataset.holdUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <CashierDashboardApp context={context} csrfToken={csrfToken} approveUrl={approveUrl} holdUrl={holdUrl} />
        </ErrorBoundary>
    );
}
