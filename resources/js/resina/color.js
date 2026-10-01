/*
 * Color math for "3D - Resina", ported one to one from the prototype
 * (docs/resina/reference.html). Keep the formulas identical: results are
 * checked against the prototype.
 *
 * A "palette" is the set of paints a page works with. Each paint has a
 * stable key ("p12" catalog paint, "u5" custom paint) used in mixes:
 * a mix is { key: drops }.
 */

export function h2r(h) {
    h = h.replace('#', '');
    if (h.length === 3) h = h.split('').map((c) => c + c).join('');
    return [0, 2, 4].map((i) => parseInt(h.substr(i, 2), 16) / 255);
}

export function toLin(c) {
    return c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
}

export function toS(c) {
    c = Math.max(0, Math.min(1, c));
    return c <= 0.0031308 ? 12.92 * c : 1.055 * Math.pow(c, 1 / 2.4) - 0.055;
}

export function lin2hex(l) {
    return '#' + l.map((c) => Math.round(toS(c) * 255).toString(16).padStart(2, '0')).join('').toUpperCase();
}

export function hexLin(h) {
    return h2r(h).map(toLin);
}

export function lab(l) {
    const r = l[0], g = l[1], b = l[2];
    const X = (r * 0.4124 + g * 0.3576 + b * 0.1805) / 0.95047, Y = r * 0.2126 + g * 0.7152 + b * 0.0722, Z = (r * 0.0193 + g * 0.1192 + b * 0.9505) / 1.08883;
    const f = (t) => (t > 0.008856 ? Math.cbrt(t) : 7.787 * t + 16 / 116);
    const fx = f(X), fy = f(Y), fz = f(Z);
    return [116 * fy - 16, 500 * (fx - fy), 200 * (fy - fz)];
}

export function lab2lin(L) {
    const fy = (L[0] + 16) / 116, fx = fy + L[1] / 500, fz = fy - L[2] / 200;
    const inv = (t) => {
        const t3 = t * t * t;
        return t3 > 0.008856 ? t3 : (t - 16 / 116) / 7.787;
    };
    const X = inv(fx) * 0.95047, Y = inv(fy), Z = inv(fz) * 1.08883;
    const r = 3.2406 * X - 1.5372 * Y - 0.4986 * Z, g = -0.9689 * X + 1.8758 * Y + 0.0415 * Z, b = 0.0557 * X - 0.204 * Y + 1.057 * Z;
    return [r, g, b].map((v) => Math.max(0.0005, Math.min(1, v)));
}

export function dE(a, b) {
    return Math.sqrt(Math.pow(a[0] - b[0], 2) + Math.pow(a[1] - b[1], 2) + Math.pow(a[2] - b[2], 2));
}

/**
 * The TMM airbrush bottles are metallic too (the prototype flags them
 * both "metal" and "aero"), so type "airbrush" counts as metallic.
 */
export function isMetallicType(type) {
    return type === 'metallic' || type === 'airbrush';
}

/**
 * @param {Array<{key: string, code: string, name: string, hex: string, type: string}>} paints
 */
export function createPalette(paints) {
    const list = paints.map((p) => {
        const lin = hexLin(p.hex);
        return {
            ...p,
            metal: isMetallicType(p.type),
            wash: p.type === 'wash',
            aero: p.type === 'airbrush',
            lin,
            log: lin.map((v) => Math.log(Math.max(v, 0.002))),
            lab: lab(lin),
        };
    });
    const byKey = {};
    list.forEach((p) => {
        byKey[p.key] = p;
    });
    return { list, byKey, cache: {} };
}

/* Pigment-like mix: weighted geometric mean in linear light. */
export function mixLin(mix, palette) {
    let S = 0;
    const acc = [0, 0, 0];
    Object.keys(mix).forEach((id) => {
        const n = mix[id], p = palette.byKey[id];
        if (!n || !p) return;
        S += n;
        for (let k = 0; k < 3; k++) acc[k] += n * p.log[k];
    });
    return S ? acc.map((v) => Math.exp(v / S)) : null;
}

/* Metallic when metallic paints are at least 30% of the drops. */
export function isMetal(mix, palette) {
    let S = 0, M = 0;
    Object.keys(mix).forEach((id) => {
        const p = palette.byKey[id];
        if (!p) return;
        S += mix[id];
        if (p.metal) M += mix[id];
    });
    return S > 0 && M / S >= 0.3;
}

export function swStyle(lin, metal) {
    const hex = lin2hex(lin);
    if (!metal) return 'background:' + hex;
    const light = lin2hex(lin.map((v) => v + (1 - v) * 0.6)), dark = lin2hex(lin.map((v) => v * 0.3));
    return 'background:linear-gradient(135deg,' + light + ' 0%,' + hex + ' 30%,' + dark + ' 55%,' + hex + ' 78%,' + light + ' 100%)';
}

export const EMPTY_STYLE = 'background:var(--resina-chip, #e5e7eb)';

export function hexStyle(hex, metal) {
    return swStyle(hexLin(hex), metal);
}

export function mixStyle(mix, palette) {
    const l = mixLin(mix, palette);
    return l ? swStyle(l, isMetal(mix, palette)) : EMPTY_STYLE;
}

export function mixHex(mix, palette) {
    const l = mixLin(mix, palette);
    return l ? lin2hex(l) : '—';
}

export function drops(n) {
    return n + (n == 1 ? ' goccia' : ' gocce');
}

export function ratioText(mix, palette) {
    return Object.keys(mix)
        .filter((id) => palette.byKey[id])
        .map((id) => drops(mix[id]) + ' ' + palette.byKey[id].name + ' (' + palette.byKey[id].code + ')')
        .join(' + ');
}

/** [badge kind, label] — "ok" < 4, "mid" < 10, "far" otherwise. */
export function closeness(d) {
    return d < 4 ? ['ok', 'quasi identico'] : d < 10 ? ['mid', 'vicino'] : ['far', 'approssimativo'];
}

function gcd(a, b) {
    return b ? gcd(b, a % b) : a;
}

/*
 * Best mixes for a target color among the palette's normal paints
 * (no metallics, washes or airbrush): single paints, pairs with 1–8
 * drops each, and (three=true) triples with 1–5 drops each, only in
 * lowest terms. Score: ΔE + 0.9·(paints−1) + 0.05·drops. Top 6.
 */
export function findMixes(targetHex, three, palette) {
    const key = targetHex + (three ? '3' : '2');
    if (palette.cache[key]) return palette.cache[key];
    const tLab = lab(hexLin(targetHex));
    const pool = palette.list.filter((p) => !p.metal && !p.wash && !p.aero);
    const n = pool.length, best = {};
    function consider(ids, parts) {
        let S = 0, a0 = 0, a1 = 0, a2 = 0;
        for (let i = 0; i < ids.length; i++) {
            const L = pool[ids[i]].log, w = parts[i];
            S += w;
            a0 += w * L[0];
            a1 += w * L[1];
            a2 += w * L[2];
        }
        const l = [Math.exp(a0 / S), Math.exp(a1 / S), Math.exp(a2 / S)];
        const d = dE(lab(l), tLab), score = d + 0.9 * (ids.length - 1) + 0.05 * S, k = ids.join(',');
        if (!best[k] || best[k].score > score) best[k] = { score, d, ids: ids.slice(), parts: parts.slice(), lin: l };
    }
    let a, b, c, x, y, z;
    for (a = 0; a < n; a++) consider([a], [1]);
    for (a = 0; a < n; a++) for (b = a + 1; b < n; b++) for (x = 1; x <= 8; x++) for (y = 1; y <= 8; y++) {
        if (gcd(x, y) === 1) consider([a, b], [x, y]);
    }
    if (three) for (a = 0; a < n; a++) for (b = a + 1; b < n; b++) for (c = b + 1; c < n; c++) for (x = 1; x <= 5; x++) for (y = 1; y <= 5; y++) for (z = 1; z <= 5; z++) {
        if (gcd(gcd(x, y), z) === 1) consider([a, b, c], [x, y, z]);
    }
    const list = Object.keys(best)
        .map((k) => best[k])
        .sort((p, q) => p.score - q.score)
        .slice(0, 6)
        .map((r) => {
            const mix = {};
            r.ids.forEach((ix, i) => {
                mix[pool[ix].key] = r.parts[i];
            });
            return { mix, d: r.d, hex: lin2hex(r.lin) };
        });
    palette.cache[key] = list;
    return list;
}

/*
 * Automatic shade / base / light recipe for a target color: shade is
 * L−17 with a and b ×0.92, light is L+15 with a and b ×0.82.
 */
export function autoBSL(hex, palette) {
    const L = lab(hexLin(hex));
    const sh = lin2hex(lab2lin([Math.max(8, L[0] - 17), L[1] * 0.92, L[2] * 0.92]));
    const li = lin2hex(lab2lin([Math.min(96, L[0] + 15), L[1] * 0.82, L[2] * 0.82]));
    const b = findMixes(hex, true, palette)[0], s = findMixes(sh, true, palette)[0], l = findMixes(li, true, palette)[0];
    return [
        { role: 'Ombra', mix: s.mix, usage: 'Calcolata in automatico: incavi e zone basse.', d: s.d },
        { role: 'Base', mix: b.mix, usage: 'Calcolata in automatico: su tutta la zona.', d: b.d },
        { role: 'Luce', mix: l.mix, usage: 'Calcolata in automatico: rilievi.', d: l.d },
    ];
}

/** The closest suggested paint to buy, with its ΔE. */
export function nearestBuy(hex, suggestions) {
    const t = lab(hexLin(hex));
    return suggestions
        .map((b) => ({ b, d: dE(t, lab(hexLin(b.hex))) }))
        .sort((x, y) => x.d - y.d)[0];
}

/** The palette paint closest to a linear color: { p, d }. */
export function nearestPaint(lin, palette) {
    const L = lab(lin);
    return palette.list.map((p) => ({ p, d: dE(L, p.lab) })).sort((a, b) => a.d - b.d)[0];
}

/** Lightness of a mix, for the "toni" scale (darkest first). */
export function mixLightness(mix, palette) {
    const l = mixLin(mix, palette);
    return l ? lab(l)[0] : 0;
}
