<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\BigDBMLeads;
use Illuminate\Http\Request;
use App\Models\LeadspeekUser;
use Illuminate\Bus\Queueable;
use App\Models\LeadspeekReport;
use Illuminate\Support\Facades\Log;
use App\Models\DistributionSchedule;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Http\Controllers\WebhookController;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Bus;

class LeadsDistributionJobs implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $id;
    protected $leadspeek_api_id;
    protected $last_interval;
    protected $webhookController;

    public $timeout = 120; // Timeout of 2 minutes
    // public $failOnTimeout = true;

    public function __construct($id, $leadspeek_api_id, $last_interval)
    {
        $this->onQueue('distribution');
        $this->id = $id;
        $this->leadspeek_api_id = $leadspeek_api_id;
        $this->last_interval = $last_interval;
        // info("id schedule job : " . $id);

    }

    public function uniqueId()
    {
        return "{$this->leadspeek_api_id}|{$this->id}|{$this->last_interval}";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WebhookController $webhookController)
    {

        $this->webhookController = $webhookController;
        //get schedule
        Log::info("Handle In DistScheduleID : " . $this->id);
        $schedule = DistributionSchedule::where('id',$this->id)->first();
        $schedule->last_interval += 1;
        $schedule->status = 'progress';
        $schedule->save();

        $campaign = LeadspeekUser::select('id','company_id', 'leadspeek_api_id', 'lp_limit_leads', 'leadspeek_locator_keyword','active','disabled','active_user')
                                    ->where('leadspeek_type', 'enhance')
                                    ->where('leadspeek_api_id', $schedule->leadspeek_api_id)
                                    ->first();

        if (!empty($campaign) && !empty($schedule)) {

            //set keyword
            $keyword = explode(",",$campaign->leadspeek_locator_keyword);

            //get total leads to distribute
            $leads_per_interval = json_decode($schedule->leads_per_interval);
            $total_leads = 0;
            foreach ($leads_per_interval as $value) {
                if ($value->interval == $schedule->last_interval) {
                    $total_leads = $value->leads ;
                   
                }
            }

            if ($total_leads > 0) {
                Log::info("Distribution in Plan | Last Interval: " . $schedule->last_interval . ' | Total Leads :' .   $total_leads);
                $this->leads_distribution($schedule->leadspeek_api_id,$schedule->list_queue_id,$keyword,$total_leads);
            }
            // Log::info("block2");

            if ($schedule->last_interval > $schedule->end_interval) 
            {
                Log::info("Last Interval : " . $schedule->last_interval . " >= End Interval : " . $schedule->end_interval);
                while(true)
                {
                    sleep(1);
                    $leads_report = LeadspeekReport::where('leadspeek_api_id', $campaign->leadspeek_api_id)
                                    ->where('created_at','>=', Carbon::today())
                                    ->count();
                    $leads_database = BigDBMLeads::select('id','leadspeek_api_id','list_queue_id','keyword','md5')
                                    ->where('leadspeek_api_id', $campaign->leadspeek_api_id)
                                    //->where('list_queue_id',$schedule->list_queue_id)
                                    ->where(function($query) use ($keyword) {
                                        foreach ($keyword as $word) {
                                            $query->orWhereRaw('? LIKE CONCAT("%", `keyword`, "%")', [trim($word)]);
                                        }
                                    })
                                    ->count();

                    // Log::info("cek jumlah existing leads_redistribution", [
                    //     'leads_report' => $leads_report,
                    //     'lp_limit_leads' => $campaign->lp_limit_leads,
                    //     'leads_database' => $leads_database,
                    // ]);
                        
                    if($leads_report >= $campaign->lp_limit_leads || $leads_database == 0) {
                        $schedule->status = 'done';
                        $schedule->save();
                        Log::info("ALREADY FULL FILL LEAD PER DAY OR THE BIGDBM LEADS ZERO LEFT");
                        break;
                    } else {
                        // Log::info("masuk leads_redistribution");
                        $minus_leads = $campaign->lp_limit_leads - $leads_report;
                        $this->leads_distribution($schedule->leadspeek_api_id,$schedule->list_queue_id,$keyword,$minus_leads);
                        Log::info("NOT FILL THE REQUEST PER DAY YET OR NOT ZERO BIGDBM LEADS | MINUS LEADS : " . $minus_leads);
                        
                        if($minus_leads > 10) {
                            Log::info(['=============CARA CHUNK IN BREAK=============']);
                            break;
                            Log::info(['=============CARA CHUNK IN BREAK=============']);
                        }
                    }
                }
            }
        }else{
            Log::info("There is no campaign to distribute");
        }
    }

    private function leads_distribution($leadspeek_api_id, $list_queue_id, $keyword, $limit) 
    {
        /* CHUNK METHOD */
        $leads = BigDBMLeads::select('id','leadspeek_api_id','list_queue_id','keyword','md5')
                            ->where('leadspeek_api_id', $leadspeek_api_id)
                            // ->where('list_queue_id', $list_queue_id)
                            // ->whereIn('keyword', $keyword)
                            ->where(function($query) use ($keyword) {
                                foreach ($keyword as $word) {
                                    $query->orWhereRaw('? LIKE CONCAT("%", `keyword`, "%")', [trim($word)]);
                                }
                            })
                            ->where('status','pending')
                            ->orderBy('id', 'desc')
                            ->limit($limit)
                            ->get();

        Log::info("RUN leads_distribution leadspeekID : " . $leadspeek_api_id . " | List Queue ID : " . $list_queue_id);
        Log::info($keyword);
        Log::info(['leadspeek-api_id' => $leadspeek_api_id]);

        if(count($leads) > 0) {
            
            foreach($leads as $lead) {
                $lead->status = 'proceed';
                $lead->save();
            }

            $leads = $leads->toArray();

            // pakai metode chunk jika lebih dari 10
            if($limit > 10) {
                Log::info(['=============CARA CHUNK=============']);
                Log::info(['count' => count($leads)]);
    
                $jobs = [];
                $leads_chunk = array_chunk($leads, 10);
    
                foreach($leads_chunk as $index => $lead) {
                    $jobs[] = new SendLeadDistributionJob($lead, $this->leadspeek_api_id, $this->id, $this->last_interval, $index);
                }
    
                $jobs[] = new FinalizeSendLeadDistributionJob($this->id, $leadspeek_api_id, $keyword, $this->last_interval);
    
                Log::info(['count_jobs' => count($jobs), 'limit' => $limit]);

                Bus::chain($jobs)->dispatch();
                Log::info(['=============CARA CHUNK=============']);
            } 
            // jika leadsnya <= 10, maka pakai cara biasa jangan pakai chunk
            else {
                Log::info(['=============CARA LANGSUNG=============']);
                $leads = BigDBMLeads::select('id','leadspeek_api_id','list_queue_id','keyword','md5')
                                    ->where('leadspeek_api_id', $leadspeek_api_id)
                                    //->where('list_queue_id', $list_queue_id)
                                    //->whereIn('keyword', $keyword)
                                    ->where(function($query) use ($keyword) {
                                        foreach ($keyword as $word) {
                                            $query->orWhereRaw('? LIKE CONCAT("%", `keyword`, "%")', [trim($word)]);
                                        }
                                    })
                                    ->orderBy('id', 'desc')
                                    ->limit($limit)
                                    ->get();

                foreach($leads as $lead)
                {
                    $data = new Request([
                        'label' => $lead['leadspeek_api_id'].'|'.$lead['keyword'],
                        'md5_email' => $lead['md5']
                    ]);

                    $this->webhookController->getleadwebhook($data);

                    /* CHECK BIGBDM DOUBLE ATAU TIDAK DENGAN CARA CEK ID NYA ADA TIDAK DI DATABASE */
                    $double_id_bigdbm_lead = BigDBMLeads::where('id', $lead['id'])->exists();
                    Log::channel('custom')->info('', ['id' => $lead['id'], 'result' => ($double_id_bigdbm_lead ? "EXISTS" : 'NOTHING') ]);
                    // Log::channel('double')->info('', ['id' => $lead['id'], 'result' => ($double_id_bigdbm_lead ? "EXISTS" : 'NOTHING') ]);
                    /* CHECK BIGBDM DOUBLE ATAU TIDAK DENGAN CARA CEK ID NYA ADA TIDAK DI DATABASE */
            
                    BigDBMLeads::where('id', $lead['id'])->delete();
                }
                Log::info(['=============CARA LANGSUNG=============']);
            }
        }

        // if($leads->count() > 0) {
        //     Log::info(['count' => $leads->count()]);

        //     $jobs = [];

        //     $leads->chunk(10, function ($chunkLeads) use (&$jobs) {
        //         $jobs[] = new SendLeadDistributionJob($chunkLeads->toArray());
        //     });

        //     $jobs[] = new FinalizeSendLeadDistributionJob($this->id, $leadspeek_api_id, $keyword);

        //     Log::info(['count_jobs' => count($jobs), 'limit' => $limit]);
            
        //     Bus::chain($jobs)->dispatch();
        // }
        /* CHUNK METHOD */

        /* ONE ONE LEADS METHOD */
        /*
        $leads = BigDBMLeads::select('id','leadspeek_api_id','list_queue_id','keyword','md5')
        ->where('leadspeek_api_id', $leadspeek_api_id)
        //->where('list_queue_id', $list_queue_id)
       //->whereIn('keyword', $keyword)
       ->where(function($query) use ($keyword) {
            foreach ($keyword as $word) {
                $query->orWhereRaw('? LIKE CONCAT("%", `keyword`, "%")', [trim($word)]);
            }
        })
        ->orderBy('id', 'desc')
        ->limit($limit)
        ->get()
        ->toArray();

        Log::info("RUN leads_distribution leadspeekID : " . $leadspeek_api_id . " | List Queue ID : " . $list_queue_id);
        Log::info($keyword);
        Log::info(['count_leads' => count($leads), 'leadspeek-api_id' => $leadspeek_api_id]);

        if (count($leads) > 0) {
            // $lead_index = 1;
            // $lead_limit = count($leads);
            
            foreach($leads as $lead)
            {
                SendLeadDistributionJob::dispatch(
                    $lead,
                    $this->id,
                    $keyword,
                );
            }
        }
        */
        /* ONE ONE LEADS METHOD */
    }

    // public function middleware()
    // {
    //     return [new WithoutOverlapping($this->id)];
    // }

    // public function uniqueId()
    // {
    //     return $this->id;
    // }

    public function failed(Exception $exception)
    {
        $schedule = DistributionSchedule::where('id', $this->id)->first();
        Log::error([
            "result" => "failed distribution",
            "last_interval" => $schedule->last_interval,
            "list_queue_id" => $schedule->list_queue_id,
            "leadspeek_api_id" => $schedule->leadspeek_api_id,
            "exception" => $exception->getMessage(),
        ]);

    }
}
