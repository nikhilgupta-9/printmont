const originalFetch = window.fetch;

const activeRequests = new Map();
const responseCache = new Map();
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes cache

// Automatically purge cache when user focuses window (e.g. switching back from Admin panel)
if (typeof window !== 'undefined') {
  window.addEventListener('focus', () => {
    responseCache.clear();
    try {
      Object.keys(sessionStorage).forEach(key => {
        if (key.startsWith('api_cache_')) sessionStorage.removeItem(key);
      });
    } catch (e) {}
  });
}

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

// ── Silent token refresh ─────────────────────────────────────────────
// The access token lives 1 hour; the refresh token (saved at login) 30 days. On a 401 we get a
// new access token once (shared by all requests that failed at the same time) and retry.
let refreshPromise = null;

const refreshAccessToken = (requestUrl) => {
  const refreshToken = localStorage.getItem('refresh_token');
  const apiRoot = requestUrl && requestUrl.includes('/api/') ? requestUrl.split('/api/')[0] + '/api/' : null;
  if (!refreshToken || !apiRoot) return Promise.resolve(null);

  if (!refreshPromise) {
    refreshPromise = (async () => {
      try {
        const res = await originalFetch(`${apiRoot}user-api.php?action=refresh_token`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ refresh_token: refreshToken }),
        });
        const data = await res.json().catch(() => null);
        const newToken = data?.success ? data.tokens?.access_token : null;
        if (!newToken) {
          localStorage.removeItem('refresh_token');
          return null;
        }
        localStorage.setItem('token', newToken);
        if (data.tokens.refresh_token) localStorage.setItem('refresh_token', data.tokens.refresh_token);
        window.dispatchEvent(new CustomEvent('auth:token-refreshed', { detail: { token: newToken } }));
        return newToken;
      } catch {
        return null;
      } finally {
        setTimeout(() => { refreshPromise = null; }, 0);
      }
    })();
  }
  return refreshPromise;
};

// Same request again with the new token (only requests that carried a Bearer token).
// Headers stay a plain object with their original names: a Headers object lowercases them
// ("authorization"), which the PHP APIs' getBearerToken() does not find.
const retryWithFreshToken = async (args, url) => {
  const options = args[1] || {};
  const headers = options.headers instanceof Headers
    ? Object.fromEntries(options.headers.entries())
    : { ...(options.headers || {}) };
  const authKey = Object.keys(headers).find((k) => k.toLowerCase() === 'authorization');
  if (!authKey || !/^Bearer\s/i.test(headers[authKey])) return null;
  const newToken = await refreshAccessToken(url);
  if (!newToken) return null;
  delete headers[authKey];
  headers.Authorization = `Bearer ${newToken}`;
  return originalFetch(args[0], { ...options, headers });
};

window.fetch = async (...args) => {
  const url = typeof args[0] === 'string' ? args[0] : args[0]?.url;
  const options = args[1] || {};
  const method = (options.method || 'GET').toUpperCase();
  
  // Only deduplicate and cache GET requests (exclude dynamic user/cart/wishlist APIs and real-time admin sync APIs)
  const isGet = method === 'GET';
  const isNoStore = options.cache === 'no-store' || options.cache === 'reload';
  const isDynamicApi = url && (
    url.includes('user-api.php') || 
    url.includes('cart-api.php') || 
    url.includes('wishlist-api.php') || 
    url.includes('auth-settings-api.php') ||
    url.includes('menu_api.php') ||
    url.includes('header-settings-api.php') ||
    url.includes('category-api.php') ||
    url.includes('category_api.php') ||
    url.includes('home-layout-api.php') ||
    url.includes('product-layout-api.php') ||
    url.includes('footer-api.php')
  );
  const requestKey = isGet && url && !isDynamicApi && !isNoStore ? url : null;

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

      // A 401 from our own API means the stored token is expired or invalid.
      // Announce it once, globally, so AuthContext can clear the session —
      // otherwise the header keeps showing the user as signed in while every
      // authenticated request is being rejected.
      if (response && response.status === 401 && !/\/login|\/register|action=(login|register|refresh_token)/.test(url || "")) {
        const retried = await retryWithFreshToken(args, url);
        if (retried && retried.status !== 401) return retried;
        window.dispatchEvent(new CustomEvent("auth:unauthorized", { detail: { url } }));
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
