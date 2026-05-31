import { cn } from '@/lib/utils';
import { CheckCircleIcon, X, XCircleIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

interface FlashMessageProps {
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function FlashMessage({ flash }: FlashMessageProps) {
    const [visible, setVisible] = useState(!!flash?.success || !!flash?.error);

    useEffect(() => {
        if (flash?.success || flash?.error) {
            setVisible(true);
            const timer = setTimeout(() => setVisible(false), 5000);
            return () => clearTimeout(timer);
        }
    }, [flash?.success, flash?.error]);

    if (!visible || (!flash?.success && !flash?.error)) {
        return null;
    }

    const isSuccess = !!flash?.success;
    const message = flash?.success ?? flash?.error ?? '';

    return (
        <div className="animate-in slide-in-from-top fixed top-4 right-4 z-50 duration-300">
            <div
                className={cn(
                    'border-border bg-background flex max-w-sm items-start gap-3 rounded-lg border px-4 py-3 shadow-lg',
                    isSuccess ? 'border-foreground/20' : 'border-destructive/30',
                )}
            >
                {isSuccess ? (
                    <CheckCircleIcon className="text-foreground mt-0.5 h-5 w-5 shrink-0" />
                ) : (
                    <XCircleIcon className="text-destructive mt-0.5 h-5 w-5 shrink-0" />
                )}
                <p className="text-foreground flex-1 text-sm font-medium">{message}</p>
                <button
                    type="button"
                    onClick={() => setVisible(false)}
                    className="text-muted-foreground hover:text-foreground rounded-md p-0.5 transition-colors"
                    aria-label="Dismiss message"
                >
                    <X className="h-4 w-4" />
                </button>
            </div>
        </div>
    );
}
