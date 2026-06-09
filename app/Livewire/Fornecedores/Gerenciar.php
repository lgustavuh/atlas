<?php

declare(strict_types=1);

namespace App\Livewire\Fornecedores;

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\Fornecedor;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Gestão de Fornecedores.
 *
 * Pessoa física OU jurídica. CNPJ/CPF validado com dígito verificador.
 */
#[Layout('layouts.app')]
#[Title('Fornecedores')]
class Gerenciar extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'tipo')]
    public string $filterTipo = '';

    #[Url(as: 'h')]
    public string $filterHomologado = '';

    // Modal
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    // Form - identificação
    public string $tipo_pessoa = 'juridica';
    public string $razao_social = '';
    public string $nome_fantasia = '';
    public string $cnpj_cpf = '';
    public string $inscricao_estadual = '';
    public string $inscricao_municipal = '';

    // Contato
    public string $telefone = '';
    public string $celular = '';
    public string $email = '';
    public string $site = '';
    public string $contato_nome = '';
    public string $contato_cargo = '';

    // Endereço
    public string $cep = '';
    public string $logradouro = '';
    public string $numero = '';
    public string $complemento = '';
    public string $bairro = '';
    public ?int $cidade_id = null;
    public ?int $estadoFiltro = null;

    // Dados bancários (conforme schema: sem banco_nome/tipo_conta)
    public string $banco_codigo = '';
    public string $banco_agencia = '';
    public string $banco_conta = '';
    public string $pix_chave = '';

    // Outros
    public bool $homologado = false;
    public ?int $avaliacao = null;
    public string $observacoes = '';

    // Confirmação
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Fornecedor::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterTipo', 'filterHomologado'])) {
            $this->resetPage();
        }
    }

    public function updatedEstadoFiltro(): void
    {
        $this->cidade_id = null;
    }

    /**
     * Aplica máscara quando o usuário sai do campo CNPJ/CPF.
     */
    public function updatedCnpjCpf(): void
    {
        $limpo = preg_replace('/[^0-9]/', '', $this->cnpj_cpf) ?? '';
        if (strlen($limpo) === 11) {
            $this->cnpj_cpf = Cpf::formatar($limpo);
            $this->tipo_pessoa = 'fisica';
        } elseif (strlen($limpo) === 14) {
            $this->cnpj_cpf = Cnpj::formatar($limpo);
            $this->tipo_pessoa = 'juridica';
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', Fornecedor::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $f = Fornecedor::with('cidade')->findOrFail($id);
        $this->authorize('update', $f);

        $this->editingId = $f->id;
        $this->tipo_pessoa = $f->tipo_pessoa;
        $this->razao_social = $f->razao_social;
        $this->nome_fantasia = (string) $f->nome_fantasia;
        // Formata o CNPJ/CPF para exibição
        $this->cnpj_cpf = $f->tipo_pessoa === 'fisica'
            ? Cpf::formatar($f->cnpj_cpf ?? '')
            : Cnpj::formatar($f->cnpj_cpf ?? '');
        $this->inscricao_estadual = (string) $f->inscricao_estadual;
        $this->inscricao_municipal = (string) $f->inscricao_municipal;
        $this->telefone = (string) $f->telefone;
        $this->celular = (string) $f->celular;
        $this->email = (string) $f->email;
        $this->site = (string) $f->site;
        $this->contato_nome = (string) $f->contato_nome;
        $this->contato_cargo = (string) $f->contato_cargo;
        $this->cep = (string) $f->cep;
        $this->logradouro = (string) $f->logradouro;
        $this->numero = (string) $f->numero;
        $this->complemento = (string) $f->complemento;
        $this->bairro = (string) $f->bairro;
        $this->cidade_id = $f->cidade_id;
        $this->estadoFiltro = $f->cidade?->estado_id;
        $this->banco_codigo = (string) $f->banco_codigo;
        $this->banco_agencia = (string) $f->banco_agencia;
        $this->banco_conta = (string) $f->banco_conta;
        $this->pix_chave = (string) $f->pix_chave;
        $this->homologado = $f->homologado;
        $this->avaliacao = $f->avaliacao;
        $this->observacoes = (string) $f->observacoes;

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
        // Normaliza CNPJ/CPF para apenas dígitos antes de validar
        // (banco armazena sem máscara via mutator, então unique tem que comparar sem máscara)
        $this->cnpj_cpf = preg_replace('/[^0-9]/', '', $this->cnpj_cpf) ?? '';

        // Regra do documento depende do tipo de pessoa
        $regraDocumento = $this->tipo_pessoa === 'fisica' ? new Cpf() : new Cnpj();

        $data = $this->validate([
            'tipo_pessoa' => ['required', Rule::in(['fisica', 'juridica'])],
            'razao_social' => ['required', 'string', 'min:3', 'max:200'],
            'nome_fantasia' => ['nullable', 'string', 'max:200'],
            'cnpj_cpf' => [
                'required',
                $regraDocumento,
                Rule::unique('fornecedores', 'cnpj_cpf')
                    ->ignore($this->editingId)
                    ->whereNull('deleted_at'),
            ],
            'inscricao_estadual' => ['nullable', 'string', 'max:30'],
            'inscricao_municipal' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'site' => ['nullable', 'string', 'max:200'],
            'contato_nome' => ['nullable', 'string', 'max:150'],
            'contato_cargo' => ['nullable', 'string', 'max:100'],
            'cep' => ['nullable', 'string', 'max:10'],
            'logradouro' => ['nullable', 'string', 'max:200'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'cidade_id' => ['nullable', 'integer', 'exists:cidades,id'],
            'banco_codigo' => ['nullable', 'string', 'max:5'],
            'banco_agencia' => ['nullable', 'string', 'max:10'],
            'banco_conta' => ['nullable', 'string', 'max:20'],
            'pix_chave' => ['nullable', 'string', 'max:100'],
            'homologado' => ['boolean'],
            'avaliacao' => ['nullable', 'integer', 'min:1', 'max:5'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
        ], messages: [
            'cnpj_cpf.unique' => 'Já existe um fornecedor com este documento.',
            'razao_social.min' => 'A razão social/nome deve ter pelo menos 3 caracteres.',
        ]);

        $payload = $this->prepararPayload($data);

        if ($this->editando) {
            $f = Fornecedor::findOrFail($this->editingId);
            $this->authorize('update', $f);
            $f->update($payload);
            $this->dispatch('toast', type: 'success', message: "Fornecedor {$f->nome_exibicao} atualizado.");
        } else {
            $payload['created_by'] = Auth::id();
            $f = Fornecedor::create($payload);
            $this->dispatch('toast', type: 'success', message: "Fornecedor {$f->nome_exibicao} cadastrado.");
        }

        $this->closeModal();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function prepararPayload(array $data): array
    {
        // Limpa strings vazias para null
        foreach ($data as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                $data[$key] = null;
            }
        }

        $data['updated_by'] = Auth::id();
        $data['homologado'] = $this->homologado;

        return $data;
    }

    public function confirmDelete(int $id): void
    {
        $f = Fornecedor::withCount('pedidosCompra')->findOrFail($id);
        $this->authorize('delete', $f);

        if ($f->pedidos_compra_count > 0) {
            $this->dispatch('toast', type: 'error',
                message: "Não é possível excluir: {$f->pedidos_compra_count} pedidos de compra estão vinculados.");
            return;
        }

        $this->deletingId = $id;
        $this->deletingName = $f->nome_exibicao;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $f = Fornecedor::findOrFail($this->deletingId);
        $this->authorize('delete', $f);

        if ($f->pedidosCompra()->count() > 0) {
            $this->dispatch('toast', type: 'error', message: 'Fornecedor com pedidos vinculados, não pode ser excluído.');
            return;
        }

        $f->update(['updated_by' => Auth::id()]);
        $f->delete();
        $this->dispatch('toast', type: 'success', message: "Fornecedor {$f->nome_exibicao} desativado.");
        $this->showDeleteModal = false;
    }

    public function toggleHomologacao(int $id): void
    {
        $f = Fornecedor::findOrFail($id);
        $this->authorize('update', $f);

        $f->update([
            'homologado' => !$f->homologado,
            'updated_by' => Auth::id(),
        ]);

        $msg = $f->homologado ? 'Fornecedor homologado.' : 'Homologação removida.';
        $this->dispatch('toast', type: 'success', message: $msg);
    }

    private function resetForm(): void
    {
        $this->reset([
            'razao_social', 'nome_fantasia', 'cnpj_cpf', 'inscricao_estadual',
            'inscricao_municipal', 'telefone', 'celular', 'email', 'site',
            'contato_nome', 'contato_cargo', 'cep', 'logradouro', 'numero', 'complemento',
            'bairro', 'cidade_id', 'estadoFiltro', 'banco_codigo',
            'banco_agencia', 'banco_conta', 'pix_chave',
            'avaliacao', 'observacoes', 'editingId', 'editando',
        ]);
        $this->tipo_pessoa = 'juridica';
        $this->homologado = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Fornecedor::query()
            ->with('cidade:id,nome,estado_id')
            ->withCount('pedidosCompra')
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterTipo !== '', fn (Builder $q) => $q->where('tipo_pessoa', $this->filterTipo))
            ->when($this->filterHomologado === 'sim', fn (Builder $q) => $q->where('homologado', true))
            ->when($this->filterHomologado === 'nao', fn (Builder $q) => $q->where('homologado', false))
            ->orderBy('razao_social');

        return view('livewire.fornecedores.gerenciar', [
            'fornecedores' => $query->paginate(20),
            'estados' => Estado::orderBy('nome')->get(['id', 'nome', 'uf']),
            'cidadesDisponiveis' => $this->estadoFiltro
                ? Cidade::where('estado_id', $this->estadoFiltro)->orderBy('nome')->get(['id', 'nome'])
                : collect(),
        ]);
    }
}
