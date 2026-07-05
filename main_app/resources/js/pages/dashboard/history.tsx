import SummaryMarkdown from '@/components/summary-markdown';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatNumericDateTime, formatPaginationLabel, formatShortDate, formatSummaryType } from '@/lib/formatters';
import { markNotificationAsRead } from '@/lib/notifications';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type NotificationSummaryItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircleIcon, CopyIcon, FileTextIcon, HistoryIcon } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

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

export default function DashboardHistory({ summaries }: HistoryProps) {
    const page = usePage<SharedData>();
    const sharedUnreadSummaryItems = page.props.notifications?.unreadSummaryItems;
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
    const [unreadSummaryItems, setUnreadSummaryItems] = useState<NotificationSummaryItem[]>(sharedUnreadSummaryItems ?? []);
    const selectedSummary = useMemo(
        () => normalizedSummaries.find((summary) => summary.id === selectedId) ?? normalizedSummaries[0] ?? null,
        [selectedId, normalizedSummaries],
    );

    useEffect(() => {
        setUnreadSummaryItems(sharedUnreadSummaryItems ?? []);
    }, [sharedUnreadSummaryItems]);

    useEffect(() => {
        if (!selectedSummary) {
            return;
        }
        const unreadNotification = unreadSummaryItems.find((notification) => notification.summaryId === selectedSummary.id);
        if (unreadNotification) {
            void markSummaryNotificationAsViewed(unreadNotification);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectedSummary?.id, unreadSummaryItems]);

    const unreadNotificationForSummary = (summaryId: number): NotificationSummaryItem | undefined => {
        return unreadSummaryItems.find((notification) => notification.summaryId === summaryId);
    };

    const markSummaryNotificationAsViewed = async (notification: NotificationSummaryItem) => {
        setUnreadSummaryItems((current) => current.filter((item) => item.id !== notification.id));

        try {
            await markNotificationAsRead(notification.id);
            router.reload({ only: ['notifications'], preserveScroll: true });
        } catch {
            setUnreadSummaryItems((current) => (current.some((item) => item.id === notification.id) ? current : [...current, notification]));
        }
    };

    const openSummary = (summary: (typeof normalizedSummaries)[number]) => {
        setSelectedId(summary.id);
        setCopied(false);

        const unreadNotification = unreadNotificationForSummary(summary.id);

        if (unreadNotification) {
            void markSummaryNotificationAsViewed(unreadNotification);
        }
    };

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
                    <div className="grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Documents</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {normalizedSummaries.map((summary) => {
                                    const isNew = Boolean(unreadNotificationForSummary(summary.id));

                                    return (
                                        <button
                                            key={summary.id}
                                            type="button"
                                            onClick={() => openSummary(summary)}
                                            className={cn(
                                                'w-full rounded-lg border p-3 text-left transition-colors',
                                                selectedSummary?.id === summary.id
                                                    ? 'border-primary bg-primary/5'
                                                    : 'border-sidebar-border/70 hover:bg-accent/40 dark:border-sidebar-border',
                                            )}
                                        >
                                            <div className="flex items-start gap-3">
                                                <div className="bg-secondary relative mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md">
                                                    <FileTextIcon className="h-4 w-4" />
                                                    {isNew ? <span className="bg-primary absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full" /> : null}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <div className="truncate text-sm font-medium">{summary.filename}</div>
                                                        {isNew ? (
                                                            <Badge variant="secondary" className="h-5 px-1.5 text-[11px]">
                                                                New
                                                            </Badge>
                                                        ) : null}
                                                    </div>
                                                    <div className="text-muted-foreground mt-1 flex flex-wrap items-center gap-2 text-xs">
                                                        <Badge variant="outline" className="h-5 px-1.5 text-[11px]">
                                                            {formatSummaryType(summary.summary_type)}
                                                        </Badge>
                                                        <span>{formatShortDate(summary.created_at)}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </button>
                                    );
                                })}
                            </CardContent>
                        </Card>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader className="pb-3">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            <CardTitle className="truncate text-base">{selectedSummary?.filename}</CardTitle>
                                            {selectedSummary && unreadNotificationForSummary(selectedSummary.id) ? (
                                                <Badge variant="secondary" className="h-5 px-1.5 text-[11px]">
                                                    New
                                                </Badge>
                                            ) : null}
                                        </div>
                                        <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-2 text-xs">
                                            <Badge variant="secondary">{formatSummaryType(selectedSummary?.summary_type ?? '')}</Badge>
                                            <span>{formatNumericDateTime(selectedSummary?.created_at)}</span>
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
