<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Models\Colaborador;
use App\Models\Departamento;
use App\Models\User;
use App\Rules\SenhaForte;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * Gestão de usuários do sistema.
 *
 * REGRA DE NEGOCIO (v1.9):
 *   - Um usuário SEMPRE corresponde a um colaborador cadastrado.
 *   - Ao criar, o admin seleciona o colaborador e o sistema pré-preenche
 *     nome e email a partir dele.
 *   - O perfil padrao e sugerido pelo departamento do colaborador
 *     (RH -> gestor_rh, Compras -> aprovador_compras, etc.) mas o admin
 *     pode alterar livremente antes de salvar.
 *
 * Senhas:
 *   - Na criação, admin define uma senha temporária forte.
 *   - Edição NÃO mexe em senha — usuário troca pelo próprio fluxo.
 */
#[Layout('layouts.app')]
#[Title('Usuários')]
class UserList extends Component
{
    use WithPagination;

    /**
     * Mapeamento de palavras-chave em nomes de departamentos -> perfil padrao.
     * E busca case-insensitive + sem acento (lower + unaccent).
     *
     * Ordem importa: o primeiro match ganha. Por isso "almoxarif" vem antes
     * de algo mais generico que pudesse pegar "rifa".
     */
    private const MAPA_DEPARTAMENTO_PERFIL = [
        'recursos humanos' => 'gestor_rh',
        'rh' => 'gestor_rh',
        'gestao de pessoas' => 'gestor_rh',
        'departamento pessoal' => 'gestor_rh',
        'almoxarif' => 'almoxarife',
        'compra' => 'aprovador_compras',
        'suprimento' => 'aprovador_compras',
        'frota' => 'gestor_frota',
        'veicul' => 'gestor_frota',
        'transport' => 'gestor_frota',
    ];

    private const PERFIL_PADRAO = 'colaborador';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = 'todos';

    // Estados do modal
    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;

    // Form
    public ?int $colaborador_id = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public bool $active = true;
    /** @var array<int> IDs das roles selecionadas */
    public array $selectedRoles = [];
    public string $departamento_label = ''; // só pra exibir no modal, não vai pro banco

    // Modal de exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public ?string $deletingName = null;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Quando o admin seleciona um colaborador no modal de criacao,
     * preenche automaticamente nome/email e sugere o perfil
     * baseado no departamento dele.
     */
    public function updatedColaboradorId(?int $value): void
    {
        if (! $value || $this->editing) {
            return;
        }

        $colab = Colaborador::with('departamento:id,nome')
            ->find($value);

        if (! $colab) {
            return;
        }

        $this->name = $colab->nome;
        $this->email = (string) $colab->email;
        $this->departamento_label = $colab->departamento?->nome ?? '';

        // Sugere o perfil baseado no departamento
        $perfilSlug = $this->mapearDepartamentoParaPerfil($colab->departamento?->nome);
        $role = Role::where('name', $perfilSlug)->first();
        if ($role) {
            $this->selectedRoles = [$role->id];
        }
    }

    /**
     * Aplica o mapeamento departamento -> perfil.
     * Busca case-insensitive + ignorando acentos.
     */
    private function mapearDepartamentoParaPerfil(?string $nomeDepartamento): string
    {
        if (! $nomeDepartamento) {
            return self::PERFIL_PADRAO;
        }

        // Normaliza: lower + tira acentos
        $normalizado = $this->normalizar($nomeDepartamento);

        foreach (self::MAPA_DEPARTAMENTO_PERFIL as $palavra => $perfil) {
            if (str_contains($normalizado, $palavra)) {
                return $perfil;
            }
        }

        return self::PERFIL_PADRAO;
    }

    private function normalizar(string $texto): string
    {
        $sem_acento = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $texto);
        return $sem_acento ?: Str::lower($texto);
    }

    /**
     * Abre o modal para criar um novo usuário.
     */
    public function openCreate(): void
    {
        $this->authorize('create', User::class);

        $this->resetForm();
        $this->editing = false;
        $this->showModal = true;
    }

    /**
     * Abre o modal para editar um usuário existente.
     */
    public function openEdit(int $id): void
    {
        $user = User::with(['roles', 'colaborador:id,nome'])->findOrFail($id);
        $this->authorize('update', $user);

        $this->editingId = $user->id;
        $this->colaborador_id = $user->colaborador_id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->active = $user->active;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        $this->editing = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $rules = [
            // Colaborador obrigatorio so na CRIACAO
            // (usuarios existentes podem nao ter colaborador vinculado por legado)
            'colaborador_id' => $this->editing
                ? ['nullable', 'integer', 'exists:colaboradores,id']
                : [
                    'required',
                    'integer',
                    'exists:colaboradores,id',
                    // Nao permite criar 2 usuarios pro mesmo colaborador
                    'unique:users,colaborador_id',
                ],
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => [
                'required', 'email', 'max:255',
                $this->editing
                    ? 'unique:users,email,' . $this->editingId
                    : 'unique:users,email',
            ],
            'active' => ['boolean'],
            'selectedRoles' => ['array', 'min:1'],
            'selectedRoles.*' => ['integer', 'exists:roles,id'],
        ];

        // Senha só é obrigatória na criação
        if (! $this->editing) {
            $rules['password'] = ['required', 'string', new SenhaForte()];
        } elseif ($this->password !== '') {
            $rules['password'] = ['nullable', 'string', new SenhaForte()];
        }

        $data = $this->validate($rules, messages: [
            'colaborador_id.required' => 'É preciso selecionar um colaborador para criar o usuário.',
            'colaborador_id.unique' => 'Este colaborador já tem um usuário cadastrado no sistema.',
            'selectedRoles.min' => 'Selecione ao menos um perfil para o usuário.',
        ]);

        DB::transaction(function () use ($data) {
            if ($this->editing) {
                $user = User::findOrFail($this->editingId);
                $this->authorize('update', $user);

                $payload = [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'active' => $data['active'],
                    'updated_by' => Auth::id(),
                ];

                // colaborador_id so e atualizado se ainda nao tem vinculo
                // (evita admin "trocar dono" de uma conta sem querer)
                if (! $user->colaborador_id && ! empty($data['colaborador_id'])) {
                    $payload['colaborador_id'] = $data['colaborador_id'];
                }

                $user->fill($payload);

                if ($this->password !== '') {
                    $user->password = $this->password;
                }

                $user->save();
                $user->syncRoles(Role::whereIn('id', $this->selectedRoles)->get());

                $this->dispatch('toast', type: 'success', message: "Usuário {$user->name} atualizado.");
            } else {
                $this->authorize('create', User::class);

                $user = User::create([
                    'colaborador_id' => $data['colaborador_id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $this->password,
                    'active' => $data['active'],
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]);

                $user->syncRoles(Role::whereIn('id', $this->selectedRoles)->get());

                $this->dispatch('toast', type: 'success', message: "Usuário {$user->name} criado.");
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        if ($user->id === Auth::id()) {
            session()->flash('error', 'Você não pode excluir a sua própria conta.');
            return;
        }

        $this->deletingId = $user->id;
        $this->deletingName = $user->name;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $user = User::findOrFail($this->deletingId);
        $this->authorize('delete', $user);

        if ($user->id === Auth::id()) {
            session()->flash('error', 'Operação não permitida.');
            return;
        }

        $nome = $user->name;
        $user->update(['updated_by' => Auth::id()]);
        $user->delete(); // soft delete

        $this->showDeleteModal = false;
        $this->deletingId = null;

        $this->dispatch('toast', type: 'success', message: "Usuário {$nome} desativado.");
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        if ($user->id === Auth::id()) {
            session()->flash('error', 'Você não pode desativar a si mesmo.');
            return;
        }

        $user->update([
            'active' => ! $user->active,
            'updated_by' => Auth::id(),
        ]);

        $this->dispatch('toast', type: 'success', message: 'Status atualizado: '.($user->active ? 'Ativo' : 'Inativo'));
    }

    /**
     * Desbloqueia usuário com lockout por excesso de tentativas.
     */
    public function unlock(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'updated_by' => Auth::id(),
        ]);

        $this->dispatch('toast', type: 'success', message: "Usuário {$user->name} desbloqueado.");
    }

    private function resetForm(): void
    {
        $this->reset([
            'colaborador_id', 'name', 'email', 'password',
            'editingId', 'selectedRoles', 'departamento_label',
        ]);
        $this->active = true;
        $this->editing = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = User::query()
            ->with(['roles', 'colaborador:id,nome'])
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%' . Str::lower($this->search) . '%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->whereRaw('LOWER(name) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(email::text) LIKE ?', [$term]);
                });
            })
            ->when($this->filterStatus === 'ativos', fn (Builder $q) => $q->where('active', true))
            ->when($this->filterStatus === 'inativos', fn (Builder $q) => $q->where('active', false))
            ->when($this->filterStatus === 'bloqueados', fn (Builder $q) => $q->whereNotNull('locked_until')->where('locked_until', '>', now()))
            ->orderBy('name');

        // Colaboradores ATIVOS que ainda nao tem usuario (na criacao);
        // em edicao, soltamos a restricao pra incluir o ja-vinculado.
        $colaboradoresQuery = Colaborador::query()
            ->whereNull('data_demissao')
            ->orderBy('nome');

        if (! $this->editing) {
            $colaboradoresQuery->whereDoesntHave('user');
        }

        return view('livewire.users.user-list', [
            'users' => $query->paginate(20),
            'roles' => Role::orderBy('display_name')->get(),
            'colaboradores' => $colaboradoresQuery->get(['id', 'nome', 'departamento_id']),
        ]);
    }
}
