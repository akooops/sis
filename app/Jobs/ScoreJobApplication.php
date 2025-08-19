<?php

namespace App\Jobs;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ScoreJobApplication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public JobApplication $jobApplication)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Process current application
            $applicationData = $this->formatApplicationData($this->jobApplication);

            $response = Http::post(config('services.job_application_scoring_ai_model.url') . '/api/score-cv', [
                'application' => $applicationData
            ]);

            if ($response->successful()) {
                $this->jobApplication->update([
                    'ai_scored_at' => now(),
                    'ai_score' => $response->json()['score'] ?? null,
                    'ai_score_explanation' => $response->json()['explanation'] ?? null,
                    'ai_score_status' => JobApplication::AI_SCORE_STATUS_COMPLETED
                ]);
            } else {
                $this->jobApplication->update([
                    'ai_score_status' => JobApplication::AI_SCORE_STATUS_FAILED,
                    'ai_score_explanation' => 'API request failed: ' . $response->status()
                ]);
            }

        } catch (\Exception $e) {
            $this->jobApplication->update([
                'ai_score_status' => JobApplication::AI_SCORE_STATUS_FAILED,
                'ai_score_explanation' => 'Error: ' . $e->getMessage()
            ]);
        } finally {
            // After processing current job, check for next pending application
            $this->processNextPendingApplication();
        }
    }

    /**
     * Process the next pending application if any
     */
    private function processNextPendingApplication(): void
    {
        $nextApplication = JobApplication::whereNull('ai_scored_at')
            ->whereIn('ai_score_status', [JobApplication::AI_SCORE_STATUS_PENDING, JobApplication::AI_SCORE_STATUS_FAILED])
            ->first();

        if ($nextApplication) {
            // Mark as processing to prevent duplicate jobs
            $nextApplication->update(['ai_score_status' => JobApplication::AI_SCORE_STATUS_PROCESSING]);
            
            // Dispatch the next job
            self::dispatch($nextApplication);
        }
    }

    private function formatApplicationData(JobApplication $jobApplication)
    {
        return [
            'nationality' => $jobApplication->nationality,
            'address' => $jobApplication->address,
            'skills' => $jobApplication->skills,
            'education' => $jobApplication->education->map(function ($education) {
                return [
                    'institution' => $education->institution,
                    'degree' => $education->degree,
                    'field_of_study' => $education->field_of_study,
                    'start_year' => $education->start_year,
                    'end_year' => $education->end_year,
                    'description' => $education->description,
                ];
            }),
            'experiences' => $jobApplication->experiences->map(function ($experience) {
                return [
                    'company_name' => $experience->company_name,
                    'job_title' => $experience->job_title,
                    'start_year' => $experience->start_year,
                    'end_year' => $experience->end_year,
                    'is_current' => $experience->is_current,
                    'description' => $experience->description,
                ];
            }),
            'languages' =>  $jobApplication->languages->map(function ($language) {
                return [
                    'name' => $language->name,
                    'proficiency' => $language->proficiency,
                ];
            }),
            'job_posting' => [
                'name' => $jobApplication->jobPosting->name,
                'employment_type' => $jobApplication->jobPosting->employment_type,
                'is_remote' => $jobApplication->jobPosting->is_remote,
                'required_years_of_experience' => $jobApplication->jobPosting->required_years_of_experience,
            ],
        ];
    }
}
