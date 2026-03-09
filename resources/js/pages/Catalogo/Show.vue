<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    User, 
    Clock, 
    CreditCard, 
    Users, 
    CalendarDays, 
    BookOpen, 
    ArrowRight,
    Search,
    FilterX
} from 'lucide-vue-next';

// Interfaces corregidas para evitar errores de compilación
type Tutor = {
    id: number;
    usuario?: {
        id: number;
        name: string;
        email?: string;
    };
};

type Disponibilidad = {
    id: number;
    dia_semana: string;
    hora_apertura: string;
    hora_cierre: string;
};

type Calendario = {
    id: number;
    tipo_programacion: 'CITA_LIBRE' | 'PAQUETE_FIJO';
    numero_sesiones: number | null;
    duracion_sesion_minutos: number;
    costo_total: number;
    cupos_maximos: number;
    tutor?: Tutor;
    disponibilidades: Disponibilidad[];
};

type Servicio = {
    id: number;
    nombre: string;
    descripcion: string | null;
    modalidad: string;
};

type Pagination<T> = {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Filters = {
    id_tutor?: string;
    tipo_programacion?: string;
};

const props = defineProps<{
    servicio: Servicio;
    calendarios: Pagination<Calendario>;
    tutores: Tutor[];
    filters?: Filters;
}>();

const selectedTutor = ref(props.filters?.id_tutor ?? '');
const selectedTipo = ref(props.filters?.tipo_programacion ?? '');

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Catálogo', href: '/catalogo' },
    { title: props.servicio.nombre, href: `/catalogo/servicios/${props.servicio.id}` },
];

const aplicarFiltros = () => {
    router.get(
        route('catalogo.servicio.show', props.servicio.id),
        {
            id_tutor: selectedTutor.value,
            tipo_programacion: selectedTipo.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true
        },
    );
};

// Filtrado reactivo al cambiar selects
watch([selectedTutor, selectedTipo], () => aplicarFiltros());

const limpiar = () => {
    selectedTutor.value = '';
    selectedTipo.value = '';
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="`Catálogo - ${servicio.nombre}`" />
        <UserLayout>
            <div class="max-w-6xl mx-auto space-y-8">
                
                <!-- Hero del Servicio -->
                <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-3xl p-8 text-white shadow-xl relative overflow-hidden">
                    <div class="relative z-10">
                        <span class="inline-block px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-xs font-bold uppercase tracking-wider mb-4">
                            Servicio Disponible
                        </span>
                        <h1 class="text-4xl font-extrabold tracking-tight mb-2">{{ servicio.nombre }}</h1>
                        <p class="text-indigo-100 max-w-2xl leading-relaxed">
                            {{ servicio.descripcion || 'Explora los horarios disponibles y reserva tu clase con uno de nuestros expertos certificados.' }}
                        </p>
                    </div>
                    <!-- Decoración abstracta -->
                    <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                </div>

                <!-- Filtros de Búsqueda -->
                <div class="bg-card border border-border rounded-2xl p-5 shadow-sm flex flex-col md:flex-row gap-4 items-center">
                    <div class="flex items-center gap-3 w-full md:flex-1">
                        <Search class="w-5 h-5 text-muted-foreground ml-2" />
                        <select v-model="selectedTutor" class="flex-1  border-2 rounded-md focus:ring-0 text-sm font-medium">
                            <option class="bg-background" value="">Cualquier Tutor</option>
                            <option class="bg-background" v-for="tutor in tutores" :key="tutor.id" :value="String(tutor.id)">
                                {{ tutor.usuario?.name }}
                            </option>
                        </select>
                    </div>

                    <div class="h-8 w-px bg-border hidden md:block"></div>

                    <div class="flex items-center gap-3 w-full md:w-64">
                        <BookOpen class="w-5 h-5 text-muted-foreground" />
                        <select v-model="selectedTipo" class="flex-1  border-2 rounded-md focus:ring-0 text-sm font-medium">
                            <option class="bg-background" value="">Todos los tipos</option>
                            <option class="bg-background" value="CITA_LIBRE">Sesión Individual</option>
                            <option class="bg-background" value="PAQUETE_FIJO">Paquete de Sesiones</option>
                        </select>
                    </div>

                    <button 
                        v-if="selectedTutor || selectedTipo"
                        @click="limpiar" 
                        class="text-xs font-bold text-destructive hover:underline px-2 flex items-center gap-1"
                    >
                        <FilterX class="w-4 h-4" /> Limpiar
                    </button>
                </div>

                <!-- Listado de Tutores / Ofertas -->
                <div v-if="calendarios.data.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <article v-for="calendario in calendarios.data" :key="calendario.id" 
                        class="bg-card border border-border rounded-3xl overflow-hidden hover:shadow-xl hover:border-primary/30 transition-all duration-300 flex flex-col group"
                    >
                        <div class="p-6 flex-1">
                            <!-- Cabecera de la Tarjeta (Tutor) -->
                            <div class="flex items-center gap-4 mb-6">
                                <div class="w-14 h-14 bg-indigo-100 text-indigo-700 rounded-2xl flex items-center justify-center font-bold text-xl shadow-inner border border-indigo-200">
                                    {{ getInitials(calendario.tutor?.usuario?.name || 'T') }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-bold text-lg text-foreground group-hover:text-indigo-600 transition-colors">
                                            {{ calendario.tutor?.usuario?.name }}
                                        </h3>
                                        <span v-if="calendario.tipo_programacion === 'PAQUETE_FIJO'" 
                                            class="bg-amber-100 text-amber-700 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase border border-amber-200">
                                            Paquete
                                        </span>
                                    </div>
                                    <p class="text-xs text-muted-foreground flex items-center gap-1">
                                        <User class="w-3 h-3" /> Tutor Certificado
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-black text-foreground">BOB {{ calendario.costo_total }}</p>
                                    <p class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest">Costo Total</p>
                                </div>
                            </div>

                            <!-- Detalles Técnicos -->
                            <div class="grid grid-cols-3 gap-2 mb-6">
                                <div class="bg-muted/50 p-3 rounded-2xl text-center border border-transparent hover:border-border transition-colors">
                                    <Clock class="w-4 h-4 mx-auto mb-1 text-muted-foreground" />
                                    <p class="text-xs font-bold">{{ calendario.duracion_sesion_minutos }} min</p>
                                    <p class="text-[9px] text-muted-foreground uppercase">Duración</p>
                                </div>
                                <div class="bg-muted/50 p-3 rounded-2xl text-center border border-transparent hover:border-border transition-colors">
                                    <CalendarDays class="w-4 h-4 mx-auto mb-1 text-muted-foreground" />
                                    <p class="text-xs font-bold">
                                        {{ calendario.tipo_programacion === 'PAQUETE_FIJO' ? (calendario.numero_sesiones || 1) : 1 }}
                                    </p>
                                    <p class="text-[9px] text-muted-foreground uppercase">Sesiones</p>
                                </div>
                                <div class="bg-muted/50 p-3 rounded-2xl text-center border border-transparent hover:border-border transition-colors">
                                    <Users class="w-4 h-4 mx-auto mb-1 text-muted-foreground" />
                                    <p class="text-xs font-bold">{{ calendario.cupos_maximos }}</p>
                                    <p class="text-[9px] text-muted-foreground uppercase">Cupos</p>
                                </div>
                            </div>

                            <!-- Disponibilidad -->
                            <div class="space-y-2">
                                <h4 class="text-[10px] font-black text-muted-foreground uppercase tracking-widest mb-2 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                                    Horarios Disponibles
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    <div v-for="disp in calendario.disponibilidades" :key="disp.id" 
                                        class="px-3 py-1.5 bg-background border border-border rounded-xl text-[11px] font-medium flex items-center gap-2 group/time hover:border-primary transition-colors"
                                    >
                                        <span class="text-primary font-bold uppercase">{{ disp.dia_semana.slice(0,3) }}</span>
                                        <span class="text-muted-foreground">{{ disp.hora_apertura.slice(0,5) }} - {{ disp.hora_cierre.slice(0,5) }}</span>
                                    </div>
                                    <p v-if="!calendario.disponibilidades.length" class="text-xs italic text-muted-foreground">
                                        Consultar disponibilidad específica.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de Acción -->
                        <div class="p-6 pt-0 mt-auto">
                            <Link
                                :href="route('catalogo.inscripcion.preview', calendario.id)"
                                class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 text-white font-bold rounded-2xl hover:bg-indigo-700 active:scale-95 transition-all "
                            >
                                <CreditCard class="w-4 h-4" />
                                Ver Preinscripción
                                <ArrowRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </article>
                </div>

                <!-- Estado Vacío -->
                <div v-else class="bg-card border border-border border-dashed rounded-3xl p-20 text-center">
                    <div class="max-w-xs mx-auto">
                        <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center mx-auto mb-4">
                            <FilterX class="w-8 h-8 text-muted-foreground" />
                        </div>
                        <h3 class="text-lg font-bold">No hay horarios disponibles</h3>
                        <p class="text-sm text-muted-foreground mb-6">Prueba cambiando los filtros de tutor o tipo de sesión.</p>
                        <button @click="limpiar" class="px-6 py-2 bg-primary text-white font-bold rounded-xl shadow-md">
                            Ver todo el catálogo
                        </button>
                    </div>
                </div>

                <!-- Paginación -->
                <div v-if="calendarios.links?.length > 3" class="flex justify-center gap-2 pt-8">
                    <Link
                        v-for="link in calendarios.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="min-w-[40px] h-10 flex items-center justify-center px-4 rounded-xl border border-border text-sm font-bold transition-all"
                        :class="{ 
                            'bg-indigo-600 text-white border-indigo-600 shadow-lg shadow-indigo-200': link.active, 
                            'bg-card hover:bg-muted text-foreground': !link.active && link.url,
                            'opacity-30 pointer-events-none': !link.url 
                        }"
                        v-html="link.label"
                    />
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>