import axios from 'axios';
import { BASE_URL } from '../config/apiEndpoints';

/**
 * Service layer for global search REST API.
 * Supports request cancellation via AbortController to prevent race conditions.
 */

const SEARCH_ENDPOINT = `${BASE_URL}/search.php`;

/**
 * Fetch live search suggestions (debounced)
 * @param {string} query 
 * @param {AbortSignal} signal 
 */
export const fetchSuggestions = async (query, signal) => {
  if (!query || query.trim().length < 1) {
    return {
      products: [],
      categories: [],
      subCategories: [],
      subSubCategories: [],
      productTypes: []
    };
  }

  try {
    const response = await axios.get(SEARCH_ENDPOINT, {
      params: {
        q: query.trim(),
        type: 'suggestions'
      },
      signal
    });

    if (response.data && response.data.success) {
      return response.data.suggestions || {
        products: [],
        categories: [],
        subCategories: [],
        subSubCategories: [],
        productTypes: []
      };
    }
    return {
      products: [],
      categories: [],
      subCategories: [],
      subSubCategories: [],
      productTypes: []
    };
  } catch (error) {
    if (axios.isCancel(error) || error.name === 'CanceledError') {
      // Request aborted by superceding query, ignore
      return null;
    }
    console.error('Failed to fetch search suggestions:', error);
    return {
      products: [],
      categories: [],
      subCategories: [],
      subSubCategories: [],
      productTypes: []
    };
  }
};

/**
 * Fetch full search results page with filters and pagination
 * @param {Object} params { q, category_id, brand, min_price, max_price, sort, page, limit }
 * @param {AbortSignal} signal 
 */
export const fetchSearchResults = async (params = {}, signal) => {
  try {
    const response = await axios.get(SEARCH_ENDPOINT, {
      params: {
        type: 'all',
        ...params
      },
      signal
    });

    if (response.data && response.data.success) {
      return response.data;
    }
    return {
      success: false,
      suggestions: {},
      results: { items: [], total: 0, page: 1, totalPages: 1, facets: {} }
    };
  } catch (error) {
    if (axios.isCancel(error) || error.name === 'CanceledError') {
      return null;
    }
    console.error('Failed to fetch search results:', error);
    return {
      success: false,
      suggestions: {},
      results: { items: [], total: 0, page: 1, totalPages: 1, facets: {} }
    };
  }
};
