import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind class names while resolving conflicts (the shadcn/ui `cn`
 * helper). Later classes win over earlier conflicting ones.
 */
export function cn(...inputs: ClassValue[]): string {
    return twMerge(clsx(inputs));
}
