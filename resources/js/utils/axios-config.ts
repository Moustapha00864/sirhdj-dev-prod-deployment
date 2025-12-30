import axios from 'axios';

// Set CSRF token for all requests
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Intercept requests to add fresh CSRF token
axios.interceptors.request.use((config) => {
  let csrfToken = null;

  // Try to get CSRF token from meta tag first (most reliable)
  const metaToken = document.head.querySelector('meta[name="csrf-token"]');
  if (metaToken) {
    csrfToken = (metaToken as HTMLMetaElement).content;
  }

  // Override with Inertia token if available (fresher after login)
  try {
    if (typeof window !== 'undefined' && (window as any).page?.props?.csrf_token) {
      csrfToken = (window as any).page.props.csrf_token;
    }
  } catch (e) {
    // Ignore errors accessing window.page
  }

  if (csrfToken) {
    config.headers['X-CSRF-TOKEN'] = csrfToken;
  }

  return config;
});


// Intercept responses to handle common errors
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response ? error.response.status : null;

    if (status === 419) {
      // CSRF token mismatch or session expired - reload page to get fresh token
      window.location.reload();
    }

    return Promise.reject(error);
  }
);

export default axios;