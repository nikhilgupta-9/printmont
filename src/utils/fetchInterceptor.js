const originalFetch = window.fetch;

const activeRequests = new Map();
const responseCache = new Map();
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes cache

const MAX_CONCURRENT = 5;
let currentConcurrent = 0;
const requestQueue = [];

const processQueue = () => {
  if (requestQueue.length > 0 && currentConcurrent < MAX_CONCURRENT) {
    const next = requestQueue.shift();
    next();
  }
};

const queueRequest = (executeFetch) => {
  return new Promise((resolve, reject) => {
    const task = async () => {
      currentConcurrent++;
      try {
        const result = await executeFetch();
        resolve(result);
      } catch (err) {
        reject(err);
      } finally {
        currentConcurrent--;
        processQueue();
      }
    };

    if (currentConcurrent < MAX_CONCURRENT) {
      task();
    } else {
      requestQueue.push(task);
    }
  });
};

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

window.fetch = async (...args) => {
  const url = typeof args[0] === 'string' ? args[0] : args[0]?.url;
  const options = args[1] || {};
  
  // Only deduplicate and cache GET requests
  const isGet = !options.method || options.method.toUpperCase() === 'GET';
  const requestKey = isGet && url ? url : null;

  if (requestKey) {
    // 1. Check if we already have a valid cached response in memory OR sessionStorage
    let cached = responseCache.get(requestKey);
    
    if (!cached) {
      try {
        const sessionData = sessionStorage.getItem(`api_cache_${requestKey}`);
        if (sessionData) {
          cached = JSON.parse(sessionData);
          responseCache.set(requestKey, cached); // Load into memory
        }
      } catch (e) {
        // Ignore storage errors (e.g., quota exceeded or incognito mode)
      }
    }

    if (cached) {
      if (Date.now() - cached.timestamp < CACHE_DURATION) {
        return new Response(cached.body, {
          status: cached.status,
          statusText: cached.statusText,
          headers: new Headers(cached.headers)
        });
      } else {
        // Expired
        responseCache.delete(requestKey);
        try { sessionStorage.removeItem(`api_cache_${requestKey}`); } catch (e) {}
      }
    }
  }

  const executeWithRetry = async (retryCount = 0) => {
    try {
      const response = await originalFetch(...args);
      if (response.status === 429 && retryCount < 4) {
        const backoff = Math.pow(2, retryCount) * 1000 + Math.random() * 1000;
        console.warn(`[Fetch Interceptor] 429 Too Many Requests for ${url}. Retrying in ${Math.round(backoff)}ms...`);
        await delay(backoff);
        return executeWithRetry(retryCount + 1);
      }
      return response;
    } catch (error) {
      // Retry on network errors
      if (retryCount < 3) {
        const backoff = Math.pow(2, retryCount) * 1000 + Math.random() * 1000;
        console.warn(`[Fetch Interceptor] Network Error for ${url}. Retrying in ${Math.round(backoff)}ms...`);
        await delay(backoff);
        return executeWithRetry(retryCount + 1);
      }
      throw error;
    }
  };

  if (requestKey) {
    // 2. Check if there's an active in-flight request for this URL
    let sharedPromise;
    if (activeRequests.has(requestKey)) {
      sharedPromise = activeRequests.get(requestKey);
    } else {
      sharedPromise = queueRequest(() => executeWithRetry(0));
      activeRequests.set(requestKey, sharedPromise);
      
      sharedPromise.finally(() => {
        setTimeout(() => {
          if (activeRequests.get(requestKey) === sharedPromise) {
            activeRequests.delete(requestKey);
          }
        }, 100);
      }).catch(() => {});
    }

    try {
      const res = await sharedPromise;
      const cloneToReturn = res.clone();
      
      // 3. Save to cache asynchronously so we don't block
      if (!responseCache.has(requestKey) && res.ok) {
        const cacheClone = res.clone();
        cacheClone.text().then(textBody => {
          const headersObj = {};
          cacheClone.headers.forEach((val, key) => { headersObj[key] = val; });
          
          const cacheData = {
            body: textBody,
            status: cacheClone.status,
            statusText: cacheClone.statusText,
            headers: headersObj,
            timestamp: Date.now()
          };

          responseCache.set(requestKey, cacheData);
          try { 
            sessionStorage.setItem(`api_cache_${requestKey}`, JSON.stringify(cacheData)); 
          } catch (e) {
            // Storage might be full, silently ignore
          }
        }).catch(e => console.error("[Fetch Interceptor] Cache read error", e));
      }

      return cloneToReturn;
    } catch (error) {
      throw error;
    }
  }

  // Non-GET requests go straight through
  return queueRequest(() => executeWithRetry(0));
};
