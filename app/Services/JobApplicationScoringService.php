<?php

namespace App\Services;

use App\Jobs\ScoreJobApplication;
use App\Models\JobApplication;

class JobApplicationScoringService
{
    /**
     * Queue a job application for AI scoring
     */
    public static function queueForScoring(JobApplication $jobApplication): void
    {       
        // Check if already scored or in queue
        if ($jobApplication->ai_score_status != JobApplication::AI_SCORE_STATUS_PENDING) {
            return;
        }

        // Check if there are any jobs currently processing
        $isProcessing = JobApplication::where('ai_score_status', JobApplication::AI_SCORE_STATUS_PROCESSING)->exists();

        if ($isProcessing) {
            // Mark as pending, the current job will pick it up when done
            $jobApplication->update(['ai_score_status' => JobApplication::AI_SCORE_STATUS_PENDING]);
        } else {
            // No jobs processing, start this one immediately
            $jobApplication->update(['ai_score_status' => JobApplication::AI_SCORE_STATUS_PROCESSING]);
            ScoreJobApplication::dispatch($jobApplication);
        }
    }
}
