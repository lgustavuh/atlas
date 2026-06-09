import './bootstrap';
import persist from '@alpinejs/persist';

// Alpine.js já é carregado automaticamente pelo Livewire 3.
// Registramos o plugin 'persist' para que o estado da sidebar
// (collapsed/expanded) seja lembrado entre páginas.
//
// IMPORTANTE: usar flag para nao registrar 2x.
// Sem isso, em navegacoes via wire:navigate o evento alpine:init
// e disparado novamente e Object.defineProperty falha com
// "Cannot redefine property: $persist".
let persistRegistered = false;

document.addEventListener('alpine:init', () => {
    if (persistRegistered) {
        return;
    }
    persistRegistered = true;
    window.Alpine.plugin(persist);
});
