<script>
    import Alert from './Alert.svelte';
    import PhoneField from './PhoneField.svelte';
    import Steps from './Steps.svelte';
    import { createAlerts, normaliseErrors } from './alerts.svelte.js';

    /**
     * Seven-step job application. The vacancy description stays server-rendered
     * in Blade (it is the SEO-relevant content); this island only owns the
     * wizard and hides the description while the form is open.
     */
    let {
        t = {},
        csrf = '',
        validateUrl = '',
        submitUrl = '',
        jobPostingId = null,
        nationalities = [],
        descriptionSelector = '#job-description',
    } = $props();

    const MAX_CV_BYTES = 5 * 1024 * 1024;
    const CV_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    const thisYear = new Date().getFullYear();
    const years = Array.from({ length: thisYear - 1950 + 1 }, (_, i) => String(thisYear - i));

    const blankForm = () => ({
        job_posting_id: jobPostingId,
        personal: {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            nationality: '',
            date_of_birth: '',
            address: '',
        },
        education: [{ institution: '', degree: '', field_of_study: '', start_year: '', end_year: '', description: '' }],
        experience: [{ company_name: '', job_title: '', start_year: '', end_year: '', is_current: false, description: '' }],
        languages: [{ name: '', proficiency: '' }],
        skills: [],
        documents: { cv: null },
    });

    let open = $state(false);
    let currentStep = $state(1);
    let submitting = $state(false);
    let newSkill = $state('');
    let errors = $state({});
    let form = $state(blankForm());
    let cvInput = $state(null);

    const alerts = createAlerts();

    const steps = [
        t.step_personal,
        t.step_education,
        t.step_experience,
        t.step_languages,
        t.step_skills,
        t.step_documents,
        t.step_review,
    ];

    // The description panel and the wizard are mutually exclusive.
    $effect(() => {
        const description = document.querySelector(descriptionSelector);

        if (description) {
            description.hidden = open;
        }
    });

    // "Apply now" lives in the server-rendered panel, so listen for it here.
    $effect(() => {
        const onClick = (event) => {
            if (event.target.closest('[data-apply-now]')) {
                open = true;
            }
        };

        document.addEventListener('click', onClick);

        return () => document.removeEventListener('click', onClick);
    });

    /* ---------------------------------------------------------------- steps */

    function stepPayload() {
        switch (currentStep) {
            case 1:
                return { step: 1, personal: form.personal };
            case 2:
                return { step: 2, education: form.education };
            case 3:
                return { step: 3, experience: form.experience };
            case 4:
                return { step: 4, languages: form.languages };
            case 5:
                return { step: 5, skills: form.skills };
            default:
                return { step: currentStep };
        }
    }

    function documentsPayload() {
        const data = new FormData();

        data.append('step', String(currentStep));

        if (form.documents.cv) {
            data.append('cv', form.documents.cv);
        }

        return data;
    }

    async function validateStep() {
        if (currentStep === 7) {
            return true;
        }

        const isDocuments = currentStep === 6;

        try {
            const response = await fetch(validateUrl, {
                method: 'POST',
                headers: isDocuments
                    ? { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' }
                    : { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: isDocuments ? documentsPayload() : JSON.stringify(stepPayload()),
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                errors = {};
                return true;
            }

            errors = normaliseErrors(result.errors);
            alerts.error(result.message || (isDocuments ? t.fix_documents : t.fix_errors));

            return false;
        } catch {
            alerts.error(t.network_error);
            return false;
        }
    }

    async function nextStep() {
        if ((await validateStep()) && currentStep < 7) {
            currentStep += 1;
        }
    }

    /** Jump back to the earliest step that still holds an error. */
    function goToFirstErrorStep() {
        const sections = [
            ['personal', 1],
            ['education', 2],
            ['experience', 3],
            ['languages', 4],
            ['skills', 5],
            ['cv', 6],
        ];

        const steps = Object.keys(errors).flatMap((field) => {
            const match = sections.find(([name]) => field.includes(name));

            return match ? [match[1]] : [];
        });

        if (steps.length) {
            currentStep = Math.min(...steps);
        }
    }

    /* -------------------------------------------------------- repeatables */

    const addEducation = () =>
        form.education.push({ institution: '', degree: '', field_of_study: '', start_year: '', end_year: '', description: '' });
    const addExperience = () =>
        form.experience.push({ company_name: '', job_title: '', start_year: '', end_year: '', is_current: false, description: '' });
    const addLanguage = () => form.languages.push({ name: '', proficiency: '' });

    function removeAt(list, index) {
        if (list.length > 1) {
            list.splice(index, 1);
        }
    }

    function onCurrentJobChange(experience) {
        if (experience.is_current) {
            experience.end_year = '';
        }
    }

    /* -------------------------------------------------------------- skills */

    function addSkill() {
        const skill = newSkill.trim();

        if (skill && !form.skills.includes(skill)) {
            form.skills.push(skill);
        }

        newSkill = '';
    }

    /* ----------------------------------------------------------- documents */

    function onCvChange(event) {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        if (file.size > MAX_CV_BYTES) {
            alerts.error(t.cv_too_large);
            event.target.value = '';
            return;
        }

        if (!CV_TYPES.includes(file.type)) {
            alerts.error(t.cv_wrong_type);
            event.target.value = '';
            return;
        }

        delete errors.cv;
        form.documents.cv = file;
    }

    function removeCv() {
        form.documents.cv = null;

        if (cvInput) {
            cvInput.value = '';
        }
    }

    /* -------------------------------------------------------------- submit */

    function buildSubmission() {
        const data = new FormData();

        if (form.job_posting_id !== null) {
            data.append('job_posting_id', form.job_posting_id);
        }

        Object.entries(form.personal).forEach(([key, value]) => data.append(`personal[${key}]`, value || ''));

        form.education.forEach((education, index) =>
            Object.entries(education).forEach(([key, value]) => data.append(`education[${index}][${key}]`, value || ''))
        );

        form.experience.forEach((experience, index) =>
            Object.entries(experience).forEach(([key, value]) =>
                data.append(
                    `experience[${index}][${key}]`,
                    key === 'is_current' ? (value ? 'true' : 'false') : value || ''
                )
            )
        );

        form.languages.forEach((language, index) =>
            Object.entries(language).forEach(([key, value]) => data.append(`languages[${index}][${key}]`, value || ''))
        );

        form.skills.forEach((skill, index) => data.append(`skills[${index}]`, skill));

        if (form.documents.cv) {
            data.append('cv', form.documents.cv);
        }

        return data;
    }

    async function submitApplication() {
        submitting = true;
        errors = {};
        alerts.clear();

        try {
            const response = await fetch(submitUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: buildSubmission(),
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                alerts.success(t.submit_success);
                form = blankForm();
                currentStep = 1;
                return;
            }

            errors = normaliseErrors(result.errors);
            alerts.error(result.message || t.submit_error);
            goToFirstErrorStep();
        } catch {
            alerts.error(t.submit_error);
        } finally {
            submitting = false;
        }
    }
</script>

{#if open}
    <div class="card shadow-float">
        <div class="card-body">
            <Alert type={alerts.type} message={alerts.message} onclose={alerts.clear} />

            <div class="mb-8 flex items-center justify-between gap-4">
                <h2 class="mb-0 text-4xl font-black uppercase text-brand">{t.application_title}</h2>

                <button type="button" class="btn btn-outline btn-sm shrink-0" onclick={() => (open = false)}>
                    <i class="uil uil-times" aria-hidden="true"></i>
                    {t.close_form}
                </button>
            </div>

            <Steps {steps} current={currentStep} />

            {#if currentStep === 1}
                <h3 class="mb-4">{t.personal_info}</h3>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <input type="text" class="input" class:is-invalid={errors['personal.first_name']}
                            placeholder={t.first_name} aria-label={t.first_name}
                            bind:value={form.personal.first_name} />
                        {#if errors['personal.first_name']}<span class="field-error">{errors['personal.first_name']}</span>{/if}
                    </div>

                    <div>
                        <input type="text" class="input" class:is-invalid={errors['personal.last_name']}
                            placeholder={t.last_name} aria-label={t.last_name}
                            bind:value={form.personal.last_name} />
                        {#if errors['personal.last_name']}<span class="field-error">{errors['personal.last_name']}</span>{/if}
                    </div>

                    <div>
                        <input type="email" class="input" class:is-invalid={errors['personal.email']}
                            placeholder={t.email} aria-label={t.email}
                            bind:value={form.personal.email} />
                        {#if errors['personal.email']}<span class="field-error">{errors['personal.email']}</span>{/if}
                    </div>

                    <div>
                        <PhoneField bind:value={form.personal.phone} placeholder={t.phone}
                            invalid={!!errors['personal.phone']} />
                        {#if errors['personal.phone']}<span class="field-error">{errors['personal.phone']}</span>{/if}
                    </div>

                    <div class="md:col-span-2">
                        <select class="select" class:is-invalid={errors['personal.nationality']}
                            aria-label={t.nationality} bind:value={form.personal.nationality}>
                            <option value="">{t.nationality}</option>
                            {#each nationalities as nationality}
                                <option value={nationality.code}>{nationality.title}</option>
                            {/each}
                        </select>
                        {#if errors['personal.nationality']}<span class="field-error">{errors['personal.nationality']}</span>{/if}
                    </div>

                    <div class="md:col-span-2">
                        <textarea rows="3" class="input textarea" class:is-invalid={errors['personal.address']}
                            placeholder={t.address} aria-label={t.address}
                            bind:value={form.personal.address}></textarea>
                        {#if errors['personal.address']}<span class="field-error">{errors['personal.address']}</span>{/if}
                    </div>
                </div>
            {/if}

            {#if currentStep === 2}
                <h3 class="mb-4">{t.education}</h3>

                {#if errors.education}<div class="alert alert-danger mb-3">{errors.education}</div>{/if}

                {#each form.education as education, index}
                    <div class="repeatable">
                        {#if form.education.length > 1}
                            <button type="button" class="repeatable-remove" aria-label="Remove"
                                onclick={() => removeAt(form.education, index)}>
                                <i class="uil uil-times" aria-hidden="true"></i>
                            </button>
                        {/if}

                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="md:col-span-2">
                                <input type="text" class="input" class:is-invalid={errors[`education.${index}.institution`]}
                                    placeholder={t.institution} aria-label={t.institution}
                                    bind:value={education.institution} />
                                {#if errors[`education.${index}.institution`]}<span class="field-error">{errors[`education.${index}.institution`]}</span>{/if}
                            </div>

                            <div class="md:col-span-2">
                                <input type="text" class="input" class:is-invalid={errors[`education.${index}.degree`]}
                                    placeholder={t.degree} aria-label={t.degree} bind:value={education.degree} />
                                {#if errors[`education.${index}.degree`]}<span class="field-error">{errors[`education.${index}.degree`]}</span>{/if}
                            </div>

                            <div class="md:col-span-2">
                                <input type="text" class="input" class:is-invalid={errors[`education.${index}.field_of_study`]}
                                    placeholder={t.field_of_study} aria-label={t.field_of_study}
                                    bind:value={education.field_of_study} />
                                {#if errors[`education.${index}.field_of_study`]}<span class="field-error">{errors[`education.${index}.field_of_study`]}</span>{/if}
                            </div>

                            <div>
                                <select class="select" class:is-invalid={errors[`education.${index}.start_year`]}
                                    aria-label={t.start_year} bind:value={education.start_year}>
                                    <option value="">{t.start_year}</option>
                                    {#each years as year}<option value={year}>{year}</option>{/each}
                                </select>
                                {#if errors[`education.${index}.start_year`]}<span class="field-error">{errors[`education.${index}.start_year`]}</span>{/if}
                            </div>

                            <div>
                                <select class="select" class:is-invalid={errors[`education.${index}.end_year`]}
                                    aria-label={t.end_year} bind:value={education.end_year}>
                                    <option value="">{t.end_year}</option>
                                    {#each years as year}<option value={year}>{year}</option>{/each}
                                </select>
                                {#if errors[`education.${index}.end_year`]}<span class="field-error">{errors[`education.${index}.end_year`]}</span>{/if}
                            </div>

                            <div class="md:col-span-4">
                                <textarea rows="2" class="input textarea" class:is-invalid={errors[`education.${index}.description`]}
                                    placeholder={t.description} aria-label={t.description}
                                    bind:value={education.description}></textarea>
                                {#if errors[`education.${index}.description`]}<span class="field-error">{errors[`education.${index}.description`]}</span>{/if}
                            </div>
                        </div>
                    </div>
                {/each}

                <button type="button" class="repeatable-add" onclick={addEducation}>
                    <i class="uil uil-plus me-2" aria-hidden="true"></i>{t.add_education}
                </button>
            {/if}

            {#if currentStep === 3}
                <h3 class="mb-4">{t.work_experience}</h3>

                {#if errors.experience}<div class="alert alert-danger mb-3">{errors.experience}</div>{/if}

                {#each form.experience as experience, index}
                    <div class="repeatable">
                        {#if form.experience.length > 1}
                            <button type="button" class="repeatable-remove" aria-label="Remove"
                                onclick={() => removeAt(form.experience, index)}>
                                <i class="uil uil-times" aria-hidden="true"></i>
                            </button>
                        {/if}

                        <div class="grid gap-4 md:grid-cols-4">
                            <div class="md:col-span-2">
                                <input type="text" class="input" class:is-invalid={errors[`experience.${index}.company_name`]}
                                    placeholder={t.company_name} aria-label={t.company_name}
                                    bind:value={experience.company_name} />
                                {#if errors[`experience.${index}.company_name`]}<span class="field-error">{errors[`experience.${index}.company_name`]}</span>{/if}
                            </div>

                            <div class="md:col-span-2">
                                <input type="text" class="input" class:is-invalid={errors[`experience.${index}.job_title`]}
                                    placeholder={t.job_title} aria-label={t.job_title}
                                    bind:value={experience.job_title} />
                                {#if errors[`experience.${index}.job_title`]}<span class="field-error">{errors[`experience.${index}.job_title`]}</span>{/if}
                            </div>

                            <div>
                                <select class="select" class:is-invalid={errors[`experience.${index}.start_year`]}
                                    aria-label={t.start_year} bind:value={experience.start_year}>
                                    <option value="">{t.start_year}</option>
                                    {#each years as year}<option value={year}>{year}</option>{/each}
                                </select>
                                {#if errors[`experience.${index}.start_year`]}<span class="field-error">{errors[`experience.${index}.start_year`]}</span>{/if}
                            </div>

                            <div>
                                <select class="select" class:is-invalid={errors[`experience.${index}.end_year`]}
                                    disabled={experience.is_current} aria-label={t.end_year}
                                    bind:value={experience.end_year}>
                                    <option value="">{t.end_year}</option>
                                    {#each years as year}<option value={year}>{year}</option>{/each}
                                </select>
                                {#if errors[`experience.${index}.end_year`]}<span class="field-error">{errors[`experience.${index}.end_year`]}</span>{/if}
                            </div>

                            <label class="flex items-end gap-2 md:col-span-2">
                                <input type="checkbox" class="checkbox" bind:checked={experience.is_current}
                                    onchange={() => onCurrentJobChange(experience)} />
                                <span>{t.current_job}</span>
                            </label>

                            <div class="md:col-span-4">
                                <textarea rows="3" class="input textarea" class:is-invalid={errors[`experience.${index}.description`]}
                                    placeholder={t.job_description} aria-label={t.job_description}
                                    bind:value={experience.description}></textarea>
                                {#if errors[`experience.${index}.description`]}<span class="field-error">{errors[`experience.${index}.description`]}</span>{/if}
                            </div>
                        </div>
                    </div>
                {/each}

                <button type="button" class="repeatable-add" onclick={addExperience}>
                    <i class="uil uil-plus me-2" aria-hidden="true"></i>{t.add_experience}
                </button>
            {/if}

            {#if currentStep === 4}
                <h3 class="mb-4">{t.languages}</h3>

                {#if errors.languages}<div class="alert alert-danger mb-3">{errors.languages}</div>{/if}

                {#each form.languages as language, index}
                    <div class="repeatable">
                        {#if form.languages.length > 1}
                            <button type="button" class="repeatable-remove" aria-label="Remove"
                                onclick={() => removeAt(form.languages, index)}>
                                <i class="uil uil-times" aria-hidden="true"></i>
                            </button>
                        {/if}

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <input type="text" class="input" class:is-invalid={errors[`languages.${index}.name`]}
                                    placeholder={t.language_name} aria-label={t.language_name}
                                    bind:value={language.name} />
                                {#if errors[`languages.${index}.name`]}<span class="field-error">{errors[`languages.${index}.name`]}</span>{/if}
                            </div>

                            <div>
                                <select class="select" class:is-invalid={errors[`languages.${index}.proficiency`]}
                                    aria-label={t.select_proficiency} bind:value={language.proficiency}>
                                    <option value="">{t.select_proficiency}</option>
                                    <option value="basic">{t.basic}</option>
                                    <option value="intermediate">{t.intermediate}</option>
                                    <option value="advanced">{t.advanced}</option>
                                    <option value="native">{t.native}</option>
                                </select>
                                {#if errors[`languages.${index}.proficiency`]}<span class="field-error">{errors[`languages.${index}.proficiency`]}</span>{/if}
                            </div>
                        </div>
                    </div>
                {/each}

                <button type="button" class="repeatable-add" onclick={addLanguage}>
                    <i class="uil uil-plus me-2" aria-hidden="true"></i>{t.add_language}
                </button>
            {/if}

            {#if currentStep === 5}
                <h3 class="mb-4">{t.skills}</h3>

                {#if errors.skills}<div class="alert alert-danger mb-3">{errors.skills}</div>{/if}

                <div class="mb-3 grid gap-2 md:grid-cols-3">
                    <input type="text" class="input md:col-span-2" placeholder={t.add_skills} aria-label={t.add_skills}
                        bind:value={newSkill}
                        onkeyup={(event) => event.key === 'Enter' && addSkill()} />

                    <button type="button" class="btn btn-outline w-full text-brand" onclick={addSkill}>
                        <i class="uil uil-plus" aria-hidden="true"></i>{t.add_skills}
                    </button>
                </div>

                <div class="min-h-[100px] rounded-sm border border-line-strong p-3">
                    {#each form.skills as skill, index}
                        <span class="chip">
                            {skill}
                            <button type="button" class="chip-remove" aria-label="Remove"
                                onclick={() => form.skills.splice(index, 1)}>&times;</button>
                        </span>
                    {/each}

                    {#if form.skills.length === 0}
                        <div class="text-muted">{t.no_skills}</div>
                    {/if}
                </div>
            {/if}

            {#if currentStep === 6}
                <h3 class="mb-4">{t.documents}</h3>

                <div class="mb-4">
                    <label for="cv-input" class="field-label">
                        {t.cv_required} <span class="text-danger">*</span>
                    </label>

                    <input id="cv-input" type="file" accept=".pdf,.doc,.docx" class="input"
                        class:is-invalid={errors.cv} bind:this={cvInput} onchange={onCvChange} />

                    <span class="field-hint">{t.cv_formats}</span>
                    {#if errors.cv}<span class="field-error">{errors.cv}</span>{/if}

                    {#if form.documents.cv}
                        <div class="alert alert-success mt-2 flex items-center justify-between gap-2">
                            <span>
                                <i class="uil uil-file-alt me-2" aria-hidden="true"></i>
                                {form.documents.cv.name}
                            </span>
                            <button type="button" class="btn btn-outline btn-sm text-danger" onclick={removeCv}>
                                <i class="uil uil-times" aria-hidden="true"></i>
                            </button>
                        </div>
                    {/if}
                </div>
            {/if}

            {#if currentStep === 7}
                <h3 class="mb-4">{t.review_submit}</h3>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <h5>{t.personal_info}</h5>
                        <p><strong>{t.name}:</strong> {form.personal.first_name} {form.personal.last_name}</p>
                        <p><strong>{t.email}:</strong> {form.personal.email}</p>
                        <p><strong>{t.phone}:</strong> {form.personal.phone}</p>

                        <h5 class="mt-4">{t.education}</h5>
                        {#each form.education as education}
                            <div class="mb-2">
                                <strong>{education.degree}</strong> - {education.institution}
                                <br /><small>{education.start_year} - {education.end_year || t.present}</small>
                            </div>
                        {/each}

                        <h5 class="mt-4">{t.work_experience}</h5>
                        {#each form.experience as experience}
                            <div class="mb-2">
                                <strong>{experience.job_title}</strong> - {experience.company_name}
                                <br /><small>{experience.start_year} - {experience.end_year || t.present}</small>
                            </div>
                        {/each}
                    </div>

                    <div>
                        <h5>{t.languages}</h5>
                        {#each form.languages as language}
                            <div class="mb-1"><strong>{language.name}</strong> - {language.proficiency}</div>
                        {/each}

                        <h5 class="mt-4">{t.skills}</h5>
                        <div class="flex flex-wrap gap-1">
                            {#each form.skills as skill}<span class="badge">{skill}</span>{/each}
                        </div>

                        <h5 class="mt-4">{t.documents}</h5>
                        {#if form.documents.cv}
                            <p><i class="uil uil-file-alt me-1" aria-hidden="true"></i>CV: {form.documents.cv.name}</p>
                        {/if}
                    </div>
                </div>
            {/if}

            <div class="mt-4 flex items-center justify-between gap-4">
                {#if currentStep > 1}
                    <button type="button" class="btn btn-outline text-brand" onclick={() => (currentStep -= 1)}>
                        <i class="uil uil-arrow-left rtl:rotate-180" aria-hidden="true"></i>{t.previous}
                    </button>
                {:else}
                    <span></span>
                {/if}

                {#if currentStep < 7}
                    <button type="button" class="btn" onclick={nextStep}>
                        {t.next}<i class="uil uil-arrow-right rtl:rotate-180" aria-hidden="true"></i>
                    </button>
                {:else}
                    <button type="button" class="btn" disabled={submitting} onclick={submitApplication}>
                        {#if submitting}
                            <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-e-transparent"></span>
                        {/if}
                        {t.submit_application}
                    </button>
                {/if}
            </div>
        </div>
    </div>
{/if}
