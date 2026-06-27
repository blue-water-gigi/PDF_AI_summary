import PdfUploadCard from '@/components/pdf-upload-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { ArrowUpRightIcon, FileTextIcon, SparklesIcon, UploadIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface UserStats {
    pdfCount: number;
    pdfLimit: number;
    canUpload: boolean;
}

interface Plan {
    id: number;
    name: string;
    slug: string;
    price: number;
}

interface DashboardProps {
    currentPlanSlug?: string | null;
    plans?: Plan[];
    userStats?: UserStats | null;
}

const planLevels: Record<string, number> = {
    basic: 1,
    standard: 2,
    premium: 3,
};

function formatLimit(limit: number | undefined): string {
    if (typeof limit !== 'number') {
        return '0';
    }

    return limit < 0 ? 'Unlimited' : String(limit);
}

export default function Dashboard({ currentPlanSlug = 'basic', plans = [], userStats }: DashboardProps) {
    const activePlanSlug = currentPlanSlug ?? 'basic';
    const activePlanLevel = planLevels[activePlanSlug] ?? 1;
    const upgradePlan = plans.find((plan) => (planLevels[plan.slug] ?? Number.MAX_SAFE_INTEGER) > activePlanLevel && plan.price > 0);
    const pdfLimit = formatLimit(userStats?.pdfLimit);
    const remainingUploads =
        typeof userStats?.pdfLimit === 'number' && userStats.pdfLimit > 0 ? Math.max(userStats.pdfLimit - userStats.pdfCount, 0) : null;

    const handleUpgrade = () => {
        if (!upgradePlan) {
            return;
        }

        router.post(route('subscription.store'), {
            plan_id: upgradePlan.id,
            gateway: 'stripe',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">PDF Summarizer</h1>
                        <p className="text-muted-foreground text-sm">Upload a document and choose the summary style that fits your plan.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="secondary" className="w-fit capitalize">
                            {activePlanSlug} plan
                        </Badge>
                        {upgradePlan ? (
                            <Button size="sm" onClick={handleUpgrade} className="gap-2">
                                Upgrade to {upgradePlan.name}
                                <ArrowUpRightIcon className="h-4 w-4" />
                            </Button>
                        ) : null}
                    </div>
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
                            <p className="text-muted-foreground text-xs">Included in your current plan</p>
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
