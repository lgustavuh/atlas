<?php

declare(strict_types=1);

namespace App\Livewire\Colaboradores;

use App\Http\Requests\ColaboradorRequest;
use App\Models\Cargo;
use App\Models\Cidade;
use App\Models\Classificacao;
use App\Models\Colaborador;
use App\Models\Departamento;
use App\Models\Estado;
use App\Services\ColaboradorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulário de Colaborador (criar ou editar).
 *
 * Organização em 4 abas:
 *   1. Pessoal - dados identidade, documentos, naturalidade
 *   2. Contato e Endereço
 *   3. Profissional - cargo, departamento, salário, jornada
 *   4. Bancário - banco, agência, conta, PIX
 *
 * A validação roda no save() inteiro (não por aba) — o usuário pode preencher
 * em qualquer ordem e ver todos os erros de uma vez.
 */
#[Layout('layouts.app')]
class Formulario extends Component
{
    use WithFileUploads;

    public ?int $colaboradorId = null;
    public bool $editando = false;
    public string $abaAtiva = 'pessoal'; // pessoal | contato | profissional | bancario

    // === Pessoal ===
    public string $nome = '';
    public string $nome_social = '';
    public string $cpf = '';
    public string $rg = '';
    public string $rg_orgao_emissor = '';
    public ?string $rg_data_emissao = null;
    public string $pis = '';
    public string $ctps_numero = '';
    public string $ctps_serie = '';
    public string $ctps_uf = '';
    public string $titulo_eleitor = '';
    public string $cnh = '';
    public string $cnh_categoria = '';
    public ?string $cnh_validade = null;
    public string $reservista = '';
    public ?string $data_nascimento = null;
    public string $sexo = '';
    public string $estado_civil = '';
    public string $nacionalidade = 'Brasileira';
    public string $nome_pai = '';
    public string $nome_mae = '';
    public string $escolaridade = '';
    public string $raca_cor = '';
    public string $tipo_sanguineo = '';
    public bool $doador_orgaos = false;
    public bool $pcd = false;
    public string $pcd_descricao = '';
    public ?int $naturalidade_cidade_id = null;

    // === Contato ===
    public string $telefone_residencial = '';
    public string $telefone_celular = '';
    public string $email = '';
    public string $email_pessoal = '';

    // === Endereço ===
    public string $endereco_cep = '';
    public string $endereco_logradouro = '';
    public string $endereco_numero = '';
    public string $endereco_complemento = '';
    public string $endereco_bairro = '';
    public ?int $endereco_cidade_id = null;

    // === Profissional ===
    public string $matricula = '';
    public ?int $cargo_id = null;
    public ?int $departamento_id = null;
    public ?int $classificacao_id = null;
    public ?string $data_admissao = null;
    public ?string $data_demissao = null;
    public string $regime_contratacao = '';
    public ?float $salario = null;
    public string $jornada = '';
    public ?string $horario_entrada = null;
    public ?string $horario_saida = null;

    // === Bancário ===
    public string $banco_codigo = '';
    public string $banco_nome = '';
    public string $banco_agencia = '';
    public string $banco_conta = '';
    public string $banco_tipo_conta = '';
    public string $pix_chave = '';

    // === Outros ===
    public string $observacoes = '';

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $foto = null;
    public bool $removerFoto = false;

    // Cache para selects encadeados (cidade depende de estado)
    public ?int $estadoEnderecoFiltro = null;
    public ?int $estadoNaturalidadeFiltro = null;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $colaborador = Colaborador::with('enderecoResidencial.cidade.estado', 'naturalidadeCidade.estado')->findOrFail($id);
            $this->authorize('update', $colaborador);
            $this->carregarDados($colaborador);
        } else {
            $this->authorize('create', Colaborador::class);
        }
    }

    public function title(): string
    {
        return $this->editando ? 'Editar Colaborador' : 'Novo Colaborador';
    }

    private function carregarDados(Colaborador $colaborador): void
    {
        $this->editando = true;
        $this->colaboradorId = $colaborador->id;

        // Dados diretos do colaborador
        $atributos = [
            'nome', 'nome_social', 'rg', 'rg_orgao_emissor', 'pis',
            'ctps_numero', 'ctps_serie', 'ctps_uf', 'titulo_eleitor',
            'cnh', 'cnh_categoria', 'reservista', 'sexo', 'estado_civil',
            'nacionalidade', 'nome_pai', 'nome_mae', 'escolaridade',
            'raca_cor', 'tipo_sanguineo', 'doador_orgaos', 'pcd', 'pcd_descricao',
            'naturalidade_cidade_id',
            'telefone_residencial', 'telefone_celular', 'email', 'email_pessoal',
            'matricula', 'cargo_id', 'departamento_id', 'classificacao_id',
            'regime_contratacao', 'jornada',
            'banco_codigo', 'banco_nome', 'banco_agencia', 'banco_conta',
            'banco_tipo_conta', 'pix_chave', 'observacoes',
        ];

        foreach ($atributos as $attr) {
            if (isset($colaborador->{$attr})) {
                $this->{$attr} = (string) $colaborador->{$attr};
            }
        }

        // CPF formatado para exibição
        $this->cpf = \App\Rules\Cpf::formatar($colaborador->cpf ?? '');

        // Datas
        $this->data_nascimento = $colaborador->data_nascimento?->format('Y-m-d');
        $this->data_admissao = $colaborador->data_admissao?->format('Y-m-d');
        $this->data_demissao = $colaborador->data_demissao?->format('Y-m-d');
        $this->cnh_validade = $colaborador->cnh_validade?->format('Y-m-d');
        $this->rg_data_emissao = $colaborador->rg_data_emissao?->format('Y-m-d');

        $this->horario_entrada = $colaborador->horario_entrada?->format('H:i');
        $this->horario_saida = $colaborador->horario_saida?->format('H:i');

        $this->salario = $colaborador->salario ? (float) $colaborador->salario : null;

        // Endereço residencial
        if ($endereco = $colaborador->enderecoResidencial) {
            $this->endereco_cep = $endereco->cep ?? '';
            $this->endereco_logradouro = $endereco->logradouro ?? '';
            $this->endereco_numero = $endereco->numero ?? '';
            $this->endereco_complemento = $endereco->complemento ?? '';
            $this->endereco_bairro = $endereco->bairro ?? '';
            $this->endereco_cidade_id = $endereco->cidade_id;
            $this->estadoEnderecoFiltro = $endereco->cidade?->estado_id;
        }

        if ($colaborador->naturalidadeCidade) {
            $this->estadoNaturalidadeFiltro = $colaborador->naturalidadeCidade->estado_id;
        }
    }

    /**
     * Quando o usuário muda o estado, reseta a cidade.
     */
    public function updatedEstadoEnderecoFiltro(): void
    {
        $this->endereco_cidade_id = null;
    }

    public function updatedEstadoNaturalidadeFiltro(): void
    {
        $this->naturalidade_cidade_id = null;
    }

    /**
     * Quando o usuário muda o CPF, aplica a máscara para exibição.
     */
    public function updatedCpf(): void
    {
        $limpo = preg_replace('/[^0-9]/', '', $this->cpf);
        if (strlen($limpo) === 11) {
            $this->cpf = \App\Rules\Cpf::formatar($limpo);
        }
    }

    public function trocarAba(string $aba): void
    {
        $this->abaAtiva = $aba;
    }

    public function removerFotoAtual(): void
    {
        $this->removerFoto = true;
    }

    public function salvar(ColaboradorService $service): void
    {
        // Normaliza CPF e PIS removendo máscara antes de validar.
        // O banco armazena apenas dígitos, então Rule::unique precisa comparar valores limpos.
        $this->cpf = preg_replace('/[^0-9]/', '', $this->cpf) ?? '';
        if ($this->pis) {
            $this->pis = preg_replace('/[^0-9]/', '', $this->pis) ?? '';
        }

        $request = new ColaboradorRequest();
        $request->colaboradorId = $this->colaboradorId;

        $validados = $this->validate($request->rules(), $request->messages(), $request->attributes());

        try {
            if ($this->editando) {
                $colaborador = Colaborador::findOrFail($this->colaboradorId);
                $this->authorize('update', $colaborador);

                if ($this->removerFoto) {
                    $service->removerFoto($colaborador);
                }

                $colaborador = $service->atualizar($colaborador, $validados, $this->foto);

                session()->flash('success', "Colaborador {$colaborador->nome} atualizado.");
                $this->redirect(route('colaboradores.show', $colaborador->id), navigate: true);
            } else {
                $this->authorize('create', Colaborador::class);
                $colaborador = $service->criar($validados, $this->foto);

                session()->flash('success', "Colaborador {$colaborador->nome} cadastrado com sucesso.");
                $this->redirect(route('colaboradores.show', $colaborador->id), navigate: true);
            }
        } catch (\Throwable $e) {
            // Log do erro pro admin investigar
            \Log::error('Erro ao salvar colaborador', [
                'erro' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
            // Em testes, re-lança pra facilitar debug; em produção, apenas log+toast
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar. Verifique os dados e tente novamente.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function dadosFormulario(): array
    {
        return [
            'nome' => $this->nome,
            'nome_social' => $this->nome_social ?: null,
            'cpf' => $this->cpf,
            'rg' => $this->rg ?: null,
            'rg_orgao_emissor' => $this->rg_orgao_emissor ?: null,
            'rg_data_emissao' => $this->rg_data_emissao ?: null,
            'pis' => $this->pis ?: null,
            'ctps_numero' => $this->ctps_numero ?: null,
            'ctps_serie' => $this->ctps_serie ?: null,
            'ctps_uf' => $this->ctps_uf ?: null,
            'titulo_eleitor' => $this->titulo_eleitor ?: null,
            'cnh' => $this->cnh ?: null,
            'cnh_categoria' => $this->cnh_categoria ?: null,
            'cnh_validade' => $this->cnh_validade ?: null,
            'reservista' => $this->reservista ?: null,
            'data_nascimento' => $this->data_nascimento ?: null,
            'sexo' => $this->sexo ?: null,
            'estado_civil' => $this->estado_civil ?: null,
            'nacionalidade' => $this->nacionalidade ?: null,
            'nome_pai' => $this->nome_pai ?: null,
            'nome_mae' => $this->nome_mae ?: null,
            'escolaridade' => $this->escolaridade ?: null,
            'raca_cor' => $this->raca_cor ?: null,
            'tipo_sanguineo' => $this->tipo_sanguineo ?: null,
            'doador_orgaos' => $this->doador_orgaos,
            'pcd' => $this->pcd,
            'pcd_descricao' => $this->pcd_descricao ?: null,
            'naturalidade_cidade_id' => $this->naturalidade_cidade_id ?: null,
            'telefone_residencial' => $this->telefone_residencial ?: null,
            'telefone_celular' => $this->telefone_celular ?: null,
            'email' => $this->email ?: null,
            'email_pessoal' => $this->email_pessoal ?: null,
            'endereco_cep' => $this->endereco_cep ?: null,
            'endereco_logradouro' => $this->endereco_logradouro ?: null,
            'endereco_numero' => $this->endereco_numero ?: null,
            'endereco_complemento' => $this->endereco_complemento ?: null,
            'endereco_bairro' => $this->endereco_bairro ?: null,
            'endereco_cidade_id' => $this->endereco_cidade_id ?: null,
            'matricula' => $this->matricula ?: null,
            'cargo_id' => $this->cargo_id ?: null,
            'departamento_id' => $this->departamento_id ?: null,
            'classificacao_id' => $this->classificacao_id ?: null,
            'data_admissao' => $this->data_admissao ?: null,
            'data_demissao' => $this->data_demissao ?: null,
            'regime_contratacao' => $this->regime_contratacao ?: null,
            'salario' => $this->salario,
            'jornada' => $this->jornada ?: null,
            'horario_entrada' => $this->horario_entrada ?: null,
            'horario_saida' => $this->horario_saida ?: null,
            'banco_codigo' => $this->banco_codigo ?: null,
            'banco_nome' => $this->banco_nome ?: null,
            'banco_agencia' => $this->banco_agencia ?: null,
            'banco_conta' => $this->banco_conta ?: null,
            'banco_tipo_conta' => $this->banco_tipo_conta ?: null,
            'pix_chave' => $this->pix_chave ?: null,
            'observacoes' => $this->observacoes ?: null,
            'foto' => $this->foto,
        ];
    }

    public function render()
    {
        return view('livewire.colaboradores.formulario', [
            'cargos' => Cargo::orderBy('nome')->get(['id', 'nome']),
            'departamentos' => Departamento::orderBy('nome')->get(['id', 'nome']),
            'classificacoes' => Classificacao::orderBy('nome')->get(['id', 'nome']),
            'estados' => Estado::orderBy('nome')->get(['id', 'nome', 'uf']),
            'cidadesEndereco' => $this->estadoEnderecoFiltro
                ? Cidade::where('estado_id', $this->estadoEnderecoFiltro)->orderBy('nome')->get(['id', 'nome'])
                : collect(),
            'cidadesNaturalidade' => $this->estadoNaturalidadeFiltro
                ? Cidade::where('estado_id', $this->estadoNaturalidadeFiltro)->orderBy('nome')->get(['id', 'nome'])
                : collect(),
        ]);
    }
}
