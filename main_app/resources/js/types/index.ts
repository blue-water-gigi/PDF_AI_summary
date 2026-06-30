import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface Plan {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    price: number;
    pdf_limit: number;
    features?: string[];
}

export interface UserStats {
    pdfCount: number;
    pdfLimit: number;
    canUpload: boolean;
}

export interface SubscriptionDetails {
    gateway: string;
    status: string;
    currentPeriodEnd?: string | null;
    cancelledAt?: string | null;
    trialEndsAt?: string | null;
    isActive: boolean;
}

export interface SubscriptionData {
    plans: Plan[];
    currentPlanSlug?: string | null;
    userStats?: UserStats | null;
    hasActiveSubscription: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    subscriptionData?: SubscriptionData | null;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
