<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { TeamOutlined } from '@ant-design/icons-vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import FormPage from '@/Components/FormPage.vue';
import EstudianteForm from './EstudianteForm.vue';
import { message } from '@/lib/notify';

const props = defineProps({
    estudiante: { type: Object, required: true },
    opciones: { type: Object, required: true },
});

const e = props.estudiante;
const form = useForm({
    nombre: e.nombre ?? '',
    paterno: e.paterno ?? '',
    materno: e.materno ?? '',
    fecha_nacimiento: e.fecha_nacimiento,
    sexo: e.sexo || undefined,
    password: '',
    password_confirmation: '',
    plan_activo: !!e.plan_activo,
    cupon_id: undefined,
    curp: e.curp ?? '', calle_numero: e.calle_numero ?? '',
    colonia: e.colonia ?? '', codigo_postal: e.codigo_postal ?? '', municipio: e.municipio ?? '',
    entidad_federativa: e.entidad_federativa ?? undefined,
});

const back = () => router.visit(route('admin.estudiantes.index'));
function submit() {
    if (form.password !== form.password_confirmation) {
        message.warning('Las contraseñas no coinciden.');
        return;
    }
    form.put(route('admin.estudiantes.update', e.id));
}
</script>

<template>
    <AdminLayout title="Editar estudiante">
        <PageHead :title="`Editar: ${e.nombre} ${e.paterno}`" subtitle="Actualizar datos del alumno" :icon="TeamOutlined" back @back="back" />
        <FormPage submit-label="Guardar cambios" :processing="form.processing" @submit="submit" @cancel="back">
            <a-alert v-if="e.cupon" type="info" show-icon style="margin-bottom: 16px"
                :message="`Cupón aplicado actualmente: ${e.cupon}`" />
            <EstudianteForm :form="form" :opciones="opciones" modo="edit" />
        </FormPage>
    </AdminLayout>
</template>
