<script>
    import { marked } from 'marked';
    import { onMount } from 'svelte';

    export let content = '';

    let html = '';

    marked.setOptions({
        breaks: true,
        gfm: true,
    });

    $: html = content ? marked.parse(content) : '';

    onMount(() => {
        marked.setOptions({ breaks: true, gfm: true });
    });
</script>

{#if content}
    <div class="chat-markdown">
        {@html html}
    </div>
{/if}

<style>
    :global(.chat-markdown) {
        font-size: 0.75rem;
        line-height: 1.5;
        word-break: break-word;
        color: inherit;
    }

    :global(.chat-markdown > :first-child) {
        margin-top: 0;
    }

    :global(.chat-markdown > :last-child) {
        margin-bottom: 0;
    }

    :global(.chat-markdown h1),
    :global(.chat-markdown h2),
    :global(.chat-markdown h3),
    :global(.chat-markdown h4) {
        font-weight: 600;
        margin: 0.5rem 0 0.25rem;
        line-height: 1.4;
    }

    :global(.chat-markdown h1) { font-size: 0.875rem; }
    :global(.chat-markdown h2) { font-size: 0.8125rem; }
    :global(.chat-markdown h3),
    :global(.chat-markdown h4) { font-size: 0.75rem; }

    :global(.chat-markdown p) {
        margin: 0.35rem 0;
        font-size: 0.75rem;
    }

    :global(.chat-markdown ul),
    :global(.chat-markdown ol) {
        margin: 0.35rem 0;
        padding-left: 1.25rem;
        font-size: 0.75rem;
    }

    :global(.chat-markdown ul) {
        list-style-type: disc;
    }

    :global(.chat-markdown ol) {
        list-style-type: decimal;
    }

    :global(.chat-markdown ul ul) {
        list-style-type: circle;
    }

    :global(.chat-markdown li) {
        margin: 0.15rem 0;
        display: list-item;
    }

    :global(.chat-markdown pre) {
        background: var(--muted, #f4f4f5);
        border-radius: 0.375rem;
        padding: 0.5rem;
        overflow-x: auto;
        margin: 0.35rem 0;
        font-size: 0.6875rem;
    }

    :global(.chat-markdown code) {
        font-family: ui-monospace, monospace;
        font-size: 0.6875rem;
    }

    :global(.chat-markdown :not(pre) > code) {
        background: var(--muted, #f4f4f5);
        padding: 0.1rem 0.3rem;
        border-radius: 0.25rem;
    }

    :global(.chat-markdown table) {
        width: 100%;
        border-collapse: collapse;
        margin: 0.35rem 0;
        font-size: 0.6875rem;
    }

    :global(.chat-markdown th),
    :global(.chat-markdown td) {
        border: 1px solid var(--border, #e4e4e7);
        padding: 0.25rem 0.4rem;
        text-align: left;
    }

    :global(.chat-markdown blockquote) {
        border-left: 2px solid var(--border, #e4e4e7);
        margin: 0.35rem 0;
        padding-left: 0.6rem;
        color: var(--muted-foreground, #71717a);
        font-size: 0.75rem;
    }

    :global(.chat-markdown strong) {
        font-weight: 600;
        font-size: inherit;
    }
</style>
