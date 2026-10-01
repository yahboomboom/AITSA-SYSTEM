import React, { useEffect, useState } from 'react';

const MIN_ZOOM = 0.5;
const MAX_ZOOM = 3;
const ZOOM_STEP = 0.25;

export default function AttachmentLightbox({ url, name, isImage, onClose }) {
    const [zoom, setZoom] = useState(1);

    useEffect(() => {
        const onKeyDown = (e) => { if (e.key === 'Escape') onClose(); };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [onClose]);

    const zoomIn = () => setZoom((z) => Math.min(MAX_ZOOM, +(z + ZOOM_STEP).toFixed(2)));
    const zoomOut = () => setZoom((z) => Math.max(MIN_ZOOM, +(z - ZOOM_STEP).toFixed(2)));
    const resetZoom = () => setZoom(1);

    return (
        <div
            className="fixed inset-0 bg-black/90 z-50 flex flex-col"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className="flex items-center justify-between gap-3 px-4 py-3 flex-shrink-0 bg-brandNavy border-b border-white/10 shadow-lg">
                <p className="text-sm font-medium text-white truncate">{name}</p>
                <div className="flex items-center gap-2 flex-shrink-0">
                    {isImage && (
                        <div className="flex items-center gap-1 bg-white/10 rounded-lg p-1">
                            <button type="button" onClick={zoomOut} disabled={zoom <= MIN_ZOOM}
                                title="Zoom out"
                                className="w-8 h-8 rounded flex items-center justify-center text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed">
                                <i className="fa-solid fa-magnifying-glass-minus text-sm" />
                            </button>
                            <button type="button" onClick={resetZoom}
                                className="text-xs font-medium text-white/80 hover:text-white w-14 text-center">
                                {Math.round(zoom * 100)}%
                            </button>
                            <button type="button" onClick={zoomIn} disabled={zoom >= MAX_ZOOM}
                                title="Zoom in"
                                className="w-8 h-8 rounded flex items-center justify-center text-white hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed">
                                <i className="fa-solid fa-magnifying-glass-plus text-sm" />
                            </button>
                        </div>
                    )}
                    <a href={url} target="_blank" rel="noopener noreferrer" title="Open in a new tab"
                        className="w-8 h-8 rounded flex items-center justify-center text-white hover:bg-white/10">
                        <i className="fa-solid fa-arrow-up-right-from-square text-sm" />
                    </a>
                    <button type="button" onClick={onClose} title="Close"
                        className="flex items-center gap-1.5 pl-2.5 pr-3 h-8 rounded-full bg-white text-brandNavy font-semibold text-xs hover:bg-white/90 transition-colors">
                        <i className="fa-solid fa-xmark text-sm" />Close
                    </button>
                </div>
            </div>
            <div className="flex-1 min-h-0 overflow-auto flex items-center justify-center p-4">
                {isImage ? (
                    <img src={url} alt={name} style={{ transform: `scale(${zoom})` }}
                        className="max-w-full max-h-full transition-transform duration-150 rounded shadow-2xl" />
                ) : (
                    <iframe src={url} title={name} className="w-full h-full bg-white rounded shadow-2xl" />
                )}
            </div>
        </div>
    );
}
