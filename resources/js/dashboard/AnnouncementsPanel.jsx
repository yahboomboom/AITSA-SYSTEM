import React, { useState } from 'react';
import AttachmentLightbox from './AttachmentLightbox';
import InlineAttachmentViewer from './InlineAttachmentViewer';

function iconFor(a) {
    if (a.attachmentUrl) return a.attachmentIsImage ? 'fa-file-image' : 'fa-file-pdf';
    if (a.linkUrl) return a.linkHost && a.linkHost.includes('youtu') ? 'fa-circle-play' : 'fa-link';
    return 'fa-bullhorn';
}

// Shown for links the site won't let us embed (e.g. Facebook posts).
function LinkCard({ url, host, compact = false }) {
    if (compact) {
        return (
            <a href={url} target="_blank" rel="noopener noreferrer"
                className="mt-2 flex items-center justify-between gap-3 rounded-md bg-lightBg dark:bg-slate-800/60 px-3 py-2 text-xs hover:bg-brandNavy/5 dark:hover:bg-slate-800 transition-colors">
                <span className="truncate text-brandNavy/70 dark:text-slate-300"><i className={`fa-brands ${host && host.includes('facebook') ? 'fa-facebook' : 'fa-chrome'} mr-1.5`} />{host}</span>
                <span className="font-semibold text-brandGreen whitespace-nowrap">Open on {host} <i className="fa-solid fa-arrow-up-right-from-square ml-1" /></span>
            </a>
        );
    }
    return (
        <a href={url} target="_blank" rel="noopener noreferrer"
            className="mt-3 flex items-center gap-3 rounded-lg border border-brandNavy/10 dark:border-slate-700 bg-lightBg dark:bg-slate-800/60 p-3 hover:border-brandNavy/30 dark:hover:border-slate-500 transition-colors">
            <span className="w-10 h-10 rounded-lg bg-brandNavy/10 dark:bg-white/10 flex items-center justify-center text-brandNavy dark:text-[#8EC3DE] flex-shrink-0">
                <i className="fa-solid fa-link" />
            </span>
            <span className="min-w-0 flex-1">
                <span className="block text-sm font-semibold text-brandNavy dark:text-white truncate">{host}</span>
                <span className="block text-xs text-brandNavy/50 dark:text-slate-400 truncate">{url}</span>
            </span>
            <span className="text-xs font-semibold text-brandGreen whitespace-nowrap">Open link <i className="fa-solid fa-arrow-up-right-from-square ml-1" /></span>
        </a>
    );
}

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
                <ul className="space-y-6">
                    {announcements.map((announcement, index) => (
                        <li key={index} className="rounded-lg border border-brandNavy/10 dark:border-slate-700 bg-lightBg/50 dark:bg-slate-900/30 p-4 sm:p-5 shadow-sm">
                            <div className="flex items-start gap-2 flex-wrap">
                                <i className={`fa-solid ${iconFor(announcement)} text-brandGold mt-1`} />
                                <p className="font-heading text-base font-semibold text-brandNavy dark:text-white flex-1 min-w-0">{announcement.title}</p>
                                {announcement.isNew && (
                                    <span className="text-[10px] font-bold text-brandGold bg-brandGold/10 rounded-full px-2 py-0.5">New</span>
                                )}
                            </div>
                            {announcement.attachmentUrl && (
                                <InlineAttachmentViewer
                                    url={announcement.attachmentUrl}
                                    name={announcement.attachmentName}
                                    isImage={announcement.attachmentIsImage}
                                    onFullscreen={() => setViewer({ url: announcement.attachmentUrl, name: announcement.attachmentName, isImage: announcement.attachmentIsImage })}
                                />
                            )}
                            {announcement.linkUrl && (announcement.linkType === 'card' ? (
                                announcement.linkImageUrl ? (
                                    <>
                                        <InlineAttachmentViewer
                                            url={announcement.linkImageUrl}
                                            openUrl={announcement.linkUrl}
                                            name={announcement.title}
                                            isImage
                                            onFullscreen={() => setViewer({ url: announcement.linkImageUrl, name: announcement.title, isImage: true })}
                                        />
                                        <LinkCard url={announcement.linkUrl} host={announcement.linkHost} compact />
                                    </>
                                ) : (
                                    <LinkCard url={announcement.linkUrl} host={announcement.linkHost} />
                                )
                            ) : (
                                <InlineAttachmentViewer
                                    url={announcement.linkSrc}
                                    openUrl={announcement.linkUrl}
                                    name={announcement.title}
                                    isImage={announcement.linkType === 'image'}
                                    onFullscreen={() => setViewer({ url: announcement.linkSrc, name: announcement.title, isImage: announcement.linkType === 'image' })}
                                />
                            ))}
                            <p className="text-xs text-brandNavy/40 dark:text-slate-500 mt-2">{announcement.postedAt}</p>
                        </li>
                    ))}
                </ul>
            )}

            {viewer && (
                <AttachmentLightbox
                    url={viewer.url}
                    name={viewer.name}
                    isImage={viewer.isImage}
                    onClose={() => setViewer(null)}
                />
            )}
        </div>
    );
}
