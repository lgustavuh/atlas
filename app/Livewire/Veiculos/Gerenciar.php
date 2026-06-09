<?php

declare(strict_types=1);

namespace App\Livewire\Veiculos;

use App\Exports\VeiculosExport;
use App\Livewire\Concerns\ExportaExcel;
use App\Models\Colaborador;
use App\Models\Veiculo;
use App\Rules\Placa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Gestão de Veículos da frota.
 */
#[Layout('layouts.app')]
#[Title('Veículos')]
class Gerenciar extends Component
{
    use ExportaExcel;
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'cat')]
    public string $filterCategoria = '';

    // Filtros de vencimento (item 3)
    // Valores: '' = sem filtro, 'licenc_vencendo', 'licenc_vencido',
    //          'seguro_vencendo', 'seguro_vencido', 'qualquer_vencendo'
    #[Url(as: 'venc')]
    public string $filterVencimento = '';

    // Numero de dias para considerar "vencendo em breve" (padrao 30, ajustavel)
    #[Url(as: 'dias')]
    public int $filterDias = 30;

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form — identificação
    public string $placa = '';
    public string $renavam = '';
    public string $chassi = '';
    public string $marca = '';
    public string $modelo = '';
    public ?int $ano_fabricacao = null;
    public ?int $ano_modelo = null;
    public string $cor = '';
    public string $combustivel = '';
    public string $categoria = '';
    public int $km_atual = 0;

    // Aquisição
    public ?string $data_aquisicao = null;
    public ?float $valor_aquisicao = null;

    // Status e operação
    public string $status = 'disponivel';
    public ?int $responsavel_id = null;

    // Documentação
    public ?string $licenciamento_vencimento = null;
    public ?string $seguro_vencimento = null;
    public string $seguradora = '';
    public string $apolice = '';

    // Documento PDF do veiculo (CRLV)
    public $documento = null; // upload novo (Livewire TemporaryUploadedFile)
    public ?string $documento_path_atual = null; // ja salvo no banco
    public ?string $documento_nome_atual = null;

    public string $observacoes = '';

    // Exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Veiculo::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterStatus', 'filterCategoria', 'filterVencimento', 'filterDias'])) {
            $this->resetPage();
        }
    }

    /**
     * Atalhos rapidos pra filtros de vencimento.
     */
    public function filtrarLicenciamentoVencendo(): void
    {
        $this->filterVencimento = 'licenc_vencendo';
        $this->filterDias = 30;
        $this->resetPage();
    }

    public function filtrarSeguroVencendo(): void
    {
        $this->filterVencimento = 'seguro_vencendo';
        $this->filterDias = 30;
        $this->resetPage();
    }

    public function filtrarQualquerVencendo(): void
    {
        $this->filterVencimento = 'qualquer_vencendo';
        $this->filterDias = 30;
        $this->resetPage();
    }

    public function filtrarVencidos(): void
    {
        $this->filterVencimento = 'qualquer_vencido';
        $this->resetPage();
    }

    public function limparFiltros(): void
    {
        $this->reset(['search', 'filterStatus', 'filterCategoria', 'filterVencimento']);
        $this->filterDias = 30;
        $this->resetPage();
    }

    public function updatedPlaca(): void
    {
        // Auto-formata a placa quando o usuário sai do campo
        if (strlen(Placa::limpar($this->placa)) === 7) {
            $this->placa = Placa::formatar($this->placa);
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Veiculo::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $v = Veiculo::findOrFail($id);
        $this->authorize('update', $v);

        $this->editingId = $v->id;
        $this->placa = Placa::formatar($v->placa ?? '');
        $this->renavam = (string) $v->renavam;
        $this->chassi = (string) $v->chassi;
        $this->marca = $v->marca;
        $this->modelo = $v->modelo;
        $this->ano_fabricacao = $v->ano_fabricacao;
        $this->ano_modelo = $v->ano_modelo;
        $this->cor = (string) $v->cor;
        $this->combustivel = (string) $v->combustivel;
        $this->categoria = (string) $v->categoria;
        $this->km_atual = $v->km_atual ?? 0;
        $this->data_aquisicao = $v->data_aquisicao?->toDateString();
        $this->valor_aquisicao = $v->valor_aquisicao !== null ? (float) $v->valor_aquisicao : null;
        $this->status = $v->status;
        $this->responsavel_id = $v->responsavel_id;
        $this->licenciamento_vencimento = $v->licenciamento_vencimento?->toDateString();
        $this->seguro_vencimento = $v->seguro_vencimento?->toDateString();
        $this->seguradora = (string) $v->seguradora;
        $this->apolice = (string) $v->apolice;
        $this->observacoes = (string) $v->observacoes;

        // Documento ja salvo
        $this->documento_path_atual = $v->documento_path;
        $this->documento_nome_atual = $v->documento_nome_original;
        $this->documento = null;

        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        // Normaliza placa para só letras/dígitos antes de validar (compat. unique)
        $this->placa = Placa::limpar($this->placa);

        $anoAtual = (int) now()->year;

        $data = $this->validate([
            'placa' => [
                'required',
                new Placa(),
                Rule::unique('veiculos', 'placa')
                    ->ignore($this->editingId)
                    ->whereNull('deleted_at'),
            ],
            'renavam' => ['nullable', 'string', 'max:20'],
            'chassi' => ['nullable', 'string', 'min:17', 'max:25'],
            'marca' => ['required', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:100'],
            'ano_fabricacao' => ['nullable', 'integer', 'min:1950', 'max:' . ($anoAtual + 1)],
            'ano_modelo' => ['nullable', 'integer', 'min:1950', 'max:' . ($anoAtual + 2)],
            'cor' => ['nullable', 'string', 'max:30'],
            'combustivel' => ['nullable', Rule::in(['gasolina', 'etanol', 'flex', 'diesel', 'eletrico', 'hibrido', 'gnv'])],
            'categoria' => ['nullable', Rule::in(['passeio', 'utilitario', 'caminhao', 'moto', 'onibus', 'maquina_pesada', 'outro'])],
            'km_atual' => ['required', 'integer', 'min:0'],
            'data_aquisicao' => ['nullable', 'date'],
            'valor_aquisicao' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['disponivel', 'em_uso', 'em_manutencao', 'inativo', 'vendido'])],
            'responsavel_id' => ['nullable', 'integer', 'exists:colaboradores,id'],
            'licenciamento_vencimento' => ['nullable', 'date'],
            'seguro_vencimento' => ['nullable', 'date'],
            'seguradora' => ['nullable', 'string', 'max:100'],
            'apolice' => ['nullable', 'string', 'max:50'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            // Documento: PDF, max 5MB. mimes + mimetypes em paralelo
            // (correcao de seguranca v1.1 - mime real e checado)
            'documento' => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:5120'],
        ], messages: [
            'placa.unique' => 'Já existe um veículo com esta placa.',
            'chassi.min' => 'O chassi deve ter 17 caracteres.',
            'documento.mimes' => 'O documento precisa ser um PDF.',
            'documento.mimetypes' => 'O arquivo enviado não é um PDF válido.',
            'documento.max' => 'O documento não pode passar de 5MB.',
        ], attributes: [
            'placa' => 'placa',
            'responsavel_id' => 'responsável',
        ]);

        // String vazia → null em campos opcionais (regra B6 do BUGS-CORRIGIDOS)
        $payload = [];
        foreach ($data as $k => $v) {
            if ($k === 'documento') {
                continue; // upload e tratado a parte abaixo
            }
            $payload[$k] = is_string($v) && trim($v) === '' ? null : $v;
        }
        $payload['updated_by'] = Auth::id();

        // Upload do documento (CRLV) — segue mesma estrategia dos atestados:
        // nome do arquivo no disco e o hash SHA-256 (dedupe + nao adivinhavel)
        if ($this->documento) {
            $conteudo = file_get_contents($this->documento->getRealPath());
            $hash = hash('sha256', $conteudo);
            $extensao = 'pdf';
            $pathRelativo = "veiculos/documentos/{$hash}.{$extensao}";

            Storage::disk('local')->put($pathRelativo, $conteudo);

            $payload['documento_path'] = $pathRelativo;
            $payload['documento_hash'] = $hash;
            $payload['documento_nome_original'] = Str::limit(
                $this->documento->getClientOriginalName(),
                250,
                ''
            );
        }

        if ($this->editando) {
            $veiculo = Veiculo::findOrFail($this->editingId);
            $this->authorize('update', $veiculo);
            $veiculo->update($payload);
            $this->dispatch('toast', type: 'success', message: "Veículo {$veiculo->placa_formatada} atualizado.");
        } else {
            $payload['created_by'] = Auth::id();
            $veiculo = Veiculo::create($payload);
            $this->dispatch('toast', type: 'success', message: "Veículo {$veiculo->placa_formatada} cadastrado.");
        }

        $this->closeModal();
    }

    /**
     * Remove o documento atual do veiculo (mantem o registro, so anula o path).
     */
    public function removerDocumento(): void
    {
        if (! $this->editando || ! $this->editingId) {
            return;
        }

        $v = Veiculo::findOrFail($this->editingId);
        $this->authorize('update', $v);

        if ($v->documento_path && Storage::disk('local')->exists($v->documento_path)) {
            Storage::disk('local')->delete($v->documento_path);
        }

        $v->update([
            'documento_path' => null,
            'documento_hash' => null,
            'documento_nome_original' => null,
            'updated_by' => Auth::id(),
        ]);

        $this->documento_path_atual = null;
        $this->documento_nome_atual = null;
        $this->dispatch('toast', type: 'success', message: 'Documento removido.');
    }

    public function confirmDelete(int $id): void
    {
        $v = Veiculo::withCount('manutencoes')->findOrFail($id);
        $this->authorize('delete', $v);

        $this->deletingId = $id;
        $this->deletingName = $v->identificacao;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $v = Veiculo::findOrFail($this->deletingId);
        $this->authorize('delete', $v);
        $v->update(['updated_by' => Auth::id()]);
        $v->delete();
        $this->dispatch('toast', type: 'success', message: "Veículo {$v->placa_formatada} desativado.");
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'placa', 'renavam', 'chassi', 'marca', 'modelo',
            'ano_fabricacao', 'ano_modelo', 'cor', 'combustivel', 'categoria',
            'data_aquisicao', 'valor_aquisicao', 'responsavel_id',
            'licenciamento_vencimento', 'seguro_vencimento', 'seguradora', 'apolice',
            'observacoes', 'editingId', 'editando',
            'documento', 'documento_path_atual', 'documento_nome_atual',
        ]);
        $this->km_atual = 0;
        $this->status = 'disponivel';
        $this->resetErrorBag();
    }

    /**
     * @return \Closure(Builder): Builder
     */
    protected function aplicarFiltros(): \Closure
    {
        return function (Builder $q): Builder {
            // Sanitiza filterDias (entre 1 e 365)
            $dias = max(1, min(365, $this->filterDias ?: 30));
            $hoje = now()->toDateString();
            $limite = now()->addDays($dias)->toDateString();

            return $q
                ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
                ->when($this->filterStatus !== '', fn (Builder $q) => $q->where('status', $this->filterStatus))
                ->when($this->filterCategoria !== '', fn (Builder $q) => $q->where('categoria', $this->filterCategoria))
                ->when($this->filterVencimento === 'licenc_vencendo', fn (Builder $q) => $q
                    ->whereNotNull('licenciamento_vencimento')
                    ->whereBetween('licenciamento_vencimento', [$hoje, $limite]))
                ->when($this->filterVencimento === 'licenc_vencido', fn (Builder $q) => $q
                    ->whereNotNull('licenciamento_vencimento')
                    ->where('licenciamento_vencimento', '<', $hoje))
                ->when($this->filterVencimento === 'seguro_vencendo', fn (Builder $q) => $q
                    ->whereNotNull('seguro_vencimento')
                    ->whereBetween('seguro_vencimento', [$hoje, $limite]))
                ->when($this->filterVencimento === 'seguro_vencido', fn (Builder $q) => $q
                    ->whereNotNull('seguro_vencimento')
                    ->where('seguro_vencimento', '<', $hoje))
                ->when($this->filterVencimento === 'qualquer_vencendo', fn (Builder $q) => $q
                    ->where(function (Builder $sub) use ($hoje, $limite) {
                        $sub->whereBetween('licenciamento_vencimento', [$hoje, $limite])
                            ->orWhereBetween('seguro_vencimento', [$hoje, $limite]);
                    }))
                ->when($this->filterVencimento === 'qualquer_vencido', fn (Builder $q) => $q
                    ->where(function (Builder $sub) use ($hoje) {
                        $sub->where('licenciamento_vencimento', '<', $hoje)
                            ->orWhere('seguro_vencimento', '<', $hoje);
                    }));
        };
    }

    public function exportar()
    {
        $this->authorize('viewAny', Veiculo::class);
        return $this->fazerDownload(new VeiculosExport($this->aplicarFiltros()), 'veiculos');
    }

    public function render()
    {
        $query = Veiculo::query()
            ->with('responsavel:id,nome')
            ->withCount('manutencoes')
            ->orderBy('marca')
            ->orderBy('modelo');

        $query = ($this->aplicarFiltros())($query);

        return view('livewire.veiculos.gerenciar', [
            'veiculos' => $query->paginate(15),
            'colaboradores' => Colaborador::orderBy('nome')->get(['id', 'nome']),
            'stats' => [
                'total' => Veiculo::count(),
                'em_manutencao' => Veiculo::comStatus(Veiculo::STATUS_EM_MANUTENCAO)->count(),
                'licenc_proximo' => Veiculo::licenciamentoProximo()->count(),
                'seguro_proximo' => Veiculo::seguroProximo()->count(),
            ],
        ]);
    }
}
