import { LucideIcon } from 'lucide-react';

export interface SharedData {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            avatar?: string;
            email_verified_at?: string | null;
            must_change_password?: boolean;
        } | null;
    };
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon;
    permission?: string;
    children?: NavItem[];
    target?: string;
    external?: boolean;
    defaultOpen?: boolean;
    badge?: {
        label: string | number;
        variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost';
    };
}

export interface BreadcrumbItem {
    title: string;
    href?: string;
}

export interface PageAction {
    label: string;
    icon: React.ReactNode;
    variant: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
    onClick: () => void;
}