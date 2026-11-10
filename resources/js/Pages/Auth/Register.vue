<script setup>
import { ref, computed, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import {
    IdcardOutlined, LockOutlined, UserAddOutlined, LoginOutlined,
    CheckCircleFilled, UserOutlined, CalendarOutlined, MailOutlined,
} from '@ant-design/icons-vue';
import AuthShell from '@/Components/AuthShell.vue';
import PhoneInput from '@/Components/PhoneInput.vue';
import { message } from '@/lib/notify';
import { estadoConfirmacion } from '@/lib/password';
import { normalizarCurp, errorCurp, consultarCurp } from '@/lib/curp';

const form = useForm({
    curp: '', nombre: '', paterno: '', materno: '',
    correo: '', telefono: '',
    password: '', password_confirmation: '',
});
const acepta = ref(false);

// ===== Validación de la CURP en tiempo real =====
// estado: '' (sin capturar) | 'validando' | 'ok' | 'error'
const curpEstado = ref('');
const curpMensaje = ref('');
const curpDatos = ref(null);   // { nombre, paterno, materno, fecha_nacimiento, sexo }
const deRenapo = ref(false);   // true: los datos vienen de RENAPO y no se editan
let consulta = 0;
let espera;

function limpiarDatos() {
    curpDatos.value = null;
    deRenapo.value = false;
    form.nombre = form.paterno = form.materno = '';
}

watch(() => form.curp, (curp) => {
    clearTimeout(espera);
    consulta++;
    form.clearErrors('curp');
    limpiarDatos();

    if (curp.length < 18) {
        curpEstado.value = '';
        curpMensaje.value = curp ? `${curp.length}/18 caracteres` : '';
        return;
    }

    const error = errorCurp(curp);
    if (error) {
        curpEstado.value = 'error';
        curpMensaje.value = error;
        return;
    }

    curpEstado.value = 'validando';
    curpMensaje.value = 'Validando tu CURP…';
    const id = consulta;
    espera = setTimeout(async () => {
        try {
            const r = await consultarCurp(curp);
            if (id !== consulta) return;
            if (!r.valida) {
                curpEstado.value = 'error';
                curpMensaje.value = r.mensaje || 'La CURP no es válida.';
                return;
            }
            curpDatos.value = r.datos;
            deRenapo.value = r.fuente === 'renapo';
            form.nombre = r.datos.nombre || '';
            form.paterno = r.datos.paterno || '';
            form.materno = r.datos.materno || '';
            curpEstado.value = 'ok';
            curpMensaje.value = deRenapo.value
                ? 'CURP verificada en RENAPO.'
                : 'CURP válida. Escribe tu nombre como aparece en tu CURP.';
        } catch (e) {
            if (id !== consulta) return;
            curpEstado.value = 'error';
            curpMensaje.value = e.response?.status === 429
                ? 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'
                : 'No pudimos validar tu CURP. Revisa tu conexión e inténtalo de nuevo.';
        }
    }, 350);
});

const curpStatus = computed(() => {
    if (form.errors.curp) return 'error';
    return { validando: 'validating', ok: 'success', error: 'error' }[curpEstado.value] || '';
});
const curpHelp = computed(() => form.errors.curp || curpMensaje.value);

const nombreCompleto = computed(() => [form.nombre, form.paterno, form.materno]
    .map((p) => (p || '').trim()).filter(Boolean).join(' '));

const fechaNacimiento = computed(() => {
    const f = curpDatos.value?.fecha_nacimiento;
    if (!f) return '';
    const [a, m, d] = f.split('-').map(Number);
    return new Date(a, m - 1, d).toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
});

const mayus = (v) => String(v ?? '').toUpperCase();

// ===== Contraseña =====
const fuerza = computed(() => {
    const p = form.password || '';
    let s = 0;
    if (p.length >= 6) s++;
    if (p.length >= 10) s++;
    if (/[0-9]/.test(p) && /[a-zA-Z]/.test(p)) s++;
    if (/[^a-zA-Z0-9]/.test(p)) s++;
    return Math.min(s, 4);
});
const fuerzaTxt = computed(() => ['', 'Débil', 'Aceptable', 'Buena', 'Excelente'][fuerza.value]);
const fuerzaColor = computed(() => ['#e2e8f0', '#ef4444', '#f59e0b', '#2a8ad6', '#16a34a'][fuerza.value]);

const confirmacion = computed(() => estadoConfirmacion(
    form.password, form.password_confirmation, form.errors.password_confirmation,
));

function submit() {
    if (curpEstado.value !== 'ok') {
        message.warning(curpEstado.value === 'validando'
            ? 'Espera un momento, estamos validando tu CURP.'
            : 'Ingresa una CURP válida.');
        return;
    }
    if (!form.nombre.trim() || !form.paterno.trim()) {
        message.warning('Escribe tu nombre y apellido paterno.');
        return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.correo.trim())) {
        message.warning('Escribe un correo electrónico válido.');
        return;
    }
    if (String(form.telefono).length !== 10) {
        message.warning('Escribe tu teléfono celular a 10 dígitos.');
        return;
    }
    if (form.password !== form.password_confirmation) {
        message.warning('Las contraseñas no coinciden.');
        return;
    }
    if (!acepta.value) {
        message.warning('Debes aceptar los términos y condiciones.');
        return;
    }
    form.post(route('register'), {
        onSuccess: () => message.success('¡Cuenta creada! Completa tu perfil para empezar.'),
        onError: (e) => {
            form.reset('password', 'password_confirmation');
            message.error(e.curp || e.nombre || e.paterno || e.correo || e.telefono || e.password || 'No pudimos crear tu cuenta. Revisa los datos.');
        },
    });
}
</script>

<template>
    <AuthShell title="Crear cuenta" class="auth-shell-custom">
        <template #title>Crea tu cuenta gratis</template>
        <template #subtitle>Empieza tu preparación en minutos</template>

        <a-form layout="vertical" class="au-form" @submitcapture.prevent>
            <a-form-item :validate-status="curpStatus" :help="curpHelp" has-feedback>
                <a-input :value="form.curp" size="large" placeholder="CURP" autocomplete="username" :maxlength="18"
                    class="au-curp" @update:value="(v) => (form.curp = normalizarCurp(v))">
                    <template #prefix><IdcardOutlined /></template>
                </a-input>
            </a-form-item>

            <!-- Datos obtenidos de la CURP -->
            <div v-if="curpDatos" class="au-datos" :class="{ 'is-renapo': deRenapo }">
                <template v-if="deRenapo">
                    <div class="au-datos__row">
                        <UserOutlined />
                        <div><small>Nombre completo</small><b>{{ nombreCompleto }}</b></div>
                    </div>
                </template>
                <template v-else>
                    <a-form-item :validate-status="form.errors.nombre ? 'error' : ''" :help="form.errors.nombre" class="au-datos__item">
                        <a-input :value="form.nombre" placeholder="Nombre(s)" autocomplete="given-name"
                            @update:value="(v) => (form.nombre = mayus(v))">
                            <template #prefix><UserOutlined /></template>
                        </a-input>
                    </a-form-item>
                    <div class="au-datos__dos">
                        <a-form-item :validate-status="form.errors.paterno ? 'error' : ''" :help="form.errors.paterno" class="au-datos__item">
                            <a-input :value="form.paterno" placeholder="Apellido paterno" @update:value="(v) => (form.paterno = mayus(v))" />
                        </a-form-item>
                        <a-form-item class="au-datos__item">
                            <a-input :value="form.materno" placeholder="Apellido materno" @update:value="(v) => (form.materno = mayus(v))" />
                        </a-form-item>
                    </div>
                </template>
                <div class="au-datos__row">
                    <CalendarOutlined />
                    <div><small>Fecha de nacimiento</small><b>{{ fechaNacimiento }}</b></div>
                    <CheckCircleFilled v-if="deRenapo" class="au-datos__ok" />
                </div>
            </div>

            <a-form-item :validate-status="form.errors.correo ? 'error' : ''" :help="form.errors.correo">
                <a-input v-model:value="form.correo" type="email" size="large" placeholder="Correo electrónico"
                    autocomplete="email" @update:value="form.clearErrors('correo')">
                    <template #prefix><MailOutlined /></template>
                </a-input>
            </a-form-item>

            <a-form-item :validate-status="form.errors.telefono ? 'error' : ''" :help="form.errors.telefono">
                <PhoneInput v-model:value="form.telefono" size="large" placeholder="Teléfono celular (10 dígitos)"
                    @update:value="form.clearErrors('telefono')" />
            </a-form-item>

            <a-form-item :validate-status="form.errors.password ? 'error' : ''" :help="form.errors.password">
                <a-input-password v-model:value="form.password" size="large" placeholder="Contraseña (mín. 6)" autocomplete="new-password">
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
                <div v-if="form.password" class="au-strength">
                    <span v-for="n in 4" :key="n" :style="{ background: n <= fuerza ? fuerzaColor : '#e2e8f0' }"></span>
                    <small :style="{ color: fuerzaColor }">{{ fuerzaTxt }}</small>
                </div>
            </a-form-item>

            <a-form-item :validate-status="confirmacion.status" :help="confirmacion.help" has-feedback>
                <a-input-password v-model:value="form.password_confirmation" size="large" placeholder="Confirmar contraseña" autocomplete="new-password" @press-enter="submit">
                    <template #prefix><LockOutlined /></template>
                </a-input-password>
            </a-form-item>

            <div class="au-terms">
                <a-checkbox v-model:checked="acepta" />
                <span @click="acepta = !acepta">
                    Acepto los
                    <a :href="route('terms')" target="_blank" class="au-link" @click.stop>Términos y condiciones</a>
                </span>
            </div>

            <a-button type="primary" size="large" block :loading="form.processing" @click="submit">
                <template #icon><UserAddOutlined /></template>
                Crear cuenta gratis
            </a-button>
        </a-form>

        <template #footer>
            ¿Ya tienes cuenta?
            <Link :href="route('login')" class="au-link au-link--strong">
                <LoginOutlined /> Iniciar sesión
            </Link>
        </template>
    </AuthShell>
</template>

<style scoped>
/* ===== Enlaces SIN subrayado ===== */
.au-link {
    color: #1b75bc;
    font-weight: 600;
    text-decoration: none !important;
}
.au-link:hover,
.au-link:focus,
.au-link:visited {
    color: #0a3a6e;
    text-decoration: none !important;
}
.au-link--strong {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    text-decoration: none !important;
}

/* Por si el subrayado viene de estilos globales de <a> */
.auth-shell-custom :deep(a),
.auth-shell-custom a {
    text-decoration: none !important;
}

/* ===== CURP y datos autocompletados ===== */
.au-curp :deep(input), .au-curp { text-transform: uppercase; letter-spacing: .04em; }
.au-datos {
    margin: -6px 0 18px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #dbe4f0;
    background: #f8fafc;
    display: grid;
    gap: 10px;
}
.au-datos.is-renapo { border-color: #bbf7d0; background: #f0fdf4; }
.au-datos__row { display: flex; align-items: center; gap: 10px; color: #1b75bc; }
.au-datos__row > div { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.au-datos__row small { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.au-datos__row b { font-size: 14px; color: #0f172a; overflow-wrap: anywhere; }
.au-datos__ok { color: #16a34a; font-size: 16px; }
.au-datos__item { margin-bottom: 0; }
.au-datos__dos { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
@media (max-width: 420px) { .au-datos__dos { grid-template-columns: 1fr; } }

/* ===== Términos ===== */
.au-terms {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 2px 0 16px;
    font-size: 13px;
    color: #475569;
    cursor: pointer;
    user-select: none;
}
.au-terms :deep(.ant-checkbox) { top: 0; }
.au-terms :deep(.ant-checkbox + span) { padding: 0; }
.au-terms span { line-height: 1.4; }

/* ===== Barra de fuerza ===== */
.au-strength { display: flex; align-items: center; gap: 4px; margin-top: 8px; }
.au-strength span { height: 4px; flex: 1; border-radius: 999px; transition: background .2s ease; }
.au-strength small { font-size: 11px; font-weight: 600; flex: none; margin-left: 4px; }
</style>