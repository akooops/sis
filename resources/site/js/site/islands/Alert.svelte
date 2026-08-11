<script>
    /** Dismissible status banner shared by the two wizards. */
    let { type = 'success', message = '', onclose } = $props();
</script>

{#if message}
    <div
        class="alert mb-4 flex items-center gap-2"
        class:alert-success={type === 'success'}
        class:alert-danger={type !== 'success'}
        role="alert"
    >
        <i class="uil {type === 'success' ? 'uil-check-circle' : 'uil-exclamation-triangle'}" aria-hidden="true"></i>
        <span class="flex-1">{message}</span>
        <button type="button" class="btn-close shrink-0" onclick={onclose} aria-label="Close"></button>
    </div>
{/if}
