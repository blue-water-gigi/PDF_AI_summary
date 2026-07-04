import ChangePlanDialog from '@/components/change-plan-dialog';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem, type Plan, type SubscriptionDetails, type UserStats } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { CalendarClockIcon, CreditCardIcon, FileTextIcon, SparklesIcon } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Subscription settings',
        href: '/settings/subscription',
    },
];

interface SubscriptionSettingsProps {
    plans: Plan[];
    currentPlan?: Plan | null;
    currentPlanSlug?: string | null;
    subscription?: SubscriptionDetails | null;
    userStats?: UserStats | null;
    hasActiveSubscription: boolean;
}

function formatLimit(limit: number | undefined): string {
    if (typeof limit !== 'number') {
        return '0';
    }

    return limit < 0 ? 'Unlimited' : String(limit);
}

function formatDate(date: string | null | undefined): string {
    if (!date) {
        return 'Not available';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(date));
}

function formatDays(days: number | null | undefined): string {
    if (typeof days !== 'number') {
        return 'Not scheduled';
    }

    if (days === 0) {
        return 'Today';
    }

    return days === 1 ? '1 day' : `${days} days`;
}

function formatCurrentPlanRenewal(subscription: SubscriptionDetails | null | undefined, currentPlan: Plan | null | undefined): string {
    if (!subscription?.currentPeriodEnd) {
        return 'No renewal scheduled';
    }

    const renewalPlanSlug = subscription.status === 'canceled' ? 'basic' : (currentPlan?.slug ?? 'basic');

    return `Renews on ${formatDate(subscription.currentPeriodEnd)} on ${renewalPlanSlug}`;
}

export default function SubscriptionSettings({
    plans,
    currentPlan,
    currentPlanSlug = 'basic',
    subscription,
    userStats,
    hasActiveSubscription,
}: SubscriptionSettingsProps) {
    const [cancelDialogOpen, setCancelDialogOpen] = useState(false);
    const [cancellationRequested, setCancellationRequested] = useState(false);
    const { delete: destroy, processing } = useForm({
        gateway: subscription?.gateway ?? 'stripe',
    });

    const unsubscribe: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('subscription.destroy'), {
            preserveScroll: true,
            replace: true,
            onSuccess: () => setCancellationRequested(true),
            onFinish: () => setCancelDialogOpen(false),
        });
    };

    const canCancelSubscription = hasActiveSubscription && !cancellationRequested;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Subscription settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <HeadingSmall title="Subscription" description="Review your current plan, usage, and billing access" />
                        <ChangePlanDialog
                            plans={plans}
                            currentPlanSlug={currentPlanSlug}
                            hasActiveSubscription={hasActiveSubscription}
                            trigger={<Button variant="outline">Change plan</Button>}
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Current plan</CardTitle>
                                <CreditCardIcon className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <span className="text-2xl font-semibold">{currentPlan?.name ?? 'Basic'}</span>
                                    <Badge variant={subscription?.isActive ? 'secondary' : 'outline'} className="capitalize">
                                        {subscription?.status ?? 'free'}
                                    </Badge>
                                </div>
                                <p className="text-muted-foreground text-xs">{formatCurrentPlanRenewal(subscription, currentPlan)}</p>
                            </CardContent>
                        </Card>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Monthly limit</CardTitle>
                                <SparklesIcon className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{formatLimit(userStats?.pdfLimit)}</div>
                                <p className="text-muted-foreground text-xs">PDF files available per billing period</p>
                            </CardContent>
                        </Card>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border sm:col-span-2">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Processed PDFs</CardTitle>
                                <FileTextIcon className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-semibold">{userStats?.pdfCount ?? 0}</div>
                                <p className="text-muted-foreground text-xs">Files processed in the current monthly period</p>
                            </CardContent>
                        </Card>

                        <Card className="border-sidebar-border/70 dark:border-sidebar-border sm:col-span-2">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Renewal timeline</CardTitle>
                                <CalendarClockIcon className="text-muted-foreground h-4 w-4" />
                            </CardHeader>
                            <CardContent className="grid gap-3 sm:grid-cols-2">
                                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                                    <p className="text-muted-foreground text-xs">PDF limit resets in</p>
                                    <p className="mt-1 text-xl font-semibold">{formatDays(userStats?.daysUntilPdfReset)}</p>
                                    <p className="text-muted-foreground mt-1 text-xs">{formatDate(userStats?.pdfResetDate)}</p>
                                </div>

                                <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                                    <p className="text-muted-foreground text-xs">Subscription renews in</p>
                                    <p className="mt-1 text-xl font-semibold">
                                        {hasActiveSubscription ? formatDays(subscription?.daysUntilRenewal) : 'No active subscription'}
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-xs">{formatDate(subscription?.currentPeriodEnd)}</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {canCancelSubscription ? (
                    <div className="space-y-6">
                        <HeadingSmall title="Cancel subscription" description="Stop renewal for your active paid subscription" />
                        <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                            <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                <p className="font-medium">Danger zone</p>
                                <p className="text-sm">Unsubscribing cancels the active subscription through the payment provider.</p>
                            </div>

                            <Dialog open={cancelDialogOpen} onOpenChange={setCancelDialogOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="destructive">Unsubscribe</Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogTitle>Confirm subscription cancellation</DialogTitle>
                                    <DialogDescription>
                                        This will cancel your active subscription. You can choose a new plan later from the dashboard sidebar.
                                    </DialogDescription>
                                    <form className="space-y-6" onSubmit={unsubscribe}>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="secondary">Keep subscription</Button>
                                            </DialogClose>

                                            <Button variant="destructive" disabled={processing} asChild>
                                                <button type="submit">Confirm unsubscribe</button>
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </div>
                    </div>
                ) : null}
            </SettingsLayout>
        </AppLayout>
    );
}
