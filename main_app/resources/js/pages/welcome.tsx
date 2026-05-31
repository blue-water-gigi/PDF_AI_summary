import FlashMessage from '@/components/FlashMessage';
import SummaryModal from '@/components/SummaryModal';
import SummaryOptionsModal from '@/components/SummaryOptionsModel';
import AppLogoIcon from '@/components/app-logo-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { cn } from '@/lib/utils';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRightIcon,
    CheckIcon,
    ChevronDownIcon,
    ClockIcon,
    FileTextIcon,
    LoaderCircle,
    RocketIcon,
    ShieldCheckIcon,
    SparklesIcon,
    UploadIcon,
    ZapIcon,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState, type ChangeEvent, type DragEvent } from 'react';

interface Plan {
    id: number;
    name: string;
    slug: string;
    description: string;
    price: number;
    pdf_limit: number;
    features: string[];
}

interface Props {
    plans?: Plan[];
    canRegister: boolean;
    currentPlanSlug?: string | null;
    auth?: { user?: { plan?: { slug: string } } | null };
    userStats?: { pdfCount: number; pdfLimit: number; canUpload: boolean } | null;
    flash?: { success?: string; error?: string };
}

type SummaryType = 'default' | 'points' | 'highlights' | 'detailed';

const heroBadges = [
    { icon: SparklesIcon, label: 'AI-Powered' },
    { icon: ClockIcon, label: 'Instant Results' },
    { icon: ShieldCheckIcon, label: 'Secure Processing' },
];

const RECOMMENDED_PLAN_SLUG = 'standard';

const planHierarchy: Record<string, number> = { basic: 1, standard: 2, premium: 3 };

function formatPrice(price: number): string {
    if (price <= 0) {
        return 'Free';
    }

    return `$${Number(price).toFixed(price % 1 === 0 ? 0 : 2)}`;
}

function getPlanCta(plan: Plan, currentPlanSlug: string | null | undefined, isAuthenticated: boolean): { label: string; disabled: boolean } {
    if (isAuthenticated && plan.slug === currentPlanSlug) {
        return { label: 'Current plan', disabled: true };
    }

    if (!isAuthenticated) {
        return plan.slug === 'basic'
            ? { label: 'Start for free', disabled: false }
            : { label: 'Get started', disabled: false };
    }

    const currentLevel = planHierarchy[currentPlanSlug ?? 'basic'] ?? 1;
    const planLevel = planHierarchy[plan.slug] ?? 1;

    if (planLevel > currentLevel) {
        return { label: 'Upgrade', disabled: false };
    }

    return { label: 'Switch plan', disabled: false };
}

const featureCards = [
    {
        icon: FileTextIcon,
        title: 'Multiple Summary Types',
        description: 'Choose from standard, bullet points, highlights, or detailed analysis based on your needs.',
    },
    {
        icon: ZapIcon,
        title: 'Lightning Fast',
        description: 'Get comprehensive summaries in seconds, not hours of manual reading.',
    },
    {
        icon: RocketIcon,
        title: 'Scale With Your Plan',
        description: 'Start free and upgrade as your document volume grows.',
    },
];

function normalizePlans(plans: Plan[] | Record<string, Plan> | { data?: Plan[] } | null | undefined): Plan[] {
    if (!plans) {
        return [];
    }

    let list: unknown[] = [];

    if (Array.isArray(plans)) {
        list = plans;
    } else if ('data' in plans && Array.isArray(plans.data)) {
        list = plans.data;
    } else if (typeof plans === 'object') {
        list = Object.values(plans);
    }

    return list
        .filter((plan): plan is Plan => !!plan && typeof plan === 'object' && 'id' in plan && 'slug' in plan)
        .map((plan) => {
            let features: string[] = [];

            if (Array.isArray(plan.features)) {
                features = plan.features;
            } else if (typeof plan.features === 'string') {
                try {
                    const parsed = JSON.parse(plan.features);
                    features = Array.isArray(parsed) ? parsed : [];
                } catch {
                    features = [];
                }
            }

            return {
                ...plan,
                price: Number(plan.price),
                pdf_limit: Number(plan.pdf_limit),
                features,
            };
        });
}

function useInView(threshold = 0.15) {
    const ref = useRef<HTMLElement>(null);
    const [isInView, setIsInView] = useState(false);

    useEffect(() => {
        const element = ref.current;
        if (!element) {
            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setIsInView(true);
                    observer.unobserve(element);
                }
            },
            { threshold, rootMargin: '0px 0px -40px 0px' },
        );

        observer.observe(element);

        // Fallback: ensure content becomes visible even if observer never fires
        const fallback = window.setTimeout(() => setIsInView(true), 800);

        return () => {
            observer.disconnect();
            window.clearTimeout(fallback);
        };
    }, [threshold]);

    return { ref, isInView };
}

function AnimatedSection({ children, className, delay = 0 }: { children: React.ReactNode; className?: string; delay?: number }) {
    const { ref, isInView } = useInView();

    return (
        <section
            ref={ref}
            className={cn('transition-all duration-700 ease-out', isInView ? 'translate-y-0 opacity-100' : 'translate-y-8 opacity-0', className)}
            style={{ transitionDelay: `${delay}ms` }}
        >
            {children}
        </section>
    );
}

function getPlanIcon(slug: string) {
    if (slug === 'standard') {
        return ZapIcon;
    }
    if (slug === 'premium') {
        return RocketIcon;
    }
    return SparklesIcon;
}

function PricingCard({
    plan,
    currentPlanSlug,
    isAuthenticated,
    isHovered,
    onHover,
    onSelect,
}: {
    plan: Plan;
    currentPlanSlug: string | null | undefined;
    isAuthenticated: boolean;
    isHovered: boolean;
    onHover: (id: number | null) => void;
    onSelect: (plan: Plan) => void;
}) {
    const isCurrentPlan = isAuthenticated && plan.slug === currentPlanSlug;
    const isRecommended = plan.slug === RECOMMENDED_PLAN_SLUG;
    const Icon = getPlanIcon(plan.slug);
    const cta = getPlanCta(plan, currentPlanSlug, isAuthenticated);

    return (
        <Card
            className={cn(
                'relative flex flex-col overflow-hidden transition-all duration-300',
                isRecommended && 'md:-mt-2 md:mb-2 md:scale-[1.02] md:shadow-md',
                isCurrentPlan && 'border-foreground ring-foreground/20 ring-1',
                isHovered && !isCurrentPlan && '-translate-y-1 shadow-lg',
            )}
            onMouseEnter={() => onHover(plan.id)}
            onMouseLeave={() => onHover(null)}
        >
            {isRecommended && (
                <div className="bg-foreground text-background absolute top-4 right-4 rounded-full px-3 py-1 text-xs font-medium">
                    Most popular
                </div>
            )}
            {isCurrentPlan && <div className="bg-foreground absolute top-0 right-0 left-0 h-0.5" />}
            <PlaceholderPattern
                className={cn(
                    'absolute inset-0 size-full stroke-neutral-900/5 transition-opacity duration-300 dark:stroke-neutral-100/5',
                    isHovered || isCurrentPlan || isRecommended ? 'opacity-100' : 'opacity-0',
                )}
            />
            <CardHeader className="relative pb-2">
                <div className="mb-4 flex items-center gap-3">
                    <div
                        className={cn(
                            'bg-secondary flex h-11 w-11 items-center justify-center rounded-lg transition-all duration-300',
                            (isHovered || isRecommended) && 'bg-primary text-primary-foreground scale-110',
                        )}
                    >
                        <Icon className="h-5 w-5" />
                    </div>
                    <div>
                        <CardTitle className="text-xl">{plan.name}</CardTitle>
                        {isCurrentPlan && (
                            <Badge variant="secondary" className="mt-1">
                                Current plan
                            </Badge>
                        )}
                    </div>
                </div>
                <div className="flex items-baseline gap-1">
                    <span className="text-4xl font-semibold tracking-tight">{formatPrice(plan.price)}</span>
                    {plan.price > 0 && <span className="text-muted-foreground text-sm">/month</span>}
                </div>
                <CardDescription className="mt-3 text-base">{plan.description}</CardDescription>
            </CardHeader>
            <CardContent className="relative flex-1 space-y-3 pt-2">
                <div className="bg-secondary/60 flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium">
                    <FileTextIcon className="h-4 w-4 shrink-0" />
                    <span>
                        {plan.pdf_limit < 0 ? 'Unlimited' : plan.pdf_limit} PDF{plan.pdf_limit === 1 ? '' : 's'} per month
                    </span>
                </div>
                <ul className="space-y-2.5">
                    {plan.features.map((feature, idx) => (
                        <li
                            key={idx}
                            className={cn('flex items-start gap-2.5 text-sm transition-all duration-300', isHovered && 'translate-x-0.5')}
                            style={{ transitionDelay: `${idx * 30}ms` }}
                        >
                            <CheckIcon className="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{feature}</span>
                        </li>
                    ))}
                </ul>
            </CardContent>
            <CardFooter className="relative mt-auto pt-2">
                <Button className="w-full" variant={isCurrentPlan ? 'secondary' : isRecommended ? 'default' : 'outline'} disabled={cta.disabled} onClick={() => onSelect(plan)}>
                    {cta.label}
                    {!cta.disabled && <ArrowRightIcon />}
                </Button>
            </CardFooter>
        </Card>
    );
}

export default function Welcome({ plans = [], canRegister, currentPlanSlug, auth, userStats, flash }: Props) {
    const [pdf, setPdf] = useState<File | null>(null);
    const [isDragging, setIsDragging] = useState(false);
    const [mounted, setMounted] = useState(false);
    const [scrolled, setScrolled] = useState(false);
    const [hoveredFeature, setHoveredFeature] = useState<number | null>(null);
    const [hoveredPlan, setHoveredPlan] = useState<number | null>(null);
    const [loading, setLoading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [summary, setSummary] = useState('');
    const [showSummary, setShowSummary] = useState(false);
    const [showSummaryOptions, setShowSummaryOptions] = useState(false);
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const limitReached = !!(userStats && !userStats.canUpload);
    const userPlanSlug = currentPlanSlug ?? auth?.user?.plan?.slug ?? 'basic';
    const isAuthenticated = !!auth?.user;
    const safePlans = normalizePlans(plans);

    useEffect(() => {
        setMounted(true);
    }, []);

    useEffect(() => {
        const handleScroll = () => setScrolled(window.scrollY > 20);
        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    const scrollToPricing = useCallback(() => {
        document.getElementById('choose-plan')?.scrollIntoView({ behavior: 'smooth' });
    }, []);

    const handleFileSelect = (file: File) => {
        if (limitReached) {
            alert(`PDF limit reached (${userStats?.pdfCount} / ${userStats?.pdfLimit}).`);
            return;
        }
        setSelectedFile(file);
        setPdf(file);
        setShowSummaryOptions(true);
    };

    const handleSummaryTypeSelect = async (type: SummaryType) => {
        setShowSummaryOptions(false);
        if (!selectedFile || !auth?.user) {
            return;
        }

        setLoading(true);
        setProgress(0);
        setSummary('');

        const formData = new FormData();
        formData.append('pdf', selectedFile);
        formData.append('summary_type', type);

        const progressInterval = setInterval(() => {
            setProgress((prev) => (prev >= 90 ? 90 : prev + 10));
        }, 200);

        const csrfToken = document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '';
        const response = await fetch('/pdf/summarize', {
            method: 'POST',
            body: formData,
            headers: { Accept: 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(csrfToken) },
        });

        clearInterval(progressInterval);

        if (!response.ok) {
            setLoading(false);
            setProgress(0);
            alert('Failed to generate summary');
            return;
        }
        const data = await response.json();
        setProgress(100);

        let cleanSummary = data.summary || '';
        cleanSummary = cleanSummary.replace(/\*\*/g, '').replace(/\*/g, '').replace(/^#+\s/gm, '').replace(/^-\s/gm, ' ');
        setSummary(cleanSummary);

        setTimeout(() => {
            setLoading(false);
            setShowSummary(true);
        }, 500);
    };

    const handleDragOver = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        if (auth?.user) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(false);
    };

    const handleDrop = (e: DragEvent<HTMLDivElement>) => {
        e.preventDefault();
        setIsDragging(false);
        const file = e.dataTransfer.files[0];
        if (file?.type === 'application/pdf') {
            handleFileSelect(file);
        }
    };

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file?.type === 'application/pdf') {
            handleFileSelect(file);
        }
    };

    const handleNewUpload = () => {
        setPdf(null);
        setSummary('');
        setShowSummary(false);
        setSelectedFile(null);
    };

    const handlePlanClick = (plan: Plan) => {
        if (!isAuthenticated) {
            router.visit(plan.slug === 'basic' ? '/register' : '/register');
            return;
        }

        if (plan.slug === currentPlanSlug) {
            return;
        }

        if (plan.slug === 'basic') {
            router.visit('/dashboard');
            return;
        }

        router.visit(`/checkout/${plan.slug}`);
    };

    return (
        <>
            <Head title="PDF Summarizer - AI-Powered Document Summaries" />
            <div className="bg-background text-foreground relative min-h-screen">
                <FlashMessage flash={flash} />

                {/* Subtle grid background */}
                <div className="pointer-events-none fixed inset-0 overflow-hidden">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/5 dark:stroke-neutral-100/5" />
                    <div className="from-background via-background/80 to-background absolute inset-0 bg-gradient-to-b" />
                </div>

                <SummaryOptionsModal
                    show={showSummaryOptions && !!selectedFile}
                    fileName={selectedFile?.name ?? ''}
                    userPlanSlug={userPlanSlug}
                    onClose={() => {
                        setShowSummaryOptions(false);
                        setSelectedFile(null);
                        setPdf(null);
                    }}
                    onSelect={handleSummaryTypeSelect}
                />
                <SummaryModal
                    show={showSummary}
                    summary={summary}
                    fileName={pdf?.name}
                    onClose={() => setShowSummary(false)}
                    onNewUpload={handleNewUpload}
                />

                {/* Navigation */}
                <nav
                    className={cn(
                        'fixed top-0 right-0 left-0 z-40 border-b transition-all duration-300',
                        scrolled ? 'border-border bg-background/95 shadow-sm backdrop-blur-md' : 'border-transparent bg-transparent',
                    )}
                >
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex h-16 items-center justify-between">
                            <Link href={route('home')} className="group flex items-center gap-3">
                                <div
                                    className={cn(
                                        'bg-primary text-primary-foreground flex h-9 w-9 items-center justify-center rounded-md transition-transform duration-300 group-hover:scale-105',
                                        mounted && 'animate-in fade-in zoom-in duration-500',
                                    )}
                                >
                                    <AppLogoIcon className="size-5 fill-current" />
                                </div>
                                <span className="text-lg font-semibold tracking-tight">PDF Summarizer</span>
                            </Link>
                            <div className="flex items-center gap-3">
                                <Button variant="ghost" className="hidden sm:inline-flex" onClick={scrollToPricing}>
                                    Pricing
                                </Button>
                                {auth?.user ? (
                                    <Button asChild>
                                        <Link href="/dashboard">
                                            Dashboard
                                            <ArrowRightIcon />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button variant="ghost" asChild>
                                            <Link href="/login">Log in</Link>
                                        </Button>
                                        {canRegister && (
                                            <Button asChild>
                                                <Link href="/register">Sign up</Link>
                                            </Button>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </nav>

                <div className="relative z-10 pt-28 pb-16">
                    <div className="container mx-auto px-4 sm:px-6 lg:px-8">
                        {!showSummary && (
                            <>
                                {/* Hero */}
                                <section
                                    className={cn(
                                        'mb-20 text-center transition-all duration-1000 ease-out',
                                        mounted ? 'translate-y-0 opacity-100' : 'translate-y-6 opacity-0',
                                    )}
                                >
                                    <div className="mb-8 flex flex-wrap justify-center gap-2">
                                        {heroBadges.map(({ icon: Icon, label }, index) => (
                                            <Badge
                                                key={label}
                                                variant="outline"
                                                className={cn(
                                                    'hover:bg-accent gap-1.5 px-3 py-1 transition-all duration-300 hover:scale-105',
                                                    mounted && 'animate-in fade-in slide-in-from-bottom-2',
                                                )}
                                                style={{ animationDelay: `${200 + index * 100}ms`, animationFillMode: 'both' }}
                                            >
                                                <Icon className="h-3.5 w-3.5" />
                                                {label}
                                            </Badge>
                                        ))}
                                    </div>

                                    <h1
                                        className={cn(
                                            'mb-6 text-4xl font-semibold tracking-tight sm:text-6xl',
                                            mounted && 'animate-in fade-in slide-in-from-bottom-4 duration-700',
                                        )}
                                        style={{ animationDelay: '300ms', animationFillMode: 'both' }}
                                    >
                                        Turn PDFs into{' '}
                                        <span className="relative inline-block">
                                            Insights
                                            <span
                                                className={cn(
                                                    'bg-primary absolute -bottom-1 left-0 h-0.5 w-full origin-left transition-transform duration-700 ease-out',
                                                    mounted ? 'scale-x-100' : 'scale-x-0',
                                                )}
                                                style={{ transitionDelay: '800ms' }}
                                            />
                                        </span>
                                    </h1>

                                    <p
                                        className={cn(
                                            'text-muted-foreground mx-auto mb-10 max-w-2xl text-lg sm:text-xl',
                                            mounted && 'animate-in fade-in slide-in-from-bottom-4 duration-700',
                                        )}
                                        style={{ animationDelay: '450ms', animationFillMode: 'both' }}
                                    >
                                        Use AI-powered summaries to understand your documents faster. Upload, select your summary type, and get
                                        instant insights.
                                    </p>

                                    <div
                                        className={cn(
                                            'flex flex-wrap items-center justify-center gap-3',
                                            mounted && 'animate-in fade-in slide-in-from-bottom-4 duration-700',
                                        )}
                                        style={{ animationDelay: '550ms', animationFillMode: 'both' }}
                                    >
                                        {!auth?.user && canRegister && (
                                            <Button size="lg" asChild className="group">
                                                <Link href="/register">
                                                    Get Started Free
                                                    <ArrowRightIcon className="transition-transform group-hover:translate-x-0.5" />
                                                </Link>
                                            </Button>
                                        )}
                                        <Button size="lg" variant="outline" onClick={scrollToPricing} className="group">
                                            View Pricing
                                            <ChevronDownIcon className="transition-transform group-hover:translate-y-0.5" />
                                        </Button>
                                    </div>
                                </section>

                                {/* Upload */}
                                {auth?.user && (
                                    <AnimatedSection className="mx-auto mb-20 max-w-2xl" delay={100}>
                                        <Card
                                            className={cn(
                                                'relative overflow-hidden transition-all duration-300',
                                                isDragging && 'border-foreground scale-[1.01] shadow-lg',
                                                limitReached && 'opacity-50',
                                            )}
                                        >
                                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/5 dark:stroke-neutral-100/5" />
                                            <div
                                                onDragOver={handleDragOver}
                                                onDragLeave={handleDragLeave}
                                                onDrop={handleDrop}
                                                className={cn(
                                                    'relative cursor-pointer p-12 text-center transition-colors',
                                                    !limitReached && 'hover:bg-accent/30',
                                                    limitReached && 'cursor-not-allowed',
                                                )}
                                                onClick={() => !limitReached && fileInputRef.current?.click()}
                                            >
                                                <input
                                                    ref={fileInputRef}
                                                    type="file"
                                                    accept=".pdf"
                                                    onChange={handleFileChange}
                                                    disabled={limitReached}
                                                    className="hidden"
                                                />
                                                <div
                                                    className={cn(
                                                        'bg-secondary mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-lg transition-all duration-300',
                                                        isDragging && 'scale-110 animate-pulse',
                                                    )}
                                                >
                                                    <UploadIcon className="text-foreground h-7 w-7" />
                                                </div>
                                                <h3 className="mb-2 text-xl font-medium">Drop your PDF here</h3>
                                                <p className="text-muted-foreground">
                                                    or click to browse ({userStats?.pdfCount ?? 0} / {userStats?.pdfLimit ?? 0})
                                                </p>
                                                {limitReached && (
                                                    <p className="text-destructive mt-3 text-sm">PDF limit reached. Upgrade your plan.</p>
                                                )}
                                            </div>
                                            {loading && (
                                                <div className="bg-background/95 absolute inset-0 flex flex-col items-center justify-center backdrop-blur-sm">
                                                    <LoaderCircle className="text-foreground mb-4 h-10 w-10 animate-spin" />
                                                    <p className="text-muted-foreground mb-3 text-sm">Generating summary...</p>
                                                    <div className="bg-secondary h-1.5 w-48 overflow-hidden rounded-full">
                                                        <div
                                                            className="bg-primary h-full rounded-full transition-all duration-300 ease-out"
                                                            style={{ width: `${progress}%` }}
                                                        />
                                                    </div>
                                                    <p className="text-muted-foreground mt-2 text-xs">{progress}%</p>
                                                </div>
                                            )}
                                        </Card>
                                    </AnimatedSection>
                                )}

                                {/* Guest CTA */}
                                {!auth?.user && (
                                    <AnimatedSection className="mb-20" delay={100}>
                                        <Card className="relative mx-auto max-w-xl overflow-hidden">
                                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/5 dark:stroke-neutral-100/5" />
                                            <CardHeader className="relative text-center">
                                                <div className="bg-secondary mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-lg">
                                                    <SparklesIcon className="h-6 w-6" />
                                                </div>
                                                <CardTitle className="text-xl">Ready to summarize?</CardTitle>
                                                <CardDescription className="text-base">
                                                    Sign in or create an account to start summarizing PDFs
                                                </CardDescription>
                                            </CardHeader>
                                            <CardFooter className="relative flex flex-wrap justify-center gap-3 pb-8">
                                                <Button variant="outline" size="lg" asChild>
                                                    <Link href="/login">Log in</Link>
                                                </Button>
                                                {canRegister && (
                                                    <Button size="lg" asChild className="group">
                                                        <Link href="/register">
                                                            Get Started Free
                                                            <ArrowRightIcon className="transition-transform group-hover:translate-x-0.5" />
                                                        </Link>
                                                    </Button>
                                                )}
                                            </CardFooter>
                                        </Card>
                                    </AnimatedSection>
                                )}

                                {/* Features */}
                                <AnimatedSection className="mb-20" delay={0}>
                                    <div className="mb-10 text-center">
                                        <h2 className="mb-3 text-3xl font-semibold tracking-tight">Why choose us</h2>
                                        <p className="text-muted-foreground mx-auto max-w-lg">
                                            Everything you need to extract insights from documents, fast.
                                        </p>
                                    </div>
                                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        {featureCards.map(({ icon: Icon, title, description }, index) => (
                                            <Card
                                                key={title}
                                                className={cn(
                                                    'group relative overflow-hidden transition-all duration-300',
                                                    hoveredFeature === index && '-translate-y-1 shadow-md',
                                                )}
                                                onMouseEnter={() => setHoveredFeature(index)}
                                                onMouseLeave={() => setHoveredFeature(null)}
                                            >
                                                <PlaceholderPattern
                                                    className={cn(
                                                        'absolute inset-0 size-full stroke-neutral-900/5 transition-opacity duration-300 dark:stroke-neutral-100/5',
                                                        hoveredFeature === index ? 'opacity-100' : 'opacity-0',
                                                    )}
                                                />
                                                <CardHeader className="relative">
                                                    <div
                                                        className={cn(
                                                            'bg-secondary mb-2 flex h-11 w-11 items-center justify-center rounded-lg transition-all duration-300',
                                                            hoveredFeature === index && 'bg-primary text-primary-foreground scale-110',
                                                        )}
                                                    >
                                                        <Icon className="h-5 w-5" />
                                                    </div>
                                                    <CardTitle className="text-lg">{title}</CardTitle>
                                                </CardHeader>
                                                <CardContent className="relative">
                                                    <CardDescription className="text-base">{description}</CardDescription>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                </AnimatedSection>

                                {/* Choose your plan */}
                                <section
                                    id="choose-plan"
                                    className={cn(
                                        'border-border scroll-mt-24 border-t py-16 transition-all duration-700',
                                        mounted ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0',
                                    )}
                                >
                                    <div className="mb-12 text-center">
                                        <Badge variant="outline" className="mb-4">
                                            Pricing
                                        </Badge>
                                        <h2 className="mb-3 text-3xl font-semibold tracking-tight sm:text-4xl">Choose your plan</h2>
                                        <p className="text-muted-foreground mx-auto max-w-2xl text-lg">
                                            Start free and scale as you go. Every plan includes secure AI processing and monthly PDF limits that
                                            reset automatically.
                                        </p>
                                    </div>

                                    {safePlans.length > 0 ? (
                                        <div
                                            className={cn(
                                                'mx-auto grid max-w-5xl grid-cols-1 gap-4',
                                                safePlans.length === 2 && 'md:grid-cols-2',
                                                safePlans.length >= 3 && 'md:grid-cols-3',
                                            )}
                                        >
                                            {safePlans.map((plan) => (
                                                <PricingCard
                                                    key={plan.id}
                                                    plan={plan}
                                                    currentPlanSlug={currentPlanSlug}
                                                    isAuthenticated={isAuthenticated}
                                                    isHovered={hoveredPlan === plan.id}
                                                    onHover={setHoveredPlan}
                                                    onSelect={handlePlanClick}
                                                />
                                            ))}
                                        </div>
                                    ) : (
                                        <Card className="mx-auto max-w-lg text-center">
                                            <CardHeader>
                                                <CardTitle>No plans available</CardTitle>
                                                <CardDescription>
                                                    Pricing plans are being configured. Please check back soon or contact support.
                                                </CardDescription>
                                            </CardHeader>
                                        </Card>
                                    )}

                                    <p className="text-muted-foreground mt-10 text-center text-sm">
                                        Need a custom plan for your team?{' '}
                                        <a href="#" className="text-foreground underline underline-offset-4">
                                            Contact us
                                        </a>
                                    </p>
                                </section>
                            </>
                        )}
                    </div>
                </div>

                <footer className="border-border relative z-10 border-t py-10">
                    <div className="container mx-auto px-4 text-center sm:px-6 lg:px-8">
                        <div className="mb-3 flex items-center justify-center gap-2">
                            <AppLogoIcon className="text-foreground h-4 w-4 fill-current" />
                            <span className="font-medium">PDF Summarizer</span>
                        </div>
                        <p className="text-muted-foreground text-sm">AI-powered document summaries for faster insights.</p>
                    </div>
                </footer>
            </div>
        </>
    );
}
