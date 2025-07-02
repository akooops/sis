<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inquiry;
use Illuminate\Http\Request;

class InquiriesController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->indexService->limitPerPage($request->query('perPage', 10));
        $page = $this->indexService->checkPageIfNull($request->query('page', 1));
        $search = $this->indexService->checkIfSearchEmpty($request->query('search'));

        $inquiries = Inquiry::latest();

        if ($search) {
            $inquiries->where(function($query) use ($search) {
                $query->where('id', $search)
                      ->orWhere('guardian_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%')
                      ->orWhere('student_name', 'like', '%' . $search . '%')
                      ->orWhere('student_birthdate', 'like', '%' . $search . '%')
                      ->orWhere('student_school', 'like', '%' . $search . '%')
                      ->orWhere('academic_year_applied', 'like', '%' . $search . '%')
                      ->orWhere('grade_applied', 'like', '%' . $search . '%')
                      ->orWhere('questions', 'like', '%' . $search . '%');
            });
        }

        $inquiries = $inquiries->paginate($perPage, ['*'], 'inquiry', $page);

        if ($request->expectsJson() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'inquiries' => $inquiries->items(),
                'pagination' => $this->indexService->handlePagination($inquiries)
            ]);
        }

        return inertia('Inquiries/Index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Inquiry $inquiry)
    {    
        return inertia('Inquiries/Show', compact('inquiry'));
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
    */
    public function destroy(Inquiry $inquiry)
    {
        $inquiry->delete();

        return redirect()->route('admin.inquiries.index')
                        ->with('success','Inquiry deleted successfully');
    }
}
