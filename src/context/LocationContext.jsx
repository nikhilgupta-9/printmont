import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { API_ENDPOINTS } from '../config/apiEndpoints';

const LocationContext = createContext();

const DEFAULT_LOCATION = {
  city_name: 'Delhi NCR',
  state_name: 'Delhi',
  pincode: '110001',
  is_same_day: 1,
  is_next_day: 1,
  standard_delivery_days: 'Same Day / 1-2 Days',
  is_set: false
};

const STORAGE_KEY = 'printmont_delivery_location';

export const LocationProvider = ({ children }) => {
  const [location, setLocationState] = useState(() => {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved) {
        const parsed = JSON.parse(saved);
        return { ...parsed, is_set: true };
      }
    } catch (e) {
      console.error('Error reading location from storage:', e);
    }
    return DEFAULT_LOCATION;
  });

  const [isLocationModalOpen, setIsLocationModalOpen] = useState(false);
  const [popularCities, setPopularCities] = useState([]);
  const [isLoadingPopular, setIsLoadingPopular] = useState(false);

  // Fetch popular cities from Backend API on mount
  useEffect(() => {
    const fetchPopular = async () => {
      setIsLoadingPopular(true);
      try {
        const res = await fetch(API_ENDPOINTS.LOCATIONS_POPULAR);
        const json = await res.json();
        if (json && json.success && Array.isArray(json.data) && json.data.length > 0) {
          setPopularCities(json.data);
        } else {
          // Fallback cities if offline / db empty
          setPopularCities([
            { id: 1, city_name: 'Delhi NCR', state_name: 'Delhi', pincode: '110001', is_same_day: 1, is_next_day: 1, standard_delivery_days: 'Same Day / 1-2 Days' },
            { id: 2, city_name: 'Mumbai', state_name: 'Maharashtra', pincode: '400001', is_same_day: 1, is_next_day: 1, standard_delivery_days: 'Same Day / 1-2 Days' },
            { id: 3, city_name: 'Bengaluru', state_name: 'Karnataka', pincode: '560001', is_same_day: 1, is_next_day: 1, standard_delivery_days: 'Same Day / 1-2 Days' },
            { id: 4, city_name: 'Hyderabad', state_name: 'Telangana', pincode: '500001', is_same_day: 1, is_next_day: 1, standard_delivery_days: 'Same Day / 1-2 Days' },
            { id: 5, city_name: 'Pune', state_name: 'Maharashtra', pincode: '411001', is_same_day: 1, is_next_day: 1, standard_delivery_days: '1-2 Days' },
            { id: 6, city_name: 'Chennai', state_name: 'Tamil Nadu', pincode: '600001', is_same_day: 1, is_next_day: 1, standard_delivery_days: '1-2 Days' },
            { id: 7, city_name: 'Kolkata', state_name: 'West Bengal', pincode: '700001', is_same_day: 1, is_next_day: 1, standard_delivery_days: '1-2 Days' },
            { id: 8, city_name: 'Ahmedabad', state_name: 'Gujarat', pincode: '380001', is_same_day: 1, is_next_day: 1, standard_delivery_days: '1-2 Days' },
            { id: 9, city_name: 'Jaipur', state_name: 'Rajasthan', pincode: '302001', is_same_day: 0, is_next_day: 1, standard_delivery_days: '2-3 Days' },
            { id: 10, city_name: 'Chandigarh', state_name: 'Punjab', pincode: '160017', is_same_day: 0, is_next_day: 1, standard_delivery_days: '2-3 Days' },
            { id: 11, city_name: 'Lucknow', state_name: 'Uttar Pradesh', pincode: '226001', is_same_day: 0, is_next_day: 1, standard_delivery_days: '2-3 Days' },
            { id: 12, city_name: 'Gurgaon', state_name: 'Haryana', pincode: '122001', is_same_day: 1, is_next_day: 1, standard_delivery_days: 'Same Day / 1-2 Days' },
            { id: 13, city_name: 'Noida', state_name: 'Uttar Pradesh', pincode: '201301', is_same_day: 1, is_next_day: 1, standard_delivery_days: 'Same Day / 1-2 Days' },
            { id: 14, city_name: 'Indore', state_name: 'Madhya Pradesh', pincode: '452001', is_same_day: 0, is_next_day: 1, standard_delivery_days: '2-3 Days' }
          ]);
        }
      } catch (err) {
        console.error('Error fetching popular locations:', err);
      } finally {
        setIsLoadingPopular(false);
      }
    };
    fetchPopular();
  }, []);

  const selectLocation = useCallback((locData) => {
    if (!locData) return;
    const newLoc = {
      city_name: locData.city_name || 'Delhi NCR',
      state_name: locData.state_name || '',
      pincode: locData.pincode || '',
      is_same_day: locData.is_same_day ? 1 : 0,
      is_next_day: locData.is_next_day ? 1 : 0,
      standard_delivery_days: locData.standard_delivery_days || '1-2 Days',
      is_set: true
    };
    setLocationState(newLoc);
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(newLoc));
    } catch (e) {
      console.error('Error saving location to storage:', e);
    }
    setIsLocationModalOpen(false);
  }, []);

  const openLocationModal = useCallback(() => {
    setIsLocationModalOpen(true);
  }, []);

  const closeLocationModal = useCallback(() => {
    setIsLocationModalOpen(false);
  }, []);

  // Search API helper
  const searchLocations = useCallback(async (query) => {
    if (!query || !query.trim()) return [];
    try {
      const res = await fetch(API_ENDPOINTS.LOCATIONS_SEARCH(query.trim()));
      const json = await res.json();
      if (json && json.success && Array.isArray(json.data)) {
        return json.data;
      }
    } catch (e) {
      console.error('Error searching locations:', e);
    }
    return [];
  }, []);

  // Check pincode API helper
  const checkPincode = useCallback(async (pincode) => {
    if (!pincode || !pincode.trim()) return null;
    try {
      const res = await fetch(API_ENDPOINTS.LOCATIONS_CHECK(pincode.trim()));
      const json = await res.json();
      return json;
    } catch (e) {
      console.error('Error checking pincode:', e);
      return null;
    }
  }, []);

  const value = {
    location,
    isLocationModalOpen,
    openLocationModal,
    closeLocationModal,
    selectLocation,
    popularCities,
    isLoadingPopular,
    searchLocations,
    checkPincode
  };

  return (
    <LocationContext.Provider value={value}>
      {children}
    </LocationContext.Provider>
  );
};

export const useLocationContext = () => {
  const context = useContext(LocationContext);
  if (!context) {
    throw new Error('useLocationContext must be used within a LocationProvider');
  }
  return context;
};

export default LocationContext;
