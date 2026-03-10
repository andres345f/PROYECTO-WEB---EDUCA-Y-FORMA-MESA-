<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Informe {
    id_informe: number;
    id_asistencia: number;
    temas_vistos?: string | null;
    tareas_asignadas?: string | null;
    desempenio?: 'BAJO' | 'MEDIO' | 'ALTO' | 'EXCELENTE' | null;
    asistencia?: {
        sesion?: {
            fecha_sesion?: string | null;
            hora_inicio?: string | null;
            hora_fin?: string | null;
            numero_sesion?: number | null;
            calendario?: {
                servicio?: {
                    nombre?: string | null;
                };
            };
        };
    };
}

interface Pagination<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Filters {
    search?: string;
    desempenio?: string;
}

const props = defineProps<{
    informes: Pagination<Informe>;
    filters?: Filters;
}>();

const search = ref(props.filters?.search ?? '');
const desempenio = ref(props.filters?.desempenio ?? '');
const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Informes de clase', href: '/informes-clase' }];

const filtrar = () => {
    router.get(route('informes-clase.index'), { search: search.value, desempenio: desempenio.value }, { preserveState: true, preserveScroll: true });
};

const limpiar = () => {
    search.value = '';
    desempenio.value = '';
    filtrar();
};

const eliminar = (id: number) => {
    if (window.confirm('¿Eliminar informe?')) {
        router.delete(route('informes-clase.destroy', id));
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Informes de clase" />
        <UserLayout>
            <div class="space-y-4">
                <div class="bg-card border border-border rounded-xl p-5">
                    <h1 class="text-xl font-bold">Informes de clase</h1>
                </div>

                <div class="bg-card border border-border rounded-xl p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input v-model="search" type="text" placeholder="Buscar por tema" class="px-3 py-2 border border-border rounded-lg bg-card" />
                    <select v-model="desempenio" class="px-3 py-2 border border-border rounded-lg bg-card">
                        <option value="">Todos</option>
                        <option value="BAJO">Bajo</option>
                        <option value="MEDIO">Medio</option>
                        <option value="ALTO">Alto</option>
                        <option value="EXCELENTE">Excelente</option>
                    </select>
                    <button class="px-3 py-2 bg-primary text-primary-foreground rounded-lg" @click="filtrar">Filtrar</button>
                    <button class="px-3 py-2 bg-secondary text-secondary-foreground rounded-lg" @click="limpiar">Limpiar</button>
                </div>

                <div class="flex justify-end">
                    <Link :href="route('informes-clase.create')" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg">Nuevo informe</Link>
                </div>

                <div class="bg-card border border-border rounded-xl p-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left">
                                <th class="py-2 pr-3">ID</th>
                                <th class="py-2 pr-3">Sesión</th>
                                <th class="py-2 pr-3">Servicio</th>
                                <th class="py-2 pr-3">Fecha y hora</th>
                                <th class="py-2 pr-3">Temas vistos</th>
                                <th class="py-2 pr-3">Desempeño</th>
                                <th class="py-2 pr-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="informe in informes.data" :key="informe.id_informe" class="border-b border-border/60">
                                <td class="py-2 pr-3">{{ informe.id_informe }}</td>
                                <td class="py-2 pr-3">
                                    #{{ informe.id_asistencia }}
                                    <span v-if="informe.asistencia?.sesion?.numero_sesion" class="text-muted-foreground">
                                        (Sesión {{ informe.asistencia.sesion.numero_sesion }})
                                    </span>
                                </td>
                                <td class="py-2 pr-3">
                                    {{ informe.asistencia?.sesion?.calendario?.servicio?.nombre ?? '-' }}
                                </td>
                                <td class="py-2 pr-3">
                                    {{ informe.asistencia?.sesion?.fecha_sesion ?? '-' }}
                                    <span
                                        v-if="informe.asistencia?.sesion?.hora_inicio || informe.asistencia?.sesion?.hora_fin"
                                        class="text-muted-foreground"
                                    >
                                        {{ informe.asistencia?.sesion?.hora_inicio ?? '--:--' }}-{{ informe.asistencia?.sesion?.hora_fin ?? '--:--' }}
                                    </span>
                                </td>
                                <td class="py-2 pr-3">{{ informe.temas_vistos || '-' }}</td>
                                <td class="py-2 pr-3">{{ informe.desempenio || '-' }}</td>
                                <td class="py-2 pr-3 space-x-2">
                                    <Link :href="route('informes-clase.edit', informe.id_informe)" class="px-3 py-1 bg-primary text-primary-foreground rounded">Editar</Link>
                                    <button class="px-3 py-1 bg-destructive text-destructive-foreground rounded" @click="eliminar(informe.id_informe)">Eliminar</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </UserLayout>
    </AppLayout>
</template>
