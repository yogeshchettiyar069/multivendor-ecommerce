import { Toaster as Sonner } from 'sonner';

type ToasterProps = React.ComponentProps<typeof Sonner>;

/**
 * App toaster. Theme follows the document's dark-mode class so toasts match the
 * active palette without a runtime theme provider.
 */
export function Toaster(props: ToasterProps) {
    const isDark =
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark');

    return (
        <Sonner
            theme={isDark ? 'dark' : 'light'}
            className="toaster group"
            toastOptions={{
                classNames: {
                    toast: 'group toast group-[.toaster]:bg-background group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg',
                    description: 'group-[.toast]:text-muted-foreground',
                },
            }}
            {...props}
        />
    );
}
