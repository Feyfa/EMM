<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\BigDBMLeads;
use Illuminate\Http\Request;
use App\Models\LeadspeekUser;
use App\Models\LeadsListsQueue;
use App\Models\LeadspeekReport;
use Illuminate\Console\Command;
use App\Models\LeadspeekInvoice;
use App\Jobs\BigDBMCheckStatusJob;
use App\Jobs\LeadsDistributionJobs;
use Illuminate\Support\Facades\Log;
use App\Models\DistributionSchedule;

class LeadsDistributionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:leadsdistribution';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // re check failed job in check status
        try {
            $now = Carbon::now();
            $deadline_check = Carbon::today()->setHour(18);
            if ($now->isToday() && $now <= $deadline_check) {
                
                $failedCheckStatus = LeadsListsQueue::select(
                    'lead_list_queue.*',
                    'leadspeek_users.lp_limit_leads',
                    'users.company_root_id'
                    )->where('lead_list_queue.status', 500)
                ->where('lead_list_queue.created_at', '>=', Carbon::today())
                ->leftJoin('leadspeek_users', 'lead_list_queue.leadspeek_api_id', '=', 'leadspeek_users.leadspeek_api_id')
                ->leftJoin('users', 'lead_list_queue.company_id', '=', 'users.company_id')
                ->where('users.user_type','userdownline')
                ->get();
    
                if (count($failedCheckStatus) > 0) {
                    foreach ($failedCheckStatus as $value) {
                        $value->status = 300;
                        $value->save();
    
                        $_queueID = $value->list_queue_id;
                        $_companyID =  $value->company_id;
                        $_companyRootID =  $value->company_root_id;
                        $_campaignID = $value->leadspeek_api_id;
                        $_LPLimitLeads = $value->lp_limit_leads;
                        
                        BigDBMCheckStatusJob::dispatch(
                            $_queueID,
                            $_companyID,
                            $_companyRootID,
                            $_campaignID,
                            $_LPLimitLeads,
                        );
                    }
                }
            }
        } catch (\Throwable $th) {
            Log::error(['error' => 're check failed job in check status', 'message' => $th->getMessage()]);
        }
        // re check failed job in check status

        // start distribution
        $schedules = DistributionSchedule::whereIn('status', ['queue', 'progress'])->orderBy('id','asc')->get();
        if (count($schedules) > 0) {
            foreach ($schedules as $schedule) {
                Log::info("INITIAL Distributon schedule ID : " . $schedule->id);
                LeadsDistributionJobs::dispatch($schedule->id, $schedule->leadspeek_api_id, $schedule->last_interval);
            }
        }else {
            Log::info("task not scheduled yet");
        }      
        // start distribution
                  
    }

   
}
