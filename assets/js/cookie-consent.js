/**
 * Cookie Consent Manager
 * GDPR-compliant consent system for analytics tracking
 */

(function () {
    'use strict';

    const CONSENT_KEY = 'iv_analytics_consent';
    const CONSENT_COOKIE = 'iv_consent';
    const CONSENT_VERSION = 1;

    // Check if consent was already given
    function hasConsent() {
        try {
            const stored = localStorage.getItem(CONSENT_KEY);
            if (stored) {
                const data = JSON.parse(stored);
                return data.consent === true && data.version >= CONSENT_VERSION;
            }
        } catch (e) { }
        return false;
    }

    // Check if consent was explicitly declined
    function hasDeclined() {
        try {
            const stored = localStorage.getItem(CONSENT_KEY);
            if (stored) {
                const data = JSON.parse(stored);
                return data.consent === false;
            }
        } catch (e) { }
        return false;
    }

    // Store consent decision
    function storeConsent(accepted) {
        const data = {
            consent: accepted,
            version: CONSENT_VERSION,
            timestamp: new Date().toISOString()
        };

        localStorage.setItem(CONSENT_KEY, JSON.stringify(data));

        // Also set a cookie for server-side reading
        const expires = new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toUTCString();
        document.cookie = `${CONSENT_COOKIE}=${accepted ? '1' : '0'}; expires=${expires}; path=/; SameSite=Lax`;
    }

    // Show consent banner
    function showBanner() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.classList.remove('translate-y-full', 'opacity-0');
            banner.classList.add('translate-y-0', 'opacity-100');
        }
    }

    // Hide consent banner
    function hideBanner() {
        const banner = document.getElementById('cookie-consent-banner');
        if (banner) {
            banner.classList.remove('translate-y-0', 'opacity-100');
            banner.classList.add('translate-y-full', 'opacity-0');
            setTimeout(() => banner.remove(), 300);
        }
    }

    // Accept cookies
    function acceptCookies() {
        storeConsent(true);
        hideBanner();
        initTracking();
    }

    // Decline cookies
    function declineCookies() {
        storeConsent(false);
        hideBanner();
    }

    // Initialize tracking (called when consent is given)
    function initTracking() {
        if (!hasConsent()) return;

        // Track current page view
        trackPageView();

        // Set up navigation tracking for SPA-like navigation
        if (window.history && window.history.pushState) {
            const originalPushState = history.pushState;
            history.pushState = function () {
                originalPushState.apply(this, arguments);
                trackPageView();
            };

            window.addEventListener('popstate', trackPageView);
        }
    }

    // Track page view
    function trackPageView() {
        if (!hasConsent()) return;

        const data = {
            url: window.location.pathname + window.location.search,
            referrer: document.referrer || null,
            title: document.title,
            screenWidth: window.innerWidth,
            screenHeight: window.innerHeight
        };

        fetch('/api/track.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data),
            keepalive: true
        }).catch(() => {
            // Silently fail - tracking should never break the site
        });
    }

    // Expose functions globally
    window.CookieConsent = {
        hasConsent: hasConsent,
        hasDeclined: hasDeclined,
        accept: acceptCookies,
        decline: declineCookies,
        trackPageView: trackPageView
    };

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function () {
        // If already consented, start tracking immediately
        if (hasConsent()) {
            initTracking();
            return;
        }

        // If already declined, do nothing
        if (hasDeclined()) {
            return;
        }

        // Otherwise, show the consent banner after a short delay
        setTimeout(showBanner, 1000);
    });
})();
