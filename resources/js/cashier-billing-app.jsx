import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';
import FeeRatesForm from './cashier-billing/FeeRatesForm';
import DiscountTypesPanel from './cashier-billing/DiscountTypesPanel';
import StudentDiscountTable from './cashier-billing/StudentDiscountTable';

const EMPTY_CONTEXT = {
    feeRates: { tuitionPerUnit: 0, miscFee: 0, reservationFee: 0, tesdaTuitionFee: 0 },
    discountTypes: [],
    students: [],
    errors: {},
    old: {},
};

function parseContext(raw) {
    try {
        const parsed = JSON.parse(raw ?? '{}');
        return {
            feeRates: parsed.feeRates ?? EMPTY_CONTEXT.feeRates,
            discountTypes: Array.isArray(parsed.discountTypes) ? parsed.discountTypes : [],
            students: Array.isArray(parsed.students) ? parsed.students : [],
            errors: parsed.errors ?? {},
            old: parsed.old ?? {},
        };
    } catch {
        return EMPTY_CONTEXT;
    }
}

function BillingApp({ context, csrfToken, feesUrl, discountsUrl }) {
    const [discountTypes, setDiscountTypes] = useState(context.discountTypes);
    const [students, setStudents] = useState(context.students);

    const handleToggled = (id, isActive) => {
        setDiscountTypes((types) => types.map((t) => (t.id === id ? { ...t, isActive } : t)));
    };

    const handleAssigned = (studentId, discountTypeId) => {
        setStudents((list) => list.map((s) => (s.id === studentId ? { ...s, discountTypeId } : s)));
    };

    return (
        <div className="space-y-5">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <FeeRatesForm
                    feeRates={context.feeRates}
                    errors={context.errors}
                    old={context.old}
                    csrfToken={csrfToken}
                    actionUrl={feesUrl}
                />
                <DiscountTypesPanel
                    discountTypes={discountTypes}
                    errors={context.errors}
                    old={context.old}
                    csrfToken={csrfToken}
                    addUrl={discountsUrl}
                    onToggled={handleToggled}
                />
            </div>
            <StudentDiscountTable
                students={students}
                discountTypes={discountTypes}
                csrfToken={csrfToken}
                onAssigned={handleAssigned}
            />
        </div>
    );
}

const el = document.getElementById('cashier-billing-root');
if (el) {
    const context = parseContext(el.dataset.context);
    const csrfToken = el.dataset.csrfToken ?? '';
    const feesUrl = el.dataset.feesUrl ?? '';
    const discountsUrl = el.dataset.discountsUrl ?? '';

    createRoot(el).render(
        <ErrorBoundary>
            <BillingApp context={context} csrfToken={csrfToken} feesUrl={feesUrl} discountsUrl={discountsUrl} />
        </ErrorBoundary>
    );
}