<script setup>
import { useForm } from '@inertiajs/vue3';
import { UserOutlined } from '@ant-design/icons-vue';
import EstudianteLayout from '@/Layouts/EstudianteLayout.vue';
import DateField from '@/Components/DateField.vue';
import CamposIssfam from '@/Components/CamposIssfam.vue';
import { message } from '@/lib/notify';

const props = defineProps({
    curp: { type: String, default: '' },
    // Datos obtenidos de la CURP; 'bloqueado' = verificados en RENAPO (no editables).
    datosCurp: { type: Object, default: () => ({ bloqueado: false }) },
    entidades: { type: Array, default: () => [] },
});

const bloqueado = !!props.datosCurp.bloqueado;

const form = useForm({
    nombre: props.datosCurp.nombre ?? '', paterno: props.datosCurp.paterno ?? '', materno: props.datosCurp.materno ?? '',
    fecha_nacimiento: props.datosCurp.fecha_nacimiento ?? null, sexo: props.datosCurp.sexo ?? undefined,
    cupon: '',
    curp: props.curp, calle_numero: '', colonia: '',
    codigo_postal: '', municipio: '', entidad_federativa: undefined,
});

function submit() {
    const obligatorios = ['nombre', 'paterno', 'fecha_nacimiento', 'sexo',
        'calle_numero', 'colonia', 'codigo_postal', 'municipio', 'entidad_federativa'];
    if (obligatorios.some((k) => !form[k])) {
        message.warning('Completa los campos obligatorios.');
        return;
    }
    form.post(route('estudiante.completar.perfil'), {
        onError: () => message.error('Revisa los campos marcados en rojo.'),
    });
}
</script>

<template>
    <EstudianteLayout title="Completar perfil">
        <section class="sains-hero">
            <div class="sains-hero__grid">
                <div>
                    <span class="sains-hero__eyebrow"><UserOutlined /> Bienvenido</span>
                    <h1 class="sains-hero__title">¡Casi listo!</h1>
                    <p class="sains-hero__sub">Completa estos datos una sola vez para comenzar tu preparación.</p>
                </div>
            </div>
        </section>

        <!-- Sin max-width: ahora ocupa lo mismo que el hero -->
        <a-card :bordered="false">
            <a-form layout="vertical">
                <a-divider orientation="left">Información personal</a-divider>
                <a-row :gutter="16">
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Nombre(s)" required :validate-status="form.errors.nombre ? 'error' : ''" :help="form.errors.nombre">
                            <a-input v-model:value="form.nombre" placeholder="Ej: Juan Carlos" :disabled="bloqueado" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Apellido paterno" required :validate-status="form.errors.paterno ? 'error' : ''" :help="form.errors.paterno">
                            <a-input v-model:value="form.paterno" placeholder="Ej: Rodríguez" :disabled="bloqueado" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Apellido materno">
                            <a-input v-model:value="form.materno" placeholder="Ej: Pérez" :disabled="bloqueado" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="CURP" :validate-status="form.errors.curp ? 'error' : ''"
                            :help="form.errors.curp || (bloqueado ? 'Datos verificados en RENAPO.' : 'Es la CURP con la que inicias sesión.')">
                            <a-input :value="curp" disabled style="letter-spacing: .04em" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Fecha de nacimiento" required :validate-status="form.errors.fecha_nacimiento ? 'error' : ''" :help="form.errors.fecha_nacimiento">
                            <DateField v-model:value="form.fecha_nacimiento" limite="nacimiento" :disabled="!!datosCurp.fecha_nacimiento" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Sexo" required :validate-status="form.errors.sexo ? 'error' : ''" :help="form.errors.sexo">
                            <a-select v-model:value="form.sexo" placeholder="Selecciona" :disabled="!!datosCurp.sexo" :options="[
                                { value: 'M', label: 'Masculino' },
                                { value: 'F', label: 'Femenino' },
                            ]" />
                        </a-form-item>
                    </a-col>
                    <a-col :xs="24" :sm="12">
                        <a-form-item label="Cupón de descuento (opcional)" :validate-status="form.errors.cupon ? 'error' : ''" :help="form.errors.cupon">
                            <a-input v-model:value="form.cupon" placeholder="Ej: SAINS2026" />
                        </a-form-item>
                    </a-col>
                </a-row>

                <!-- CamposIssfam: domicilio (la CURP se pide arriba) -->
                <CamposIssfam
                    :form="form"
                    :entidades="entidades"
                    :mostrar-curp="false"
                />

                <div class="sains-section-actions">
                    <a-button type="primary" size="large" :loading="form.processing" @click="submit">Guardar perfil</a-button>
                </div>
            </a-form>
        </a-card>
    </EstudianteLayout>
</template>