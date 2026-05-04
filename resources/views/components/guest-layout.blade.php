<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title>{{ $title ?? config("app.name", "Task Tracker") }}</title>
        <link
            href="https://fonts.bunny.net/css?family=hanken-grotesk:400,500,600,700&display=swap"
            rel="stylesheet"
        />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-hanken-grotesk text-gray-900 antialiased">
        <div
            class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900"
        >
            <div>
                <a href="/" class="flex items-center gap-3 group">
                    <!-- Icon mark -->
                    <div
                        class="flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900"
                    >
                        <svg
                            class="w-5 h-5 text-indigo-600 dark:text-indigo-300"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke-opacity="0.4"
                            />
                            <polyline points="8 12 11 15 16 9" />
                        </svg>
                    </div>

                    <!-- Wordmark -->
                    <div class="leading-tight">
                        <span
                            class="block text-xl font-bold tracking-tight font-hanken-grotesk"
                        >
                            <span class="text-indigo-950 dark:text-indigo-100"
                                >Task</span
                            ><span class="text-indigo-600 dark:text-indigo-400"
                                >Tracker</span
                            >
                        </span>
                        <span
                            class="block text-[10px] tracking-[0.2em] uppercase text-indigo-400 dark:text-indigo-500 font-hanken-grotesk"
                        >
                            Productivity · Focus
                        </span>
                    </div>
                </a>
            </div>

            <div
                class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg"
            >
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
