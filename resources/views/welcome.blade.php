<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AppFarmayo - Farmacia en Línea</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=outfit:300,400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS (compiled via Vite) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif

    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #e0f8f5 0%, #ffffff 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 8px 32px rgba(0, 169, 143, 0.1);
        }

        .text-emerald {
            color: #00a98f;
        }

        .bg-emerald {
            background-color: #00a98f;
        }

        .hover-bg-emerald:hover {
            background-color: #008a74;
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }

        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }
    </style>
</head>

<body
    class="antialiased bg-gray-50 text-gray-800 hero-gradient min-h-screen flex flex-col relative overflow-x-hidden pt-4 sm:pt-0">

    <!-- Top Announcement Banner -->
    {{-- <div class="bg-[#25D366] text-white py-2 px-4 text-center text-sm font-bold fixed top-0 w-full z-[110] shadow-md">
        Para realizar pedido comuníquese al WhatsApp: <a href="https://wa.me/595994310145" target="_blank" class="underline decoration-2 underline-offset-2">0994310145</a>
    </div> --}}

    <!-- Decorative blobs background -->
    <div
        class="absolute top-0 left-0 w-96 h-96 bg-teal-200 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob">
    </div>
    <div
        class="absolute top-0 right-0 w-96 h-96 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-2000">
    </div>
    <div
        class="absolute -bottom-32 left-20 w-96 h-96 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-blob animation-delay-4000">
    </div>

    <!-- Navigation -->
    <nav class="relative z-10 w-full max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Mayo Plus" class="h-12 w-auto object-contain">
            <span class="text-2xl font-bold text-gray-900 tracking-tight hidden sm:block">Farmacia y Perfumeria
                Mayo</span>
        </div>

        @if (Route::has('login'))
            <div class="flex items-center gap-2 sm:gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}"
                        class="font-semibold text-gray-600 hover:text-emerald transition duration-300">Dashboard</a>
                @else
                    <a href="{{ route('login') }}"
                        class="font-semibold text-gray-600 hover:text-emerald transition duration-300">Iniciar Sesión</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}"
                            class="px-5 py-2.5 rounded-full bg-emerald text-white font-semibold hover-bg-emerald transition duration-300 shadow-xl shadow-teal-500/20 transform hover:-translate-y-0.5 ml-2 hidden sm:block">Registrarse</a>
                    @endif
                @endauth
            </div>
        @endif
    </nav>

    <!-- Main Content -->
    <main class="relative z-10 grow flex items-center justify-center pt-0 pb-20">
        <div class="w-full max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16 md:gap-8 items-center">

            <!-- Hero Text -->
            <div class="flex flex-col gap-6 text-center lg:text-left z-10 sm:pt-0">
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-teal-50 border border-teal-100 text-teal-700 text-sm font-semibold w-max mx-auto lg:mx-0 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-teal-500"></span>
                    </span>
                    Cuidado de Salud Inteligente
                </div>

                <h1 class="text-4xl sm:text-6xl lg:text-7xl font-bold leading-[1.1] text-gray-900">
                    Tu Farmacia de <br />
                    <span
                        class="text-transparent bg-clip-text bg-linear-to-r from-emerald-500 to-teal-400">Confianza</span>
                </h1>

                <p class="text-lg text-gray-600 max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                    Accede a medicamentos de primera calidad, asesoramiento profesional y servicios de entrega rápida
                    directamente a tu puerta.
                </p>

                <div class="flex flex-col sm:flex-row items-center gap-4 mt-8 lg:mt-6 justify-center lg:justify-start">
                    <a href="{{ route('login') }}"
                        class="w-full sm:w-auto px-8 py-4 rounded-full bg-emerald text-white font-semibold text-lg hover-bg-emerald transition shadow-xl shadow-teal-500/20 transform hover:-translate-y-1 flex items-center justify-center gap-2">
                        Acceder ahora
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                    <a href="{{ route('register') }}"
                        class="w-full sm:w-auto px-8 py-4 rounded-full bg-white text-gray-700 font-semibold text-lg hover:text-emerald border border-gray-200 transition shadow-sm hover:shadow-md flex items-center justify-center group">
                        Crear Cuenta
                        <svg class="w-5 h-5 ml-2 text-gray-400 group-hover:text-emerald" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                            </path>
                        </svg>
                    </a>
                </div>

                <!-- WhatsApp Hero CTA -->
                <div class="mt-4 flex justify-center lg:justify-start">
                    <a href="https://wa.me/595994310145" target="_blank"
                        class="inline-flex items-center gap-3 px-6 py-3 bg-[#25D366] text-white rounded-2xl font-bold shadow-lg shadow-green-500/20 hover:bg-[#128C7E] transition-all transform hover:-translate-y-1">
                        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        <span>Realizar pedido vía WhatsApp 0994310145</span>
                    </a>
                </div>
                {{-- 
                <div class="flex items-center gap-6 mt-8 justify-center lg:justify-start">
                    <div class="flex -space-x-3">
                        <img class="w-10 h-10 rounded-full border-2 border-white object-cover"
                            src="https://i.pravatar.cc/100?img=4" alt="User 1">
                        <img class="w-10 h-10 rounded-full border-2 border-white object-cover"
                            src="https://i.pravatar.cc/100?img=5" alt="User 2">
                        <img class="w-10 h-10 rounded-full border-2 border-white object-cover"
                            src="https://i.pravatar.cc/100?img=6" alt="User 3">
                        <div
                            class="w-10 h-10 rounded-full border-2 border-white bg-teal-50 flex items-center justify-center text-xs font-bold text-teal-700">
                            +5k</div>
                    </div>
                    <div class="text-sm font-medium text-gray-500">
                        <span class="text-gray-900 font-bold">4.9/5</span> de clientes felices
                    </div>
                </div> --}}
            </div>

            <!-- Hero Visual / Mockup -->
            <div class="relative flex items-center justify-center mt-12 lg:mt-0 lg:h-[600px] w-full max-w-lg mx-auto">
                <div
                    class="absolute inset-0 bg-linear-to-tr from-emerald-100 to-teal-50 rounded-full blur-3xl opacity-60">
                </div>

                <!-- Main Glass Card -->
                <div
                    class="glass-card relative z-10 w-full rounded-[2.5rem] p-8 transform transition duration-500 hover:-translate-y-2 hover:shadow-2xl hover:shadow-teal-500/20">
                    <div class="text-center mb-8">
                        <div
                            class="inline-flex items-center justify-center w-20 h-20 bg-emerald-100 rounded-2xl mb-6 shadow-sm">
                            <svg class="w-10 h-10 text-emerald" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Revisa tus Facturas</h3>
                        <p class="text-gray-600 leading-relaxed mb-8">
                            Regístrate ahora para acceder a tu historial de compras, descargar tus facturas y realizar
                            seguimientos detallados.
                        </p>
                    </div>

                    <a href="{{ route('register') }}"
                        class="w-full py-4 rounded-2xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition shadow-lg flex items-center justify-center gap-2 group">
                        Regístrate gratis
                        <svg class="w-5 h-5 text-gray-400 group-hover:translate-x-1 transition" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                </div>

            </div>

        </div>
    </main>

    <!-- Footer with Visit Counter -->
    <footer class="relative z-10 w-full max-w-7xl mx-auto px-6 py-8 border-t border-gray-100 mt-auto">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-gray-500 text-sm font-medium">
            <p>© {{ date('Y') }} Farmacia y Perfumería Mayo. Todos los derechos reservados.</p>
            
            <div class="flex items-center gap-2 px-4 py-1.5 rounded-full bg-white border border-gray-100 shadow-sm">
                <span class="flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-gray-600">Visitas: <span class="font-bold text-gray-900">{{ number_format($totalVisitas ?? 0) }}</span></span>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Floating Action Button (FAB) -->
    <a href="https://wa.me/595994310145" target="_blank"
        class="fixed bottom-8 right-8 z-100 group flex items-center gap-3">
        <div
            class="hidden group-hover:block transition-all duration-300 transform -translate-x-2 bg-white text-gray-800 px-4 py-2 rounded-2xl shadow-xl border border-gray-100 font-semibold whitespace-nowrap">
            ¿Necesitas ayuda? Haz tu pedido aquí
        </div>
        <div
            class="w-16 h-16 bg-[#25D366] rounded-full flex items-center justify-center shadow-2xl shadow-green-500/40 hover:scale-110 transition duration-300 animate-bounce cursor-pointer text-white text-3xl">
            <svg class="w-8 h-8 fill-current" viewBox="0 0 24 24">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
        </div>
    </a>

</body>

</html>
