import { liveCookie, liveToken, liveUrl, locale, texts } from './config.js';
import { showToast } from './toast.js';

// Tryb dynamicznego tłumaczenia. Serwer (LiveTranslationMode) otacza teksty znacznikami:
// U+E000 klucz U+E001 tekst U+E002. Tutaj zamieniamy je na elementy z data-i18n-key i dodajemy ołówek do edycji.
const markerPattern = /\uE000([^\uE001]*)\uE001([\s\S]*?)\uE002/g;
const markerStrip = /\uE000[^\uE001]*\uE001|\uE002/g;
const stripMarkers = (value) => value.replace(markerStrip, '');

const ajaxHeaders = { 'X-Requested-With': 'XMLHttpRequest' };
const PENCIL_HIDE_DELAY_MS = 160;
const COOKIE_MAX_AGE_S = 60 * 60 * 24 * 365;

// Teksty, w których nie da się pokazać elementu (np. wnętrze <option>), tracą tylko znaczniki.
const PLAIN_TAGS = ['OPTION', 'SCRIPT', 'STYLE', 'TEXTAREA', 'TITLE'];
const KEYED_ATTRIBUTES = ['placeholder', 'title'];

const wrapTextNodes = () => {
    const nodes = [];
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    while (walker.nextNode()) {
        if (walker.currentNode.nodeValue.includes('\uE000')) {
            nodes.push(walker.currentNode);
        }
    }

    nodes.forEach((node) => {
        const value = node.nodeValue;
        if (PLAIN_TAGS.includes(node.parentElement?.tagName)) {
            node.nodeValue = stripMarkers(value);
            return;
        }

        const fragment = document.createDocumentFragment();
        let last = 0;
        for (const match of value.matchAll(markerPattern)) {
            if (match.index > last) {
                fragment.append(value.slice(last, match.index));
            }
            const span = document.createElement('span');
            span.className = 'i18n-live';
            span.dataset.i18nKey = match[1];
            span.textContent = match[2];
            fragment.append(span);
            last = match.index + match[0].length;
        }
        fragment.append(value.slice(last));
        node.replaceWith(fragment);
    });
};

const cleanAttributes = () => {
    document.querySelectorAll('body *').forEach((element) => {
        [...element.attributes].forEach((attribute) => {
            if (!attribute.value.includes('\uE000')) {
                return;
            }
            const key = [...attribute.value.matchAll(markerPattern)][0]?.[1];
            element.setAttribute(attribute.name, stripMarkers(attribute.value));
            if (key && KEYED_ATTRIBUTES.includes(attribute.name)) {
                element.dataset.i18nKey = key;
            }
        });
    });
};

const createPencil = () => {
    const pencil = document.createElement('button');
    pencil.type = 'button';
    pencil.className = 'i18n-pencil';
    pencil.setAttribute('aria-label', texts.liveEdit);
    pencil.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    document.body.append(pencil);

    return pencil;
};

// Ołówek pojawia się nad elementem z data-i18n-key i znika po zjechaniu myszą.
const bindPencil = (pencil, onEdit) => {
    let current = null;
    let hideTimer = null;

    const show = (target) => {
        window.clearTimeout(hideTimer);
        current = target;
        const rect = target.getBoundingClientRect();
        // W otwartym oknie dialogowym ołówek musi być jego dzieckiem, inaczej zasłoni go warstwa modala.
        const host = target.closest('dialog[open]') ?? document.body;
        if (pencil.parentElement !== host) {
            host.append(pencil);
        }
        pencil.style.left = `${Math.min(rect.right - 4, window.innerWidth - 30)}px`;
        pencil.style.top = `${Math.max(rect.top - 12, 2)}px`;
        pencil.classList.add('is-visible');
    };

    const hide = () => {
        hideTimer = window.setTimeout(() => {
            pencil.classList.remove('is-visible');
            current = null;
        }, PENCIL_HIDE_DELAY_MS);
    };

    document.addEventListener('mouseover', (event) => {
        if (pencil.contains(event.target)) {
            window.clearTimeout(hideTimer);
            return;
        }
        const target = event.target.closest?.('[data-i18n-key]');
        if (target) {
            show(target);
        } else if (current) {
            hide();
        }
    });

    document.addEventListener('scroll', () => pencil.classList.remove('is-visible'), true);

    pencil.addEventListener('click', () => {
        if (current) {
            const key = current.dataset.i18nKey;
            pencil.classList.remove('is-visible');
            onEdit(key);
        }
    });
};

// Okno poprawy: pola per język (name = kod języka) wypełniane z odpowiedzi serwera.
const bindEditModal = () => {
    const modal = document.getElementById('i18n-edit-modal');
    const form = modal.querySelector('[data-i18n-edit-form]');
    let editedKey = '';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = new FormData(form);
        body.set('key', editedKey);
        body.set('_token', liveToken);

        try {
            const response = await fetch(liveUrl, { method: 'POST', body, headers: ajaxHeaders });
            if (!response.ok) {
                throw new Error(texts.liveError);
            }
            window.location.reload();
        } catch {
            showToast('error', texts.liveError);
        }
    });

    return async (key) => {
        editedKey = key;

        try {
            const response = await fetch(`${liveUrl}?key=${encodeURIComponent(key)}`, { headers: ajaxHeaders });
            if (!response.ok) {
                throw new Error(texts.liveError);
            }
            const entry = await response.json();
            modal.querySelector('[data-i18n-edit-key]').textContent = entry.key;
            Object.entries(entry.values).forEach(([code, value]) => {
                form.elements[code].value = value;
            });
            modal.showModal();
            form.elements[locale]?.focus();
        } catch {
            showToast('error', texts.liveError);
        }
    };
};

export const initLiveTranslation = () => {
    if (!document.documentElement.hasAttribute('data-live-i18n')) {
        return;
    }

    document.title = stripMarkers(document.title);
    wrapTextNodes();
    cleanAttributes();
    bindPencil(createPencil(), bindEditModal());
};

// Przełącznik w menu zębatki: włącza/wyłącza tryb ciasteczkiem i odświeża stronę.
export const initLiveToggle = () => {
    document.querySelector('[data-live-toggle]')?.addEventListener('change', (event) => {
        const enabled = event.target.checked;
        document.cookie = `${liveCookie}=${enabled ? '1' : ''}; path=/; max-age=${enabled ? COOKIE_MAX_AGE_S : 0}; SameSite=Lax`;
        window.location.reload();
    });
};
