<script>
    import { onMount } from 'svelte';

    // Props for the layout
    export let pageTitle = 'Auth Page';

    function prepareFormData(form, update = false) {
        let formData = new FormData();

        if (update) {
            formData.append('_method', 'PUT');
        }

        Object.keys(form).forEach(key => {
            const value = form[key];
            
            if (value === true || value === false) {
                formData.append(key, value ? '1' : '0');
            } else if (Array.isArray(value) && value.length > 0) {
                formData.append(key, JSON.stringify(value));
            } else if (value !== null && value !== undefined && value !== '') {
                formData.append(key, value);
            }
        });

        return formData;
    }

    window.prepareFormData = prepareFormData;
</script>

<svelte:head>    
    <style>
        .branded-bg {
            background-image: url('/assets/media/images/auth-page-cover.jpg');
        }
        
        .dark .branded-bg {
            background-image: url('/assets/media/images/auth-page-cover.jpg');
        }
    </style>
</svelte:head>

<div class="grid lg:grid-cols-2 grow bg-background" style="min-height: 100vh;">
    <slot />
</div> 