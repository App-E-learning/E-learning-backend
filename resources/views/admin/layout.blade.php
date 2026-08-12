<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Back-office') — E-learning</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#3B5BFE', primaryDark: '#2C46D9' },
                },
            },
        };
    </script>
    <script src="{{ asset('admin.js') }}"></script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-r border-slate-200 flex flex-col">
            <div class="px-5 py-5 border-b border-slate-100">
                <p class="font-bold text-lg text-primary">E-learning</p>
                <p class="text-xs text-slate-500">Back-office</p>
            </div>
            <nav class="flex-1 px-3 py-4 space-y-1 text-sm">
                @php
                    $links = [
                        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => '📊'],
                        ['route' => 'admin.matieres', 'label' => 'Matières', 'icon' => '📚'],
                        ['route' => 'admin.niveaux', 'label' => 'Niveaux', 'icon' => '🎓'],
                        ['route' => 'admin.sequences', 'label' => 'Séquences', 'icon' => '🗓️'],
                        ['route' => 'admin.chapitres', 'label' => 'Chapitres', 'icon' => '📖'],
                        ['route' => 'admin.exercices', 'label' => 'Exercices', 'icon' => '📝'],
                        ['route' => 'admin.import', 'label' => 'Importer (OCR)', 'icon' => '📥'],
                        ['route' => 'admin.brouillons', 'label' => 'Brouillons', 'icon' => '🕓'],
                    ];
                @endphp
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg {{ request()->routeIs($link['route'].'*') ? 'bg-primary/10 text-primary font-semibold' : 'text-slate-600 hover:bg-slate-100' }}">
                        <span>{{ $link['icon'] }}</span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>
            <div class="px-3 py-4 border-t border-slate-100">
                <p class="px-3 text-xs text-slate-500 mb-2">{{ auth()->user()->name ?? '' }}</p>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-sm text-red-600 hover:bg-red-50">
                        ↩︎ Se déconnecter
                    </button>
                </form>
            </div>
        </aside>

        <!-- Contenu -->
        <main class="flex-1 p-8 max-w-6xl">
            <h1 class="text-2xl font-bold mb-1">@yield('page-title')</h1>
            @hasSection('page-subtitle')
                <p class="text-slate-500 mb-6">@yield('page-subtitle')</p>
            @else
                <div class="mb-6"></div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Toasts -->
    <div id="toast-container" class="fixed bottom-6 right-6 z-50 w-80"></div>

    @stack('scripts')
</body>
</html>