@extends('admin.layout')

@section('title', 'Brouillons')
@section('page-title', 'Brouillons à valider')
@section('page-subtitle', "Exercices importés par OCR, en attente de relecture. Invisibles pour les élèves tant qu'ils ne sont pas validés.")

@section('content')
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Énoncé (extrait OCR)</th>
                    <th class="text-left px-4 py-3">Chapitre</th>
                    <th class="text-left px-4 py-3">Type</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="rows" class="divide-y divide-slate-100"></tbody>
        </table>
        <p id="empty" class="hidden text-center text-slate-400 text-sm py-8">Aucun brouillon en attente — tout est à jour 🎉</p>
    </div>
@endsection

@push('scripts')
<script>
const TYPE_LABELS = { qcm: 'QCM', numerique: 'Numérique', texte_court: 'Texte court' };

async function load() {
    const data = await apiFetch('/api/admin/exercices/brouillons');
    const items = data.data || data;

    const rows = document.getElementById('rows');
    document.getElementById('empty').classList.toggle('hidden', items.length > 0);
    rows.innerHTML = items.map(ex => `
        <tr>
            <td class="px-4 py-3 max-w-md">
                <p class="line-clamp-2 text-slate-800">${escapeHtml(ex.enonce)}</p>
            </td>
            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${escapeHtml(ex.chapitre?.titre || '—')}</td>
            <td class="px-4 py-3">
                <span class="bg-slate-100 text-slate-600 text-xs font-semibold px-2 py-1 rounded-full">${TYPE_LABELS[ex.type] || ex.type}</span>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <a href="/admin/exercices/${ex.id}/modifier" class="text-primary text-xs font-semibold">Relire / compléter →</a>
            </td>
        </tr>
    `).join('');
}

load();
</script>
@endpush