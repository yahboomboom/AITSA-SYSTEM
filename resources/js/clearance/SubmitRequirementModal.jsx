import { useEffect, useRef, useState } from 'react';

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function fileIconClass(file) {
    if (file.type === 'application/pdf') return 'fa-solid fa-file-pdf text-red-500 text-base';
    if (file.type.startsWith('image/')) return 'fa-solid fa-file-image text-blue-500 text-base';
    return 'fa-solid fa-file text-slate-400 text-base';
}

export default function SubmitRequirementModal({ open, onClose, csrfToken, submitUrl, documentType, documentLabel }) {
    const [file, setFile] = useState(null);
    const [dragOver, setDragOver] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [showRequiredHint, setShowRequiredHint] = useState(false);
    const fileInputRef = useRef(null);
    const formRef = useRef(null);

    // Reset the picked file whenever the modal opens for a (possibly different)
    // requirement row — this instance is now shared across every row on the
    // Documents page, so stale selections from a previous row must not carry over.
    useEffect(() => {
        if (open) {
            setFile(null);
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    }, [open]);

    if (!open) return null;

    const handleFileSelect = (e) => {
        if (e.target.files && e.target.files[0]) setFile(e.target.files[0]);
    };

    const handleDragOver = (e) => {
        e.preventDefault();
        setDragOver(true);
    };

    const handleDragLeave = () => setDragOver(false);

    const handleDrop = (e) => {
        e.preventDefault();
        setDragOver(false);
        const dropped = e.dataTransfer.files[0];
        if (dropped) {
            const dt = new DataTransfer();
            dt.items.add(dropped);
            if (fileInputRef.current) fileInputRef.current.files = dt.files;
            setFile(dropped);
        }
    };

    const clearFile = (e) => {
        if (e) e.stopPropagation();
        if (fileInputRef.current) fileInputRef.current.value = '';
        setFile(null);
    };

    const handleSubmit = () => {
        if (!fileInputRef.current?.files?.[0]) {
            setShowRequiredHint(true);
            setTimeout(() => setShowRequiredHint(false), 1500);
            return;
        }
        setSubmitting(true);
        formRef.current.submit();
    };

    return (
        <div
            id="submitModal"
            className="fixed inset-0 bg-brandNavy/50 dark:bg-black/75 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div id="submitModalBox" className="modal-enter bg-white dark:bg-[#0D1B2A] rounded-lg w-full max-w-lg overflow-hidden shadow-lg border border-brandNavy/8 dark:border-slate-800">

                <div className="px-6 py-4 border-b border-brandNavy/10 dark:border-slate-800 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded bg-brandNavy/8 dark:bg-slate-800 flex items-center justify-center">
                            <i className="fa-solid fa-file-arrow-up text-brandNavy dark:text-brandGold text-sm" />
                        </div>
                        <div>
                            <h3 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">{documentLabel}</h3>
                            <p className="text-xs text-brandNavy/50 dark:text-slate-500 mt-0.5">Office of the University Registrar</p>
                        </div>
                    </div>
                    <button onClick={onClose} className="w-8 h-8 rounded-full bg-brandNavy/5 dark:bg-slate-800 text-brandNavy/50 hover:text-brandNavy dark:text-slate-500 dark:hover:text-white flex items-center justify-center transition-colors">
                        <i className="fa-solid fa-xmark text-xs" />
                    </button>
                </div>

                <div className="mx-6 mt-5 flex items-start gap-3 p-3.5 rounded bg-red-600/5 border border-red-600/10 dark:bg-red-500/5 dark:border-red-500/10 text-sm">
                    <i className="fa-solid fa-triangle-exclamation text-red-500 mt-0.5 flex-shrink-0" />
                    <div>
                        <span className="font-medium text-brandNavy dark:text-slate-200">Outstanding requirement</span>
                        <p className="text-brandNavy/60 dark:text-slate-500 mt-0.5">{documentLabel}</p>
                    </div>
                </div>

                <form ref={formRef} id="submissionForm" action={submitUrl} method="POST" encType="multipart/form-data" className="p-6 space-y-5">
                    <input type="hidden" name="_token" value={csrfToken} />
                    <input type="hidden" name="document_type" value={documentType} />

                    <div>
                        <label className="text-xs font-medium text-brandNavy/60 dark:text-slate-400 block mb-2">
                            Scanned document <span className="text-red-500">*</span>
                        </label>
                        <div
                            id="dropZone"
                            className={`drop-zone rounded p-6 text-center cursor-pointer bg-lightBg/50 dark:bg-slate-900/40 hover:bg-lightBg dark:hover:bg-slate-900/60 transition-colors ${dragOver ? 'dragover' : ''}`}
                            style={showRequiredHint ? { borderColor: '#ef4444' } : undefined}
                            onClick={() => fileInputRef.current?.click()}
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onDrop={handleDrop}
                        >
                            <input
                                ref={fileInputRef}
                                id="fileInput"
                                type="file"
                                name="document"
                                accept=".pdf,.jpg,.jpeg,.png"
                                className="hidden"
                                onChange={handleFileSelect}
                            />

                            {!file ? (
                                <div>
                                    <div className="w-10 h-10 rounded-full bg-brandNavy/5 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3">
                                        <i className="fa-solid fa-cloud-arrow-up text-brandNavy/40 dark:text-slate-500 text-lg" />
                                    </div>
                                    <p className="text-xs font-semibold text-brandNavy/70 dark:text-slate-400">Drag &amp; drop your file here, or <span className="text-brandGreen dark:text-brandGold font-bold">browse</span></p>
                                    <p className="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-1">Accepted: PDF, JPG, PNG — Max 10 MB</p>
                                </div>
                            ) : (
                                <div>
                                    <div className="file-chip inline-flex items-center gap-2.5 px-4 py-2.5 bg-white dark:bg-slate-800 border border-brandNavy/10 dark:border-slate-700 rounded shadow-sm">
                                        <i className={fileIconClass(file)} />
                                        <div className="text-left">
                                            <p className="text-xs font-bold text-brandNavy dark:text-slate-200 truncate max-w-[200px]">{file.name}</p>
                                            <p className="text-[10px] text-brandNavy/50 dark:text-slate-500">{formatBytes(file.size)}</p>
                                        </div>
                                        <button type="button" onClick={clearFile} className="ml-1 w-5 h-5 rounded-full bg-brandNavy/5 dark:bg-slate-700 text-brandNavy/40 dark:text-slate-500 hover:bg-red-500/10 hover:text-red-500 flex items-center justify-center transition-colors">
                                            <i className="fa-solid fa-xmark text-[9px]" />
                                        </button>
                                    </div>
                                    <p className="text-[10px] text-brandNavy/40 dark:text-slate-600 mt-2">Click to change file</p>
                                </div>
                            )}
                        </div>
                    </div>

                    <div>
                        <label className="text-xs font-medium text-brandNavy/60 dark:text-slate-400 block mb-2">Notes to Registrar <span className="font-normal">(optional)</span></label>
                        <textarea
                            name="notes"
                            rows="3"
                            placeholder="e.g. Attached is the certified true copy issued by my previous school. Original is being mailed separately."
                            className="w-full bg-lightBg/50 dark:bg-slate-900/40 text-brandNavy dark:text-slate-200 text-sm px-4 py-3 rounded border border-brandNavy/10 dark:border-slate-700 focus:outline-none focus:border-brandGreen dark:focus:border-brandGold/50 transition-colors resize-none placeholder-brandNavy/30 dark:placeholder-slate-600"
                        />
                    </div>

                    <div className="flex items-start gap-2.5 p-3 rounded bg-blue-600/5 border border-blue-600/10 dark:bg-blue-500/5 dark:border-blue-500/10 text-xs text-brandNavy/60 dark:text-slate-500">
                        <i className="fa-solid fa-circle-info text-blue-500 mt-0.5 flex-shrink-0" />
                        <p>Your submission will be forwarded directly to the Registrar&apos;s Office. You will be notified once your document has been reviewed, typically within <strong className="text-brandNavy dark:text-slate-300">1–3 business days</strong>. Submitting does not guarantee immediate clearance.</p>
                    </div>

                    <div className="flex gap-3 pt-1">
                        <button type="button" onClick={onClose} className="ui-btn-primary flex-1 justify-center bg-transparent border border-brandNavy/20 dark:border-slate-600 text-brandNavy dark:text-slate-300 hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                            Cancel
                        </button>
                        <button
                            type="button"
                            id="submitBtn"
                            onClick={handleSubmit}
                            disabled={submitting}
                            className="ui-btn-primary flex-1 justify-center bg-brandNavy hover:bg-brandGreen text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span id="submitBtnText">
                                {submitting ? (
                                    <><i className="fa-solid fa-spinner animate-spin mr-1.5" />Uploading…</>
                                ) : (
                                    <><i className="fa-solid fa-paper-plane mr-1.5" />Submit to Registrar</>
                                )}
                            </span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    );
}
