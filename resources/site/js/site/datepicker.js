import flatpickr from 'flatpickr';

import 'flatpickr/dist/flatpickr.min.css';

/**
 * Date picker (the Inquiries form).
 *
 *   <input data-datepicker data-max-date="today">
 */
export default async function initDatePickers(root = document) {
    if (document.documentElement.lang === 'ar') {
        const { Arabic } = await import('flatpickr/dist/l10n/ar.js');

        flatpickr.localize(Arabic);
    }

    root.querySelectorAll('[data-datepicker]').forEach((el) => {
        flatpickr(el, {
            dateFormat: 'Y-m-d',
            maxDate: el.dataset.maxDate || undefined,
        });
    });
}
