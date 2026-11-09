<script setup>
import { Head } from '@inertiajs/vue3';
import {
    RiseOutlined, SafetyCertificateOutlined, BankOutlined,
    ReadOutlined, FormOutlined, LineChartOutlined,
} from '@ant-design/icons-vue';
import { authTheme, antdLocale } from '@/theme';
import CeecLogo from '@/Components/CeecLogo.vue';

defineProps({
    title: { type: String, default: 'CEEC' },
});

// Descripción institucional: pendiente (la proporcionará Julio).
// Cuando llegue, basta con escribirla aquí y aparecerá bajo el logo.
const descripcion = '';

const licenciaturas = [
    { icon: RiseOutlined, label: 'Marketing Digital' },
    { icon: SafetyCertificateOutlined, label: 'Derecho Digital' },
    { icon: BankOutlined, label: 'Administración de Empresas' },
];

const ventajas = [
    { icon: ReadOutlined, label: 'Contenido en línea' },
    { icon: FormOutlined, label: 'Exámenes y simuladores' },
    { icon: LineChartOutlined, label: 'Seguimiento de tu avance' },
];

// Íconos de línea (24×24) para el patrón educativo del fondo.
const iconos = {
    libro: 'M3 6.5c3-1.6 6-1.6 9 0v12c-3-1.6-6-1.6-9 0zM21 6.5c-3-1.6-6-1.6-9 0v12c3-1.6 6-1.6 9 0z',
    birrete: 'M1.5 9 12 4l10.5 5L12 14zM6 11.2v4.6c3.3 2.6 8.7 2.6 12 0v-4.6M22.5 9v5.5',
    lapiz: 'M4 20l4.2-1L19 8.2 15.8 5 5 15.8zM13.8 7l3.2 3.2',
    foco: 'M9 18h6M10 21h4M12 3a6 6 0 0 0-3.8 10.6c.6.6.8 1.4.8 2.4h6c0-1 .2-1.8.8-2.4A6 6 0 0 0 12 3z',
    grano: 'M12 3c3.9 0 6 4 6 9s-2.1 9-6 9-6-4-6-9 2.1-9 6-9zM12 3c-2.2 3.6 2.2 5.4 0 9s2.2 5.4 0 9',
    hoja: 'M4 20C4 11 10 5 20 4c-1 10-7 16-16 16zM4 20l9-9',
    atomo: 'M10.8 12a1.2 1.2 0 1 0 2.4 0a1.2 1.2 0 1 0-2.4 0M12 3c2.5 0 4.5 4 4.5 9s-2 9-4.5 9-4.5-4-4.5-9 2-9 4.5-9z',
};
const patron = [
    { d: iconos.libro, x: 20, y: 22, r: -12 },
    { d: iconos.birrete, x: 128, y: 14, r: 8 },
    { d: iconos.lapiz, x: 216, y: 76, r: 0 },
    { d: iconos.grano, x: 70, y: 110, r: 24 },
    { d: iconos.foco, x: 168, y: 150, r: -8 },
    { d: iconos.hoja, x: 22, y: 196, r: 10 },
    { d: iconos.atomo, x: 112, y: 228, r: 30 },
    { d: iconos.grano, x: 228, y: 232, r: -30 },
];
</script>

<template>
    <a-config-provider :theme="authTheme" :locale="antdLocale">
        <Head :title="title" />

        <div class="au">
            <!-- Panel institucional (azul) -->
            <aside class="au-brand">
                <!-- Patrón educativo (libros, birretes, lápices, granos de café…) -->
                <svg class="au-brand__pattern" aria-hidden="true">
                    <defs>
                        <pattern id="au-edu" width="280" height="280" patternUnits="userSpaceOnUse">
                            <g v-for="(it, i) in patron" :key="i"
                                :transform="`translate(${it.x} ${it.y}) rotate(${it.r} 12 12) scale(1.15)`">
                                <path :d="it.d" />
                            </g>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#au-edu)" />
                </svg>
                <span class="au-brand__glow au-brand__glow--a" aria-hidden="true"></span>
                <span class="au-brand__glow au-brand__glow--b" aria-hidden="true"></span>

                <div class="au-brand__inner">
                    <div class="au-logo">
                        <div class="au-logo__card">
                            <CeecLogo class="au-logo__ceec" />
                        </div>
                    </div>

                    <p v-if="descripcion" class="au-pitch__lead">{{ descripcion }}</p>

                    <h2 class="au-careers__title">Licenciaturas en:</h2>
                    <ul class="au-programs">
                        <li v-for="c in licenciaturas" :key="c.label" class="au-program">
                            <span class="au-program__icon"><component :is="c.icon" /></span>
                            <span class="au-program__text">
                                <small>Licenciatura en</small>
                                <b>{{ c.label }}</b>
                            </span>
                        </li>
                    </ul>

                    <ul class="au-perks">
                        <li v-for="v in ventajas" :key="v.label"><component :is="v.icon" /> {{ v.label }}</li>
                    </ul>
                </div>

                <!-- Onda que separa el panel azul del formulario -->
                <svg class="au-wave" viewBox="0 0 160 1000" preserveAspectRatio="none" aria-hidden="true">
                    <path class="au-wave__back" d="M160 0H70C20 160 0 300 40 430c40 130 115 230 100 380-5 70-25 130-40 190h60z" />
                    <path class="au-wave__front" d="M160 0H95C55 170 40 300 75 430c35 130 95 240 80 390-6 60-18 120-30 180h35z" />
                </svg>
            </aside>

            <!-- Panel del formulario -->
            <main class="au-side">
                <span class="au-side__dots" aria-hidden="true"></span>
                <span class="au-side__orb au-side__orb--gold" aria-hidden="true"></span>
                <span class="au-side__orb au-side__orb--blue" aria-hidden="true"></span>

                <div class="au-card">
                    <div class="au-card__logos">
                        <CeecLogo class="au-card__ceec" />
                    </div>

                    <h1 class="au-card__title"><slot name="title">Bienvenido</slot></h1>
                    <p class="au-card__sub"><slot name="subtitle" /></p>

                    <slot />

                    <p class="au-card__foot"><slot name="footer" /></p>
                </div>

                <!-- En móvil el panel azul se oculta: oferta educativa compacta -->
                <section class="au-movil" aria-label="Oferta educativa">
                    <h2>Licenciaturas en:</h2>
                    <ul>
                        <li v-for="c in licenciaturas" :key="c.label"><component :is="c.icon" /> {{ c.label }}</li>
                    </ul>
                </section>
            </main>
        </div>
    </a-config-provider>
</template>

<style scoped>
.au {
    --au-navy: #0a3a6e;
    --au-blue: #0d47a1;
    --au-gold: #f5b301;
    --au-green: #2f9e44;
    --au-bg: #f3f6fb;
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
    background: var(--au-bg);
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    overflow: hidden;
}

/* ---------- Panel institucional ---------- */
.au-brand {
    position: relative;
    display: flex;
    align-items: center;
    padding: 48px 150px 48px 5%;
    color: #fff;
    background:
        radial-gradient(ellipse at 15% 0%, #2a8ad6 0%, transparent 55%),
        radial-gradient(ellipse at 90% 100%, #0b4f8f 0%, transparent 60%),
        linear-gradient(160deg, #1b75bc 0%, #0f5598 45%, #0a3a6e 100%);
    z-index: 1;
}
.au-brand__pattern {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    -webkit-mask-image: radial-gradient(ellipse at 45% 50%, rgba(0, 0, 0, .3) 0%, #000 75%);
    mask-image: radial-gradient(ellipse at 45% 50%, rgba(0, 0, 0, .3) 0%, #000 75%);
}
.au-brand__pattern path {
    fill: none;
    stroke: rgba(255, 255, 255, .12);
    stroke-width: 1.3;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.au-brand__glow {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.au-brand__glow--a {
    top: -18%;
    left: -12%;
    width: 60%;
    aspect-ratio: 1;
    background: radial-gradient(circle, rgba(255, 255, 255, .16), transparent 65%);
}
.au-brand__glow--b {
    bottom: -22%;
    right: 8%;
    width: 46%;
    aspect-ratio: 1;
    background: radial-gradient(circle, rgba(245, 179, 1, .22), transparent 65%);
}
.au-brand__inner {
    position: relative;
    z-index: 2;
    max-width: 600px;
    width: 100%;
    margin: 0 auto;
    text-align: center;
    animation: au-slide-in .6s cubic-bezier(.16, 1, .3, 1);
}
@keyframes au-slide-in { from { opacity: 0; transform: translateX(-22px); } to { opacity: 1; transform: none; } }


/* Logo CEEC en tarjeta blanca (el logo es azul y no se leería sobre el panel) */
.au-logo { margin-bottom: 30px; }
.au-logo__card {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px 44px;
    border-radius: 28px;
    background: linear-gradient(180deg, #ffffff 0%, #f3f6fb 100%);
    box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, .9),
        0 2px 6px rgba(4, 18, 52, .18),
        0 30px 60px -22px rgba(4, 18, 52, .75);
}
.au-logo__ceec { font-size: clamp(14px, 1.45vw, 22px); max-width: 100%; }
.au-logo__bar {
    display: block;
    width: 84px;
    height: 4px;
    margin: 26px auto 0;
    border-radius: 4px;
    background: rgba(255, 255, 255, .7);
}
.au-pitch { position: relative; }
.au-pitch__eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, .78);
}
.au-pitch__eyebrow::after {
    content: '';
    width: 36px;
    height: 1px;
    background: rgba(255, 255, 255, .45);
}
.au-pitch__eyebrow .anticon { font-size: 14px; color: #d9b45a; }
.au-pitch__lead {
    margin: 0 0 18px;
    font-size: clamp(1.08rem, 1.35vw, 1.32rem);
    font-weight: 400;
    line-height: 1.5;
    color: rgba(255, 255, 255, .92);
}
.au-pitch__lead b,
.au-pitch__lead mark {
    background: none;
    padding: 0;
    color: #fff;
    font-weight: 700;
}
.au-pitch__lead mark {
    background-image: linear-gradient(#d9b45a, #d9b45a);
    background-repeat: no-repeat;
    background-size: 100% 2px;
    background-position: 0 100%;
}
.au-pitch__card {
    margin-right: -48px;
    position: relative;
    padding: 16px 14px 16px 18px;
    border-radius: 12px;
    background: rgba(7, 24, 64, .45);
    border: 1px solid rgba(255, 255, 255, .12);
    border-left: 3px solid #d9b45a;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}
.au-pitch__card p { margin: 0; font-size: .94rem; line-height: 1.6; color: rgba(255, 255, 255, .82); }
.au-pitch__card b { color: #fff; font-weight: 600; }
.au-pitch__chips {
    list-style: none;
    display: flex;
    flex-wrap: nowrap;
    gap: 5px;
    margin: 12px 0 0;
    padding: 0;
}
.au-pitch__chips li {
    display: inline-flex;
    align-items: center;
    flex: 0 1 auto;
    min-width: 0;
    gap: 0;
    padding: 5px 7px;
    border-radius: 6px;
    font-size: clamp(9.5px, .76vw, 12px);
    white-space: nowrap;
    font-weight: 500;
    color: rgba(255, 255, 255, .88);
    background: rgba(255, 255, 255, .06);
    border: 1px solid rgba(255, 255, 255, .16);
}
.au-pitch__chips li .anticon { color: rgba(255, 255, 255, .7); font-size: 1.05em; margin-right: 4px; }

.au-rule {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 20px 0 16px;
    color: var(--au-gold);
    font-size: 20px;
}
.au-rule::before,
.au-rule::after {
    content: '';
    flex: 1;
    height: 3px;
    border-radius: 3px;
}
.au-rule::before { background: linear-gradient(90deg, var(--au-gold), rgba(245, 179, 1, .25)); margin-right: 12px; }
.au-rule::after { background: linear-gradient(90deg, rgba(79, 140, 255, .8), rgba(79, 140, 255, .1)); margin-left: 12px; }

.au-careers__title {
    display: inline-block;
    margin: 6px 0 18px;
    padding-bottom: 6px;
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: uppercase;
    color: #fff;
    border-bottom: 3px solid var(--au-gold);
}
.au-careers {
    list-style: none;
    margin: 0 auto;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
}
.au-careers--3 { grid-template-columns: repeat(3, 1fr); max-width: 520px; }
.au-careers--2 { grid-template-columns: repeat(2, 1fr); max-width: 360px; }
.au-career.is-pending .au-career__icon {
    border-style: dashed;
    border-color: rgba(255, 255, 255, .5);
    color: rgba(255, 255, 255, .7);
    background: transparent;
}
.au-career.is-pending .au-career__label {
    color: rgba(255, 255, 255, .7);
    font-weight: 500;
    font-style: italic;
}
.au-career {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 0 10px;
    text-align: center;
}
.au-career + .au-career { border-left: 1px solid rgba(255, 255, 255, .22); }
.au-career__icon {
    display: grid;
    place-items: center;
    width: 62px;
    height: 62px;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, .85);
    font-size: 26px;
    color: #fff;
    background: rgba(255, 255, 255, .06);
    transition: transform .2s, background .2s, border-color .2s;
}
.au-career:hover .au-career__icon { transform: translateY(-3px); background: rgba(245, 179, 1, .18); border-color: var(--au-gold); }
.au-career__label { font-size: .88rem; font-weight: 600; line-height: 1.3; color: #fff; }

/* Licenciaturas como tarjetas */
.au-programs {
    list-style: none;
    margin: 0 auto;
    padding: 0;
    display: grid;
    grid-template-columns: repeat(3, auto);
    justify-content: center;
    gap: 10px;
    max-width: 100%;
    text-align: center;
}
.au-program {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 18px 14px 16px;
    border-radius: 16px;
    background: rgba(255, 255, 255, .09);
    border: 1px solid rgba(255, 255, 255, .18);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 12px 30px -20px rgba(3, 20, 48, .8);
    transition: transform .2s, background .2s, border-color .2s;
}
.au-program:hover { transform: translateY(-4px); background: rgba(255, 255, 255, .14); border-color: rgba(245, 179, 1, .6); }
.au-program__icon {
    display: grid;
    place-items: center;
    flex: none;
    width: 46px;
    height: 46px;
    border-radius: 13px;
    font-size: 21px;
    color: #0a3a6e;
    background: linear-gradient(140deg, #ffd34d, #f5b301);
    box-shadow: 0 8px 18px -10px rgba(245, 179, 1, .9);
}
.au-program__text { display: flex; flex-direction: column; align-items: center; gap: 3px; min-width: 0; }
.au-program__text small { white-space: nowrap; font-size: .62rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: rgba(255, 255, 255, .7); }
.au-program__text b { white-space: nowrap; font-size: clamp(.74rem, .82vw, .95rem); font-weight: 700; line-height: 1.25; color: #fff; }

.au-perks {
    list-style: none;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin: 26px auto 0;
    padding: 18px 0 0;
    max-width: 560px;
    border-top: 1px solid rgba(255, 255, 255, .18);
}
.au-perks li { display: flex; flex-direction: column; align-items: center; gap: 6px; font-size: .8rem; font-weight: 500; line-height: 1.3; color: rgba(255, 255, 255, .85); }
.au-perks .anticon { color: #ffd34d; font-size: 1.35rem; }

/* Onda divisoria */
.au-wave {
    position: absolute;
    top: 0;
    right: -1px;
    width: 160px;
    height: 100%;
    z-index: 3;
    pointer-events: none;
}
.au-wave__back { fill: rgba(255, 255, 255, .28); }
.au-wave__front { fill: var(--au-bg); }

/* ---------- Panel del formulario ---------- */
.au-side {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 28px;
    background:
        radial-gradient(circle at 70% 30%, #ffffff 0%, transparent 60%),
        var(--au-bg);
}
.au-side__dots {
    position: absolute;
    top: 24px;
    right: 24px;
    width: 220px;
    height: 160px;
    background-image: radial-gradient(rgba(13, 71, 161, .16) 1.5px, transparent 1.6px);
    background-size: 16px 16px;
    mask-image: linear-gradient(225deg, #000 30%, transparent 85%);
    pointer-events: none;
}
.au-side__orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    animation: au-float 12s ease-in-out infinite;
}
.au-side__orb--gold {
    right: -90px;
    bottom: -90px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle at 35% 35%, rgba(255, 211, 77, .55), rgba(245, 179, 1, .18) 55%, transparent 72%);
}
.au-side__orb--blue {
    left: 4%;
    top: -60px;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(27, 117, 188, .16), transparent 70%);
    animation-delay: -6s;
}
@keyframes au-float {
    0%, 100% { transform: translate(0, 0); }
    50% { transform: translate(-10px, -12px); }
}

.au-card {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 560px;
    background: #fff;
    border: 1px solid #e8edf5;
    border-radius: 26px;
    padding: 46px 56px 38px;
    box-shadow: 0 30px 70px -30px rgba(10, 42, 107, .35), 0 2px 6px rgba(15, 23, 42, .04);
    text-align: center;
    animation: au-card-in .5s cubic-bezier(.16, 1, .3, 1);
}
@keyframes au-card-in { from { opacity: 0; transform: translateY(16px) scale(.99); } to { opacity: 1; transform: none; } }
.au-card__logos {
    display: flex; align-items: center; justify-content: center; gap: 16px;
    margin-bottom: 26px;
}
.au-card__ceec { font-size: 12.5px; }
.au-card__title {
    font-size: 1.95rem;
    font-weight: 800;
    color: var(--au-navy);
    margin: 0 0 4px;
    letter-spacing: -.01em;
}
.au-card__sub { font-size: 1rem; color: #64748b; margin: 0 0 28px; }
.au-card > :deep(form),
.au-card > :deep(.au-form) { text-align: left; }

/* Entrada escalonada de los campos del formulario */
.au-card :deep(.au-form) > * {
    opacity: 0;
    animation: au-field-in .45s cubic-bezier(.16, 1, .3, 1) forwards;
}
.au-card :deep(.au-form) > *:nth-child(1) { animation-delay: .12s; }
.au-card :deep(.au-form) > *:nth-child(2) { animation-delay: .19s; }
.au-card :deep(.au-form) > *:nth-child(3) { animation-delay: .26s; }
.au-card :deep(.au-form) > *:nth-child(4) { animation-delay: .33s; }
.au-card :deep(.au-form) > *:nth-child(5) { animation-delay: .40s; }
.au-card :deep(.au-form) > *:nth-child(6) { animation-delay: .47s; }
@keyframes au-field-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

.au-card__foot { margin: 18px 0 0; font-size: 14px; color: #64748b; display: flex; flex-direction: column; align-items: center; gap: 4px; }

/* Oferta educativa en móvil */
.au-movil { display: none; }

/* ---------- Responsive ---------- */
@media (max-width: 1280px) {
    .au-brand { padding-right: 130px; }
    .au-card { padding: 40px 40px 32px; }
    .au-career__icon { width: 62px; height: 62px; font-size: 26px; }
}
/* Laptops con poca altura: compacta sin perder tamaño de campos */
@media (max-height: 860px) and (min-width: 1025px) {
    .au-side { padding: 24px 28px; }
    .au-card { padding: 30px 48px 26px; }
    .au-card__logos { margin-bottom: 16px; }
    .au-card__ceec { font-size: 11px; }
    .au-card__sub { margin-bottom: 20px; }
    .au .ant-form-item { margin-bottom: 16px; }
    .au-card__foot { margin-top: 12px; }
    .au-brand { padding-top: 24px; padding-bottom: 24px; }
    .au-career__icon { width: 54px; height: 54px; font-size: 23px; }
    .au-career { gap: 8px; }
    .au-programs { gap: 9px; }
    .au-program { padding: 14px 8px 12px; gap: 9px; }
    .au-program__icon { width: 40px; height: 40px; font-size: 18px; border-radius: 11px; }
    .au-perks { margin-top: 18px; padding-top: 14px; }
    .au-careers__title { margin-bottom: 12px; }
    .au-rule { margin: 18px 0 16px; }
    .au-logo { margin-bottom: 20px; }
    .au-logo__card { padding: 20px 28px; border-radius: 22px; }
    .au-logo__ceec { font-size: 14px; }
    .au-logo__bar { margin-top: 18px; }
    .au-pitch__eyebrow { margin-bottom: 10px; }
    .au-pitch__lead { margin-bottom: 12px; font-size: 1.12rem; }
    .au-pitch__card { padding: 12px 12px 12px 16px; }
    .au-pitch__card p { font-size: .9rem; }
    .au-pitch__chips { margin-top: 10px; gap: 4px; }
    .au-pitch__chips li { padding: 4px 6px; }
}
@media (max-width: 1024px) {
    .au { grid-template-columns: 1fr; }
    .au-brand { display: none; }
    .au-side { min-height: 100vh; padding: 36px 18px; }
    .au-side__orb--gold { width: 220px; height: 220px; right: -80px; bottom: -80px; }
    .au-movil {
        display: block;
        position: relative;
        z-index: 2;
        width: 100%;
        max-width: 560px;
        margin-top: 18px;
        padding: 18px 18px 8px;
        border-radius: 20px;
        color: #fff;
        background: linear-gradient(160deg, #1b75bc 0%, #0f5598 55%, #0a3a6e 100%);
        box-shadow: 0 20px 40px -24px rgba(10, 42, 107, .6);
    }
    .au-movil h2 {
        margin: 0 0 10px;
        font-size: .8rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        color: #fff;
    }
    .au-movil h2::after {
        content: '';
        display: block;
        width: 36px;
        height: 3px;
        margin-top: 6px;
        border-radius: 3px;
        background: var(--au-gold);
    }
    .au-movil ul { list-style: none; margin: 0 0 14px; padding: 0; display: flex; flex-wrap: wrap; gap: 8px; }
    .au-movil li {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 11px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        background: rgba(255, 255, 255, .1);
        border: 1px solid rgba(255, 255, 255, .22);
    }
    .au-movil li.is-pending { font-weight: 500; font-style: italic; color: rgba(255, 255, 255, .75); border-style: dashed; background: transparent; }
}
@media (max-width: 520px) {
    .au-card { padding: 28px 20px 24px; }
    .au-card__logos { gap: 10px; }
    .au-card__ceec { font-size: 8.5px; }
    .au-side__dots { display: none; }
}

@media (prefers-reduced-motion: reduce) {
    .au *, .au *::before, .au *::after { animation: none !important; }
    .au-card :deep(.au-form) > *,
    .au-brand__inner { opacity: 1 !important; }
}
</style>

<style>
/* Ajustes globales para inputs/botones dentro de las pantallas de auth */
.au .ant-input-affix-wrapper { border-radius: 14px; padding-top: 12px; padding-bottom: 12px; font-size: 15.5px; }
.au .ant-input-affix-wrapper .ant-input { font-size: 15.5px; }
.au .ant-form-item { margin-bottom: 20px; }
.au .ant-btn { border-radius: 14px; font-weight: 600; }
.au .ant-btn-lg { height: 54px; font-size: 16px; }
.au .ant-checkbox-wrapper { font-size: 14px; }
.au .ant-btn-primary { background: #0d47a1; box-shadow: 0 10px 22px -12px rgba(13, 71, 161, .8); }
.au .ant-btn-primary:not(:disabled):hover { background: #0a3a86; }
.au .ant-input-affix-wrapper > .ant-input-prefix { color: #0d47a1; margin-inline-end: 8px; }
</style>
