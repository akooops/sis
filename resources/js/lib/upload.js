/**
 * Single-endpoint upload flow.
 *
 * Files are POSTed to `POST /api/v1/admin/uploads` which quarantines + scans them and
 * returns a media reference `{ id, type, name, mime, size, scan_status }`. Forms
 * then submit only that `id`; the server attaches it on save.
 *
 * The allowed size + extensions are shared from the backend via
 * `$page.props.media` (see HandleInertiaRequests), so client-side pre-checks
 * stay in sync with server enforcement (StoreMediaData).
 */

import { page } from '@inertiajs/svelte';
import { get } from 'svelte/store';
import { api, ApiError } from './api/client';

/** The media type keys, in display order. */
export const MEDIA_TYPES = ['images', 'audio', 'videos', 'documents'];

/** The shared media config, or sane defaults if not present. */
export function mediaConfig() {
    return (
        get(page).props?.media ?? {
            max_file_size: 10 * 1024 * 1024,
            max_file_size_human: '10 MB',
            allowed_types: {},
            accept: {},
        }
    );
}

/** Comma-joined `.ext` accept string for an input of the given type. */
export function acceptFor(type) {
    return mediaConfig().accept?.[type] ?? '';
}

/** Comma-joined `.ext` accept string spanning several types. */
export function acceptForTypes(types) {
    const cfg = mediaConfig();
    return (types ?? [])
        .map((type) => cfg.accept?.[type] ?? '')
        .filter(Boolean)
        .join(',');
}

/**
 * Resolve which media type a file belongs to (by extension) within the allowed
 * set — used when an uploader accepts more than one type.
 * @returns {string | null} the matching type key, or null if none match.
 */
export function typeForFile(name, allowed = MEDIA_TYPES) {
    const ext = fileExtension(name);
    const cfg = mediaConfig();
    for (const type of allowed) {
        const exts = (cfg.allowed_types?.[type] ?? []).map((e) => e.toLowerCase());
        if (exts.includes(ext)) return type;
    }
    return null;
}

/** Allowed extensions (no dot) for a type. */
export function allowedExtensions(type) {
    return mediaConfig().allowed_types?.[type] ?? [];
}

function fileExtension(name) {
    const dot = name.lastIndexOf('.');
    return dot >= 0 ? name.slice(dot + 1).toLowerCase() : '';
}

/**
 * Validate a File against the shared media config for `type`.
 * @returns {string | null} an error message, or null when valid.
 */
export function validateFile(file, type) {
    const cfg = mediaConfig();
    const max = cfg.max_file_size ?? 0;
    if (max && file.size > max) {
        return `File is too large. Maximum size is ${cfg.max_file_size_human}.`;
    }
    const allowed = allowedExtensions(type).map((e) => e.toLowerCase());
    if (allowed.length && !allowed.includes(fileExtension(file.name))) {
        return `Unsupported file type. Allowed: ${allowed.join(', ')}.`;
    }
    return null;
}

/**
 * Upload one file. Runs a client-side pre-check, then POSTs to the upload
 * endpoint and returns the media reference.
 *
 * @param {File} file
 * @param {string} type  one of images | documents | videos | audio
 * @param {{ signal?: AbortSignal }} [opts]
 * @returns {Promise<{ id: string, type: string, name: string, mime: string, size: number, scan_status: string }>}
 */
export async function uploadFile(file, type, opts = {}) {
    const preError = validateFile(file, type);
    if (preError) {
        throw new ApiError(preError, { status: 422, errors: { file: preError } });
    }

    const form = new FormData();
    form.append('type', type);
    form.append('file', file);

    return api.post(route('api.v1.admin.media.store'), form, { signal: opts.signal });
}
