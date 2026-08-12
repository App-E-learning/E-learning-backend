@extends('admin.layout')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')
@section('page-subtitle', "Vue d'ensemble du référentiel pédagogique et de la banque d'exercices.")

@section('content')
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        @php
            $cards = [
                ['label' => 'Matières', 'value' => $stats['matieres'], 'route' => 'admin.matieres', 'icon' => '📚'],
                ['label' => 'Niveaux', 'value' => $stats['niveaux'], 'route' => 'admin.niveaux', 'icon' => '🎓'],
                ['label' => 'Séquences', 'value' => $stats['sequences'], 'route' => 'admin.sequences', 'icon' => '🗓️'],
                ['label' => 'Chapitres', 'value' => $stats['chapitres'], 'route' => 'admin.chapitres', 'icon' => '📖'],
                ['label' => 'Exercices', 'value' => $stats['exercices'], 'route' => 'admin.exercices', 'icon' => '📝'],
                ['label' => 'Brouillons à valider', 'value' => $stats['brouillons'], 'route' => 'admin.brouillons', 'icon' => '🕓'],
            ];
        @endphp
        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}"
               class="bg-white border border-slate-200 rounded-xl p-5 hover:border-primary/40 hover:shadow-sm transition">
                <div class="text-2xl mb-2">{{ $card['icon'] }}</div>
                <div class="text-2xl font-bold">{{ $card['value'] }}</div>
                <div class="text-sm text-slate-500">{{ $card['label'] }}</div>
            </a>
        @endforeach
    </div>

    @if ($stats['brouillons'] > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-center justify-between">
            <div>
                <p class="font-semibold text-amber-900">{{ $stats['brouillons'] }} exercice(s) importé(s) par OCR en attente de relecture</p>
                <p class="text-sm text-amber-700">Ils ne sont visibles par aucun élève tant qu'ils ne sont pas validés.</p>
            </div>
            <a href="{{ route('admin.brouillons') }}" class="bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold px-4 py-2 rounded-lg whitespace-nowrap">
                Relire maintenant
            </a>
        </div>
    @endif

    <div class="mt-8 flex gap-3">
        <a href="{{ route('admin.exercices.create') }}" class="bg-primary hover:bg-primary/90 text-white text-sm font-semibold px-4 py-2.5 rounded-lg">
            + Nouvel exercice (saisie manuelle)
        </a>
        <a href="{{ route('admin.import') }}" class="border border-slate-300 hover:bg-slate-100 text-sm font-semibold px-4 py-2.5 rounded-lg">
            📥 Importer depuis un scan (OCR)
        </a>
    </div>
@endsection