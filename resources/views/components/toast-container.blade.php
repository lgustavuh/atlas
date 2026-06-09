{{--
    Sistema de toast notifications.

    Como usar a partir de qualquer Livewire component:

        $this->dispatch('toast', type: 'success', message: 'Salvo!');
        $this->dispatch('toast', type: 'error', message: 'Erro ao salvar');

    Tipos: success, error, warning, info
    Duração padrão: 5s (customizável com `duration: 8000`)
--}}

<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, ...toast });
            setTimeout(() => this.remove(id), toast.duration || 5000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        styles(type) {
            return {
                success: 'bg-green-50 border-green-400 text-green-800',
                error:   'bg-red-50 border-red-400 text-red-800',
                warning: 'bg-yellow-50 border-yellow-400 text-yellow-800',
                info:    'bg-blue-50 border-blue-400 text-blue-800',
            }[type] || 'bg-gray-50 border-gray-400 text-gray-800';
        },
        icon(type) {
            return {
                success: 'circle-check',
                error:   'alert-circle',
                warning: 'alert-triangle',
                info:    'info-circle',
            }[type] || 'bell';
        },
        iconColor(type) {
            return {
                success: 'text-green-500',
                error:   'text-red-500',
                warning: 'text-yellow-500',
                info:    'text-blue-500',
            }[type] || 'text-gray-500';
        }
    }"
    @toast.window="add($event.detail)"
    class="fixed top-4 right-4 z-[60] flex flex-col items-end space-y-2 pointer-events-none"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :class="styles(toast.type)"
            class="pointer-events-auto max-w-sm w-full shadow-lg rounded-lg border-l-4 p-4"
            role="alert"
        >
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i :class="'ti ti-' + icon(toast.type) + ' ' + iconColor(toast.type) + ' text-xl'" aria-hidden="true"></i>
                </div>
                <div class="ml-3 flex-1 pt-0.5">
                    <p x-text="toast.title" x-show="toast.title" class="font-medium text-sm"></p>
                    <p x-text="toast.message" class="text-sm" :class="toast.title ? 'mt-1' : ''"></p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button @click="remove(toast.id)" class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none">
                        <i class="ti ti-x" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
