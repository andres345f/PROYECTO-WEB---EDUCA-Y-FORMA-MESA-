<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { 
    BookOpen, 
    Video, 
    Users, 
    GraduationCap, 
    Search,
    CheckCircle2,
    CalendarCheck,
    ArrowRight
} from 'lucide-vue-next';
import { route } from 'ziggy-js';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="Bienvenido - Encuentra tu Tutor Ideal" />

    <div class="min-h-screen bg-slate-50 text-slate-900 font-sans selection:bg-indigo-500 selection:text-white">
        
        <!-- Navegación Superior -->
        <header class="fixed top-0 w-full bg-white/80 backdrop-blur-md border-b border-slate-200 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                
                <!-- Logo -->
                <div class="flex items-center gap-2">
                    <div class="bg-indigo-600 p-2 rounded-lg">
                        <GraduationCap class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-xl font-bold tracking-tight text-slate-900">EduConnect</span>
                </div>

                <!-- Botones de Autenticación (Derecha por estándar UX) -->
                <nav  class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-colors"
                    >
                        Ir al Panel
                    </Link>

                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-colors"
                        >
                            Iniciar Sesión
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="text-sm font-semibold bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-all shadow-sm hover:shadow active:scale-95"
                        >
                            Regístrate Gratis
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="pt-32 pb-16 sm:pt-40 sm:pb-24 lg:pb-32 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
                <div class="text-center max-w-3xl mx-auto">
                    <h1 class="text-5xl sm:text-6xl font-extrabold tracking-tight text-slate-900 mb-8">
                        Aprende a tu ritmo, <br class="hidden sm:block" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500">
                            donde y cuando quieras.
                        </span>
                    </h1>
                    
                    <p class="text-lg sm:text-xl text-slate-600 mb-10 leading-relaxed">
                        Conecta con expertos en cientos de materias. Elige entre clases particulares, cursos estructurados, de forma presencial o 100% online.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link 
                            :href="route('register')" 
                            class="w-full sm:w-auto px-8 py-4 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 flex items-center justify-center gap-2 group"
                        >
                            Encontrar un Tutor
                            <ArrowRight class="w-5 h-5 group-hover:translate-x-1 transition-transform" />
                        </Link>
                        <!-- <Link 
                            :href="route('register')" 
                            class="w-full sm:w-auto px-8 py-4 bg-white text-slate-700 font-bold rounded-xl border-2 border-slate-200 hover:border-indigo-200 hover:bg-slate-50 transition-all flex items-center justify-center"
                        >
                            Quiero Enseñar
                        </Link> -->
                    </div>
                </div>
            </div>
        </main>

        <!-- Sección de Modalidades -->
        <section class="py-16 bg-white border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">Educación que se adapta a ti</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Modalidad Virtual -->
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 hover:shadow-lg transition-shadow">
                        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mb-4">
                            <Video class="w-6 h-6" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Clases Virtuales</h3>
                        <p class="text-slate-600">Aprende desde casa a través de nuestra sala de videoconferencia integrada con pizarra virtual.</p>
                    </div>

                    <!-- Modalidad Presencial -->
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 hover:shadow-lg transition-shadow">
                        <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-4">
                            <Users class="w-6 h-6" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Clases Presenciales</h3>
                        <p class="text-slate-600">Encuentra tutores cerca de tu zona para sesiones en persona, en tu domicilio o en lugares públicos.</p>
                    </div>

                    <!-- Cursos Estructurados -->
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 hover:shadow-lg transition-shadow">
                        <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center mb-4">
                            <BookOpen class="w-6 h-6" />
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-2">Paquetes y Cursos</h3>
                        <p class="text-slate-600">Inscríbete a programas completos de varias semanas con horarios definidos y temarios específicos.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cómo funciona -->
        <section class="py-20 bg-slate-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold mb-4">¿Cómo funciona?</h2>
                    <p class="text-slate-400">Tres simples pasos para alcanzar tus metas académicas.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                    <!-- Línea conectora oculta en móvil -->
                    <div class="hidden md:block absolute top-8 left-[16%] right-[16%] h-0.5 bg-slate-700"></div>

                    <div class="relative text-center z-10">
                        <div class="w-16 h-16 mx-auto bg-indigo-500 text-white rounded-full flex items-center justify-center mb-6 shadow-lg shadow-indigo-500/30 border-4 border-slate-900">
                            <Search class="w-6 h-6" />
                        </div>
                        <h3 class="text-xl font-bold mb-2">1. Busca un Servicio</h3>
                        <p class="text-slate-400">Explora nuestro catálogo por materia, nivel educativo o modalidad.</p>
                    </div>

                    <div class="relative text-center z-10">
                        <div class="w-16 h-16 mx-auto bg-indigo-500 text-white rounded-full flex items-center justify-center mb-6 shadow-lg shadow-indigo-500/30 border-4 border-slate-900">
                            <CalendarCheck class="w-6 h-6" />
                        </div>
                        <h3 class="text-xl font-bold mb-2">2. Reserva tu Horario</h3>
                        <p class="text-slate-400">Elige la disponibilidad del tutor o únete a un curso pre-programado.</p>
                    </div>

                    <div class="relative text-center z-10">
                        <div class="w-16 h-16 mx-auto bg-indigo-500 text-white rounded-full flex items-center justify-center mb-6 shadow-lg shadow-indigo-500/30 border-4 border-slate-900">
                            <CheckCircle2 class="w-6 h-6" />
                        </div>
                        <h3 class="text-xl font-bold mb-2">3. Aprende y Evalúa</h3>
                        <p class="text-slate-400">Asiste a tu sesión, recibe informes de progreso y califica a tu tutor.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-slate-900 border-t border-slate-200 py-8 text-center text-slate-500">
            <p>&copy; 2026 EduConnect. Todos los derechos reservados.</p>
        </footer>
    </div>
</template>