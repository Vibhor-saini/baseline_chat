import './bootstrap';
import './chat';

// Suppress the browser's "Changes you made may not be saved" dialog.
// In this chat app all state is server-persisted — there is no local-only
// data that would be lost on navigation, so the Livewire dirty-model
// beforeunload warning is misleading and should never show.
window.addEventListener('beforeunload', (e) => {
    e.stopImmediatePropagation();
}, true); // capture phase — runs before Livewire's own listener