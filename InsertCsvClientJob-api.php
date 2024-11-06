<?php

namespace App\Jobs;

use App\Models\JobProgress;
use App\Models\SuppressionList;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsertCsvClientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $job_id;
    public array $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $job_id, array $data)
    {
        $this->job_id = $job_id;
        $this->data = $data;

        $this->startProcess();
    }

    public function startProcess() {
        // Calculate the percentage completed
        $percentage = ($this->data['loopCount'] / $this->data['numChunks']) * 100;
        $percentage = round($percentage, 2);

        /* UPDATE JOBPRGRESS */
        $jobProgress = JobProgress::where('job_id',$this->job_id)
                ->update([
                    'status' => 'progress',
                    'percentage' => $percentage,
                ]);
        /* UPDATE JOBPRGRESS */

        $suppressionLists = [];

        foreach($this->data['emails'] as $email)
        {
            if(filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $md5email =  md5($email);

                $existingSuppression = SuppressionList::where('emailmd5', $md5email)
                                                      ->where('company_id', $this->data['company_id'])
                                                      ->where('suppression_type', $this->data['suppression_type'])
                                                      ->exists();

                if (!$existingSuppression) {
                    $suppressionLists[] = [
                        'lead_userid' => '',
                        'company_id' => $this->data['company_id'],
                        'leadspeek_api_id' => '',
                        'suppression_type' => $this->data['suppression_type'],
                        'reidentification_date' => date('Y-m-d'),
                        'email' => $email,
                        'emailmd5' => $md5email,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        DB::transaction(function () use ($suppressionLists) {
            try {
                SuppressionList::insert($suppressionLists);
            } catch (\Exception $e) {
                Log::error('Error inserting suppression list', ['message' => $e->getMessage()]);
            }
        });

        if ($this->data['loopCount'] == $this->data['numChunks']) {
            $jobProgress = JobProgress::where('job_id',$this->job_id)
                    ->update([
                        'status' => 'done',
                        'done_at' => Carbon::now()->timestamp
                    ]);
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
    }
}
