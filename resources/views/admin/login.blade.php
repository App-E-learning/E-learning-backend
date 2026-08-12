<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Back-office</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#3B5BFE' } } } };
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
        <div class="mb-6 text-center">
            <p class="text-xl font-bold text-primary">E-learning</p>
            <p class="text-sm text-slate-500">Back-office administrateur</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-red-50 text-red-700 text-sm rounded-lg px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Email ou téléphone</label>
                <input type="text" name="identifiant" value="{{ old('identifiant') }}" required autofocus
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Mot de passe</label>
                <input type="password" name="password" required
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
            </div>
            <button type="submit"
                    class="w-full bg-primary hover:bg-primary/90 text-white font-semibold text-sm rounded-lg py-2.5">
                Se connecter
            </button>
        </form>
    </div>
</body>
</html>