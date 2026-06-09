<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\Cpf;
use App\Rules\Pis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação centralizada para criação/edição de Colaborador.
 *
 * Por que usar FormRequest mesmo com Livewire?
 *   - Permite reuso (se um dia tiver API, mesma validação)
 *   - Mantém regras em um único lugar (sem duplicar create/update)
 *   - Facilita testes
 *
 * No Livewire o uso é via:
 *   $this->validate((new ColaboradorRequest())->rules());
 */
class ColaboradorRequest extends FormRequest
{
    /**
     * ID do colaborador sendo editado (null se for criação).
     */
    public ?int $colaboradorId = null;

    public function authorize(): bool
    {
        return true; // autorização vai pela Policy, não aqui
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // === Dados Pessoais ===
            'nome' => ['required', 'string', 'min:3', 'max:150'],
            'nome_social' => ['nullable', 'string', 'max:150'],
            'cpf' => [
                'required',
                new Cpf(),
                // Unique entre não-deletados, ignorando o próprio registro
                Rule::unique('colaboradores', 'cpf')
                    ->ignore($this->colaboradorId)
                    ->whereNull('deleted_at'),
            ],
            'rg' => ['nullable', 'string', 'max:20'],
            'rg_orgao_emissor' => ['nullable', 'string', 'max:20'],
            'rg_data_emissao' => ['nullable', 'date', 'before_or_equal:today'],
            'pis' => ['nullable', new Pis()],
            'ctps_numero' => ['nullable', 'string', 'max:20'],
            'ctps_serie' => ['nullable', 'string', 'max:10'],
            'ctps_uf' => ['nullable', 'string', 'size:2'],
            'titulo_eleitor' => ['nullable', 'string', 'max:20'],
            'cnh' => ['nullable', 'string', 'max:20'],
            'cnh_categoria' => ['nullable', 'string', 'max:5'],
            'cnh_validade' => ['nullable', 'date'],
            'reservista' => ['nullable', 'string', 'max:20'],

            'data_nascimento' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'sexo' => ['nullable', Rule::in(['M', 'F', 'O'])],
            'estado_civil' => ['nullable', Rule::in([
                'solteiro', 'casado', 'divorciado', 'viuvo', 'uniao_estavel', 'separado'
            ])],
            'nacionalidade' => ['nullable', 'string', 'max:50'],
            'nome_pai' => ['nullable', 'string', 'max:150'],
            'nome_mae' => ['nullable', 'string', 'max:150'],
            'escolaridade' => ['nullable', 'string', 'max:50'],
            'raca_cor' => ['nullable', 'string', 'max:30'],
            'tipo_sanguineo' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'doador_orgaos' => ['boolean'],
            'pcd' => ['boolean'],
            'pcd_descricao' => ['nullable', 'string', 'max:500', 'required_if:pcd,true'],

            'naturalidade_cidade_id' => ['nullable', 'integer', 'exists:cidades,id'],

            // === Contato ===
            'telefone_residencial' => ['nullable', 'string', 'max:20'],
            'telefone_celular' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'email_pessoal' => ['nullable', 'email', 'max:150'],

            // === Endereço ===
            'endereco_cep' => ['nullable', 'string', 'max:10'],
            'endereco_logradouro' => ['nullable', 'string', 'max:200'],
            'endereco_numero' => ['nullable', 'string', 'max:20'],
            'endereco_complemento' => ['nullable', 'string', 'max:100'],
            'endereco_bairro' => ['nullable', 'string', 'max:100'],
            'endereco_cidade_id' => ['nullable', 'integer', 'exists:cidades,id'],

            // === Dados Profissionais ===
            'matricula' => [
                'nullable', 'string', 'max:20',
                Rule::unique('colaboradores', 'matricula')
                    ->ignore($this->colaboradorId)
                    ->whereNull('deleted_at'),
            ],
            'cargo_id' => ['nullable', 'integer', 'exists:cargos,id'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
            'classificacao_id' => ['nullable', 'integer', 'exists:classificacoes,id'],
            'data_admissao' => ['nullable', 'date'],
            'data_demissao' => ['nullable', 'date', 'after_or_equal:data_admissao'],
            'regime_contratacao' => ['nullable', Rule::in([
                'clt', 'pj', 'estagio', 'temporario', 'autonomo', 'terceirizado'
            ])],
            'salario' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'jornada' => ['nullable', Rule::in([
                'integral', 'meio_periodo', 'turno_revezamento', 'home_office', 'hibrido'
            ])],
            'horario_entrada' => ['nullable', 'date_format:H:i'],
            'horario_saida' => ['nullable', 'date_format:H:i'],

            // === Bancários ===
            'banco_codigo' => ['nullable', 'string', 'max:5'],
            'banco_nome' => ['nullable', 'string', 'max:100'],
            'banco_agencia' => ['nullable', 'string', 'max:10'],
            'banco_conta' => ['nullable', 'string', 'max:20'],
            'banco_tipo_conta' => ['nullable', Rule::in(['corrente', 'poupanca', 'salario'])],
            'pix_chave' => ['nullable', 'string', 'max:100'],

            // === Foto (validação real de mime via finfo) ===
            'foto' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:5120', // 5 MB
                'dimensions:min_width=100,min_height=100,max_width=5000,max_height=5000',
            ],

            'observacoes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome',
            'cpf' => 'CPF',
            'pis' => 'PIS',
            'data_nascimento' => 'data de nascimento',
            'data_admissao' => 'data de admissão',
            'data_demissao' => 'data de demissão',
            'cargo_id' => 'cargo',
            'departamento_id' => 'departamento',
            'classificacao_id' => 'classificação',
            'naturalidade_cidade_id' => 'cidade de naturalidade',
            'endereco_cidade_id' => 'cidade',
            'foto' => 'foto',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cpf.unique' => 'Este CPF já está cadastrado para outro colaborador.',
            'matricula.unique' => 'Esta matrícula já está em uso.',
            'data_demissao.after_or_equal' => 'A data de demissão deve ser igual ou posterior à admissão.',
            'pcd_descricao.required_if' => 'Informe a deficiência ou condição quando marcar como PCD.',
            'foto.dimensions' => 'A foto deve ter entre 100x100 e 5000x5000 pixels.',
            'foto.max' => 'A foto não pode ser maior que 5MB.',
        ];
    }
}
