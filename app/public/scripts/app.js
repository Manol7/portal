// Punkt wejścia: każdy moduł inicjalizuje się sam i bezpiecznie pomija strony, na których nie ma jego elementów.
import { initLiveToggle, initLiveTranslation } from './modules/live-translation.js';
import { initModals } from './modules/modal.js';
import { initPasswordToggle } from './modules/password-toggle.js';
import { initTasks } from './modules/tasks.js';
import { initToasts } from './modules/toast.js';
import { initTranslationAdmin } from './modules/translation-admin.js';
import { initUserAdmin } from './modules/user-admin.js';

initLiveTranslation();
initLiveToggle();
initToasts();
initModals();
initPasswordToggle();
initUserAdmin();
initTranslationAdmin();
initTasks();
