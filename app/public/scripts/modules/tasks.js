// Lista zadań: formularz w oknie dialogowym, przeciąganie kart, podzadania, usuwanie
// oraz dynamiczne odświeżanie statystyk i tablicy (po każdej akcji i cyklicznie w tle).
// Części dynamiczne ([data-live-part]) są podmieniane, dlatego zdarzenia są delegowane z document.
import { showToast } from './toast.js';

const REFRESH_INTERVAL_MS = 8000;
const SEARCH_DEBOUNCE_MS = 250;

const post = (url, token, body) => fetch(url, {
    method: 'POST',
    headers: { 'X-CSRF-Token': token, 'Content-Type': 'application/json', Accept: 'application/json' },
    body: body === undefined ? undefined : JSON.stringify(body),
});

const live = () => document.querySelector('[data-live-part="board"]');
const dialog = () => document.getElementById('task-dialog');
const parse = (html) => new DOMParser().parseFromString(html, 'text/html');

let dragged = null;
let busy = false;

// Nie podmieniamy widoku w trakcie przeciągania, edycji w oknie ani przy ukrytej karcie.
const canRefresh = () => !busy && !dragged && !dialog()?.open && !document.hidden;

/** Podmienia części dynamiczne na wersję z podanego dokumentu. Zwraca false, gdy ich brak. */
const applyParts = (doc) => {
    const parts = doc.querySelectorAll('[data-live-part]');
    parts.forEach((fresh) => {
        document.querySelector(`[data-live-part="${fresh.dataset.livePart}"]`)?.replaceWith(fresh);
    });

    return parts.length > 0;
};

const filtersForm = () => document.querySelector('[data-tasks-filters]');

/** Bieżące filtry z formularza jako parametry adresu (puste wartości pomijamy). */
const filterParams = () => {
    const params = new URLSearchParams();
    new FormData(filtersForm()).forEach((value, key) => {
        if (value !== '' && !(key === 'sort' && value === 'manual')) {
            params.set(key, value);
        }
    });

    return params;
};

let requestSeq = 0;

/** Dopasowuje elementy poza częściami dynamicznymi (lista tagów, przycisk czyszczenia, ukryte pole widoku). */
const syncToolbar = () => {
    const board = live();
    const form = filtersForm();
    if (!board || !form) {
        return;
    }
    const select = form.elements.tag;
    const wanted = JSON.parse(board.dataset.tags || '[]');
    if (select.value !== '' && !wanted.includes(select.value)) {
        wanted.push(select.value);
    }
    const existing = [...select.options].slice(1).map((option) => option.value);
    if (existing.join('|') !== wanted.join('|')) {
        const current = select.value;
        select.querySelectorAll('option:not([value=""])').forEach((option) => option.remove());
        wanted.forEach((tag) => select.add(new Option(`#${tag}`, tag)));
        select.value = current;
    }
    form.querySelector('[data-tasks-reset]').hidden = board.dataset.filtered !== '1';
    const view = dialog()?.querySelector('input[name="view"]');
    if (view) {
        view.value = filterParams().toString();
    }
};

export const refresh = async ({ force = false } = {}) => {
    const board = live();
    if (!board || (!force && !canRefresh())) {
        return;
    }
    const seq = ++requestSeq;
    busy = true;
    board.classList.add('is-loading');
    try {
        const params = filterParams();
        params.set('partial', '1');
        const response = await fetch(`${filtersForm().action}?${params}`, { headers: { Accept: 'text/html' }, cache: 'no-store' });
        if (seq !== requestSeq) {
            return; // nowsze żądanie (np. kolejna zmiana filtra) wyprzedziło to
        }
        if (response.redirected || !response.ok) {
            // Sesja wygasła: pełne przeładowanie przeniesie na logowanie.
            window.location.reload();

            return;
        }
        if (applyParts(parse(await response.text()))) {
            syncToolbar();
            initDragAndDrop();
        }
    } catch {
        // Brak sieci: spróbujemy ponownie przy następnym cyklu.
    } finally {
        if (seq === requestSeq) {
            busy = false;
            live()?.classList.remove('is-loading');
        }
    }
};

/** Zmiana filtra: adres w pasku odzwierciedla widok (można go odświeżyć lub udostępnić), a dane ładują się w tle. */
const applyFilters = () => {
    const query = filterParams().toString();
    window.history.replaceState(null, '', query ? `?${query}` : window.location.pathname);

    return refresh({ force: true });
};

const fillForm = (task) => {
    const form = dialog().querySelector('[data-task-form]');
    const title = dialog().querySelector('[data-task-dialog-title]');
    form.elements.id.value = task?.id ?? '';
    form.elements.title.value = task?.title ?? '';
    form.elements.description.value = task?.description ?? '';
    form.elements.status.value = task?.status ?? 'todo';
    form.elements.priority.value = task?.priority ?? 'medium';
    form.elements.due.value = task?.due ?? '';
    form.elements.tags.value = task?.tags ?? '';
    form.elements.subtasks.value = task?.subtasks ?? '';
    title.textContent = task ? title.dataset.titleEdit : title.dataset.titleNew;
};

/** Wysyła formularz w tle; serwer odpowiada przekierowaniem na tablicę, z której bierzemy dane i komunikaty. Zwraca true przy sukcesie. */
const submitInBackground = async (form) => {
    busy = true;
    try {
        const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'text/html' } });
        if (!response.ok) {
            throw new Error();
        }
        const doc = parse(await response.text());
        if (applyParts(doc)) {
            syncToolbar();
            initDragAndDrop();
        }
        doc.querySelectorAll('.app-toast').forEach((toast) => {
            const type = [...toast.classList].find((c) => c.startsWith('app-toast--'))?.replace('app-toast--', '') ?? 'info';
            showToast(type, toast.querySelector('p')?.textContent ?? '');
        });

        return !doc.querySelector('.app-toast--error');
    } catch {
        form.submit();

        return false;
    } finally {
        busy = false;
    }
};

const initDelegatedEvents = () => {
    document.addEventListener('click', (event) => {
        const edit = event.target.closest('[data-task-edit]');
        if (edit) {
            fillForm(JSON.parse(edit.closest('[data-task]').dataset.task));
            dialog().showModal();
        }
        if (event.target.closest('[data-task-new]')) {
            fillForm(null);
        }
    });

    document.addEventListener('change', async (event) => {
        const box = event.target.closest('[data-subtask-index]');
        if (!box) {
            return;
        }
        const url = box.closest('[data-subtasks]').dataset.subtaskUrl.replace(/\/\d+$/, `/${box.dataset.subtaskIndex}`);
        box.disabled = true;
        try {
            const response = await post(url, live().dataset.token);
            if (!response.ok) {
                throw new Error();
            }
            await refresh({ force: true });
        } catch {
            box.checked = !box.checked;
            box.disabled = false;
        }
    });

    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (form.matches('[data-task-form]')) {
            event.preventDefault();
            if (await submitInBackground(form)) {
                dialog().close();
            }
        } else if (form.matches('form[data-confirm]')) {
            event.preventDefault();
            if (window.confirm(form.dataset.confirm)) {
                await submitInBackground(form);
            }
        }
    });
};

function initDragAndDrop() {
    const board = document.querySelector('[data-tasks-board]');
    if (!board) {
        return;
    }
    if (board.dataset.sortable !== 'true') {
        board.querySelectorAll('.task-card').forEach((card) => card.removeAttribute('draggable'));

        return;
    }

    const token = live().dataset.token;
    const reorderUrl = live().dataset.reorderUrl;
    const zones = board.querySelectorAll('[data-dropzone]');

    const save = async (zone) => {
        const ids = [...zone.querySelectorAll('.task-card')].map((card) => Number(card.dataset.taskId));
        const status = zone.closest('[data-status]').dataset.status;
        const response = await post(reorderUrl, token, { status, ids });
        if (response.ok) {
            await refresh({ force: true });
        } else {
            window.location.reload();
        }
    };

    const afterElement = (zone, y) => [...zone.querySelectorAll('.task-card:not(.is-dragging)')].find((card) => {
        const box = card.getBoundingClientRect();

        return y < box.top + box.height / 2;
    });

    board.querySelectorAll('.task-card').forEach((card) => {
        card.addEventListener('dragstart', (event) => {
            dragged = card;
            card.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', card.dataset.taskId);
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('is-dragging');
            zones.forEach((zone) => zone.classList.remove('is-over'));
            dragged = null;
        });
    });

    zones.forEach((zone) => {
        zone.addEventListener('dragover', (event) => {
            if (!dragged) {
                return;
            }
            event.preventDefault();
            zone.classList.add('is-over');
            zone.querySelector('.tasks-empty')?.remove();
            zone.insertBefore(dragged, afterElement(zone, event.clientY) ?? null);
        });
        zone.addEventListener('dragleave', (event) => {
            if (!zone.contains(event.relatedTarget)) {
                zone.classList.remove('is-over');
            }
        });
        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            dragged = null;
            save(zone);
        });
    });
}

const initFilters = () => {
    const form = filtersForm();
    if (!form) {
        return;
    }
    let timer = null;
    form.addEventListener('input', (event) => {
        if (event.target.type === 'search') {
            window.clearTimeout(timer);
            timer = window.setTimeout(applyFilters, SEARCH_DEBOUNCE_MS);
        }
    });
    form.addEventListener('change', (event) => {
        if (event.target.type !== 'search') {
            applyFilters();
        }
    });
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        window.clearTimeout(timer);
        applyFilters();
    });
    form.querySelector('[data-tasks-reset]').addEventListener('click', () => {
        form.reset();
        [...form.elements].forEach((field) => {
            if (field.type === 'checkbox') {
                field.checked = false;
            } else if (field.name) {
                field.value = field.name === 'sort' ? 'manual' : '';
            }
        });
        applyFilters();
    });

    // Klik w tag na karcie ustawia filtr tagu bez przeładowania.
    document.addEventListener('click', (event) => {
        const link = event.target.closest('.task-tags a');
        if (link) {
            event.preventDefault();
            form.elements.tag.value = new URL(link.href).searchParams.get('tag') ?? '';
            applyFilters();
        }
    });
    syncToolbar();
};

export const initTasks = () => {
    if (!live()) {
        return;
    }
    initDelegatedEvents();
    initFilters();
    initDragAndDrop();
    window.setInterval(() => refresh(), REFRESH_INTERVAL_MS);
    document.addEventListener('visibilitychange', () => refresh());
    window.addEventListener('focus', () => refresh());
};
