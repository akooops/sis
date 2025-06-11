<?php

namespace App\Http\Controllers\Admin;

use App\Models\ContactSubmission;
use App\Models\Inquiry;
use Illuminate\Http\Request;

class ContactSubmissionsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $contactSubmissions = ContactSubmission::latest();

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

        return view('admin.contact-submissions.index', [
            'contactSubmissions' => $contactSubmissions,
            'pagination' => $this->indexService->handlePagination($contactSubmissions)
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
        return view('admin.contact-submissions.show', compact('contactSubmission'));
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
