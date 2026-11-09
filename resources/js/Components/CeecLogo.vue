<script setup>
/**
 * Logo CEEC · Centro de Estudios Cafetales (vectorial, nítido a cualquier tamaño).
 *
 *  <CeecLogo />                 → marca + leyenda (horizontal)
 *  <CeecLogo stacked />         → marca arriba, leyenda centrada abajo
 *  <CeecLogo mark-only />       → sólo las letras "ceec"
 *  <CeecLogo tone="light" />    → versión blanca para fondos oscuros
 */
const props = defineProps({
    tone: { type: String, default: 'brand' }, // 'brand' | 'light'
    stacked: { type: Boolean, default: false },
    markOnly: { type: Boolean, default: false },
});

const R = 40;          // radio de cada letra
const GAP = 86;        // separación entre centros (las letras casi se tocan)
const CY = 50;
const xs = [50, 50 + GAP, 50 + GAP * 2, 50 + GAP * 3];

const pt = (cx, deg) => {
    const a = (deg * Math.PI) / 180;
    return [+(cx + R * Math.cos(a)).toFixed(2), +(CY - R * Math.sin(a)).toFixed(2)];
};

// "C": círculo abierto hacia la derecha.
const letraC = (cx) => {
    const [x1, y1] = pt(cx, 42);
    const [x2, y2] = pt(cx, -42);
    return `M${x1} ${y1} A${R} ${R} 0 1 0 ${x2} ${y2}`;
};

// "e": círculo abierto abajo a la derecha, cruzado por una diagonal.
const letraE = (cx) => {
    const [x1, y1] = pt(cx, 38);
    const [x2, y2] = pt(cx, -32);
    const [dx, dy] = pt(cx, 218);
    return `M${x1} ${y1} A${R} ${R} 0 1 0 ${x2} ${y2} M${dx} ${dy} L${x1} ${y1}`;
};

const letras = [letraC(xs[0]), letraE(xs[1]), letraE(xs[2]), letraC(xs[3])];
const markW = xs[3] + R + 6; // ≈ 354
</script>

<template>
    <span class="ceec" :class="[`ceec--${props.tone}`, { 'ceec--stacked': stacked }]" role="img"
        aria-label="CEEC · Centro de Estudios Cafetales">
        <svg class="ceec__mark" :viewBox="`0 0 ${markW} 100`" aria-hidden="true">
            <path v-for="(d, i) in letras" :key="i" :d="d" />
        </svg>

        <template v-if="!markOnly">
            <span class="ceec__bar" aria-hidden="true"></span>
            <span class="ceec__text" aria-hidden="true">
                <span>centro</span>
                <span>de estudios</span>
                <b>cafetales</b>
            </span>
        </template>
    </span>
</template>

<style scoped>
.ceec {
    --ceec-ink: #1b75bc;
    --ceec-text: #1b75bc;
    --ceec-accent: #f5b301;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .9em;
    font-size: 16px;            /* escala todo el logo */
    line-height: 1;
    font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
}
.ceec--light { --ceec-ink: #ffffff; --ceec-text: rgba(255, 255, 255, .92); }

.ceec__mark { height: 4.4em; width: auto; overflow: visible; flex: none; }
.ceec__mark path {
    fill: none;
    stroke: var(--ceec-ink);
    stroke-width: 3.6;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.ceec__bar {
    align-self: stretch;
    width: 3px;
    min-height: 4.4em;
    border-radius: 3px;
    background: linear-gradient(180deg, #ffd34d, var(--ceec-accent));
    flex: none;
}

.ceec__text {
    display: flex;
    flex-direction: column;
    gap: .32em;
    text-align: left;
    color: var(--ceec-text);
    font-size: .98em;
    letter-spacing: .16em;
    font-weight: 400;
    white-space: nowrap;
}
.ceec__text b { font-weight: 700; letter-spacing: .14em; }

/* Variante apilada: marca arriba, leyenda centrada debajo */
.ceec--stacked { flex-direction: column; gap: .7em; }
.ceec--stacked .ceec__bar { align-self: center; width: 3.4em; height: 3px; min-height: 0; }
.ceec--stacked .ceec__text { flex-direction: row; gap: .45em; text-align: center; font-size: .82em; }
</style>
