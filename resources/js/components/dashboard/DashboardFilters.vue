<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    filtros: {
        desde: string;
        hasta: string;
        servicio: number | null;
        modalidad: string | null;
        estado_academico: string | null;
    };
    serviciosLista: { id: number; nombre: string; modalidad: string }[];
}>();

const form = reactive({ ...props.filtros });

function aplicar() {
    router.get(route('dashboard.index'),
        {
            desde: form.desde,
            hasta: form.hasta,
            servicio: form.servicio ?? undefined,
            modalidad: form.modalidad ?? undefined,
            estado_academico: form.estado_academico ?? undefined,
        },
        { preserveScroll: true, replace: true },
    );
}

function limpiar() {
    const hoy = new Date();
    const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().slice(0, 10);
    const hoyStr = hoy.toISOString().slice(0, 10);
    form.desde = primerDia;
    form.hasta = hoyStr;
    form.servicio = null;
    form.modalidad = null;
    form.estado_academico = null;
    aplicar();
}
</script>

<template>
    <div
        class="flex flex-wrap items-end gap-3 rounded-xl border border-sidebar-border/70 bg-card px-4 py-3 shadow-sm dark:border-sidebar-border"
    >
        <!-- Rango de fechas -->
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Desde</label>
            <input
                v-model="form.desde"
                type="date"
                class="rounded-lg border border-input bg-background px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
        </div>
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Hasta</label>
            <input
                v-model="form.hasta"
                type="date"
                class="rounded-lg border border-input bg-background px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            />
        </div>

        <!-- Filtro por servicio -->
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Servicio</label>
            <select
                v-model="form.servicio"
                class="rounded-lg border border-input bg-background px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            >
                <option :value="null">Todos</option>
                <option v-for="s in serviciosLista" :key="s.id" :value="s.id">
                    {{ s.nombre }}
                </option>
            </select>
        </div>

        <!-- Filtro por modalidad -->
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Modalidad</label>
            <select
                v-model="form.modalidad"
                class="rounded-lg border border-input bg-background px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            >
                <option :value="null">Todas</option>
                <option value="VIRTUAL">Virtual</option>
                <option value="PRESENCIAL">Presencial</option>
                <option value="HIBRIDO">Híbrido</option>
            </select>
        </div>

        <!-- Filtro por estado académico -->
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-muted-foreground">Estado académico</label>
            <select
                v-model="form.estado_academico"
                class="rounded-lg border border-input bg-background px-3 py-1.5 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
            >
                <option :value="null">Todos</option>
                <option value="CURSANDO">Cursando</option>
                <option value="FINALIZADO">Finalizado</option>
                <option value="ABANDONADO">Abandonado</option>
                <option value="PENDIENTE_PAGO">Pendiente pago</option>
            </select>
        </div>

        <!-- Botones -->
        <div class="flex gap-2 pb-0.5">
            <button
                type="button"
                class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition hover:opacity-90"
                @click="aplicar"
            >
                Aplicar
            </button>
            <button
                type="button"
                class="rounded-lg border border-input bg-background px-4 py-2 text-sm font-semibold text-foreground transition hover:bg-muted"
                @click="limpiar"
            >
                Limpiar
            </button>
        </div>
    </div>
</template>
