import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Plan, type User } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FileTextIcon, MoreHorizontalIcon, ShieldCheckIcon, UsersIcon } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

interface AdminSubscription {
    id: number;
    user_id: number;
    plan_id: number | null;
    gateway: string;
    gateway_customer_id: string;
    gateway_subscription_id: string;
    status: string;
    current_period_end?: string | null;
    cancelled_at?: string | null;
    trial_ends_at?: string | null;
    created_at: string;
    updated_at: string;
}

interface AdminUser extends User {
    role: 'admin' | 'user';
    plan_id: number | null;
    pdf_count: number;
    pdf_count_resets_at?: string | null;
    plan?: Plan | null;
    subscription?: AdminSubscription | null;
    pdf_summaries_count?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: AdminUser[];
    current_page: number;
    from: number | null;
    links: PaginationLink[];
    per_page: number;
    to: number | null;
    total: number;
}

interface AdminUsersProps {
    users: PaginatedUsers;
    plans: Plan[];
}

function formatDate(date: string | null | undefined): string {
    if (!date) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(date));
}

function formatLimit(limit: number | undefined): string {
    if (typeof limit !== 'number') {
        return 'Not set';
    }

    return limit < 0 ? 'Unlimited' : `${limit}`;
}

function formatPaginationLabel(label: string): string {
    return label.replace(/&laquo;|&raquo;/g, '').trim();
}

function planLabel(plan: Plan | null | undefined): string {
    return plan ? `${plan.name} (${plan.slug})` : 'No plan';
}

function subscriptionStatusVariant(status: string | undefined): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'active') {
        return 'secondary';
    }

    if (status === 'past_due' || status === 'unpaid') {
        return 'destructive';
    }

    return 'outline';
}

function AdminUserActions({ user, plans }: { user: AdminUser; plans: Plan[] }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        plan_id: String(user.plan_id ?? ''),
    });

    const openPlanDialog = () => {
        reset();
        setData('plan_id', String(user.plan_id ?? ''));
        setOpen(true);
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('admin.users.update-plan', user.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <>
            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" size="icon" aria-label={`Open actions for ${user.email}`}>
                        <MoreHorizontalIcon className="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        onSelect={(event) => {
                            event.preventDefault();
                            openPlanDialog();
                        }}
                    >
                        Change subscription
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Change subscription</DialogTitle>
                        <DialogDescription>Assign a plan to {user.email}. This updates the database directly.</DialogDescription>
                    </DialogHeader>

                    <form className="space-y-4" onSubmit={submit}>
                        <div className="space-y-2">
                            <Label htmlFor={`plan-${user.id}`}>Plan</Label>
                            <Select value={data.plan_id} onValueChange={(value) => setData('plan_id', value)}>
                                <SelectTrigger id={`plan-${user.id}`}>
                                    <SelectValue placeholder="Select plan" />
                                </SelectTrigger>
                                <SelectContent>
                                    {plans.map((plan) => (
                                        <SelectItem key={plan.id} value={String(plan.id)}>
                                            {plan.name} - {formatLimit(plan.pdf_limit)} PDFs / month
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.plan_id ? <p className="text-destructive text-sm">{errors.plan_id}</p> : null}
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="secondary" onClick={() => setOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={processing || !data.plan_id}>
                                Update
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}

export default function AdminUsers({ users, plans }: AdminUsersProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <HeadingSmall title="Admin panel" description="Manage users, plans, and subscription records" />
                    <div className="grid grid-cols-3 gap-2 text-sm sm:min-w-[360px]">
                        <div className="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                            <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                <UsersIcon className="h-4 w-4" />
                                Users
                            </div>
                            <div className="mt-1 text-xl font-semibold">{users.total}</div>
                        </div>
                        <div className="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                            <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                <ShieldCheckIcon className="h-4 w-4" />
                                Plans
                            </div>
                            <div className="mt-1 text-xl font-semibold">{plans.length}</div>
                        </div>
                        <div className="rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
                            <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                <FileTextIcon className="h-4 w-4" />
                                Showing
                            </div>
                            <div className="mt-1 text-xl font-semibold">{users.data.length}</div>
                        </div>
                    </div>
                </div>

                <Card className="border-sidebar-border/70 dark:border-sidebar-border">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base">Users</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/40 text-muted-foreground border-y text-left text-xs uppercase">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">User</th>
                                        <th className="px-4 py-3 font-medium">Role</th>
                                        <th className="px-4 py-3 font-medium">Plan</th>
                                        <th className="px-4 py-3 font-medium">Usage</th>
                                        <th className="px-4 py-3 font-medium">Subscription</th>
                                        <th className="px-4 py-3 font-medium">Dates</th>
                                        <th className="px-4 py-3 text-right font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.map((user) => (
                                        <tr key={user.id} className="border-b align-top last:border-b-0">
                                            <td className="min-w-[260px] px-4 py-4">
                                                <div className="font-medium">{user.name}</div>
                                                <div className="text-muted-foreground text-xs">{user.email}</div>
                                                <div className="text-muted-foreground mt-2 grid gap-1 text-xs">
                                                    <span>ID: {user.id}</span>
                                                    <span>Email verified: {formatDate(user.email_verified_at)}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={user.role === 'admin' ? 'default' : 'outline'} className="capitalize">
                                                    {user.role}
                                                </Badge>
                                            </td>
                                            <td className="min-w-[190px] px-4 py-4">
                                                <div className="font-medium">{planLabel(user.plan)}</div>
                                                <div className="text-muted-foreground mt-1 text-xs">Plan ID: {user.plan_id ?? 'Not set'}</div>
                                                <div className="text-muted-foreground text-xs">Limit: {formatLimit(user.plan?.pdf_limit)} PDFs</div>
                                            </td>
                                            <td className="min-w-[160px] px-4 py-4">
                                                <div>{user.pdf_count} PDFs used</div>
                                                <div className="text-muted-foreground text-xs">Summaries: {user.pdf_summaries_count ?? 0}</div>
                                                <div className="text-muted-foreground text-xs">Resets: {formatDate(user.pdf_count_resets_at)}</div>
                                            </td>
                                            <td className="min-w-[280px] px-4 py-4">
                                                <Badge variant={subscriptionStatusVariant(user.subscription?.status)} className="capitalize">
                                                    {user.subscription?.status ?? 'none'}
                                                </Badge>
                                                <div className="text-muted-foreground mt-2 grid gap-1 text-xs">
                                                    <span>Subscription ID: {user.subscription?.id ?? 'Not set'}</span>
                                                    <span>Gateway: {user.subscription?.gateway ?? 'Not set'}</span>
                                                    <span>Customer: {user.subscription?.gateway_customer_id ?? 'Not set'}</span>
                                                    <span>Gateway sub: {user.subscription?.gateway_subscription_id ?? 'Not set'}</span>
                                                    <span>Plan ID: {user.subscription?.plan_id ?? 'Not set'}</span>
                                                </div>
                                            </td>
                                            <td className="min-w-[210px] px-4 py-4 text-xs">
                                                <div>Created: {formatDate(user.created_at)}</div>
                                                <div>Updated: {formatDate(user.updated_at)}</div>
                                                <div>Period end: {formatDate(user.subscription?.current_period_end)}</div>
                                                <div>Cancelled: {formatDate(user.subscription?.cancelled_at)}</div>
                                                <div>Trial ends: {formatDate(user.subscription?.trial_ends_at)}</div>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <AdminUserActions user={user} plans={plans} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {users.data.length === 0 ? <div className="text-muted-foreground p-6 text-sm">No users found.</div> : null}
                    </CardContent>
                </Card>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-muted-foreground text-sm">
                        Showing {users.from ?? 0} to {users.to ?? 0} of {users.total} users
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {users.links.map((link) => (
                            <Button key={`${link.label}-${link.url}`} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url} asChild={!!link.url}>
                                {link.url ? <Link href={link.url} preserveScroll>{formatPaginationLabel(link.label)}</Link> : <span>{formatPaginationLabel(link.label)}</span>}
                            </Button>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}