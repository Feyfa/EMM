<?php

namespace App\Jobs;

use App\Models\BigDBMLeads;
use App\Models\DistributionSchedule;
use App\Models\LeadspeekReport;
use App\Models\LeadspeekUser;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeSendLeadDistributionJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;

    protected $schedule_id;
    protected $leadspeek_api_id;
    protected $last_interval;
    protected $keyword;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($schedule_id, $leadspeek_api_id, $keyword, $last_interval)
    {   
        $this->onQueue('send_lead');
        $this->schedule_id = $schedule_id;
        $this->leadspeek_api_id = $leadspeek_api_id;
        $this->keyword = $keyword;
        $this->last_interval = $last_interval;
    }

    public function uniqueId()
    {
        return "{$this->leadspeek_api_id}|{$this->schedule_id}|{$this->last_interval}";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::channel('custom')->info('=============start_finalizesendlead=============');
        $schedule = DistributionSchedule::where('id',$this->schedule_id)->first();
    
        if ($schedule->last_interval >= $schedule->end_interval) 
        {
            $campaign = LeadspeekUser::select('id','company_id', 'leadspeek_api_id', 'lp_limit_leads', 'leadspeek_locator_keyword','active','disabled','active_user')
                                    ->where('leadspeek_type', 'enhance')
                                    ->where('leadspeek_api_id', $this->leadspeek_api_id)
                                    ->first();
    
            $leads_report = LeadspeekReport::where('leadspeek_api_id', $campaign->leadspeek_api_id)
                                        ->where('created_at','>=', Carbon::today())
                                        ->count();
            $leads_database = BigDBMLeads::select('id','leadspeek_api_id','list_queue_id','keyword','md5')
                                        ->where('leadspeek_api_id', $campaign->leadspeek_api_id)
                                        //->where('list_queue_id',$schedule->list_queue_id)
                                        ->where(function($query) {
                                            foreach ($this->keyword as $word) {
                                                $query->orWhereRaw('? LIKE CONCAT("%", `keyword`, "%")', [trim($word)]);
                                            }
                                        })
                                        ->count();
    
            // Log::channel('custom')->info("cek jumlah existing leads_redistribution", [
            //     'leads_report' => $leads_report,
            //     'lp_limit_leads' => $campaign->lp_limit_leads,
            //     'leads_database' => $leads_database,
            // ]);
                
            if($leads_report >= $campaign->lp_limit_leads || $leads_database == 0) {
                $schedule->status = 'done';
                $schedule->save();
                Log::channel('custom')->info("ALREADY FULL FILL LEAD PER DAY OR THE BIGDBM LEADS ZERO LEFT");
            }
        }
        Log::channel('custom')->info('=============end_finalizesendlead=============');
    }
}
