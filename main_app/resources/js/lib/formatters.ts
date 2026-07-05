export function formatShortDate(date: string | null | undefined): string {
    if (!date) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(new Date(date));
}

export function formatNumericDate(date: string | null | undefined): string {
    if (!date) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(date));
}

export function formatNumericDateTime(date: string | null | undefined): string {
    if (!date) {
        return 'Not set';
    }

    return new Intl.DateTimeFormat('ru-RU', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(date));
}

export function formatFileSize(size: number | null | undefined): string {
    if (!size) {
        return 'Not set';
    }

    if (size < 1024 * 1024) {
        return `${Math.max(1, Math.round(size / 1024))} KB`;
    }

    return `${(size / 1024 / 1024).toFixed(1)} MB`;
}

export function formatLimit(limit: number | undefined): string {
    if (typeof limit !== 'number') {
        return '0';
    }

    return limit < 0 ? 'Unlimited' : String(limit);
}

export function formatSummaryType(type: string): string {
    return type
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

export function formatPaginationLabel(label: string): string {
    return label.replace(/&laquo;|&raquo;/g, '').trim();
}
