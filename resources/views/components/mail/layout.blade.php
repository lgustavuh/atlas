@props([
    'titulo' => 'Notificação',
    'tipoNotificacao' => null,
    'linkAcesso' => null,
])

@php
    // O slot já é HTML renderizado pelo Blade (filhos escapados na compilação);
    // marcamos explicitamente como HtmlString para o layout entender que pode renderizar.
    $conteudoSafe = new \Illuminate\Support\HtmlString((string) ($slot ?? ''));
@endphp

@include('emails.layout', [
    'titulo' => $titulo,
    'tipoNotificacao' => $tipoNotificacao,
    'linkAcesso' => $linkAcesso,
    'conteudo' => $conteudoSafe,
])
