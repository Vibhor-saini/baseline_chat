import './bootstrap';
console.log('APP JS LOADED');
// import Alpine from 'alpinejs';
import './chat';
// window.Alpine = Alpine;
window.Echo.channel('conversations')
    .listen('.conversation.updated', (e) => {

        console.log('====================================');
        console.log('REALTIME EVENT RECEIVED');
        console.log(e);
        console.log('====================================');

        window.Livewire.dispatch('conversation-updated', {
            conversation: e.conversation
        });
    });
// Alpine.start();
