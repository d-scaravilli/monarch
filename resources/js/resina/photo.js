/*
 * Photo analysis helpers (dominantColors and the canvas sampling of the
 * prototype), plus the in-browser resize that keeps uploads small.
 */
import { dE, hexLin, lab } from './color.js';

/*
 * Main colors of an image: k-means (k clusters, 12 rounds) over every
 * other pixel of a small canvas, clusters under 2% dropped, near
 * duplicates (ΔE < 9) merged. [{ hex, pct }], biggest first.
 */
export function dominantColors(cv, k) {
    const d = cv.getContext('2d').getImageData(0, 0, cv.width, cv.height).data, px = [];
    for (let i = 0; i < d.length; i += 8) {
        if (d[i + 3] < 128) continue;
        px.push([d[i], d[i + 1], d[i + 2]]);
    }
    if (!px.length) return [];
    const sorted = px.slice().sort((a, b) => a[0] + a[1] + a[2] - (b[0] + b[1] + b[2]));
    let c = [];
    for (let j = 0; j < k; j++) c.push(sorted[Math.floor(((j + 0.5) * sorted.length) / k)].slice());
    let cnt = [];
    for (let it = 0; it < 12; it++) {
        const sum = c.map(() => [0, 0, 0, 0]);
        px.forEach((p) => {
            let bi = 0, bd = 1e12;
            for (let q = 0; q < c.length; q++) {
                const x = p[0] - c[q][0], y = p[1] - c[q][1], z = p[2] - c[q][2], dd = x * x + y * y + z * z;
                if (dd < bd) {
                    bd = dd;
                    bi = q;
                }
            }
            const s = sum[bi];
            s[0] += p[0];
            s[1] += p[1];
            s[2] += p[2];
            s[3]++;
        });
        c = sum.map((s, q) => (s[3] ? [s[0] / s[3], s[1] / s[3], s[2] / s[3]] : c[q]));
        cnt = sum.map((s) => s[3]);
    }
    const out = c
        .map((v, q) => ({ hex: '#' + v.map((x) => Math.round(x).toString(16).padStart(2, '0')).join('').toUpperCase(), pct: cnt[q] / px.length }))
        .filter((o) => o.pct >= 0.02)
        .sort((a, b) => b.pct - a.pct);
    const res = [];
    out.forEach((o) => {
        const L = lab(hexLin(o.hex));
        if (!res.some((r) => dE(lab(hexLin(r.hex)), L) < 9)) res.push(o);
    });
    return res;
}

/* The average color of the 5×5 pixels around a canvas point. */
export function sampleAt(cv, x, y) {
    const d = cv.getContext('2d').getImageData(Math.max(0, x - 2), Math.max(0, y - 2), 5, 5).data, s = [0, 0, 0];
    let n = 0;
    for (let i = 0; i < d.length; i += 4) {
        s[0] += d[i];
        s[1] += d[i + 1];
        s[2] += d[i + 2];
        n++;
    }
    return '#' + s.map((v) => Math.round(v / n).toString(16).padStart(2, '0')).join('').toUpperCase();
}

/* Draw an image into a new canvas whose longer side is at most max px. */
export function scaledCanvas(img, max) {
    const scale = Math.min(1, max / Math.max(img.width, img.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(img.width * scale);
    canvas.height = Math.round(img.height * scale);
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
    return canvas;
}

export function canvasToJpeg(canvas, quality) {
    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('toBlob'))), 'image/jpeg', quality);
    });
}

/*
 * Decode a picked file into an image, or explain why not. Browsers that
 * can't read HEIC (iPhone photos outside Safari) fail here.
 */
export function loadImageFile(file) {
    return new Promise((resolve, reject) => {
        const heic = /\.hei[cf]$/i.test(file.name) || /image\/hei[cf]/i.test(file.type);
        if (!heic && file.type && !/^image\//.test(file.type)) {
            reject(new Error('not-image'));
            return;
        }
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error(heic ? 'heic' : 'unreadable'));
        };
        img.src = url;
    });
}
