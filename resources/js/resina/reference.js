/*
 * Loading a version's reference photo, four ways: choose a file, drag
 * it, paste it (Cmd+V on a Mac, «Incolla» on iPhone/iPad) or paste its
 * address (the server downloads it). Pictures are resized here with
 * photo.js; every way ends in a temporary token on the server, then:
 *  - "panel" mode: the admin confirms and the photo is attached to the
 *    version at once (checklist page, character sheet);
 *  - "form" mode: the token goes into the new-version form.
 */
import { canvasToJpeg, loadImageFile, scaledCanvas } from './photo.js';
import { sendJson } from './view.js';

const PHOTO_MAX = 1600;
const THUMB_MAX = 400;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function dataUrlToFile(dataUrl) {
    const blob = await (await fetch(dataUrl)).blob();
    return new File([blob], 'immagine', { type: blob.type || 'image/png' });
}

/* An address inside pasted or dropped text: a plain URL, or <img src> in HTML. */
function addressIn(text, html) {
    const plain = (text || '').trim().split(/\s+/)[0] || '';
    if (/^(https?:|data:image\/)/i.test(plain)) return plain;
    const match = /<img[^>]+src=["']([^"']+)["']/i.exec(html || '');
    return match ? match[1] : null;
}

/*
 * options: { temporaryUrl, mode: 'panel' | 'form', version? }
 * version: { title, searchUrl, attachUrl, source, photo } (panel mode
 * gets it when opened).
 */
export function referenceUploader(options) {
    return {
        mode: options.mode,
        open: options.mode === 'form',
        version: options.version || null,
        status: 'idle',
        error: '',
        dragging: false,
        address: '',
        preview: null,
        token: '',
        source: options.version?.source || '',
        canReadClipboard: !!(navigator.clipboard && navigator.clipboard.read),

        /* Panel mode: opened from a version row or the character sheet. */
        show(version) {
            this.version = version;
            this.reset();
            this.source = version.source || '';
            this.open = true;
            // Once the panel is visible: then Cmd+V lands on the paste zone too.
            setTimeout(() => this.$refs.pasteZone?.focus({ preventScroll: true }), 60);
        },
        close() {
            this.open = false;
        },
        reset() {
            this.status = 'idle';
            this.error = '';
            this.address = '';
            this.preview = null;
            this.token = '';
        },

        fail(message) {
            this.status = 'idle';
            this.error = message;
        },

        async fromFile(file) {
            if (!file) return;
            this.error = '';
            this.status = 'working';
            try {
                const img = await loadImageFile(file);
                const photo = await canvasToJpeg(scaledCanvas(img, PHOTO_MAX), 0.85);
                const thumb = await canvasToJpeg(scaledCanvas(img, THUMB_MAX), 0.8);
                await this.upload(photo, thumb);
            } catch (e) {
                this.fail(
                    e.message === 'heic'
                        ? "Questa è una foto HEIC e il browser non riesce ad aprirla: salvala come JPEG o caricala da Safari."
                        : e.message === 'not-image' || e.message === 'unreadable'
                          ? "Non riesco ad aprire questa immagine: prova con un JPG o un PNG."
                          : e.message || 'Caricamento non riuscito: riprova.',
                );
            }
        },

        async upload(photo, thumb) {
            const form = new FormData();
            form.append('photo', photo, 'foto.jpg');
            form.append('thumb', thumb, 'miniatura.jpg');
            if (this.source) form.append('source', this.source);
            const response = await fetch(options.temporaryUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
                body: form,
            });
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(body.message || 'Caricamento non riuscito: riprova.');
            this.received(body);
        },

        /* An address: the server downloads it (checks included). */
        async fromAddress(address) {
            address = (address || '').trim();
            if (!address) return;
            if (/^data:image\//i.test(address)) return this.fromFile(await dataUrlToFile(address));
            this.error = '';
            this.status = 'working';
            try {
                const body = await sendJsonWithMessage('POST', options.temporaryUrl, { url: address });
                if (body.dataUrl) {
                    // The server couldn't resize this format: do it here.
                    this.source = body.source || this.source;
                    return this.fromFile(await dataUrlToFile(body.dataUrl));
                }
                this.received(body);
            } catch (e) {
                this.fail(e.message);
            }
        },

        received(body) {
            this.token = body.token;
            this.preview = body.thumbUrl;
            if (body.source) this.source = body.source;
            this.status = 'preview';
        },

        /* Cmd+V / «Incolla» on the paste zone. */
        async pasted(event) {
            // Pasting into the address or source field is plain typing.
            if (event.target.closest?.('input, textarea')) return;
            if (this.status === 'working' || this.status === 'saving') return;
            event.preventDefault();
            const data = event.clipboardData;
            const file = [...(data?.files || [])].find((f) => f.type.startsWith('image/'))
                || [...(data?.items || [])].filter((i) => i.kind === 'file' && i.type.startsWith('image/')).map((i) => i.getAsFile())[0];
            if (file) return this.fromFile(file);
            const address = addressIn(data?.getData('text/uri-list') || data?.getData('text/plain'), data?.getData('text/html'));
            if (address) return this.fromAddress(address);
            this.fail("Negli appunti non c'è un'immagine né un indirizzo: copia di nuovo l'immagine.");
        },

        /* The «Incolla» button: asks the browser for the clipboard (Safari shows its own «Incolla»). */
        async pasteFromClipboard() {
            try {
                const items = await navigator.clipboard.read();
                for (const item of items) {
                    const type = item.types.find((t) => t.startsWith('image/'));
                    if (type) {
                        const blob = await item.getType(type);
                        return this.fromFile(new File([blob], 'immagine', { type }));
                    }
                }
                for (const item of items) {
                    if (item.types.includes('text/plain')) {
                        const text = await (await item.getType('text/plain')).text();
                        const html = item.types.includes('text/html') ? await (await item.getType('text/html')).text() : '';
                        const address = addressIn(text, html);
                        if (address) return this.fromAddress(address);
                    }
                }
                this.fail("Negli appunti non c'è un'immagine né un indirizzo.");
            } catch (e) {
                this.fail('Il browser non ha dato accesso agli appunti: tocca il riquadro e scegli «Incolla».');
            }
        },

        dropped(event) {
            this.dragging = false;
            const file = [...(event.dataTransfer?.files || [])].find((f) => f.type.startsWith('image/') || /\.hei[cf]$/i.test(f.name));
            if (file) return this.fromFile(file);
            const address = addressIn(event.dataTransfer?.getData('text/uri-list') || event.dataTransfer?.getData('text/plain'), event.dataTransfer?.getData('text/html'));
            if (address) return this.fromAddress(address);
            this.fail('Trascina un file immagine, o un\'immagine da un\'altra scheda.');
        },

        /* Panel mode: attach the photo to the version. */
        async confirm() {
            if (!this.token || !this.version) return;
            this.status = 'saving';
            try {
                const version = await sendJsonWithMessage('PUT', this.version.attachUrl, { token: this.token, source: this.source || null });
                window.dispatchEvent(new CustomEvent('resina-reference-saved', { detail: version }));
                this.open = false;
            } catch (e) {
                this.status = 'preview';
                this.error = e.message;
            }
        },
    };
}

async function sendJsonWithMessage(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const first = data.errors ? Object.values(data.errors)[0][0] : null;
        throw new Error(first || data.message || 'Operazione non riuscita: riprova.');
    }
    return data;
}

/* The checklist page: filter, counter, copy the missing list, edit sources. */
export function referenceChecklist(payloadId) {
    const node = document.getElementById(payloadId);
    const data = node ? JSON.parse(node.textContent) : {};

    return {
        projects: data.projects,
        onlyMissing: false,
        copied: false,

        init() {
            window.addEventListener('resina-reference-saved', (event) => this.replace(event.detail));
        },

        get versions() {
            return this.projects.flatMap((p) => p.characters.flatMap((c) => c.versions.map((v) => ({ ...v, project: p.name }))));
        },
        get missing() {
            return this.versions.filter((v) => !v.photo);
        },
        visible(version) {
            return !this.onlyMissing || !version.photo;
        },
        characterVisible(character) {
            return character.versions.some((v) => this.visible(v));
        },
        charactersOf(project, groupSlug) {
            return project.characters.filter((c) => c.group === groupSlug && this.characterVisible(c));
        },

        replace(updated) {
            this.projects.forEach((p) => p.characters.forEach((c) => c.versions.forEach((v, i) => {
                if (v.id === updated.id) c.versions[i] = { ...v, ...updated };
            })));
        },

        async saveSource(version) {
            await sendJson('PATCH', version.sourceUrl, { source: version.source || null });
        },

        get missingText() {
            return this.missing.map((v) => `${v.project} · ${v.title}${v.subtitle ? ' (' + v.subtitle + ')' : ''} — ${v.searchQuery}`).join('\n');
        },
        async copyMissing() {
            try {
                await navigator.clipboard.writeText(this.missingText);
            } catch (e) {
                const area = document.createElement('textarea');
                area.value = this.missingText;
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                area.remove();
            }
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        },
    };
}
