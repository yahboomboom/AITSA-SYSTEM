import React from 'react';
import { createRoot } from 'react-dom/client';

function CurriculumApp() {
    return <p className="text-sm text-slate-500">Curriculum editor loading…</p>;
}

const el = document.getElementById('curriculum-root');
if (el) createRoot(el).render(<CurriculumApp />);
