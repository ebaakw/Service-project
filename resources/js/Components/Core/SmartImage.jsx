import React, { useCallback, useEffect, useRef, useState } from 'react';
import classNames from 'classnames';

/**
 * Display-only image for API/storage URLs (not FilePond previews).
 * Uses IntersectionObserver so nested scroll containers defer network until near viewport.
 * Prefers thumbSrc when provided to reduce bytes in grids/lists.
 */
export default function SmartImage({
    src,
    thumbSrc,
    alt = '',
    width = 70,
    height = 70,
    className = '',
    imgClassName = '',
    scrollRootRef = null,
    rootMargin = '200px',
    fallbackClassName = 'fas fa-image text-muted',
}) {
    const containerRef = useRef(null);
    const [shouldLoad, setShouldLoad] = useState(false);
    const [loadError, setLoadError] = useState(false);
    const [useFullFallback, setUseFullFallback] = useState(false);

    const primary = thumbSrc || src;
    const effectiveSrc =
        useFullFallback && src && thumbSrc && src !== thumbSrc ? src : primary;

    useEffect(() => {
        setShouldLoad(false);
        setLoadError(false);
        setUseFullFallback(false);
    }, [primary]);

    useEffect(() => {
        if (!primary) return;
        if (shouldLoad) return;

        const el = containerRef.current;
        if (!el) return;

        const root = scrollRootRef?.current ?? null;
        const io = new IntersectionObserver(
            (entries) => {
                if (entries.some((e) => e.isIntersecting)) {
                    setShouldLoad(true);
                }
            },
            { root, rootMargin, threshold: 0.01 }
        );
        io.observe(el);
        return () => io.disconnect();
    }, [primary, scrollRootRef, rootMargin, shouldLoad]);

    const onImgError = useCallback(() => {
        if (thumbSrc && src && !useFullFallback && primary === thumbSrc) {
            setUseFullFallback(true);
            setLoadError(false);
            return;
        }
        setLoadError(true);
    }, [thumbSrc, src, useFullFallback, primary]);

    const w = typeof width === 'number' ? `${width}px` : width;
    const h = typeof height === 'number' ? `${height}px` : height;

    if (!primary) {
        return (
            <div
                className={className}
                style={{
                    width: w,
                    height: h,
                    flexShrink: 0,
                }}
                aria-hidden={alt ? undefined : true}
            >
                <div
                    className="d-flex align-items-center justify-content-center w-100 h-100 rounded bg-light"
                    style={{ minHeight: h }}
                >
                    <i className={fallbackClassName} style={{ fontSize: '1.25rem', opacity: 0.35 }} aria-hidden />
                </div>
            </div>
        );
    }

    const showPlaceholder = !shouldLoad || (!effectiveSrc && !loadError);
    const showImg = shouldLoad && effectiveSrc && !loadError;

    return (
        <div
            ref={containerRef}
            className={classNames('rounded', className)}
            style={{
                width: w,
                height: h,
                position: 'relative',
                flexShrink: 0,
                backgroundColor: 'var(--bs-light, #f8f9fa)',
            }}
            aria-hidden={alt ? undefined : true}
        >
            {showPlaceholder && !loadError && (
                <div
                    className="position-absolute top-0 start-0 w-100 h-100"
                    style={{ background: 'linear-gradient(90deg, #e9ecef 0%, #f8f9fa 50%, #e9ecef 100%)' }}
                />
            )}
            {showImg && (
                <img
                    className={imgClassName}
                    src={effectiveSrc}
                    alt={alt}
                    width={typeof width === 'number' ? width : undefined}
                    height={typeof height === 'number' ? height : undefined}
                    loading="lazy"
                    decoding="async"
                    style={{
                        width: w,
                        height: h,
                        objectFit: 'contain',
                        display: 'block',
                        position: 'relative',
                        zIndex: 1,
                    }}
                    onError={onImgError}
                    onLoad={() => setLoadError(false)}
                />
            )}
            {loadError && (
                <div
                    className="d-flex align-items-center justify-content-center w-100 h-100 position-absolute top-0 start-0"
                    style={{ zIndex: 2 }}
                >
                    <i className={fallbackClassName} style={{ fontSize: '1.25rem', opacity: 0.45 }} aria-hidden />
                </div>
            )}
        </div>
    );
}
