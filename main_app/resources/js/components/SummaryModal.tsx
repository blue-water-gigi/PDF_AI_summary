import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Link } from '@inertiajs/react';
import html2canvas from 'html2canvas';
import jsPDF from 'jspdf';
import { CheckCircle, Copy, Download, FileText, LoaderCircle, X } from 'lucide-react';
import { useRef, useState } from 'react';

interface SummaryModalProps {
    show: boolean;
    summary: string;
    fileName?: string;
    onClose: () => void;
    onNewUpload: () => void;
}

export default function SummaryModal({ show, summary, fileName, onClose, onNewUpload }: SummaryModalProps) {
    const [copied, setCopied] = useState(false);
    const [exporting, setExporting] = useState(false);
    const summaryRef = useRef<HTMLDivElement>(null);

    if (!show || !summary) {
        return null;
    }

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(summary);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
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
        URL.revokeObjectURL(url);
    };

    const handleExportPDF = async () => {
        if (!summaryRef.current) {
            return;
        }
        setExporting(true);

        const exportContainer = document.createElement('div');
        exportContainer.style.cssText = 'position:absolute; left: 0; top: 0; width: 800px; z-index: 9999; background: #fff;';
        const paragraphs = summary
            .split('\n\n')
            .map((p) => `<p style="font-size:16px;margin:0 0 16px 0;">${p}</p>`)
            .join('');
        exportContainer.innerHTML = `
            <div style="padding:32px;background:#fff;font-family:Arial,sans-serif;color:#111;">
            <h3 style="font-size:24px;font-weight:bold;color:#111;margin: 0 0 16px 0;">Summary</h3>
            <p style="font-size:14px;color:#666;margin: 0 0 24px 0;">${fileName || 'Document'}</p>
            <div style="color: #333; line-height: 1.75;">${paragraphs}</div></div>
            `;
        document.body.appendChild(exportContainer);

        await new Promise((r) => setTimeout(r, 100));

        const canvas = await html2canvas(exportContainer, { scale: 2, backgroundColor: '#fff' });
        const imgData = canvas.toDataURL('image/png');
        const pdfDoc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const imgWidth = 210;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

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
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div className="border-border bg-background relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl border shadow-xl">
                <PlaceholderPattern className="pointer-events-none absolute inset-0 size-full stroke-neutral-900/5 dark:stroke-neutral-100/5" />

                <div className="border-border relative border-b px-6 py-5">
                    <div className="flex items-start justify-between gap-4">
                        <div className="flex items-start gap-3">
                            <div className="bg-primary text-primary-foreground flex h-10 w-10 shrink-0 items-center justify-center rounded-lg">
                                <CheckCircle className="h-5 w-5" aria-hidden />
                            </div>
                            <div>
                                <h2 id="summary-modal-title" className="text-xl font-semibold tracking-tight">
                                    Summary generated
                                </h2>
                                <p className="text-muted-foreground mt-0.5 truncate text-sm">{fileName}</p>
                            </div>
                        </div>
                        <Button variant="ghost" size="icon" onClick={onClose} aria-label="Close summary">
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="relative max-h-[calc(90vh-220px)] overflow-y-auto p-6">
                    <div ref={summaryRef} className="border-border bg-card rounded-lg border p-6">
                        <h3 className="mb-4 text-base font-semibold">Summary</h3>
                        {summary.split('\n\n').map((p, i) => (
                            <p key={i} className="text-muted-foreground mb-4 leading-relaxed last:mb-0">
                                {p}
                            </p>
                        ))}
                    </div>
                </div>

                <div className="border-border relative border-t px-6 py-4">
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" onClick={handleCopy} aria-label="Copy summary to clipboard">
                            {copied ? (
                                <>
                                    <CheckCircle className="h-4 w-4" />
                                    Copied
                                </>
                            ) : (
                                <>
                                    <Copy className="h-4 w-4" />
                                    Copy
                                </>
                            )}
                        </Button>

                        <Button variant="outline" size="sm" onClick={handleDownload} aria-label="Download summary as text file">
                            <Download className="h-4 w-4" />
                            Download TXT
                        </Button>

                        <Button variant="outline" size="sm" onClick={handleExportPDF} disabled={exporting} aria-label="Export summary as PDF">
                            {exporting ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <FileText className="h-4 w-4" />}
                            {exporting ? 'Exporting...' : 'Export PDF'}
                        </Button>

                        <Button variant="secondary" size="sm" onClick={onNewUpload} aria-label="Upload new document">
                            New upload
                        </Button>

                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/history" aria-label="View summary history">
                                View history
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
