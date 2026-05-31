import { CheckCircleIcon, X } from 'lucide-react';

interface FlashMessageProps {
    flash?: {
        success?: string;
        error?: string;
    };
}

export default function FlashMessage({ flash }: FlashMessageProps) {
    if (!flash?.success && !flash?.error) return null;

    return (
        <>
            {flash?.success && (
                <div className="animate-in slide-in-from-top fixed top-4 right-4 z-50 duration-500">
                    <div className="flex items-center gap-3 rounded-xl bg-green-500 px-6 py-4 text-white shadow-2xl">
                        <CheckCircleIcon className="h-6 w-6" />
                        <p className="font-semibold">{flash.success}</p>
                    </div>
                </div>
            )}
            {flash?.error && (
                <div className="animate-in slide-in-from-top fixed top-4 right-4 z-50 duration-500">
                    <div className="flex items-center gap-3 rounded-xl bg-red-500 px-6 py-4 text-white shadow-2xl">
                        <X className="h-6 w-6" />
                        <p className="font-semibold">{flash.error}</p>
                    </div>
                </div>
            )}
        </>
    );
}
