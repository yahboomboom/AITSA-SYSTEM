import React from 'react';
import { createRoot } from 'react-dom/client';

function EnrollmentApp() {
    return <p className="text-sm text-slate-500">Enrollment module loading…</p>;
}

const el = document.getElementById('enrollment-root');
if (el) createRoot(el).render(<EnrollmentApp />);
