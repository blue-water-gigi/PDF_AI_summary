import { router } from '@inertiajs/react';
import { AlignLeft, FileText, Lightbulb, List, X } from 'lucide-react';

type SummaryType = 'default' | 'points' | 'highlights' | 'detailed';

interface SummaryOptionsModalProps {
    show: boolean;
    fileName?: string;
    userPlanSlug: string;
    onClose: () => void;
    onSelect: (type: SummaryType) => void;
}

const summaryOptions = [
    { type: 'default' as const, icon: AlignLeft, title: 'Standard Summary', description: 'Concise overview', color: 'violet', requiredPlan: 'basic' },
    {
        type: 'points' as const,
        icon: List,
        title: 'Bullet points',
        description: 'Key points in bullet format',
        color: 'indigo',
        requiredPlan: 'basic',
    },
    {
        type: 'highlights' as const,
        icon: Lightbulb,
        title: 'Key highlights',
        description: 'Most important takeaways',
        color: 'purple',
        requiredPlan: 'standard',
    },
    {
        type: 'detailed' as const,
        icon: FileText,
        title: 'Detailed analysis',
        description: 'Comprehensive breakdown',
        color: 'blue',
        requiredPlan: 'premium',
    },
];

const planHierarchy: Record<string, number> = { basic: 1, standard: 2, premium: 3 };

export default function SummaryOptionsModal({ show, fileName, userPlanSlug, onClose, onSelect }: SummaryOptionsModalProps) {
    if (!show) return null;

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
            router.visit('/dashboard');
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div className="relative w-full max-w-3xl overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                {/* Header */}
                <div className="bg-gradient-to-r from-violet-600 to-purple-600 px-8 py-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-bold text-white">Choose Summary Type</h2>
                            <p className="text-sm text-white/80">{fileName}</p>
                        </div>
                        <button onClick={onClose} aria-label="Close modal" className="rounded-lg p-2 transition hover:bg-white/20">
                            <X className="h-6 w-6 text-white" />
                        </button>
                    </div>
                </div>

                {/* Options Grid */}
                <div className="grid gap-4 p-8 md:grid-cols-2">
                    {summaryOptions.map((option) => {
                        const isLocked = !canAccess(option.requiredPlan);
                        return (
                            <button
                                key={option.type}
                                onClick={() => handleClick(option)}
                                className={`group relative rounded-2xl border-2 bg-white p-6 text-left transition-all dark:bg-slate-800 ${
                                    isLocked
                                        ? 'cursor-not-allowed border-slate-300 opacity-75'
                                        : 'border-slate-200 hover:scale-105 hover:border-violet-500 hover:shadow-xl'
                                }`}
                            >
                                {isLocked && (
                                    <div className="absolute top-3 right-3 rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">
                                        {option.requiredPlan.toUpperCase()}
                                    </div>
                                )}
                                <div
                                    className={`mb-4 inline-flex rounded-xl p-3 ${
                                        isLocked ? 'bg-slate-100' : 'bg-violet-100 transition-transform group-hover:scale-110'
                                    }`}
                                >
                                    <option.icon className={`h-6 w-6 ${isLocked ? 'text-slate-400' : 'text-violet-600'}`} />
                                </div>
                                <h3 className={`mb-2 text-lg font-bold ${isLocked ? 'text-slate-500' : 'text-slate-900 dark:text-white'}`}>
                                    {option.title}
                                </h3>
                                <p className={`text-sm ${isLocked ? 'text-slate-400' : 'text-slate-600 dark:text-slate-300'}`}>
                                    {option.description}
                                </p>
                                {isLocked && <div className="mt-3 text-sm font-semibold text-violet-600">Click to upgrade</div>}
                            </button>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}
