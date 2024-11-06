<?php

namespace App\Jobs;

use App\Models\JobProgress;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChunkCsvClientJob implements ShouldQueue
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
        //Log::info('Constructor initialized', ['job_id' => $job_id, 'data' => $data]);
        $this->job_id = $job_id;
        $this->data = $data;

        $this->startProcess();
    }

    public function startProcess() {
        //Log::info('Constructor HANDLE', ['job_id' => $this->job_id, 'data' => $this->data]);
        /* UPDATE JOBPRGRESS */
        //$jobId = $this->job->getJobId();
        $jobProgress = JobProgress::where('job_id',$this->job_id)
                ->update([
                    'status' => 'progress'
                ]);
        
        /* UPDATE JOBPRGRESS */
        
        /* GET CONTENT FILE IN DIGITAL OCEAN SPACE WITH CURL */
        $ch = curl_init($this->data['filedownload_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $csvText = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            return response()->json(['error' => $error], 500);
        }
        
        curl_close($ch);
        /* GET CONTENT FILE IN DIGITAL OCEAN SPACE WITH CURL */
        
        /* CONVERT TO ARRAY AND FILTER */
        // ubah menjadi array
        $csvArray = explode("\n", $csvText); 
        // filter karakter
        $csvArray = array_map(function($line) {
            return trim($line, "\xEF\xBB\xBF\"\\\r");
        }, $csvArray);
        // filter membuat unique email
        $csvArray = collect($csvArray)->unique()->values()->all();
        
        $numChunks = ceil(count($csvArray) / 2000);
        $loopCount = 1;

        // chunk per 2000
        $csvArray = array_chunk($csvArray, 2000);
        /* CONVERT TO ARRAY AND FILETER */

        foreach($csvArray as $emails)
        {
            // $lockKey = 'chunckprocess';

            // while (!$this->acquireLock($lockKey)) {
            //     sleep(1); // Wait before trying again
            // }
            //$numberRandom = $this->generateUniqueNumber();

            /* CREATE JOB PROGRESS */
            // JobProgress::create([
            //     'job_id' => $numberRandom,
            //     'upload_at' => $this->data['upload_at'],
            //     'lead_userid' => '',
            //     'company_id' => $this->data['company_id'],
            //     'suppression_type' => $this->data['suppression_type'],
            //     'leadspeek_api_id' => '',
            //     'filename' => $this->data['filename'],
            //     'status' => 'queue',
            // ]);
            /* CREATE JOB PROGRESS */

            /* RUNNING QUEUE */
            InsertCsvClientJob::dispatch($this->job_id, [
                'emails' => $emails,
                'company_id' => $this->data['company_id'],
                'suppression_type' => $this->data['suppression_type'],
                'loopCount' => $loopCount,
                'numChunks' => $numChunks
            ]);
            /* RUNNING QUEUE */

            $loopCount = $loopCount + 1;
            // $this->releaseLock($lockKey);
            // sleep(1);
        }

        /* DELETE FILE IN SPACES */
        Storage::disk('spaces')->delete($this->data['path']);
        /* DELETE FILE IN SPACES */

        /* UPDATE JOBPRGRESS */
        // $jobProgress = JobProgress::where('job_id',$this->job_id)
        //         ->update([
        //             'status' => 'done',
        //             'done_at' => Carbon::now()->timestamp
        //         ]);
        /* UPDATE JOBPRGRESS */
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
    }

    private function acquireLock($lockKey, $ttl = 10) {
        return Cache::add($lockKey, true, $ttl);
    }

    // Function to release lock
    private function releaseLock($lockKey) {
        Cache::forget($lockKey);
    }

    public function generateUniqueNumber()
    {
        do {
            $number = mt_rand(1, 1000000000);
        } while (JobProgress::where('job_id')->exists());

        return $number;
    }
}
