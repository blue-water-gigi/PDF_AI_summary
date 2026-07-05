export interface AppNotification {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface PaginatedNotifications {
    data: AppNotification[];
    current_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    from: number | null;
    to: number | null;
    total: number;
}

export function getXsrfToken(): string {
    return decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '');
}

export async function fetchNotifications(url: string = route('notifications.index')): Promise<PaginatedNotifications> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Failed to load notifications.');
    }

    return response.json();
}

async function postNotificationAction(url: string): Promise<void> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': getXsrfToken(),
        },
    });

    if (!response.ok) {
        throw new Error('Failed to update notification.');
    }
}

export async function markNotificationAsRead(id: string): Promise<void> {
    await postNotificationAction(route('notifications.read', id));
}

export async function markAllNotificationsAsRead(): Promise<void> {
    await postNotificationAction(route('notifications.read.all'));
}

export function notificationText(value: unknown, fallback: string): string {
    return typeof value === 'string' && value.trim() !== '' ? value : fallback;
}
