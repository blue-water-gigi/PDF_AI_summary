import PdfUploadCard from '@/components/pdf-upload-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatLimit } from '@/lib/formatters';
import { type BreadcrumbItem, type UserStats } from '@/types';
import { Head } from '@inertiajs/react';
import { UploadIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    currentPlanSlug?: string | null;
    userStats?: UserStats | null;
}

function remainingLabel(userStats: UserStats | null | undefined): string {
    if (typeof userStats?.pdfLimit !== 'number') {
        return '0';
    }

    if (userStats.pdfLimit < 0) {
        return 'Unlimited';
    }

    return String(Math.max(userStats.pdfLimit - userStats.pdfCount, 0));
}

function usagePercent(userStats: UserStats | null | undefined): number {
    if (!userStats || userStats.pdfLimit <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((userStats.pdfCount / userStats.pdfLimit) * 100));
}

export default function Dashboard({ currentPlanSlug = 'basic', userStats }: DashboardProps) {
    const activePlanSlug = currentPlanSlug ?? 'basic';
    const used = userStats?.pdfCount ?? 0;
    const limit = formatLimit(userStats?.pdfLimit);
    const remaining = remainingLabel(userStats);
    const percent = usagePercent(userStats);
    const limitReached = userStats?.canUpload === false;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">PDF Summarizer</h1>
                    <p className="text-muted-foreground text-sm">Upload a document and choose the summary style that fits your plan.</p>
                </div>

                <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">Upload status</CardTitle>
                        <UploadIcon className="text-muted-foreground h-4 w-4" />
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                            <div>
                                <div className="text-4xl font-semibold tracking-tight">{remaining}</div>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {limitReached ? 'PDF limit reached' : 'PDF files remaining this billing period'}
                                </p>
                            </div>
                            <div className="min-w-[240px] space-y-2">
                                <div className="text-muted-foreground flex justify-between text-xs">
                                    <span>{used} used</span>
                                    <span>{limit} limit</span>
                                </div>
                                <div className="bg-secondary h-2 overflow-hidden rounded-full">
                                    <div className="bg-primary h-full rounded-full transition-all duration-500" style={{ width: `${percent}%` }} />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <PdfUploadCard userStats={userStats} userPlanSlug={activePlanSlug} className="min-h-[320px]" />
            </div>
        </AppLayout>
    );
}
