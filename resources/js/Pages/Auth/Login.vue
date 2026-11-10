<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import {
    MailOutlined, LockOutlined, LoginOutlined, UserAddOutlined,
} from '@ant-design/icons-vue';
import AuthShell from '@/Components/AuthShell.vue';
import { message } from '@/lib/notify';

// En CEEC se inicia sesión únicamente con el correo electrónico.
const form = useForm({ correo: '', password: '', remember: false });

function submit() {
    form.post(route('login'), {
        onSuccess: () => message.success('¡Bienvenido de vuelta!'),
        onError: (e) => {
            form.reset('password');
            message.error(e.correo || e.password || 'No pudimos iniciar tu sesión. Revisa tus datos.');
        },
    });
}
</script>

<template>
    <AuthShell title="Iniciar sesión" class="auth-shell-custom">
        <template #title>Bienvenido de vuelta</template>
        <template #subtitle>Ingresa a tu cuenta para continuar</template>

        <a-form layout="vertical" class="au-form" @submitcapture.prevent>
            <a-form-item :validate-status="form.errors.correo ? 'error' : ''" :help="form.errors.correo">
                <a-input v-model:value.trim="form.correo" type="email" size="large" placeholder="Correo electrónico"
                    autocomplete="email" inputmode="email" @press-enter="submit">
                    <template #prefix>
                        <MailOutlined />
                    </template>
                </a-input>
            </a-form-item>

            <a-form-item :validate-status="form.errors.password ? 'error' : ''" :help="form.errors.password">
                <a-input-password v-model:value="form.password" size="large" placeholder="Contraseña"
                    autocomplete="current-password" @press-enter="submit">
                    <template #prefix>
                        <LockOutlined />
                    </template>
                </a-input-password>
            </a-form-item>

            <div class="au-row">
                <a-checkbox v-model:checked="form.remember">Recordarme</a-checkbox>
                <Link :href="route('password.request')" class="au-link">¿Olvidaste tu contraseña?</Link>
            </div>

            <a-button type="primary" size="large" block :loading="form.processing" @click="submit">
                <template #icon>
                    <LoginOutlined />
                </template>
                Iniciar sesión
            </a-button>
        </a-form>

        <template #footer>
            ¿No tienes cuenta?
            <Link :href="route('registro')" class="au-link au-link--strong">
                <UserAddOutlined /> Crear cuenta gratis
            </Link>
        </template>
    </AuthShell>
</template>

<style scoped>
/* ===== Estilos generales ===== */
.au-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: -2px 0 16px;
    font-size: 14px;
}

/* 👇 Enlaces SIN subrayado */
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
</style>