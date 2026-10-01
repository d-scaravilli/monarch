/*
 * Queue color searches in the mix worker; onResult(id, result) fires
 * once per job. Without Worker support, the same work runs on the main
 * thread one job per tick, so the page still repaints in between.
 */
import { autoBSL, createPalette, findMixes } from './color.js';

export function runMixJobs(paints, jobs, onResult) {
    if (!jobs.length) return;

    if (typeof Worker !== 'undefined') {
        try {
            const worker = new Worker(new URL('./mix-worker.js', import.meta.url), { type: 'module' });
            let remaining = jobs.length;
            worker.onmessage = (event) => {
                onResult(event.data.id, event.data.result);
                if (--remaining === 0) worker.terminate();
            };
            worker.postMessage({ paints: JSON.parse(JSON.stringify(paints)), jobs });
            return;
        } catch (e) {
            // Fall through to the main-thread queue.
        }
    }

    const palette = createPalette(paints);
    const queue = jobs.slice();
    const next = () => {
        const job = queue.shift();
        if (!job) return;
        onResult(job.id, job.kind === 'auto' ? autoBSL(job.hex, palette) : findMixes(job.hex, job.three !== false, palette));
        setTimeout(next, 0);
    };
    setTimeout(next, 0);
}
