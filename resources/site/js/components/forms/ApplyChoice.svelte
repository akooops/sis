<script>
    /**
     * The two ways into a long form: fill it yourself, or upload a CV and have it
     * filled in for you.
     *
     * A GATE, NOT A STEP. The form does not exist until a choice is made, which is
     * the point — a thirty-field application shown cold is what makes people
     * leave. Neither path changes what is submitted: the AI route writes the same
     * answers into the same renderer, the applicant checks every one, and the
     * submission takes the identical validated path a typed one does.
     *
     * The uploaded CV is kept and passed through as the form's own file answer, so
     * nobody is asked for it twice.
     */
    import { translate } from '@site/lib/forms/i18n';

    let {
        labels = {},
        uploadAction = null,
        parseAction = null,
        token = null,
        csrf = null,
        cvKey = 'cv',
        onready = null,
    } = $props();

    let busy = $state(false);
    let error = $state(null);
    let fileInput = $state(null);

    const t = (key, fallback) => labels[key] ?? fallback;

    /** Straight into the blank form. */
    function manual() {
        onready?.({ values: {} });
    }

    /**
     * Upload, parse, then open the form already filled in.
     *
     * A PARSE FAILURE IS NOT AN ERROR THE APPLICANT SHOULD SEE AS A DEAD END — the
     * form opens anyway, with whatever came back (often nothing) and the CV
     * already attached. Being asked to type it yourself is a far better outcome
     * than being stopped.
     */
    async function withCv(event) {
        const file = event.currentTarget.files?.[0];

        if (!file) return;

        busy = true;
        error = null;

        try {
            const body = new FormData();
            body.append('file', file);
            body.append('field', cvKey);
            body.append('submission_token', token ?? '');
            body.append('_token', csrf ?? '');

            const uploaded = await fetch(uploadAction, {
                method: 'POST',
                body,
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            if (!uploaded.ok) throw new Error('upload');

            const media = await uploaded.json();

            const parsed = await fetch(parseAction, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf ?? '',
                },
                body: JSON.stringify({ media: media.id, submission_token: token }),
            });

            const values = parsed.ok ? (await parsed.json()).values ?? {} : {};

            // The CV the applicant just gave us IS the form's file answer. Asking
            // for it a second time on the next screen would be the single most
            // obvious way to make this feature feel broken.
            values[cvKey] = { id: media.id, name: media.name };

            onready?.({ values });
        } catch {
            error = t('cvFailed', 'That file could not be read. You can still fill the form in yourself.');
            busy = false;
        }
    }
</script>

<!-- THE TOKEN SCOPE, and it has to be declared here. The gate mounts straight
     into [data-sisf-root], which deliberately carries no `sisf` class —
     FormRenderer emits its own root, and that does not exist yet. Every --sisf-*
     below would otherwise be undefined, which is not a fallback but an invalid
     declaration: the cards would lose their border and radius outright. Nothing
     nests, because the gate is unmounted before the renderer mounts. -->
<div class="sisf">
    <div class="sisf-apply-choice">
        <button type="button" class="sisf-apply-card" onclick={manual} disabled={busy}>
            <i class="uil uil-edit-alt" aria-hidden="true"></i>
            <span class="sisf-apply-title">{t('fillManually', 'Fill in manually')}</span>
            <span class="sisf-apply-hint">{t('fillManuallyHint', 'Answer the questions yourself.')}</span>
        </button>

        <!-- A LABEL, not a button: it owns the file input, so a click opens the
             picker with no JS in between and it stays keyboard-reachable — which
             is also why the input is moved off-screen by class rather than by
             `hidden`, since a display:none control takes no focus at all. -->
        <label class="sisf-apply-card" class:is-busy={busy}>
            <i class="uil uil-file-upload-alt" aria-hidden="true"></i>
            <span class="sisf-apply-title">{t('fillWithAi', 'Fill in from my CV')}</span>
            <span class="sisf-apply-hint">
                {busy
                    ? t('cvReading', 'Reading your CV…')
                    : t('fillWithAiHint', 'Upload your CV and we will fill in what we can. You can correct anything.')}
            </span>
            <input
                bind:this={fileInput}
                type="file"
                class="sisf-apply-file"
                accept=".pdf,.doc,.docx"
                disabled={busy}
                onchange={withCv}
            />
        </label>
    </div>

    {#if error}
        <p class="sisf-error" role="alert">{error}</p>
    {/if}
</div>
