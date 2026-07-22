import { useState, useEffect } from 'react';

/**
 * Custom hook to debounce a value by specified delay (default 300ms)
 * @param {any} value 
 * @param {number} delay 
 * @returns debouncedValue
 */
export const useDebounce = (value, delay = 300) => {
  const [debouncedValue, setDebouncedValue] = useState(value);

  useEffect(() => {
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
};

export default useDebounce;
