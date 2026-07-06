import { useCallback, useRef, type MouseEvent, type TouchEvent } from 'react';

type UseLongPressOptions = {
    delay?: number;
    onLongPress: () => void;
};

export function useLongPress({ delay = 500, onLongPress }: UseLongPressOptions) {
    const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const longPressTriggeredRef = useRef(false);

    const clear = useCallback(() => {
        if (timerRef.current !== null) {
            clearTimeout(timerRef.current);
            timerRef.current = null;
        }
    }, []);

    const start = useCallback(() => {
        longPressTriggeredRef.current = false;
        clear();
        timerRef.current = setTimeout(() => {
            longPressTriggeredRef.current = true;
            onLongPress();
        }, delay);
    }, [clear, delay, onLongPress]);

    return {
        longPressTriggeredRef,
        handlers: {
            onMouseDown: start,
            onMouseUp: clear,
            onMouseLeave: clear,
            onTouchStart: start,
            onTouchEnd: clear,
            onTouchMove: clear,
            onContextMenu: (event: MouseEvent | TouchEvent) => {
                event.preventDefault();
            },
        },
    };
}
