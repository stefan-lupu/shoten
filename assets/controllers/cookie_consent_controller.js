import { Controller } from '@hotwired/stimulus';

const COOKIE_NAME = 'cookie_consent';
const COOKIE_MAX_AGE = 60 * 60 * 24 * 365;

export default class extends Controller {
    connect() {
        if (!this.hasConsentCookie()) {
            this.element.classList.remove('d-none');
        }
    }

    accept() {
        this.setCookie('accepted');
        // Scripturile Google (Analytics/AdSense/Ads) sunt randate condiționat
        // server-side de prezența acestui cookie — un reload e cel mai simplu
        // mod sigur de a le activa fără să duplicăm logica de gating în JS.
        window.location.reload();
    }

    refuse() {
        this.setCookie('refused');
        this.element.classList.add('d-none');
    }

    hasConsentCookie() {
        return document.cookie.split('; ').some((c) => c.startsWith(COOKIE_NAME + '='));
    }

    setCookie(value) {
        document.cookie = `${COOKIE_NAME}=${value}; path=/; max-age=${COOKIE_MAX_AGE}; samesite=lax`;
    }
}
