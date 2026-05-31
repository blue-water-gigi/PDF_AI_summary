import { Link } from '@inertiajs/react';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';
import { CheckCircle, Copy, Download, FileText, X } from 'lucide-react';
import { useRef, useState } from 'react';

interface SummaryModelProps {
    show: boolean;
    summary: string;
    fileName?: string;
    onClose: () => void;
    onNewUpload: () => void;
}

export default function SummaryModel({ show, summary, fileName, onClose, onNewUpload }: SummaryModelProps) {
    const [copied, setCopied] = useState(false);
    const [exporting, setExporting] = useState(false);
    const summaryRef = useRef<HTMLDivElement>(null);

    if (!show || !summary) return null;

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(summary);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Fallback for older browsers: create a temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = summary;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                setCopied(true);
                setTimeout(() => setCopied(false), 2000);
            } finally {
                document.body.removeChild(textarea);
            }
        }
    };

    const handleDownload = () => {
        const file = new Blob([summary], { type: 'text/plain' });
        const url = URL.createObjectURL(file);
        const element = document.createElement('a');
        element.href = url;
        element.download = `${fileName?.replace('.pdf', '') || 'summary'}_summary.txt`;
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
        // revoke the object URL to free memory
        URL.revokeObjectURL(url);
    };

    const handleExportPDF = async () => {
        if (!summaryRef.current) return;
        setExporting(true);

        const exportContainer = document.createElement('div');
        exportContainer.style.cssText = 'position:absolute; left: 0; top: 0; width: 800px; z-index: 9999; background: #fff;';
        const paragraphs = summary
            .split('\n\n')
            .map((p) => `<p style="font-size:16px;margin:0 0 16px 0;">${p}</p>`)
            .join('');
        exportContainer.innerHTML = `
            <div style="padding:32px;background:#fff;font-family:Arial,sans-serif;color:#111;">
            <h3 style="font-size:24px;font-weight:bold;color:#7c3aed;margin: 0 0 16px 0;">Summary</h3>
            <p style="font-size:14px;color:#64748b;margin: 0 0 24px 0;">${fileName || 'Document'}</p>
            <div style="color: #334155; line-height: 1.75;">${paragraphs}</div></div>
            `;
        document.body.appendChild(exportContainer);

        // wait a tick for styles to apply
        await new Promise((r) => setTimeout(r, 100));

        const canvas = await html2canvas(exportContainer, { scale: 2, backgroundColor: '#fff' });
        const imgData = canvas.toDataURL('image/png');
        const pdfDoc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const imgWidth = 210; // mm for a4
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        // if content height exceeds page height, scale down to fit one page width-wise.
        pdfDoc.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
        pdfDoc.save(`${fileName?.replace('.pdf', '') || 'summary'}_summary.pdf`);

        document.body.removeChild(exportContainer);
        setExporting(false);
    };

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="summary-modal-title"
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
        >
            <div className="relative max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                {/* Header */}
                <div className="sticky top-0 z-10 bg-gradient-to-r from-violet-600 to-violet-700 px-8 py-6">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <CheckCircle className="h-6 w-6 text-white" aria-hidden />
                            <div>
                                <h2 id="summary-modal-title" className="text-2xl font-bold text-white">
                                    Summary Generated!
                                </h2>
                                <p className="text-sm text-white/80">{fileName}</p>
                            </div>
                        </div>
                        <button onClick={onClose} aria-label="Close summary" className="rounded-lg p-2 hover:bg-white/20">
                            <X className="h-6 w-6 text-white" />
                        </button>
                    </div>
                </div>

                {/* Content */}
                <div className="max-h-[calc(90vh-200px)] overflow-y-auto p-8">
                    <div ref={summaryRef} className="rounded-xl border bg-white p-6">
                        <h3 className="mb-4 text-lg font-bold text-violet-600">Summary</h3>
                        {summary.split('\n\n').map((p, i) => (
                            <p key={i} className="mb-4 text-slate-700">
                                {p}
                            </p>
                        ))}
                    </div>
                </div>

                {/* Footer */}
                <div className="sticky bottom-0 border-t bg-white px-8 py-6 dark:bg-slate-900">
                    <div className="flex flex-wrap justify-between gap-3">
                        <div className="flex gap-3">
                            <button
                                onClick={handleCopy}
                                aria-label="Copy summary to clipboard"
                                className="flex items-center gap-2 rounded-xl bg-violet-100 px-6 py-3 font-medium text-violet-600 hover:bg-violet-200"
                            >
                                {copied ? (
                                    <>
                                        <CheckCircle className="h-5 w-5" />
                                        Copied!
                                    </>
                                ) : (
                                    <>
                                        <Copy className="h-5 w-5" />
                                        Copy
                                    </>
                                )}
                            </button>

                            <button
                                onClick={handleDownload}
                                aria-label="Download summary as text file"
                                className="flex items-center gap-2 rounded-xl bg-blue-100 px-6 py-3 font-medium text-blue-600 hover:bg-blue-200"
                            >
                                <Download className="h-5 w-5" />
                                <span>Download TXT</span>
                            </button>

                            <button
                                onClick={handleExportPDF}
                                disabled={exporting}
                                aria-label="Export summary as PDF"
                                aria-disabled={exporting}
                                className="flex items-center gap-2 rounded-xl bg-green-100 px-6 py-3 font-medium text-green-600 hover:bg-green-200 disabled:opacity-50"
                            >
                                <FileText className="h-5 w-5" />
                                {exporting ? 'Exporting...' : 'Export PDF'}
                            </button>

                            <button
                                onClick={onNewUpload}
                                aria-label="Upload new document"
                                className="flex items-center gap-2 rounded-xl bg-slate-100 px-6 py-3 font-medium text-slate-600 hover:bg-slate-200"
                            >
                                New Upload
                            </button>

                            <Link
                                href="/history"
                                className="rounded-xl border-2 border-violet-600 px-6 py-3 text-sm font-medium text-violet-600 hover:bg-violet-500"
                                aria-label="View summary history"
                            >
                                View History
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
