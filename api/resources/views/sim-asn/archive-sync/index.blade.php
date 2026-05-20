<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="SIM-ASN Archive Sync Monitoring Dashboard">
    <title>SIM-ASN Archive Sync — Monitor</title>
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Instrument Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <meta http-equiv="refresh" content="30">
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100">

    <div class="min-h-full">

        {{-- Header --}}
        <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-sm font-semibold text-slate-900 dark:text-white">SIM-ASN Archive Sync</h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Monitoring Dashboard</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs text-slate-400 dark:text-slate-500">
                            Refreshes every 30s
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full
                            @if ($queueSize > 0)
                                bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800
                            @else
                                bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800
                            @endif
                        ">
                            <span class="w-1.5 h-1.5 rounded-full
                                @if ($queueSize > 0) bg-amber-500 animate-pulse @else bg-emerald-500 @endif
                            "></span>
                            Queue: {{ number_format($queueSize) }}
                        </span>
                    </div>
                </div>
            </div>
        </header>

        {{-- Main Content --}}
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">

                {{-- Total --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Total Records</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-white">
                        {{ number_format($stats['total']) }}
                    </div>
                    <div class="text-xs text-slate-400 mt-1">
                        {{ number_format($stats['unique_nips']) }} employees
                    </div>
                </div>

                {{-- Success --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Success</span>
                    </div>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ number_format($stats['success']) }}
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5 mt-3">
                        <div class="bg-emerald-500 h-1.5 rounded-full transition-all"
                             style="width: {{ $stats['success_rate'] }}%"></div>
                    </div>
                </div>

                {{-- Failed --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-red-500"></div>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Failed</span>
                    </div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ number_format($stats['failed']) }}
                    </div>
                    @if ($stats['total'] > 0)
                        <div class="text-xs text-slate-400 mt-1">
                            {{ round(($stats['failed'] / $stats['total']) * 100, 1) }}% of total
                        </div>
                    @endif
                </div>

                {{-- Pending --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-amber-500"></div>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Pending</span>
                    </div>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($stats['pending']) }}
                    </div>
                </div>

                {{-- Success Rate --}}
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Success Rate</div>
                    <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $stats['success_rate'] }}<span class="text-sm">%</span>
                    </div>
                    <div class="text-xs text-slate-400 mt-1">
                        @if ($stats['last_updated'])
                            Last update: {{ \Carbon\Carbon::parse($stats['last_updated'])->diffForHumans() }}
                        @else
                            No activity yet
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- Per Jenis Document Breakdown --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Breakdown by Document Type
                            </h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            Document Type
                                        </th>
                                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            Total
                                        </th>
                                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            Success
                                        </th>
                                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            Failed
                                        </th>
                                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            Pending
                                        </th>
                                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">
                                            Rate
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @forelse ($perJenis as $row)
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                            <td class="px-5 py-3">
                                                <span class="font-medium text-slate-800 dark:text-slate-200">
                                                    {{ $row['jenis_dokumen'] }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right text-slate-600 dark:text-slate-400">
                                                {{ number_format($row['total']) }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-emerald-600 dark:text-emerald-400 font-medium">
                                                {{ number_format($row['success']) }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">
                                                {{ number_format($row['failed']) }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-amber-600 dark:text-amber-400">
                                                {{ number_format($row['pending']) }}
                                            </td>
                                            <td class="px-5 py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <div class="w-12 bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                                                        <div class="bg-indigo-500 h-1.5 rounded-full"
                                                             style="width: {{ $row['rate'] }}%"></div>
                                                    </div>
                                                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400 w-10 text-right">
                                                        {{ $row['rate'] }}%
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400 dark:text-slate-500">
                                                No records yet. Run the sync command to start.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Recent Errors --}}
                <div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Recent Errors
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($recentErrors as $err)
                                <div class="px-5 py-3">
                                    <div class="flex items-start gap-2">
                                        <div class="mt-0.5 w-4 h-4 rounded-full bg-red-100 dark:bg-red-950 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-2.5 h-2.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-medium text-slate-700 dark:text-slate-300 truncate">
                                                {{ $err['jenis_dokumen'] }}
                                            </p>
                                            <p class="text-xs text-slate-400 dark:text-slate-500 truncate mt-0.5">
                                                NIP: {{ $err['nip'] }}
                                            </p>
                                            <p class="text-xs text-red-500 dark:text-red-400 mt-1 line-clamp-2">
                                                {{ $err['error_message'] ?? 'Unknown error' }}
                                            </p>
                                            <p class="text-xs text-slate-400 dark:text-slate-600 mt-1">
                                                {{ \Carbon\Carbon::parse($err['updated_at'])->diffForHumans() }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-5 py-8 text-center">
                                    <div class="w-8 h-8 rounded-full bg-emerald-50 dark:bg-emerald-950 mx-auto mb-2 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <p class="text-sm text-slate-400 dark:text-slate-500">No errors</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="mt-4 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800">
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Quick Actions</h2>
                        </div>
                        <div class="p-4 space-y-2">
                            <a href="{{ route('sim-asn.archive-sync.index') }}"
                               class="flex items-center justify-center gap-2 w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Refresh Now
                            </a>
                            <div class="text-xs text-slate-400 dark:text-slate-500 text-center">
                                Auto-refresh: 30s
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>