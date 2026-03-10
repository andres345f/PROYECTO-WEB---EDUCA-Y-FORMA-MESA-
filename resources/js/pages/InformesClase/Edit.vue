<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import UserLayout from '@/layouts/UserLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Sesion {
    id: number;
    fecha_sesion: string;
    numero_sesion: number;
    hora_inicio?: string | null;
    hora_fin?: string | null;
    servicio_nombre?: string | null;
}

interface Informe {
    id_informe: number;
    id_asistencia: number;
    temas_vistos?: string | null;
    tareas_asignadas?: string | null;
    desempenio?: string | null;
}

const props = defineProps<{ informe: Informe; sesiones: Sesion[] }>();

const form = useForm({
    id_asistencia: String(props.informe.id_asistencia),
    temas_vistos: props.informe.temas_vistos ?? '',
    tareas_asignadas: props.informe.tareas_asignadas ?? '',
    desempenio: props.informe.desempenio ?? '',
});

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Informes de clase', href: '/informes-clase' },
    { title: 'Editar', href: `/informes-clase/${props.informe.id_informe}/edit` },
];

const submit = () => form.put(route('informes-clase.update', props.informe.id_informe));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Editar informe" />
        <UserLayout>
            <div class="max-w-3xl mx-auto bg-card border border-border rounded-xl p-5">
                <h1 class="text-xl font-bold mb-4">Editar informe de clase</h1>
                <form class="space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm mb-1">Sesión programada</label>
                        <select v-model="form.id_asistencia" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                            <option value="">Seleccione</option>
                            <option v-for="sesion in sesiones" :key="sesion.id" :value="String(sesion.id)">
                                #{{ sesion.id }} - {{ sesion.servicio_nombre ?? 'Servicio sin nombre' }} | {{ sesion.fecha_sesion }} {{ sesion.hora_inicio ?? '--:--' }}-{{ sesion.hora_fin ?? '--:--' }} (Sesión {{ sesion.numero_sesion }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Temas vistos</label>
                        <textarea v-model="form.temas_vistos" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Tareas asignadas</label>
                        <textarea v-model="form.tareas_asignadas" class="w-full px-3 py-2 border border-border rounded-lg bg-card" />
                    </div>
                    <div>
                        <label class="block text-sm mb-1">Desempeño</label>
                        <select v-model="form.desempenio" class="w-full px-3 py-2 border border-border rounded-lg bg-card">
                            <option value="">Sin definir</option>
                            <option value="BAJO">Bajo</option>
                            <option value="MEDIO">Medio</option>
                            <option value="ALTO">Alto</option>
                            <option value="EXCELENTE">Excelente</option>
                        </select>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <Link :href="route('informes-clase.index')" class="px-4 py-2 bg-secondary text-secondary-foreground rounded-lg">Cancelar</Link>
                        <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-lg" :disabled="form.processing">Actualizar</button>
                    </div>
                </form>
            </div>
        </UserLayout>
    </AppLayout>
</template>
