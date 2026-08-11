<script>
    import Alert from './Alert.svelte';
    import PhoneField from './PhoneField.svelte';
    import Steps from './Steps.svelte';
    import { createAlerts, normaliseErrors } from './alerts.svelte.js';

    /**
     * Three-step visit booking: pick a service and party size, pick a slot on
     * the calendar, then confirm. Copy comes in from Blade so the DB
     * translations stay the single source of truth.
     */
    let { services = [], t = {}, locale = 'en', csrf = '', bookingUrl = '' } = $props();

    let currentStep = $state(1);
    let visitorCounts = $state(Object.fromEntries(services.map((service) => [service.id, 1])));
    let selectedService = $state(null);
    let selectedTimeSlot = $state(null);
    let bookingInProgress = $state(false);
    let errors = $state({});

    let form = $state({ guardian_name: '', email: '', phone: '', students: [] });

    const alerts = createAlerts();

    let calendarEl = $state(null);
    let calendar = null;

    let dialogs = {};

    const clamp = (n) => Math.min(5, Math.max(1, n));

    function setVisitors(serviceId, next) {
        visitorCounts[serviceId] = clamp(next);

        if (selectedService?.id === serviceId) {
            selectedService.visitorCount = visitorCounts[serviceId];
        }
    }

    function selectService(service) {
        selectedService = {
            id: service.id,
            name: service.title,
            visitorCount: visitorCounts[service.id] || 1,
            capacity: service.capacity,
        };

        currentStep = 2;
    }

    function slotEvents(service) {
        return (service.timeSlots || []).map((slot) => {
            const isFull = Number(slot.remaining_capacity) <= 0;

            return {
                id: String(slot.id),
                start: slot.starts_at,
                end: slot.ends_at,
                title: isFull
                    ? `${t.fully_booked} (${slot.total_capacity}/${slot.total_capacity})`
                    : `${t.available_slots} (${slot.remaining_capacity}/${slot.total_capacity})`,
                classNames: [isFull ? 'is-full' : 'is-available'],
                extendedProps: {
                    remaining_capacity: slot.remaining_capacity,
                    total_capacity: slot.total_capacity,
                    fully_booked: isFull,
                },
            };
        });
    }

    // Build the calendar whenever step 2 is showing, tear it down when it is not.
    $effect(() => {
        if (currentStep !== 2 || !calendarEl || !selectedService) {
            return;
        }

        const service = services.find((item) => item.id === selectedService.id);
        let active = true;

        import('../calendar').then(({ createCalendar }) => {
            if (!active) {
                return;
            }

            calendar = createCalendar(calendarEl, {
                locale,
                initialView: 'timeGridWeek',
                headerToolbar: { left: 'prev,next', right: 'dayGridMonth,timeGridWeek' },
                slotMinTime: '08:00:00',
                slotMaxTime: '18:00:00',
                allDaySlot: false,
                events: slotEvents(service),
                eventClick: (info) => {
                    if (info.event.extendedProps.fully_booked) {
                        alerts.error(t.slot_fully_booked);
                        return;
                    }

                    selectTimeSlot(info.event);
                },
            });
        });

        return () => {
            active = false;
            calendar?.destroy();
            calendar = null;
        };
    });

    function selectTimeSlot(event) {
        selectedTimeSlot = {
            id: event.id,
            start: event.start,
            end: event.end,
            remaining_capacity: event.extendedProps.remaining_capacity,
            total_capacity: event.extendedProps.total_capacity,
        };

        form.students = Array.from({ length: selectedService.visitorCount }, () => ({
            name: '',
            grade: '',
            school: '',
        }));

        currentStep = 3;
    }

    function goBackToServices() {
        currentStep = 1;
        selectedService = null;
        selectedTimeSlot = null;
    }

    function goBackToSlots() {
        currentStep = 2;
        selectedTimeSlot = null;
        errors = {};
        alerts.clear();
    }

    function reset() {
        currentStep = 1;
        selectedService = null;
        selectedTimeSlot = null;
        form = { guardian_name: '', email: '', phone: '', students: [] };
        errors = {};
    }

    async function confirmBooking() {
        bookingInProgress = true;
        errors = {};
        alerts.clear();

        try {
            const response = await fetch(bookingUrl.replace('__ID__', selectedService.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    guardian_name: form.guardian_name.trim() || null,
                    email: form.email.trim() || null,
                    phone: form.phone.trim() || null,
                    students: form.students,
                    visitors_count: selectedService.visitorCount,
                    visit_time_slot_id: selectedTimeSlot.id,
                }),
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                alerts.success(t.booking_success);
                reset();
                return;
            }

            if (result.errors) {
                errors = normaliseErrors(result.errors);

                if (result.errors.visit_time_slot_id) {
                    alerts.error(result.message || t.insufficient_capacity);
                }
            } else {
                alerts.error(result.message || t.booking_error);
            }
        } catch {
            alerts.error(t.booking_error);
        } finally {
            bookingInProgress = false;
        }
    }

    const formatDateTime = (value) => new Date(value).toLocaleString(locale);
</script>

<Steps steps={[t.step_1, t.step_2, t.step_3]} current={currentStep} />

{#if currentStep === 1}
    <Alert type={alerts.type} message={alerts.message} onclose={alerts.clear} />

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
        {#each services as service (service.id)}
            <div class="card">
                <figure class="overlay h-[350px]">
                    <img class="h-full w-full object-cover" src={service.thumbnail} alt={service.title} />
                </figure>

                <div class="card-body p-4">
                    <h2 class="mb-2 text-3xl font-black uppercase leading-[35px] text-brand">{service.title}</h2>

                    <p class="line-clamp-3">{service.description}</p>

                    <button
                        type="button"
                        class="mt-2 self-start text-brand underline"
                        onclick={() => dialogs[service.id]?.showModal()}
                    >
                        {t.read_more}
                    </button>

                    <ul class="post-meta mt-2">
                        <li>
                            <i class="uil uil-clock" aria-hidden="true"></i>
                            <span>{service.duration}</span>
                        </li>
                    </ul>

                    <div class="counter my-2 w-full">
                        <span class="flex h-full items-center px-3">
                            <i class="uil uil-user" aria-hidden="true"></i>
                        </span>

                        <div class="flex flex-1">
                            <input
                                type="number"
                                min="1"
                                max="5"
                                readonly
                                class="counter-input"
                                value={visitorCounts[service.id]}
                                aria-label={t.visitors_count}
                            />

                            <div class="flex flex-col border-s border-[#dee2e6]">
                                <button
                                    type="button"
                                    class="counter-btn"
                                    onclick={() => setVisitors(service.id, visitorCounts[service.id] + 1)}
                                    aria-label="+"
                                >&#9650;</button>
                                <button
                                    type="button"
                                    class="counter-btn"
                                    disabled={visitorCounts[service.id] <= 1}
                                    onclick={() => setVisitors(service.id, visitorCounts[service.id] - 1)}
                                    aria-label="-"
                                >&#9660;</button>
                            </div>

                            <button
                                type="button"
                                class="btn flex-1 rounded-s-none px-3 text-sm"
                                onclick={() => selectService(service)}
                            >
                                {t.select}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <dialog bind:this={dialogs[service.id]} class="dialog">
                <button
                    type="button"
                    class="btn-close absolute end-4 top-4 z-10"
                    onclick={() => dialogs[service.id]?.close()}
                    aria-label="Close"
                ></button>

                <figure>
                    <img class="w-full object-cover" src={service.thumbnail} alt={service.title} />
                </figure>

                <div class="px-6 py-4">
                    <h3>{service.title}</h3>
                    <p class="text-xl font-medium text-ink">{service.description}</p>
                    <hr class="mb-4 mt-2 border-line" />
                    <div class="prose mb-6">{@html service.content}</div>
                </div>
            </dialog>
        {/each}
    </div>
{/if}

{#if currentStep === 2}
    <div class="card">
        <div class="card-header p-2 sm:p-8">
            <button type="button" class="btn btn-sm mb-2" onclick={goBackToServices} aria-label={t.step_1}>
                <i class="uil uil-arrow-left rtl:rotate-180" aria-hidden="true"></i>
            </button>

            <h2 class="mb-0 text-3xl font-black uppercase text-brand">{selectedService?.name}</h2>
            <small class="text-muted">{t.visitors_count}: {selectedService?.visitorCount}</small>
        </div>

        <div class="card-body p-2 sm:p-8">
            <div class="mb-5 flex flex-wrap justify-center gap-5">
                <div class="flex items-center gap-2">
                    <span class="h-5 w-5 rounded-xs bg-slot-open"></span>
                    <span>{t.available_slots}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-5 w-5 rounded-xs bg-slot-full"></span>
                    <span>{t.fully_booked}</span>
                </div>
            </div>

            <div bind:this={calendarEl}></div>
        </div>
    </div>
{/if}

{#if currentStep === 3}
    <Alert type={alerts.type} message={alerts.message} onclose={alerts.clear} />

    <div class="card">
        <div class="card-header p-2 sm:p-8">
            <button type="button" class="btn btn-sm mb-2" onclick={goBackToSlots} aria-label={t.step_2}>
                <i class="uil uil-arrow-left rtl:rotate-180" aria-hidden="true"></i>
            </button>

            <h2 class="mb-0 text-3xl font-black uppercase text-brand">{selectedService?.name}</h2>
            <small class="text-muted">{t.visitors_count}: {selectedService?.visitorCount}</small>
        </div>

        <div class="card-body">
            <h2 class="text-3xl font-black uppercase text-brand">{t.confirmation_title}</h2>

            {#if selectedTimeSlot}
                <form class="mt-6 grid gap-6 md:grid-cols-2" onsubmit={(e) => { e.preventDefault(); confirmBooking(); }}>
                    <div class="flex flex-col gap-4">
                        <div>
                            <div class="input-group">
                                <span class="input-addon"><i class="uil uil-user" aria-hidden="true"></i></span>
                                <input
                                    type="text"
                                    class="input"
                                    class:is-invalid={errors.guardian_name}
                                    placeholder={t.name_input}
                                    aria-label={t.name_input}
                                    bind:value={form.guardian_name}
                                />
                            </div>
                            {#if errors.guardian_name}<span class="field-error">{errors.guardian_name}</span>{/if}
                        </div>

                        <div>
                            <div class="input-group">
                                <span class="input-addon"><i class="uil uil-envelope" aria-hidden="true"></i></span>
                                <input
                                    type="email"
                                    class="input"
                                    class:is-invalid={errors.email}
                                    placeholder={t.email_input}
                                    aria-label={t.email_input}
                                    bind:value={form.email}
                                />
                            </div>
                            {#if errors.email}<span class="field-error">{errors.email}</span>{/if}
                        </div>

                        <div>
                            <PhoneField bind:value={form.phone} placeholder={t.phone_input} invalid={!!errors.phone} />
                            {#if errors.phone}<span class="field-error">{errors.phone}</span>{/if}
                        </div>

                        {#each form.students as student, index}
                            <div class="flex flex-col gap-4">
                                <h6 class="text-brand">{t.student} {index + 1}</h6>

                                <div>
                                    <div class="input-group">
                                        <span class="input-addon"><i class="uil uil-book-reader" aria-hidden="true"></i></span>
                                        <input
                                            type="text"
                                            class="input"
                                            class:is-invalid={errors[`students.${index}.name`]}
                                            placeholder={t.student_name_input}
                                            aria-label={t.student_name_input}
                                            bind:value={student.name}
                                        />
                                    </div>
                                    {#if errors[`students.${index}.name`]}
                                        <span class="field-error">{errors[`students.${index}.name`]}</span>
                                    {/if}
                                </div>

                                <div>
                                    <div class="input-group">
                                        <span class="input-addon"><i class="uil uil-code-branch" aria-hidden="true"></i></span>
                                        <input
                                            type="text"
                                            class="input"
                                            class:is-invalid={errors[`students.${index}.grade`]}
                                            placeholder={t.student_grade_input}
                                            aria-label={t.student_grade_input}
                                            bind:value={student.grade}
                                        />
                                    </div>
                                    {#if errors[`students.${index}.grade`]}
                                        <span class="field-error">{errors[`students.${index}.grade`]}</span>
                                    {/if}
                                </div>

                                <div>
                                    <div class="input-group">
                                        <span class="input-addon"><i class="uil uil-university" aria-hidden="true"></i></span>
                                        <input
                                            type="text"
                                            class="input"
                                            class:is-invalid={errors[`students.${index}.school`]}
                                            placeholder={t.student_school_input}
                                            aria-label={t.student_school_input}
                                            bind:value={student.school}
                                        />
                                    </div>
                                    {#if errors[`students.${index}.school`]}
                                        <span class="field-error">{errors[`students.${index}.school`]}</span>
                                    {/if}
                                </div>
                            </div>
                        {/each}
                    </div>

                    <div class="text-center md:text-start">
                        <p><strong>{t.selected_service}:</strong> {selectedService?.name}</p>
                        <p><strong>{t.date_time}:</strong> {formatDateTime(selectedTimeSlot.start)}</p>
                        <p><strong>{t.visitors_count}:</strong> {selectedService?.visitorCount}</p>

                        <div class="flex justify-center md:justify-end">
                            <button type="submit" class="btn" disabled={bookingInProgress}>
                                {#if bookingInProgress}
                                    <span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-e-transparent"></span>
                                {/if}
                                {t.confirm}
                            </button>
                        </div>
                    </div>
                </form>
            {/if}
        </div>
    </div>
{/if}
