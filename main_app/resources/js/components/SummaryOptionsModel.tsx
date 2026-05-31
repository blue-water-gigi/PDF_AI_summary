import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { AlignLeft, FileText, Lightbulb, List, LockIcon, X } from 'lucide-react';

type SummaryType = 'default' | 'points' | 'highlights' | 'detailed';

interface SummaryOptionsModalProps {
    show: boolean;
    fileName?: string;
    userPlanSlug: string;
    onClose: () => void;
    onSelect: (type: SummaryType) => void;
}

const summaryOptions = [
    {
        type: 'default' as const,
        icon: AlignLeft,
        title: 'Standard Summary',
        description: 'Concise overview of the document',
        requiredPlan: 'basic',
    },
    {
        type: 'points' as const,
        icon: List,
        title: 'Bullet Points',
        description: 'Key points in bullet format',
        requiredPlan: 'basic',
    },
    {
        type: 'highlights' as const,
        icon: Lightbulb,
        title: 'Key Highlights',
        description: 'Most important takeaways',
        requiredPlan: 'standard',
    },
    {
        type: 'detailed' as const,
        icon: FileText,
        title: 'Detailed Analysis',
        description: 'Comprehensive breakdown',
        requiredPlan: 'premium',
    },
];

const planHierarchy: Record<string, number> = { basic: 1, standard: 2, premium: 3 };

export default function SummaryOptionsModal({ show, fileName, userPlanSlug, onClose, onSelect }: SummaryOptionsModalProps) {
    if (!show) {
        return null;
    }

    const canAccess = (requiredPlan: string) => {
        const userLevel = planHierarchy[userPlanSlug] || 1;
        const requiredLevel = planHierarchy[requiredPlan] || 1;
        return userLevel >= requiredLevel;
    };

    const handleClick = (option: (typeof summaryOptions)[0]) => {
        if (canAccess(option.requiredPlan)) {
            onSelect(option.type);
        } else {
            alert(`This feature requires a ${option.requiredPlan} plan. Please upgrade.`);
            router.visit('/#choose-plan');
        }
    };

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label="Choose summary type"
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
        >
            <div className="border-border bg-background relative w-full max-w-3xl overflow-hidden rounded-xl border shadow-xl">
                <PlaceholderPattern className="pointer-events-none absolute inset-0 size-full stroke-neutral-900/5 dark:stroke-neutral-100/5" />

                <div className="border-border relative border-b px-6 py-5">
                    <div className="flex items-start justify-between gap-4">
                        <div>
                            <h2 className="text-xl font-semibold tracking-tight">Choose summary type</h2>
                            <p className="text-muted-foreground mt-1 truncate text-sm">{fileName}</p>
                        </div>
                        <Button variant="ghost" size="icon" onClick={onClose} aria-label="Close modal">
                            <X className="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div className="relative grid gap-3 p-6 md:grid-cols-2">
                    {summaryOptions.map((option) => {
                        const isLocked = !canAccess(option.requiredPlan);
                        const Icon = option.icon;

                        return (
                            <button
                                key={option.type}
                                type="button"
                                onClick={() => handleClick(option)}
                                className={cn(
                                    'group text-left transition-all duration-200',
                                    isLocked ? 'cursor-not-allowed' : 'hover:-translate-y-0.5',
                                )}
                            >
                                <Card
                                    className={cn(
                                        'h-full transition-all duration-200',
                                        isLocked ? 'opacity-60' : 'hover:border-foreground/40 hover:shadow-md',
                                    )}
                                >
                                    <CardHeader className="relative pb-3">
                                        {isLocked && (
                                            <Badge variant="outline" className="absolute top-4 right-4 gap-1 capitalize">
                                                <LockIcon className="h-3 w-3" />
                                                {option.requiredPlan}
                                            </Badge>
                                        )}
                                        <div
                                            className={cn(
                                                'bg-secondary mb-3 flex h-10 w-10 items-center justify-center rounded-lg transition-all duration-200',
                                                !isLocked && 'group-hover:bg-primary group-hover:text-primary-foreground',
                                            )}
                                        >
                                            <Icon className="h-5 w-5" />
                                        </div>
                                        <CardTitle className="text-base">{option.title}</CardTitle>
                                        <CardDescription>{option.description}</CardDescription>
                                        {isLocked && (
                                            <p className="text-muted-foreground pt-1 text-xs font-medium">Upgrade to unlock</p>
                                        )}
                                    </CardHeader>
                                </Card>
                            </button>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
