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

            // Call Ollama directly
            $instruction = "Rate this job application against the job posting on a scale of 1-10 and provide a brief explanation for the Saudi HR market context.";
            $inputData = "Job Application Data: " . json_encode($applicationData);
            
            $prompt = "Below is an instruction that describes a task, paired with an input that provides further context. Write a response that appropriately completes the request.\n\n### Instruction:\n{$instruction}\n\n### Input:\n{$inputData}\n\n### Response:\n";

            $response = Http::post(config('services.job_application_scoring_ai_model.url') . '/api/generate', [
                'model' => config('services.job_application_scoring_ai_model.model'),
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                    'top_k' => 40,
                    'num_ctx' => 4096
                ]
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $output = $responseData['response'] ?? '';
                
                // Extract numerical score from the response
                preg_match('/Score:\s*(\d+)\/10/', $output, $matches);
                $score = $matches[1] ?? null;
                
                $this->jobApplication->update([
                    'ai_scored_at' => now(),
                    'ai_score' => $score,
                    'ai_score_explanation' => $output,
                    'ai_score_status' => JobApplication::AI_SCORE_STATUS_COMPLETED
                ]);
            } else {
                $this->jobApplication->update([
                    'ai_score_status' => JobApplication::AI_SCORE_STATUS_FAILED,
                    'ai_score_explanation' => 'API request failed: ' . $response->status()
                ]);

                // Dispatch the next job
                self::dispatch($this->jobApplication);
            }

        } catch (\Exception $e) {
            $this->jobApplication->update([
                'ai_score_status' => JobApplication::AI_SCORE_STATUS_FAILED,
                'ai_score_explanation' => 'Error: ' . $e->getMessage()
            ]);

            // Dispatch the next job
            self::dispatch($this->jobApplication);
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
