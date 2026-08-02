<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\ContactDetail;
use Illuminate\Http\JsonResponse;

/**
 * The contact catalogue the form's type picker renders from.
 *
 * Read-only and not paginated: it is a config-backed registry, not a table, so
 * there is nothing to filter or sort and the whole list is five rows. Each entry
 * carries the shape of its `value`, which is what tells the form whether to draw
 * a phone, an email, a URL or nothing at all.
 *
 * `extras` is the server's OWN branch, shipped rather than left to be guessed:
 * it names the group of extra columns the type owns, and the form renders those
 * fields from it. Inferring them from the `value` shape instead is what would
 * let a second url-shaped type draw a Platform field that update() then nulls.
 *
 * The social platform list rides along in the same response — one field of the
 * form needs it, and it comes from the same config file. Each entry carries the
 * network's icon, so a row stores only the code and nothing renders an
 * admin-supplied string into a class attribute.
 */
class ContactTypesController extends ApiController
{
    public function index(): JsonResponse
    {
        $types = [];

        foreach (ContactDetail::types() as $code => $type) {
            $types[] = ['code' => $code, 'extras' => ContactDetail::extrasFor($code)] + $type;
        }

        $platforms = [];

        foreach (ContactDetail::platforms() as $code => $platform) {
            $platforms[] = ['code' => $code] + $platform;
        }

        return $this->respond([
            'types' => $types,
            'platforms' => $platforms,
        ], 'Contact types retrieved successfully');
    }
}
