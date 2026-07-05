import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatLimit, formatNumericDate, formatPaginationLabel } from '@/lib/formatters';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Plan, type User } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowDownIcon,
    ArrowUpIcon,
    CheckCircleIcon,
    ChevronsUpDownIcon,
    FileTextIcon,
    MoreHorizontalIcon,
    SearchIcon,
    ShieldCheckIcon,
    UsersIcon,
    XCircleIcon,
} from 'lucide-react';
import { FormEventHandler, Fragment, ReactNode, useState } from 'react';

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

type SortField = 'id' | 'role' | 'plan_id' | 'created_at' | 'updated_at' | 'email_verified_at' | 'pdf_count_resets_at';
type SortDirection = 'asc' | 'desc';

interface AdminFilters {
    search?: string;
    sort?: SortField | null;
    direction?: SortDirection;
}

interface AdminUsersProps {
    users: PaginatedUsers;
    plans: Plan[];
    filters?: AdminFilters;
}

function planName(plan: Plan | null | undefined): string {
    return plan?.name ?? 'No plan';
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

function usagePercent(user: AdminUser): number {
    const limit = user.plan?.pdf_limit;

    if (typeof limit !== 'number' || limit <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((user.pdf_count / limit) * 100));
}

function compactValue(value: string | number | null | undefined): string {
    if (value === null || typeof value === 'undefined' || value === '') {
        return 'Not set';
    }

    return String(value);
}

function SortIcon({ active, direction }: { active: boolean; direction?: SortDirection }) {
    if (!active) {
        return <ChevronsUpDownIcon className="h-3.5 w-3.5" />;
    }

    return direction === 'asc' ? <ArrowUpIcon className="h-3.5 w-3.5" /> : <ArrowDownIcon className="h-3.5 w-3.5" />;
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

export default function AdminUsers({ users, plans, filters = {} }: AdminUsersProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const activeSort = filters.sort ?? null;
    const activeDirection = filters.direction ?? 'desc';

    const visitUsers = (params: Record<string, string | undefined>) => {
        router.get(route('admin.index'), params, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const queryForCurrentSearch = (nextSort?: SortField, nextDirection?: SortDirection) => ({
        search: search.trim() || undefined,
        sort: nextSort,
        direction: nextSort ? nextDirection : undefined,
    });

    const toggleSort = (field: SortField) => {
        if (activeSort !== field) {
            visitUsers(queryForCurrentSearch(field, 'desc'));
            return;
        }

        if (activeDirection === 'desc') {
            visitUsers(queryForCurrentSearch(field, 'asc'));
            return;
        }

        visitUsers(queryForCurrentSearch());
    };

    const submitSearch: FormEventHandler = (event) => {
        event.preventDefault();
        visitUsers(queryForCurrentSearch(activeSort ?? undefined, activeSort ? activeDirection : undefined));
    };

    const clearSearch = () => {
        setSearch('');
        visitUsers({ sort: activeSort ?? undefined, direction: activeSort ? activeDirection : undefined });
    };

    const SortHeader = ({ field, children, className = '' }: { field: SortField; children: ReactNode; className?: string }) => {
        const active = activeSort === field;

        return (
            <th className={cn('px-3 py-3 font-medium', className)}>
                <button
                    type="button"
                    onClick={() => toggleSort(field)}
                    className="hover:text-foreground inline-flex items-center gap-1.5 transition-colors"
                >
                    {children}
                    <SortIcon active={active} direction={activeDirection} />
                </button>
            </th>
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex w-full flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <HeadingSmall title="Admin panel" description="Manage users, plans, and subscription records" />
                    <div className="grid grid-cols-3 gap-2 text-sm xl:min-w-[360px]">
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                <UsersIcon className="h-4 w-4" />
                                Users
                            </div>
                            <div className="mt-1 text-xl font-semibold">{users.total}</div>
                        </div>
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                <ShieldCheckIcon className="h-4 w-4" />
                                Plans
                            </div>
                            <div className="mt-1 text-xl font-semibold">{plans.length}</div>
                        </div>
                        <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-lg border p-3">
                            <div className="text-muted-foreground flex items-center gap-2 text-xs">
                                <FileTextIcon className="h-4 w-4" />
                                Showing
                            </div>
                            <div className="mt-1 text-xl font-semibold">{users.data.length}</div>
                        </div>
                    </div>
                </div>

                <Card className="border-sidebar-border/70 dark:border-sidebar-border w-full overflow-hidden">
                    <CardHeader className="gap-3 pb-3 lg:flex-row lg:items-center lg:justify-between">
                        <CardTitle className="text-base">Users</CardTitle>
                        <form className="flex w-full flex-col gap-2 sm:flex-row lg:max-w-md" onSubmit={submitSearch}>
                            <div className="relative flex-1">
                                <SearchIcon className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
                                <Input
                                    value={search}
                                    onChange={(event) => setSearch(event.target.value)}
                                    placeholder="Search by email"
                                    className="pl-9"
                                    type="search"
                                />
                            </div>
                            <div className="flex gap-2">
                                <Button type="submit" className="flex-1 sm:flex-none">
                                    Search
                                </Button>
                                {filters.search ? (
                                    <Button type="button" variant="outline" onClick={clearSearch}>
                                        Clear
                                    </Button>
                                ) : null}
                            </div>
                        </form>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div className="relative w-full overflow-x-auto">
                            <table className="w-full min-w-[1080px] table-fixed text-sm">
                                <thead className="bg-muted/40 text-muted-foreground border-y text-left text-xs uppercase">
                                    <tr>
                                        <SortHeader
                                            field="id"
                                            className="bg-muted sticky left-0 z-30 w-[310px] shadow-[8px_0_12px_-14px_rgba(0,0,0,0.45)]"
                                        >
                                            User
                                        </SortHeader>
                                        <SortHeader field="role" className="w-[110px]">
                                            Role
                                        </SortHeader>
                                        <SortHeader field="plan_id" className="w-[160px]">
                                            Plan
                                        </SortHeader>
                                        <th className="w-[160px] px-3 py-3 font-medium">Usage</th>
                                        <th className="w-[230px] px-3 py-3 font-medium">Subscription</th>
                                        <SortHeader field="created_at" className="hidden w-[120px] md:table-cell">
                                            Created
                                        </SortHeader>
                                        <SortHeader field="updated_at" className="hidden w-[120px] xl:table-cell">
                                            Updated
                                        </SortHeader>
                                        <SortHeader field="email_verified_at" className="hidden w-[120px] xl:table-cell">
                                            Verified
                                        </SortHeader>
                                        <SortHeader field="pdf_count_resets_at" className="hidden w-[120px] lg:table-cell">
                                            Reset
                                        </SortHeader>
                                        <th className="bg-muted sticky right-0 z-30 w-[72px] px-3 py-3 text-right font-medium shadow-[-8px_0_12px_-14px_rgba(0,0,0,0.45)]">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.map((user) => (
                                        <Fragment key={user.id}>
                                            <tr key={user.id} className="border-b align-top last:border-b-0">
                                                <td className="bg-card sticky left-0 z-20 w-[310px] px-3 py-3 shadow-[8px_0_12px_-14px_rgba(0,0,0,0.45)]">
                                                    <div className="flex items-start gap-2">
                                                        {user.email_verified_at ? (
                                                            <CheckCircleIcon
                                                                className="mt-0.5 h-4 w-4 shrink-0 text-emerald-600"
                                                                aria-label="Email verified"
                                                            />
                                                        ) : (
                                                            <XCircleIcon
                                                                className="text-muted-foreground mt-0.5 h-4 w-4 shrink-0"
                                                                aria-label="Email not verified"
                                                            />
                                                        )}
                                                        <div className="min-w-0">
                                                            <div className="truncate font-medium">{user.email}</div>
                                                            <div className="text-muted-foreground truncate text-xs">{user.name}</div>
                                                            <div className="text-muted-foreground mt-1 flex flex-wrap items-center gap-1.5 text-xs">
                                                                <span>ID {user.id}</span>
                                                                <Badge
                                                                    variant={user.role === 'admin' ? 'default' : 'outline'}
                                                                    className="h-5 px-1.5 text-[11px] capitalize"
                                                                >
                                                                    {user.role}
                                                                </Badge>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3">
                                                    <Badge variant={user.role === 'admin' ? 'default' : 'outline'} className="capitalize">
                                                        {user.role}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-3">
                                                    <div className="font-medium">{planName(user.plan)}</div>
                                                    <div className="text-muted-foreground mt-1 text-xs">ID {compactValue(user.plan_id)}</div>
                                                    <div className="text-muted-foreground text-xs">Limit {formatLimit(user.plan?.pdf_limit)}</div>
                                                </td>
                                                <td className="px-3 py-3">
                                                    <div className="flex items-center justify-between gap-2 text-xs">
                                                        <span>{user.pdf_count} used</span>
                                                        <span>{formatLimit(user.plan?.pdf_limit)} limit</span>
                                                    </div>
                                                    <div className="bg-secondary mt-2 h-1.5 overflow-hidden rounded-full">
                                                        <div className="bg-primary h-full rounded-full" style={{ width: `${usagePercent(user)}%` }} />
                                                    </div>
                                                    <div className="text-muted-foreground mt-1 text-xs">
                                                        Summaries {user.pdf_summaries_count ?? 0}
                                                    </div>
                                                </td>
                                                <td className="px-3 py-3">
                                                    <Badge variant={subscriptionStatusVariant(user.subscription?.status)} className="capitalize">
                                                        {user.subscription?.status ?? 'none'}
                                                    </Badge>
                                                    <div className="text-muted-foreground mt-2 grid gap-1 text-xs">
                                                        <span>Gateway {compactValue(user.subscription?.gateway)}</span>
                                                        <span>Sub ID {compactValue(user.subscription?.id)}</span>
                                                        <span className="truncate">
                                                            Gateway sub {compactValue(user.subscription?.gateway_subscription_id)}
                                                        </span>
                                                        {user.subscription?.current_period_end ? (
                                                            <span>End {formatNumericDate(user.subscription.current_period_end)}</span>
                                                        ) : null}
                                                        {user.subscription?.cancelled_at ? (
                                                            <span>Cancelled {formatNumericDate(user.subscription.cancelled_at)}</span>
                                                        ) : null}
                                                        {user.subscription?.trial_ends_at ? (
                                                            <span>Trial {formatNumericDate(user.subscription.trial_ends_at)}</span>
                                                        ) : null}
                                                    </div>
                                                </td>
                                                <td className="hidden px-3 py-3 text-xs whitespace-nowrap md:table-cell">
                                                    {formatNumericDate(user.created_at)}
                                                </td>
                                                <td className="hidden px-3 py-3 text-xs whitespace-nowrap xl:table-cell">
                                                    {formatNumericDate(user.updated_at)}
                                                </td>
                                                <td className="hidden px-3 py-3 text-xs whitespace-nowrap xl:table-cell">
                                                    {formatNumericDate(user.email_verified_at)}
                                                </td>
                                                <td className="hidden px-3 py-3 text-xs whitespace-nowrap lg:table-cell">
                                                    {formatNumericDate(user.pdf_count_resets_at)}
                                                </td>
                                                <td className="bg-card sticky right-0 z-20 px-3 py-2 text-right shadow-[-8px_0_12px_-14px_rgba(0,0,0,0.45)]">
                                                    <AdminUserActions user={user} plans={plans} />
                                                </td>
                                            </tr>
                                            <tr className="border-b md:hidden">
                                                <td className="bg-card sticky left-0 z-10 px-3 py-2 text-xs shadow-[8px_0_12px_-14px_rgba(0,0,0,0.45)]">
                                                    <div className="text-muted-foreground grid gap-1">
                                                        <span>Created {formatNumericDate(user.created_at)}</span>
                                                        <span>Updated {formatNumericDate(user.updated_at)}</span>
                                                        <span>Verified {formatNumericDate(user.email_verified_at)}</span>
                                                    </div>
                                                </td>
                                                <td colSpan={8} className="px-3 py-2 text-xs">
                                                    <div className="text-muted-foreground flex flex-wrap gap-x-4 gap-y-1">
                                                        <span>Reset {formatNumericDate(user.pdf_count_resets_at)}</span>
                                                        <span>Plan {planName(user.plan)}</span>
                                                        <span>{user.pdf_count} PDFs used</span>
                                                    </div>
                                                </td>
                                                <td className="bg-card sticky right-0 z-10" />
                                            </tr>
                                        </Fragment>
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
                            <Button
                                key={`${link.label}-${link.url}`}
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
            </div>
        </AppLayout>
    );
}
