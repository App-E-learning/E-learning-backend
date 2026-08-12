@extends('admin.layout')

@section('title', 'Importer (OCR)')
@section('page-title', 'Importer un exercice scanné')
@section('page-subtitle', "L'image ou le PDF est passé à l'OCR, puis l'exercice est créé en brouillon — jamais visible par les élèves tant qu'il n'est pas relu et validé.")

@section('content')
    <form id="form" class="max-w-lg bg-white border border-slate-200 rounded-xl p-6 space-y-4" enctype="multipart/form-data">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Fichier (image ou PDF, 20 Mo max)</label>
            <input id="f-fichier" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Chapitre</label>
            <select id="f-chapitre" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm"></select>
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                <select id="f-type" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                    <option value="qcm">QCM</option>
                    <option value="numerique">Numérique</option>
                    <option value="texte_court">Texte court</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Difficulté</label>
                <input id="f-difficulte" type="number" min="1" max="5" value="3" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Année</label>
                <input id="f-annee" type="number" min="2000" value="{{ date('Y') }}" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input id="f-vision" type="checkbox">
            Forcer le passage par la vision IA (scan de mauvaise qualité, écriture manuscrite…)
        </label>

        <button type="submit" id="submit-btn" class="w-full bg-primary hover:bg-primary/90 text-white text-sm font-semibold py-2.5 rounded-lg">
            Importer
        </button>
    </form>

    <div id="result" class="hidden max-w-lg mt-5 bg-emerald-50 border border-emerald-200 rounded-xl p-5">
        <p class="font-semibold text-emerald-900 mb-1">Exercice importé en brouillon</p>
        <p class="text-sm text-emerald-700 mb-3">Relis l'énoncé extrait par l'OCR et complète le corrigé avant de le valider.</p>
        <a id="result-link" href="#" class="inline-block bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2 rounded-lg">
            Compléter le corrigé maintenant
        </a>
    </div>
@endsection

@push('scripts')
<script>
async function loadChapitres() {
    const data = await apiFetch('/api/chapitres');
    const chapitres = (data.data || data).map(c => ({ id: c.id, label: `${c.titre} — ${c.matiere?.nom || ''}` }));
    fillSelect(document.getElementById('f-chapitre'), chapitres, { placeholder: 'Choisir un chapitre' });
}

document.getElementById('form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const fichier = document.getElementById('f-fichier').files[0];
    if (!fichier) return;

    const formData = new FormData();
    formData.append('fichier', fichier);
    formData.append('chapitre_id', document.getElementById('f-chapitre').value);
    formData.append('type', document.getElementById('f-type').value);
    formData.append('difficulte', document.getElementById('f-difficulte').value);
    formData.append('annee_origine', document.getElementById('f-annee').value);
    formData.append('forcer_vision_ia', document.getElementById('f-vision').checked ? '1' : '0');

    btn.disabled = true;
    btn.textContent = 'Import en cours… (OCR)';

    try {
        const data = await apiFetch('/api/admin/exercices/import', { method: 'POST', body: formData });
        const ex = data.data || data;
        toast('Exercice importé en brouillon');
        document.getElementById('result').classList.remove('hidden');
        document.getElementById('result-link').href = `/admin/exercices/${ex.id}/modifier`;
        document.getElementById('form').reset();
        await loadChapitres();
    } catch (err) {
        toastFromError(err, "L'import a échoué.");
    } finally {
        btn.disabled = false;
        btn.textContent = 'Importer';
    }
});

loadChapitres();
</script>
@endpush