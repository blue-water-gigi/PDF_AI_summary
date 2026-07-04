import ChangePlanDialog from '@/components/change-plan-dialog';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { CreditCardIcon, HistoryIcon, LayoutGrid, RefreshCwIcon, ShieldCheckIcon } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'History',
        url: '/dashboard/history',
        icon: HistoryIcon,
    },
];

export function AppSidebar() {
    const page = usePage<SharedData>();
    const isAdmin = page.props.auth.user.role === 'admin';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />

                {isAdmin ? (
                    <SidebarGroup className="px-2 py-0">
                        <SidebarGroupLabel>Admin panel</SidebarGroupLabel>
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton asChild isActive={page.url.startsWith('/users')} tooltip="Manage users">
                                    <Link href="/users" prefetch>
                                        <ShieldCheckIcon />
                                        <span>Manage</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroup>
                ) : null}

                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Subscription</SidebarGroupLabel>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <ChangePlanDialog
                                trigger={
                                    <SidebarMenuButton tooltip="Change plan">
                                        <RefreshCwIcon />
                                        <span>Change plan</span>
                                    </SidebarMenuButton>
                                }
                            />
                        </SidebarMenuItem>
                        <SidebarMenuItem>
                            <SidebarMenuButton asChild isActive={page.url.startsWith('/settings/subscription')} tooltip="Manage subscription">
                                <Link href="/settings/subscription" prefetch>
                                    <CreditCardIcon />
                                    <span>Manage subscription</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
