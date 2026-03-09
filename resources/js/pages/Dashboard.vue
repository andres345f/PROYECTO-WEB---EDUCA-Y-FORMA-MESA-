<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import KpiCard from '@/components/dashboard/KpiCard.vue';
import DistributionBar from '@/components/dashboard/DistributionBar.vue';
import MiniBarChart from '@/components/dashboard/MiniBarChart.vue';
import CircularProgress from '@/components/dashboard/CircularProgress.vue';
import DashboardFilters from '@/components/dashboard/DashboardFilters.vue';

// ---- Tipos ----
interface VentaEstado {
    total: number;
    monto: number;
}

interface MetodoPago {
    metodo: string;
    total: number;
    monto: number;
}

interface ServicioTop {
    nombre: string;
    modalidad: string;
    inscripciones: number;
}

interface KpisFinanciero {
    ingresos_periodo: number;
    saldo_pendiente: number;
    ventas_por_estado: Record<string, VentaEstado>;
    metodos_pago: MetodoPago[];
}

interface KpisAcademico {
    total_alumnos: number;
    inscripciones_periodo: number;
    distribucion_estados: Record<string, number>;
    promedio_calificacion: number;
    tasa_finalizacion: number;
}

interface KpisAsistencia {
    tasa_asistencia: number;
    total_asistencias: number;
    presentes: number;
    distribucion_asistencia: Record<string, number>;
}

interface KpisOferta {
    servicios_activos: number;
    calendarios_activos: number;
    distribucion_modalidad: Record<string, number>;
    cupos_totales: number;
    inscripciones_activas: number;
    pct_ocupacion: number;
    top_servicios: ServicioTop[];
}

interface KpisTutores {
    total_tutores: number;
    tutores_activos: number;
}

interface Props {
    filtros: {
        desde: string;
        hasta: string;
        servicio: number | null;
        modalidad: string | null;
        estado_academico: string | null;
    };
    kpis: {
        financiero: KpisFinanciero;
        academico: KpisAcademico;
        asistencia: KpisAsistencia;
        oferta: KpisOferta;
        tutores: KpisTutores;
    };
    servicios_lista: { id: number; nombre: string; modalidad: string }[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard/kpis' },
];

// ---- Utilidades de formato ----
const moneda = (v: number) =>
    new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', maximumFractionDigits: 0 }).format(v);

// ---- Distribuciones como segmentos ----
const coloresEstado: Record<string, string> = {
    CURSANDO:       'bg-blue-500',
    FINALIZADO:     'bg-emerald-500',
    ABANDONADO:     'bg-red-400',
    PENDIENTE_PAGO: 'bg-amber-400',
};

const labelsEstado: Record<string, string> = {
    CURSANDO:       'Cursando',
    FINALIZADO:     'Finalizado',
    ABANDONADO:     'Abandonado',
    PENDIENTE_PAGO: 'Pend. pago',
};

const estadosAcademicosSegments = computed(() =>
    Object.entries(props.kpis.academico.distribucion_estados).map(([k, v]) => ({
        label: labelsEstado[k] ?? k,
        value: v as number,
        color: coloresEstado[k] ?? 'bg-gray-400',
    })),
);

const coloresFinanciero: Record<string, string> = {
    PENDIENTE: 'bg-amber-400',
    PARCIAL:   'bg-blue-400',
    PAGADO:    'bg-emerald-500',
    ANULADO:   'bg-red-400',
};

const ventasEstadoSegments = computed(() =>
    Object.entries(props.kpis.financiero.ventas_por_estado).map(([k, v]) => ({
        label: k,
        value: (v as VentaEstado).total,
        color: coloresFinanciero[k] ?? 'bg-gray-400',
    })),
);

const coloresAsistencia: Record<string, string> = {
    PRESENTE: 'bg-emerald-500',
    AUSENTE:  'bg-red-400',
    LICENCIA: 'bg-amber-400',
    TARDANZA: 'bg-blue-400',
};

const asistenciaSegments = computed(() =>
    Object.entries(props.kpis.asistencia.distribucion_asistencia).map(([k, v]) => ({
        label: k,
        value: v as number,
        color: coloresAsistencia[k] ?? 'bg-gray-400',
    })),
);

const coloresModalidad: Record<string, string> = {
    VIRTUAL:    'bg-indigo-500',
    PRESENCIAL: 'bg-emerald-500',
    HIBRIDO:    'bg-amber-400',
};

const modalidadSegments = computed(() =>
    Object.entries(props.kpis.oferta.distribucion_modalidad).map(([k, v]) => ({
        label: k,
        value: v as number,
        color: coloresModalidad[k] ?? 'bg-gray-400',
    })),
);

const coloresMetodo: Record<string, string> = {
    EFECTIVO:    'bg-emerald-500',
    QR:          'bg-blue-500',
    TRANSFERENCIA: 'bg-indigo-500',
    TARJETA:     'bg-purple-500',
};

const metodosPagoBarItems = computed(() =>
    props.kpis.financiero.metodos_pago.map((m) => ({
        label: m.metodo,
        value: m.monto,
    })),
);

const topServiciosBarItems = computed(() =>
    props.kpis.oferta.top_servicios.map((s) => ({
        label: s.nombre,
        value: s.inscripciones,
        badge: s.modalidad,
    })),
);

// Rango de fechas legible
const periodoLabel = computed(() => {
    const d = new Date(props.filtros.desde + 'T12:00:00');
    const h = new Date(props.filtros.hasta + 'T12:00:00');
    return `${d.toLocaleDateString('es-AR', { day: '2-digit', month: 'short' })} – ${h.toLocaleDateString('es-AR', { day: '2-digit', month: 'short', year: 'numeric' })}`;
});

const exportParams = computed(() => ({
    desde: props.filtros.desde,
    hasta: props.filtros.hasta,
    servicio: props.filtros.servicio ?? undefined,
    modalidad: props.filtros.modalidad ?? undefined,
    estado_academico: props.filtros.estado_academico ?? undefined,
}));

const pdfExportUrl = computed(() => route('dashboard.export.pdf', exportParams.value));
const excelExportUrl = computed(() => route('dashboard.export.excel', exportParams.value));
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">

            <!-- ====== FILTROS ====== -->
            <DashboardFilters
                :filtros="filtros"
                :servicios-lista="servicios_lista"
            />

            <!-- Sub-título del período -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-foreground">Resumen del período</h2>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">
                        📅 {{ periodoLabel }}
                    </span>
                    <a
                        :href="pdfExportUrl"
                        class="rounded-lg border border-input bg-background px-3 py-1.5 text-xs font-semibold text-foreground transition hover:bg-muted"
                    >
                        Exportar PDF
                    </a>
                    <a
                        :href="excelExportUrl"
                        class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground transition hover:opacity-90"
                    >
                        Exportar Excel
                    </a>
                </div>
            </div>

            <!-- ====== SECCIÓN 1: KPIs PRINCIPALES ====== -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <KpiCard
                    title="Ingresos del período"
                    :value="moneda(kpis.financiero.ingresos_periodo)"
                    icon="💰"
                    color="green"
                    subtitle="Pagos recibidos en el rango"
                />
                <KpiCard
                    title="Saldo pendiente"
                    :value="moneda(kpis.financiero.saldo_pendiente)"
                    icon="⏳"
                    color="yellow"
                    subtitle="Total en ventas abiertas"
                />
                <KpiCard
                    title="Inscripciones"
                    :value="kpis.academico.inscripciones_periodo"
                    icon="📋"
                    color="blue"
                    subtitle="Nuevas en el período"
                />
                <KpiCard
                    title="Total alumnos"
                    :value="kpis.academico.total_alumnos"
                    icon="👥"
                    color="indigo"
                    subtitle="Registrados en la plataforma"
                />
            </div>

            <!-- ====== SECCIÓN 2: FINANCIERO + ACADÉMICO ====== -->
            <div class="grid gap-4 md:grid-cols-2">

                <!-- Card: Estado de ventas -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">Estado de ventas del período</h3>
                    <DistributionBar
                        :segments="ventasEstadoSegments"
                        :format-value="(v) => String(v) + ' ventas'"
                    />
                    <!-- Detalle de montos por estado -->
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div
                            v-for="(estado, key) in kpis.financiero.ventas_por_estado"
                            :key="key"
                            class="rounded-lg bg-muted/40 px-3 py-2"
                        >
                            <div class="text-xs font-medium text-muted-foreground">{{ key }}</div>
                            <div class="text-sm font-bold text-foreground">{{ moneda(estado.monto) }}</div>
                            <div class="text-xs text-muted-foreground">{{ estado.total }} ventas</div>
                        </div>
                    </div>
                </div>

                <!-- Card: Estado académico -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">Distribución estado académico</h3>
                    <DistributionBar :segments="estadosAcademicosSegments" />
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-muted/40 p-3 text-center">
                            <div class="text-xs text-muted-foreground">Tasa finalización</div>
                            <div class="text-xl font-bold text-emerald-600">{{ kpis.academico.tasa_finalizacion }}%</div>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-3 text-center">
                            <div class="text-xs text-muted-foreground">Calificación promedio</div>
                            <div class="text-xl font-bold text-blue-600">
                                {{ kpis.academico.promedio_calificacion > 0 ? kpis.academico.promedio_calificacion : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== SECCIÓN 3: ASISTENCIA + OCUPACIÓN + MODALIDAD ====== -->
            <div class="grid gap-4 md:grid-cols-3">

                <!-- Asistencia circular -->
                <div class="flex flex-col items-center justify-center rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <h3 class="mb-4 w-full text-sm font-semibold text-foreground">Tasa de asistencia</h3>
                    <CircularProgress
                        :value="kpis.asistencia.tasa_asistencia"
                        :size="100"
                        :stroke-width="10"
                        color="stroke-emerald-500"
                        label="Presentes"
                    />
                    <div class="mt-3 w-full">
                        <DistributionBar :segments="asistenciaSegments" />
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">
                        {{ kpis.asistencia.presentes }} / {{ kpis.asistencia.total_asistencias }} asistencias
                    </p>
                </div>

                <!-- Ocupación de calendarios -->
                <div class="flex flex-col items-center justify-center rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <h3 class="mb-4 w-full text-sm font-semibold text-foreground">Ocupación calendarios</h3>
                    <CircularProgress
                        :value="kpis.oferta.pct_ocupacion"
                        :size="100"
                        :stroke-width="10"
                        color="stroke-blue-500"
                        label="Ocupación"
                    />
                    <div class="mt-4 grid w-full grid-cols-2 gap-2 text-center">
                        <div class="rounded-lg bg-muted/40 p-2">
                            <div class="text-lg font-bold text-blue-600">{{ kpis.oferta.inscripciones_activas }}</div>
                            <div class="text-xs text-muted-foreground">Alumnos activos</div>
                        </div>
                        <div class="rounded-lg bg-muted/40 p-2">
                            <div class="text-lg font-bold text-foreground">{{ kpis.oferta.cupos_totales }}</div>
                            <div class="text-xs text-muted-foreground">Cupos totales</div>
                        </div>
                    </div>
                </div>

                <!-- Distribución modalidad + tutores -->
                <div class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <div>
                        <h3 class="mb-3 text-sm font-semibold text-foreground">Modalidad de servicios</h3>
                        <DistributionBar :segments="modalidadSegments" />
                    </div>
                    <hr class="border-border" />
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-950/30">
                            <div class="text-2xl font-bold text-indigo-600">{{ kpis.tutores.total_tutores }}</div>
                            <div class="text-xs text-muted-foreground">Tutores registrados</div>
                        </div>
                        <div class="rounded-lg bg-emerald-50 p-3 dark:bg-emerald-950/30">
                            <div class="text-2xl font-bold text-emerald-600">{{ kpis.tutores.tutores_activos }}</div>
                            <div class="text-xs text-muted-foreground">Tutores activos</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950/30">
                            <div class="text-2xl font-bold text-blue-600">{{ kpis.oferta.servicios_activos }}</div>
                            <div class="text-xs text-muted-foreground">Servicios activos</div>
                        </div>
                        <div class="rounded-lg bg-purple-50 p-3 dark:bg-purple-950/30">
                            <div class="text-2xl font-bold text-purple-600">{{ kpis.oferta.calendarios_activos }}</div>
                            <div class="text-xs text-muted-foreground">Calendarios</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ====== SECCIÓN 4: TOP SERVICIOS + MÉTODOS DE PAGO ====== -->
            <div class="grid gap-4 md:grid-cols-2">

                <!-- Top servicios por inscripciones -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">Top 5 servicios por inscripciones</h3>
                    <MiniBarChart
                        v-if="topServiciosBarItems.length > 0"
                        :items="topServiciosBarItems"
                        color="bg-indigo-500"
                    />
                    <p v-else class="text-sm text-muted-foreground">Sin datos para mostrar.</p>
                </div>

                <!-- Métodos de pago -->
                <div class="rounded-xl border border-sidebar-border/70 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <h3 class="mb-4 text-sm font-semibold text-foreground">Ingresos por método de pago</h3>
                    <MiniBarChart
                        v-if="metodosPagoBarItems.length > 0"
                        :items="metodosPagoBarItems"
                        color="bg-emerald-500"
                        :format-value="(v) => moneda(v)"
                    />
                    <p v-else class="text-sm text-muted-foreground">Sin pagos en el período.</p>

                    <!-- Lista de métodos con totales -->
                    <div v-if="kpis.financiero.metodos_pago.length > 0" class="mt-4 space-y-2">
                        <div
                            v-for="m in kpis.financiero.metodos_pago"
                            :key="m.metodo"
                            class="flex items-center justify-between rounded-lg bg-muted/40 px-3 py-2 text-xs"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[coloresMetodo[m.metodo] ?? 'bg-gray-400', 'inline-block h-2.5 w-2.5 rounded-sm']"
                                />
                                <span class="font-medium">{{ m.metodo }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold text-foreground">{{ moneda(m.monto) }}</span>
                                <span class="ml-2 text-muted-foreground">({{ m.total }} pagos)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
