import React, { useState } from 'react';
import AttachmentLightbox from './AttachmentLightbox';

export default function AnnouncementsPanel({ announcements }) {
    const [viewer, setViewer] = useState(null); // null | announcement

    return (
        <div className="bg-white dark:bg-panelDark border border-brandNavy/8 dark:border-slate-800 rounded-lg p-6 animate-fade-in">
            <div className="flex items-center justify-between mb-4">
                <h2 className="font-heading text-sm font-semibold text-brandNavy dark:text-white">
                    <i className="fa-solid fa-bullhorn text-brandGreen mr-2" />Announcements
                </h2>
                {announcements.length > 0 && (
                    <span className="text-xs font-semibold text-brandNavy/40 dark:text-slate-500 bg-lightBg dark:bg-slate-800 rounded-full min-w-[1.5rem] text-center px-2 py-0.5">
                        {announcements.length}
                    </span>
                )}
            </div>
            {announcements.length === 0 ? (
                <div className="text-center text-brandNavy/40 dark:text-slate-500 py-4">
                    <p className="text-sm">No announcements at this time.</p>
                </div>
            ) : (
                <ul className="space-y-5 max-h-[32rem] overflow-y-auto pr-1">
                    {announcements.map((announcement, index) => (
                        <li key={index} className="border-l-2 border-brandGreen/20 dark:border-brandGreen/25 pl-4">
                            <div className="flex items-center gap-2 flex-wrap">
                                <p className="font-medium text-brandNavy dark:text-white">{announcement.title}</p>
                                {announcement.isNew && (
                                    <span className="text-[10px] font-bold text-brandGold bg-brandGold/10 rounded-full px-2 py-0.5">New</span>
                                )}
                            </div>
                            <p className="text-sm text-brandNavy/60 dark:text-slate-400 mt-1 whitespace-pre-wrap">{announcement.body}</p>
                            {announcement.attachmentUrl && (
                                announcement.attachmentIsImage ? (
                                    <div className="relative flex justify-center mt-3">
                                        <img src={announcement.attachmentUrl} alt={announcement.attachmentName}
                                            className="max-h-48 w-auto rounded-lg border border-brandNavy/8 dark:border-slate-700 object-contain" />
                                        <button type="button" onClick={() => setViewer(announcement)} title="View full size"
                                            className="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/90 dark:bg-panelDark/90 shadow flex items-center justify-center text-brandNavy/60 dark:text-slate-300 hover:text-brandNavy hover:bg-white dark:hover:text-white dark:hover:bg-panelDark transition-colors">
                                            <i className="fa-solid fa-up-right-and-down-left-from-center text-[10px]" />
                                        </button>
                                    </div>
                                ) : (
                                    <div className="mt-3">
                                        <div className="relative w-full h-48 rounded-lg overflow-hidden border border-brandNavy/8 dark:border-slate-700 bg-lightBg dark:bg-slate-800">
                                            <iframe src={announcement.attachmentUrl} title={announcement.attachmentName}
                                                className="w-full h-full" />
                                            <button type="button" onClick={() => setViewer(announcement)} title="View full size"
                                                className="absolute top-2 right-2 w-7 h-7 rounded-full bg-white/90 dark:bg-panelDark/90 shadow flex items-center justify-center text-brandNavy/60 dark:text-slate-300 hover:text-brandNavy hover:bg-white dark:hover:text-white dark:hover:bg-panelDark transition-colors">
                                                <i className="fa-solid fa-up-right-and-down-left-from-center text-[10px]" />
                                            </button>
                                        </div>
                                        <p className="flex items-center gap-1.5 mt-1.5 text-xs font-medium text-brandNavy/70 dark:text-slate-300">
                                            <i className="fa-solid fa-file-lines text-brandGreen" />{announcement.attachmentName}
                                        </p>
                                    </div>
                                )
                            )}
                            <p className="text-xs text-brandNavy/40 dark:text-slate-600 mt-2">{announcement.postedAt}</p>
                        </li>
                    ))}
                </ul>
            )}

            {viewer && (
                <AttachmentLightbox
                    url={viewer.attachmentUrl}
                    name={viewer.attachmentName}
                    isImage={viewer.attachmentIsImage}
                    onClose={() => setViewer(null)}
                />
            )}
        </div>
    );
}
