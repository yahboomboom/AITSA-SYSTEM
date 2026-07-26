import React from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import BalanceCard from './payment/BalanceCard';
import PaymentHistoryCard from './payment/PaymentHistoryCard';
import ClearanceStatusCard from './payment/ClearanceStatusCard';

const EMPTY_BREAKDOWN = {
    units: 0, rate: 0, tuition: 0, discount_name: null, discount_percent: 0,
    discount_amount: 0, misc: 0, assessment: 0, paid: 0, balance: 0, fully_paid: false,
};

const EMPTY_CONTEXT = {
    settled: true,
    hasPendingGateway: false,
    breakdown: EMPTY_BREAKDOWN,
    history: [],
    cashierCleared: false,
    registrarCleared: false,
    chairCleared: false,
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            settled: !!parsed.settled,
            hasPendingGateway: !!parsed.hasPendingGateway,
            breakdown: parsed.breakdown ?? EMPTY_BREAKDOWN,
            history: Array.isArray(parsed.history) ? parsed.history : [],
            cashierCleared: !!parsed.cashierCleared,
            registrarCleared: !!parsed.registrarCleared,
            chairCleared: !!parsed.chairCleared,
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function PaymentApp({ context, csrfToken, checkoutUrl, verifyUrl }) {
    return (
        <div className="space-y-6">
            <BalanceCard
                settled={context.settled}
                breakdown={context.breakdown}
                hasPendingGateway={context.hasPendingGateway}
                checkoutUrl={checkoutUrl}
                verifyUrl={verifyUrl}
                csrfToken={csrfToken}
            />
            <PaymentHistoryCard history={context.history} />
            <ClearanceStatusCard
                cashierCleared={context.cashierCleared}
                registrarCleared={context.registrarCleared}
                chairCleared={context.chairCleared}
            />
        </div>
    );
}

const el = document.getElementById('payment-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const checkoutUrl = el.dataset.checkoutUrl ?? '';
    const verifyUrl = el.dataset.verifyUrl ?? '';
    createRoot(el).render(
        <ErrorBoundary>
            <PaymentApp context={context} csrfToken={csrfToken} checkoutUrl={checkoutUrl} verifyUrl={verifyUrl} />
        </ErrorBoundary>
    );
}
