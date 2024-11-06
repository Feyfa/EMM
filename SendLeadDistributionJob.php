<?php

namespace App\Jobs;

use App\Http\Controllers\WebhookController;
use App\Models\BigDBMLeads;
use App\Models\DistributionSchedule;
use App\Models\LeadspeekReport;
use App\Models\LeadspeekUser;
use Carbon\Carbon;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Request;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendLeadDistributionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhookController;
    /* NEW METHOD */
    protected $leads;
    protected $leadspeek_api_id;
    protected $schedule_id;
    protected $last_interval;
    protected $index;
    /* NEW METHOD */

    /* OLD METHOD */
    // protected $lead;
    // protected $schedule_id;
    // protected $keyword;
    /* OLD METHOD */

    public $timeout = 120; // Timeout of 2 minutes
    // public $failOnTimeout = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    /* NEW METHOD */
    public function __construct($leads, $leadspeek_api_id, $schedule_id, $last_interval, $index)
    {
        $this->onQueue('send_lead');
        $this->leads = $leads;
        $this->leadspeek_api_id = $leadspeek_api_id;
        $this->schedule_id = $schedule_id;
        $this->last_interval = $last_interval;
        $this->index = $index;
    }
    /* NEW METHOD */

    /* OLD METHOD */
    // public function __construct($lead, $schedule_id, $keyword)
    // {
    //     $this->onQueue('send_lead');
        
    //     $this->lead = $lead;
    //     $this->schedule_id = $schedule_id;
    //     $this->keyword = $keyword;
    // }
    /* OLD METHOD */

    public function uniqueId()
    {
        return "{$this->leadspeek_api_id}|{$this->schedule_id}|{$this->last_interval}|{$this->index}";
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    /* NEW METHOD */
    public function handle(WebhookController $webhookController)
    {
        Log::channel('custom')->info('=============start_sendlead=============');
        Log::channel('custom')->info('', ['count_leads_chunk' => count($this->leads)]);

        $this->webhookController = $webhookController;

        /* SEND LEADS AFTER CHUNK / 10 LEADS */
        foreach($this->leads as $lead)
        {
            Log::channel('custom')->info("==oneleads==");
            Log::channel('custom')->info('', ['leadspeek_api_id' => $lead['leadspeek_api_id']]);
            $data = new Request([
                'label' => $lead['leadspeek_api_id'].'|'.$lead['keyword'],
                'md5_email' => $lead['md5']
            ]);

            // $this->webhookController->getleadwebhook($data);

            /* CHECK BIGBDM DOUBLE ATAU TIDAK DENGAN CARA CEK ID NYA ADA TIDAK DI DATABASE */
            $double_id_bigdbm_lead = BigDBMLeads::where('id', $lead['id'])->exists();
            Log::channel('custom')->info('', ['id' => $lead['id'], 'result' => ($double_id_bigdbm_lead ? "EXISTS" : 'NOTHING') ]);
            // Log::channel('double')->info('', ['id' => $lead['id'], 'result' => ($double_id_bigdbm_lead ? "EXISTS" : 'NOTHING') ]);
            /* CHECK BIGBDM DOUBLE ATAU TIDAK DENGAN CARA CEK ID NYA ADA TIDAK DI DATABASE */

            if($double_id_bigdbm_lead) {
                $this->webhookController->getleadwebhook($data);
                BigDBMLeads::where('id', $lead['id'])->delete();
            }
            

            Log::channel('custom')->info("==oneleads==");
        }
        /* SEND LEADS AFTER CHUNK / 10 LEADS */
        
        Log::channel('custom')->info('=============end_sendlead=============');
    }
    /* NEW METHOD */

    /* OLD METHOD */
    // public function handle(WebhookController $webhookController)
    // {
    //     /* LOCK PROCESS GETDATAMATCH */
    //     Log::channel('custom')->info('=============start=============');
    //     // Log::channel('custom')->info('START SENDLEAD LOCK');
    //     // $initLock = 'initsendlead';
    //     // while(!$this->webhookController->acquireLock($initLock)) {
    //     //     Log::channel('custom')->info("Initial Get Data Match Processing. Waiting to acquire lock.");
    //     //     sleep(1); // Wait before trying again
    //     // }
    //     /* LOCK PROCESS GETDATAMATCH */

    //     Log::channel('custom')->info("", ['lead_id' => $this->lead['id']]);
    //     Log::channel('custom')->info('', ['count_keyword' => count($this->keyword)]);
    //     Log::channel('custom')->info('', ["RUN DISTRIBUTION id" => $this->lead['id'],"leadspeek_api_id" => $this->lead['leadspeek_api_id'], "data : " => $this->lead['list_queue_id']]);

    //     $start_time = microtime(true);
    //     $this->webhookController = $webhookController;
    //     $data = new Request([
    //         'label' => $this->lead['leadspeek_api_id'].'|'.$this->lead['keyword'],
    //         'md5_email' => $this->lead['md5']
    //     ]);

    //     Log::channel('custom')->info('', ['data' => $data->toArray()]);

    //     $this->webhookController->getleadwebhook($data);

    //     /* CHECK BIGBDM DOUBLE ATAU TIDAK DENGAN CARA CEK ID NYA ADA TIDAK DI DATABASE */
    //     $double_id_bigdbm_lead = BigDBMLeads::where('id', $this->lead['id'])->exists();
    //     Log::channel('double')->info('', ['id' => $this->lead['id'], 'result' => ($double_id_bigdbm_lead ? "EXISTS" : 'NOTHING') ]);
    //     /* CHECK BIGBDM DOUBLE ATAU TIDAK DENGAN CARA CEK ID NYA ADA TIDAK DI DATABASE */
        
    //     BigDBMLeads::where('id', $this->lead['id'])->delete();

    //     $schedule = DistributionSchedule::where('id',$this->schedule_id)->first();

    //     if ($schedule->last_interval >= $schedule->end_interval) 
    //     {
    //         $campaign = LeadspeekUser::select('id','company_id', 'leadspeek_api_id', 'lp_limit_leads', 'leadspeek_locator_keyword','active','disabled','active_user')
    //                                 ->where('leadspeek_type', 'enhance')
    //                                 ->where('leadspeek_api_id', $this->lead['leadspeek_api_id'])
    //                                 ->first();
    
    //         $leads_report = LeadspeekReport::where('leadspeek_api_id', $campaign->leadspeek_api_id)
    //                                     ->where('created_at','>=', Carbon::today())
    //                                     ->count();
    //         $leads_database = BigDBMLeads::select('id','leadspeek_api_id','list_queue_id','keyword','md5')
    //                                     ->where('leadspeek_api_id', $campaign->leadspeek_api_id)
    //                                     //->where('list_queue_id',$schedule->list_queue_id)
    //                                     ->where(function($query) {
    //                                         foreach ($this->keyword as $word) {
    //                                             $query->orWhereRaw('? LIKE CONCAT("%", `keyword`, "%")', [trim($word)]);
    //                                         }
    //                                     })
    //                                     ->count();
    
    //         // Log::channel('custom')->info("cek jumlah existing leads_redistribution", [
    //         //     'leads_report' => $leads_report,
    //         //     'lp_limit_leads' => $campaign->lp_limit_leads,
    //         //     'leads_database' => $leads_database,
    //         // ]);
                
    //         if($leads_report == $campaign->lp_limit_leads || $leads_database == 0) {
    //             $schedule->status = 'done';
    //             $schedule->save();
    //             Log::channel('custom')->info("ALREADY FULL FILL LEAD PER DAY OR THE BIGDBM LEADS ZERO LEFT");
    //         }
    //     }

    //     $end_time = microtime(true);
    //     Log::channel('custom')->info("", ['duration' => (int) ($end_time - $start_time), 'start_time' => $start_time, 'end_time' => $end_time]);
        
    //     /* RELEASE LOCK PROCESS FOR GETDATAMATCH */
    //     Log::channel('custom')->info("RELEASE SENDLEAD LOCK");
    //     Log::channel('custom')->info('=============end=============');
    //     // $this->webhookController->releaseLock($initLock);
    //     /* RELEASE LOCK PROCESS FOR GETDATAMATCH */
    // }
    /* OLD METHOD */

    // public function middleware()
    // {
    //     return [new WithoutOverlapping($this->schedule_id)];
    // }

    // public function uniqueId()
    // {
    //     return $this->schedule_id;
    // }

    public function failed(Exception $exception)
    {
        Log::channel('custom')->error([
            "result" => "failed send job",
            "exception" => $exception->getMessage(),
        ]);
    }
}
