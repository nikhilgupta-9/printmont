import { useState, useEffect } from "react";

/**
 * Hook to track the current bootstrap breakpoint.
 * Returns 'sm', 'md', 'lg', or 'xl'.
 */
export default function useBreakpoint() {
  const [breakpoint, setBreakpoint] = useState("sm");

  useEffect(() => {
    const handleResize = () => {
      const width = window.innerWidth;
      if (width >= 1200) {
        setBreakpoint("xl");
      } else if (width >= 992) {
        setBreakpoint("lg");
      } else if (width >= 768) {
        setBreakpoint("md");
      } else {
        setBreakpoint("sm");
      }
    };

    handleResize();
    window.addEventListener("resize", handleResize);

    return () => {
      window.removeEventListener("resize", handleResize);
    };
  }, []);

  return breakpoint;
}
