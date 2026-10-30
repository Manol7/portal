// Konfiguracja przekazywana z serwera (templates/_partials/i18n_config.html.twig).
const raw = JSON.parse(document.getElementById('app-i18n')?.textContent || '{}');

export const texts = raw.texts ?? {};
export const roles = raw.roles ?? {};
export const locale = raw.locale ?? document.documentElement.lang;
export const liveCookie = raw.liveCookie;
export const liveUrl = raw.liveUrl;
export const liveToken = raw.liveToken;
