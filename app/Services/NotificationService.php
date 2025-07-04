<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\ContactSubmission;
use App\Models\Inquiry;
use App\Models\VisitBooking;
use App\Models\JobApplication;

class NotificationService
{
    /**
     * Create a notification for contact submission
     */
    public function createContactSubmissionNotification(ContactSubmission $contactSubmission)
    {
        return Notification::create([
            'type' => Notification::TYPE_CONTACT_SUBMISSION,
            'title' => 'New Contact Submission',
            'message' => "New contact submission from {$contactSubmission->name}",
            'url' => route('admin.contact-submissions.show', $contactSubmission->id),
            'data' => [
                'contact_submission_id' => $contactSubmission->id,
                'name' => $contactSubmission->name,
                'email' => $contactSubmission->email,
                'subject' => $contactSubmission->subject,
            ],
        ]);
    }

    /**
     * Create a notification for inquiry
     */
    public function createInquiryNotification(Inquiry $inquiry)
    {
        return Notification::create([
            'type' => Notification::TYPE_INQUIRY,
            'title' => 'New Inquiry',
            'message' => "New inquiry from {$inquiry->guardian_name} for {$inquiry->student_name}",
            'url' => route('admin.inquiries.show', $inquiry->id),
            'data' => [
                'inquiry_id' => $inquiry->id,
                'guardian_name' => $inquiry->guardian_name,
                'student_name' => $inquiry->student_name,
                'grade_applied' => $inquiry->grade_applied,
            ],
        ]);
    }

    /**
     * Create a notification for visit booking
     */
    public function createVisitBookingNotification(VisitBooking $visitBooking)
    {
        $visitService = $visitBooking->visitService;
        
        return Notification::create([
            'type' => Notification::TYPE_VISIT_BOOKING,
            'title' => 'New Visit Booking',
            'message' => "New visit booking from {$visitBooking->guardian_name} for {$visitService->name}",
            'url' => route('admin.visit-bookings.show', $visitBooking->id),
            'data' => [
                'visit_booking_id' => $visitBooking->id,
                'guardian_name' => $visitBooking->guardian_name,
                'visit_service_name' => $visitService->name,
                'visitors_count' => $visitBooking->visitors_count,
                'booking_date' => $visitBooking->visitTimeSlot->date ?? null,
            ],
        ]);
    }

    /**
     * Create a notification for job application
     */
    public function createJobApplicationNotification(JobApplication $jobApplication)
    {
        $jobPosting = $jobApplication->jobPosting;
        
        return Notification::create([
            'type' => Notification::TYPE_JOB_APPLICATION,
            'title' => 'New Job Application',
            'message' => "New job application from {$jobApplication->first_name} {$jobApplication->last_name} for {$jobPosting->name}",
            'url' => route('admin.job-applications.show', $jobApplication->id),
            'data' => [
                'job_application_id' => $jobApplication->id,
                'applicant_name' => "{$jobApplication->first_name} {$jobApplication->last_name}",
                'job_posting_name' => $jobPosting->name,
                'email' => $jobApplication->email,
            ],
        ]);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount()
    {
        return Notification::unread()->count();
    }

    /**
     * Get recent notifications
     */
    public function getRecentNotifications($limit = 10)
    {
        return Notification::latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get notifications by type
     */
    public function getNotificationsByType($type, $limit = 10)
    {
        return Notification::byType($type)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        $notification = Notification::find($notificationId);
        
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        
        return false;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        return Notification::unread()->update([
            'read_at' => now(),
        ]);
    }
} 