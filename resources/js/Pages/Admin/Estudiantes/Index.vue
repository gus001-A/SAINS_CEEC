<script setup>
import { computed, reactive, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { PlusOutlined, SearchOutlined, TeamOutlined, FileExcelOutlined } from '@ant-design/icons-vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import StatCard from '@/Components/StatCard.vue';
import RowActions from '@/Components/RowActions.vue';
import { confirmDelete } from '@/lib/notify';

const props = defineProps({
    estudiantes: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

function fecha(iso) {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
}

const state = reactive({
    search: props.filters.search ?? '',
    sexo: props.filters.sexo ?? undefined,
    plan_activo: props.filters.plan_activo ?? undefined,
    curp: props.filters.curp ?? '',
    documentos: props.filters.documentos ?? undefined,
    registro: props.filters.registro_desde || props.filters.registro_hasta
        ? [props.filters.registro_desde, props.filters.registro_hasta] : undefined,
});

const documentosOptions = [
    { value: 'sin', label: 'Sin documentos' },
    { value: 'faltan', label: 'Faltan documentos' },
    { value: 'rechazados', label: 'Con rechazados' },
    { value: 'revision', label: 'En revisión' },
    { value: 'autorizados', label: 'Autorizados' },
];

let debounce = null;
function reload(extra = {}) {
    router.get(route('admin.estudiantes.index'), {
        search: state.search || undefined,
        sexo: state.sexo || undefined,
        plan_activo: state.plan_activo ?? undefined,
        curp: state.curp || undefined,
        documentos: state.documentos || undefined,
        registro_desde: state.registro?.[0] || undefined,
        registro_hasta: state.registro?.[1] || undefined,
        ...extra,
    }, { preserveState: true, replace: true, preserveScroll: true });
}
watch([() => state.search, () => state.curp], () => { clearTimeout(debounce); debounce = setTimeout(() => reload(), 350); });
watch([() => state.sexo, () => state.plan_activo, () => state.documentos, () => state.registro], () => reload());

function eliminar(r) {
    confirmDelete({ title: r.sin_perfil ? '¿Eliminar usuario?' : '¿Eliminar estudiante?', content: r.sin_perfil ? `${r.curp} no terminó su registro. Se eliminará su cuenta.` : r.nombre_completo, onOk: () => router.delete(route('admin.estudiantes.destroy', r.id), { preserveScroll: true }) });
}

const pagination = computed(() => ({
    current: props.estudiantes.current_page,
    pageSize: props.estudiantes.per_page,
    total: props.estudiantes.total,
    showSizeChanger: false,
    showTotal: (t) => `${t} estudiantes`,
}));
function onChange(pag) { reload({ page: pag.current }); }

const columns = [
    { title: 'Estudiante', key: 'nombre' },
    { title: 'Documentos', key: 'documentos', width: 170, align: 'center' },
    { title: 'Plan', key: 'plan', width: 130 },
    { title: 'Registro', key: 'registro', width: 150, align: 'center', responsive: ['lg'] },
    { title: '', key: 'acciones', width: 120, align: 'right' },
];
</script>

<template>
    <AdminLayout title="Estudiantes">
        <PageHead title="Estudiantes" subtitle="Alumnos registrados en la plataforma" :icon="TeamOutlined">
            <template #actions>
                <a-button :href="route('admin.estudiantes.exportar.excel')">
                    <template #icon><FileExcelOutlined /></template>Exportar
                </a-button>
                <Link :href="route('admin.estudiantes.create')">
                    <a-button type="primary"><template #icon><PlusOutlined /></template>Nuevo estudiante</a-button>
                </Link>
            </template>
        </PageHead>

        <div class="sains-stats sains-stats--4">
            <StatCard label="Total" :value="stats.total" color="indigo" />
            <StatCard label="Con plan activo" :value="stats.activos" color="green" />
            <StatCard label="Sin plan" :value="stats.inactivos" color="slate" />
            <StatCard label="Con cupón" :value="stats.conCupon" color="pink" />
        </div>

        <a-card :bordered="false">
            <a-table class="filtered-table" :columns="columns" :data-source="estudiantes.data" :pagination="pagination" row-key="id" size="middle" :scroll="{ x: 700 }" @change="onChange">
                <template #summary>
                    <a-table-summary-row>
                        <a-table-summary-cell v-for="(col, i) in columns" :key="col.key ?? i" :index="i">
                            <a-input v-if="col.key === 'nombre'" v-model:value="state.search" size="small" allow-clear placeholder="Nombre, CURP…">
                                <template #prefix><SearchOutlined /></template>
                            </a-input>
                            <a-input v-else-if="col.key === 'curp'" v-model:value="state.curp" size="small" allow-clear placeholder="CURP…">
                                <template #prefix><SearchOutlined /></template>
                            </a-input>
                            <a-range-picker v-else-if="col.key === 'registro'" v-model:value="state.registro" size="small" value-format="YYYY-MM-DD"
                                format="DD/MM/YYYY" :placeholder="['Desde', 'Hasta']" style="width: 100%" />
                            <a-select v-else-if="col.key === 'documentos'" v-model:value="state.documentos" size="small" allow-clear placeholder="Todos"
                                :options="documentosOptions" style="width: 100%" />
                            <a-select v-else-if="col.key === 'plan'" v-model:value="state.plan_activo" size="small" allow-clear placeholder="Todos"
                                :options="[{ value: 1, label: 'Activo' }, { value: 0, label: 'Sin plan' }]" />
                        </a-table-summary-cell>
                    </a-table-summary-row>
                </template>

                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'nombre'">
                        <div style="font-weight:600">{{ record.nombre_completo }}</div>
                        <div v-if="record.curp" class="sains-faint" style="font-size:12px;letter-spacing:.02em">{{ record.curp }}</div>
                        <a-tag v-if="record.sin_perfil" color="orange" style="margin-top:2px">Perfil incompleto</a-tag>
                    </template>
                    <template v-else-if="column.key === 'registro'">{{ fecha(record.fecha_registro) }}</template>
                    <template v-else-if="column.key === 'documentos'">
                        <a-tooltip :title="record.documentos_estado?.detalle">
                            <a-tag :color="record.documentos_estado?.color" style="white-space:normal;line-height:1.35;padding:2px 8px;margin:0">
                                {{ record.documentos_estado?.texto }}
                            </a-tag>
                        </a-tooltip>
                    </template>
                    <template v-else-if="column.key === 'plan'">
                        <a-tag :color="record.plan_activo ? 'green' : 'default'">{{ record.plan_activo ? 'Activo' : 'Sin plan' }}</a-tag>
                        <a-tag v-if="record.cupon" color="blue">{{ record.cupon }}</a-tag>
                    </template>
                    <template v-else-if="column.key === 'acciones'">
                        <RowActions
                            :view-href="route('admin.estudiantes.show', record.id)"
                            :edit-href="record.sin_perfil ? null : route('admin.estudiantes.edit', record.id)"
                            @delete="eliminar(record)"
                        />
                    </template>
                </template>
            </a-table>
        </a-card>
    </AdminLayout>
</template>
