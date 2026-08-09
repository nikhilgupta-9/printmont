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
  const method = (options.method || 'GET').toUpperCase();
  
  // Only deduplicate and cache GET requests
  const isGet = method === 'GET';
  const requestKey = isGet && url ? url : null;

  if (requestKey) {
    let cached = responseCache.get(requestKey);
    
    if (!cached) {
      try {
        const sessionData = sessionStorage.getItem(`api_cache_${requestKey}`);
        if (sessionData) {
          cached = JSON.parse(sessionData);
          responseCache.set(requestKey, cached);
        }
      } catch (e) {}
    }

    if (cached) {
      if (Date.now() - cached.timestamp < CACHE_DURATION) {
        return new Response(cached.body, {
          status: cached.status,
          statusText: cached.statusText,
          headers: new Headers(cached.headers)
        });
      } else {
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

      // Hostinger's CDN sporadically answers requests arriving from the Vercel
      // proxy with a 403 "Bot Verification" HTML page. It is served by hcdn, so
      // the request never reaches PHP — retrying is side-effect free even for a
      // POST, and usually succeeds on the next attempt. Surfaced to the user as
      // "Server error (403)" on login/signup before this.
      if (response.status === 403 && retryCount < 3) {
        const peek = await response.clone().text().catch(() => '');
        if (peek.includes('Bot Verification')) {
          const backoff = 400 * Math.pow(2, retryCount) + Math.random() * 300;
          console.warn(`[Fetch Interceptor] Hostinger bot challenge on ${url}. Retrying in ${Math.round(backoff)}ms...`);
          await delay(backoff);
          return executeWithRetry(retryCount + 1);
        }
      }

      // Enhanced Error Logging to Browser Console for All Requests
      if (response) {
        const cloneForLog = response.clone();
        cloneForLog.text().then((text) => {
          let isJson = false;
          let json = null;
          try {
            json = JSON.parse(text);
            isJson = true;
          } catch (e) {}

          if (!response.ok || (isJson && json && json.success === false)) {
            console.group(`🚨 [API ERROR ${response.status}] ${method} ${url}`);
            console.error("URL:", url);
            console.error("Status:", response.status, response.statusText);
            if (isJson) {
              console.error("Error Detail:", json.error || json.message || json);
            } else {
              console.error("Server HTML/Raw Output:", text.slice(0, 500));
            }
            console.groupEnd();
          }
        }).catch(() => {});
      }

      return response;
    } catch (error) {
      console.group(`❌ [NETWORK ERROR] ${method} ${url}`);
      console.error("Request Failed:", error.message || error);
      console.groupEnd();

      if (retryCount < 3) {
        const backoff = Math.pow(2, retryCount) * 1000 + Math.random() * 1000;
        console.warn(`[Fetch Interceptor] Retrying network failure (${retryCount + 1}/3)...`);
        await delay(backoff);
        return executeWithRetry(retryCount + 1);
      }
      throw error;
    }
  };

  if (requestKey) {
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
          } catch (e) {}
        }).catch(e => console.error("[Fetch Interceptor] Cache read error", e));
      }

      return cloneToReturn;
    } catch (error) {
      throw error;
    }
  }

  return queueRequest(() => executeWithRetry(0));
};
