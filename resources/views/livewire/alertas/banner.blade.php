<div>
@if ($alertas->isNotEmpty())
    <div class="space-y-2 mb-4">
        @foreach ($alertas as $alerta)
            <div wire:key="banner-{{ $alerta->id }}"
                 @class([
                     'rounded-lg border p-4 flex items-start gap-3',
                     'bg-red-50 border-red-300 text-red-900' => $alerta->prioridade === 'critica',
                     'bg-yellow-50 border-yellow-300 text-yellow-900' => $alerta->prioridade === 'alta',
                     'bg-blue-50 border-blue-300 text-blue-900' => $alerta->prioridade === 'normal',
                     'bg-gray-50 border-gray-300 text-gray-900' => $alerta->prioridade === 'baixa',
                 ])>
                <div class="flex-shrink-0">
                    @if ($alerta->prioridade === 'critica')
                        <i class="ti ti-alert-octagon text-2xl" aria-hidden="true"></i>
                    @elseif ($alerta->prioridade === 'alta')
                        <i class="ti ti-alert-triangle text-2xl" aria-hidden="true"></i>
                    @else
                        <i class="ti ti-info-circle text-2xl" aria-hidden="true"></i>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <p class="font-semibold text-sm">{{ $alerta->titulo }}</p>
                        <span class="text-xs opacity-75">· {{ $alerta->prioridade_label }}</span>
                    </div>
                    <p class="mt-1 text-sm whitespace-pre-line">{{ $alerta->mensagem }}</p>
                </div>
                <button wire:click="descartar({{ $alerta->id }})"
                        class="flex-shrink-0 opacity-60 hover:opacity-100"
                        title="Marcar como visualizado">
                    <i class="ti ti-x text-lg" aria-hidden="true"></i>
                </button>
            </div>
        @endforeach
    </div>
@endif
</div>
