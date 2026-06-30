import PdfUploadCard from '@/components/pdf-upload-card';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type UserStats } from '@/types';
import { Head } from '@inertiajs/react';
import { FileTextIcon, SparklesIcon, UploadIcon } from 'lucide-react';

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

function formatLimit(limit: number | undefined): string {
    if (typeof limit !== 'number') {
        return '0';
    }

    return limit < 0 ? 'Unlimited' : String(limit);
}

export default function Dashboard({ currentPlanSlug = 'basic', userStats }: DashboardProps) {
    const activePlanSlug = currentPlanSlug ?? 'basic';
    const pdfLimit = formatLimit(userStats?.pdfLimit);
    const remainingUploads =
        typeof userStats?.pdfLimit === 'number' && userStats.pdfLimit > 0 ? Math.max(userStats.pdfLimit - userStats.pdfCount, 0) : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">PDF Summarizer</h1>
                    <p className="text-muted-foreground text-sm">Upload a document and choose the summary style that fits your plan.</p>
                </div>

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">PDFs used</CardTitle>
                            <FileTextIcon className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">{userStats?.pdfCount ?? 0}</div>
                            <p className="text-muted-foreground text-xs">This billing period</p>
                        </CardContent>
                    </Card>

                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Monthly limit</CardTitle>
                            <SparklesIcon className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">{pdfLimit}</div>
                            <p className="text-muted-foreground text-xs">Included in your current subscription</p>
                        </CardContent>
                    </Card>

                    <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Upload status</CardTitle>
                            <UploadIcon className="text-muted-foreground h-4 w-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-semibold">{(userStats?.canUpload ?? true) ? 'Ready' : 'Limit reached'}</div>
                            <p className="text-muted-foreground text-xs">
                                {remainingUploads === null ? 'Unlimited uploads available' : `${remainingUploads} PDFs remaining`}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <PdfUploadCard userStats={userStats} userPlanSlug={activePlanSlug} className="min-h-[320px]" />
            </div>
        </AppLayout>
    );
}
