import React, { useEffect, useRef, useState } from 'react';

const MIN_ZOOM = 0.5;
const MAX_ZOOM = 3;
const ZOOM_STEP = 0.25;

// Announcement attachment shown in place: a dark frame with the file fitted
// inside, a floating − / reset / + pill to zoom right on the card, and drag
// (or scroll) to pan once zoomed in. PDFs use the browser's own viewer.
export default function InlineAttachmentViewer({ url, name, isImage, onFullscreen, openUrl }) {
    const [zoom, setZoom] = useState(1);
    const frameRef = useRef(null);
    const drag = useRef(null);
    const [natural, setNatural] = useState(null); // { w, h } of the image file
    const [frame, setFrame] = useState(null);     // { w, h } of the visible frame

    // Track the frame's size so "fit" (100%) always contains the whole image,
    // whether it's a tall document or a wide screenshot, on any screen width.
    useEffect(() => {
        const el = frameRef.current;
        if (!el || typeof ResizeObserver === 'undefined') return;
        const ro = new ResizeObserver(() => setFrame({ w: el.clientWidth, h: el.clientHeight }));
        ro.observe(el);
        return () => ro.disconnect();
    }, []);

    const PAD = 24;
    const fit = natural && frame ? Math.min((frame.w - PAD) / natural.w, (frame.h - PAD) / natural.h) : null;
    const imgSize = fit ? { width: Math.round(natural.w * fit * zoom), height: Math.round(natural.h * fit * zoom) } : null;

    const zoomIn = () => setZoom((z) => Math.min(MAX_ZOOM, +(z + ZOOM_STEP).toFixed(2)));
    const zoomOut = () => setZoom((z) => Math.max(MIN_ZOOM, +(z - ZOOM_STEP).toFixed(2)));

    const onPointerDown = (e) => {
        if (zoom <= 1 || !frameRef.current) return;
        drag.current = { x: e.clientX, y: e.clientY, left: frameRef.current.scrollLeft, top: frameRef.current.scrollTop };
        e.currentTarget.setPointerCapture(e.pointerId);
    };
    const onPointerMove = (e) => {
        if (!drag.current || !frameRef.current) return;
        frameRef.current.scrollLeft = drag.current.left - (e.clientX - drag.current.x);
        frameRef.current.scrollTop = drag.current.top - (e.clientY - drag.current.y);
    };
    const endDrag = () => { drag.current = null; };

    const cornerBtn = 'w-9 h-9 rounded-md bg-black/55 hover:bg-black/75 text-white flex items-center justify-center transition-colors';

    return (
        <div className="relative mt-3 rounded-lg overflow-hidden bg-[#0E1F29] border border-brandNavy/10 dark:border-slate-700">
            {isImage ? (
                <div
                    ref={frameRef}
                    className={`h-72 sm:h-[28rem] overflow-auto select-none ${zoom > 1 ? 'cursor-grab active:cursor-grabbing' : ''}`}
                    onPointerDown={onPointerDown}
                    onPointerMove={onPointerMove}
                    onPointerUp={endDrag}
                    onPointerCancel={endDrag}
                >
                    <div className="min-w-full min-h-full w-max h-max flex items-center justify-center p-3">
                        <img
                            src={url}
                            alt={name}
                            draggable={false}
                            onLoad={(e) => setNatural({ w: e.currentTarget.naturalWidth, h: e.currentTarget.naturalHeight })}
                            style={imgSize ?? { maxWidth: '100%', maxHeight: '100%' }}
                            className="max-w-none shadow-2xl bg-white"
                        />
                    </div>
                </div>
            ) : (
                <iframe src={url} title={name} className="w-full h-72 sm:h-[28rem] bg-white"
                    allow="accelerometer; encrypted-media; picture-in-picture; fullscreen" allowFullScreen />
            )}

            <div className="absolute top-3 right-3 flex gap-2">
                <a href={openUrl ?? url} target="_blank" rel="noopener noreferrer" title="Open in a new tab" aria-label="Open in a new tab" className={cornerBtn}>
                    <i className="fa-solid fa-arrow-up-right-from-square text-sm" />
                </a>
                <button type="button" onClick={onFullscreen} title="Full screen" aria-label="Full screen" className={cornerBtn}>
                    <i className="fa-solid fa-expand text-sm" />
                </button>
            </div>

            {isImage && (
                <div className="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-1 rounded-full bg-black/65 backdrop-blur px-2 py-1.5 shadow-lg text-white">
                    <button type="button" onClick={zoomOut} disabled={zoom <= MIN_ZOOM} title="Zoom out" aria-label="Zoom out"
                        className="w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/15 disabled:opacity-30 disabled:cursor-not-allowed">
                        <i className="fa-solid fa-minus" />
                    </button>
                    <button type="button" onClick={() => setZoom(1)} title="Reset to fit" aria-label="Reset zoom"
                        className="h-8 px-2 rounded-full flex items-center gap-1.5 hover:bg-white/15 text-xs font-semibold tabular-nums">
                        <i className="fa-solid fa-magnifying-glass" />{Math.round(zoom * 100)}%
                    </button>
                    <button type="button" onClick={zoomIn} disabled={zoom >= MAX_ZOOM} title="Zoom in" aria-label="Zoom in"
                        className="w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/15 disabled:opacity-30 disabled:cursor-not-allowed">
                        <i className="fa-solid fa-plus" />
                    </button>
                </div>
            )}
        </div>
    );
}
