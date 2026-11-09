<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import {
    IdcardOutlined, CalendarOutlined, ManOutlined,
    LockOutlined, CrownOutlined, TagOutlined, ThunderboltOutlined,
    FilePdfOutlined, CloudUploadOutlined, DeleteOutlined, EyeOutlined, FolderOpenOutlined,
    CheckCircleFilled,
} from '@ant-design/icons-vue';
import DateField from '@/Components/DateField.vue';
import CamposIssfam from '@/Components/CamposIssfam.vue';
import { message } from '@/lib/notify';
import { generarContrasena, estadoConfirmacion } from '@/lib/password';
import { normalizarCurp, errorCurp, consultarCurp } from '@/lib/curp';

const props = defineProps({
    form: { type: Object, required: true },
    opciones: { type: Object, required: true },
    modo: { type: String, default: 'create' }, // create | edit
    tiposDocumento: { type: Object, default: null }, // { tipo: etiqueta } → muestra la sección de documentos
});

const sexoOpts = [{ value: 'M', label: 'Masculino' }, { value: 'F', label: 'Femenino' }];
const cuponOpts = props.opciones.cupones.map((c) => ({ value: c.id, label: c.label }));

const err = (f) => (props.form.errors[f] ? 'error' : undefined);

/* ---------- CURP (usuario de la cuenta) ---------- */
// En el alta se valida contra RENAPO y se autocompletan los datos personales.
const curpAviso = ref({ status: undefined, help: '' });
let consulta = 0;
watch(() => props.form.curp, async (curp) => {
    const id = ++consulta;
    if (!curp || curp.length < 18) {
        curpAviso.value = { status: undefined, help: curp ? `${curp.length}/18 caracteres` : '' };
        return;
    }
    const error = errorCurp(curp);
    if (error) {
        curpAviso.value = { status: 'error', help: error };
        return;
    }
    if (props.modo !== 'create') {
        curpAviso.value = { status: 'success', help: '' };
        return;
    }
    curpAviso.value = { status: 'validating', help: 'Validando CURP…' };
    try {
        const r = await consultarCurp(curp);
        if (id !== consulta) return;
        if (!r.valida) {
            curpAviso.value = { status: 'error', help: r.mensaje };
            return;
        }
        const d = r.datos;
        for (const k of ['nombre', 'paterno', 'materno', 'fecha_nacimiento', 'sexo']) {
            if (d[k]) props.form[k] = d[k];
        }
        curpAviso.value = {
            status: 'success',
            help: r.fuente === 'renapo' ? 'CURP verificada en RENAPO; datos autocompletados.' : 'CURP válida.',
        };
    } catch (e) {
        if (id === consulta) curpAviso.value = { status: 'warning', help: 'No se pudo consultar la CURP; captura los datos a mano.' };
    }
}, { immediate: props.modo === 'create' });

/* ---------- Contraseña ---------- */
const verPassword = ref(false);
const confirmacion = computed(() => estadoConfirmacion(
    props.form.password, props.form.password_confirmation, props.form.errors.password_confirmation,
));

async function generar() {
    const nueva = generarContrasena(10);
    props.form.password = nueva;
    props.form.password_confirmation = nueva;
    verPassword.value = true;
    try {
        await navigator.clipboard.writeText(nueva);
        message.success('Contraseña generada y copiada al portapapeles');
    } catch (e) {
        message.success('Contraseña generada; anótala para compartirla con el estudiante');
    }
}

/* ---------- Documentos (solo en alta) ---------- */
const MAX_MB = 5;
const descripciones = {
    acta_nacimiento: 'Acta de nacimiento legible y completa',
    curp: 'Formato actualizado de la CURP',
    certificado_secundaria: 'Certificado oficial de secundaria',
    ine: 'Identificación oficial por ambos lados',
};
const docs = computed(() => Object.entries(props.tiposDocumento || {}).map(([tipo, label]) => ({ tipo, label })));
const vistas = reactive({}); // tipo → URL temporal para previsualizar
const arrastrando = ref(null);
const inputs = {};

function asignar(tipo, file) {
    if (!file) return;
    const esPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    if (!esPdf) {
        message.error('Solo se permiten archivos PDF');
        return;
    }
    if (file.size > MAX_MB * 1024 * 1024) {
        message.error(`El archivo pesa ${(file.size / 1048576).toFixed(1)} MB; el máximo es ${MAX_MB} MB`);
        return;
    }
    quitar(tipo);
    props.form.documentos[tipo] = file;
    vistas[tipo] = URL.createObjectURL(file);
}
function quitar(tipo) {
    if (vistas[tipo]) URL.revokeObjectURL(vistas[tipo]);
    delete vistas[tipo];
    props.form.documentos[tipo] = null;
    if (inputs[tipo]) inputs[tipo].value = '';
}
function onDrop(tipo, ev) {
    arrastrando.value = null;
    asignar(tipo, ev.dataTransfer?.files?.[0]);
}
const peso = (b) => (b >= 1048576 ? `${(b / 1048576).toFixed(1)} MB` : `${Math.ceil(b / 1024)} KB`);
const cargados = computed(() => docs.value.filter((d) => props.form.documentos?.[d.tipo]).length);
const errDoc = (tipo) => props.form.errors[`documentos.${tipo}`];

onBeforeUnmount(() => Object.values(vistas).forEach((u) => URL.revokeObjectURL(u)));
</script>

<template>
    <a-form layout="vertical" class="sains-form-inner">
        <a-divider orientation="left" style="margin-top: 0"><span class="fsec"><IdcardOutlined />Datos personales</span></a-divider>
        <a-row :gutter="16">
            <a-col :xs="24" :md="8">
                <a-form-item required :validate-status="form.errors.curp ? 'error' : curpAviso.status"
                    :help="form.errors.curp || curpAviso.help || 'Es su usuario para iniciar sesión.'" has-feedback>
                    <template #label><IdcardOutlined />CURP</template>
                    <a-input :value="form.curp" :maxlength="18" class="curp-input" placeholder="18 caracteres"
                        @update:value="(v) => (form.curp = normalizarCurp(v))" />
                </a-form-item>
            </a-col>
        </a-row>
        <a-row :gutter="16">
            <a-col :xs="24" :md="8">
                <a-form-item label="Nombre(s)" required :validate-status="err('nombre')" :help="form.errors.nombre">
                    <a-input v-model:value="form.nombre"><template #prefix><IdcardOutlined /></template></a-input>
                </a-form-item>
            </a-col>
            <a-col :xs="24" :md="8">
                <a-form-item label="Apellido paterno" required :validate-status="err('paterno')" :help="form.errors.paterno">
                    <a-input v-model:value="form.paterno" />
                </a-form-item>
            </a-col>
            <a-col :xs="24" :md="8">
                <a-form-item label="Apellido materno" :validate-status="err('materno')" :help="form.errors.materno">
                    <a-input v-model:value="form.materno" />
                </a-form-item>
            </a-col>
            <a-col :xs="24" :md="8">
                <a-form-item :validate-status="err('fecha_nacimiento')" :help="form.errors.fecha_nacimiento || 'El estudiante debe tener al menos 15 años'">
                    <template #label><CalendarOutlined />Fecha de nacimiento</template>
                    <DateField v-model:value="form.fecha_nacimiento" limite="nacimiento-estudiante" />
                </a-form-item>
            </a-col>
            <a-col :xs="24" :md="8">
                <a-form-item required :validate-status="err('sexo')" :help="form.errors.sexo">
                    <template #label><ManOutlined />Sexo</template>
                    <a-select v-model:value="form.sexo" :options="sexoOpts" placeholder="Selecciona…" />
                </a-form-item>
            </a-col>
        </a-row>

        <CamposIssfam :form="form" :entidades="opciones.entidades || []" :requerido="false" :mostrar-curp="false" />

        <a-divider orientation="left"><span class="fsec"><LockOutlined />Cuenta y plan</span></a-divider>
        <a-row :gutter="16">
            <a-col :xs="24" :md="12">
                <a-form-item :required="modo === 'create'"
                    :validate-status="err('password')" :help="form.errors.password || (modo === 'edit' ? 'Deja en blanco para no cambiarla' : '')">
                    <template #label><LockOutlined />{{ modo === 'edit' ? 'Nueva contraseña' : 'Contraseña' }}</template>
                    <a-input-password v-model:value="form.password" v-model:visible="verPassword" autocomplete="new-password" placeholder="Mínimo 6 caracteres" />
                </a-form-item>
            </a-col>
            <a-col :xs="24" :md="12">
                <a-form-item :required="modo === 'create'" :validate-status="confirmacion.status" :help="confirmacion.help" has-feedback>
                    <template #label><LockOutlined />Confirmar contraseña</template>
                    <a-input-password v-model:value="form.password_confirmation" v-model:visible="verPassword" autocomplete="new-password" placeholder="Repite la contraseña" />
                </a-form-item>
            </a-col>
        </a-row>

        <div class="pw-tools">
            <a-button @click="generar"><template #icon><ThunderboltOutlined /></template>Generar contraseña segura</a-button>
            <span>Crea una contraseña de 10 caracteres, la llena en ambos campos y la copia para compartirla con el estudiante.</span>
        </div>

        <div class="plan-box">
            <div class="plan-box__item">
                <div class="plan-box__icon"><CrownOutlined /></div>
                <div class="plan-box__text">
                    <b>Plan Premium</b>
                    <small>{{ form.plan_activo ? 'Tendrá acceso a todo el contenido' : 'Sin acceso al contenido Premium' }}</small>
                </div>
                <a-switch v-model:checked="form.plan_activo" checked-children="Activo" un-checked-children="Inactivo" />
            </div>
            <div class="plan-box__sep" aria-hidden="true"></div>
            <div class="plan-box__item plan-box__item--grow">
                <div class="plan-box__icon plan-box__icon--tag"><TagOutlined /></div>
                <a-form-item class="plan-box__field" :validate-status="err('cupon_id')" :help="form.errors.cupon_id">
                    <template #label>Aplicar cupón (opcional)</template>
                    <a-select v-model:value="form.cupon_id" allow-clear show-search option-filter-prop="label" :options="cuponOpts" placeholder="Sin cupón" />
                </a-form-item>
            </div>
        </div>

        <template v-if="docs.length">
            <a-divider orientation="left"><span class="fsec"><FolderOpenOutlined />Documentos</span></a-divider>
            <div class="docs-head">
                <p>Sube en PDF (máx. {{ MAX_MB }} MB cada uno) los documentos que se le solicitan al estudiante. Son opcionales: también podrá subirlos desde su cuenta.</p>
                <span class="docs-count" :class="{ 'is-full': cargados === docs.length }">{{ cargados }} de {{ docs.length }} cargados</span>
            </div>

            <div class="docs-grid">
                <div v-for="d in docs" :key="d.tipo" class="doc"
                    :class="{ 'has-file': form.documentos[d.tipo], 'is-drag': arrastrando === d.tipo, 'has-err': errDoc(d.tipo) }"
                    @dragover.prevent="arrastrando = d.tipo" @dragleave.prevent="arrastrando = null" @drop.prevent="onDrop(d.tipo, $event)">
                    <input :ref="(el) => (inputs[d.tipo] = el)" type="file" accept="application/pdf,.pdf" hidden
                        @change="asignar(d.tipo, $event.target.files[0])" />

                    <div class="doc__icon">
                        <CheckCircleFilled v-if="form.documentos[d.tipo]" />
                        <FilePdfOutlined v-else />
                    </div>
                    <div class="doc__body">
                        <b>{{ d.label }}</b>
                        <template v-if="form.documentos[d.tipo]">
                            <span class="doc__file" :title="form.documentos[d.tipo].name">{{ form.documentos[d.tipo].name }}</span>
                            <small>{{ peso(form.documentos[d.tipo].size) }}</small>
                        </template>
                        <small v-else>{{ descripciones[d.tipo] || 'Archivo PDF' }} · o arrástralo aquí</small>
                        <small v-if="errDoc(d.tipo)" class="doc__err">{{ errDoc(d.tipo) }}</small>
                    </div>
                    <div class="doc__actions">
                        <template v-if="form.documentos[d.tipo]">
                            <a-tooltip title="Ver"><a-button type="text" size="small" :href="vistas[d.tipo]" target="_blank"><EyeOutlined /></a-button></a-tooltip>
                            <a-tooltip title="Quitar"><a-button type="text" size="small" danger @click="quitar(d.tipo)"><DeleteOutlined /></a-button></a-tooltip>
                        </template>
                        <a-button v-else size="small" @click="inputs[d.tipo]?.click()"><CloudUploadOutlined /> Subir</a-button>
                    </div>
                </div>
            </div>

            <div v-if="cargados" class="docs-auth">
                <a-switch v-model:checked="form.documentos_autorizados" size="small" />
                <span>Marcar como <b>autorizados</b> los documentos cargados (si no, quedan pendientes de revisión)</span>
            </div>
        </template>
    </a-form>
</template>

<style scoped>
.curp-input, .curp-input :deep(input) { text-transform: uppercase; letter-spacing: .04em; }
.pw-tools {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    margin: -6px 0 18px; padding: 10px 14px; border-radius: 12px;
    background: #f6f8fc; border: 1px solid var(--sains-line);
}
.pw-tools :deep(.ant-btn) { flex: none; width: auto; }
.pw-tools > span { font-size: 12.5px; color: var(--sains-muted); flex: 1; min-width: 220px; }

.plan-box {
    display: flex; align-items: stretch; gap: 18px; padding: 14px 18px; border-radius: 14px;
    border: 1px solid var(--sains-line); background: #fff;
}
.plan-box__item { display: flex; align-items: center; gap: 12px; }
.plan-box__item--grow { flex: 1; min-width: 0; }
.plan-box__sep { width: 1px; background: var(--sains-line); }
.plan-box__icon {
    flex: none; width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center;
    font-size: 18px; background: #fef3c7; color: #b45309;
}
.plan-box__icon--tag { background: #e0eaf9; color: var(--sains-primary); }
.plan-box__text { display: flex; flex-direction: column; line-height: 1.3; min-width: 170px; }
.plan-box__text b { font-size: 13.5px; color: var(--sains-ink); }
.plan-box__text small { font-size: 12px; color: var(--sains-muted); }
.plan-box__field { flex: 1; min-width: 0; margin-bottom: 0; }
.plan-box__field :deep(.ant-form-item-label) { padding-bottom: 2px; }
.plan-box__field :deep(.ant-form-item-label > label) { font-size: 12.5px; color: var(--sains-muted); }

.docs-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 12px; }
.docs-head p { margin: 0; font-size: 13px; color: var(--sains-muted); }
.docs-count {
    flex: none; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600;
    background: #eef2f8; color: var(--sains-muted);
}
.docs-count.is-full { background: #dcfce7; color: #15803d; }
.docs-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.doc {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 12px;
    border: 1.5px dashed #cfd8e6; background: #fafbfd; transition: border-color .15s, background .15s;
}
.doc:hover, .doc.is-drag { border-color: var(--sains-primary); background: #f3f7fe; }
.doc.has-file { border-style: solid; border-color: #bbf7d0; background: #f0fdf4; }
.doc.has-err { border-color: #fca5a5; background: #fef2f2; }
.doc__icon {
    flex: none; width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center;
    font-size: 20px; background: #fee2e2; color: #dc2626;
}
.doc.has-file .doc__icon { background: #dcfce7; color: #16a34a; }
.doc__body { flex: 1; min-width: 0; display: flex; flex-direction: column; line-height: 1.35; }
.doc__body b { font-size: 13.5px; color: var(--sains-ink); }
.doc__body small { font-size: 12px; color: var(--sains-muted); }
.doc__file { font-size: 12.5px; color: var(--sains-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.doc__err { color: #dc2626 !important; }
.doc__actions { flex: none; display: flex; gap: 2px; }
.docs-auth { display: flex; align-items: center; gap: 10px; margin-top: 14px; font-size: 13px; color: var(--sains-ink); }

@media (max-width: 768px) {
    .docs-grid { grid-template-columns: 1fr; }
    .plan-box { flex-direction: column; gap: 12px; }
    .plan-box__sep { width: auto; height: 1px; }
    .docs-head { flex-direction: column; gap: 8px; }
}
</style>
