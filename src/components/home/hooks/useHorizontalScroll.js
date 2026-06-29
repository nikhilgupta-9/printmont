import { useState, useEffect } from "react";

/**
 * Hook to manage custom horizontal scroll controls for a container.
 * @param {React.RefObject} containerRef Ref to the scrollable container.
 * @param {Array} dependencies Triggers update when dependencies change (e.g. products list).
 */
export default function useHorizontalScroll(containerRef, dependencies = []) {
  const [canScrollLeft, setCanScrollLeft] = useState(false);
  const [canScrollRight, setCanScrollRight] = useState(false);

  const updateScrollButtons = () => {
    const container = containerRef.current;
    if (!container) return;
    const { scrollLeft, scrollWidth, clientWidth } = container;
    setCanScrollLeft(scrollLeft > 5);
    setCanScrollRight(scrollLeft + clientWidth < scrollWidth - 5);
  };

  const scroll = (direction, scrollAmount = 400) => {
    const container = containerRef.current;
    if (container) {
      container.scrollBy({
        left: direction === "left" ? -scrollAmount : scrollAmount,
        behavior: "smooth",
      });
    }
  };

  useEffect(() => {
    updateScrollButtons();

    const container = containerRef.current;
    if (!container) return;

    container.addEventListener("scroll", updateScrollButtons);
    window.addEventListener("resize", updateScrollButtons);

    return () => {
      container.removeEventListener("scroll", updateScrollButtons);
      window.removeEventListener("resize", updateScrollButtons);
    };
  }, [containerRef, ...dependencies]);

  return {
    canScrollLeft,
    canScrollRight,
    scroll,
    updateScrollButtons
  };
}
