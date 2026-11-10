<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { router, Link, useForm } from '@inertiajs/vue3';
import {
    CheckCircleFilled, CloseCircleFilled, FileDoneOutlined, EditOutlined, SaveOutlined,
    HistoryOutlined, UndoOutlined, ThunderboltOutlined, ReloadOutlined, UserOutlined,
    CalendarOutlined, ClockCircleOutlined, NumberOutlined, SearchOutlined, InfoCircleOutlined,
} from '@ant-design/icons-vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PageHead from '@/Components/PageHead.vue';

const props = defineProps({
    examen: { type: Object, required: true },
    resumen: { type: Object, required: true },
    respuestas: { type: Array, default: () => [] },
    ediciones: { type: Array, default: () => [] },
});

const total = computed(() => props.respuestas.length);
const letra = (i) => String.fromCharCode(65 + i);

/* ---------- Modo edición: corregir respuestas y calificación final ---------- */
const editando = ref(false);
const seleccion = reactive({});
const form = useForm({ cambios: [], calificacion_manual: null, motivo: '' });
const manual = ref(false);
const original = computed(() => Object.fromEntries(props.respuestas.map((r) => [r.indice, r.respuesta])));
const autoIdx = ref(new Set());   // preguntas ajustadas automáticamente al capturar calificación
const tocadas = ref(new Set());   // preguntas que el admin cambió a mano (el ajuste no las toca)
const avisoAjuste = ref(null);

function iniciar() {
    props.respuestas.forEach((r) => { seleccion[r.indice] = r.respuesta; });
    form.reset();
    form.clearErrors();
    manual.value = false;
    autoIdx.value = new Set();
    tocadas.value = new Set();
    avisoAjuste.value = null;
    editando.value = true;
}
function cancelar() {
    editando.value = false;
    avisoAjuste.value = null;
}
watch(() => props.respuestas, () => { if (editando.value) iniciar(); });

const esCorrectaSel = (r) => {
    const op = r.opciones.find((o) => o.texto === seleccion[r.indice]);
    return op ? op.correcta : r.correcta;
};
const estaBien = (r) => (editando.value ? esCorrectaSel(r) : r.correcta);
const cambiada = (r) => editando.value && r.opciones.length && seleccion[r.indice] !== r.respuesta;

const cambios = computed(() => props.respuestas
    .filter((r) => r.opciones.length && seleccion[r.indice] !== undefined && seleccion[r.indice] !== r.respuesta)
    .map((r) => ({ indice: r.indice, respuesta: seleccion[r.indice] })));
const aciertos = computed(() => props.respuestas.filter(estaBien).length);
const calculada = computed(() => (total.value ? Math.round((aciertos.value / total.value) * 100) : 0));
const calificacionFinal = computed(() => (manual.value && form.calificacion_manual != null ? form.calificacion_manual : calculada.value));

function elegir(r, texto) {
    if (!editando.value) return;
    seleccion[r.indice] = texto;
    const auto = new Set(autoIdx.value); auto.delete(r.indice); autoIdx.value = auto;
    const t = new Set(tocadas.value); t.add(r.indice); tocadas.value = t;
}

/* ---------- Ajuste aleatorio para alcanzar la calificación capturada ---------- */
function barajar(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}

function ajustarA(objetivo) {
    // Deshace el sorteo anterior para partir siempre de las respuestas reales + cambios manuales.
    autoIdx.value.forEach((i) => { seleccion[i] = original.value[i]; });
    const nuevos = new Set();
    avisoAjuste.value = null;

    if (objetivo == null || objetivo === '' || !total.value) {
        autoIdx.value = nuevos;
        return;
    }

    const meta = Math.round((Math.min(100, Math.max(0, Number(objetivo))) / 100) * total.value);
    const libres = props.respuestas.filter((r) => r.opciones.some((o) => o.correcta) && !tocadas.value.has(r.indice));
    const actuales = aciertos.value;

    if (meta > actuales) {
        const candidatos = barajar(libres.filter((r) => !esCorrectaSel(r))).slice(0, meta - actuales);
        candidatos.forEach((r) => {
            seleccion[r.indice] = r.opciones.find((o) => o.correcta).texto;
            nuevos.add(r.indice);
        });
        avisoAjuste.value = {
            tipo: candidatos.length < meta - actuales ? 'warning' : 'success',
            texto: candidatos.length < meta - actuales
                ? `Solo se pudieron corregir ${candidatos.length} preguntas; no hay más incorrectas disponibles.`
                : `Se marcaron como correctas ${candidatos.length} preguntas elegidas al azar (${actuales} → ${meta} aciertos).`,
        };
    } else if (meta < actuales) {
        const candidatos = barajar(libres.filter((r) => esCorrectaSel(r) && r.opciones.some((o) => !o.correcta)))
            .slice(0, actuales - meta);
        candidatos.forEach((r) => {
            const malas = r.opciones.filter((o) => !o.correcta);
            seleccion[r.indice] = malas[Math.floor(Math.random() * malas.length)].texto;
            nuevos.add(r.indice);
        });
        avisoAjuste.value = {
            tipo: 'success',
            texto: `Se marcaron como incorrectas ${candidatos.length} preguntas elegidas al azar (${actuales} → ${meta} aciertos).`,
        };
    } else {
        avisoAjuste.value = { tipo: 'info', texto: `Los aciertos actuales (${actuales}) ya corresponden a esa calificación.` };
    }
    autoIdx.value = nuevos;
}

let debounce = null;
watch([manual, () => form.calificacion_manual], ([m, cal]) => {
    if (!editando.value) return;
    clearTimeout(debounce);
    debounce = setTimeout(() => ajustarA(m ? cal : null), 350);
});
const resortear = () => ajustarA(form.calificacion_manual);

function guardar() {
    form.cambios = cambios.value;
    form.calificacion_manual = manual.value ? form.calificacion_manual : null;
    form.put(route('admin.examenes-realizados.update', props.examen.id), {
        preserveScroll: true,
        onSuccess: () => { editando.value = false; avisoAjuste.value = null; },
    });
}

/* ---------- Presentación ---------- */
const calif = computed(() => (editando.value ? calificacionFinal.value : props.examen.calificacion));
const aprobado = computed(() => (calif.value ?? 0) >= 60);
const tono = computed(() => ((calif.value ?? 0) >= 80 ? 'top' : aprobado.value ? 'ok' : 'bad'));
const R = 52;
const C = 2 * Math.PI * R;
const dash = computed(() => `${(Math.min(100, Math.max(0, calif.value ?? 0)) / 100) * C} ${C}`);
const pctBien = computed(() => (total.value ? (aciertos.value / total.value) * 100 : 0));

const filtro = ref('todas');
const busqueda = ref('');
const filtros = computed(() => [
    { key: 'todas', label: 'Todas', n: total.value },
    { key: 'correctas', label: 'Correctas', n: aciertos.value },
    { key: 'incorrectas', label: 'Incorrectas', n: total.value - aciertos.value },
    {
        key: 'corregidas',
        label: editando.value ? 'Cambiadas' : 'Corregidas',
        n: editando.value ? cambios.value.length : props.respuestas.filter((r) => r.editada).length,
    },
]);
const visibles = computed(() => {
    const q = busqueda.value.trim().toLowerCase();
    return props.respuestas.filter((r) => {
        if (filtro.value === 'correctas' && !estaBien(r)) return false;
        if (filtro.value === 'incorrectas' && estaBien(r)) return false;
        if (filtro.value === 'corregidas' && !(editando.value ? cambiada(r) : r.editada)) return false;
        return !q || String(r.pregunta).toLowerCase().includes(q) || String(r.numero) === q;
    });
});

function irA(r) {
    filtro.value = 'todas';
    busqueda.value = '';
    requestAnimationFrame(() => {
        document.getElementById(`er-q-${r.indice}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
}

function claseOpcion(r, op) {
    const elegida = editando.value ? seleccion[r.indice] === op.texto : r.respuesta === op.texto;
    return {
        'is-chosen': elegida,
        'is-right': op.correcta,
        'is-wrong': elegida && !op.correcta,
        'is-orig': editando.value && r.respuesta === op.texto && seleccion[r.indice] !== op.texto,
        'is-click': editando.value,
    };
}
</script>

<template>
    <AdminLayout :title="`Examen realizado #${examen.id}`">
        <PageHead :title="`Examen de ${examen.estudiante}`" :subtitle="examen.tipo_examen" :icon="FileDoneOutlined"
            back @back="router.visit(route('admin.examenes-realizados.index'))">
            <template #actions>
                <a-button v-if="!editando" type="primary" @click="iniciar"><template #icon><EditOutlined /></template>Corregir examen</a-button>
                <Link v-if="examen.estudiante_id" :href="route('admin.estudiantes.show', examen.estudiante_id)"><a-button><template #icon><UserOutlined /></template>Ver estudiante</a-button></Link>
            </template>
        </PageHead>

        <!-- Resumen -->
        <section class="er-hero" :class="`is-${tono}`">
            <div class="er-ring">
                <svg viewBox="0 0 120 120">
                    <circle cx="60" cy="60" :r="R" class="er-ring__bg" />
                    <circle cx="60" cy="60" :r="R" class="er-ring__fg" :stroke-dasharray="dash" />
                </svg>
                <div class="er-ring__val">
                    <b>{{ calif ?? '—' }}</b>
                    <small>de 100</small>
                </div>
            </div>

            <div class="er-hero__body">
                <div class="er-hero__top">
                    <span class="er-pill" :class="`is-${tono}`">
                        <CheckCircleFilled v-if="aprobado" /><CloseCircleFilled v-else />
                        {{ tono === 'top' ? 'Excelente' : aprobado ? 'Aprobado' : 'No aprobado' }}
                    </span>
                    <span v-if="editando" class="er-pill is-edit"><EditOutlined /> Vista previa de la corrección</span>
                </div>
                <h2 class="er-hero__name">{{ examen.estudiante }}</h2>
                <div class="er-meta">
                    <span><FileDoneOutlined /> {{ examen.tipo_examen }}</span>
                    <span v-if="examen.fecha_inicio"><CalendarOutlined /> {{ examen.fecha_inicio }}</span>
                    <span v-if="examen.tiempo"><ClockCircleOutlined /> {{ examen.tiempo }}</span>
                    <span><NumberOutlined /> Intento {{ examen.intento ?? 1 }}</span>
                </div>

                <div class="er-bar">
                    <div class="er-bar__ok" :style="{ width: `${pctBien}%` }"></div>
                </div>
                <div class="er-bar__legend">
                    <span><i class="dot dot--ok"></i>{{ aciertos }} correctas</span>
                    <span><i class="dot dot--bad"></i>{{ total - aciertos }} incorrectas</span>
                    <span class="muted">{{ total }} preguntas</span>
                </div>
            </div>
        </section>

        <!-- Panel de corrección -->
        <transition name="er-slide">
            <section v-if="editando" class="er-edit">
                <div class="er-edit__head">
                    <div>
                        <b><EditOutlined /> Corrigiendo examen</b>
                        <span>Haz clic en una opción para cambiar la respuesta, o captura la calificación final y el sistema ajustará preguntas al azar.</span>
                    </div>
                    <div class="er-compare">
                        <div><small>Antes</small><b>{{ examen.calificacion ?? '—' }}</b></div>
                        <span class="er-compare__arrow">→</span>
                        <div class="is-now"><small>Después</small><b>{{ calificacionFinal }}</b></div>
                    </div>
                </div>

                <div class="er-edit__grid">
                    <div class="er-box">
                        <div class="er-box__title">
                            <a-switch v-model:checked="manual" size="small" />
                            <span>Capturar calificación final manualmente</span>
                        </div>
                        <template v-if="manual">
                            <div class="er-box__row">
                                <a-input-number v-model:value="form.calificacion_manual" :min="0" :max="100" :step="1"
                                    size="large" style="width: 140px" placeholder="0 a 100" />
                                <a-tooltip title="Elegir otras preguntas al azar">
                                    <a-button :disabled="form.calificacion_manual == null" @click="resortear">
                                        <template #icon><ReloadOutlined /></template>Volver a sortear
                                    </a-button>
                                </a-tooltip>
                            </div>
                            <a-alert v-if="avisoAjuste" :type="avisoAjuste.tipo" show-icon :message="avisoAjuste.texto" class="er-box__alert">
                                <template #icon><ThunderboltOutlined /></template>
                            </a-alert>
                            <p v-else class="er-box__hint"><InfoCircleOutlined /> Se elegirán preguntas al azar para que los aciertos coincidan con la calificación.</p>
                        </template>
                        <p v-else class="er-box__hint">La calificación se calcula con los aciertos: {{ aciertos }} de {{ total }}.</p>
                        <div v-if="form.errors.calificacion_manual" class="er-err">{{ form.errors.calificacion_manual }}</div>
                    </div>

                    <div class="er-box">
                        <div class="er-box__title"><HistoryOutlined /> <span>Motivo (queda en el historial)</span></div>
                        <a-textarea v-model:value="form.motivo" :maxlength="500" :auto-size="{ minRows: 2, maxRows: 4 }"
                            placeholder="Ej. revisión solicitada por el estudiante" />
                    </div>
                </div>

                <div class="er-edit__foot">
                    <span class="er-edit__count">
                        <b>{{ cambios.length }}</b> {{ cambios.length === 1 ? 'respuesta cambiada' : 'respuestas cambiadas' }}
                        <template v-if="autoIdx.size"> · {{ autoIdx.size }} por ajuste automático</template>
                    </span>
                    <div class="er-edit__actions">
                        <a-button @click="cancelar"><template #icon><UndoOutlined /></template>Cancelar</a-button>
                        <a-button type="primary" :loading="form.processing" @click="guardar">
                            <template #icon><SaveOutlined /></template>Guardar corrección
                        </a-button>
                    </div>
                </div>
            </section>
        </transition>

        <div class="er-layout">
            <!-- Mapa de preguntas -->
            <aside class="er-map">
                <div v-if="editando" class="er-map__live">
                    <small>Calificación con la corrección</small>
                    <b>{{ calificacionFinal }}</b>
                    <span>{{ aciertos }} / {{ total }} aciertos · {{ cambios.length }} cambios</span>
                </div>
                <div class="er-map__title">Mapa de preguntas</div>
                <div class="er-map__grid">
                    <button v-for="r in respuestas" :key="r.indice" type="button" class="er-map__dot"
                        :class="{ 'is-ok': estaBien(r), 'is-bad': !estaBien(r), 'is-changed': cambiada(r) || (!editando && r.editada), 'is-auto': autoIdx.has(r.indice) }"
                        :title="`Pregunta ${r.numero}`" @click="irA(r)">{{ r.numero }}</button>
                </div>
                <div class="er-map__legend">
                    <span><i class="dot dot--ok"></i>Correcta</span>
                    <span><i class="dot dot--bad"></i>Incorrecta</span>
                    <span><i class="dot dot--gold"></i>{{ editando ? 'Cambiada' : 'Corregida' }}</span>
                </div>
            </aside>

            <!-- Preguntas -->
            <section class="er-list">
                <div class="er-toolbar">
                    <div class="er-tabs">
                        <button v-for="f in filtros" :key="f.key" type="button" class="er-tab" :class="{ 'is-active': filtro === f.key }"
                            @click="filtro = f.key">
                            {{ f.label }} <span>{{ f.n }}</span>
                        </button>
                    </div>
                    <a-input v-model:value="busqueda" allow-clear placeholder="Buscar pregunta o número" class="er-search">
                        <template #prefix><SearchOutlined /></template>
                    </a-input>
                </div>

                <a-empty v-if="!visibles.length" description="No hay preguntas con este filtro" style="padding: 40px 0" />

                <article v-for="r in visibles" :id="`er-q-${r.indice}`" :key="r.indice" class="er-q"
                    :class="{ 'is-ok': estaBien(r), 'is-bad': !estaBien(r), 'is-changed': cambiada(r) }">
                    <header class="er-q__head">
                        <span class="er-q__num">{{ r.numero }}</span>
                        <p class="er-q__text">{{ r.pregunta }}</p>
                        <div class="er-q__tags">
                            <a-tag v-if="autoIdx.has(r.indice)" color="purple" :bordered="false"><ThunderboltOutlined /> Ajuste automático</a-tag>
                            <a-tag v-else-if="cambiada(r)" color="gold" :bordered="false">Cambiada</a-tag>
                            <a-tag v-if="!editando && r.editada" color="gold" :bordered="false">Corregida por admin</a-tag>
                            <span class="er-q__status">
                                <CheckCircleFilled v-if="estaBien(r)" /><CloseCircleFilled v-else />
                            </span>
                        </div>
                    </header>

                    <ul v-if="r.opciones.length" class="er-opts">
                        <li v-for="(op, i) in r.opciones" :key="op.texto" class="er-opt" :class="claseOpcion(r, op)"
                            @click="elegir(r, op.texto)">
                            <span class="er-opt__letter">{{ letra(i) }}</span>
                            <span class="er-opt__text">{{ op.texto }}</span>
                            <span class="er-opt__badges">
                                <em v-if="op.correcta" class="b-right">Correcta</em>
                                <em v-if="editando ? seleccion[r.indice] === op.texto : r.respuesta === op.texto" class="b-chosen">
                                    {{ editando && seleccion[r.indice] !== r.respuesta ? 'Nueva respuesta' : 'Respuesta del estudiante' }}
                                </em>
                                <em v-else-if="editando && r.respuesta === op.texto" class="b-orig">Original</em>
                            </span>
                        </li>
                    </ul>
                    <div v-else class="er-plain">
                        <div>Respuesta del estudiante: <strong>{{ r.respuesta }}</strong></div>
                        <div v-if="!r.correcta">Respuesta correcta: <strong class="txt-ok">{{ r.respuesta_correcta }}</strong></div>
                    </div>

                    <div v-if="r.justificacion && !editando" class="er-just">
                        <InfoCircleOutlined /> {{ r.justificacion }}
                    </div>
                </article>
            </section>
        </div>

        <a-card v-if="ediciones.length" :bordered="false" class="er-hist">
            <template #title><HistoryOutlined /> Historial de correcciones</template>
            <a-timeline style="margin-top: 8px">
                <a-timeline-item v-for="(e, i) in ediciones" :key="i" color="gold">
                    <b>{{ e.admin }}</b> · <span class="muted">{{ e.fecha }}</span><br />
                    {{ e.respuestas_modificadas }} respuesta(s) modificada(s) · calificación {{ e.calificacion_anterior ?? '—' }} → <b>{{ e.calificacion_nueva }}</b>
                    <a-tag v-if="e.manual" :bordered="false" style="margin-left: 6px">manual</a-tag>
                    <div v-if="e.motivo" class="muted">Motivo: {{ e.motivo }}</div>
                </a-timeline-item>
            </a-timeline>
        </a-card>
    </AdminLayout>
</template>

<style scoped>
.muted { color: var(--sains-muted); }
.txt-ok { color: #16a34a; }
.dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }
.dot--ok { background: #22c55e; }
.dot--bad { background: #ef4444; }
.dot--gold { background: #f5b301; }

/* ---------- Hero ---------- */
.er-hero {
    --tone: #1b75bc; --tone-soft: #e8f2fa;
    display: flex; align-items: center; gap: 28px; flex-wrap: wrap;
    padding: 24px 28px; margin-bottom: 18px; border-radius: 20px;
    background: var(--sains-surface); border: 1px solid var(--sains-line); box-shadow: var(--sains-shadow-md);
    position: relative; overflow: hidden;
}
.er-hero::before {
    content: ''; position: absolute; inset: 0 auto 0 0; width: 6px; background: var(--tone);
}
.er-hero.is-top { --tone: #059669; --tone-soft: #d1fae5; }
.er-hero.is-ok { --tone: #1b75bc; --tone-soft: #e1eef8; }
.er-hero.is-bad { --tone: #dc2626; --tone-soft: #fee2e2; }

.er-ring { position: relative; width: 132px; height: 132px; flex: none; }
.er-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
.er-ring__bg { fill: none; stroke: var(--tone-soft); stroke-width: 11; }
.er-ring__fg { fill: none; stroke: var(--tone); stroke-width: 11; stroke-linecap: round; transition: stroke-dasharray .6s cubic-bezier(.16, 1, .3, 1); }
.er-ring__val { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.er-ring__val b { font-size: 2.1rem; font-weight: 800; color: var(--sains-ink); letter-spacing: -.03em; line-height: 1; }
.er-ring__val small { font-size: 11.5px; color: var(--sains-muted); margin-top: 4px; }

.er-hero__body { flex: 1; min-width: 260px; }
.er-hero__top { display: flex; gap: 8px; flex-wrap: wrap; }
.er-pill {
    display: inline-flex; align-items: center; gap: 6px; padding: 3px 12px; border-radius: 999px;
    font-size: 12px; font-weight: 700; background: var(--tone-soft); color: var(--tone);
}
.er-pill.is-edit { background: #fef3c7; color: #b45309; }
.er-hero__name { margin: 8px 0 6px; font-size: 1.35rem; font-weight: 800; color: var(--sains-ink); }
.er-meta { display: flex; flex-wrap: wrap; gap: 6px 18px; font-size: 13px; color: var(--sains-muted); }
.er-meta span { display: inline-flex; align-items: center; gap: 6px; }
.er-bar { margin-top: 16px; height: 10px; border-radius: 999px; background: #fee2e2; overflow: hidden; }
.er-bar__ok { height: 100%; background: linear-gradient(90deg, #16a34a, #4ade80); border-radius: 999px; transition: width .5s cubic-bezier(.16, 1, .3, 1); }
.er-bar__legend { display: flex; gap: 18px; flex-wrap: wrap; margin-top: 8px; font-size: 12.5px; color: var(--sains-ink); }
.er-bar__legend .muted { margin-left: auto; }

/* ---------- Panel de corrección ---------- */
.er-edit {
    margin-bottom: 18px; padding: 18px 22px; border-radius: 18px;
    background: linear-gradient(180deg, #fffdf5, #fffbeb); border: 1px solid #f5d27a;
    box-shadow: 0 18px 40px -26px rgba(180, 83, 9, .45);
}
.er-edit__head { display: flex; gap: 20px; align-items: center; flex-wrap: wrap; }
.er-edit__head > div:first-child { flex: 1; min-width: 260px; }
.er-edit__head b { display: flex; align-items: center; gap: 8px; font-size: 15px; color: var(--sains-ink); }
.er-edit__head span { font-size: 12.5px; color: var(--sains-muted); }
.er-compare { display: flex; align-items: center; gap: 12px; }
.er-compare > div { text-align: center; padding: 6px 14px; border-radius: 12px; background: #fff; border: 1px solid #f1e3b5; min-width: 78px; }
.er-compare small { display: block; font-size: 11px; color: var(--sains-muted); }
.er-compare b { font-size: 22px; font-weight: 800; color: var(--sains-muted); }
.er-compare .is-now { border-color: var(--sains-primary); }
.er-compare .is-now b { color: var(--sains-primary); }
.er-compare__arrow { font-size: 20px; color: #b45309; }
.er-edit__grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px; }
.er-box { background: #fff; border: 1px solid #f1e3b5; border-radius: 14px; padding: 12px 14px; }
.er-box__title { display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; color: var(--sains-ink); margin-bottom: 10px; }
.er-box__row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.er-box__alert { margin-top: 10px; border-radius: 10px; font-size: 12.5px; }
.er-box__hint { margin: 8px 0 0; font-size: 12px; color: var(--sains-muted); }
.er-edit__foot { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-top: 14px; }
.er-edit__count { font-size: 13px; color: var(--sains-muted); }
.er-edit__count b { color: var(--sains-ink); }
.er-edit__actions { display: flex; gap: 8px; }
.er-err { color: #dc2626; font-size: 12px; margin-top: 6px; }
.er-slide-enter-active, .er-slide-leave-active { transition: all .25s ease; }
.er-slide-enter-from, .er-slide-leave-to { opacity: 0; transform: translateY(-8px); }

/* ---------- Layout ---------- */
.er-layout { display: grid; grid-template-columns: 250px 1fr; gap: 18px; align-items: start; }
.er-map {
    position: sticky; top: 72px; padding: 16px; border-radius: 16px;
    background: var(--sains-surface); border: 1px solid var(--sains-line); box-shadow: var(--sains-shadow-sm);
}
.er-map__live { margin-bottom: 14px; padding: 10px 12px; border-radius: 12px; background: #fffbeb; border: 1px solid #f5d27a; text-align: center; }
.er-map__live small { display: block; font-size: 11px; color: var(--sains-muted); }
.er-map__live b { display: block; font-size: 26px; font-weight: 800; color: var(--sains-primary); line-height: 1.2; }
.er-map__live span { font-size: 11.5px; color: var(--sains-muted); }
.er-map__title { font-weight: 700; font-size: 13px; color: var(--sains-ink); margin-bottom: 10px; }
.er-map__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(34px, 1fr)); gap: 6px; max-height: 340px; overflow: auto; padding: 2px; }
.er-map__dot {
    height: 32px; border-radius: 9px; border: 1px solid transparent; cursor: pointer;
    font-size: 12px; font-weight: 700; transition: transform .12s, box-shadow .12s;
}
.er-map__dot:hover { transform: translateY(-1px); box-shadow: 0 4px 10px -4px rgba(15, 23, 42, .3); }
.er-map__dot.is-ok { background: #dcfce7; color: #15803d; }
.er-map__dot.is-bad { background: #fee2e2; color: #b91c1c; }
.er-map__dot.is-changed { border-color: #f5b301; box-shadow: inset 0 0 0 1px #f5b301; }
.er-map__dot.is-auto { border-color: #145a92; box-shadow: inset 0 0 0 1px #145a92; }
.er-map__legend { display: flex; flex-wrap: wrap; gap: 6px 12px; margin-top: 12px; font-size: 11.5px; color: var(--sains-muted); }

.er-toolbar { display: flex; gap: 12px; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-bottom: 12px; }
.er-tabs { display: inline-flex; gap: 4px; padding: 4px; border-radius: 12px; background: #eef2f8; flex-wrap: wrap; }
.er-tab {
    border: 0; background: transparent; padding: 6px 12px; border-radius: 9px; cursor: pointer;
    font-size: 13px; font-weight: 600; color: var(--sains-muted); transition: all .15s;
}
.er-tab span { margin-left: 4px; font-size: 11.5px; padding: 1px 7px; border-radius: 999px; background: rgba(15, 23, 42, .06); }
.er-tab.is-active { background: #fff; color: var(--sains-primary); box-shadow: var(--sains-shadow-sm); }
.er-tab.is-active span { background: #e1eef8; }
.er-search { max-width: 260px; border-radius: 10px; }

/* ---------- Pregunta ---------- */
.er-q {
    position: relative; padding: 16px 18px 14px; margin-bottom: 12px; border-radius: 16px;
    background: var(--sains-surface); border: 1px solid var(--sains-line); box-shadow: var(--sains-shadow-sm);
    border-left: 4px solid #22c55e; scroll-margin-top: 120px; transition: border-color .2s, box-shadow .2s;
}
.er-q.is-bad { border-left-color: #ef4444; }
.er-q.is-changed { box-shadow: 0 0 0 2px rgba(245, 179, 1, .35), var(--sains-shadow-sm); }
.er-q__head { display: flex; gap: 12px; align-items: flex-start; }
.er-q__num {
    flex: none; width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center;
    font-weight: 800; font-size: 13px; background: #dcfce7; color: #15803d;
}
.er-q.is-bad .er-q__num { background: #fee2e2; color: #b91c1c; }
.er-q__text { flex: 1; margin: 4px 0 0; font-weight: 600; color: var(--sains-ink); line-height: 1.45; }
.er-q__tags { display: flex; align-items: center; gap: 4px; flex: none; }
.er-q__status { font-size: 20px; color: #22c55e; margin-left: 4px; }
.er-q.is-bad .er-q__status { color: #ef4444; }

.er-opts { list-style: none; margin: 12px 0 0 42px; padding: 0; display: grid; gap: 6px; }
.er-opt {
    display: flex; align-items: center; gap: 10px; padding: 8px 12px; border-radius: 11px;
    border: 1px solid var(--sains-line); background: #fafbfd; font-size: 13.5px; color: var(--sains-ink);
    transition: background .15s, border-color .15s;
}
.er-opt.is-click { cursor: pointer; }
.er-opt.is-click:hover { border-color: var(--sains-primary); background: #f2f8fc; }
.er-opt__letter {
    flex: none; width: 24px; height: 24px; border-radius: 7px; display: grid; place-items: center;
    font-size: 12px; font-weight: 700; background: #e9edf4; color: var(--sains-muted);
}
.er-opt__text { flex: 1; }
.er-opt__badges { display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.er-opt__badges em { font-style: normal; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
.b-right { background: #dcfce7; color: #15803d; }
.b-chosen { background: #e1eef8; color: var(--sains-primary); }
.b-orig { background: #f1f5f9; color: var(--sains-muted); text-decoration: line-through; }
.er-opt.is-right { border-color: #bbf7d0; background: #f0fdf4; }
.er-opt.is-right .er-opt__letter { background: #22c55e; color: #fff; }
.er-opt.is-chosen { border-color: var(--sains-primary); box-shadow: inset 0 0 0 1px var(--sains-primary); }
.er-opt.is-wrong { border-color: #fca5a5; background: #fef2f2; box-shadow: inset 0 0 0 1px #f87171; }
.er-opt.is-wrong .er-opt__letter { background: #ef4444; color: #fff; }
.er-opt.is-wrong .b-chosen { background: #fee2e2; color: #b91c1c; }
.er-opt.is-orig { opacity: .75; border-style: dashed; }

.er-plain { margin: 10px 0 0 42px; font-size: 13.5px; display: grid; gap: 4px; }
.er-just {
    margin: 10px 0 0 42px; padding: 8px 12px; border-radius: 10px; background: #f8fafc;
    font-size: 12.5px; color: var(--sains-muted); line-height: 1.5;
}
.er-hist { margin-top: 18px; border-radius: 16px; }

@media (max-width: 992px) {
    .er-layout { grid-template-columns: 1fr; }
    .er-map { position: static; }
    .er-map__grid { max-height: 160px; }
    .er-edit__grid { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .er-hero { padding: 20px; justify-content: center; text-align: center; }
    .er-hero__top, .er-meta { justify-content: center; }
    .er-opts, .er-plain, .er-just { margin-left: 0; }
    .er-search { max-width: none; width: 100%; }
    .er-opt { flex-wrap: wrap; }
}
</style>
