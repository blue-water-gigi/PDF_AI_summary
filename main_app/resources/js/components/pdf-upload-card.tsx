import ChangePlanDialog from '@/components/change-plan-dialog';
import SummaryMarkdown from '@/components/summary-markdown';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent } from '@/components/ui/collapsible';
import { formatFileSize, formatLimit } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { type UserStats } from '@/types';
import { router } from '@inertiajs/react';
import {
    CheckCircleIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    CopyIcon,
    FileTextIcon,
    HistoryIcon,
    LoaderCircle,
    LockIcon,
    SparklesIcon,
    UploadIcon,
    XIcon,
} from 'lucide-react';
import { useMemo, useRef, useState, type ChangeEvent, type DragEvent } from 'react';

type SummaryType = 'standard' | 'bullet_points' | 'key_highlights' | 'detailed_analysis';
type GenerationState = 'idle' | 'processing' | 'finishing' | 'success';

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
        label: 'Standard',
        description: 'Overview, main ideas, important details, and final summary.',
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
        description: 'Full breakdown with structure, highlights, risks, and conclusion.',
        requiredPlan: 'premium',
    },
];

const planHierarchy: Record<string, number> = { basic: 1, standard: 2, premium: 3 };
const progressMessages = [
    { threshold: 0, text: 'Starting process...' },
    { threshold: 18, text: 'Reading PDF file...' },
    { threshold: 38, text: 'Extracting document text...' },
    { threshold: 58, text: 'Parsing PDF and asking AI...' },
    { threshold: 76, text: 'Receiving answer...' },
    { threshold: 88, text: 'Just a little bit more...' },
    { threshold: 100, text: 'Saved to history.' },
];

function delay(ms: number): Promise<void> {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
}

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

function canUseSummaryType(userPlanSlug: string, requiredPlan: string): boolean {
    return (planHierarchy[userPlanSlug] ?? 1) >= (planHierarchy[requiredPlan] ?? 1);
}

function progressMessage(progress: number, generationState: GenerationState): string {
    if (generationState === 'finishing') {
        return 'Finishing and saving result...';
    }

    if (generationState === 'success') {
        return 'Saved to history.';
    }

    return progressMessages.reduce((message, item) => (progress >= item.threshold ? item.text : message), progressMessages[0].text);
}

function buttonContent(generationState: GenerationState) {
    if (generationState === 'processing' || generationState === 'finishing') {
        return (
            <>
                <LoaderCircle className="h-4 w-4 animate-spin" />
                {generationState === 'finishing' ? 'Finishing...' : 'Generating...'}
            </>
        );
    }

    if (generationState === 'success') {
        return (
            <>
                <CheckCircleIcon className="animate-in zoom-in-75 h-4 w-4" />
                Done
            </>
        );
    }

    return 'Generate summary';
}

export default function PdfUploadCard({ userStats, userPlanSlug = 'basic', className }: PdfUploadCardProps) {
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const [summaryType, setSummaryType] = useState<SummaryType>('standard');
    const [upgradeOption, setUpgradeOption] = useState<SummaryOption | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [generationState, setGenerationState] = useState<GenerationState>('idle');
    const [progress, setProgress] = useState(0);
    const [result, setResult] = useState<SummaryResult | null>(null);
    const [resultOpen, setResultOpen] = useState(true);
    const [savedNotice, setSavedNotice] = useState(false);
    const [savedNoticeLeaving, setSavedNoticeLeaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [copied, setCopied] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const activePlanSlug = userPlanSlug ?? 'basic';
    const limitReached = userStats?.canUpload === false;
    const selectedOption = summaryOptions.find((option) => option.value === summaryType) ?? summaryOptions[0];
    const summaryText = useMemo(() => normalizeSummary(result?.summary ?? null), [result]);
    const displayLimit = formatLimit(userStats?.pdfLimit);
    const canUseSelectedType = canUseSummaryType(activePlanSlug, selectedOption.requiredPlan);
    const isBusy = generationState !== 'idle';
    const uploadLocked = limitReached || isBusy;
    const canSubmit = !!selectedFile && !isBusy && !limitReached && canUseSelectedType;

    const showSavedNotice = () => {
        setSavedNotice(true);
        setSavedNoticeLeaving(false);
        window.setTimeout(() => setSavedNoticeLeaving(true), 3400);
        window.setTimeout(() => setSavedNotice(false), 4200);
    };

    const clearFile = () => {
        if (isBusy) {
            return;
        }

        setSelectedFile(null);
        setError(null);

        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleFileSelect = (file: File | undefined) => {
        if (isBusy) {
            return;
        }

        setError(null);
        setResult(null);
        setSavedNotice(false);
        setSavedNoticeLeaving(false);
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

    const chooseSummaryType = (option: SummaryOption) => {
        if (isBusy) {
            return;
        }

        setSummaryType(option.value);
        setError(null);

        if (!canUseSummaryType(activePlanSlug, option.requiredPlan)) {
            setUpgradeOption(option);
            return;
        }

        setUpgradeOption(null);
    };

    const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        handleFileSelect(event.target.files?.[0]);
    };

    const handleDragOver = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();

        if (!uploadLocked) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setIsDragging(false);

        if (!uploadLocked) {
            handleFileSelect(event.dataTransfer.files[0]);
        }
    };

    const submit = async () => {
        if (!selectedFile) {
            setError('Choose a PDF before generating a summary.');
            return;
        }

        if (!canUseSelectedType) {
            setUpgradeOption(selectedOption);
            return;
        }

        setGenerationState('processing');
        setProgress(4);
        setError(null);
        setResult(null);
        setSavedNotice(false);
        setSavedNoticeLeaving(false);
        setCopied(false);

        const formData = new FormData();
        formData.append('pdf', selectedFile);
        formData.append('summary_type', summaryType);

        const progressTimer = window.setInterval(() => {
            setProgress((value) => {
                if (value < 24) {
                    return value + 2.8;
                }

                if (value < 60) {
                    return value + 1.8;
                }

                if (value < 82) {
                    return value + 0.9;
                }

                return Math.min(value + 0.25, 94);
            });
        }, 900);

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

            window.clearInterval(progressTimer);
            setGenerationState('finishing');
            setProgress(100);
            await delay(1050);
            setResult(data as SummaryResult);
            setResultOpen(true);
            showSavedNotice();
            setGenerationState('success');
            router.reload({ only: ['userStats', 'subscriptionData', 'notifications'], preserveScroll: true });
            await delay(1300);
            setGenerationState('idle');
        } catch (caughtError) {
            window.clearInterval(progressTimer);
            setError(caughtError instanceof Error ? caughtError.message : 'Failed to generate summary.');
            setProgress(0);
            setGenerationState('idle');
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
                    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
                        <div
                            onDragOver={handleDragOver}
                            onDragLeave={handleDragLeave}
                            onDrop={handleDrop}
                            onClick={() => !uploadLocked && fileInputRef.current?.click()}
                            role="button"
                            tabIndex={uploadLocked ? -1 : 0}
                            onKeyDown={(event) => {
                                if (!uploadLocked && (event.key === 'Enter' || event.key === ' ')) {
                                    event.preventDefault();
                                    fileInputRef.current?.click();
                                }
                            }}
                            className={cn(
                                'flex min-h-[240px] flex-col items-center justify-center rounded-lg border border-dashed p-6 text-center transition-all duration-300',
                                isDragging && 'border-primary bg-primary/5',
                                !isDragging && 'border-sidebar-border/70 hover:bg-accent/30 dark:border-sidebar-border',
                                uploadLocked ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
                            )}
                        >
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="application/pdf,.pdf"
                                onChange={handleFileChange}
                                disabled={uploadLocked}
                                className="hidden"
                            />
                            <div className="bg-secondary mb-4 flex h-14 w-14 items-center justify-center rounded-lg transition-transform duration-300">
                                <UploadIcon className="h-6 w-6" />
                            </div>
                            <h3 className="text-base font-medium">{isBusy ? 'Generation in progress' : 'Drop PDF here'}</h3>
                            <p className="text-muted-foreground mt-1 text-sm">
                                {isBusy ? 'Finish the current summary before selecting another file' : 'or click to browse from your device'}
                            </p>
                            <p className="text-muted-foreground mt-2 text-xs">PDF only, up to 20 MB</p>
                        </div>

                        <div className="border-sidebar-border/70 dark:border-sidebar-border space-y-4 rounded-lg border p-4">
                            <div className="space-y-3">
                                <div className="flex items-center justify-between gap-2">
                                    <div className="text-sm font-medium">Summary type</div>
                                    <SparklesIcon className="text-muted-foreground h-4 w-4" />
                                </div>

                                <div className="grid gap-2">
                                    {summaryOptions.map((option) => {
                                        const locked = !canUseSummaryType(activePlanSlug, option.requiredPlan);
                                        const selected = option.value === summaryType;

                                        return (
                                            <button
                                                key={option.value}
                                                type="button"
                                                onClick={() => chooseSummaryType(option)}
                                                disabled={isBusy}
                                                className={cn(
                                                    'rounded-lg border p-3 text-left transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60',
                                                    selected
                                                        ? 'border-primary bg-primary/5'
                                                        : 'border-sidebar-border/70 hover:bg-accent/40 dark:border-sidebar-border',
                                                )}
                                            >
                                                <div className="flex items-start justify-between gap-3">
                                                    <div>
                                                        <div className="text-sm font-medium">{option.label}</div>
                                                        <div className="text-muted-foreground mt-1 text-xs">{option.description}</div>
                                                    </div>
                                                    {locked ? (
                                                        <Badge variant="outline" className="shrink-0 gap-1 capitalize">
                                                            <LockIcon className="h-3 w-3" />
                                                            {option.requiredPlan}
                                                        </Badge>
                                                    ) : selected ? (
                                                        <CheckCircleIcon className="text-primary h-4 w-4 shrink-0" />
                                                    ) : null}
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            {upgradeOption ? (
                                <div className="border-sidebar-border/70 dark:border-sidebar-border bg-muted/30 rounded-lg border p-3">
                                    <div className="text-sm font-medium">Upgrade required</div>
                                    <p className="text-muted-foreground mt-1 text-xs">
                                        {upgradeOption.label} requires the {upgradeOption.requiredPlan} plan.
                                    </p>
                                    <ChangePlanDialog
                                        trigger={
                                            <Button className="mt-3 w-full" size="sm" disabled={isBusy}>
                                                Upgrade plan
                                            </Button>
                                        }
                                    />
                                </div>
                            ) : null}

                            {selectedFile ? (
                                <div className="bg-muted/40 flex items-start justify-between gap-3 rounded-lg p-3 transition-colors duration-300">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2 text-sm font-medium">
                                            <FileTextIcon className="h-4 w-4 shrink-0" />
                                            <span className="truncate">{selectedFile.name}</span>
                                        </div>
                                        <p className="text-muted-foreground mt-1 text-xs">{formatFileSize(selectedFile.size)}</p>
                                    </div>
                                    <Button variant="ghost" size="icon" onClick={clearFile} disabled={isBusy} aria-label="Remove selected PDF">
                                        <XIcon className="h-4 w-4" />
                                    </Button>
                                </div>
                            ) : null}

                            <Button className="w-full transition-all duration-300" onClick={submit} disabled={!canSubmit || isBusy}>
                                <span className="inline-flex items-center gap-2 transition-all duration-300">{buttonContent(generationState)}</span>
                            </Button>

                            {generationState !== 'idle' ? (
                                <div
                                    className={cn(
                                        'space-y-2 transition-all duration-500',
                                        generationState === 'success' ? 'translate-y-1 opacity-0 delay-700' : 'translate-y-0 opacity-100',
                                    )}
                                >
                                    <div className="bg-secondary h-2 overflow-hidden rounded-full">
                                        <div
                                            className="bg-primary h-full rounded-full transition-all duration-1000 ease-out"
                                            style={{ width: `${Math.round(progress)}%` }}
                                        />
                                    </div>
                                    <div className="text-muted-foreground flex items-center justify-between text-xs">
                                        <span>{progressMessage(progress, generationState)}</span>
                                        <span>{Math.round(progress)}%</span>
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    </div>

                    {error ? (
                        <Alert variant="destructive" className="animate-in fade-in slide-in-from-top-1 duration-300">
                            <AlertTitle>Summary was not created</AlertTitle>
                            <AlertDescription>{error}</AlertDescription>
                        </Alert>
                    ) : null}
                </CardContent>
            </Card>

            {summaryText ? (
                <Card className="border-sidebar-border/70 dark:border-sidebar-border animate-in fade-in slide-in-from-bottom-2 duration-500">
                    <CardHeader className="pb-3">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <CardTitle className="text-base">Summary result</CardTitle>
                                    {savedNotice ? (
                                        <Badge
                                            variant="secondary"
                                            className={cn(
                                                'gap-1 transition-all duration-500',
                                                savedNoticeLeaving ? 'translate-y-1 opacity-0' : 'translate-y-0 opacity-100',
                                            )}
                                        >
                                            <HistoryIcon className="h-3 w-3" />
                                            Saved to History
                                        </Badge>
                                    ) : null}
                                </div>
                                <p className="text-muted-foreground mt-1 text-sm">{selectedFile?.name ?? 'Generated document summary'}</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="outline" size="sm" onClick={copySummary}>
                                    {copied ? <CheckCircleIcon className="h-4 w-4" /> : <CopyIcon className="h-4 w-4" />}
                                    {copied ? 'Copied' : 'Copy text'}
                                </Button>
                                <Button variant="ghost" size="sm" onClick={() => setResultOpen((value) => !value)}>
                                    {resultOpen ? <ChevronUpIcon className="h-4 w-4" /> : <ChevronDownIcon className="h-4 w-4" />}
                                    {resultOpen ? 'Collapse' : 'Expand'}
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <Collapsible open={resultOpen} onOpenChange={setResultOpen}>
                        <CollapsibleContent className="data-[state=closed]:animate-out data-[state=closed]:fade-out data-[state=open]:animate-in data-[state=open]:fade-in">
                            <CardContent>
                                <div className="border-sidebar-border/70 bg-background dark:border-sidebar-border rounded-lg border p-5">
                                    <SummaryMarkdown text={summaryText} />
                                </div>
                            </CardContent>
                        </CollapsibleContent>
                    </Collapsible>
                </Card>
            ) : null}
        </div>
    );
}
