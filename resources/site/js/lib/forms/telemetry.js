/**
 * What the public form measures, and how it gets home.
 *
 * COUNTS AND TIMINGS ONLY. Keystroke content and pasted text are never
 * transmitted and never stored — this file sees values (it has to, to measure a
 * length) and deliberately keeps nothing but `.length`. That is a privacy line,
 * not an optimisation, and it is the first thing to check when adding a metric.
 *
 * THE PAYLOAD IS A FULL SNAPSHOT, NEVER A DELTA. Both `pagehide` and
 * `visibilitychange` fire on a real close, beacons arrive out of order and the
 * browser drops them under memory pressure — so every send has to be complete
 * and idempotent on its own. A delta protocol would need sequencing, acks and a
 * server-side merge to buy nothing. Snapshots are why the field records below
 * use one-letter wire keys: the whole thing has to stay small enough (~3KB) that
 * repeating it every few seconds is free.
 *
 * KEYSTROKES COME FROM `beforeinput`, NOT `keydown`. keydown reports keyCode 229
 * for every IME composition, so Arabic and Hindi — both shipped locales — and
 * every mobile virtual keyboard would produce garbage. `beforeinput` fires once
 * per real value mutation and its `inputType` says whether it inserted or
 * deleted, which is the whole distinction we want.
 *
 * THE FIRST BEACON CREATES THE SERVER-SIDE DRAFT, so nothing is sent until a
 * human has actually done something. A crawler that loads the page, or a visitor
 * who bounces, must not write a row.
 *
 * Wire format for a field record (short keys, because these repeat per field):
 *   k   key            o    focus order (1-based)
 *   f   focus_ms       r    revisits
 *   ks  keystrokes     del  deletions
 *   len final_length   p    paste_count
 *
 * Imports nothing. It runs inside the standalone public island, which has no
 * Inertia, no API client and no Ziggy.
 */

/** Hard caps. A form larger than this still works; the tail is simply not sent. */
const MAX_FIELDS = 60;
const MAX_STEPS = 20;

/** How often the heartbeat considers sending, and the floor between sends. */
const HEARTBEAT_MS = 5000;
const MIN_INTERVAL_MS = 10000;

/** Pointer sampling ceiling — 20Hz. Off by default (capture.mouse). */
const POINTER_SAMPLE_MS = 50;

/**
 * How long a requested animation frame may stay unrun before handleScroll gives
 * up on it. A frame that has not fired in a second never will — the tab was
 * hidden or frozen while it was queued — and without this the guard it set would
 * stay latched for the rest of the visit.
 */
const SCROLL_FRAME_TIMEOUT_MS = 1000;

/** Column widths, mirrored from the migration so we never post a truncating value. */
const MAX_URL = 2000;
const MAX_SMALLINT = 65535;

/** A monotonic clock: wall time can jump backwards mid-visit and negative durations are unsigned columns. */
const clock = () =>
    typeof performance !== 'undefined' && typeof performance.now === 'function' ? performance.now() : Date.now();

function text(value, max) {
    return typeof value === 'string' && value !== '' ? value.slice(0, max) : null;
}

function positive(value, max = MAX_SMALLINT) {
    const number = Math.round(Number(value));

    return Number.isFinite(number) && number > 0 ? Math.min(number, max) : null;
}

/**
 * The passive environment: headers the browser sends anyway, plus what the page
 * can read about its own window. No canvas, WebGL, audio or font probing.
 *
 * `timezone`, `screen` and `platform` are named exactly as
 * SubmissionGuard::fingerprint() reads them — they are hashed into the device
 * signature, so renaming one here silently changes every fingerprint.
 *
 * `timezone_offset_minutes` is minutes AHEAD of UTC (the sign everyone expects),
 * which is the negation of what getTimezoneOffset() returns.
 */
export function collectEnvironment() {
    const nav = typeof navigator === 'undefined' ? {} : navigator;
    const view = typeof window === 'undefined' ? {} : window;
    const screen = view.screen ?? {};

    let timezone = null;

    try {
        timezone = text(Intl.DateTimeFormat().resolvedOptions().timeZone, 64);
    } catch {
        timezone = null;
    }

    const width = positive(screen.width);
    const height = positive(screen.height);

    return {
        timezone,
        timezone_offset_minutes: -new Date().getTimezoneOffset(),
        screen_w: width,
        screen_h: height,
        viewport_w: positive(view.innerWidth),
        viewport_h: positive(view.innerHeight),
        browser_language: text(nav.language, 32),
        // Absent on Safari and Firefox; null is a legitimate answer, not a failure.
        connection: text(nav.connection?.effectiveType, 32),
        landing_url: text(view.location?.href, MAX_URL),
        // The page the visitor is actually on. The server cannot derive it from
        // a beacon: that request's URL is the telemetry endpoint, not the form.
        page_url: text(view.location?.href, MAX_URL),
        referrer: text(typeof document === 'undefined' ? '' : document.referrer, MAX_URL),

        /* Fingerprint inputs — see the note above before touching these names. */
        screen: width && height ? `${width}x${height}` : null,
        platform: text(nav.userAgentData?.platform ?? nav.platform, 64),
    };
}

/**
 * Build a collector for one rendered form.
 *
 * @param {object}  options
 * @param {string}  options.endpoint  where beacons go
 * @param {string}  options.token     the render's submission token — the ONLY thing
 *                                    authenticating a CSRF-excepted endpoint
 * @param {object}  options.capture   config('forms.capture'), as sent by the page
 */
export function createTelemetry({ endpoint = null, token = null, capture = {} } = {}) {
    const on = {
        timing: capture.timing !== false,
        behaviour: capture.behaviour !== false,
        scroll: capture.scroll !== false,
        keystrokes: capture.keystrokes !== false,
        mouse: capture.mouse === true,
    };

    const environment = collectEnvironment();
    const startedAt = clock();

    /** Nothing is sent — no draft row is created — until this is true. */
    let interacted = false;
    let firstInteractionAt = null;
    let lastInteractionAt = null;

    /** Set by anything that changes the snapshot; cleared by a successful send. */
    let dirty = false;
    let lastSentAt = 0;

    let visibleSince = typeof document !== 'undefined' && document.visibilityState === 'visible' ? clock() : null;
    let visibleMs = 0;

    /** key => record. Insertion order is the focus order. */
    const fields = new Map();
    let openKey = null;
    /** The furthest field reached: what the visitor was on when they gave up. */
    let reachedKey = null;

    const visited = new Set();
    let steps = [];
    let lastPageId = null;
    let backNavigations = 0;
    let submitAttempts = 0;
    let clickCount = 0;
    let pasteCount = 0;
    let scrollDepth = null;
    let pointerMoves = 0;
    let lastPointerAt = 0;

    let root = null;
    let heartbeat = null;
    let rafPending = false;
    let rafRequestedAt = 0;
    let destroyed = false;
    const bound = [];

    /* ------------------------------------------------------------------ */

    function keyOf(node) {
        if (!node || typeof node.closest !== 'function') {
            return null;
        }

        const el = node.closest('[data-sisf-key]');

        return el ? el.getAttribute('data-sisf-key') : null;
    }

    function fieldRecord(key) {
        if (typeof key !== 'string' || key === '') {
            return null;
        }

        let record = fields.get(key);

        if (!record) {
            // Past the cap the tail is simply not measured, rather than the
            // payload growing without bound.
            if (fields.size >= MAX_FIELDS) {
                return null;
            }

            record = {
                key,
                order: fields.size + 1,
                focusMs: 0,
                openedAt: null,
                seen: false,
                revisits: 0,
                keystrokes: 0,
                deletions: 0,
                length: 0,
                pastes: 0,
            };

            fields.set(key, record);
        }

        return record;
    }

    /*
     * Collapse forced beacons that land in the same tick.
     *
     * step() and interact() both call touched() and then flush immediately, and
     * on the very first interaction touched() flushes too — so two POSTs left
     * microseconds apart, were handled in parallel, and each created its own
     * draft row for one visitor. Routing every forced flush through here means
     * the second is dropped rather than racing the first.
     *
     * This is the cheap half of the fix. The durable half is server-side: the
     * recorder claims the session's row under a lock and the table has a unique
     * index on (form_id, session_id), so a race cannot double-insert even if a
     * future caller forgets this.
     */
    let forcedPending = false;

    function forceFlush(reason) {
        if (forcedPending) {
            return;
        }

        forcedPending = true;
        queueMicrotask(() => {
            forcedPending = false;
        });

        flush(reason, true);
    }

    function touched() {
        const first = !interacted;

        if (first) {
            interacted = true;
            firstInteractionAt = clock();
        }

        lastInteractionAt = clock();
        dirty = true;

        /*
         * The first beacon goes out the moment somebody engages, rather than
         * waiting on the heartbeat's interval floor. It is the send that CREATES
         * the draft row, and a visitor who focuses one field and closes the tab
         * inside ten seconds is exactly the visit the funnel most wants to know
         * about — waiting would lose it.
         */
        if (first) {
            forceFlush('start');
        }
    }

    function openField(key) {
        if (openKey === key) {
            return;
        }

        closeField();

        const record = fieldRecord(key);

        if (!record) {
            return;
        }

        if (record.seen) {
            record.revisits += 1;
        }

        record.seen = true;
        record.openedAt = clock();
        openKey = key;
        reachedKey = key;
        touched();
    }

    /**
     * Bank the open field's elapsed time.
     *
     * MUST be called explicitly by the navigation handler: unmounting a page
     * destroys its inputs without firing focusout, so the timer would otherwise
     * be dropped and that field's focus_ms would silently read zero.
     */
    function closeField() {
        if (openKey === null) {
            return;
        }

        const record = fields.get(openKey);

        if (record && record.openedAt !== null) {
            record.focusMs += Math.round(clock() - record.openedAt);
            record.openedAt = null;
        }

        openKey = null;
        dirty = true;
    }

    /* ------------------------------------------------------------------ */

    function handleFocusIn(event) {
        const key = keyOf(event.target);

        if (key) {
            openField(key);
        }
    }

    /**
     * Closing on relatedTarget rather than on the event alone is what keeps a
     * radio group honest: arrowing between its inputs fires focusout/focusin on
     * siblings inside ONE element, and closing there would count a revisit for
     * every arrow key.
     */
    function handleFocusOut(event) {
        const key = keyOf(event.target);
        const next = keyOf(event.relatedTarget);

        if (key && next === key) {
            return;
        }

        closeField();
    }

    function handleBeforeInput(event) {
        const record = fieldRecord(keyOf(event.target));

        if (!record) {
            return;
        }

        const type = String(event.inputType ?? '');

        // Undo/redo mutate the value without being typing; counting them would
        // make a corrected field look busier than a retyped one.
        if (type.startsWith('history')) {
            return;
        }

        if (type.startsWith('delete')) {
            record.deletions += 1;
        } else {
            record.keystrokes += 1;
        }

        touched();
    }

    function handlePaste(event) {
        // The event carries the clipboard. We read nothing off it — only that it
        // happened. Never log, never send, never store the content.
        const record = fieldRecord(keyOf(event.target));

        if (record) {
            record.pastes += 1;
        }

        pasteCount += 1;
        touched();
    }

    /**
     * Clicks are counted but do NOT arm the beacon: a bounce clicks, and a click
     * alone must not write a draft row. Focusing a field or typing does.
     */
    function handlePointerDown() {
        clickCount += 1;
        dirty = true;
    }

    function handlePointerMove() {
        const now = clock();

        if (now - lastPointerAt < POINTER_SAMPLE_MS) {
            return;
        }

        lastPointerAt = now;
        pointerMoves += 1;
    }

    function handleScroll() {
        const requestedAt = clock();

        /*
         * The flag coalesces a burst of scroll events into one measurement per
         * frame, and the frame itself clears it — EXCEPT when the frame never
         * runs. Hide the tab (or let the browser freeze it) with a frame
         * queued and the callback is simply dropped: the flag stayed true
         * forever and scroll depth froze at whatever it was, for the rest of
         * the visit, on the visitor's very next scroll. So the guard also
         * expires: a frame that has not run in SCROLL_FRAME_TIMEOUT_MS is gone
         * and this asks for another.
         */
        if (rafPending && requestedAt - rafRequestedAt < SCROLL_FRAME_TIMEOUT_MS) {
            return;
        }

        rafPending = true;
        rafRequestedAt = requestedAt;

        requestAnimationFrame(() => {
            rafPending = false;

            const doc = document.documentElement;
            const height = doc?.scrollHeight ?? 0;

            if (height <= 0) {
                return;
            }

            const seen = Math.min(100, Math.round(((window.scrollY + window.innerHeight) / height) * 100));

            if (scrollDepth === null || seen > scrollDepth) {
                scrollDepth = Math.max(0, seen);
                dirty = true;
            }
        });
    }

    function handleVisibility() {
        if (document.visibilityState === 'hidden') {
            if (visibleSince !== null) {
                visibleMs += clock() - visibleSince;
                visibleSince = null;
            }

            // Both this and pagehide fire on a real close, in an order nobody
            // agrees on. Snapshots make the duplicate harmless.
            flush('hidden', true);

            return;
        }

        if (visibleSince === null) {
            visibleSince = clock();
        }
    }

    function handlePageHide() {
        closeField();
        flush('pagehide', true);
    }

    /* ------------------------------------------------------------------ */

    /** Visible milliseconds, including the stretch currently open. */
    function timeOnPage() {
        return Math.round(visibleMs + (visibleSince === null ? 0 : clock() - visibleSince));
    }

    function snapshot(reason) {
        const now = clock();

        const records = [...fields.values()]
            .sort((a, b) => a.order - b.order)
            .map((record) => ({
                k: record.key,
                o: record.order,
                // The open field's running time is added WITHOUT closing it, so a
                // snapshot never mutates what it is describing.
                f: Math.round(record.focusMs + (record.openedAt === null ? 0 : now - record.openedAt)),
                r: record.revisits,
                ks: on.keystrokes ? record.keystrokes : 0,
                del: on.keystrokes ? record.deletions : 0,
                len: record.length,
                p: record.pastes,
            }));

        const payload = {
            submission_token: token,
            reason,
            // Posted on submit as hidden client[] inputs too, so the fingerprint
            // and the technical columns agree across both paths.
            client: {
                timezone: environment.timezone,
                screen: environment.screen,
                platform: environment.platform,
            },
            timezone: environment.timezone,
            timezone_offset_minutes: environment.timezone_offset_minutes,
            screen_w: environment.screen_w,
            screen_h: environment.screen_h,
            viewport_w: positive(window.innerWidth),
            viewport_h: positive(window.innerHeight),
            browser_language: environment.browser_language,
            connection: environment.connection,
            landing_url: environment.landing_url,
            page_url: environment.page_url,
            referrer: environment.referrer,
            last_page_id: lastPageId ?? currentPageId(),
            pages_completed: visited.size,
            open_field: reachedKey,
            fields: records,
        };

        if (on.timing) {
            payload.ttfi_ms = firstInteractionAt === null ? null : Math.round(firstInteractionAt - startedAt);
            payload.fill_ms =
                firstInteractionAt === null ? null : Math.round((lastInteractionAt ?? firstInteractionAt) - firstInteractionAt);
            payload.time_on_page_ms = timeOnPage();
        }

        if (on.behaviour) {
            payload.click_count = clickCount;
            payload.paste_count = pasteCount;
            payload.back_navigations = backNavigations;
            payload.submit_attempts = submitAttempts;
            payload.steps = steps;

            if (on.mouse) {
                payload.pointer_moves = pointerMoves;
            }
        }

        if (on.scroll && scrollDepth !== null) {
            payload.scroll_depth_percent = scrollDepth;
        }

        return payload;
    }

    /** The page the visitor is looking at, read off the DOM when no step has fired yet. */
    function currentPageId() {
        return root?.querySelector('[data-sisf-page]')?.getAttribute('data-sisf-page') ?? null;
    }

    /**
     * Send one snapshot.
     *
     * A JSON Blob, not a bare string: sendBeacon on a string posts
     * text/plain;charset=UTF-8, which Laravel does not parse as JSON and which
     * would arrive as an empty request. sendBeacon returns false when its ~64KB
     * per-origin queue is full — that is the one case fetch(keepalive) covers,
     * and it is why the fallback is not just a nicety.
     */
    function post(payload) {
        let body;

        try {
            body = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        } catch {
            return false;
        }

        if (typeof navigator !== 'undefined' && typeof navigator.sendBeacon === 'function') {
            try {
                if (navigator.sendBeacon(endpoint, body)) {
                    return true;
                }
            } catch {
                // Fall through: some browsers throw rather than returning false.
            }
        }

        try {
            fetch(endpoint, {
                method: 'POST',
                body,
                headers: { 'Content-Type': 'application/json' },
                keepalive: true,
                credentials: 'same-origin',
            }).catch(() => {});

            return true;
        } catch {
            return false;
        }
    }

    /**
     * @param {string}  reason  what triggered this send, kept for debugging
     * @param {boolean} force   skip the interval floor (a terminal or structural event)
     */
    function flush(reason, force = false) {
        if (destroyed || !endpoint || !token) {
            return false;
        }

        // THE GATE. No interaction, no beacon, no draft row — a crawler and a
        // bounce both leave this page having written nothing.
        if (!interacted) {
            return false;
        }

        const now = clock();

        if (!force && (!dirty || now - lastSentAt < MIN_INTERVAL_MS)) {
            return false;
        }

        if (!post(snapshot(reason))) {
            return false;
        }

        lastSentAt = now;
        dirty = false;

        return true;
    }

    /* ------------------------------------------------------------------ */

    return {
        /** Install the listeners. Everything is delegated off the island's root. */
        attach(element) {
            if (destroyed || !element) {
                return;
            }

            root = element;
            lastPageId = currentPageId();

            const listen = (target, type, handler, options) => {
                target.addEventListener(type, handler, options);
                bound.push([target, type, handler, options]);
            };

            /*
             * THE SWITCHES GATE THE LISTENERS, not just what the snapshot
             * prints. focusin/focusout/paste used to be attached whatever
             * config('forms.capture') said, so `behaviour: false` still watched
             * every field, still built the field records and still posted them —
             * the admin turned observation off and got observation. A switch
             * that is off must mean nothing is being watched.
             *
             * Who owns what: `behaviour` owns the focus timeline (order,
             * revisits, dwell, the abandoned-field marker), pastes and clicks;
             * `keystrokes` owns typing and deletion counts; `mouse` owns pointer
             * sampling; `scroll` owns depth. Answer LENGTHS still arrive through
             * the renderer's change callback under any setting — they come with
             * the value the visitor typed and cost no listener of ours.
             */
            if (on.behaviour) {
                listen(root, 'focusin', handleFocusIn, true);
                listen(root, 'focusout', handleFocusOut, true);
                listen(root, 'paste', handlePaste, true);
                listen(root, 'pointerdown', handlePointerDown, true);
            }

            if (on.keystrokes) {
                listen(root, 'beforeinput', handleBeforeInput, true);
            }

            if (on.mouse) {
                listen(root, 'pointermove', handlePointerMove, { capture: true, passive: true });
            }

            if (on.scroll) {
                listen(window, 'scroll', handleScroll, { passive: true });
                handleScroll();
            }

            listen(document, 'visibilitychange', handleVisibility, false);
            listen(window, 'pagehide', handlePageHide, false);

            heartbeat = setInterval(() => flush('heartbeat'), HEARTBEAT_MS);
        },

        /**
         * FormRenderer's oninteract.
         *
         * Focus and blur arrive here too and are DELIBERATELY IGNORED: the
         * delegated focusout listener is the only one that sees relatedTarget,
         * so it is the only one that can tell a radio-group arrow key from a
         * real revisit. One source of truth for the focus timeline.
         */
        interact(event) {
            if (destroyed || !event) {
                return;
            }

            if (event.type === 'change') {
                const record = fieldRecord(event.field?.key);

                if (record) {
                    // LENGTH ONLY. The value is right here and is never kept.
                    record.length = measure(event.value);
                }

                touched();

                return;
            }

            if (event.type === 'submit') {
                submitAttempts += 1;
                closeField();

                const page = lastPageId ?? currentPageId();

                if (page) {
                    visited.add(page);
                }

                touched();
                // Synchronous, before the native POST navigates away: the draft
                // this upgrades has to exist by the time the submit lands.
                forceFlush('submit');
            }
        },

        /** FormRenderer's onstep — a page change, forward or back. */
        step(event) {
            if (destroyed || !event) {
                return;
            }

            // A page unmount fires no focusout for the inputs it destroys.
            closeField();

            if (event.direction === 'back') {
                backNavigations += 1;
            } else if (lastPageId) {
                visited.add(lastPageId);
            }

            lastPageId = event.pageId ?? lastPageId;

            if (steps.length < MAX_STEPS) {
                steps = [
                    ...steps,
                    { p: event.pageId ?? null, t: Math.round(clock() - startedAt), d: event.direction === 'back' ? 0 : 1 },
                ];
            }

            touched();
            forceFlush('step');
        },

        destroy() {
            if (destroyed) {
                return;
            }

            closeField();
            flush('destroy', true);

            destroyed = true;
            clearInterval(heartbeat);

            for (const [target, type, handler, options] of bound) {
                target.removeEventListener(type, handler, options);
            }

            bound.length = 0;
        },
    };
}

/** How much the visitor put in a field — never what. */
function measure(value) {
    if (value === null || value === undefined || value === false) {
        return 0;
    }

    if (Array.isArray(value)) {
        return value.length;
    }

    if (value === true) {
        return 1;
    }

    // A file answer is {id, name}: one file, not a string length.
    if (typeof value === 'object') {
        return 1;
    }

    return Math.min(String(value).length, MAX_SMALLINT);
}
