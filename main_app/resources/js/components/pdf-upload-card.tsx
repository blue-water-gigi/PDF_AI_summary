import SummaryModal from '@/components/SummaryModal';
import SummaryOptionsModal from '@/components/SummaryOptionsModel';
import { Card } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { cn } from '@/lib/utils';
import { LoaderCircle, UploadIcon } from 'lucide-react';
import { useRef, useState, type ChangeEvent, type DragEvent } from 'react';

type SummaryType = 'default' | 'points' | 'highlights' | 'detailed';

interface UserStats {
    pdfCount: number;
    pdfLimit: number;
    canUpload: boolean;
}

interface PdfUploadCardProps {
    userStats?: UserStats | null;
    userPlanSlug?: string | null;
    className?: string;
}

export default function PdfUploadCard({ userStats, userPlanSlug = 'basic', className }: PdfUploadCardProps) {
    const [pdf, setPdf] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [loading, setLoading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [summary, setSummary] = useState('');
    const [showSummary, setShowSummary] = useState(false);
    const [showSummaryOptions, setShowSummaryOptions] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const limitReached = !!(userStats && !userStats.canUpload);
    const displayLimit = userStats?.pdfLimit && userStats.pdfLimit < 0 ? 'Unlimited' : (userStats?.pdfLimit ?? 0);

    const handleFileSelect = (file: File) => {
        if (limitReached) {
            alert('PDF limit reached (' + (userStats?.pdfCount ?? 0) + ' / ' + displayLimit + ').');
            return;
        }

        setSelectedFile(file);
        setPdf(file);
        setShowSummaryOptions(true);
    };

    const handleSummaryTypeSelect = async (type: SummaryType) => {
        setShowSummaryOptions(false);
        if (!selectedFile) {
            return;
        }

        setLoading(true);
        setProgress(0);
        setSummary('');

        const formData = new FormData();
        formData.append('pdf', selectedFile);
        formData.append('summary_type', type);

        const progressInterval = window.setInterval(() => {
            setProgress((prev) => (prev >= 90 ? 90 : prev + 10));
        }, 200);

        try {
            const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '';
            const response = await fetch('/pdf/summarize', {
                method: 'POST',
                body: formData,
                headers: { Accept: 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(csrfToken) },
            });

            if (!response.ok) {
                throw new Error('Failed to generate summary');
            }

            const data = await response.json();
            const cleanSummary = String(data.summary || '')
                .replace(/\*\*/g, '')
                .replace(/\*/g, '')
                .replace(/^#+\s/gm, '')
                .replace(/^-\s/gm, ' ');

            setProgress(100);
            setSummary(cleanSummary);

            window.setTimeout(() => {
                setLoading(false);
                setShowSummary(true);
            }, 500);
        } catch {
            setLoading(false);
            setProgress(0);
            alert('Failed to generate summary');
        } finally {
            window.clearInterval(progressInterval);
        }
    };

    const handleDragOver = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragging(true);
    };

    const handleDragLeave = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragging(false);

        const file = event.dataTransfer.files[0];
        if (file?.type === 'application/pdf') {
            handleFileSelect(file);
        }
    };

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file?.type === 'application/pdf') {
            handleFileSelect(file);
        }
    };

    const handleNewUpload = () => {
        setPdf(null);
        setSummary('');
        setShowSummary(false);
        setSelectedFile(null);
    };

    return (
        <>
            <SummaryOptionsModal
                show={showSummaryOptions && !!selectedFile}
                fileName={selectedFile?.name ?? ''}
                userPlanSlug={userPlanSlug ?? 'basic'}
                onClose={() => {
                    setShowSummaryOptions(false);
                    setSelectedFile(null);
                    setPdf(null);
                }}
                onSelect={handleSummaryTypeSelect}
            />
            <SummaryModal
                show={showSummary}
                summary={summary}
                fileName={pdf?.name}
                onClose={() => setShowSummary(false)}
                onNewUpload={handleNewUpload}
            />

            <Card
                className={cn(
                    'relative overflow-hidden transition-all duration-300',
                    isDragging && 'border-foreground scale-[1.01] shadow-lg',
                    limitReached && 'opacity-60',
                    className,
                )}
            >
                <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/5 dark:stroke-neutral-100/5" />
                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    className={cn(
                        'relative cursor-pointer p-12 text-center transition-colors',
                        !limitReached && 'hover:bg-accent/30',
                        limitReached && 'cursor-not-allowed',
                    )}
                    onClick={() => !limitReached && fileInputRef.current?.click()}
                >
                    <input ref={fileInputRef} type="file" accept=".pdf" onChange={handleFileChange} disabled={limitReached} className="hidden" />
                    <div
                        className={cn(
                            'bg-secondary mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg transition-all duration-300',
                            isDragging && 'scale-110 animate-pulse',
                        )}
                    >
                        <UploadIcon className="text-foreground h-7 w-7" />
                    </div>
                    <h3 className="mb-2 text-xl font-medium">Drop your PDF here</h3>
                    <p className="text-muted-foreground">
                        or click to browse ({userStats?.pdfCount ?? 0} / {displayLimit})
                    </p>
                    {limitReached && <p className="text-destructive mt-3 text-sm">PDF limit reached. Upgrade your plan.</p>}
                </div>
                {loading && (
                    <div className="bg-background/95 absolute inset-0 flex flex-col items-center justify-center backdrop-blur-sm">
                        <LoaderCircle className="text-foreground mb-4 h-10 w-10 animate-spin" />
                        <p className="text-muted-foreground mb-3 text-sm">Generating summary...</p>
                        <div className="bg-secondary h-1.5 w-48 overflow-hidden rounded-full">
                            <div className="bg-primary h-full rounded-full transition-all duration-300 ease-out" style={{ width: progress + '%' }} />
                        </div>
                        <p className="text-muted-foreground mt-2 text-xs">{progress}%</p>
                    </div>
                )}
            </Card>
        </>
    );
}
