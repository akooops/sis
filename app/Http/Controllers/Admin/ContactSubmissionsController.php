<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactSubmission;
use App\Models\Inquiry;
use App\Models\Facility;
use Illuminate\Http\Request;

class ContactSubmissionsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $contactSubmissions = ContactSubmission::with('facility')->latest();

        $facilityId = $this->indexService->checkIfEmpty($request->query('facility_id'));

        if ($facilityId === 'main') {
            $contactSubmissions->whereNull('facility_id');
        } elseif ($facilityId) {
            $contactSubmissions->where('facility_id', $facilityId);
        }

        if ($search) {
            $contactSubmissions->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%')
                      ->orWhere('subject', 'like', '%' . $search . '%')
                      ->orWhere('message', 'like', '%' . $search . '%');
            });
        }

        $contactSubmissions = $contactSubmissions->paginate($perPage, ['*'], 'contactSubmission', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'contactSubmissions' => $contactSubmissions->items(),
                'pagination' => $this->indexService->handlePagination($contactSubmissions)
            ]);
        }

        return inertia('ContactSubmissions/Index', [
            'facilities' => Facility::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ContactSubmission $contactSubmission)
    {    
        return inertia('ContactSubmissions/Show', compact('contactSubmission'));
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
    */
    public function destroy(ContactSubmission $contactSubmission)
    {
        $contactSubmission->delete();

        return redirect()->route('admin.contact-submissions.index')
                        ->with('success','Contact Submission deleted successfully');
    }
}
