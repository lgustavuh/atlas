<?php

declare(strict_types=1);

namespace App\Livewire\Biblioteca;

use App\Models\BibliotecaArea;
use App\Models\BibliotecaDocumento;
use App\Services\DocumentoUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Biblioteca')]
class Gerenciar extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'area')]
    public ?int $filterAreaId = null;

    // Modal de documento
    public bool $showModal = false;
    public bool $editando = false;
    public ?int $editingId = null;

    public string $titulo = '';
    public string $descricao = '';
    public string $versao = '';
    /** @var list<int> */
    public array $selectedAreas = [];
    public $arquivo = null;

    // Modal de exclusão
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', BibliotecaDocumento::class);
    }

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'filterAreaId'])) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', BibliotecaDocumento::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $doc = BibliotecaDocumento::with('areas')->findOrFail($id);
        $this->authorize('update', $doc);

        $this->editingId = $doc->id;
        $this->titulo = $doc->titulo;
        $this->descricao = (string) $doc->descricao;
        $this->versao = (string) $doc->versao;
        $this->selectedAreas = $doc->areas->pluck('id')->all();
        $this->editando = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(DocumentoUploadService $uploader): void
    {
        $mimes = 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,txt';
        $mimetypes = implode(',', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'image/jpeg',
            'image/png',
            'application/zip',
            'application/x-zip-compressed',
            'text/plain',
        ]);

        $regrasArquivo = $this->editando
            ? ['nullable', 'file', 'max:20480', "mimes:{$mimes}", "mimetypes:{$mimetypes}"]
            : ['required', 'file', 'max:20480', "mimes:{$mimes}", "mimetypes:{$mimetypes}"];

        $this->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:200'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'versao' => ['nullable', 'string', 'max:20'],
            'selectedAreas' => ['array'],
            'selectedAreas.*' => ['integer', 'exists:biblioteca_areas,id'],
            'arquivo' => $regrasArquivo,
        ], messages: [
            'titulo.min' => 'O título deve ter pelo menos 3 caracteres.',
            'arquivo.max' => 'O arquivo deve ter no máximo 20 MB.',
            'arquivo.mimes' => 'Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, ZIP, TXT.',
        ]);

        try {
            if ($this->editando) {
                $doc = BibliotecaDocumento::findOrFail($this->editingId);
                $this->authorize('update', $doc);

                $payload = [
                    'titulo' => $this->titulo,
                    'descricao' => trim($this->descricao) === '' ? null : $this->descricao,
                    'versao' => trim($this->versao) === '' ? null : $this->versao,
                    'updated_by' => Auth::id(),
                ];

                // Se trocou o arquivo, substituir
                if ($this->arquivo) {
                    $arquivoAntigo = $doc->arquivo_path;
                    $info = $uploader->armazenar($this->arquivo, 'biblioteca');
                    $payload = array_merge($payload, $info);

                    // Remove antigo só se não for o mesmo hash (DocumentoUploadService é idempotente)
                    if ($arquivoAntigo !== $info['arquivo_path']) {
                        Storage::disk('local')->delete($arquivoAntigo);
                    }
                }

                $doc->update($payload);
                $doc->areas()->sync($this->selectedAreas);
                $this->dispatch('toast', type: 'success', message: 'Documento atualizado.');
            } else {
                $this->authorize('create', BibliotecaDocumento::class);
                $info = $uploader->armazenar($this->arquivo, 'biblioteca');

                $doc = BibliotecaDocumento::create(array_merge($info, [
                    'titulo' => $this->titulo,
                    'descricao' => trim($this->descricao) === '' ? null : $this->descricao,
                    'versao' => trim($this->versao) === '' ? null : $this->versao,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]));

                $doc->areas()->sync($this->selectedAreas);
                $this->dispatch('toast', type: 'success', message: 'Documento adicionado à biblioteca.');
            }
            $this->closeModal();
        } catch (\Throwable $e) {
            \Log::error('Erro ao salvar documento na biblioteca', ['erro' => $e->getMessage()]);
            if (app()->environment('testing', 'local')) {
                throw $e;
            }
            $this->dispatch('toast', type: 'error', message: 'Erro ao salvar o documento.');
        }
    }

    public function confirmDelete(int $id): void
    {
        $doc = BibliotecaDocumento::findOrFail($id);
        $this->authorize('delete', $doc);
        $this->deletingId = $id;
        $this->deletingName = $doc->titulo;
        $this->showDeleteModal = true;
    }

    public function delete(DocumentoUploadService $uploader): void
    {
        $doc = BibliotecaDocumento::findOrFail($this->deletingId);
        $this->authorize('delete', $doc);

        // Remove arquivo só se nenhum outro registro estiver usando o mesmo hash
        $uploader->remover($doc->arquivo_path, function (string $path) use ($doc): bool {
            return BibliotecaDocumento::where('arquivo_path', $path)
                ->where('id', '!=', $doc->id)
                ->exists();
        });

        $doc->update(['updated_by' => Auth::id()]);
        $doc->delete();
        $this->dispatch('toast', type: 'success', message: 'Documento excluído.');
        $this->showDeleteModal = false;
    }

    private function resetForm(): void
    {
        $this->reset(['titulo', 'descricao', 'versao', 'arquivo', 'editingId', 'editando']);
        $this->selectedAreas = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = BibliotecaDocumento::query()
            ->with('areas:id,nome')
            ->when($this->search !== '', fn (Builder $q) => $q->buscar($this->search))
            ->when($this->filterAreaId, fn (Builder $q) => $q->daArea($this->filterAreaId))
            ->orderBy('titulo');

        return view('livewire.biblioteca.gerenciar', [
            'documentos' => $query->paginate(15),
            'areas' => BibliotecaArea::orderBy('nome')->get(['id', 'nome']),
        ]);
    }
}
