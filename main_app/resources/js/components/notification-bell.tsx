import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatShortDate } from '@/lib/formatters';
import {
    type AppNotification,
    fetchNotifications,
    markAllNotificationsAsRead,
    markNotificationAsRead,
    notificationText,
    type PaginatedNotifications,
} from '@/lib/notifications';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { BellIcon, CheckIcon, LoaderCircle } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

function notificationTitle(notification: AppNotification): string {
    return notificationText(notification.data.title, 'Notification');
}

function notificationMessage(notification: AppNotification): string {
    return notificationText(notification.data.message, 'Open the notification to see details.');
}

function notificationMeta(notification: AppNotification): string | null {
    const filename = notificationText(notification.data.filename, '');
    const summaryType = notificationText(notification.data.summary_type, '').replaceAll('_', ' ');

    if (filename && summaryType) {
        return `${filename} - ${summaryType}`;
    }

    return filename || summaryType || null;
}

export default function NotificationBell() {
    const page = usePage<SharedData>();
    const sharedUnreadCount = page.props.notifications?.unreadCount ?? 0;
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [updatingId, setUpdatingId] = useState<string | null>(null);
    const [markingAll, setMarkingAll] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [notifications, setNotifications] = useState<AppNotification[]>([]);
    const [nextPageUrl, setNextPageUrl] = useState<string | null>(null);

    const unreadLoadedCount = useMemo(() => notifications.filter((notification) => !notification.read_at).length, [notifications]);
    const hasUnread = sharedUnreadCount > 0 || unreadLoadedCount > 0;

    const loadNotifications = async (url?: string, append = false) => {
        append ? setLoadingMore(true) : setLoading(true);
        setError(null);

        try {
            const payload: PaginatedNotifications = await fetchNotifications(url);
            setNotifications((current) => (append ? [...current, ...payload.data] : payload.data));
            setNextPageUrl(payload.next_page_url);
        } catch (caughtError) {
            setError(caughtError instanceof Error ? caughtError.message : 'Failed to load notifications.');
        } finally {
            append ? setLoadingMore(false) : setLoading(false);
        }
    };

    useEffect(() => {
        if (open) {
            void loadNotifications();
        }
    }, [open]);

    const markOne = async (notification: AppNotification) => {
        setUpdatingId(notification.id);
        setError(null);
        setNotifications((current) => current.map((item) => (item.id === notification.id ? { ...item, read_at: new Date().toISOString() } : item)));

        try {
            await markNotificationAsRead(notification.id);
            router.reload({ only: ['notifications'], preserveScroll: true });
        } catch (caughtError) {
            setNotifications((current) => current.map((item) => (item.id === notification.id ? { ...item, read_at: notification.read_at } : item)));
            setError(caughtError instanceof Error ? caughtError.message : 'Failed to update notification.');
        } finally {
            setUpdatingId(null);
        }
    };

    const markAll = async () => {
        setMarkingAll(true);
        setError(null);
        const previousNotifications = notifications;
        setNotifications((current) =>
            current.map((notification) => ({ ...notification, read_at: notification.read_at ?? new Date().toISOString() })),
        );

        try {
            await markAllNotificationsAsRead();
            router.reload({ only: ['notifications'], preserveScroll: true });
        } catch (caughtError) {
            setNotifications(previousNotifications);
            setError(caughtError instanceof Error ? caughtError.message : 'Failed to update notifications.');
        } finally {
            setMarkingAll(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <Button variant="ghost" size="icon" className="relative shrink-0" aria-label="Open notifications" onClick={() => setOpen(true)}>
                <BellIcon className="h-4 w-4" />
                {sharedUnreadCount > 0 ? (
                    <span className="bg-primary text-primary-foreground absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full px-1 text-[10px] font-medium">
                        {sharedUnreadCount > 9 ? '9+' : sharedUnreadCount}
                    </span>
                ) : null}
            </Button>

            <DialogContent className="w-[calc(100vw-2rem)] overflow-hidden p-0 sm:max-w-[560px]">
                <DialogHeader className="border-sidebar-border/70 dark:border-sidebar-border border-b px-5 pt-5 pr-12 pb-4">
                    <div className="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div className="min-w-0">
                            <DialogTitle>Notifications</DialogTitle>
                            <DialogDescription className="mt-1">Recent account, subscription, and summary updates.</DialogDescription>
                        </div>
                        {hasUnread ? (
                            <Button variant="outline" size="sm" onClick={markAll} disabled={markingAll} className="shrink-0 self-start">
                                {markingAll ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <CheckIcon className="h-4 w-4" />}
                                Mark all
                            </Button>
                        ) : null}
                    </div>
                </DialogHeader>

                <div className="space-y-3 px-5 py-4">
                    {error ? <div className="text-destructive border-destructive/40 rounded-lg border p-3 text-sm">{error}</div> : null}

                    {loading ? (
                        <div className="text-muted-foreground flex min-h-[220px] items-center justify-center gap-2 text-sm">
                            <LoaderCircle className="h-4 w-4 animate-spin" />
                            Loading notifications...
                        </div>
                    ) : notifications.length > 0 ? (
                        <div className="max-h-[430px] space-y-2 overflow-y-auto pr-1">
                            {notifications.map((notification) => {
                                const unread = !notification.read_at;
                                const meta = notificationMeta(notification);

                                return (
                                    <div
                                        key={notification.id}
                                        className={cn(
                                            'rounded-lg border p-3 transition-colors',
                                            unread ? 'border-primary/30 bg-primary/5' : 'border-sidebar-border/70 dark:border-sidebar-border',
                                        )}
                                    >
                                        <div className="grid min-w-0 grid-cols-[8px_minmax(0,1fr)] gap-3 sm:grid-cols-[8px_minmax(0,1fr)_auto]">
                                            <span className={cn('mt-1.5 h-2 w-2 rounded-full', unread ? 'bg-primary' : 'bg-transparent')} />
                                            <div className="min-w-0">
                                                <div className="flex min-w-0 flex-wrap items-center gap-2">
                                                    <div className="min-w-0 text-sm font-medium break-words">{notificationTitle(notification)}</div>
                                                    {unread ? (
                                                        <Badge variant="secondary" className="h-5 px-1.5 text-[11px]">
                                                            New
                                                        </Badge>
                                                    ) : null}
                                                </div>
                                                <p className="text-muted-foreground mt-1 text-sm break-words">{notificationMessage(notification)}</p>
                                                {meta ? <p className="text-muted-foreground mt-1 text-xs break-words capitalize">{meta}</p> : null}
                                                <div className="text-muted-foreground mt-2 text-xs">{formatShortDate(notification.created_at)}</div>
                                            </div>
                                            {unread ? (
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => void markOne(notification)}
                                                    disabled={updatingId === notification.id}
                                                    className="col-start-2 w-fit shrink-0 sm:col-start-auto"
                                                >
                                                    {updatingId === notification.id ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                                    Mark as read
                                                </Button>
                                            ) : null}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="text-muted-foreground flex min-h-[220px] flex-col items-center justify-center rounded-lg border border-dashed text-center text-sm">
                            <BellIcon className="mb-3 h-6 w-6" />
                            No notifications yet
                        </div>
                    )}

                    {nextPageUrl ? (
                        <Button variant="outline" className="w-full" onClick={() => void loadNotifications(nextPageUrl, true)} disabled={loadingMore}>
                            {loadingMore ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                            Load more
                        </Button>
                    ) : null}
                </div>
            </DialogContent>
        </Dialog>
    );
}
