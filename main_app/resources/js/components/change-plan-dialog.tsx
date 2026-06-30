import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { type Plan, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { CheckIcon } from 'lucide-react';
import { ReactNode, useState } from 'react';

interface ChangePlanDialogProps {
    trigger: ReactNode;
    plans?: Plan[];
    currentPlanSlug?: string | null;
    hasActiveSubscription?: boolean;
}

function formatPrice(price: number): string {
    return price > 0 ? `$${price.toFixed(2)}` : 'Free';
}

function formatLimit(limit: number): string {
    return limit < 0 ? 'Unlimited PDFs / month' : `${limit} PDFs / month`;
}

function getActionLabel(plan: Plan, isCurrentPlan: boolean, hasActiveSubscription: boolean): string {
    if (isCurrentPlan) {
        return 'Current plan';
    }

    if (hasActiveSubscription) {
        return 'Switch plan';
    }

    return plan.price > 0 ? 'Choose plan' : 'Unavailable';
}

export default function ChangePlanDialog({ trigger, plans, currentPlanSlug, hasActiveSubscription }: ChangePlanDialogProps) {
    const { subscriptionData } = usePage<SharedData>().props;
    const [open, setOpen] = useState(false);

    const availablePlans = plans ?? subscriptionData?.plans ?? [];
    const activePlanSlug = currentPlanSlug ?? subscriptionData?.currentPlanSlug ?? 'basic';
    const hasSubscription = hasActiveSubscription ?? subscriptionData?.hasActiveSubscription ?? false;

    const selectPlan = (plan: Plan) => {
        if (plan.slug === activePlanSlug) {
            return;
        }

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (hasSubscription) {
            router.patch(
                route('subscription.update'),
                {
                    new_plan_id: plan.id,
                    gateway: 'stripe',
                },
                options,
            );

            return;
        }

        if (plan.price <= 0) {
            return;
        }

        router.post(
            route('subscription.store'),
            {
                plan_id: plan.id,
                gateway: 'stripe',
            },
            options,
        );
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Change plan</DialogTitle>
                    <DialogDescription>Select the monthly PDF limit and features that fit your workflow.</DialogDescription>
                </DialogHeader>

                <div className="grid gap-3 md:grid-cols-3">
                    {availablePlans.map((plan) => {
                        const isCurrentPlan = plan.slug === activePlanSlug;
                        const canSelect = !isCurrentPlan && (hasSubscription || plan.price > 0);

                        return (
                            <div
                                key={plan.id}
                                className={cn(
                                    'bg-card text-card-foreground flex min-h-[260px] flex-col rounded-lg border p-4 shadow-xs',
                                    isCurrentPlan ? 'border-primary' : 'border-sidebar-border/70 dark:border-sidebar-border',
                                )}
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 className="text-base font-semibold">{plan.name}</h3>
                                        <p className="text-muted-foreground mt-1 text-xs">{plan.description}</p>
                                    </div>
                                    {isCurrentPlan ? <Badge variant="secondary">Current</Badge> : null}
                                </div>

                                <div className="mt-4">
                                    <span className="text-2xl font-semibold">{formatPrice(plan.price)}</span>
                                    {plan.price > 0 ? <span className="text-muted-foreground text-xs"> / month</span> : null}
                                    <p className="text-muted-foreground mt-1 text-sm">{formatLimit(plan.pdf_limit)}</p>
                                </div>

                                <ul className="mt-4 flex-1 space-y-2 text-sm">
                                    {(plan.features ?? []).slice(0, 4).map((feature) => (
                                        <li key={feature} className="flex gap-2">
                                            <CheckIcon className="text-primary mt-0.5 h-4 w-4 shrink-0" />
                                            <span>{feature}</span>
                                        </li>
                                    ))}
                                </ul>

                                <Button
                                    className="mt-5 w-full"
                                    variant={isCurrentPlan ? 'secondary' : 'default'}
                                    disabled={!canSelect}
                                    onClick={() => selectPlan(plan)}
                                >
                                    {getActionLabel(plan, isCurrentPlan, hasSubscription)}
                                </Button>
                            </div>
                        );
                    })}
                </div>
            </DialogContent>
        </Dialog>
    );
}
