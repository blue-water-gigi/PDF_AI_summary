import SummaryMarkdown from '@/components/summary-markdown';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { type UserStats } from '@/types';
import { CheckCircleIcon, CopyIcon, FileTextIcon, LoaderCircle, UploadIcon, XIcon } from 'lucide-react';
import { useMemo, useRef, useState, type ChangeEvent, type DragEvent } from 'react';

type SummaryType = 'standard' | 'bullet_points' | 'key_highlights' | 'detailed_analysis';

interface SummaryResult {
    id: number | string;
    summary: string | string[] | Record<string, unknown>;
}

interface PdfUploadCardProps {
    userStats?: UserStats | null;
    userPlanSlug?: string | null;
    className?: string;
}

interface SummaryOption {
    value: SummaryType;
    label: string;
    description: string;
    requiredPlan: 'basic' | 'standard' | 'premium';
}

const summaryOptions: SummaryOption[] = [
    {
        value: 'standard',
        label: 'Standard summary',
        description: 'Clear overview, main ideas, important details, and final summary.',
        requiredPlan: 'basic',
    },
    {
        value: 'bullet_points',
        label: 'Bullet points',
        description: 'Structured list of the most important facts and takeaways.',
        requiredPlan: 'basic',
    },
    {
        value: 'key_highlights',
        label: 'Key highlights',
        description: 'Most valuable points with short explanations of why they matter.',
        requiredPlan: 'standard',
    },
    {
        value: 'detailed_analysis',
        label: 'Detailed analysis',
        description: 'Full breakdown with summary, structure, highlights, risks, and conclusion.',
        requiredPlan: 'premium',
    },
];

const planHierarchy: Record<string, number> = { basic: 1, standard: 2, premium: 3 };

function getXsrfToken(): string {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');
}

function normalizeSummary(summary: SummaryResult['summary'] | null): string {
    if (!summary) {
        return '';
    }

    if (typeof summary === 'string') {
        return summary.trim();
    }

    if (Array.isArray(summary)) {
        return summary.join('\n').trim();
    }

    return JSON.stringify(summary, null, 2);
}

function formatFileSize(size: number): string {
    if (size < 1024 * 1024) {
        return `${Math.max(1, Math.round(size / 1024))} KB`;
    }

    return `${(size / 1024 / 1024).toFixed(1)} MB`;
}

function formatLimit(limit: number | undefined): string {
    if (typeof limit !== 'number') {
        return '0';
    }

    return limit < 0 ? 'Unlimited' : String(limit);
}

function canUseSummaryType(userPlanSlug: string, requiredPlan: string): boolean {
    return (planHierarchy[userPlanSlug] ?? 1) >= (planHierarchy[requiredPlan] ?? 1);
}

export default function PdfUploadCard({ userStats, userPlanSlug = 'basic', className }: PdfUploadCardProps) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [summaryType, setSummaryType] = useState<SummaryType>('standard');
    const [isDragging, setIsDragging] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [progress, setProgress] = useState(0);
    const [result, setResult] = useState<SummaryResult | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const activePlanSlug = userPlanSlug ?? 'basic';
    const limitReached = userStats?.canUpload === false;
    const selectedOption = summaryOptions.find((option) => option.value === summaryType) ?? summaryOptions[0];
    const summaryText = useMemo(() => normalizeSummary(result?.summary ?? null), [result]);
    const displayLimit = formatLimit(userStats?.pdfLimit);
    const canSubmit = !!selectedFile && !processing && !limitReached && canUseSummaryType(activePlanSlug, selectedOption.requiredPlan);

    const clearFile = () => {
        setSelectedFile(null);
        setError(null);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleFileSelect = (file: File | undefined) => {
        setError(null);
        setResult(null);
        setCopied(false);

        if (!file) {
            return;
        }

        if (limitReached) {
            setError('PDF limit reached for the current billing period.');
            return;
        }

        if (file.type !== 'application/pdf') {
            setError('Please choose a PDF file.');
            return;
        }

        if (file.size > 20 * 1024 * 1024) {
            setError('PDF file must be 20 MB or smaller.');
            return;
        }

        setSelectedFile(file);
    };

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        handleFileSelect(event.target.files?.[0]);
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
        handleFileSelect(event.dataTransfer.files[0]);
    };

    const submit = async () => {
        if (!selectedFile) {
            setError('Choose a PDF before generating a summary.');
            return;
        }

        if (!canUseSummaryType(activePlanSlug, selectedOption.requiredPlan)) {
            setError(`${selectedOption.label} requires the ${selectedOption.requiredPlan} plan.`);
            return;
        }

        setProcessing(true);
        setProgress(12);
        setError(null);
        setResult(null);
        setCopied(false);

        const formData = new FormData();
        formData.append('pdf', selectedFile);
        formData.append('summary_type', summaryType);

        const progressTimer = window.setInterval(() => {
            setProgress((value) => Math.min(value + 8, 92));
        }, 500);

        try {
            const response = await fetch(route('pdf.summarize'), {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': getXsrfToken(),
                },
            });

            const data = await response.json().catch(() => null);

            if (!response.ok) {
                const message = data?.message ?? data?.errors?.pdf?.[0] ?? data?.errors?.summary_type?.[0] ?? 'Failed to generate summary.';
                throw new Error(message);
            }

            setProgress(100);
            setResult(data as SummaryResult);
        } catch (caughtError) {
            setError(caughtError instanceof Error ? caughtError.message : 'Failed to generate summary.');
            setProgress(0);
        } finally {
            window.clearInterval(progressTimer);
            setProcessing(false);
        }
    };

    const copySummary = async () => {
        if (!summaryText) {
            return;
        }

        try {
            await navigator.clipboard.writeText(summaryText);
        } catch {
            const textarea = document.createElement('textarea');
            textarea.value = summaryText;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }

        setCopied(true);
        window.setTimeout(() => setCopied(false), 1800);
    };

    return (
        <div className="space-y-4">
            <Card className={cn('border-sidebar-border/70 dark:border-sidebar-border transition-all', className)}>
                <CardHeader className="pb-3">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <CardTitle className="text-base">Create summary</CardTitle>
                            <p className="text-muted-foreground mt-1 text-sm">Upload a PDF and choose how the answer should be structured.</p>
                        </div>
                        <Badge variant={limitReached ? 'destructive' : 'outline'}>
                            {userStats?.pdfCount ?? 0} / {displayLimit} PDFs
                        </Badge>
                    </div>
                </CardHeader>

                <CardContent className="space-y-4">
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                        <div
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onDrop={handleDrop}
                            onClick={() => !limitReached && fileInputRef.current?.click()}
                            role="button"
                            tabIndex={limitReached ? -1 : 0}
                            onKeyDown={(event) => {
                                if (!limitReached && (event.key === 'Enter' || event.key === ' ')) {
                                    event.preventDefault();
                                    fileInputRef.current?.click();
                                }
                            }}
                            className={cn(
                                'flex min-h-[220px] cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed p-6 text-center transition-colors',
                                isDragging && 'border-primary bg-primary/5',
                                !isDragging && 'border-sidebar-border/70 hover:bg-accent/30 dark:border-sidebar-border',
                                limitReached && 'cursor-not-allowed opacity-60',
                            )}
                        >
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="application/pdf,.pdf"
                                onChange={handleFileChange}
                                disabled={limitReached}
                                className="hidden"
                            />
                            <div className="bg-secondary mb-4 flex h-14 w-14 items-center justify-center rounded-lg">
                                <UploadIcon className="h-6 w-6" />
                            </div>
                            <h3 className="text-base font-medium">Drop PDF here</h3>
                            <p className="text-muted-foreground mt-1 text-sm">or click to browse from your device</p>
                            <p className="text-muted-foreground mt-2 text-xs">PDF only, up to 20 MB</p>
                        </div>

                        <div className="border-sidebar-border/70 dark:border-sidebar-border space-y-4 rounded-lg border p-4">
                            <div className="space-y-2">
                                <label className="text-sm font-medium" htmlFor="summary-type">
                                    Summary type
                                </label>
                                <Select value={summaryType} onValueChange={(value) => setSummaryType(value as SummaryType)}>
                                    <SelectTrigger id="summary-type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {summaryOptions.map((option) => {
                                            const locked = !canUseSummaryType(activePlanSlug, option.requiredPlan);

                                            return (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                    {locked ? ` (${option.requiredPlan})` : ''}
                                                </SelectItem>
                                            );
                                        })}
                                    </SelectContent>
                                </Select>
                                <p className="text-muted-foreground text-xs">{selectedOption.description}</p>
                            </div>

                            {selectedFile ? (
                                <div className="bg-muted/40 flex items-start justify-between gap-3 rounded-lg p-3">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2 text-sm font-medium">
                                            <FileTextIcon className="h-4 w-4 shrink-0" />
                                            <span className="truncate">{selectedFile.name}</span>
                                        </div>
                                        <p className="text-muted-foreground mt-1 text-xs">{formatFileSize(selectedFile.size)}</p>
                                    </div>
                                    <Button variant="ghost" size="icon" onClick={clearFile} aria-label="Remove selected PDF">
                                        <XIcon className="h-4 w-4" />
                                    </Button>
                                </div>
                            ) : null}

                            <Button className="w-full" onClick={submit} disabled={!canSubmit}>
                                {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                {processing ? 'Generating...' : 'Generate summary'}
                            </Button>

                            {processing ? (
                                <div className="space-y-2">
                                    <div className="bg-secondary h-1.5 overflow-hidden rounded-full">
                                        <div
                                            className="bg-primary h-full rounded-full transition-all duration-300"
                                            style={{ width: `${progress}%` }}
                                        />
                                    </div>
                                    <p className="text-muted-foreground text-xs">Parsing PDF and asking AI...</p>
                                </div>
                            ) : null}
                        </div>
                    </div>

                    {error ? (
                        <Alert variant="destructive">
                            <AlertTitle>Summary was not created</AlertTitle>
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    ) : null}
                </CardContent>
            </Card>

            {summaryText ? (
                <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader className="pb-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <CardTitle className="text-base">Summary result</CardTitle>
                                <p className="text-muted-foreground mt-1 text-sm">{selectedFile?.name ?? 'Generated document summary'}</p>
                            </div>
                            <Button variant="outline" size="sm" onClick={copySummary}>
                                {copied ? <CheckCircleIcon className="h-4 w-4" /> : <CopyIcon className="h-4 w-4" />}
                                {copied ? 'Copied' : 'Copy text'}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="border-sidebar-border/70 bg-background dark:border-sidebar-border rounded-lg border p-5">
                            <SummaryMarkdown text={summaryText} />
                        </div>
                    </CardContent>
                </Card>
            ) : null}
        </div>
    );
}
