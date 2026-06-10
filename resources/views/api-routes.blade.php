<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Route Explorer | {{ config('app.name', 'HR Portal') }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (CDN for simple, single-page Blade tool) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js (for micro-interactions and copy functionality) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    }
                }
            }
        }
    </script>
    
    <style>
        .glass-panel {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glow-effect {
            box-shadow: 0 0 25px -5px rgba(99, 102, 241, 0.15);
        }
    </style>
</head>
<body class="h-full font-sans antialiased overflow-hidden flex flex-col">

    <!-- Glowing Background blobs -->
    <div class="fixed top-0 left-1/4 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>
    <div class="fixed bottom-0 right-1/4 w-96 h-96 bg-emerald-600/10 rounded-full blur-3xl -z-10 pointer-events-none"></div>

    <div class="flex-none border-b border-slate-800 bg-slate-900/50 backdrop-blur-md px-6 py-4">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-500/10 rounded-xl flex items-center justify-center border border-indigo-500/20">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        API Route Explorer
                        <span class="text-[10px] uppercase font-mono font-medium tracking-widest bg-indigo-500/15 text-indigo-400 px-2 py-0.5 rounded-full border border-indigo-500/25">Laravel</span>
                    </h1>
                    <p class="text-xs text-slate-400">Interactive directory of registered API endpoints</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2 text-xs">
                <span class="text-slate-400">Base URL:</span>
                <code class="px-2.5 py-1 rounded bg-slate-800 border border-slate-700 font-mono text-indigo-300 font-semibold select-all">{{ $baseUrl }}</code>
            </div>
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="flex-1 max-w-7xl w-full mx-auto p-6 flex flex-col overflow-hidden">
        
        <!-- Filters Bar -->
        <form method="GET" action="{{ url('/api-routes') }}" class="flex-none grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Search -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4.5 w-4.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}"
                    placeholder="Search by URI (e.g. assets)..." 
                    class="block w-full pl-10 pr-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-sm placeholder-slate-500 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition duration-150"
                />
            </div>

            <!-- HTTP Method Filter -->
            <div>
                <select 
                    name="method" 
                    class="block w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition duration-150"
                >
                    <option value="">All Methods</option>
                    <option value="GET" {{ $methodFilter === 'GET' ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ $methodFilter === 'POST' ? 'selected' : '' }}>POST</option>
                    <option value="PUT" {{ $methodFilter === 'PUT' ? 'selected' : '' }}>PUT</option>
                    <option value="PATCH" {{ $methodFilter === 'PATCH' ? 'selected' : '' }}>PATCH</option>
                    <option value="DELETE" {{ $methodFilter === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                </select>
            </div>

            <!-- Auth Filter -->
            <div>
                <select 
                    name="auth" 
                    class="block w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500 transition duration-150"
                >
                    <option value="">All Auth States</option>
                    <option value="yes" {{ $authFilter === 'yes' ? 'selected' : '' }}>Auth Required (Sanctum)</option>
                    <option value="no" {{ $authFilter === 'no' ? 'selected' : '' }}>Public (Guest)</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex gap-2">
                <button 
                    type="submit" 
                    class="flex-1 bg-indigo-600 hover:bg-indigo-500 text-white font-medium px-4 py-2 rounded-xl text-sm transition shadow-lg shadow-indigo-600/10 flex items-center justify-center gap-2"
                >
                    Apply Filters
                </button>
                @if($search || $methodFilter || $authFilter)
                    <a 
                        href="{{ url('/api-routes') }}" 
                        class="bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 font-medium px-3 py-2 rounded-xl text-sm transition flex items-center justify-center"
                        title="Clear Filters"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3" />
                        </svg>
                    </a>
                @endif
            </div>
        </form>

        <!-- Metrics & Counter -->
        <div class="flex-none flex items-center justify-between mb-4 px-1">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                Matching Routes: <span class="text-indigo-400 font-mono text-sm ml-1">{{ $totalRoutes }}</span>
            </span>
        </div>

        <!-- Routes Table Container (Scrollable) -->
        <div class="flex-1 overflow-y-auto glass-panel rounded-2xl glow-effect overflow-hidden border border-slate-800">
            <table class="w-full text-left border-collapse table-fixed">
                <thead>
                    <tr class="bg-slate-900/80 border-b border-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-400">
                        <th class="w-28 py-3.5 px-6">Method</th>
                        <th class="py-3.5 px-4">URI / Endpoint</th>
                        <th class="w-40 py-3.5 px-4">Auth Requirement</th>
                        <th class="w-56 py-3.5 px-4">Route Name</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono text-sm">
                    @forelse($routes as $route)
                        @php
                            $methods = array_filter(explode('|', $route['method']), fn($m) => $m !== 'HEAD');
                            $cleanMethod = reset($methods);
                            
                            $badgeClass = match($cleanMethod) {
                                'GET' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'POST' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                'PUT', 'PATCH' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                'DELETE' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
                                default => 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                            };
                        @endphp
                        <tr class="hover:bg-slate-900/30 transition-all duration-150 group">
                            <!-- Method Badge -->
                            <td class="py-3.5 px-6 whitespace-nowrap align-middle">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border {{ $badgeClass }}">
                                    {{ $cleanMethod }}
                                </span>
                            </td>
                            
                            <!-- Endpoint URI -->
                            <td class="py-3.5 px-4 text-slate-100 font-medium align-middle select-all flex items-center gap-2 group-hover:text-indigo-400 transition-colors">
                                <span class="truncate">{{ $route['uri'] }}</span>
                                <button 
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText('{{ $route['uri'] }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-slate-800 text-slate-400 hover:text-white transition duration-150"
                                    title="Copy URI"
                                >
                                    <!-- Copy Icon / Check Icon -->
                                    <template x-if="!copied">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                        </svg>
                                    </template>
                                    <template x-if="copied">
                                        <svg class="w-3.5 h-3.5 text-emerald-400 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </template>
                                </button>
                            </td>

                            <!-- Auth State -->
                            <td class="py-3.5 px-4 align-middle whitespace-nowrap">
                                @if($route['auth_required'])
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/25">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Sanctum
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                        </svg>
                                        Public
                                    </span>
                                @endif
                            </td>

                            <!-- Route Name -->
                            <td class="py-3.5 px-4 text-xs align-middle text-slate-400 truncate whitespace-nowrap">
                                {{ $route['name'] ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-500 font-sans">
                                <svg class="w-10 h-10 mx-auto text-slate-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="block text-sm font-medium">No matching API routes found</span>
                                <span class="block text-xs mt-1">Try relaxing your search terms or filters</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
    </div>

</body>
</html>
