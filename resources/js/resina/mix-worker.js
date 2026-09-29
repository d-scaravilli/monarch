/*
 * Runs the heavy color searches off the main thread, so pages stay
 * responsive on phones. Message in: { paints, jobs: [{ id, kind, hex }] }
 * with kind "find" (findMixes with triples) or "auto" (autoBSL).
 * One message out per job, as soon as it's ready: { id, result }.
 */
import { autoBSL, createPalette, findMixes } from './color.js';

self.onmessage = (event) => {
    const { paints, jobs } = event.data;
    const palette = createPalette(paints);

    for (const job of jobs) {
        const result = job.kind === 'auto' ? autoBSL(job.hex, palette) : findMixes(job.hex, true, palette);
        self.postMessage({ id: job.id, result });
    }
};
