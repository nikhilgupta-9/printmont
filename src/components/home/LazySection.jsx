import React, { useState, useRef, useEffect } from "react";

/**
 * LazySection — IntersectionObserver-based lazy loading wrapper.
 * Renders a skeleton placeholder until the section scrolls near the viewport,
 * then mounts the actual children and never unmounts them.
 *
 * @param {React.ReactNode} children — The actual section content to render.
 * @param {React.ReactNode} skeleton — Skeleton placeholder shown before trigger.
 * @param {string} [rootMargin='300px'] — How far before the viewport to start loading.
 * @param {string|number} [minHeight='120px'] — Minimum height to prevent layout shift.
 * @param {string} [className] — Optional wrapper class name.
 */
export default function LazySection({
  children,
  skeleton,
  rootMargin = "300px",
  minHeight = "120px",
  className = "",
}) {
  const [isVisible, setIsVisible] = useState(false);
  const ref = useRef(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    // If IntersectionObserver is not supported, just render immediately
    if (!("IntersectionObserver" in window)) {
      setIsVisible(true);
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setIsVisible(true);
          observer.unobserve(el);
        }
      },
      {
        rootMargin: `${rootMargin} 0px ${rootMargin} 0px`,
        threshold: 0,
      }
    );

    observer.observe(el);

    return () => {
      observer.unobserve(el);
    };
  }, [rootMargin]);

  return (
    <div
      ref={ref}
      className={className}
      style={{ minHeight: isVisible ? undefined : minHeight }}
    >
      {isVisible ? children : skeleton}
    </div>
  );
}
