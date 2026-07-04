import SummaryMarkdown from '@/components/summary-markdown';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CheckCircleIcon, CopyIcon, FileTextIcon, HistoryIcon } from 'lucide-react';
import { useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'History',
        href: '/dashboard/history',
    },
];

type SummaryPayload = string | string[] | Record<string, unknown>;

interface PdfSummary {
    id: number;
    user_id: number;
    filename: string;
    summary: SummaryPayload;
    summary_type: string;
    file_size?: number | null;
    created_at: string;
    updated_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedSummaries {
    data: PdfSummary[];
    current_page: number;
    from: number | null;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
}

interface HistoryProps {
    summaries: PaginatedSummaries;
}

function formatDate(date: string | null | undefined): string {
    if (!date) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
}

function formatFileSize(size: number | null | undefined): string {
    if (!size) {
        return 'Not set';
    }

    if (size < 1024 * 1024) {
        return `${Math.max(1, Math.round(size / 1024))} KB`;
    }

    return `${(size / 1024 / 1024).toFixed(1)} MB`;
}

function formatSummaryType(type: string): string {
    return type
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function formatPaginationLabel(label: string): string {
    return label.replace(/&laquo;\s*/g, '').replace(/\s*&raquo;/g, '').trim();
}

function normalizeSummary(summary: SummaryPayload | null | undefined): string {
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

function truncateSummary(summary: string): string {
    const plainText = summary
        .replace(/^#{1,6}\s+/gm, '')
        .replace(/\*\*/g, '')
        .replace(/`/g, '')
        .trim();

    return plainText.length > 180 ? `${plainText.slice(0, 180)}...` : plainText;
}

export default function DashboardHistory({ summaries }: HistoryProps) {
    const normalizedSummaries = useMemo(
        () =>
            summaries.data.map((summary) => ({
                ...summary,
                summary: normalizeSummary(summary.summary),
            })),
        [summaries.data],
    );
    const [selectedId, setSelectedId] = useState<number | null>(normalizedSummaries[0]?.id ?? null);
    const [copied, setCopied] = useState(false);
    const selectedSummary = useMemo(
        () => normalizedSummaries.find((summary) => summary.id === selectedId) ?? normalizedSummaries[0] ?? null,
        [selectedId, normalizedSummaries],
    );

    const copySummary = async () => {
        if (!selectedSummary?.summary) {
            return;
        }

        try {
            await navigator.clipboard.writeText(selectedSummary.summary);
        } catch {
            const textarea = document.createElement('textarea');
            textarea.value = selectedSummary.summary;
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
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Summary history" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Summary history</h1>
                        <p className="text-muted-foreground text-sm">Review your generated PDF summaries and copy results when needed.</p>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3 sm:min-w-[150px]">
                        <div className="text-muted-foreground flex items-center gap-2 text-xs">
                            <HistoryIcon className="h-4 w-4" />
                            Total summaries
                        </div>
                        <div className="mt-1 text-xl font-semibold">{summaries.total}</div>
                    </div>
                </div>

                {normalizedSummaries.length > 0 ? (
                    <div className="grid gap-4 xl:grid-cols-[420px_minmax(0,1fr)]">
                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Documents</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {normalizedSummaries.map((summary) => (
                                    <button
                                        key={summary.id}
                                        type="button"
                                        onClick={() => {
                                            setSelectedId(summary.id);
                                            setCopied(false);
                                        }}
                                        className={cn(
                                            'w-full rounded-lg border p-4 text-left transition-colors',
                                            selectedSummary?.id === summary.id
                                                ? 'border-primary bg-primary/5'
                                                : 'border-sidebar-border/70 hover:bg-accent/40 dark:border-sidebar-border',
                                        )}
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-2 font-medium">
                                                    <FileTextIcon className="h-4 w-4 shrink-0" />
                                                    <span className="truncate">{summary.filename}</span>
                                                </div>
                                                <p className="text-muted-foreground mt-2 line-clamp-3 text-sm">{truncateSummary(summary.summary)}</p>
                                            </div>
                                            <Badge variant="outline" className="shrink-0">
                                                #{summary.id}
                                            </Badge>
                                        </div>
                                        <div className="text-muted-foreground mt-3 flex flex-wrap gap-2 text-xs">
                                            <span>{formatSummaryType(summary.summary_type)}</span>
                                            <span>{formatFileSize(summary.file_size)}</span>
                                            <span>{formatDate(summary.created_at)}</span>
                                        </div>
                                    </button>
                                ))}
                            </CardContent>
                        </Card>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader className="pb-3">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <CardTitle className="truncate text-base">{selectedSummary?.filename}</CardTitle>
                                        <div className="text-muted-foreground mt-2 flex flex-wrap gap-2 text-xs">
                                            <Badge variant="secondary">{formatSummaryType(selectedSummary?.summary_type ?? '')}</Badge>
                                            <span>Created: {formatDate(selectedSummary?.created_at)}</span>
                                            <span>Updated: {formatDate(selectedSummary?.updated_at)}</span>
                                            <span>Size: {formatFileSize(selectedSummary?.file_size)}</span>
                                        </div>
                                    </div>
                                    <Button variant="outline" size="sm" onClick={copySummary}>
                                        {copied ? <CheckCircleIcon className="h-4 w-4" /> : <CopyIcon className="h-4 w-4" />}
                                        {copied ? 'Copied' : 'Copy text'}
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="border-sidebar-border/70 bg-background dark:border-sidebar-border max-h-[70vh] overflow-y-auto rounded-lg border p-5">
                                    <SummaryMarkdown text={selectedSummary?.summary ?? ''} />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                ) : (
                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardContent className="flex min-h-[280px] flex-col items-center justify-center text-center">
                            <div className="bg-secondary mb-4 flex h-14 w-14 items-center justify-center rounded-lg">
                                <HistoryIcon className="h-6 w-6" />
                            </div>
                            <h2 className="text-lg font-semibold">No summaries yet</h2>
                            <p className="text-muted-foreground mt-1 text-sm">Generated PDF summaries will appear here.</p>
                            <Button className="mt-4" asChild>
                                <Link href="/dashboard">Create summary</Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {summaries.links.length > 3 ? (
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-muted-foreground text-sm">
                            Showing {summaries.from ?? 0} to {summaries.to ?? 0} of {summaries.total} summaries
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {summaries.links.map((link, index) => (
                                <Button
                                    key={`pagination-${index}`}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    disabled={!link.url}
                                    asChild={!!link.url}
                                >
                                    {link.url ? (
                                        <Link href={link.url} preserveScroll>
                                            {formatPaginationLabel(link.label)}
                                        </Link>
                                    ) : (
                                        <span>{formatPaginationLabel(link.label)}</span>
                                    )}
                                </Button>
                            ))}
                        </div>
                    </div>
                ) : null}
            </div>
        </AppLayout>
    );
}
