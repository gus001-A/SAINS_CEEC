<script setup>
import { useForm, router } from '@inertiajs/vue3';
import { TeamOutlined } from '@ant-design/icons-vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import FormPage from '@/Components/FormPage.vue';
import EstudianteForm from './EstudianteForm.vue';
import { message } from '@/lib/notify';

const props = defineProps({
    opciones: { type: Object, required: true },
    tiposDocumento: { type: Object, default: () => ({}) },
});

const form = useForm({
    nombre: '',
    paterno: '',
    materno: '',
    fecha_nacimiento: null,
    sexo: undefined,
    password: '',
    password_confirmation: '',
    plan_activo: false,
    cupon_id: undefined,
    curp: '', calle_numero: '',
    colonia: '', codigo_postal: '', municipio: '',
    entidad_federativa: undefined,
    documentos: { acta_nacimiento: null, curp: null, certificado_secundaria: null, ine: null },
    documentos_autorizados: false,
});

const back = () => router.visit(route('admin.estudiantes.index'));
function submit() {
    if (form.password !== form.password_confirmation) {
        message.warning('Las contraseñas no coinciden.');
        return;
    }
    form.post(route('admin.estudiantes.store'), { forceFormData: true });
}
</script>

<template>
    <AdminLayout title="Nuevo estudiante">
        <PageHead title="Nuevo estudiante" subtitle="Registrar un alumno en la plataforma" :icon="TeamOutlined" back @back="back" />
        <FormPage submit-label="Registrar estudiante" :processing="form.processing" @submit="submit" @cancel="back">
            <EstudianteForm :form="form" :opciones="opciones" :tipos-documento="tiposDocumento" modo="create" />
        </FormPage>
    </AdminLayout>
</template>
