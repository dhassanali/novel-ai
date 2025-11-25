import { useEffect, useRef } from 'react';

export function useAutoSave(
    content: string,
    initialContent: string,
    onSave: () => void,
    delay: number = 2000,
) {
    const timeoutRef = useRef<NodeJS.Timeout | null>(null);

    useEffect(() => {
        if (content === initialContent) return;

        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        timeoutRef.current = setTimeout(() => {
            onSave();
        }, delay);

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [content, initialContent, onSave, delay]);
}
