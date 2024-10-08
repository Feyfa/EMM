<?php

namespace App\Http\Controllers;

use App\Mail\Gmail;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\EmailNotification;
use App\Models\FailedRecord;
use App\Models\LeadspeekInvoice;
use App\Models\LeadspeekReport;
use App\Models\LeadspeekUser;
use App\Models\Topup;
use App\Models\User;
use App\Services\GoogleSheet;
use Carbon\Carbon;
use DateTime;
use Exception;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_Spreadsheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\OAuth\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\StripeClient;

class MarketingController extends Controller
{
    /* CHARGE PREPAID COST MONTH WHEN STATUS CAMPAIGN PLAY , PAUSED OR RUN */
    public function processchargeprepaidcostmonth()
    {
        date_default_timezone_set('America/Chicago');

        //  1 bulan yang lalu dari sekarang, di timezone America/Chicago
        $subMonth = Carbon::now()->subMonth()->toDateString();

        $leads = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.url_code','leadspeek_users.user_id','leadspeek_users.leadspeek_api_id','leadspeek_users.campaign_name','leadspeek_users.spreadsheet_id',
                                    'leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','leadspeek_users.lp_max_lead_month','leadspeek_users.paymentterm','leadspeek_users.leadspeek_type','leadspeek_users.gtminstalled','leadspeek_users.company_id as company_parent',
                                    'leadspeek_users.active_user','leadspeek_users.lp_enddate','leadspeek_users.platformfee','leadspeek_users.lp_min_cost_month','leadspeek_users.lp_limit_leads','leadspeek_users.lp_limit_freq',
                                    'leadspeek_users.created_at', 'leadspeek_users.embedded_lastreminder','leadspeek_users.trysera','leadspeek_users.topupoptions','leadspeek_users.leadsbuy','leadspeek_users.stopcontinual','leadspeek_users.cost_perlead',
                                    'companies.company_name','companies.id as company_id',
                                    'users.customer_payment_id','users.customer_card_id','users.email','users.company_root_id')
                              ->join('users','leadspeek_users.user_id','=','users.id')
                              ->join('companies','users.company_id','=','companies.id')
                              ->whereRaw("DATE(leadspeek_users.start_billing_date) <= ?", [$subMonth])
                              ->where('leadspeek_users.paymentterm','=','Prepaid')
                              ->where('users.active','=','T')
                              ->where(function ($query) {
                                $query->where(function ($query) {
                                    /* PLAY */
                                    $query->where('leadspeek_users.active','=','T')
                                          ->where('leadspeek_users.disabled','=','F')
                                          ->where('leadspeek_users.active_user','=','T');
                                    /* PLAY */
                                })
                                ->orWhere(function ($query) {
                                    /* PAUSED ON PLAY */
                                    $query->where('leadspeek_users.active','=','F')
                                          ->where('leadspeek_users.disabled','=','F')
                                          ->where('leadspeek_users.active_user','=','T');
                                    /* PAUSED ON PLAY */
                                })
                                ->orWhere(function ($query) {
                                    /* PAUSED */
                                    $query->where('leadspeek_users.active','=','F')
                                          ->where('leadspeek_users.disabled','=','T')
                                          ->where('leadspeek_users.active_user','=','T');
                                    /* PAUSED */
                                });
                              })
                              ->get();

        foreach($leads as $lead)
        {   
            /* CHARGE CLIENT TO AGECY */
            if($lead['lp_min_cost_month'] >= 0.5) {
                /* CHARGE CLIENT TO AGENCY */
                $this->chargePrepaidCostMonth($lead['customer_payment_id'],$lead['customer_card_id'],$lead['email'],$lead['lp_min_cost_month'],$lead,'client');
                /* CHARGE CLIENT TO AGENCY */
            }
            /* CHARGE CLIENT TO AGECY */

            /* CHARGE AGECNY TO ROOT */
            $leadspeekType = $lead['leadspeek_type'];
            $paymentTerm = $lead['paymentterm'];
            $typeMinCostMonth = '';
            
            if($lead['leadspeek_type'] === 'local') {
                $typeMinCostMonth = 'LeadspeekMinCostMonth';
            } else if($lead['leadspeek_type'] === 'locator') { 
                $typeMinCostMonth = 'LocatorMinCostMonth';
            } else if($lead['leadspeek_type'] === 'enhance') {
                $typeMinCostMonth = 'EnhanceMinCostMonth';
            }

            $masterCost = $this->getcompanysetting($lead['company_parent'], 'costagency');
            if(!isset($masterCost->$leadspeekType->$paymentTerm->$typeMinCostMonth)) {
                $masterCost = $this->getcompanysetting($lead['company_root_id'], 'rootcostagency');
            }

            $platform_LeadspeekMinCostMonth = $masterCost->$leadspeekType->$paymentTerm->$typeMinCostMonth;

            if($platform_LeadspeekMinCostMonth >= 0.5) {
                $agency = User::select('users.id','users.company_root_id','users.customer_payment_id','users.customer_card_id','users.email','companies.company_name')
                              ->join('companies','users.company_id','=','companies.id')
                              ->where('users.company_id','=',$lead['company_parent'])
                              ->where('users.company_parent','=',$lead['company_root_id'])
                              ->first();

                /* CHARGE AGENCY TO ROOT */
                $this->chargePrepaidCostMonth($agency['customer_payment_id'],$agency['customer_card_id'],$agency['email'],$platform_LeadspeekMinCostMonth,$agency,'userdownline');
                /* CHARGE AGENCY TO ROOT */
            }
            /* CHARGE AGECNY TO ROOT */

            /* UPDATE start_billing_date */
            $lead->start_billing_date = date('Y-m-d H:i:s');
            $lead->save();
            /* UPDATE start_billing_date */
        }
    }
    /* CHARGE PREPAID COST MONTH WHEN STATUS CAMPAIGN PLAY , PAUSED OR RUN */

    /* PROCESS CHARGE COST MONTH CLENT TO AGENCY OR AGENCY TO CLIENT */
    public function chargePrepaidCostMonth($custStripeID,$custStripeCardID,$custEmail,$totalAmount,$usrInfo,$type)
    {
        if($type != 'userdownline' && $type != 'client') {
            return ['result'=>'error','message'=>'type invalid'];
        }

        $_lp_user_id = $usrInfo['id'];
        $invoiceNum = date('Ymd') . '-' . $_lp_user_id;

        $accConID = '';
        if($type == 'client') {
            /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */
            if ($usrInfo['company_parent'] != '') {
                $accConID = $this->check_connected_account($usrInfo['company_parent'],$usrInfo['company_root_id']);
            }
            /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */
        }

        /** GET STRIPE KEY */
        $stripeseckey = config('services.stripe.secret');
        $stripepublish = $this->getcompanysetting($usrInfo['company_root_id'],'rootstripe');
        if ($stripepublish != '') {
            $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
        }
        /** GET STRIPE KEY */

        $stripe = new StripeClient([
            'api_key' => $stripeseckey,
            'stripe_version' => '2020-08-27'
        ]);

        if(trim($custStripeID) != '' && trim($custStripeCardID) != '' && $totalAmount > 0.5 && $totalAmount != '') { 
            date_default_timezone_set('America/Chicago');

            $chargeAmount = $totalAmount * 100;

            if($type == 'userdownline') {
                $defaultInvoice = '#' . $invoiceNum . '-' . $usrInfo['company_name'] . " - platform_min_cost";
            } else if($type == 'client') {
                $defaultInvoice = '#' . $invoiceNum . '-' . str_replace($usrInfo['leadspeek_api_id'],'',$usrInfo['company_name']) . ' #' . $usrInfo['leadspeek_api_id'] . " - min_cost";
            }

            /* CHARGE WITH STRIPE */
            try {
                if($type == 'userdownline') {
                    Log::info("", [
                        'block' => 'userdownline',
                        'defaultInvoice' => $defaultInvoice,
                    ]);
                    $payment_intent =  $stripe->paymentIntents->create([
                        'payment_method_types' => ['card'],
                        'customer' => trim($custStripeID),
                        'amount' => $chargeAmount,
                        'currency' => 'usd',
                        'receipt_email' => $custEmail,
                        'payment_method' => $custStripeCardID,
                        'confirm' => true,
                        'description' => $defaultInvoice,
                    ]);
                } else if($type == 'client') {
                    Log::info('', [
                        'block' => 'client',
                        'defaultInvoice' => $defaultInvoice,
                        'stripe_account' => $accConID,
                        'company_parent' => $usrInfo['company_parent'],
                        'company_root_id' => $usrInfo['company_root_id']
                    ]);
                    $payment_intent =  $stripe->paymentIntents->create([
                        'payment_method_types' => ['card'],
                        'customer' => trim($custStripeID),
                        'amount' => $chargeAmount,
                        'currency' => 'usd',
                        'receipt_email' => $custEmail,
                        'payment_method' => $custStripeCardID,
                        'confirm' => true,
                        'description' => $defaultInvoice,
                    ],['stripe_account' => $accConID]);
                }

                return ['result'=>'success','message'=>'payment successfully'];
            } catch (RateLimitException $e) {
                // Too many requests made to the API too quickly
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                return ['result'=>'error','message'=>$errorstripe];
            } catch (InvalidRequestException $e) {
                // Invalid parameters were supplied to Stripe's API
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                return ['result'=>'error','message'=>$errorstripe];
            } catch (AuthenticationException $e) {
                // Authentication with Stripe's API failed
                // (maybe you changed API keys recently)
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                return ['result'=>'error','message'=>$errorstripe];
            } catch (ApiConnectionException $e) {
                // Network communication with Stripe failed
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                return ['result'=>'error','message'=>$errorstripe];
            } catch (ApiErrorException $e) {
                // Display a very generic error to the user, and maybe send
                // yourself an email
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                return ['result'=>'error','message'=>$errorstripe];
            } catch (Exception $e) {
                // Something else happened, completely unrelated to Stripe
                $errorstripe = 'error not stripe things';
                return ['result'=>'error','message'=>$errorstripe];
            }
            /* CHARGE WITH STRIPE */
        }

        return ['result'=>'error','message'=>'requirements are not met'];
    }
    /* PROCESS CHARGE COST MONTH CLENT TO AGENCY*/

    /** CHECK ALL ACTIVE CAMPAIGN WITH STATUS PLAY,PAUSED,STOP will Sync to SiFi */
    public function synccampaignstatus(Request $request) {
        date_default_timezone_set('America/New_York');

        $pageSize = 100; // Adjust the page size based on your requirements
        $page = 1;
        $active = "";
        $paused = "";
        $ended = "";
        $emptycampaign = "";
        $devmode = "";
        if (config('services.appconf.devmode') === true) {
            $devmode = "(SANDBOX) - ";
        }

        $yesterday = date('Y-m-d', strtotime('-3 day'));
        
        do {
        /** ACTIVE,PAUSED,ENDED CAMPAIGN */
        $campaignList = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.leadspeek_api_id','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.active_user','leadspeek_users.campaign_name','users.company_root_id')
                            ->join('users','leadspeek_users.user_id','=','users.id')
                            ->join('companies','users.company_id','=','companies.id')
                            ->where('leadspeek_users.archived','=','F')
                            ->where('leadspeek_type','=','locator')
                            ->where('users.user_type','=','client')
                            ->whereNull('leadspeek_users.sync_with_sifi')
                            ->orWhere(function($query) use ($yesterday) {
                                
                                $query->where(DB::raw('DATE(leadspeek_users.sync_with_sifi)'),'=',$yesterday)
                                        ->whereNotNull('leadspeek_users.sync_with_sifi');
                                
                            })->offset(($page - 1) * $pageSize)->limit($pageSize)->get();
        foreach($campaignList as $cl) {
            if ($cl['leadspeek_organizationid'] != '' && $cl['leadspeek_campaignsid'] != '') {
                if($cl['active'] == 'T' && $cl['disabled'] == 'F' && $cl['active_user'] == 'T') { /** IF CAMPAIGN ACTIVE STATE */
                    $active = $active . $this->check_sifi_state($cl['leadspeek_organizationid'],$cl['leadspeek_campaignsid'],$cl['leadspeek_api_id'],'active') . ',';
                }else if($cl['active'] == 'F' && $cl['disabled'] == 'T' && $cl['active_user'] == 'T') { /** IF CAMPAIGN PAUSED STATE */
                    $paused = $paused . $this->check_sifi_state($cl['leadspeek_organizationid'],$cl['leadspeek_campaignsid'],$cl['leadspeek_api_id'],'paused') . ',';
                }else if($cl['active'] == 'F' && $cl['disabled'] == 'T' && $cl['active_user'] == 'F') { /** IF CAMPAIGN ENDED STATE */
                    $ended = $ended . $this->check_sifi_state($cl['leadspeek_organizationid'],$cl['leadspeek_campaignsid'],$cl['leadspeek_api_id'],'ended') . ',';
                }

                /** UPDATE LAST SYNC */
                $updateLU = LeadspeekUser::find($cl['id']);
                $updateLU->sync_with_sifi = date('Y-m-d');
                $updateLU->save();
                /** UPDATE LAST SYNC */
                usleep(600000);
            }else{
                $emptycampaign = $emptycampaign . $cl['leadspeek_api_id'] . ',';
            }
        }
        
        $page++;
        /** ACTIVE,PAUSED,ENDED CAMPAIGN */
        }while($campaignList->isNotEmpty());

        $active = rtrim($active,',');
        $paused = rtrim($paused,',');
        $ended = rtrim($ended,',');
        $emptycampaign = rtrim($emptycampaign,',');

        $details = [
            'errormsg'  => $devmode . 'Cron Job Run (sync_campaign_status). SYNC to this campaign ID :<br/><br/><strong>Active:</strong> ' . $active . ' <br/><br/><strong>Paused :</strong> ' . $paused . '<br/><br/><strong>Ended :</strong> ' . $ended . '<br/><br/><strong>Empty ID :</strong> ' . $emptycampaign,
        ];

        $from = [
            'address' => 'newleads@leadspeek.com',
            'name' => 'support',
            'replyto' => 'harrison@uncommonreach.com',
        ];
        
        $this->send_email(array('harrison@uncommonreach.com'),'Cron Job Run (sync_campaign_status)',$details,array(),'emails.tryseramatcherrorlog',$from);
    }

    public function check_sifi_state($_organizationID,$_campaignsID,$_leadspeek_api_id,$_status) {
        $http = new \GuzzleHttp\Client;

        $appkey = "86bb19a0-43e6-0139-8548-06b4c2516bae";
        $usrkey = "63c52610-87cd-0139-b15f-06a60fe5fe77";
        $organizationID = trim($_organizationID);
        $campaignsID = trim($_campaignsID);

        $_result = "";

        $devmode = "";
        // if (config('services.appconf.devmode') === true) {
        //     $devmode = "(SANDBOX) - ";
        // }

        try {
            /** CHECK ACTIONS IF CAMPAIGN ALLOW TO RUN STATUS  */
            $apiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID;
            $options = [
                'headers' => [
                    'X-App-Key' => $appkey,        
                    'X-User-Key' => $usrkey,
                    'Content-Type' => 'application/json',
                ],
            ];

            $response = $http->get($apiURL,$options);
            $result =  json_decode($response->getBody());

            $sifiStatus = strtolower($result->campaigns[0]->status);
            $_status = strtolower($_status);

            if ($_status != $sifiStatus) {
                $_result = $_leadspeek_api_id . '->' . $sifiStatus . '->' . strtolower($_status);

                for($j=0;$j<count($result->campaigns[0]->actions);$j++) {
                    if ($_status == 'active') {
                        if(isset($result->campaigns[0]->actions[$j]->activate)) {
                            //echo "activate";
                            try {
                                /** ACTIVATE THE CAMPAIGN */
                                $ActionApiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID . "/activate";
                                $ActionResponse = $http->post($ActionApiURL,$options);
                                /** ACTIVATE THE CAMPAIGN */
                            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                                $details = [
                                    'errormsg'  => 'Error when trying to Activate Campaign Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID . ' (' . $e->getCode() . ')',
                                ];

                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                                
                                $this->send_email(array('harrison@uncommonreach.com'),$devmode . 'Error Log for Activate Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from);
                            }
                        }
                    }else if ($_status == 'paused') {
                        if(isset($result->campaigns[0]->actions[$j]->pause)) {
                            //echo "Pause";
                            try {
                                /** PAUSE THE CAMPAIGN */
                                $ActionApiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID . "/pause";
                                $ActionResponse = $http->post($ActionApiURL,$options);
                                /** PAUSE THE CAMPAIGN */
                            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                                $details = [
                                    'errormsg'  => 'Error when trying to Pause Campaign Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID . ' (' . $e->getCode() . ')',
                                ];

                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                                
                                $this->send_email(array('harrison@uncommonreach.com'),$devmode . 'Error Log for Pause Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from);
                            }
                        }
                    }else if ($_status == 'ended') {
                        if(isset($result->campaigns[0]->actions[$j]->end)) {
                            //echo "Pause";
                            try {
                                /** PAUSE THE CAMPAIGN */
                                $ActionApiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID . "/end";
                                $ActionResponse = $http->post($ActionApiURL,$options);
                                /** PAUSE THE CAMPAIGN */
                            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                                $details = [
                                    'errormsg'  => 'Error when trying to Pause Campaign Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID . ' (' . $e->getCode() . ')',
                                ];
                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                                
                                $this->send_email(array('harrison@uncommonreach.com'),$devmode . 'Error Log for Pause Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            }
                        }
                    }
                    //echo $result->campaigns[0]->actions[$j]->activate[0];
                }

            }

            return $_result;
        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            
                $details = [
                    'errormsg'  => 'Error when trying to sync campaign (' . $_status . ') Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID . '(' . $e->getCode() . ')',
                ];
                $from = [
                    'address' => 'newleads@leadspeek.com',
                    'name' => 'support',
                    'replyto' => 'harrison@uncommonreach.com',
                ];

                $this->send_email(array('harrison@uncommonreach.com'),$devmode . 'Error SYNC Campaign (' . $_status . ') ID : ' . $_leadspeek_api_id . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from);
            return $_result . 'Failed';
        }
            
    }
    /** CHECK ALL ACTIVE CAMPAIGN WITH STATUS PLAY,PAUSED,STOP will Sync to SiFi */

    /** CHECK INCOMING LEAD EVERY 30 minutes should have incoming lead*/
    public function checkincominglead(Request $request) {
        date_default_timezone_set('America/Chicago');

        $currentDateTime = date('Y-m-d H:i:s');
        $thirtyMinutesAgo = date('Y-m-d H:i:s', strtotime('-30 minutes'));

        $createdData = FailedRecord::where('created_at', '>=', $thirtyMinutesAgo)->limit(5)->get();
        if (count($createdData) == 0) {
            /** SEND EMAIL TO ME */
            $details = [
                'errormsg'  => 'Alert - None Lead been Fired for 30 minutes (SYSTEM API CronJob)<br/>',
            ];

            $from = [
                'address' => 'noreply@sitesettingsapi.com',
                'name' => 'support',
                'replyto' => 'noreply@sitesettingsapi.com',
            ];
                $this->send_email(array('harrison@uncommonreach.com'),'Alert - None Lead been Fired for 30 minutes (SYSTEM API CronJob L-53 Marketing)',$details,array(),'emails.tryseramatcherrorlog',$from,'');
            /** SEND EMAIL TO ME */
        }
    }
    /** CHECK INCOMING LEAD EVERY 30 minutes should have incoming lead*/

    /** CRAWLING WEBSITE EMBEDDED CODE */
    private function dateDiffInDays($date1, $date2) 
    {
      // Calculating the difference in timestamps
      $diff = strtotime($date2) - strtotime($date1);
  
      // 1 day = 24 hours
      // 24 * 60 * 60 = 86400 seconds
      return abs(round($diff / 86400));
    }

    public function notactiveagency(Request $request) {
        $http = new \GuzzleHttp\Client;
        $appkey = config('services.trysera.api_id');
        //$companyrootID = (config('services.application.systemid') != '' && config('services.application.systemid') !== null)?config('services.application.systemid'):'60';
        /** GET ROOT COMPANY */
        $rootCompany = User::select('company_id')
                                    ->whereNull('company_parent')
                                    ->where('user_type','=','userdownline')
                                    ->where('active','=','T')
                                    ->get();
        /** GET ROOT COMPANY */

        $chkNotActiveAgency = User::select('company_id')
                                    //->where('company_parent','=',$companyrootID)
                                    ->whereIn('company_parent',$rootCompany)
                                    ->where('user_type','=','userdownline')
                                    ->where('active','=','F')
                                    ->get();
        if(count($chkNotActiveAgency) > 0) {
            foreach($chkNotActiveAgency as $agency) {
                
                $chkinvalidusr = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.user_id','leadspeek_users.leadspeek_api_id','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','leadspeek_users.campaign_name','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.active_user',
                                        'companies.id as company_id','companies.company_name','leadspeek_users.trysera','leadspeek_users.leadspeek_type')
                                    ->join('users','leadspeek_users.user_id','=','users.id')
                                    ->join('companies','users.company_id','=','companies.id')
                                    ->where('users.user_type','=','client')
                                    ->where('users.active','=','T')
                                    ->where('users.company_parent','=',$agency['company_id'])
                                    ->where(function($query){
                                        $query->where(function($query){
                                            $query->where('leadspeek_users.active','=','T')
                                                ->where('leadspeek_users.disabled','=','F')
                                                ->where('leadspeek_users.active_user','=','T');
                                        })
                                        ->orWhere(function($query){
                                            $query->where('leadspeek_users.active','=','F')
                                                ->where('leadspeek_users.disabled','=','F')
                                                ->where('leadspeek_users.active_user','=','T');
                                        })
                                        ->orWhere(function($query){
                                            $query->where('leadspeek_users.active','=','F')
                                                ->where('leadspeek_users.disabled','=','T')
                                                ->where('leadspeek_users.active_user','=','T');
                                        });
                                    })->get();

                                    if (count($chkinvalidusr) > 0) {
                                        foreach($chkinvalidusr as $inv) {
                                            
                                             /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                                             $_company_id = $inv['company_id'];
                                             $_user_id = $inv['user_id'];
                                             $_lp_user_id = $inv['id'];
                                             $_leadspeek_api_id = $inv['leadspeek_api_id'];
                                             $organizationid = $inv['leadspeek_organizationid'];
                                             $campaignsid = $inv['leadspeek_campaignsid'];
                                             $tryseramethod = (isset($inv['trysera']) && $inv['trysera'] == "T")?true:false;

                                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                                            if ($organizationid != '' && $campaignsid != '' && $inv['leadspeek_type'] == "locator") {
                                                $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                                                if ($camp == true) {
                                                    /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                                                    $updateleadusr = LeadspeekUser::find($inv['id']);
                                                    $updateleadusr->active = 'F';
                                                    $updateleadusr->disabled = 'T';
                                                    $updateleadusr->active_user = 'F';
                                                    $updateleadusr->save();
                                                    
                                                    /** DISABLED CLIENT */
                                                        $updateUser = User::find($_user_id);
                                                        $updateUser->active = "F";
                                                        $updateUser->save();
                                                    /** DISABLED CLIENT */
                                                }else{
                                                    /** SEND EMAIL TO ME */
                                                    $details = [
                                                        'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                                    ];

                                                    $from = [
                                                        'address' => 'noreply@sitesettingsapi.com',
                                                        'name' => 'support',
                                                        'replyto' => 'noreply@sitesettingsapi.com',
                                                    ];
                                                        $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - Cron-notactiveagency -L128) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                                    /** SEND EMAIL TO ME */
                                                    continue;
                                                }
                                            }else if ($inv['leadspeek_type'] == "local") {
                                                /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                                                $updateleadusr = LeadspeekUser::find($inv['id']);
                                                $updateleadusr->active = 'F';
                                                $updateleadusr->disabled = 'T';
                                                $updateleadusr->active_user = 'F';
                                                $updateleadusr->save();
                                                
                                                /** DISABLED CLIENT */
                                                    $updateUser = User::find($_user_id);
                                                    $updateUser->active = "F";
                                                    $updateUser->save();
                                                /** DISABLED CLIENT */
                                            }
                                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                            
                                             /** GET COMPANY NAME AND CUSTOM ID */
                                             $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                                             /** GET COMPANY NAME AND CUSTOM ID */
                            
                                             $campaignName = '';
                                             if (isset($inv['campaign_name']) && trim($inv['campaign_name']) != '') {
                                                 $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$inv['campaign_name']);
                                             }
                            
                                             $company_name = str_replace($_leadspeek_api_id,'',$inv['company_name']) . $campaignName;
                            
                                             if ($tryseramethod) {
                                                $pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $inv['leadspeek_api_id'];
                                                $pauseoptions = [
                                                    'headers' => [
                                                        'Authorization' => 'Bearer ' . $appkey,
                                                    ],
                                                    'json' => [
                                                        "SubClient" => [
                                                            "ID" => $inv['leadspeek_api_id'],
                                                            "Name" => trim($company_name),
                                                            "CustomID" => $tryseraCustomID ,
                                                            "Active" => false
                                                        ]       
                                                    ]
                                                ]; 
                                                $pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                                                $result =  json_decode($pauseresponse->getBody());
                                             }
                            
                                             /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                                             
                            
                                            /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                            
                                            /** SEND EMAIL TO ME */
                                            $details = [
                                                'errormsg'  => 'User already not active Leadspeek ID :' . $_leadspeek_api_id . ' companyID: ' . $_company_id . ' Company Parent:' . $_user_id . ' Campaign Name:' . $inv['campaign_name'],
                                            ];
                            
                                            $from = [
                                                'address' => 'newleads@leadspeek.com',
                                                'name' => 'support',
                                                'replyto' => 'harrison@uncommonreach.com',
                                            ];
                                            //$this->send_email(array('harrison@uncommonreach.com'),'notactiveuser - User already not Active #' . $_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                            /** SEND EMAIL TO ME */
                            
                                        }
                                    }


            }
        }
    }

    public function notactiveuser(Request $request) {
        $http = new \GuzzleHttp\Client;
        $appkey = config('services.trysera.api_id');

        $chkinvalidusr = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.user_id','leadspeek_users.leadspeek_api_id','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','leadspeek_users.campaign_name','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.active_user',
                                        'companies.id as company_id','companies.company_name','leadspeek_users.trysera','leadspeek_users.leadspeek_type')
                                    ->join('users','leadspeek_users.user_id','=','users.id')
                                    ->join('companies','users.company_id','=','companies.id')
                                    ->where('users.user_type','=','client')
                                    ->where('users.active','=','F')
                                    ->where(function($query){
                                        $query->where(function($query){
                                            $query->where('leadspeek_users.active','=','T')
                                                ->where('leadspeek_users.disabled','=','F')
                                                ->where('leadspeek_users.active_user','=','T');
                                        })
                                        ->orWhere(function($query){
                                            $query->where('leadspeek_users.active','=','F')
                                                ->where('leadspeek_users.disabled','=','F')
                                                ->where('leadspeek_users.active_user','=','T');
                                        });
                                    })->get();

        if (count($chkinvalidusr) > 0) {
            foreach($chkinvalidusr as $inv) {

                 /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                 $_company_id = $inv['company_id'];
                 $_user_id = $inv['user_id'];
                 $_lp_user_id = $inv['id'];
                 $_leadspeek_api_id = $inv['leadspeek_api_id'];
                 $organizationid = $inv['leadspeek_organizationid'];
                 $campaignsid = $inv['leadspeek_campaignsid'];
                 $tryseramethod = (isset($inv['trysera']) && $inv['trysera'] == "T")?true:false;

                /** ACTIVATE CAMPAIGN SIMPLIFI */
                if ($organizationid != '' && $campaignsid != '' && $inv['leadspeek_type'] == "locator") {
                    $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                    if ($camp == true) {
                        /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                        $updateleadusr = LeadspeekUser::find($inv['id']);
                        $updateleadusr->active = 'F';
                        $updateleadusr->disabled = 'T';
                        $updateleadusr->active_user = 'F';
                        $updateleadusr->save();
                    }else{
                        /** SEND EMAIL TO ME */
                        $details = [
                            'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                        ];

                        $from = [
                            'address' => 'noreply@sitesettingsapi.com',
                            'name' => 'support',
                            'replyto' => 'noreply@sitesettingsapi.com',
                        ];
                            $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - Cron-notactiveuser - 235) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                        /** SEND EMAIL TO ME */
                        continue;
                    }
                }else if ($inv['leadspeek_type'] == "local") {
                    /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                    $updateleadusr = LeadspeekUser::find($inv['id']);
                    $updateleadusr->active = 'F';
                    $updateleadusr->disabled = 'T';
                    $updateleadusr->active_user = 'F';
                    $updateleadusr->save();
                }
                /** ACTIVATE CAMPAIGN SIMPLIFI */

                 /** GET COMPANY NAME AND CUSTOM ID */
                 $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                 /** GET COMPANY NAME AND CUSTOM ID */

                 $campaignName = '';
                 if (isset($inv['campaign_name']) && trim($inv['campaign_name']) != '') {
                     $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$inv['campaign_name']);
                 }

                 $company_name = str_replace($_leadspeek_api_id,'',$inv['company_name']) . $campaignName;

                 if ($tryseramethod) {
                    $pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $inv['leadspeek_api_id'];
                    $pauseoptions = [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $appkey,
                        ],
                        'json' => [
                            "SubClient" => [
                                "ID" => $inv['leadspeek_api_id'],
                                "Name" => trim($company_name),
                                "CustomID" => $tryseraCustomID ,
                                "Active" => false
                            ]       
                        ]
                    ]; 
                    $pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                    $result =  json_decode($pauseresponse->getBody());
                 }

                 /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */

                /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */

                /** SEND EMAIL TO ME */
                $details = [
                    'errormsg'  => 'User already not active Leadspeek ID :' . $_leadspeek_api_id . ' companyID: ' . $_company_id . ' Company Parent:' . $_user_id . ' Campaign Name:' . $inv['campaign_name'],
                ];

                $from = [
                    'address' => 'newleads@leadspeek.com',
                    'name' => 'support',
                    'replyto' => 'harrison@uncommonreach.com',
                ];
                
                $this->send_email(array('harrison@uncommonreach.com'),'notactiveuser - User already not Active (INTERNAL) #' . $_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                /** SEND EMAIL TO ME */

            }
        }
    }

    public function checkembededcode(Request $request) {
        date_default_timezone_set('America/Chicago');

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        
        $http = new \GuzzleHttp\Client;
        $appkey = config('services.trysera.api_id');

        $datenow = date('Y-m-d');
        $dayremind = array(3,6,9,12);

        $sitelist = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.url_code','leadspeek_users.user_id','leadspeek_users.leadspeek_api_id','leadspeek_users.campaign_name','leadspeek_users.spreadsheet_id',
                                          'leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','leadspeek_users.lp_max_lead_month','leadspeek_users.paymentterm','leadspeek_users.leadspeek_type','leadspeek_users.gtminstalled','leadspeek_users.company_id as company_parent',
                                          'leadspeek_users.active_user','leadspeek_users.lp_enddate','leadspeek_users.platformfee','leadspeek_users.lp_min_cost_month','leadspeek_users.lp_limit_leads','leadspeek_users.lp_limit_freq',
                                          'leadspeek_users.created_at', 'leadspeek_users.embedded_lastreminder','leadspeek_users.trysera','leadspeek_users.topupoptions','leadspeek_users.leadsbuy','leadspeek_users.stopcontinual','leadspeek_users.cost_perlead',
                                          'companies.company_name','companies.id as company_id',
                                          'users.customer_payment_id','users.customer_card_id','users.email','users.company_root_id')
                                ->join('users','leadspeek_users.user_id','=','users.id')
                                ->join('companies','users.company_id','=','companies.id')
                                ->where('leadspeek_users.embeddedcode_crawl','=','F')
                                ->where('leadspeek_users.active','=','F')
                                ->where('leadspeek_users.disabled','=','T')
                                ->where('leadspeek_users.active_user','=','F')
                                ->where('leadspeek_users.leadspeek_type','=','local')
                                ->where('leadspeek_users.archived','=','F')
                                ->where('users.active','=','T')
                                ->where('users.payment_status','=','')
                                ->get();

        foreach($sitelist as $st) {

            if (trim($st['url_code']) == '') {
                continue;
            }

            /** CHECK IF SSL AND EMBEDDED CODE EXIST */
            $_urlcrawl = str_replace(array('http://','https://'),'',$st['url_code']);
            $urlcrawl = "https://" . $_urlcrawl;
            
            $_leadspeek_api_id = trim($st['leadspeek_api_id']);
            $_lp_user_id = $st['id'];
            $_company_id = $st['company_id'];
            $_user_id = $st['user_id'];

            $activeUser = $st['active_user'];
            $clientLimitLeads = $st['lp_limit_leads'];
            $clientLimitFreq = $st['lp_limit_freq'];
            //$clientEmail = explode(PHP_EOL, trim($st['report_sent_to']));
            $_clientEmail = str_replace(["\r\n", "\r"], "\n", trim($st['report_sent_to']));
            $clientEmail = explode("\n", $_clientEmail);
            $clientAdminNotify = explode(',',trim($st['admin_notify_to']));
            $spreadSheetID = $st['spreadsheet_id'];
            $daydiff = 0;
            $gtminstalled = ($st['gtminstalled'] == 'T')?true:false;
            $_company_parent = $st['company_parent'];
            $tryseramethod = (isset($cl['trysera']) && $cl['trysera'] == "T")?true:false;

            $adminEmail = array();
            $adminNotify = array();
            $clientAdmin = array();
            foreach($clientEmail as $value) {
                array_push($adminEmail,trim($value));
                array_push($clientAdmin,trim($value));
            }

            if (trim($st['admin_notify_to']) != "") {
                $tmp = User::select('email')->whereIn('id', $clientAdminNotify)->get();
                foreach($tmp as $ad) {
                    array_push($adminEmail,trim($ad['email']));
                    array_push($adminNotify,trim($ad['email']));
                }
            }

            $_manualbill = false;
            $agencycompany = Company::select('company_name','domain','subdomain','status_domain','manual_bill')
                                                ->where('id','=',$st['company_parent'])
                                                ->get();
            if(count($agencycompany) > 0) {
                if ($agencycompany[0]['manual_bill'] == 'T') {
                    $_manualbill = true;
                }
                if(isset($agencycompany[0]['domain']) && $agencycompany[0]['domain'] != "") {
                    $dashboardlogin = 'https://' . $agencycompany[0]['domain'] . '/login';
                }else{
                    $dashboardlogin = 'https://' . $agencycompany[0]['subdomain'] . '/login';
                }
            }else{
                $dashboardlogin = 'https://app.exactmatchmarketing.com/login';
            }

            $campaignName = '';
            if (isset($st['campaign_name']) && trim($st['campaign_name']) != '') {
                $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$st['campaign_name']);
            }
            
            $company_name = str_replace($_leadspeek_api_id,'',$st['company_name']) . $campaignName;
            $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');

            $AdminDefault = $this->get_default_admin($st['company_parent']);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'newleads@leadspeek.com';

            if ($this->is_ssl_exists($urlcrawl)) {
                $contents = "";
                try {
                    $contents = $http->get($urlcrawl,['verify' => false ]);
                    $contents = $contents->getBody();
                }catch (Exception $e) {
                    $embeddedupdate = LeadspeekUser::find($st['id']);
                    $embeddedupdate->embedded_status = $_urlcrawl . '<br/>(Needs manual review.)';
                    $embeddedupdate->save();

                    /** SENT EMAIL NOTIFICATION FAILED CRAWL */
                    $EmailReminderEmbedded = $this->check_embeddedcode_emailsent($st,$datenow);

                    if ($EmailReminderEmbedded && $gtminstalled == false) {

                        /** CHECK FOR SITE ID NAME */
                        $_campaignLocalName = 'Site ID';
                        $customsidebarleadmenu = '';
                            /** GET SETTING MENU MODULE */
                            $companysetting = CompanySetting::where('company_id',trim($st['company_parent']))->whereEncrypted('setting_name','customsidebarleadmenu')->get();
                            if (count($companysetting) > 0) {
                                $customsidebarleadmenu = json_decode($companysetting[0]['setting_value']);
                                $_campaignLocalName = $customsidebarleadmenu->local->name;
                            }
                            /** GET SETTING MENU MODULE */
                        /** CHECK FOR SITE ID NAME */

                        $details = [
                            'website' => $_urlcrawl,
                            'dashboardlogin' => $dashboardlogin,
                            'campaignlocalname' => $_campaignLocalName,
                            'campaignname' => $campaignName,
                            'campaignid' => $_leadspeek_api_id,
                        ];
                        $attachement = array();
                        
                        $from = [
                            'address' => $AdminDefaultEmail,
                            'name' => 'support',
                            'replyto' => $AdminDefaultEmail,
                        ];
                        
                        if (count($adminNotify) > 0 && $adminNotify[0] != "") { 
                            $this->send_email($adminNotify,'Possible Issue With Your Campaign - ' . $company_name . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseracrawlfailed',$from,$_company_parent);
                            $this->send_email(array('harrison@uncommonreach.com'),'Possible Issue With Your Campaign - ' . $company_name . ' (INTERNAL) #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseracrawlfailed',$from,$_company_parent);
                        }
                    }
                    /** SENT EMAIL NOTIFICATION FAILED CRAWL */
                }
                //if ((str_contains($contents,'https://tag.leadspeek.com/i/14798651632618831906/s/') || str_contains($contents,'https://oi.0o0o.io/i/14798651632618831906/s/')) && $contents != "") {
                if ((str_contains($contents,'i/14798651632618831906/s/' . trim($st['leadspeek_api_id'])) || str_contains($contents,'s: ' . trim($st['leadspeek_api_id'])) || str_contains($contents,trim($st['leadspeek_api_id']) . '|')) && $contents != "") {
                    
                    /** RUN THE CAMPAIGN */

                            /** ENABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                            if($tryseramethod) {
                                $pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $st['leadspeek_api_id'];
                                $pauseoptions = [
                                    'headers' => [
                                        'Authorization' => 'Bearer ' . $appkey,
                                    ],
                                    'json' => [
                                        "SubClient" => [
                                            "ID" => $st['leadspeek_api_id'],
                                            "Name" => trim($company_name),
                                            "CustomID" => $tryseraCustomID ,
                                            "Active" => true
                                        ]       
                                    ]
                                ]; 
                                $pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                            }
                            //$result =  json_decode($pauseresponse->getBody());

                            $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                            // $updateLeadspeekUser->active = 'T';
                            // $updateLeadspeekUser->disabled = 'F';
                            // $updateLeadspeekUser->active_user = 'T';
                            $updateLeadspeekUser->embeddedcode_crawl = 'T';
                            // $updateLeadspeekUser->embedded_status = 'Campaign is running.';
                            // $updateLeadspeekUser->last_lead_start = date('Y-m-d H:i:s');
                            //$updateLeadspeekUser->save();
                            /** ENABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */

                            /** IF THIS FIRST TIME ACTIVE THEN WILL CHARGE CLIENT FOR ONE TIME CREATIVE AND FIRST PLATFORM FEE */
                            if (false) {
                            //if($activeUser != '' && $activeUser == 'F') {
                                /** CHARGE ONE TIME CREATIVE / SET UP FEE WHEN STATUS ACTIVATED (ONE TIME) */
                                    //date_default_timezone_set('America/Chicago');
                                    $updateLeadspeekUser->lp_limit_startdate = date('Y-m-d H:i:s');
                                
                                if(((isset($st['customer_payment_id']) && $st['customer_payment_id'] != '' && $st['customer_card_id'] != '') || $_manualbill) && ($st['platformfee'] > 0 || $st['lp_min_cost_month'] > 0 || $st['paymentterm'] == 'One Time')) {
                                    if ($st['paymentterm'] == 'Prepaid') {
                        
                                        /** CHECK REMINING BALANCE TOP UP */
                                        $remainingBalanceTotal = Topup::where('leadspeek_api_id','=',$_leadspeek_api_id)
                                                                      ->sum('balance_leads');
                
                                        $campaign = LeadspeekUser::where('leadspeek_api_id','=',$_leadspeek_api_id)->first();
                
                                        /** CHECK IF TOP UP EVER BEEN CREATE */
                                        $dataContinualNotCreated = Topup::where('leadspeek_api_id', $_leadspeek_api_id)
                                                                         ->where('topupoptions', 'continual')
                                                                         ->whereIn('topup_status', ['progress', 'queue'])
                                                                         ->exists();
                
                                        if(($st['topupoptions'] === 'continual' && ($remainingBalanceTotal < $campaign->lp_limit_leads || !$dataContinualNotCreated)) || ($st['topupoptions'] === 'onetime')) {
                                            /** FOR TOP UP PREPAID */
                                            $topup_status = 'progress';
                                            //check if campaign has any topup
                                            $runningTopup = Topup::where('leadspeek_api_id','=',$_leadspeek_api_id)
                                                                 ->where('topup_status', '=', 'progress')
                                                                 ->get(); 
                    
                                            if (count($runningTopup) > 0 ) {
                                                $topup_status = 'queue';
                                            }
                                            

                                            $data['user_id'] = $st['user_id'] ?? '';
                                            $data['lp_user_id'] = $st['id'] ?? 0;
                                            $data['company_id'] = $st['company_parent'] ?? 0;
                                            $data['leadspeek_api_id'] = $_leadspeek_api_id ?? '';
                                            $data['leadspeek_type'] = $st['leadspeek_type'] ?? '';
                                            $data['topupoptions'] = $st['topupoptions'] ?? '';
                                            $data['platformfee'] = $st['platformfee'] ?? 0;
                                            $data['cost_perlead'] = $st['cost_perlead'] ?? 0;
                                            $data['lp_limit_leads'] = $st['lp_limit_leads'] ?? 0;
                                            $data['lp_min_cost_month'] = $st['lp_min_cost_month'] ?? 0;
                                            $data['total_leads'] = $st['leadsbuy'] ?? 0;
                                            $data['balance_leads'] = $st['leadsbuy'] ?? 0;
                                            $data['treshold'] = $st['lp_limit_leads'] ?? 0;
                                            $data['payment_amount'] = '0';
                                            
                                            $data['active'] = 'T';
                                            $data['stop_continue'] = $st['stopcontinual'] ?? 'F';
                    
                                            $data['last_cost_perlead'] = '0';
                                            $data['last_limit_leads_day'] = '0';
                                            $data['topup_status'] = $topup_status ?? '';
                                            $data['platform_price'] = '0';
                                            $data['root_price'] = '0';
                
                                            /* GET COST AGENCY PRICE */
                                            $settingnameAgency = 'costagency';
                                            $companyParent = $st['company_parent'];
                                                $getcompanysetting = CompanySetting::select('setting_value')
                                                    ->where('company_id', $companyParent)
                                                    ->whereEncrypted('setting_name', $settingnameAgency)
                                                    ->get();
                
                                                $companysetting = "";
                
                                                if (count($getcompanysetting) > 0) {
                                                    $companysetting = json_decode($getcompanysetting[0]['setting_value']);
                                                    if ($st['leadspeek_type'] == 'local') {
                                                        $data['platform_price'] = $companysetting->local->Prepaid->LeadspeekCostperlead;
                                                    }else if ($st['leadspeek_type'] == 'locator') {
                                                        $data['platform_price'] = $companysetting->locator->Prepaid->LocatorCostperlead;
                                                    }
                                                }
                                            /* GET COST AGENCY PRICE */
                                            
                                            /** GET ROOT FEE PER LEADS FROM SUPER ROOT */
                                            $masterRootFee = $this->getcompanysetting($st['company_root_id'],'rootfee');
                                            if ($masterRootFee != '') {
                                                if ($st['leadspeek_type'] == 'local') {
                                                    $data['root_price'] = (isset($masterRootFee->feesiteid))?$masterRootFee->feesiteid:0;
                                                }else if ($st['leadspeek_type'] == 'locator') {
                                                    $data['root_price'] = (isset($masterRootFee->feesearchid))?$masterRootFee->feesearchid:0;
                                                }else if ($st['leadspeek_type'] == 'enhance') {
                                                    $data['root_price'] = (isset($masterRootFee->feeenhance))?$masterRootFee->feeenhance:0;
                                                }
                                            }
                                            /** GET ROOT FEE PER LEADS FROM SUPER ROOT */
                
                                            $addTopUp = Topup::create($data);
                                            /** FOR TOP UP PREPAID */
                
                                            /** CHARGE CLIENT FIRST TIME PLAY */
                                            $totalFirstCharge = ($data['cost_perlead'] * $data['total_leads']) + $data['platformfee'];
                                            $_platformFee = ($data['platform_price'] * $data['total_leads']);
                                            return $this->chargeClient($st['customer_payment_id'],$st['customer_card_id'],$st['email'],$totalFirstCharge,$st['platformfee'],$_platformFee,$st,$data);
                                            /** CHARGE CLIENT FIRST TIME PLAY */
                                        } 
                
                                    }else{
                                        /** PUT FORMULA FOR PLATFORM FEE CALCULATION */
                                        $totalFirstCharge = $st['platformfee'] + $st['lp_min_cost_month'];
                                        $this->chargeClient($st['customer_payment_id'],$st['customer_card_id'],$st['email'],$totalFirstCharge,$st['platformfee'],$st['lp_min_cost_month'],$st);
                                    }
                                }
                            
                            /** CHARGE ONE TIME CREATIVE / SET UP FEE WHEN STATUS ACTIVATED (ONE TIME) */
                            
                        }

                        $updateLeadspeekUser->save();
                        /** IF THIS FIRST TIME ACTIVE THEN WILL CHARGE CLIENT FOR ONE TIME CREATIVE AND FIRST PLATFORM FEE */
                    
                    /** RUN THE CAMPAIGN */

                    /** SENT NOTIFICATION TO CLIENT AND ADMIN */
                    
                    //$AdminDefault = $this->get_default_admin($st['company_parent']);
                    //$AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'newleads@leadspeek.com';
                    
                    $details = [
                        'companyname'  => $st['company_name'],
                        'campaignanme' => str_replace($_leadspeek_api_id,'',$st['campaign_name']),
                        'website' => $_urlcrawl,
                        'dashboardlogin' => $dashboardlogin,
                        'googlesheetlink' => 'https://docs.google.com/spreadsheets/d/' . $spreadSheetID . '/edit?usp=sharing',
                        'defaultadmin' => $AdminDefaultEmail,
                    ];
                    $attachement = array();
                    
                    $from = [
                        'address' => $AdminDefaultEmail,
                        'name' => 'support',
                        'replyto' => $AdminDefaultEmail,
                    ];

                    if (count($adminEmail) > 0 && $adminEmail[0] != "") { 
                        $this->send_email($adminEmail,'Notification for ' . $company_name . ' #' . $_leadspeek_api_id,$details,$attachement,'emails.tryseracrawlsuccess',$from,$_company_parent);
                        $this->send_email(array('harrison@uncommonreach.com'),'Notification for ' . $company_name . ' (INTERNAL) #' . $_leadspeek_api_id,$details,$attachement,'emails.tryseracrawlsuccess',$from,$_company_parent);
                    }

                    /** SENT NOTIFICATION TO CLIENT AND ADMIN */

                }else{
                    $embeddedupdate = LeadspeekUser::find($st['id']);
                    $embeddedupdate->embedded_status = $_urlcrawl . '<br/>(Waiting for the embedded code to be placed)';
                    $embeddedupdate->save();

                    /** RUN EMAIL REMINDER TO CHECK*/
                    $EmailReminderEmbedded = $this->check_embeddedcode_emailsent($st,$datenow);

                    if ($EmailReminderEmbedded && $gtminstalled == false) {
                        $details = [
                            'website' => $_urlcrawl,
                            'dashboardlogin' => $dashboardlogin,
                        ];
                        $attachement = array();
                        
                        $from = [
                            'address' => $AdminDefaultEmail,
                            'name' => 'support',
                            'replyto' => $AdminDefaultEmail,
                        ];
                        
                        if (count($adminEmail) > 0 && $adminEmail[0] != "") { 
                            $this->send_email($adminEmail,'Waiting for the embedded code to be placed - ' . $company_name . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseraembeddedreminder',$from,$_company_parent);
                            $this->send_email(array('harrison@uncommonreach.com'),'Waiting for the embedded code to be placed - ' . $company_name . ' (INTERNAL) #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseraembeddedreminder',$from,$_company_parent);
                        }
                    }
                    /** RUN EMAIL REMINDER TO CHECK */
                }
            }else{
                $embeddedupdate = LeadspeekUser::find($st['id']);
                $embeddedupdate->embedded_status = $_urlcrawl . '<br/>(Make sure the domain begins with https:// or domain can not be found)';
                $embeddedupdate->save();

                $EmailReminderEmbedded = $this->check_embeddedcode_emailsent($st,$datenow);

                if ($EmailReminderEmbedded && $gtminstalled == false) {
                    $details = [
                        'website' => $_urlcrawl,
                        'dashboardlogin' => $dashboardlogin,
                    ];
                    $attachement = array();
                    
                    $from = [
                        'address' => $AdminDefaultEmail,
                        'name' => 'support',
                        'replyto' => $AdminDefaultEmail,
                    ];
                    
                    if (count($adminEmail) > 0 && $adminEmail[0] != "") { 
                        $this->send_email($adminEmail,'Waiting for the embedded code to be placed - ' . $company_name . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseraembeddedreminder',$from,$_company_parent);
                        $this->send_email(array('harrison@uncommonreach.com'),'Waiting for the embedded code to be placed - ' . $company_name . ' (INTERNAL) #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseraembeddedreminder',$from,$_company_parent);
                    }
                }

            }
            /** CHECK IF SSL AND EMBEDDED CODE EXIST */
        }
    }

    private function check_embeddedcode_emailsent($st,$datenow) {

        $EmailReminderEmbedded = false;

        if ($st['embedded_lastreminder'] == null || $st['embedded_lastreminder'] == '0000-00-00') {
            $datelast = date('Y-m-d',strtotime($st['created_at']));
        }else{
            $datelast = date('Y-m-d',strtotime($st['embedded_lastreminder']));
        }

        $datecreated =  date('Y-m-d',strtotime($st['created_at']));
        $daydiff = $this->dateDiffInDays($datenow,$datecreated);

        if ($daydiff <= 12) {
            if (date('Ymd',strtotime($datelast)) < date('Ymd',strtotime($datenow))) {
                $nexdays = date('Y-m-d',strtotime($datenow . ' + 3 days'));
                /** UPDATE THE FIRST REMINDER */
                $embeddedreminder = LeadspeekUser::find($st['id']);
                $embeddedreminder->embedded_lastreminder = $nexdays;
                $embeddedreminder->save();
                /** UPDATE THE FIRST REMINDER */
                $EmailReminderEmbedded = true;
            }else{
                $EmailReminderEmbedded = false;
            }
        }else{
            $EmailReminderEmbedded = false;
            $embeddedreminder = LeadspeekUser::find($st['id']);
            $embeddedreminder->embeddedcode_crawl = 'T';
            $embeddedreminder->save();
        }

        return $EmailReminderEmbedded;
    }

    private function check_embeddedcode_exist($urlcode,$_leadspeek_api_id = '') {
        $_urlcrawl = str_replace(array('http://','https://'),'',$urlcode);
        $urlcrawl = "https://" . $_urlcrawl;

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );
        
        $http = new \GuzzleHttp\Client;
        $appkey = config('services.trysera.api_id');
        $status = "";
        $contents = "";

        if ($this->is_ssl_exists($urlcrawl)) {
            try {
                $contents = $http->get($urlcrawl,['verify' => false ]);
                $contents = $contents->getBody();
            }catch (Exception $e) {
                $status = $_urlcrawl . ' Blocked from crawling (Needs manual review.)';
            }

        }else{
            $status = $_urlcrawl . ' (Make sure the domain begins with https:// or domain can not be found)';
        }

        if (!str_contains($contents,'i/14798651632618831906/s/' . trim($_leadspeek_api_id)) && !str_contains($contents,'s: ' . trim($_leadspeek_api_id))) {
            $status = "Embedded Code was missing on " . $_urlcrawl;
        }

        return $status;
    }

    private function is_ssl_exists($url)
    {
        /*try {
            $orignal_parse = parse_url($url, PHP_URL_HOST);
            $get = stream_context_create(array("ssl" => array("verify_peer" => false,"verify_peer_name" => false,"capture_peer_cert" => TRUE)));
            $read = stream_socket_client("ssl://" . $orignal_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
            $cert = stream_context_get_params($read);
            $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

            if (isset($certinfo) && !empty($certinfo)) {
                if (
                    isset($certinfo['name']) && !empty($certinfo['name']) &&
                    isset($certinfo['issuer']) && !empty($certinfo['issuer'])
                ) {
                    return true;
                }
                return false;
            }
            return false;
        }catch (Exception $e) {
            return false;
        }*/
        
        $ch=curl_init(); 
        $timeout=5; 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); 
        $contents = curl_exec($ch); 
        // close handle to release resources 
        curl_close($ch);

        if ($contents != '') {
            return true;
        }else{
            return false;
        }
    }

    private function chargeClient($custStripeID,$custStripeCardID,$custEmail,$totalAmount,$oneTime,$platformFee,$usrInfo,$topup=array()) {
        $_lp_user_id = $usrInfo['id'];
        $invoiceNum = date('Ymd') . '-' . $_lp_user_id;
        $AgencyManualBill = "F";
        
        /** GET COMPANY PARENT NAME / AGENCY */
        $getParentInfo = Company::select('company_name','manual_bill')->where('id','=',$usrInfo['company_parent'])->get();
        if(count($getParentInfo) > 0) {
            $companyParentName = $getParentInfo[0]['company_name'];
            $AgencyManualBill = $getParentInfo[0]['manual_bill'];
        }
        /** GET COMPANY PARENT NAME / AGENCY */

        /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */
        $accConID = '';
        if ($usrInfo['company_parent'] != '') {
            $accConID = $this->check_connected_account($usrInfo['company_parent'],$usrInfo['company_root_id']);
        }
        /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */

        /** CHECK IF USER DATA STILL ON PLATFORM */
        $validuser = true;
        $user[0]['customer_payment_id'] = (isset($usrInfo['customer_payment_id']))?$usrInfo['customer_payment_id']:'';
        $user[0]['company_id'] = (isset($usrInfo['company_id']))?$usrInfo['company_id']:'';
        $user[0]['id'] = (isset($usrInfo['user_id']))?$usrInfo['user_id']:'';
        $user[0]['company_root_id'] = (isset($usrInfo['company_root_id']))?$usrInfo['company_root_id']:'';

        $chkStripeUser = $this->check_stripe_customer_platform_exist($user,$accConID);
        $chkResultUser = json_decode($chkStripeUser);
        if ($chkResultUser->result == 'success') {
            $validuser = true;
            $custStripeID = $chkResultUser->custStripeID;
            $custStripeCardID = $chkResultUser->CardID;
        }else{
            $validuser = false;
        }
        /** CHECK IF USER DATA STILL ON PLATFORM */

        /** CHECK IF AGENCY MANUAL BILL */
        if ($AgencyManualBill == "T") {
            $validuser = true;
            $custStripeID = "agencyDirectPayment";
            $custStripeCardID = "agencyDirectPayment";
            /** GET STRIPE ACC AND CARD AGENCY */
            $custAgencyStripeID = "";
            $custAgencyStripeCardID = "";

            $chkAgency = User::select('id','customer_payment_id','customer_card_id','email')
                    ->where('company_id','=',$usrInfo['company_parent'])
                    ->where('company_parent','<>',$usrInfo['company_parent'])
                    ->where('user_type','=','userdownline')
                    ->where('isAdmin','=','T')
                    ->where('active','=','T')
                    ->get();
            if(count($chkAgency) > 0) {
                $custAgencyStripeID = $chkAgency[0]['customer_payment_id'];
                $custAgencyStripeCardID = $chkAgency[0]['customer_card_id'];
            }
            /** GET STRIPE ACC AND CARD AGENCY */
        }
        /** CHECK IF AGENCY MANUAL BILL */
        $paymentintentID = '';
        $platform_errorstripe = '';
        $errorstripe = '';
        $platform_paymentintentID = '';
        $sr_id = 0;
        $ae_id = 0;
        $ar_id = 0;
        $sales_fee = 0;
        $platformfee_charge = false;

        $platform_LeadspeekCostperlead = 0;
        $platform_LeadspeekMinCostMonth = 0;
        $platform_LeadspeekPlatformFee = 0;
        $platformfee = 0;
        $platformfee_ori = 0;
        $statusPayment = 'pending';
        $_ongoingleads = "";
        
        /** CHARGE WITH STRIPE */
        if(trim($custStripeID) != '' && trim($custStripeCardID) != '' && ($totalAmount > 0 || $totalAmount != '' || $usrInfo['paymentterm'] == 'One Time' || $usrInfo['paymentterm'] == 'Prepaid') && $validuser) { 
            date_default_timezone_set('America/Chicago');

            $totalAmount = number_format($totalAmount,2,'.','');

            /** GET STRIPE KEY */
            $stripeseckey = config('services.stripe.secret');
            $stripepublish = $this->getcompanysetting($usrInfo['company_root_id'],'rootstripe');
            if ($stripepublish != '') {
                $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
            }
            /** GET STRIPE KEY */

            $stripe = new StripeClient([
                'api_key' => $stripeseckey,
                'stripe_version' => '2020-08-27'
            ]);

            /** GET PLATFORM MARGIN */
            $platformMargin = $this->getcompanysetting($usrInfo['company_parent'],'costagency');
            
            $paymentterm = trim($usrInfo['paymentterm']);
            $paymentterm = str_replace(' ','',$paymentterm);
            if ($platformMargin != '') {
                // $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');

                if ($usrInfo['leadspeek_type'] == "local") {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:0;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:0;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:0;
                    
                    // $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:$rootcostagency->local->$paymentterm->LeadspeekCostperlead;
                    // $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:$rootcostagency->local->$paymentterm->LeadspeekMinCostMonth;
                    // $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:$rootcostagency->local->$paymentterm->LeadspeekPlatformFee;
                }else if ($usrInfo['leadspeek_type'] == "locator") {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LocatorCostperlead))?$platformMargin->locator->$paymentterm->LocatorCostperlead:0;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LocatorMinCostMonth))?$platformMargin->locator->$paymentterm->LocatorMinCostMonth:0;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LocatorPlatformFee))?$platformMargin->locator->$paymentterm->LocatorPlatformFee:0;

                    // $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LeadspeekCostperlead))?$platformMargin->locator->$paymentterm->LeadspeekCostperlead:$rootcostagency->locator->$paymentterm->LeadspeekCostperlead;
                    // $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->locator->$paymentterm->LeadspeekMinCostMonth:$rootcostagency->locator->$paymentterm->LeadspeekMinCostMonth;
                    // $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LeadspeekPlatformFee))?$platformMargin->locator->$paymentterm->LeadspeekPlatformFee:$rootcostagency->locator->$paymentterm->LeadspeekPlatformFee;
                }else if($usrInfo['leadspeek_type'] == "enhance") {
                    $rootcostagency = []; 
                    if(!isset($platformMargin->enhance)) {
                        $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');
                    }
                    $platform_LeadspeekCostperlead = (isset($platformMargin->enhance->$paymentterm->EnhanceCostperlead))?$platformMargin->enhance->$paymentterm->EnhanceCostperlead:$rootcostagency->enhance->$paymentterm->EnhanceCostperlead;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->enhance->$paymentterm->EnhanceMinCostMonth))?$platformMargin->enhance->$paymentterm->EnhanceMinCostMonth:$rootcostagency->enhance->$paymentterm->EnhanceMinCostMonth;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->enhance->$paymentterm->EnhancePlatformFee))?$platformMargin->enhance->$paymentterm->EnhancePlatformFee:$rootcostagency->enhance->$paymentterm->EnhancePlatformFee;
                }
            }
            /** GET PLATFORM MARGIN */

            if ($usrInfo['paymentterm'] == 'One Time') {
                $costinclude = ($usrInfo['lp_max_lead_month'] * $platform_LeadspeekCostperlead);
                $platformfee = (($platform_LeadspeekPlatformFee + $platform_LeadspeekMinCostMonth) - $costinclude);
                if ($platformfee < 0) {
                    $platformfee = $platformfee * -1;
                }
            }else if ($usrInfo['paymentterm'] == 'Prepaid') {
                $platformfee = $platformFee + $platform_LeadspeekPlatformFee;
                $_ongoingleads = (isset($topup['total_leads']))?$topup['total_leads']:'';
            }else{
                $platformfee = ($platform_LeadspeekPlatformFee + $platform_LeadspeekMinCostMonth);
            }
            $platformfee = number_format($platformfee,2,'.','');

            $platformfee_ori = $platformfee;

            $defaultInvoice = '#' . $invoiceNum . '-' . str_replace($usrInfo['leadspeek_api_id'],'',$usrInfo['company_name']) . ' #' . $usrInfo['leadspeek_api_id'];

            /** CHECK IF TOTAL AMOUNT IS SMALLER THAN PLATFORM FEE */
            //if (($totalAmount < $platformfee) &&  $platformfee > 0) {
            // if ($platformfee >= 0.5) {
            //     $agencystripe = $this->check_agency_stripeinfo($usrInfo['company_parent'],$platformfee,$usrInfo['leadspeek_api_id'],'Agency ' . $defaultInvoice);
            //     $agencystriperesult = json_decode($agencystripe);
            //     $platformfee_charge = true;

            //     if ($agencystriperesult->result == 'success') {
            //         $platform_paymentintentID = $agencystriperesult->payment_intentID;
            //         $sr_id = $agencystriperesult->srID;
            //         $ae_id = $agencystriperesult->aeID;
            //         $sales_fee = $agencystriperesult->salesfee;
            //         $platformfee = 0;
            //         $platform_errorstripe = '';
            //     }else{
            //         $platform_paymentintentID = $agencystriperesult->payment_intentID;
            //         $platform_errorstripe .= $agencystriperesult->error;
            //     }
            // }
            /** CHECK IF TOTAL AMOUNT IS SMALLER THAN PLATFORM FEE */
                
            /** CREATE ONE TIME PAYMENT USING PAYMENT INTENT */
            if ($totalAmount < 0.5 || $totalAmount <= 0) {
                $paymentintentID = '';
                $statusPayment = 'paid';
                $platformfee_charge = false;
            }else{
                if ($AgencyManualBill == 'F') { 
                    try {
                        $chargeAmount = $totalAmount * 100;
                        if ($usrInfo['paymentterm'] == 'One Time') {

                            $payment_intent =  $stripe->paymentIntents->create([
                                'payment_method_types' => ['card'],
                                'customer' => trim($custStripeID),
                                'amount' => $chargeAmount,
                                'currency' => 'usd',
                                'receipt_email' => $custEmail,
                                'payment_method' => $custStripeCardID,
                                'confirm' => true,
                                'description' => $defaultInvoice,
                            ],['stripe_account' => $accConID]);

                        }else{

                            $payment_intent =  $stripe->paymentIntents->create([
                                'payment_method_types' => ['card'],
                                'customer' => trim($custStripeID),
                                'amount' => $chargeAmount,
                                'currency' => 'usd',
                                'receipt_email' => $custEmail,
                                'payment_method' => $custStripeCardID,
                                'confirm' => true,
                                'description' => $defaultInvoice,
                                'application_fee_amount' => ($platformfee * 100),
                            ],['stripe_account' => $accConID]);
                        }

                        $paymentintentID = $payment_intent->id;
                        $statusPayment = 'paid';
                        $errorstripe = '';
                        $platformfee_charge = true;
                        
                        if ($usrInfo['paymentterm'] == 'One Time') {
                            $agencystripe = $this->check_agency_stripeinfo($usrInfo['company_parent'],$platformfee,$usrInfo['leadspeek_api_id'],'Agency ' . $defaultInvoice,date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59'));
                            $agencystriperesult = json_decode($agencystripe);

                            if ($agencystriperesult->result == 'success') {
                                $platform_paymentintentID = $agencystriperesult->payment_intentID;
                                $sr_id = $agencystriperesult->srID;
                                $ae_id = $agencystriperesult->aeID;
                                $ar_id = $agencystriperesult->arID;
                                $sales_fee = $agencystriperesult->salesfee;
                                $platformfee = 0;
                                $platform_errorstripe = '';
                            }else{
                                $platform_paymentintentID = $agencystriperesult->payment_intentID;
                                $platform_errorstripe .= $agencystriperesult->error;
                            }
                        }else{
                            /** TRANSFER SALES COMMISSION IF ANY */
                            $salesfee = $this->transfer_commission_sales($usrInfo['company_parent'],$platformfee,$usrInfo['leadspeek_api_id'],date('Y-m-d'),date('Y-m-d'),$stripeseckey,$_ongoingleads);
                            $salesfeeresult = json_decode($salesfee);
                            $platform_paymentintentID = $salesfeeresult->payment_intentID;
                            $sr_id = $salesfeeresult->srID;
                            $ae_id = $salesfeeresult->aeID;
                            $ar_id = $salesfeeresult->arID;
                            $sales_fee = $salesfeeresult->salesfee;
                            /** TRANSFER SALES COMMISSION IF ANY */
                        }
                    }catch (RateLimitException $e) {
                        $statusPayment = 'failed';
                        $platformfee_charge = false;
                        // Too many requests made to the API too quickly
                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                    } catch (InvalidRequestException $e) {
                        $statusPayment = 'failed';
                        $platformfee_charge = false;
                        // Invalid parameters were supplied to Stripe's API
                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                    } catch (AuthenticationException $e) {
                        $statusPayment = 'failed';
                        $platformfee_charge = false;
                        // Authentication with Stripe's API failed
                        // (maybe you changed API keys recently)
                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                    } catch (ApiConnectionException $e) {
                        $statusPayment = 'failed';
                        $platformfee_charge = false;
                        // Network communication with Stripe failed
                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                    } catch (ApiErrorException $e) {
                        $statusPayment = 'failed';
                        $platformfee_charge = false;
                        // Display a very generic error to the user, and maybe send
                        // yourself an email
                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                    } catch (Exception $e) {
                        $statusPayment = 'failed';
                        $platformfee_charge = false;
                        // Something else happened, completely unrelated to Stripe
                        $errorstripe = 'error not stripe things';
                    }
                }else{
                    $statusPayment = 'paid';
                    $platformfee_charge = false;
                    $errorstripe = "This direct Agency Bill Method";
                }
            }
            
            $cardinfo = "";
            $cardlast = "";

            if ($AgencyManualBill == "T") {
                $custStripeID = $custAgencyStripeID;
                $custStripeCardID = $custAgencyStripeCardID;

                $cardinfo = $stripe->customers->retrieveSource(trim($custStripeID),trim($custStripeCardID),[]);
                $cardlast = $cardinfo->last4;
            }else {
                $cardinfo = $stripe->customers->retrieveSource(trim($custStripeID),trim($custStripeCardID),[],['stripe_account' => $accConID]);
                $cardlast = $cardinfo->last4;
            }

            /** CHECK IF FAILED CHARGE CLIENT WE STILL CHARGE THE AGENCY */
            //if ($statusPayment == 'failed' && $platformfee_charge == false && $platformfee >= 0.5) {
            if ($platformfee_charge == false && $platformfee >= 0.5) {
                $agencystripe = $this->check_agency_stripeinfo($usrInfo['company_parent'],$platformfee,$usrInfo['leadspeek_api_id'],'Agency ' . $defaultInvoice,date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59'),$_ongoingleads);
                $agencystriperesult = json_decode($agencystripe);

                if (isset($agencystriperesult->result) && $agencystriperesult->result == 'success') {
                    $platform_paymentintentID = $agencystriperesult->payment_intentID;
                    $sr_id = $agencystriperesult->srID;
                    $ae_id = $agencystriperesult->aeID;
                    $ar_id = $agencystriperesult->arID;
                    $sales_fee = $agencystriperesult->salesfee;
                    $platformfee = 0;
                    $platform_errorstripe = '';
                }else{
                    $platform_paymentintentID = $agencystriperesult->payment_intentID;
                    $platform_errorstripe .= $agencystriperesult->error;
                }
            }
            /** CHECK IF FAILED CHARGE CLIENT WE STILL CHARGE THE AGENCY */
            
            $statusClientPayment = $statusPayment;

            $_company_id = $usrInfo['company_id'];
            $_user_id = $usrInfo['user_id'];
            $_leadspeek_api_id = $usrInfo['leadspeek_api_id'];
            $clientPaymentTerm = $usrInfo['paymentterm'];
            //$minCostLeads = $usrInfo[0]->lp_min_cost_month;
            $minCostLeads = number_format($platformFee,2,'.','');
            //$reportSentTo = explode(PHP_EOL, $usrInfo['report_sent_to']);
            $_reportSentTo = str_replace(["\r\n", "\r"], "\n", trim($usrInfo['report_sent_to']));
            $reportSentTo = explode("\n", $_reportSentTo);
            $todayDate = date('Y-m-d H:i:s');
            $clientMaxperTerm = $usrInfo['lp_max_lead_month'];

            $oneTime = number_format($oneTime,2,'.','');

            if (trim($sr_id) == "") {
                $sr_id = 0;
            }
            if (trim($ae_id) == "") {
                $ae_id = 0;
            }
            if (trim($ar_id) == "") {
                $ar_id = 0;
            }
            
            /** CREATE INVOICE FOR THIS */
            $invoiceCreated = LeadspeekInvoice::create([
                'company_id' => $_company_id,
                'user_id' => $_user_id,
                'leadspeek_api_id' => $_leadspeek_api_id,
                'invoice_number' => '',
                'payment_term' => $clientPaymentTerm,
                'onetimefee' => $oneTime,
                'platform_onetimefee' => $platform_LeadspeekPlatformFee,
                'min_leads' => $clientMaxperTerm,
                'exceed_leads' => '0',
                'total_leads' => '0',
                'min_cost' => $minCostLeads,
                'platform_min_cost' => $platform_LeadspeekMinCostMonth,
                'cost_leads' => '0',
                'total_amount' => $totalAmount,
                'platform_total_amount' => $platformfee_ori,
                'status' => $statusPayment,
                'customer_payment_id' => $paymentintentID,
                'platform_customer_payment_id' => $platform_paymentintentID,
                'error_payment' => $errorstripe,
                'platform_error_payment' => $platform_errorstripe,
                'invoice_date' => date('Y-m-d'),
                'invoice_start' => date('Y-m-d'),
                'invoice_end' => date('Y-m-d'),
                'sent_to' => json_encode($reportSentTo),
                'sr_id' => $sr_id,
                'sr_fee' => $sales_fee,
                'ae_id' => $ae_id,
                'ae_fee' => $sales_fee,
                'ar_id' => $ar_id,
                'ar_fee' => $sales_fee,
                'active' => 'T',
            ]);
            $invoiceID = $invoiceCreated->id;
    
            $invoice = LeadspeekInvoice::find($invoiceID);
            $invoice->invoice_number = $invoiceNum . '-' . $invoiceID;
            $invoice->save();
    
            $lpupdate = LeadspeekUser::find($_lp_user_id);
            $lpupdate->ongoing_leads = 0;
            $lpupdate->start_billing_date = $todayDate;
            $lpupdate->lifetime_cost = ($lpupdate->lifetime_cost + $totalAmount);
            $lpupdate->save();
            
             /** CREATE INVOICE FOR THIS */

            $adminnotify = explode(',',$usrInfo['admin_notify_to']);

            $campaignName = '';
            if (isset($usrInfo['campaign_name']) && trim($usrInfo['campaign_name']) != '') {
                $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$usrInfo['campaign_name']);
            }
            
            $companyName = str_replace($_leadspeek_api_id,'',$usrInfo['company_name']) . $campaignName;

            /** FIND ADMIN EMAIL */
            $tmp = User::select('email')->whereIn('id', $adminnotify)->get();
            $adminEmail = array();
            foreach($tmp as $ad) {
                array_push($adminEmail,$ad['email']);
            }
            array_push($adminEmail,'harrison@uncommonreach.com');

            if ($statusPayment == 'paid') {
                $statusPayment = "Customer's Credit Card Successfully Charged ";
            }else if ($statusPayment == 'failed') {
                $statusPayment = "Customer's Credit Card Failed";
            }else{
                $statusPayment = "Customer's Credit Card Successfully Charged ";
            }
    
            if ($totalAmount == '0.00' || $totalAmount == '0') {
                $statusPayment = "Customer's Credit Card Successfully Charged";
            }

            if ($platformfee_ori != '0.00' || $platformfee_ori != '0') {
                if ($statusClientPayment == 'failed') {
                    $statusPayment .= " and Agency's Card Charged For Overage";
                }
            }
            
            if ($AgencyManualBill == "T") {
                $statusPayment = "You must directly bill your client the amount due.";
            }
            /** FIND ADMIN EMAIL */

            $AdminDefault = $this->get_default_admin($usrInfo['company_root_id']);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';
            $rootCompanyInfo = $this->getCompanyRootInfo($usrInfo['company_root_id']);
            $defaultdomain = $this->getDefaultDomainEmail($usrInfo['company_root_id']);

            $details = [
                'paymentterm' => $clientPaymentTerm,
                'name'  => $companyName,
                'cardlast' => trim($cardlast),
                'leadspeekapiid' =>$_leadspeek_api_id,
                'invoiceNumber' => $invoiceNum . '-' . $invoiceID,
                'invoiceStatus' => $statusPayment,
                'invoiceDate' => date('m-d-Y'),
                'onetimefee' => $oneTime,
                'cost_perlead' => (isset($topup['cost_perlead']))?$topup['cost_perlead']:'0',
                'total_leads' => (isset($topup['total_leads']))?$topup['total_leads']:'0',
                'platform_onetimefee' => $platform_LeadspeekPlatformFee,
                'platform_cost_perlead' => (isset($topup['platform_price']))?$topup['platform_price']:'0',
                'min_cost' => $minCostLeads,
                'platform_min_cost' => $platform_LeadspeekMinCostMonth,
                'min_leads'=> $clientMaxperTerm,
                'total_amount' => $totalAmount,  
                'platform_total_amount' => $platformfee_ori,
                'startBillingDate' => date('m-d-Y'),
                'endBillingDate' => date('m-d-Y'),
                'invoicetype' => 'agency',
                'agencyname' => $rootCompanyInfo['company_name'],
                'defaultadmin' => $AdminDefaultEmail,
            ];
            $attachement = array();

            $from = [
                'address' => 'noreply@' . $defaultdomain,
                'name' => 'Invoice',
                'replyto' => 'support@' . $defaultdomain,
            ];

            $subjectFailed = "";
            if ($statusClientPayment == 'failed') {
                $subjectFailed = "Failed Payment - ";
            }

            $tmp = $this->send_email($adminEmail,$subjectFailed . 'Invoice for ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',$details,$attachement,'emails.tryseramatchlistcharge',$from,$usrInfo['company_parent']);

            /** SENT FOR CLIENT INVOICE */
                $adminEmail = array();
                foreach($reportSentTo as $ad) {
                    array_push($adminEmail,trim($ad));
                }
                //array_push($adminEmail,'harrison+clienttecn@uncommonreach.com');

                $AdminDefault = $this->get_default_admin($usrInfo['company_parent']);
                $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'newleads@leadspeek.com';

                $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                                ->where('id','=',$usrInfo['company_parent'])
                                                ->get();
                $agencyname = "Exact Match Marketing";
                
                if (count($agencycompany) > 0) {
                    $agencyname = $agencycompany[0]['company_name'];
                }

                $details['invoicetype'] = 'client';
                $details['agencyname'] = $agencyname;
                $details['defaultadmin'] = $AdminDefaultEmail;
                $details['invoiceStatus'] = str_replace("and Agency's Card Charged For Overage","",$details['invoiceStatus']);

                $from = [
                    'address' => $AdminDefaultEmail,
                    'name' => 'Invoice',
                    'replyto' => $AdminDefaultEmail,
                ];

            $tmp = $this->send_email($adminEmail,$subjectFailed . 'Invoice for ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',$details,$attachement,'emails.tryseramatchlistcharge',$from,$usrInfo['company_parent']);
            /** SENT FOR CLIENT INVOICE */

            /** CHECK IF FAILED PAYMENT THEN PAUSED THE CAMPAIGN AND SENT EMAIL*/
                if ($statusClientPayment == 'failed') {
                    $ClientCompanyIDFailed = "";
                    $ListFailedCampaign = "";
                    $_ListFailedCampaign = "";
                    $_failedUserID = "";

                    $leadsuser = LeadspeekUser::select('leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.trysera','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id','users.company_id')
                                        ->join('users','leadspeek_users.user_id','=','users.id')
                                        ->where('leadspeek_users.leadspeek_api_id','=',$_leadspeek_api_id)
                                        ->get();
                    if (count($leadsuser) > 0) {
                        foreach($leadsuser as $lds) {
                            $ClientCompanyIDFailed = $lds['company_id'];

                            //if ($lds['active'] == "T" || ($lds['active'] == "F" && $lds['disabled'] == "F")) {
                            if (!($lds['active'] == "F" && $lds['disabled'] == "T" && $lds['active_user'] == "F")) {
                                $http = new \GuzzleHttp\Client;
                                $appkey = config('services.trysera.api_id');
                                $organizationid = ($lds['leadspeek_organizationid'] != "")?$lds['leadspeek_organizationid']:"";
                                $campaignsid = ($lds['leadspeek_campaignsid'] != "")?$lds['leadspeek_campaignsid']:"";    
                                $userStripeID = $lds['customer_payment_id'];

                                $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                                $updateLeadspeekUser->active = 'F';
                                $updateLeadspeekUser->disabled = 'T';
                                $updateLeadspeekUser->active_user = 'T';
                                $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                                $updateLeadspeekUser->save();
                                /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */

                                /** UPDATE USER CARD STATUS */
                                $_failedUserID = $lds['user_id'];
                                $updateUser = User::find($lds['user_id']);

                                $failedInvoiceID = $invoiceID;
                                $failedInvoiceNumber = $invoiceNum . '-' . $invoiceID;
                                $failedTotalAmount = $totalAmount;
                                $failedCampaignID = $_leadspeek_api_id;

                                if (trim($updateUser->failed_invoiceid) != '') {
                                    $failedInvoiceID = $updateUser->failed_invoiceid . '|' . $failedInvoiceID;
                                }
                                if (trim($updateUser->failed_invoicenumber) != '') {
                                    $failedInvoiceNumber = $updateUser->failed_invoicenumber . '|' . $failedInvoiceNumber;
                                }
                                if (trim($updateUser->failed_total_amount) != '') {
                                    $failedTotalAmount = $updateUser->failed_total_amount . '|' . $failedTotalAmount;
                                }
                                if (trim($updateUser->failed_campaignid) != '') {
                                    $failedCampaignID = $updateUser->failed_campaignid . '|' . $failedCampaignID;
                                }

                                
                                $updateUser->payment_status = 'failed';
                                $updateUser->failed_invoiceid = $failedInvoiceID;
                                $updateUser->failed_invoicenumber = $failedInvoiceNumber;
                                $updateUser->failed_total_amount = $failedTotalAmount;
                                $updateUser->failed_campaignid = $failedCampaignID; 
                                $updateUser->save();
                                /** UPDATE USER CARD STATUS */

                                /** ACTIVATE CAMPAIGN SIMPLIFI */
                                if ($organizationid != '' && $campaignsid != '' && $lds['leadspeek_type'] == 'locator') {
                                    $camp = $this->startPause_campaign($organizationid,$campaignsid,'pause');
                                    if ($camp != true) {
                                        /** SEND EMAIL TO ME */
                                            $details = [
                                                'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                            ];

                                            $from = [
                                                'address' => 'noreply@sitesettingsapi.com',
                                                'name' => 'support',
                                                'replyto' => 'noreply@sitesettingsapi.com',
                                            ];
                                            $tmp =  $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-due the payment failed - L2197) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                        /** SEND EMAIL TO ME */
                                    }
                                }
                                /** ACTIVATE CAMPAIGN SIMPLIFI */

                                $ListFailedCampaign = $ListFailedCampaign . $_leadspeek_api_id . '<br/>';
                                $_ListFailedCampaign = $_ListFailedCampaign . $_leadspeek_api_id . '|';

                            }
                        }

                        /** PAUSED THE OTHER ACTIVE CAMPAIGN FOR THIS CLIENT */
                        $otherCampaignPause = false;

                            $leadsuser = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.trysera','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id','users.company_id','leadspeek_users.leadspeek_api_id')
                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                            ->where('users.company_id','=',$ClientCompanyIDFailed)
                                            ->where('users.user_type','=','client')
                                            ->where('leadspeek_users.leadspeek_api_id','<>',$_leadspeek_api_id)
                                            ->where(function($query){
                                                $query->where(function($query){
                                                    $query->where('leadspeek_users.active','=','T')
                                                        ->where('leadspeek_users.disabled','=','F')
                                                        ->where('leadspeek_users.active_user','=','T');
                                                })
                                                ->orWhere(function($query){
                                                    $query->where('leadspeek_users.active','=','F')
                                                        ->where('leadspeek_users.disabled','=','F')
                                                        ->where('leadspeek_users.active_user','=','T');
                                                });
                                            })->get();

                            if (count($leadsuser) > 0) {
                                $otherCampaignPause = true;
                                foreach($leadsuser as $lds) {
                                    $http = new \GuzzleHttp\Client;
                                    
                                    $organizationid = ($lds['leadspeek_organizationid'] != "")?$lds['leadspeek_organizationid']:"";
                                    $campaignsid = ($lds['leadspeek_campaignsid'] != "")?$lds['leadspeek_campaignsid']:"";    
                                    $userStripeID = $lds['customer_payment_id'];

                                    $updateLeadspeekUser = LeadspeekUser::find($lds['id']);
                                    $updateLeadspeekUser->active = 'F';
                                    $updateLeadspeekUser->disabled = 'T';
                                    $updateLeadspeekUser->active_user = 'T';
                                    $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                                    $updateLeadspeekUser->save();
                                    /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                                    
                                    /** ACTIVATE CAMPAIGN SIMPLIFI */
                                    if ($organizationid != '' && $campaignsid != '' && $lds['leadspeek_type'] == 'locator') {
                                        $camp = $this->startPause_campaign($organizationid,$campaignsid,'pause');
                                        if ($camp != true) {
                                            /** SEND EMAIL TO ME */
                                                $details = [
                                                    'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                                ];

                                                $from = [
                                                    'address' => 'noreply@sitesettingsapi.com',
                                                    'name' => 'support',
                                                    'replyto' => 'noreply@sitesettingsapi.com',
                                                ];
                                                $tmp = $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-due the payment failed - L2197) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                            /** SEND EMAIL TO ME */
                                        }
                                    }
                                    /** ACTIVATE CAMPAIGN SIMPLIFI */

                                    $ListFailedCampaign = $ListFailedCampaign . $lds['leadspeek_api_id'] . '<br/>';
                                    $_ListFailedCampaign = $_ListFailedCampaign . $lds['leadspeek_api_id'] . '|';
                                }
                            }
                            /** PAUSED THE OTHER ACTIVE CAMPAIGN FOR THIS CLIENT */

                            if ($otherCampaignPause) {
                                /** UPDATE ON INVOICE TABLE THAT FAILED WITH CAMPAIGN LIST THAT PAUSED */
                                $updateInvoiceCampaignPaused = LeadspeekInvoice::find($invoiceID);
                                $updateInvoiceCampaignPaused->campaigns_paused = rtrim($_ListFailedCampaign,"|");
                                $updateInvoiceCampaignPaused->save();
                                /** UPDATE ON INVOICE TABLE THAT FAILED WITH CAMPAIGN LIST THAT PAUSED */

                                $usrUpdate = User::find($_failedUserID);
                                $usrUpdate->failed_campaigns_paused = rtrim($_ListFailedCampaign,"|");
                                $usrUpdate->save();
                                
                                if (trim($ListFailedCampaign) != '' && (isset($userStripeID) && $userStripeID != '')) {
                                    /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */
                                    $from = [
                                        'address' => 'noreply@' . $defaultdomain,
                                        'name' => 'Invoice',
                                        'replyto' => 'support@' . $defaultdomain,
                                    ];
                                    
                                    $details = [
                                        'campaignid'  => $_leadspeek_api_id,
                                        'stripeid' => (isset($userStripeID))?$userStripeID:'',
                                        'othercampaigneffected' => $ListFailedCampaign,
                                    ];
                                    
                                    $tmp = $this->send_email($adminEmail,'Campaign ' . $companyName . ' #' . $_leadspeek_api_id . ' (has been pause due the payment failed)',$details,$attachement,'emails.invoicefailed',$from,"");
                                    
                                    /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */
                                }
                            }

                    }
                }
                /** CHECK IF FAILED PAYMENT THEN PAUSED THE CAMPAIGN AND SENT EMAIL*/
            
        }
        /** CHARGE WITH STRIPE */
    }
    /** CRAWLING WEBSITE EMBEDDED CODE */

    /** SIMPLI.FI API */
    
    public function startPause_campaign($_organizationID,$_campaignsID,$status='') {
        $http = new \GuzzleHttp\Client;

        $appkey = "86bb19a0-43e6-0139-8548-06b4c2516bae";
        $usrkey = "63c52610-87cd-0139-b15f-06a60fe5fe77";
        $organizationID = $_organizationID;
        $campaignsID = explode(PHP_EOL, $_campaignsID);
        
        $ProcessStatus = true;

        for($i=0;$i<count($campaignsID);$i++) {
            
           
            try {
                /** CHECK ACTIONS IF CAMPAIGN ALLOW TO RUN STATUS  */
                $apiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID[$i];
                $options = [
                    'headers' => [
                        'X-App-Key' => $appkey,        
                        'X-User-Key' => $usrkey,
                        'Content-Type' => 'application/json',
                    ],
                ];

                $response = $http->get($apiURL,$options);
                $result =  json_decode($response->getBody());
                
                for($j=0;$j<count($result->campaigns[0]->actions);$j++) {
                    if ($status == 'activate') {
                        if(isset($result->campaigns[0]->actions[$j]->activate)) {
                            //echo "activate";
                            try {
                                /** ACTIVATE THE CAMPAIGN */
                                $ActionApiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID[$i] . "/activate";
                                $ActionResponse = $http->post($ActionApiURL,$options);
                                /** ACTIVATE THE CAMPAIGN */
                            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                                $details = [
                                    'errormsg'  => 'Error when trying to Activate Campaign Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID[$i] . ' (' . $e->getCode() . ')',
                                ];

                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                                $ProcessStatus = false;
                                $this->send_email(array('harrison@uncommonreach.com'),'Error Log for Activate Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from);
                            }
                        }
                    }else if ($status == 'pause') {
                        if(isset($result->campaigns[0]->actions[$j]->pause)) {
                            //echo "Pause";
                            try {
                                /** PAUSE THE CAMPAIGN */
                                $ActionApiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID[$i] . "/pause";
                                $ActionResponse = $http->post($ActionApiURL,$options);
                                /** PAUSE THE CAMPAIGN */
                            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                                $details = [
                                    'errormsg'  => 'Error when trying to Pause Campaign Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID[$i] . ' (' . $e->getCode() . ')',
                                ];

                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                                $ProcessStatus = false;
                                $this->send_email(array('harrison@uncommonreach.com'),'Error Log for Pause Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from);
                            }
                        }
                    }else if ($status == 'stop') {
                        if(isset($result->campaigns[0]->actions[$j]->end)) {
                            //echo "Pause";
                            try {
                                /** PAUSE THE CAMPAIGN */
                                $ActionApiURL = "https://app.simpli.fi/api/organizations/" . $organizationID . "/campaigns/" . $campaignsID[$i] . "/end";
                                $ActionResponse = $http->post($ActionApiURL,$options);
                                /** PAUSE THE CAMPAIGN */
                            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                                $details = [
                                    'errormsg'  => 'Error when trying to Pause Campaign Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID[$i] . ' (' . $e->getCode() . ')',
                                ];
                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                                $ProcessStatus = false;
                                $this->send_email(array('harrison@uncommonreach.com'),'Error Log for Pause Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            }
                        }
                    }
                    //echo $result->campaigns[0]->actions[$j]->activate[0];
                }
                
                //return response()->json(array("result"=>'success','message'=>'xx','param'=>$result));
                /** CHECK ACTIONS IF CAMPAIGN ALLOW TO RUN STATUS  */
            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                $ProcessStatus = false;

                $details = [
                    'errormsg'  => 'Error when trying to get campaign information Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID[$i] . '(' . $e->getCode() . ')',
                ];
                $from = [
                    'address' => 'newleads@leadspeek.com',
                    'name' => 'support',
                    'replyto' => 'harrison@uncommonreach.com',
                ];

                $this->send_email(array('harrison@uncommonreach.com'),'Error Log for Start / Pause Get Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog',$from);

                // if ($e->getCode() === 400) {
                //     return response()->json(array("result"=>'failed','message'=>'Invalid Request. Please enter a username or a password.'), $e->getCode());
                // } else if ($e->getCode() === 401) {
                //     return response()->json(array("result"=>'failed','message'=>'Your credentials are incorrect. Please try again'), $e->getCode());
                // }

                //return response()->json(array("result"=>'failed','message'=>'Something went wrong on the server.'), $e->getCode());
            }
            
        }
        
        return $ProcessStatus;
        
    }
    /** SIMPLI.FI API */

    /** TRYSERA API */
    public function googlespreadsheet(Request $request) {
        $client = new Google_Client();
        $client->setApplicationName('LeedSpeak Google Sheet');
        $client->setAuthConfig(storage_path('client_secret.json'));
        $client->setRedirectUri("http://apilocal.exactmatchmarketing.com/auth/google/callback");
        $client->addScope("https://www.googleapis.com/auth/spreadsheets");
        $client->setAccessType('offline');        // offline access
        $client->setIncludeGrantedScopes(true);   // incremental auth
        #$client->setState($sample_passthrough_value);
        #$client->setPrompt('consent');
        if(!isset($request->code)) {
            if ($request->code == '') {
                $auth_url = $client->createAuthUrl();
                header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
                exit;die();
            } 
        }

        $accessToken = $client->fetchAccessTokenWithAuthCode($request->code);
        $client->setAccessToken($accessToken);

        $googleSheetService = new Google_Service_Sheets($client);
        
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => "Harrison Test sheet"
            ]
        ]);
        $spreadsheet = $googleSheetService->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);

        $spreadSheetId = $spreadsheet->spreadsheetId;

        echo $spreadSheetId;
    }

    public function processinvoicemonthly(Request $request) {
        date_default_timezone_set('America/Chicago');
        //if (date("d") == '01') {
        set_time_limit(0);
        if(true) { 
            // dapatkan tanggal beserta jam nya hari ini, format "Y-m-d H:i:s"
            $nowDate = Carbon::now();
            // 1 hari sebelum $nowDate 
            $previousDate = $nowDate->copy()->subDay();
            // atur waktunya menjadi akhir jam 29:59:59
            $endBillingDate = $previousDate->copy()->endOfDay();

            $clientList = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.start_billing_date','companies.id as company_id','leadspeek_users.user_id','leadspeek_users.leadspeek_type',
                                                'leadspeek_users.leadspeek_api_id','companies.company_name','leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to',
                                                'leadspeek_users.paymentterm','leadspeek_users.lp_enddate','leadspeek_users.lp_limit_startdate','leadspeek_users.campaign_name',
                                                'leadspeek_users.cost_perlead','leadspeek_users.lp_max_lead_month','leadspeek_users.lp_min_cost_month','users.customer_payment_id','users.customer_card_id','users.email','leadspeek_users.company_id as company_parent','users.active','users.company_root_id')
                            ->join('users','leadspeek_users.user_id','=','users.id')
                            ->join('companies','users.company_id','=','companies.id')
                            ->where(function($query){
                                $query->where(function($query){
                                    $query->where('leadspeek_users.active','=','T')
                                        ->where('leadspeek_users.disabled','=','F')
                                        ->where('leadspeek_users.active_user','=','T');
                                })
                                ->orWhere(function($query){
                                    $query->where('leadspeek_users.active','=','F')
                                        ->where('leadspeek_users.disabled','=','T')
                                        ->where('leadspeek_users.active_user','=','T');
                                })
                                ->orWhere(function($query){
                                    $query->where('leadspeek_users.active','=','F')
                                        ->where('leadspeek_users.disabled','=','F')
                                        ->where('leadspeek_users.active_user','=','T');
                                });
                            })
                            ->where('leadspeek_users.paymentterm','=','Monthly')
                            ->where('leadspeek_users.archived','=','F')
                            ->where('users.user_type','=','client')
                            // ->where(DB::raw('DATE_FORMAT(DATE_ADD(leadspeek_users.start_billing_date,INTERVAL 1 MONTH),"%Y%m%d%H%i%s")'),'<=',date("YmdHis"))
                            ->whereRaw("TIMESTAMPDIFF(MONTH, leadspeek_users.start_billing_date, ?) >= 1", [$endBillingDate])
                            /*->where(function($query) {
                                $query->where('leadspeek_users.cost_perlead','>',0)
                                        ->orWhere('leadspeek_users.lp_max_lead_month','>',0)
                                        ->orWhere('leadspeek_users.lp_min_cost_month','>',0);
                            })*/
                            ->orderBy(DB::raw("DATE_FORMAT(leadspeek_users.start_billing_date,'%Y%m%d')"),'ASC')
                            ->get();

            foreach($clientList as $cl) {
                /** CHECK IF THE LAST BILLING IS NOT THIS MONTH AND YEAR */
                //$lastBilling = date('Ym',strtotime($cl['start_billing_date']));
               
                    /** BILL IF THE LAST INVOICE NOT THIS MONTH */
                    //if($lastBilling < date('Ym')) {
                        $_lp_user_id = $cl['id'];
                        $_company_id = $cl['company_id'];
                        $_company_parent = $cl['company_parent'];
                        $_user_id = $cl['user_id'];
                        $_leadspeek_api_id = $cl['leadspeek_api_id'];
                        $campaignName = '';
                        if (isset($cl['campaign_name']) && trim($cl['campaign_name']) != '') {
                            $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
                        }

                        $company_name = str_replace($_leadspeek_api_id,'',$cl['company_name']) . $campaignName;
                        //$clientEmail = explode(PHP_EOL, $cl['report_sent_to']);
                        $_clientEmail = str_replace(["\r\n", "\r"], "\n", trim($cl['report_sent_to']));
                        $clientEmail = explode("\n", $_clientEmail);
                        $clientAdminNotify = explode(',',$cl['admin_notify_to']);


                        $clientCostPerLead = ($cl['cost_perlead'] != '')?$cl['cost_perlead']:0;
                        $clientMaxLead = ($cl['lp_max_lead_month'] != '')?$cl['lp_max_lead_month']:0;
                        $clientMinCostMonth = ($cl['lp_min_cost_month'] != '')?$cl['lp_min_cost_month']:0;
                        $clientPaymentTerm = $cl['paymentterm'];
                        
                        //if ($clientCostPerLead != '0' || $clientMaxLead != '0' || $clientMinCostMonth != '0') {
                            $custStripeID = $cl['customer_payment_id'];
                            $custStripeCardID = $cl['customer_card_id'];
                            $custEmail = $cl['email'];
                            $clientStartBilling = date("YmdHis", strtotime($cl['start_billing_date']));
                            //$nextBillingDate =  date("Ymd", strtotime("last day of previous month"));
                            //$nextBillingDate =  date("YmdHis", strtotime("-1 days"));
                            // $nextBillingDate =  date("YmdHis");
                            $clientendBillingDate = date("YmdHis", strtotime($endBillingDate));
                            /** CREATE INVOICE AND SENT IT */
                                // $invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxLead,$clientCostPerLead,$clientMinCostMonth,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl,$_company_parent);
                                $invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxLead,$clientCostPerLead,$clientMinCostMonth,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$clientendBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl,$_company_parent);
                            /** CREATE INVOICE AND SENT IT */

                            /** IF USER ACTIVE THEN STOP THE CAMPAIGN */
                            if ($cl['active'] == "F") {
                                $updateleadsuser = LeadspeekUser::find($_lp_user_id);
                                $updateleadsuser->active = 'F';
                                $updateleadsuser->disabled = 'T';
                                $updateleadsuser->active_user = 'F';
                                $updateleadsuser->save();
                                
                                $details = [
                                    'errormsg'  => 'User already not active Leadspeek ID :' . $_leadspeek_api_id . ' companyID: ' . $_company_id . ' Company Parent:' . $_user_id . ' Campaign Name:' . $cl['campaign_name'],
                                ];
                
                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                                
                                $this->send_email(array('harrison@uncommonreach.com'),'processinvoicemonthly - User already not Active (INTERNAL) #' . $_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            }
                            /** IF USER ACTIVE THEN STOP THE CAMPAIGN */

                        //}

                    //}
                    /** BILL IF THE LAST INVOICE NOT THIS MONTH */
            }

        }
    }

    public function processinvoice(Request $request) {
        date_default_timezone_set('America/Chicago');
        set_time_limit(0);
        //if (strtolower(date("D")) == 'tue') {       
        if (true) {  
            // dapatkan tanggal beserta jam nya hari ini, format "Y-m-d H:i:s"
            $nowDate = Carbon::now();
            // 1 hari sebelum $nowDate 
            $previousDate = $nowDate->copy()->subDay();
            // atur waktunya menjadi akhir jam 29:59:59
            $endBillingDate = $previousDate->copy()->endOfDay();

            $clientList = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.start_billing_date','companies.id as company_id','leadspeek_users.user_id','leadspeek_users.leadspeek_type',
                                                'leadspeek_users.leadspeek_api_id','companies.company_name','leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to',
                                                'leadspeek_users.paymentterm','leadspeek_users.lp_enddate','leadspeek_users.lp_limit_startdate','leadspeek_users.campaign_name',
                                                'leadspeek_users.cost_perlead','leadspeek_users.lp_max_lead_month','leadspeek_users.lp_min_cost_month','users.customer_payment_id','users.customer_card_id','users.customer_ach_id','users.payment_type','users.email','leadspeek_users.company_id as company_parent','users.active','users.company_root_id')
                            ->join('users','leadspeek_users.user_id','=','users.id')
                            ->join('companies','users.company_id','=','companies.id')
                            ->where(function($query){
                                $query->where(function($query){
                                    $query->where('leadspeek_users.active','=','T')
                                        ->where('leadspeek_users.disabled','=','F')
                                        ->where('leadspeek_users.active_user','=','T');
                                })
                                ->orWhere(function($query){
                                    $query->where('leadspeek_users.active','=','F')
                                        ->where('leadspeek_users.disabled','=','T')
                                        ->where('leadspeek_users.active_user','=','T');
                                })
                                ->orWhere(function($query){
                                    $query->where('leadspeek_users.active','=','F')
                                        ->where('leadspeek_users.disabled','=','F')
                                        ->where('leadspeek_users.active_user','=','T');
                                });
                            })
                            ->where('leadspeek_users.paymentterm','=','Weekly')
                            ->where('users.user_type','=','client')
                            ->where('leadspeek_users.archived','=','F')
                            //->where(DB::raw('DATE_FORMAT(leadspeek_users.start_billing_date, "%Y%m")'),'<',DB::raw('DATE_FORMAT(CURDATE(), "%Y%m")'))
                            //->where(DB::raw('DATE_FORMAT(leadspeek_users.start_billing_date, "%Y%m%d")'),'<',DB::raw('DATE_FORMAT(CURDATE(), "%Y%m%d")'))
                            //->where(DB::raw('DATE_FORMAT(leadspeek_users.start_billing_date, "%Y%m%d")'),'<',date("Ymd"))
                            // ->where(DB::raw('DATE_FORMAT(DATE_ADD(leadspeek_users.start_billing_date,INTERVAL 7 DAY),"%Y%m%d%H%i%s")'),'<=',date("YmdHis"))
                            ->whereRaw("TIMESTAMPDIFF(WEEK, leadspeek_users.start_billing_date, ?) >= 1", [$endBillingDate])
                            /*->where(function($query) {
                                $query->where('leadspeek_users.cost_perlead','>',0)
                                        ->orWhere('leadspeek_users.lp_max_lead_month','>',0)
                                        ->orWhere('leadspeek_users.lp_min_cost_month','>',0);
                            })*/
                            ->orderBy(DB::raw("DATE_FORMAT(leadspeek_users.start_billing_date,'%Y%m%d')"),'ASC')
                            ->get();

            foreach($clientList as $cl) {

                /** CHECK IF THE LAST BILLING IS NOT THIS MONTH AND YEAR */
                //$lastBilling = date('Ym',strtotime($cl['start_billing_date']));
               
                    /** BILL IF THE LAST INVOICE NOT THIS MONTH */
                    //if($lastBilling < date('Ym')) {
                        $_lp_user_id = $cl['id'];
                        $_company_id = $cl['company_id'];
                        $_company_parent = $cl['company_parent'];
                        $_user_id = $cl['user_id'];
                        $_leadspeek_api_id = $cl['leadspeek_api_id'];
                        $campaignName = '';
                        if (isset($cl['campaign_name']) && trim($cl['campaign_name']) != '') {
                            $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
                        }

                        $company_name = str_replace($_leadspeek_api_id,'',$cl['company_name']) . $campaignName;
                        //$clientEmail = explode(PHP_EOL, $cl['report_sent_to']);
                        $_clientEmail = str_replace(["\r\n", "\r"], "\n", trim($cl['report_sent_to']));
                        $clientEmail = explode("\n", $_clientEmail);
                        $clientAdminNotify = explode(',',$cl['admin_notify_to']);


                        $clientCostPerLead = ($cl['cost_perlead'] != '')?$cl['cost_perlead']:0;
                        $clientMaxLead = ($cl['lp_max_lead_month'] != '')?$cl['lp_max_lead_month']:0;
                        $clientMinCostMonth = ($cl['lp_min_cost_month'] != '')?$cl['lp_min_cost_month']:0;
                        $clientPaymentTerm = $cl['paymentterm'];

                        /*
                        $clientWeeksContract = 52; //assume will be one year if end date is null or empty
                        $clientLimitStartDate = ($cl['lp_limit_startdate'] == null || $cl['lp_limit_startdate'] == '0000-00-00 00:00:00')?'':$cl['lp_limit_startdate'];
                        $clientLimitEndDate = ($cl['lp_enddate'] == null || $cl['lp_enddate'] == '0000-00-00 00:00:00')?'':$cl['lp_enddate'];
                        $clientMonthRange = 12;
                        */

                        //if ($clientCostPerLead != '0' || $clientMaxLead != '0' || $clientMinCostMonth != '0') {
                            $custStripeID = $cl['customer_payment_id'];
                            $custStripeCardID = "";
                            if($cl['payment_type'] == '') {
                                Log::info("campaign " . $cl['leadspeek_api_id'] . " not select payment type");
                                break;
                            } else if($cl['payment_type'] == 'credit_card') {
                                $custStripeCardID = $cl['customer_card_id'];
                            } else if($cl['payment_type'] == 'bank_account') {
                                $custStripeCardID = $cl['customer_ach_id'];
                            }
                            $custEmail = $cl['email'];
                            //$clientStartBilling = date("Ymd", strtotime("first day of previous month"));
                            //$nextBillingDate =  date("Ymd", strtotime("last day of previous month"));
                            //$clientStartBilling = date("Ymd", strtotime($cl['start_billing_date']));
                            /*
                            $lastTuesday =  date('Ymd',strtotime('last Tuesday'));
                            $lastBilling = date('Ymd',strtotime($cl['start_billing_date']));
                            
                            if($lastBilling > $lastTuesday) {
                                $clientStartBilling = $lastBilling;
                            }else{
                                $clientStartBilling = $lastTuesday;
                            }
                            */
                            $clientStartBilling = date("YmdHis", strtotime($cl['start_billing_date']));
                            //$nextBillingDate =  date("YmdHis", strtotime("-1 days"));
                            // $nextBillingDate =  date("YmdHis");
                            // $nextBillingDate =  date("YmdHis");
                            $clientendBillingDate = date("YmdHis", strtotime($endBillingDate));
                            /*$date1=date_create($clientStartBilling);
                            $date2=date_create($nextBillingDate);
                            $diff=date_diff($date1,$date2);

                            if ($diff->format("%a") > 6) {
                                $clientStartBilling  =  date("Ymd", strtotime("-7 days"));
                            }
                            */
                            /** CHECK IF NEED TO BILLED PLATFORM FEE OR NOT */
                            /*
                            $platformFee = 0;
                            if ($clientPaymentTerm == 'Weekly') {
                                $date1=date_create($nextBillingDate);
                                $date2=date_create($clientStartBilling);
                                $diff=date_diff($date1,$date2);
                                if ($diff->format("%a") >= 6) {
                                    $platformFee = $clientMinCostMonth;
                                }
                            }else if ($clientPaymentTerm == 'Monthly') {
                                if(date('m') > date('m',strtotime($clientStartBilling))) {
                                    $platformFee = $clientMinCostMonth;
                                }
                            }
                            */
                            /** CHECK IF NEED TO BILLED PLATFORM FEE OR NOT */

                            /** PUT FORMULA TO DEVIDED HOW MANY TUESDAY FROM PLATFORM FEE COST */
                            /*
                            if ($platformFee != '' && $platformFee > 0) {
                                if ($clientLimitEndDate != '') {
                                    $d1 = new DateTime($clientLimitStartDate);
                                    $d2 = new DateTime($clientLimitEndDate);
                                    $interval = $d1->diff($d2);
                                    $clientMonthRange = $interval->m;

                                    $d1 = strtotime($clientLimitStartDate);
                                    $d2 = strtotime($clientLimitEndDate);
                                    $clientWeeksContract = $this->countDays(2, $d1, $d2);

                                    $platformFee = ($clientMinCostMonth * $clientMonthRange) / $clientWeeksContract;

                                }else{
                                    $platformFee = ($clientMinCostMonth * $clientMonthRange) / $clientWeeksContract;
                                }
                            }
                            */
                            /** PUT FORMULA TO DEVIDED HOW MANY TUESDAY FROM PLATFORM FEE COST */
                            //echo $_lp_user_id . ' | ' . $_company_id. ' | ' .$_user_id. ' | ' .$_leadspeek_api_id. ' | ' .$clientMaxLead. ' | ' .$clientCostPerLead. ' | ' .$clientMinCostMonth. ' | ' .$clientPaymentTerm. ' | ' .$company_name.   ' | ' . date('d-m-Y',strtotime($clientStartBilling)). ' | ' . date('d-m-Y',strtotime($nextBillingDate)) .' | ' . $diff->format("%a")  .' | ' .$custStripeID. ' | ' .$custStripeCardID. ' | ' .$custEmail . ' | ' . $_company_parent .  '<br><br/>';
                            /** CREATE INVOICE AND SENT IT */
                                //$invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxLead,$clientCostPerLead,$platformFee,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl);
                                $invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxLead,$clientCostPerLead,$clientMinCostMonth,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$clientendBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl,$_company_parent);
                            /** CREATE INVOICE AND SENT IT */

                            /** IF USER ACTIVE THEN STOP THE CAMPAIGN */
                            if ($cl['active'] == "F") {
                                $updateleadsuser = LeadspeekUser::find($_lp_user_id);
                                $updateleadsuser->active = 'F';
                                $updateleadsuser->disabled = 'T';
                                $updateleadsuser->active_user = 'F';
                                $updateleadsuser->save();
                                
                                $details = [
                                    'errormsg'  => 'User already not active Leadspeek ID :' . $_leadspeek_api_id . ' companyID: ' . $_company_id . ' Company Parent:' . $_user_id . ' Campaign Name:' . $cl['campaign_name'],
                                ];
                
                                $from = [
                                    'address' => 'newleads@leadspeek.com',
                                    'name' => 'support',
                                    'replyto' => 'harrison@uncommonreach.com',
                                ];
                
                                $this->send_email(array('harrison@uncommonreach.com'),'processinvoice - User already not Active (INTERNAL) #' . $_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            }
                            /** IF USER ACTIVE THEN STOP THE CAMPAIGN */

                        //}

                    //}
                    /** BILL IF THE LAST INVOICE NOT THIS MONTH */
            }

        }
    }

    private function createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$minLeads,$costLeads,$minCostLeads,$clientPaymentTerm,$companyName,$reportSentTo,$adminnotify,$startBillingDate,$endBillingDate,$custStripeID,$custStripeCardID,$custEmail,$usrInfo,$companyParent = '') {
        // Log::info("start createInvoice");
        // Log::info(['custStripeID' => $custStripeID,'custStripeCardID' => $custStripeCardID]);
        date_default_timezone_set('America/Chicago');
        $todayDate = date('Y-m-d H:i:s');
        $invoiceNum = date('Ymd') . '-' . $_lp_user_id;
        $exceedLeads = 0;
        $totalAmount = 0;
        $costPriceLeads = 0;
        $platform_costPriceLeads = 0;
        $root_costPriceLeads = 0;
        $rootFee = 0;
        $cleanProfit = 0;
        //$totalLeads = $minLeads + $exceedLeads;
        $rootAccCon = "";
        $ongoingLeads = 0;

        /** FIND IF THERE IS ANY EXCEED LEADS */
        $reportCat = LeadspeekReport::select(DB::raw("COUNT(*) as total"),DB::raw("SUM(price_lead) as costleadprice"),DB::raw("SUM(platform_price_lead) as platform_costleadprice"),DB::raw("SUM(root_price_lead) as root_costleadprice"))
                        ->where('lp_user_id','=',$_lp_user_id)
                        ->where('company_id','=',$_company_id)
                        ->where('user_id','=',$_user_id)
                        ->where('leadspeek_api_id','=',$_leadspeek_api_id)
                        ->where('active','=','T')
                        //->whereBetween(DB::raw('DATE_FORMAT(clickdate,"%Y-%m-%d")'),[$startBillingDate,$endBillingDate])
                        ->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d%H%i%s")'),'>=',$startBillingDate)
                        ->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d%H%i%s")'),'<=',$endBillingDate)
                        ->get();
        if(count($reportCat) > 0) {
            $ongoingLeads = $reportCat[0]['total'];
            $costPriceLeads = $reportCat[0]['costleadprice'];
            $platform_costPriceLeads = $reportCat[0]['platform_costleadprice'];
            $root_costPriceLeads = $reportCat[0]['root_costleadprice'];

            // Log::info([
            //     'ongoingLeads' => $ongoingLeads,
            //     'costPriceLeads' => $costPriceLeads,
            //     'platform_costPriceLeads' => $platform_costPriceLeads,
            //     'root_costPriceLeads' => $root_costPriceLeads,
            // ]);
        }
        
        /*if(($ongoingLeads > $minLeads) && $minLeads > 0) {
            $exceedLeads = $ongoingLeads - $minLeads;
        }*/
        /** FIND IF THERE IS ANY EXCEED LEADS */

        //$totalAmount = ($costLeads * $exceedLeads) + $minCostLeads;
        /** IF JUST WILL CHARGE PER LEAD */
        if ($clientPaymentTerm != 'One Time' && ($costLeads != '0' || trim($costLeads) != '')) {
            //$totalAmount =  ($costLeads * $ongoingLeads) + $minCostLeads;
            $totalAmount = $costPriceLeads + $minCostLeads;
            // Log::info([
            //     'totalAmount' => $totalAmount,
            //     'minCostLeads' => $minCostLeads,
            //     'costPriceLeads' => $costPriceLeads,
            // ]);
        }else if($clientPaymentTerm == 'One Time') {
            $totalAmount = $minCostLeads;
        }
        /** IF JUST WILL CHARGE PER LEAD */

        /** CHARGE WITH STRIPE */
        $metadataStripe = [];
        $usePaymentMethod = "";
        $paymentintentID = '';
        $errorstripe = '';
        $errorStripeMessage = "";
        $errorstripeAch = '';
        $errorStripeAchMessage = "";
        $platform_errorstripe = '';
        $statusPayment = 'pending';
        $statusPaymentAch = 'pending';
        $cardlast = '';
        $platform_paymentintentID = '';
        $sr_id = 0;
        $ae_id = 0;
        $ar_id = 0;
        $sales_fee = 0;
        $platformfee_charge = false;
        $platformfee_charge_ach = false;

        $totalAmount = number_format($totalAmount,2,'.','');
        $minCostLeads = number_format($minCostLeads,2,'.','');
        if ($root_costPriceLeads != "0") {
            $rootFee = number_format($root_costPriceLeads,2,'.','');
        }
        $companyParentName = "";
        $AgencyManualBill = "F";

        /** GET COMPANY PARENT NAME / AGENCY */
        $getParentInfo = Company::select('company_name','manual_bill')->where('id','=',$usrInfo['company_parent'])->get();
        if(count($getParentInfo) > 0) {
            $companyParentName = $getParentInfo[0]['company_name'];
            $AgencyManualBill = $getParentInfo[0]['manual_bill'];
        }
        /** GET COMPANY PARENT NAME / AGENCY */

        /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */
            $accConID = '';
            if ($usrInfo['company_parent'] != '') {
                $accConID = $this->check_connected_account($usrInfo['company_parent'],$usrInfo['company_root_id']);
            }
        /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */

        /** CHECK IF USER DATA STILL ON PLATFORM */
            $validuser = true;
            $user[0]['customer_payment_id'] = (isset($usrInfo['customer_payment_id']))?$usrInfo['customer_payment_id']:'';
            $user[0]['company_id'] = (isset($usrInfo['company_id']))?$usrInfo['company_id']:'';
            $user[0]['id'] = (isset($usrInfo['user_id']))?$usrInfo['user_id']:'';
            $user[0]['company_root_id'] = (isset($usrInfo['company_root_id']))?$usrInfo['company_root_id']:'';

            if($usrInfo['payment_type'] == 'credit_card') {
                $chkStripeUser = $this->check_stripe_customer_platform_exist($user,$accConID);
                $chkResultUser = json_decode($chkStripeUser);
                if ($chkResultUser->result == 'success') {
                    $validuser = true;
                    $custStripeID = $chkResultUser->custStripeID;
                    $custStripeCardID = $chkResultUser->CardID;
                }else{
                    $validuser = false;
                }

                // Log::info([
                //     'payment_type' => $usrInfo['payment_type'],
                //     'accConID' => $accConID,
                //     'validuser' => $validuser,
                //     'custStripeID' => $chkResultUser->custStripeID,
                //     'custStripeCardID' => $chkResultUser->CardID,
                //     'chkStripeUser' => $chkStripeUser
                // ]);
            } else if($usrInfo['payment_type'] == 'bank_account') {
                $user[0]['customer_ach_id'] = (isset($usrInfo['customer_ach_id']))?$usrInfo['customer_ach_id']:'';
                // Log::info("", [
                //     'customer_ach_id' => $user[0]['customer_ach_id']
                // ]);
                $chkStripeUser = $this->check_stripe_customer_ach_platform_exist($user,$accConID);
                $chkResultUser = json_decode($chkStripeUser);
                if ($chkResultUser->result == 'success') {
                    $validuser = true;
                    $custStripeID = $chkResultUser->custStripeID;
                    $custStripeCardID = $chkResultUser->AchID;
                }else{
                    $validuser = false;
                }

                // Log::info([
                //     'payment_type' => $usrInfo['payment_type'],
                //     'accConID' => $accConID,
                //     'validuser' => $validuser,
                //     'custStripeID' => $chkResultUser->custStripeID,
                //     'custStripeCardID' => $chkResultUser->AchID,
                //     'chkStripeUser' => $chkStripeUser
                // ]);
            }
        /** CHECK IF USER DATA STILL ON PLATFORM */

        /** CHECK IF AGENCY MANUAL BILL */
        if ($AgencyManualBill == "T") {
            $validuser = true;
            $custStripeID = "agencyDirectPayment";
            $custStripeCardID = "agencyDirectPayment";
            /** GET STRIPE ACC AND CARD AGENCY */
            $custAgencyStripeID = "";
            $custAgencyStripeCardID = "";

            $chkAgency = User::select('id','customer_payment_id','customer_card_id','email')
                    ->where('company_id','=',$usrInfo['company_parent'])
                    ->where('company_parent','<>',$usrInfo['company_parent'])
                    ->where('user_type','=','userdownline')
                    ->where('isAdmin','=','T')
                    ->where('active','=','T')
                    ->get();
            if(count($chkAgency) > 0) {
                $custAgencyStripeID = $chkAgency[0]['customer_payment_id'];
                $custAgencyStripeCardID = $chkAgency[0]['customer_card_id'];
            }
            /** GET STRIPE ACC AND CARD AGENCY */
        }
        /** CHECK IF AGENCY MANUAL BILL */
        
        $platform_LeadspeekCostperlead = 0;
        $platform_LeadspeekMinCostMonth = 0;
        $platform_LeadspeekPlatformFee = 0;
        $platformfee_ori = 0;
        $platformfee = 0;

        if(trim($custStripeID) != '' && trim($custStripeCardID) != '' && $validuser) { 
            /** GET STRIPE KEY */
            $stripeseckey = config('services.stripe.secret');
            $stripepublish = $this->getcompanysetting($usrInfo['company_root_id'],'rootstripe');
            if ($stripepublish != '') {
                $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
            }
            /** GET STRIPE KEY */

            // Log::info([
            //     'stripeseckey' => $stripeseckey
            // ]);

            $stripe = new StripeClient([
                'api_key' => $stripeseckey,
                'stripe_version' => '2020-08-27'
            ]);

            /** GET PLATFORM MARGIN */
                $platformMargin = $this->getcompanysetting($usrInfo['company_parent'],'costagency');

                $paymentterm = trim($usrInfo['paymentterm']);
                $paymentterm = str_replace(' ','',$paymentterm);
                if ($platformMargin != '') {
                    // $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');

                    if ($usrInfo['leadspeek_type'] == "local") {
                        $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:0;
                        $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:0;
                        $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:0;
                    
                        // $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:$rootcostagency->local->$paymentterm->LeadspeekCostperlead;
                        // $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:$rootcostagency->local->$paymentterm->LeadspeekMinCostMonth;
                        // $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:$rootcostagency->local->$paymentterm->LeadspeekPlatformFee;
                    }else if ($usrInfo['leadspeek_type'] == "locator") {
                        $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LocatorCostperlead))?$platformMargin->locator->$paymentterm->LocatorCostperlead:0;
                        $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LocatorMinCostMonth))?$platformMargin->locator->$paymentterm->LocatorMinCostMonth:0;
                        $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LocatorPlatformFee))?$platformMargin->locator->$paymentterm->LocatorPlatformFee:0;

                        // $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LeadspeekCostperlead))?$platformMargin->locator->$paymentterm->LeadspeekCostperlead:$rootcostagency->locator->$paymentterm->LeadspeekCostperlead;
                        // $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->locator->$paymentterm->LeadspeekMinCostMonth:$rootcostagency->locator->$paymentterm->LeadspeekMinCostMonth;
                        // $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LeadspeekPlatformFee))?$platformMargin->locator->$paymentterm->LeadspeekPlatformFee:$rootcostagency->locator->$paymentterm->LeadspeekPlatformFee;
                    }else if($usrInfo['leadspeek_type'] == "enhance") {
                        $rootcostagency = [];
                        if(!isset($platformMargin->enhance)) {
                            $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');
                        }

                        $clientTypeLead = $this->getClientCapType($usrInfo['company_root_id']);
                        if($clientTypeLead['type'] == 'clientcapleadpercentage') {
                            $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');
                            $costagency = $this->getcompanysetting($usrInfo['company_parent'], 'costagency');
                            
                            if($usrInfo['paymentterm'] == 'Weekly') {
                                $m_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
                                $rootCostPerLeadMin = ($costagency->enhance->Weekly->EnhanceCostperlead > $rootcostagency->enhance->Weekly->EnhanceCostperlead) ? $costagency->enhance->Weekly->EnhanceCostperlead : $rootcostagency->enhance->Weekly->EnhanceCostperlead;
                                $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                                // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                                if(($usrInfo['cost_perlead'] == 0) || ($usrInfo['cost_perlead'] <= $rootCostPerLeadMax && $usrInfo['cost_perlead'] >= $rootCostPerLeadMin)) { 
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                                // jika lebih dari $rootCostPerLeadMax, maka dinamis
                                else if($usrInfo['cost_perlead'] > $rootCostPerLeadMax) {
                                    $platform_LeadspeekCostperlead = $m_LeadspeekCostperlead;
                                }
                                else {
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                            } else if($usrInfo['paymentterm'] == 'Monthly') {
                                $m_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
                                $rootCostPerLeadMin = ($costagency->enhance->Monthly->EnhanceCostperlead > $rootcostagency->enhance->Monthly->EnhanceCostperlead) ? $costagency->enhance->Monthly->EnhanceCostperlead : $rootcostagency->enhance->Monthly->EnhanceCostperlead;
                                $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                                // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                                if(($usrInfo['cost_perlead'] == 0) || ($usrInfo['cost_perlead'] <= $rootCostPerLeadMax && $usrInfo['cost_perlead'] >= $rootCostPerLeadMin)) { 
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                                // jika lebih dari $rootCostPerLeadMax, maka dinamis
                                else if($usrInfo['cost_perlead'] > $rootCostPerLeadMax) {
                                    $platform_LeadspeekCostperlead = $m_LeadspeekCostperlead;
                                }
                                else {
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                            } else if($usrInfo['paymentterm'] == 'One Time') {
                                $m_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
                                $rootCostPerLeadMin = ($costagency->enhance->OneTime->EnhanceCostperlead > $rootcostagency->enhance->OneTime->EnhanceCostperlead) ? $costagency->enhance->OneTime->EnhanceCostperlead : $rootcostagency->enhance->OneTime->EnhanceCostperlead;
                                $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                                // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                                if(($usrInfo['cost_perlead'] == 0) || ($usrInfo['cost_perlead'] <= $rootCostPerLeadMax && $usrInfo['cost_perlead'] >= $rootCostPerLeadMin)) { 
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                                // jika lebih dari $rootCostPerLeadMax, maka dinamis
                                else if($usrInfo['cost_perlead'] > $rootCostPerLeadMax) {
                                    $platform_LeadspeekCostperlead = $m_LeadspeekCostperlead;
                                }
                                else {
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                            } else if($usrInfo['paymentterm'] == 'Prepaid') {
                                $m_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
                                $rootCostPerLeadMin = ($costagency->enhance->Prepaid->EnhanceCostperlead > $rootcostagency->enhance->Prepaid->EnhanceCostperlead) ? $costagency->enhance->Prepaid->EnhanceCostperlead : $rootcostagency->enhance->Prepaid->EnhanceCostperlead;
                                $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                                // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                                if(($usrInfo['cost_perlead'] == 0) || ($usrInfo['cost_perlead'] <= $rootCostPerLeadMax && $usrInfo['cost_perlead'] >= $rootCostPerLeadMin)) {
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                                // jika lebih dari $rootCostPerLeadMax, maka dinamis
                                else if($usrInfo['cost_perlead'] > $rootCostPerLeadMax) {
                                    $platform_LeadspeekCostperlead = $m_LeadspeekCostperlead;
                                }
                                else {
                                    $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                                }
                            }
                        } else {
                            $platform_LeadspeekCostperlead = (isset($platformMargin->enhance->$paymentterm->EnhanceCostperlead))?$platformMargin->enhance->$paymentterm->EnhanceCostperlead:$rootcostagency->enhance->$paymentterm->EnhanceCostperlead;
                        }

                        $platform_LeadspeekMinCostMonth = (isset($platformMargin->enhance->$paymentterm->EnhanceMinCostMonth))?$platformMargin->enhance->$paymentterm->EnhanceMinCostMonth:$rootcostagency->enhance->$paymentterm->EnhanceMinCostMonth;
                        $platform_LeadspeekPlatformFee = (isset($platformMargin->enhance->$paymentterm->EnhancePlatformFee))?$platformMargin->enhance->$paymentterm->EnhancePlatformFee:$rootcostagency->enhance->$paymentterm->EnhancePlatformFee;
                    }
                }
            /** GET PLATFORM MARGIN */

            if ($clientPaymentTerm != 'One Time' && ($costLeads != '0' || trim($costLeads) != '')) {
                //$platformfee =  ($platform_LeadspeekCostperlead * $ongoingLeads) + $platform_LeadspeekMinCostMonth;
                $platformfee =  $platform_costPriceLeads + $platform_LeadspeekMinCostMonth;
                /** HACKED PLATFORMFEE FOR ONLY CAMPAIGN ID #642466 */
                // if ($_leadspeek_api_id == '642466') {
                //     $platform_LeadspeekCostperlead = 0.15;
                //     $platformfee = (0.15 * $ongoingLeads) + $platform_LeadspeekMinCostMonth;
                // }
                /** HACKED PLATFORMFEE FOR ONLY CAMPAIGN ID #642466 */

            }else if($clientPaymentTerm == 'One Time') {
                $platformfee = $platform_LeadspeekMinCostMonth;
            }
            
            $platformfee = number_format($platformfee,2,'.','');
            $platformfee_ori = $platformfee;

            // Log::info([
            //     'platformfee_ori' => $platformfee_ori,
            //     'platformfee' => $platformfee,
            // ]);

            $defaultInvoice = '#' . $invoiceNum . '-' . $companyName . ' #' . $_leadspeek_api_id;

            /** CHECK IF TOTAL AMOUNT IS SMALLER THAN PLATFORM FEE */
            //if (($totalAmount < $platformfee) && $platformfee > 0) {
            // if ($platformfee >= 0.5) {
            //     $agencystripe = $this->check_agency_stripeinfo($usrInfo['company_parent'],$platformfee,$_leadspeek_api_id,'Agency ' . $defaultInvoice);
            //     $agencystriperesult = json_decode($agencystripe);
            //     $platformfee_charge = true;

            //     if ($agencystriperesult->result == 'success') {
            //         $platform_paymentintentID = $agencystriperesult->payment_intentID;
            //         $sr_id = $agencystriperesult->srID;
            //         $ae_id = $agencystriperesult->aeID;
            //         $sales_fee = $agencystriperesult->salesfee;
            //         $platformfee = 0;
            //         $platform_errorstripe = '';
            //     }else{
            //         $platform_paymentintentID = $agencystriperesult->payment_intentID;
            //         $platform_errorstripe .= $agencystriperesult->error;
            //     }
            // }
            /** CHECK IF TOTAL AMOUNT IS SMALLER THAN PLATFORM FEE */
                
            /** CREATE ONE TIME PAYMENT USING PAYMENT INTENT */
            if ($totalAmount < 0.5 || $totalAmount <= 0) {
                $paymentintentID = '';
                $statusPayment = 'paid';
                $platformfee_charge = false;
            }else{
                if ($AgencyManualBill == 'F') {
                    try {
                        $payment_intent = [];
                        $chargeAmount = $totalAmount * 100;

                        // Log::info(['leadspeek_type' => $usrInfo['leadspeek_type']]);
                        if($usrInfo['leadspeek_type'] == 'enhance') {
                            $masterRootFee = $this->getcompanysetting($usrInfo['company_root_id'],'rootfee');

                            // Log::info([
                            //     'company_root_id' => $usrInfo['company_root_id'],
                            //     'masterRootFee' => $masterRootFee,
                            // ]);


                            if((isset($masterRootFee->feepercentagemob) && $masterRootFee->feepercentagemob != "") || (isset($masterRootFee->feepercentagedom) && $masterRootFee->feepercentagedom != "")) {
                                // if root mobile or dominator
                                // Log::info("platformfee = $platformfee ke-1");
                                $feePercentageEmm = (isset($masterRootFee->feepercentageemm))?$masterRootFee->feepercentageemm:0;
                                $platformfee = ($platformfee * $feePercentageEmm) / 100;
                                $platformfee = number_format($platformfee,2,'.','');
                                // Log::info("platformfee = $platformfee ke-2");
                                
                                // Log::info([
                                //     'action' => 'pembagian ke emm jika enhance 1',
                                //     'in_root' => isset($masterRootFee->feepercentagemob) && isset($masterRootFee->feepercentagedom) ? "mobile" : (isset($masterRootFee->feepercentagedom) ? "dominator" : ""),
                                //     'platformfee' => $platformfee,
                                //     'feePercentageEmm' => $feePercentageEmm,
                                //     'customer' => trim($custStripeID),
                                //     'stripe_account' => $accConID
                                // ]);
                            }
                        }

                        /* USE CREDIT_CARD */
                        if($usrInfo['payment_type'] == 'credit_card')
                        {
                            $usePaymentMethod = 'credit_card';
                            // Log::info('CHARGE PERTAMA USE CREDIT_CARD');
                            // Log::info([
                            //     'application_fee_amount' => ($platformfee * 100)
                            // ]);

                            // Log::info([
                            //     'action' => 'process charge client with stripe',
                            //     'payment_method_types' => ['card'],
                            //     'customer' => trim($custStripeID),
                            //     'amount' => $chargeAmount,
                            //     'currency' => 'usd',
                            //     'receipt_email' => $custEmail,
                            //     'payment_method' => $custStripeCardID,
                            //     'confirm' => true,
                            //     'application_fee_amount' => ($platformfee * 100),
                            //     'description' => $defaultInvoice,
                            //     'stripe_account' => $accConID
                            // ]);

                            $payment_intent =  $stripe->paymentIntents->create([
                                'payment_method_types' => ['card'],
                                'customer' => trim($custStripeID),
                                'amount' => $chargeAmount,
                                'currency' => 'usd',
                                'receipt_email' => $custEmail,
                                'payment_method' => $custStripeCardID,
                                'confirm' => true,
                                'application_fee_amount' => ($platformfee * 100),
                                'description' => $defaultInvoice,
                            ],['stripe_account' => $accConID]);
                            
                            $statusPayment = 'paid';
                            $platformfee_charge = true;
                            $errorstripe = '';
                            $errorStripeMessage = '';
    
                            /* CHECK STATUS PAYMENT INTENTS */
                            $payment_intent_status = (isset($payment_intent->status))?$payment_intent->status:"";
                            if($payment_intent_status == 'requires_action') {
                                // Log::info("payment_intent_status = $payment_intent_status");
                                $statusPayment = 'failed';
                                $platformfee_charge = false;
                                $errorstripe = "Payment for campaign $_leadspeek_api_id was unsuccessful: Stripe status '$payment_intent_status' indicates further user action is needed.";
                                $errorStripeMessage = "Payment unsuccessful: Stripe status '$payment_intent_status' indicates further user action is needed.";
                                // Log::info("errorStripeMessage = $errorStripeMessage");
                            }

                            // Log::info([
                            //     'statusPayment' => $statusPayment,
                            //     'platformfee_charge' => $platformfee_charge,
                            //     'errorstripe' => $errorstripe,
                            // ]);
                            /* CHECK STATUS PAYMENT INTENTS */
                        }
                        /* USE CREDIT_CARD */
                        /* USE BANK_ACCOUNT */
                        else if($usrInfo['payment_type'] == 'bank_account')
                        {
                            // Log::info('CHARGE PERTAMA USE BANK_ACCOUNT');
                            // Log::info([
                            //     'application_fee_amount' => ($platformfee * 100)
                            // ]);

                            $ipAddress = request()->ip(); // Mendapatkan IP pengguna
                            $userAgent = request()->header('User-Agent'); // Mendapatkan user agent pengguna

                            // Log::info([
                            //     'amount' => $chargeAmount,
                            //     'application_fee_amount' => ($platformfee * 100),
                            //     'currency' => 'usd',
                            //     'payment_method_types' => ['us_bank_account'], // Menggunakan ACH
                            //     'customer' => trim($custStripeID), // Customer ID dari session
                            //     'payment_method' => $custStripeCardID, // Bank Account ID dari session
                            //     'confirm' => true, // Mengonfirmasi dan memulai pembayaran
                            //     'off_session' => false, // Pembayaran dilakukan saat sesi pengguna aktif
                            //     'description' => $defaultInvoice,
                            //     'mandate_data' => [
                            //         'customer_acceptance' => [
                            //             'type' => 'online',
                            //             'online' => [
                            //                 'ip_address' => $ipAddress, // Alamat IP pengguna
                            //                 'user_agent' => $userAgent, // User agent pengguna
                            //             ],
                            //         ],
                            //     ],
                            //     'stripe_account' => $accConID
                            // ]);
                            
                            /* PROCESS CHARGE */
                            $payment_intent = $stripe->paymentIntents->create([
                                'amount' => $chargeAmount,
                                'application_fee_amount' => ($platformfee * 100),
                                'currency' => 'usd',
                                'payment_method_types' => ['us_bank_account'], // Menggunakan ACH
                                'customer' => trim($custStripeID), // Customer ID dari session
                                'payment_method' => $custStripeCardID, // Bank Account ID dari session
                                'confirm' => true, // Mengonfirmasi dan memulai pembayaran
                                'off_session' => false, // Pembayaran dilakukan saat sesi pengguna aktif
                                'description' => $defaultInvoice,
                                'mandate_data' => [
                                    'customer_acceptance' => [
                                        'type' => 'online',
                                        'online' => [
                                            'ip_address' => $ipAddress, // Alamat IP pengguna
                                            'user_agent' => $userAgent, // User agent pengguna
                                        ],
                                    ],
                                ],
                                'metadata' => [
                                    'function' => 'createInvoice',
                                    'try_charge_credit_card' => true,
                                    'user' => $usrInfo[0],
                                    'acc_connect_id' => $accConID,
                                    'total_amount' => $totalAmount,
                                    'platformfee' => $platformfee,
                                    'cust_email' => $custEmail,
                                    'default_invoice' => $defaultInvoice,
                                ],
                            ],['stripe_account' => $accConID]);
                            // Log::info('',['payment_intent' => $payment_intent]);
                            $payment_intent_id = $payment_intent->id;
                            $statusPaymentAch = 'pending';
                            $platformfee_charge_ach = true;
                            $errorstripeAch = '';
                            $errorStripeAchMessage = '';
                            /* PROCESS CHARGE */

                            /* CHECK STATUS CHARGE */
                            // for($i = 1; $i <= 10; $i++) {
                            //     sleep(3);
                                
                            //     Log::info("retreive ke-$i");
                            //     $payment_intent  = $stripe->paymentIntents->retrieve(
                            //         $payment_intent_id,
                            //         [],
                            //         ['stripe_account' => $accConID]
                            //     );
                                
                            //     Log::info(['paymentIntent_status' => $payment_intent->status]);
                                
                            //     if($payment_intent->status != 'processing') {
                            //         Log::info('prosess charge bank account selesai');
                            //         break;
                            //     }
                            // }

                            // $statusPaymentAch = 'paid';
                            // $platformfee_charge_ach = true;
                            // $errorstripeAch = '';

                            // $payment_intent_status = (isset($payment_intent->status))?$payment_intent->status:"";
                            // if($payment_intent_status == 'requires_payment_method') {
                            //     $statusPaymentAch = 'failed';
                            //     $platformfee_charge_ach = false;
                            //     $errorstripeAch = $payment_intent->last_payment_error->message;
                            //     $errorStripeAchMessage = $errorstripeAch;
                            // }

                            // Log::info([
                            //     'statusPaymentAch' => $statusPaymentAch,
                            //     'platformfee_charge_ach' => $platformfee_charge_ach,
                            //     'errorstripeAch' => $errorstripeAch,
                            // ]);
                            /* CHECK STATUS CHARGE */
                        }
                        /* USE BANK_ACCOUNT */

                        $paymentintentID = (isset($payment_intent->id))?$payment_intent->id:"";

                        if(($statusPayment == 'paid' && $platformfee_charge) || ($statusPaymentAch == 'paid' && $platformfee_charge_ach)) {
                            /** TRANSFER SALES COMMISSION IF ANY */
                            $_cleanProfit = "";
                            if($rootFee != "0" && $rootFee != "") {
                                $_cleanProfit = $platformfee_ori - $rootFee;
                            }
                            $salesfee = $this->transfer_commission_sales($usrInfo['company_parent'],$platformfee,$_leadspeek_api_id,$startBillingDate,$endBillingDate,$stripeseckey,$ongoingLeads,$_cleanProfit);
                            $salesfeeresult = json_decode($salesfee);
                            $platform_paymentintentID = $salesfeeresult->payment_intentID;
                            $sr_id = $salesfeeresult->srID;
                            $ae_id = $salesfeeresult->aeID;
                            $ar_id = $salesfeeresult->arID;
                            $sales_fee = $salesfeeresult->salesfee;
                            /** TRANSFER SALES COMMISSION IF ANY */
                        }
                    }catch (RateLimitException $e) {
                        if($usrInfo['payment_type'] == 'credit_card') {
                            $statusPayment = 'failed';
                            $platformfee_charge = false;
                            // Too many requests made to the API too quickly
                            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeMessage = $e->getMessage();
                        } else if($usrInfo['payment_type']) {
                            $statusPaymentAch = 'failed';
                            $platformfee_charge_ach = false;
                            // Too many requests made to the API too quickly
                            $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeAchMessage = $e->getMessage();
                        }
                        Log::info(['payment_type1' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                    } catch (InvalidRequestException $e) {
                        if($usrInfo['payment_type'] == 'credit_card') {
                            $statusPayment = 'failed';
                            $platformfee_charge = false;
                            // Invalid parameters were supplied to Stripe's API
                            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeMessage = $e->getMessage();
                        } else if($usrInfo['payment_type'] == 'bank_account') {
                            $statusPaymentAch = 'failed';
                            $platformfee_charge_ach = false;
                            // Invalid parameters were supplied to Stripe's API
                            $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeAchMessage = $e->getMessage();
                        }
                        Log::info(['payment_type2' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                    } catch (AuthenticationException $e) {
                        if($usrInfo['payment_type'] == 'credit_card') {
                            $statusPayment = 'failed';
                            $platformfee_charge = false;
                            // Authentication with Stripe's API failed
                            // (maybe you changed API keys recently)
                            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeMessage = $e->getMessage();
                        } else if($usrInfo['payment_type'] == 'bank_account') {
                            $statusPaymentAch = 'failed';
                            $platformfee_charge_ach = false;
                            // Authentication with Stripe's API failed
                            // (maybe you changed API keys recently)
                            $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeAchMessage = $e->getMessage();
                        }
                        Log::info(['payment_type3' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                    } catch (ApiConnectionException $e) {
                        if($usrInfo['payment_type'] == 'credit_card') {
                            $statusPayment = 'failed';
                            $platformfee_charge = false;
                            // Network communication with Stripe failed
                            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeMessage = $e->getMessage();
                        } else if($usrInfo['payment_type'] == 'bank_account') {
                            $statusPaymentAch = 'failed';
                            $platformfee_charge_ach = false;
                            // Network communication with Stripe failed
                            $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeAchMessage = $e->getMessage();
                        }
                        Log::info(['payment_type4' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                    } catch (ApiErrorException $e) {
                        if($usrInfo['payment_type'] == 'credit_card') {
                            $statusPayment = 'failed';
                            $platformfee_charge = false;
                            // Display a very generic error to the user, and maybe send
                            // yourself an email
                            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeMessage = $e->getMessage();
                        } else if($usrInfo['payment_type'] == 'bank_account') {
                            $statusPaymentAch = 'failed';
                            $platformfee_charge_ach = false;
                            // Display a very generic error to the user, and maybe send
                            // yourself an email
                            $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                            $errorStripeAchMessage = $e->getMessage();
                        }
                        Log::info(['payment_type5' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                    } catch (Exception $e) {
                        if($usrInfo['payment_type'] == 'credit_card') {
                            $statusPayment = 'failed';
                            $platformfee_charge = false;
                            // Something else happened, completely unrelated to Stripe
                            $errorstripe = 'error not stripe things : ' . $e->getMessage();
                            $errorStripeMessage = $e->getMessage();
                        } else if($usrInfo['payment_type'] == 'bank_account') {
                            $statusPaymentAch = 'failed';
                            $platformfee_charge_ach = false;
                            // Something else happened, completely unrelated to Stripe
                            $errorstripeAch = 'error not stripe things : ' . $e->getMessage();
                            $errorStripeAchMessage = $e->getMessage();
                        }
                        Log::info(['payment_type6' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                    }

                    /* JIKA SETELAH CHARGE TERNYATA GAGAL */
                    if(($statusPayment == 'failed' && !$platformfee_charge) || ($statusPaymentAch == 'failed' && !$platformfee_charge_ach)) 
                    {
                        // Log::info("JIKA SETELAH CHARGE TERNYATA GAGAL");
                        try {
                            $payment_intent = [];

                            /* JIKA SEBELUMNYA MENGGUNAKAN CREDIT_CARD, MAKA COBA CHARGE PAKAI BANK_ACCOUNT JIKA ADA */
                            if($usrInfo['payment_type'] == 'credit_card' && $usrInfo['customer_ach_id'] != '')
                            {
                                $user[0]['customer_ach_id'] = (isset($usrInfo['customer_ach_id']))?$usrInfo['customer_ach_id']:'';
                                
                                // Log::info("JIKA SEBELUMNYA MENGGUNAKAN CREDIT_CARD, MAKA COBA CHARGE PAKAI BANK_ACCOUNT JIKA ADA");
                                // Log::info("", ['customer_ach_id' => $user[0]['customer_ach_id']]);
                                
                                $chkStripeUser = $this->check_stripe_customer_ach_platform_exist($user,$accConID);
                                $chkResultUser = json_decode($chkStripeUser);
                                
                                /* PROCESS CHARGE WITH BANK ACCOUNT */
                                if ($chkResultUser->result == 'success') {
                                    $validuser = true;
                                    $custStripeID = $chkResultUser->custStripeID;
                                    $custStripeCardID = $chkResultUser->AchID;
                                
                                    // Log::info('USE BANK_ACCOUNT');

                                    $ipAddress = request()->ip(); // Mendapatkan IP pengguna
                                    $userAgent = request()->header('User-Agent'); // Mendapatkan user agent pengguna

                                    // Log::info([
                                    //     'amount' => $chargeAmount,
                                    //     'application_fee_amount' => ($platformfee * 100),
                                    //     'currency' => 'usd',
                                    //     'payment_method_types' => ['us_bank_account'], // Menggunakan ACH
                                    //     'customer' => trim($custStripeID), // Customer ID dari session
                                    //     'payment_method' => $custStripeCardID, // Bank Account ID dari session
                                    //     'confirm' => true, // Mengonfirmasi dan memulai pembayaran
                                    //     'off_session' => false, // Pembayaran dilakukan saat sesi pengguna aktif
                                    //     'description' => $defaultInvoice,
                                    //     'mandate_data' => [
                                    //         'customer_acceptance' => [
                                    //             'type' => 'online',
                                    //             'online' => [
                                    //                 'ip_address' => $ipAddress, // Alamat IP pengguna
                                    //                 'user_agent' => $userAgent, // User agent pengguna
                                    //             ],
                                    //         ],
                                    //     ],
                                    //     'stripe_account' => $accConID
                                    // ]);
                                    
                                    /* PROCESS CHARGE */
                                    $payment_intent = $stripe->paymentIntents->create([
                                        'amount' => $chargeAmount,
                                        'application_fee_amount' => ($platformfee * 100),
                                        'currency' => 'usd',
                                        'payment_method_types' => ['us_bank_account'], // Menggunakan ACH
                                        'customer' => trim($custStripeID), // Customer ID dari session
                                        'payment_method' => $custStripeCardID, // Bank Account ID dari session
                                        'confirm' => true, // Mengonfirmasi dan memulai pembayaran
                                        'off_session' => false, // Pembayaran dilakukan saat sesi pengguna aktif
                                        'description' => $defaultInvoice,
                                        'mandate_data' => [
                                            'customer_acceptance' => [
                                                'type' => 'online',
                                                'online' => [
                                                    'ip_address' => $ipAddress, // Alamat IP pengguna
                                                    'user_agent' => $userAgent, // User agent pengguna
                                                ],
                                            ],
                                        ],
                                        'metadata' => [
                                            'function' => 'createInvoice',
                                            'try_charge_credit_card' => false,
                                            'user' => $usrInfo[0],
                                            'acc_connect_id' => $accConID,
                                            'total_amount' => $totalAmount,
                                            'platformfee' => $platformfee,
                                            'cust_email' => $custEmail,
                                            'default_invoice' => $defaultInvoice,
                                        ],
                                    ],['stripe_account' => $accConID]);
                                    // Log::info('',['payment_intent' => $payment_intent]);
                                    $payment_intent_id = $payment_intent->id;
                                    $statusPaymentAch = 'pending';
                                    $platformfee_charge_ach = true;
                                    $errorstripeAch = '';
                                    $errorStripeAchMessage = "";
                                    /* PROCESS CHARGE */

                                    /* CHECK STATUS CHARGE */
                                    // for($i = 1; $i <= 10; $i++) {
                                    //     sleep(3);
                                        
                                    //     Log::info("retreive ke-$i");
                                    //     $payment_intent  = $stripe->paymentIntents->retrieve(
                                    //         $payment_intent_id,
                                    //         [],
                                    //         ['stripe_account' => $accConID]
                                    //     );
                                        
                                    //     Log::info(['paymentIntent_status' => $payment_intent->status]);
                                        
                                    //     if($payment_intent->status != 'processing') {
                                    //         Log::info('prosess charge bank account selesai');
                                    //         break;
                                    //     }
                                    // }

                                    // $payment_intent_status = (isset($payment_intent->status))?$payment_intent->status:"";
                                    // if($payment_intent_status == 'requires_payment_method') {
                                    //     $statusPaymentAch = 'failed';
                                    //     $platformfee_charge_ach = false;
                                    //     $errorstripeAch = $payment_intent->last_payment_error->message;
                                    //     $errorStripeAchMessage = $errorstripeAch;
                                    // }

                                    // Log::info([
                                    //     'statusPaymentAch' => $statusPaymentAch,
                                    //     'platformfee_charge_ach' => $platformfee_charge_ach,
                                    //     'errorstripeAch' => $errorstripeAch,
                                    // ]);
                                    /* CHECK STATUS CHARGE */
                                }
                                /* PROCESS CHARGE WITH BANK ACCOUNT */

                                // Log::info([
                                //     'payment_type' => $usrInfo['payment_type'],
                                //     'accConID' => $accConID,
                                //     'validuser' => $validuser,
                                //     'custStripeID' => $chkResultUser->custStripeID,
                                //     'custStripeCardID' => $chkResultUser->AchID,
                                //     'chkStripeUser' => $chkStripeUser
                                // ]);
                            }
                            /* JIKA SEBELUMNYA MENGGUNAKAN CREDIT_CARD, MAKA COBA PAKAI BANK_ACCOUNT */
                            /* JIKA SEBELUMNYA MENGGUNAKAN BANK_ACCOUNT, MAKA COBA PAKAI CREDIT CARD */
                            else if($usrInfo['payment_type'] == 'bank_account' && $usrInfo['customer_card_id'])
                            {
                                $user[0]['customer_card_id'] = (isset($usrInfo['customer_card_id']))?$usrInfo['customer_card_id']:'';

                                // Log::info("JIKA SEBELUMNYA MENGGUNAKAN BANK_ACCOUNT, MAKA COBA PAKAI CREDIT CARD");
                                // Log::info("", ['customer_card_id' => $user[0]['customer_card_id']]);

                                $chkStripeUser = $this->check_stripe_customer_platform_exist($user,$accConID);
                                $chkResultUser = json_decode($chkStripeUser);
                                
                                if ($chkResultUser->result == 'success') {
                                    $validuser = true;
                                    $custStripeID = $chkResultUser->custStripeID;
                                    $custStripeCardID = $chkResultUser->CardID;

                                    // Log::info('USE CREDIT_CARD');
                                    // Log::info([
                                    //     'application_fee_amount' => ($platformfee * 100)
                                    // ]);
                                    
                                    /* PROCESS CHARGE */
                                    $payment_intent =  $stripe->paymentIntents->create([
                                        'payment_method_types' => ['card'],
                                        'customer' => trim($custStripeID),
                                        'amount' => $chargeAmount,
                                        'currency' => 'usd',
                                        'receipt_email' => $custEmail,
                                        'payment_method' => $custStripeCardID,
                                        'confirm' => true,
                                        'application_fee_amount' => ($platformfee * 100),
                                        'description' => $defaultInvoice,
                                    ],['stripe_account' => $accConID]);
                                    /* PROCESS CHARGE */
                                    
                                    $statusPayment = 'paid';
                                    $platformfee_charge = true;
                                    $errorstripe = '';
                                    $errorStripeMessage = "";
            
                                    /* CHECK STATUS PAYMENT INTENTS */
                                    $payment_intent_status = (isset($payment_intent->status))?$payment_intent->status:"";
                                    if($payment_intent_status == 'requires_action') {
                                        Log::info("payment_intent_status = $payment_intent_status");
                                        $statusPayment = 'failed';
                                        $platformfee_charge = false;
                                        $errorstripe = "Payment for campaign $_leadspeek_api_id was unsuccessful: Stripe status '$payment_intent_status' indicates further user action is needed.";
                                        $errorStripeMessage = "Payment unsuccessful: Stripe status '$payment_intent_status' indicates further user action is needed.";
                                    }

                                    // Log::info([
                                    //     'statusPayment' => $statusPayment,
                                    //     'platformfee_charge' => $platformfee_charge,
                                    //     'errorstripe' => $errorstripe,
                                    // ]);
                                    /* CHECK STATUS PAYMENT INTENTS */

                                }

                                // Log::info([
                                //     'payment_type' => $usrInfo['payment_type'],
                                //     'accConID' => $accConID,
                                //     'validuser' => $validuser,
                                //     'custStripeID' => $chkResultUser->custStripeID,
                                //     'custStripeCardID' => $chkResultUser->CardID,
                                //     'chkStripeUser' => $chkStripeUser
                                // ]);
                            }
                            /* JIKA SEBELUMNYA MENGGUNAKAN BANK_ACCOUNT, MAKA COBA PAKAI CREDIT CARD */

                            $paymentintentID = (isset($payment_intent->id))?$payment_intent->id:"";

                            if(($statusPayment == 'paid' && $platformfee_charge) || ($statusPaymentAch == 'paid' && $platformfee_charge_ach)) {
                                /** TRANSFER SALES COMMISSION IF ANY */
                                Log::info("KEDUA TRANSFER SALES COMMISSION IF ANY");
                                $_cleanProfit = "";
                                if($rootFee != "0" && $rootFee != "") {
                                    $_cleanProfit = $platformfee_ori - $rootFee;
                                }
                                $salesfee = $this->transfer_commission_sales($usrInfo['company_parent'],$platformfee,$_leadspeek_api_id,$startBillingDate,$endBillingDate,$stripeseckey,$ongoingLeads,$_cleanProfit);
                                $salesfeeresult = json_decode($salesfee);
                                $platform_paymentintentID = $salesfeeresult->payment_intentID;
                                $sr_id = $salesfeeresult->srID;
                                $ae_id = $salesfeeresult->aeID;
                                $ar_id = $salesfeeresult->arID;
                                $sales_fee = $salesfeeresult->salesfee;
                                /** TRANSFER SALES COMMISSION IF ANY */
                            }
                        } catch (RateLimitException $e) {
                            if($usrInfo['payment_type'] == 'credit_card') {
                                $statusPaymentAch = 'failed';
                                $platformfee_charge_ach = false;
                                // Too many requests made to the API too quickly
                                $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeAchMessage = $e->getMessage();
                            } else if($usrInfo['payment_type'] == 'bank_account') {
                                $statusPayment = 'failed';
                                $platformfee_charge = false;
                                // Too many requests made to the API too quickly
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeMessage = $e->getMessage();
                            }
                            // Log::info(['payment_type11' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                        } catch (InvalidRequestException $e) {
                            if($usrInfo['payment_type'] == 'credit_card') {
                                $statusPaymentAch = 'failed';
                                $platformfee_charge_ach = false;
                                // Invalid parameters were supplied to Stripe's API
                                $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeAchMessage = $e->getMessage();
                            } else if($usrInfo['payment_type'] == 'bank_account') {
                                $statusPayment = 'failed';
                                $platformfee_charge = false;
                                // Invalid parameters were supplied to Stripe's API
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeMessage = $e->getMessage();
                            }
                            // Log::info(['payment_type22' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                        } catch (AuthenticationException $e) {
                            if($usrInfo['payment_type'] == 'credit_card') {
                                $statusPaymentAch = 'failed';
                                $platformfee_charge_ach = false;
                                // Authentication with Stripe's API failed
                                // (maybe you changed API keys recently)
                                $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeAchMessage = $e->getMessage();
                            } else if($usrInfo['payment_type'] == 'bank_account') {
                                $statusPayment = 'failed';
                                $platformfee_charge = false;
                                // Authentication with Stripe's API failed
                                // (maybe you changed API keys recently)
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeMessage = $e->getMessage();
                            }
                            // Log::info(['payment_type33' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                        } catch (ApiConnectionException $e) {
                            if($usrInfo['payment_type'] == 'credit_card') {
                                $statusPaymentAch = 'failed';
                                $platformfee_charge_ach = false;
                                // Network communication with Stripe failed
                                $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeAchMessage = $e->getMessage();
                            } else if($usrInfo['payment_type'] == 'bank_account') {
                                $statusPayment = 'failed';
                                $platformfee_charge = false;
                                // Network communication with Stripe failed
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeMessage = $e->getMessage();
                            }
                            // Log::info(['payment_type44' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                        } catch (ApiErrorException $e) {
                            if($usrInfo['payment_type'] == 'credit_card') {
                                $statusPaymentAch = 'failed';
                                $platformfee_charge_ach = false;
                                // Display a very generic error to the user, and maybe send
                                // yourself an email
                                $errorstripeAch = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeAchMessage = $e->getMessage();
                            } else if($usrInfo['payment_type'] == 'bank_account') {
                                $statusPayment = 'failed';
                                $platformfee_charge = false;
                                // Display a very generic error to the user, and maybe send
                                // yourself an email
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $errorStripeMessage = $e->getMessage();
                            }
                            // Log::info(['payment_type55' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                        } catch (Exception $e) {
                            if($usrInfo['payment_type'] == 'credit_card') {
                                $statusPaymentAch = 'failed';
                                $platformfee_charge_ach = false;
                                // Something else happened, completely unrelated to Stripe
                                $errorstripeAch = 'error not stripe things : ' . $e->getMessage();
                                $errorStripeAchMessage = $e->getMessage();
                            } else if($usrInfo['payment_type'] == 'bank_account') {
                                $statusPayment = 'failed';
                                $platformfee_charge = false;
                                // Something else happened, completely unrelated to Stripe
                                $errorstripe = 'error not stripe things : ' . $e->getMessage();
                                $errorStripeMessage = $e->getMessage();
                            }
                            // Log::info(['payment_type66' => $usrInfo['payment_type'], 'error' => $e->getMessage()]);
                        }
                    }
                    /* JIKA SETELAH CHARGE TERNYATA GAGAL */

                }else{
                    $statusPayment = 'paid';
                    $platformfee_charge = false;
                    $errorstripe = "This direct Agency Bill Method";
                }
            }

            /* WRITE METADATA IN DATABASE */
            if($usePaymentMethod == 'bank_account' && $statusPaymentAch == 'pending')
            {
                $metadataStripe = array_merge($metadataStripe, [
                    'function' => 'createInvoice',
                    'try_charge_credit_card' => false,
                    'user' => $usrInfo[0],
                    'acc_connect_id' => $accConID,
                    'total_amount' => $totalAmount,
                    'platformfee' => $platformfee,
                    'cust_email' => $custEmail,
                    'default_invoice' => $defaultInvoice,
                ]);
            }
            /* WRITE METADATA IN DATABASE */

            $cardinfo = "";
            $cardlast = "";

            if ($AgencyManualBill == "T") {
                $custStripeID = $custAgencyStripeID;
                $custStripeCardID = $custAgencyStripeCardID;

                $cardinfo = $stripe->customers->retrieveSource(trim($custStripeID),trim($custStripeCardID),[]);
                $cardlast = $cardinfo->last4;
            }else {
                $cardinfo = $stripe->customers->retrieveSource(trim($custStripeID),trim($custStripeCardID),[],['stripe_account' => $accConID]);
                $cardlast = $cardinfo->last4;
            }

            if ($AgencyManualBill == "T") {
                $custStripeID = $custAgencyStripeID;
                $custStripeCardID = $custAgencyStripeCardID;
            }
            
            /** CHECK IF FAILED CHARGE CLIENT WE STILL CHARGE THE AGENCY */
            //if ($statusPayment == 'failed' && $platformfee_charge == false && $platformfee >= 0.5) {
            if ($platformfee_charge == false && $platformfee_charge_ach == false && $platformfee >= 0.5) {
                $_cleanProfit = "";
                if($rootFee != "0" && $rootFee != "") {
                    $_cleanProfit = $platformfee_ori - $rootFee;
                }
                $agencystripe = $this->check_agency_stripeinfo($usrInfo['company_parent'],$platformfee,$_leadspeek_api_id,'Agency ' . $defaultInvoice,$startBillingDate,$endBillingDate,$ongoingLeads,$_cleanProfit);
                $agencystriperesult = json_decode($agencystripe);

                if (isset($agencystriperesult->result) && $agencystriperesult->result == 'success') {
                    //$statusPayment = $agencystriperesult->statusPayment;
                    $platform_paymentintentID = $agencystriperesult->payment_intentID;
                    $sr_id = $agencystriperesult->srID;
                    $ae_id = $agencystriperesult->aeID;
                    $ar_id = $agencystriperesult->arID;
                    $sales_fee = $agencystriperesult->salesfee;
                    $platformfee = 0;
                    $platform_errorstripe = '';
                }else{
                    //$statusPayment = $agencystriperesult->statusPayment;
                    $platform_paymentintentID = $agencystriperesult->payment_intentID;
                    $platform_errorstripe .= $agencystriperesult->error;
                }
            }
            /** CHECK IF FAILED CHARGE CLIENT WE STILL CHARGE THE AGENCY */

            /** CHARGE ROOT FEE AGENCY */
            if($rootFee != "0" && $rootFee != "") {
                $rootCommissionFee = 0;
                $rootCommissionFee = ($usrInfo['leadspeek_type'] == 'enhance')?($platformfee * 0.05):($rootFee * 0.05);
                $rootCommissionFee = number_format($rootCommissionFee,2,'.','');

                $cleanProfit = $platformfee_ori - $rootFee;
                // Log::info([
                //     'cleanProfit' => $cleanProfit,
                //     'platformfee_ori' => $platformfee_ori,
                //     'rootFee' => $rootFee,
                // ]);
                //if ($cleanProfit > 0.5) {
                    /** GET ROOT CONNECTED ACCOUNT TO BE TRANSFER FOR CLEAN PROFIT AFTER CUT BY ROOT FEE COST */
                    $rootAccCon = "";
                    $rootAccConMob = "";
                    $rootCommissionSRAcc = "";
                    $rootCommissionAEAcc = "";
                    $rootCommissionSRAccVal = $rootCommissionFee;
                    $rootCommissionAEAccVal = $rootCommissionFee;

                    $rootAccConResult = $this->getcompanysetting($usrInfo['company_root_id'],'rootfee');
                    if ($rootAccConResult != '') {
                        $rootAccCon = (isset($rootAccConResult->rootfeeaccid))?$rootAccConResult->rootfeeaccid:"";
                        $rootAccConMob = (isset($rootAccConResult->rootfeeaccidmob))?$rootAccConResult->rootfeeaccidmob:"";
                        $rootCommissionSRAcc = (isset($rootAccConResult->rootcomsr))?$rootAccConResult->rootcomsr:"";
                        $rootCommissionAEAcc = (isset($rootAccConResult->rootcomae))?$rootAccConResult->rootcomae:"";
                        /** OVERRIDE IF EXIST ANOTHER VALUE NOT 5% from Root FEE */
                        if ($usrInfo['leadspeek_type'] == 'enhance') {
                            if (isset($rootAccConResult->rootcomfee) && $rootAccConResult->rootcomfee != "") {
                                $rootCommissionSRAcc = $rootAccConResult->rootcomfee;
                                $rootAccConResult->rootcomsrval = $rootAccConResult->rootcomfeeval;
                            }
                            if (isset($rootAccConResult->rootcomfee1) && $rootAccConResult->rootcomfee1 != "") {
                                $rootCommissionAEAcc = $rootAccConResult->rootcomfee1;
                                $rootAccConResult->rootcomaeval = $rootAccConResult->rootcomfeeval1;
                            }
                        }

                        if (isset($rootAccConResult->rootcomsrval) && $rootAccConResult->rootcomsrval != "") {
                            $_rootFee = ($usrInfo['leadspeek_type'] == 'enhance')?$platformfee:$rootFee;
                            $rootCommissionSRAccVal = ($_rootFee * (float) $rootAccConResult->rootcomsrval);
                            $rootCommissionSRAccVal = number_format($rootCommissionSRAccVal,2,'.','');

                            // Log::info([
                            //     'action' => 'calculate rootCommissionSRAccVal',
                            //     'rootCommissionSRAccVal' => $rootCommissionSRAccVal,
                            //     '_rootFee' => $_rootFee,
                            //     'rootAccConResult->rootcomsrval' => $rootAccConResult->rootcomsrval
                            // ]);
                        }
                        if (isset($rootAccConResult->rootcomaeval) && $rootAccConResult->rootcomaeval != "") {
                            $_rootFee = ($usrInfo['leadspeek_type'] == 'enhance')?$platformfee:$rootFee;
                            $rootCommissionAEAccVal = ($_rootFee * (float) $rootAccConResult->rootcomaeval);
                            $rootCommissionAEAccVal = number_format($rootCommissionAEAccVal,2,'.','');

                            // Log::info([
                            //     'action' => 'calculate rootCommissionAEAccVal',
                            //     'rootCommissionAEAccVal' => $rootCommissionAEAccVal,
                            //     '_rootFee' => $_rootFee,
                            //     '$rootAccConResult->rootcomaeval' => "$rootAccConResult->rootcomaeval%",
                            // ]);
                        }
                        /** OVERRIDE IF EXIST ANOTHER VALUE NOT 5% from Root FEE */
                    }
                    /** GET ROOT CONNECTED ACCOUNT TO BE TRANSFER FOR CLEAN PROFIT AFTER CUT BY ROOT FEE COST */
                    if ($rootAccCon != "" && $cleanProfit > 0.5) {
                        
                        $cleanProfit = number_format($cleanProfit,2,'.','');

                        $stripe = new StripeClient([
                            'api_key' => $stripeseckey,
                            'stripe_version' => '2020-08-27'
                        ]);

                        try {
                            if($usrInfo['leadspeek_type'] == 'enhance') {
                                //if(isset($rootAccConResult->feepercentagemob)) {
                                    // if root mobile
                                    if(isset($rootAccConResult->feepercentagemob) && $rootAccConResult->feepercentagemob != "") {
                                        // calculation cleanProfit for mobile
                                        $feePercentageMob = (isset($rootAccConResult->feepercentagemob))?$rootAccConResult->feepercentagemob:0;
                                        $cleanProfitMob = ($platformfee_ori * $feePercentageMob) / 100;
                                        $cleanProfitMob = number_format($cleanProfitMob,2,'.','');

                                        // send cleanProfit to mobile
                                        $transferRootProfit = $stripe->transfers->create([
                                            'amount' => ($cleanProfitMob * 100),
                                            'currency' => 'usd',
                                            'destination' => $rootAccConMob,
                                            'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                        ]);

                                        // Log::info([
                                        //     'action' => 'TRANSFER FOR ROOT FEE COMISSION',
                                        //     'leadspeek_type' => $usrInfo['leadspeek_type'],
                                        //     'action' => 'pembagian ke mobile jika enhance 1.1',
                                        //     'rootAccConMob' => $rootAccConMob,
                                        //     'feePercentageMob' => $feePercentageMob,
                                        //     'cleanProfitMob' => $cleanProfitMob,
                                        //     'amount' => ($cleanProfitMob * 100),
                                        //     'currency' => 'usd',
                                        //     'destination' => $rootAccConMob,
                                        //     'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                        // ]);
                                    }

                                    if(isset($rootAccConResult->feepercentagedom) && $rootAccConResult->feepercentagedom != "") {
                                        // calculation cleanProfit for dominator
                                        $feePercentageDom = (isset($rootAccConResult->feepercentagedom))?$rootAccConResult->feepercentagedom:0;
                                        $cleanProfitDom = ($platformfee_ori * $feePercentageDom) / 100;
                                        $cleanProfitDom = number_format($cleanProfitDom,2,'.','');

                                        // // send cleanProfit to dominator
                                        $transferRootProfit = $stripe->transfers->create([
                                            'amount' => ($cleanProfitDom * 100),
                                            'currency' => 'usd',
                                            'destination' => $rootAccCon,
                                            'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                        ]);

                                        // Log::info([
                                        //     'action' => 'TRANSFER FOR ROOT FEE COMISSION',
                                        //     'leadspeek_type' => $usrInfo['leadspeek_type'],
                                        //     'action' => 'pembagian ke dominator jika enhance 1.2',
                                        //     'rootAccCon' => $rootAccCon,
                                        //     'feePercentageDom' => $feePercentageDom,
                                        //     'cleanProfitDom' => $cleanProfitDom,
                                        //     'amount' => ($cleanProfitDom * 100),
                                        //     'currency' => 'usd',
                                        //     'destination' => $rootAccCon,
                                        //     'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                        // ]);
                                    }
                                // }
                                // else if(isset($rootAccConResult->feepercentagedom)) {
                                //     // if root dominator
                                //     // calculation cleanProfit for dominator
                                //     $feePercentageDom = (isset($rootAccConResult->feepercentagedom))?$rootAccConResult->feepercentagedom:0;
                                //     $cleanProfit = ($platformfee_ori * $feePercentageDom) / 100;
                                //     $cleanProfit = number_format($cleanProfit,2,'.','');

                                //     // Log::info("cleanProfit = $cleanProfit");
                                    
                                //     // Log::info([
                                //     //     'action' => 'TRANSFER FOR ROOT FEE COMISSION',
                                //     //     'leadspeek_type' => $usrInfo['leadspeek_type'],
                                //     //     'action' => 'pembagian ke dominator jika enhance 2',
                                //     //     'platformfee_ori' => $platformfee_ori,
                                //     //     'feePercentageDom' => $feePercentageDom,
                                //     //     'cleanProfit' => $cleanProfit,
                                //     //     'amount' => ($cleanProfit * 100),
                                //     //     'currency' => 'usd',
                                //     //     'destination' => $rootAccCon,
                                //     //     'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                //     // ]);

                                //     // send cleanProfit to dominator
                                //     $transferRootProfit = $stripe->transfers->create([
                                //         'amount' => ($cleanProfit * 100),
                                //         'currency' => 'usd',
                                //         'destination' => $rootAccCon,
                                //         'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                //     ]);
                                // }
                            }else {
                                // Log::info([
                                //     'action' => 'TRANSFER FOR ROOT FEE COMISSION',
                                //     'leadspeek_type' => $usrInfo['leadspeek_type'],
                                //     'amount' => ($cleanProfit * 100),
                                //     'currency' => 'usd',
                                //     'destination' => $rootAccCon,
                                //     'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                // ]);
                                $transferRootProfit = $stripe->transfers->create([
                                    'amount' => ($cleanProfit * 100),
                                    'currency' => 'usd',
                                    'destination' => $rootAccCon,
                                    'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                                ]);
                            }

                            // if (isset($transferSales->destination_payment)) {
                            //     $despay = $transferSales->destination_payment;

                            //     $transferSalesDesc =  $stripe->charges->update($despay,
                            //             [
                            //                 'description' => 'Profit Root App from Agency Invoice'
                            //             ],['stripe_account' => $rootAccCon]);
                            // }
                            
                            //$this->send_email(array($sale['email']),$from,'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.salesfee',$companyParentID);
                        }catch (Exception $e) {
                            $this->send_notif_stripeerror('Profit Root Transfer Error','Profit Root Transfer Error to ' . $rootAccCon ,$usrInfo['company_root_id']);
                        }

                    }

                    /** TRANSFER FOR ROOT SALES Representative */
                    if ($rootCommissionSRAcc != "" && $rootCommissionSRAccVal > 0.5) {
                        $stripe = new StripeClient([
                            'api_key' => $stripeseckey,
                            'stripe_version' => '2020-08-27'
                        ]);

                        try {
                            $transferRootProfitSRAcc = $stripe->transfers->create([
                                'amount' => ($rootCommissionSRAccVal * 100),
                                'currency' => 'usd',
                                'destination' => $rootCommissionSRAcc,
                                'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                            ]);

                            // Log::info([
                            //     'action' => 'TRANSFER FOR ROOT SALES Representative',
                            //     'amount' => ($rootCommissionSRAccVal * 100),
                            //     'currency' => 'usd',
                            //     'destination' => $rootCommissionSRAcc,
                            //     'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                            // ]);

                            
                            //$this->send_email(array($sale['email']),$from,'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.salesfee',$companyParentID);
                        }catch (Exception $e) {
                            $this->send_notif_stripeerror('Commission Root Transfer Error','Profit Root Transfer Error to ' . $rootCommissionSRAcc ,$usrInfo['company_root_id']);
                        }
                    }
                    /** TRANSFER FOR ROOT SALES Representative */

                    /** TRANSFER FOR ROOT Account Executive */
                    if ($rootCommissionAEAcc != "" && $rootCommissionAEAccVal > 0.5) {
                        $stripe = new StripeClient([
                            'api_key' => $stripeseckey,
                            'stripe_version' => '2020-08-27'
                        ]);

                        try {
                            $transferRootProfitAEAcc = $stripe->transfers->create([
                                'amount' => ($rootCommissionAEAccVal * 100),
                                'currency' => 'usd',
                                'destination' => $rootCommissionAEAcc,
                                'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                            ]);

                            // Log::info([
                            //     'action' => 'TRANSFER FOR ROOT Account Executive',
                            //     'amount' => ($rootCommissionAEAccVal * 100),
                            //     'currency' => 'usd',
                            //     'destination' => $rootCommissionAEAcc,
                            //     'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                            // ]);

                            
                            //$this->send_email(array($sale['email']),$from,'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.salesfee',$companyParentID);
                        }catch (Exception $e) {
                            $this->send_notif_stripeerror('Commission Root Transfer Error','Profit Root Transfer Error to ' . $rootCommissionAEAcc ,$usrInfo['company_root_id']);
                        }
                    }
                   /** TRANSFER FOR ROOT Account Executive */

                //}
            }
            /** CHARGE ROOT FEE AGENCY */

        }
        /** CHARGE WITH STRIPE */

        if (trim($sr_id) == "") {
            $sr_id = 0;
        }
        if (trim($ae_id) == "") {
            $ae_id = 0;
        }

        if (trim($ar_id) == "") {
            $ar_id = 0;
        }

        $statusClientPayment = $statusPayment;
        $statusClientPaymentAch = $statusPaymentAch;

        $invoiceCreated = LeadspeekInvoice::create([
            'company_id' => $_company_id,
            'user_id' => $_user_id,
            'leadspeek_api_id' => $_leadspeek_api_id,
            'invoice_number' => '',
            'payment_term' => $clientPaymentTerm,
            'onetimefee' => 0,
            'platform_onetimefee' => $platform_LeadspeekPlatformFee,
            'min_leads' => $minLeads,
            'exceed_leads' => $exceedLeads,
            'total_leads' => $ongoingLeads,
            'min_cost' => $minCostLeads,
            'platform_min_cost' => $platform_LeadspeekMinCostMonth,
            'cost_leads' => $costLeads,
            'platform_cost_leads' => $platform_LeadspeekCostperlead,
            'total_amount' => $totalAmount,
            'platform_total_amount' => $platformfee_ori,
            'root_total_amount' => $rootFee,
            'status' => $statusPayment,
            'status_ach' => $statusClientPaymentAch,
            'customer_payment_id' => $paymentintentID,
            'customer_stripe_id' => $custStripeID,
            'customer_card_id' => $custStripeCardID,
            'platform_customer_payment_id' => $platform_paymentintentID,
            'error_payment' => $errorstripe,
            'error_ach_payment' => $errorstripeAch,
            'platform_error_payment' => $platform_errorstripe,
            'invoice_date' => $todayDate,
            'invoice_start' => date('Y-m-d H:i:s',strtotime($startBillingDate)),
            'invoice_end' => date('Y-m-d H:i:s',strtotime($endBillingDate)),
            'sent_to' => json_encode($reportSentTo),
            'sr_id' => $sr_id,
            'sr_fee' => $sales_fee,
            'ae_id' => $ae_id,
            'ae_fee' => $sales_fee,
            'ar_id' => $ar_id,
            'ae_fee' => $sales_fee,
            'active' => 'T',
        ]);
        $invoiceID = $invoiceCreated->id;

        $invoice = LeadspeekInvoice::find($invoiceID);
        $invoice->invoice_number = $invoiceNum . '-' . $invoiceID;
        $invoice->save();

        $lpupdate = LeadspeekUser::find($_lp_user_id);
        $lpupdate->ongoing_leads = 0;
        //$lpupdate->start_billing_date = $todayDate;
        //$lpupdate->start_billing_date = date('Y-m-d H:i:s',strtotime($endBillingDate));

        // ubah format 'YmdHis' jadi 'Y-m-d H:i:s', tambah 1 hari dari tanggal $endBillingDate, lalu waktunya menjadi awal jam 00:00:00
        // contoh, $endBillingDate awalnya '20240912235959', akan berubah menjadi '2024-09-13 00:00:00'
        $updateStartBillingDate = Carbon::createFromFormat('YmdHis', $endBillingDate)->addDay()->startOfDay();
        
        // $lpupdate->start_billing_date = date('Y-m-d H:i:s',strtotime($endBillingDate));
        $lpupdate->start_billing_date = $updateStartBillingDate;
        $lpupdate->lifetime_cost = ($lpupdate->lifetime_cost + $totalAmount);
        $lpupdate->save();
        
        /** FIND ADMIN EMAIL */
        $tmp = User::select('email')->whereIn('id', $adminnotify)->get();
        $adminEmail = array();
        foreach($tmp as $ad) {
            array_push($adminEmail,$ad['email']);
        }
        array_push($adminEmail,'harrison@uncommonreach.com');
        /** FIND ADMIN EMAIL */

        if ($statusPayment == 'paid') {
            $statusPayment = "Customer's Credit Card Successfully Charged ";
        }else if ($statusPayment == 'failed') {
            $statusPayment = "Customer's Credit Card Failed";
            /** FIND ADMIN EMAIL */
                $tmp = User::select('email')->where('admin_get_notification','=','T')->whereIn('id', $adminnotify)->get();
                $adminEmail = array();
                foreach($tmp as $ad) {
                    array_push($adminEmail,$ad['email']);
                }
                array_push($adminEmail,'harrison@uncommonreach.com');
            /** FIND ADMIN EMAIL */
        }else{
            $statusPayment = "Customer's Credit Card Successfully Charged ";
        }

        if ($totalAmount == '0.00' || $totalAmount == '0') {
            $statusPayment = "Customer's Credit Card Successfully Charged";
        }

        if ($platformfee_ori != '0.00' || $platformfee_ori != '0') {
            if ($statusClientPayment == 'failed') {
                $statusPayment .= " and Agency's Card Charged For Overage";
            }
        }

        if ($AgencyManualBill == "T") {
            $statusPayment = "You must directly bill your client the amount due.";
        }
        
        $platform_LeadspeekCostperlead = number_format($platform_LeadspeekCostperlead,2,'.','');

        $agencyNet = "";
        if ($totalAmount > $platformfee_ori) {
            $agencyNet = $totalAmount - $platformfee_ori;
            $agencyNet = number_format($agencyNet,2,'.','');
        }

        $AdminDefault = $this->get_default_admin($usrInfo['company_root_id']);
        $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';
        $rootCompanyInfo = $this->getCompanyRootInfo($usrInfo['company_root_id']);
        $defaultdomain = $this->getDefaultDomainEmail($usrInfo['company_root_id']);

        $details = [
            'name'  => $companyName,
            'invoiceNumber' => $invoiceNum . '-' . $invoiceID,
            'min_leads' => $minLeads,
            'exceed_leads' => $exceedLeads,
            'total_leads' => $ongoingLeads,
            'min_cost' => $minCostLeads,
            'platform_min_cost' => $platform_LeadspeekMinCostMonth,
            'cost_leads' => $costLeads,
            'platform_cost_leads' => $platform_LeadspeekCostperlead,
            'total_amount' => $totalAmount,
            'platform_total_amount' => $platformfee_ori,
            'invoiceDate' => date('m-d-Y',strtotime($todayDate)),
            'startBillingDate' => date('m-d-Y H:i:s',strtotime($startBillingDate)),
            'endBillingDate' =>  date('m-d-Y H:i:s',strtotime($endBillingDate)),
            'invoiceStatus' => $statusPayment,
            'cardlast' => trim($cardlast),
            'leadspeekapiid' => $_leadspeek_api_id,
            'paymentterm' => $clientPaymentTerm,
            'invoicetype' => 'agency',
            'agencyname' => $rootCompanyInfo['company_name'],
            'defaultadmin' => $AdminDefaultEmail,
            'agencyNet' => $agencyNet,
            'rootFee' => $rootFee,
            'cleanProfit' => $cleanProfit,
        ];
        $attachement = array();

        $from = [
            'address' => 'noreply@' . $defaultdomain,
            'name' => 'Invoice',
            'replyto' => 'support@' . $defaultdomain,
        ];

        $subjectFailed = "";
        if ($statusClientPayment == 'failed' || $statusClientPaymentAch == 'failed') {
            $subjectFailed = "Failed Payment - ";
        }

        /* ADD INVOICE_ID AND COMPANY_NAME IF PAYMENT TYPE BANK ACCOUNT*/
        if($usePaymentMethod == 'bank_account' && $statusClientPaymentAch == 'pending') {
            $metadataStripe = array_merge($metadataStripe, [
                'company_name' => $companyName,
                'attachement' => $attachement,
                'details1' => $details,
                'from1' => $from,
            ]);
            // try {
            //     Log::info("update metadata if bank_account 1");
            //     $stripe->paymentIntents->update(
            //         $paymentintentID, 
            //         [
            //             'metadata' => [
            //                 'invoice_id' => $invoiceID,
            //                 'company_name' => $companyName,
            //                 'attachement' => $attachement,
            //                 'details1' => $details,
            //                 'from1' => $from,
            //             ]
            //         ],
            //         ['stripe_account' => $accConID]
            //     );
            // } catch (\Exception $e) {
            //     Log::error(['error' => $e->getMessage()]);
            // }
        }
        /* ADD INVOICE_ID AND COMPANY_NAME IF PAYMENT TYPE BANK ACCOUNT*/

        if($statusClientPayment != 'pending') {
            $this->send_email($adminEmail,$subjectFailed . 'Invoice for ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',$details,$attachement,'emails.tryseramatchlistinvoice',$from,$companyParent);
        }
        //if (($platformfee_ori != '0.00' || $platformfee_ori != '0') || ($totalAmount != '0.00' || $totalAmount != '0') ) {
        //}
        
        /** UPDATE DESC PROFIT ROOT */
        if (isset($transferRootProfit->destination_payment)) {
            $despay = $transferRootProfit->destination_payment;
            try {
                $transferSalesDesc =  $stripe->charges->update($despay,
                        [
                            'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')'
                        ],['stripe_account' => $rootAccCon]);
            }catch (Exception $e) {
                Log::warning('ERROR : Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ') - ' . $despay);
            }
        }

        /** UPDATE DESC SA COMMISSION */
        if (isset($transferRootProfitSRAcc->destination_payment)) {
            $despay = $transferRootProfitSRAcc->destination_payment;
            try {
                $transferSalesDesc =  $stripe->charges->update($despay,
                        [
                            'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')'
                        ],['stripe_account' => $rootCommissionSRAcc]);
            }catch (Exception $e) {
                Log::warning('Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ') - SA -' . $despay);
            }
        }

        /** UPDATE DESC AE COMMISSION */
        if (isset($transferRootProfitAEAcc->destination_payment)) {
            $despay = $transferRootProfitAEAcc->destination_payment;
            try {
                $transferSalesDesc =  $stripe->charges->update($despay,
                        [
                            'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')'
                        ],['stripe_account' => $rootCommissionAEAcc]);
            }catch (Exception $e) {
                Log::warning('Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ') - AE -' . $despay);
            }
        }
        
        /** GET ROOT ADMIN */
        // if ($rootAccCon != "") {
        //     $rootAdmin = User::select('email')
        //                             ->where('company_id','=',$usrInfo['company_root_id'])
        //                             ->where('isAdmin','=','T')
        //                             ->where(function ($query) {
        //                                 $query->where('user_type','=','userdownline')
        //                                 ->orWhere('user_type','=','user');
        //                             })->get();
        //     /** GET ROOT ADMIN */
        //     foreach($rootAdmin as $radm) {
        //         $this->send_email(array($radm['email']),'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')' ,$details,$attachement,'emails.rootProfitTransfer',$from,$usrInfo['company_parent']);
        //     }
        // }
        /** UPDATE ROOT FEE PAYMENT */

        $agencyCompanyID = $usrInfo['company_parent'];

        if (trim($companyParent) != "") {
            $agencyCompanyID = trim($companyParent);
        }

        $AdminDefault = $this->get_default_admin($agencyCompanyID);
        $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'newleads@leadspeek.com';

        $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                        ->where('id','=',$agencyCompanyID)
                                        ->get();
        $agencyname = "";
        
        if (count($agencycompany) > 0) {
            $agencyname = $agencycompany[0]['company_name'];
        }

        $details['invoicetype'] = 'client';
        $details['agencyname'] = $agencyname;
        $details['defaultadmin'] = $AdminDefaultEmail;
        $details['invoiceStatus'] = str_replace("and Agency's Card Charged For Overage","",$details['invoiceStatus']);

        $from = [
            'address' => $AdminDefaultEmail,
            'name' => 'Invoice',
            'replyto' => $AdminDefaultEmail,
        ];

        /* ADD INVOICE_ID AND COMPANY_NAME IF PAYMENT TYPE BANK ACCOUNT*/
        if($usePaymentMethod == 'bank_account' && $statusClientPaymentAch == 'pending') {
            $metadataStripe = array_merge($metadataStripe, [
                'details2' => $details,
                'from2' => $from,
            ]);

            /* ADD TO DATABASE */
            MetadataStripe::create([
                'invoice_id' => $invoiceID,
                'metadata' => json_encode($metadataStripe)
            ]);
            /* ADD TO DATABASE */

            try {
                Log::info("update metadata if bank_account 2");
                $stripe->paymentIntents->update(
                    $paymentintentID, 
                    [
                        'metadata' => [
                            'invoice_id' => $invoiceID
                        ]
                    ],
                    ['stripe_account' => $accConID]
                );
            } catch (\Exception $e) {
                Log::error(['error' => $e->getMessage()]);
            }
            // try {
            //     Log::info("update metadata if bank_account 2");
            //     $stripe->paymentIntents->update(
            //         $paymentintentID, 
            //         [
            //             'metadata' => [
            //                 'details2' => $details,
            //                 'from2' => $from,
            //             ]
            //         ],
            //         ['stripe_account' => $accConID]
            //     );
            // } catch (\Exception $e) {
            //     Log::error(['error' => $e->getMessage()]);
            // }
        }
        /* ADD INVOICE_ID AND COMPANY_NAME IF PAYMENT TYPE BANK ACCOUNT*/

        if($statusClientPaymentAch != 'pending') {
            $this->send_email(array($custEmail),'Invoice for ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',$details,$attachement,'emails.tryseramatchlistinvoice',$from,$companyParent);
        }
        /** SENT FOR CLIENT INVOICE */

        /** UPDATE DESCRIPTION OF PAYMENT ON STRIPE */

        /** UPDATE CLIENT STRIPE PAYMENT DESCRIPTION */
        if ($paymentintentID != "") {
            $updatePaymentClientDesc =  $stripe->paymentIntents->update($paymentintentID,
                [
                    'description' => '#' . $invoiceNum . '-' . $invoiceID . ' ' . $companyParentName . '-' . $companyName . ' #' . $_leadspeek_api_id,
                ],['stripe_account' => $accConID]);
            /** UPDATE CLIENT STRIPE PAYMENT DESCRIPTION */
        }
        if ($platform_paymentintentID != "") {
            /** UPDATE AGENCY STRIPE PAYMENT DESCRIPTION */
            $updatePaymentClientDesc =  $stripe->paymentIntents->update($platform_paymentintentID,
                [
                    'description' => 'Agency #' . $invoiceNum . '-' . $invoiceID . ' ' . $companyParentName . '-' . $companyName . ' #' . $_leadspeek_api_id,
                ]);
            /** UPDATE AGENCY STRIPE PAYMENT DESCRIPTION */
        }

        /** UPDATE DESCRIPTION OF PAYMENT ON STRIPE */

        /** CHECK IF FAILED PAYMENT THEN PAUSED THE CAMPAIGN AND SENT EMAIL*/
        // Log::info(['statusClientPayment' => $statusClientPayment, 'statusClientPaymentAch' => $statusClientPaymentAch]);
        if ($statusClientPayment == 'failed' || $statusClientPaymentAch == 'failed') {
            $ClientCompanyIDFailed = "";
            $ListFailedCampaign = "";
            $_ListFailedCampaign = "";
            $_failedUserID = "";

            $leadsuser = LeadspeekUser::select('leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.trysera','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id','users.company_id')
                                ->join('users','leadspeek_users.user_id','=','users.id')
                                ->where('leadspeek_users.leadspeek_api_id','=',$_leadspeek_api_id)
                                ->get();

            // Log::info('', [
            //     'leadsuser' => $leadsuser
            // ]);

            $updateUser = User::find($leadsuser[0]['user_id']);
            
            if($statusClientPayment == 'failed') {
                $updateUser->payment_status = 'failed';
                $updateUser->payment_message_error = $errorStripeMessage;
                // Log::info([
                //     'errorStripeMessage' => $errorStripeMessage,
                //     'payment_message_error' => $updateUser->payment_message_error,
                // ]);
                $updateUser->save();
            }
            if($statusClientPaymentAch == 'failed') {
                $updateUser->payment_ach_status = 'failed';
                $updateUser->payment_ach_message_error = $errorStripeAchMessage;
                // Log::info([
                //     'errorStripeAchMessage' => $errorStripeAchMessage,
                //     'payment_ach_message_error' => $updateUser->payment_ach_message_error,
                // ]);
                $updateUser->save();
            }
            
            if(($statusClientPayment == 'failed' && $usePaymentMethod == 'credit_card') || ($statusClientPaymentAch == 'failed' && $usePaymentMethod == 'bank_account')) {
                if (count($leadsuser) > 0) {
                    foreach($leadsuser as $lds) {
                        $ClientCompanyIDFailed = $lds['company_id'];
    
                        //if ($lds['active'] == "T" || ($lds['active'] == "F" && $lds['disabled'] == "F")) {
                        if (!($lds['active'] == "F" && $lds['disabled'] == "T" && $lds['active_user'] == "F")) {
                            $http = new \GuzzleHttp\Client;
                            $appkey = config('services.trysera.api_id');
                            $organizationid = ($lds['leadspeek_organizationid'] != "")?$lds['leadspeek_organizationid']:"";
                            $campaignsid = ($lds['leadspeek_campaignsid'] != "")?$lds['leadspeek_campaignsid']:"";    
                            $userStripeID = $lds['customer_payment_id'];
    
                            $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                            $updateLeadspeekUser->active = 'F';
                            $updateLeadspeekUser->disabled = 'T';
                            $updateLeadspeekUser->active_user = 'T';
                            $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                            $updateLeadspeekUser->save();
                            /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
    
                            /** UPDATE USER CARD STATUS */
                            $_failedUserID = $lds['user_id'];
                            $updateUser = User::find($lds['user_id']);
    
                            $failedInvoiceID = $invoiceID;
                            $failedInvoiceNumber = $invoiceNum . '-' . $invoiceID;
                            $failedTotalAmount = $totalAmount;
                            $failedCampaignID = $_leadspeek_api_id;
    
                            if (trim($updateUser->failed_invoiceid) != '') {
                                $failedInvoiceID = $updateUser->failed_invoiceid . '|' . $failedInvoiceID;
                            }
                            if (trim($updateUser->failed_invoicenumber) != '') {
                                $failedInvoiceNumber = $updateUser->failed_invoicenumber . '|' . $failedInvoiceNumber;
                            }
                            if (trim($updateUser->failed_total_amount) != '') {
                                $failedTotalAmount = $updateUser->failed_total_amount . '|' . $failedTotalAmount;
                            }
                            if (trim($updateUser->failed_campaignid) != '') {
                                $failedCampaignID = $updateUser->failed_campaignid . '|' . $failedCampaignID;
                            }
    
                            
                            $updateUser->payment_status = 'failed';
                            $updateUser->failed_invoiceid = $failedInvoiceID;
                            $updateUser->failed_invoicenumber = $failedInvoiceNumber;
                            $updateUser->failed_total_amount = $failedTotalAmount;
                            $updateUser->failed_campaignid = $failedCampaignID; 
                            $updateUser->save();
                            /** UPDATE USER CARD STATUS */
    
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                            if ($organizationid != '' && $campaignsid != '' && $lds['leadspeek_type'] == 'locator') {
                                $camp = $this->startPause_campaign($organizationid,$campaignsid,'pause');
                                if ($camp != true) {
                                    /** SEND EMAIL TO ME */
                                        $details = [
                                            'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                        ];
    
                                        $from = [
                                            'address' => 'noreply@sitesettingsapi.com',
                                            'name' => 'support',
                                            'replyto' => 'noreply@sitesettingsapi.com',
                                        ];
                                        // $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-due the payment failed - L2197) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                    /** SEND EMAIL TO ME */
                                }
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
    
                            $ListFailedCampaign = $ListFailedCampaign . $_leadspeek_api_id . '<br/>';
                            $_ListFailedCampaign = $_ListFailedCampaign . $_leadspeek_api_id . '|';
    
                        }
                    }
    
                    /** PAUSED THE OTHER ACTIVE CAMPAIGN FOR THIS CLIENT */
                    $otherCampaignPause = false;
    
                        $leadsuser = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.trysera','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id','users.company_id','leadspeek_users.leadspeek_api_id')
                                        ->join('users','leadspeek_users.user_id','=','users.id')
                                        ->where('users.company_id','=',$ClientCompanyIDFailed)
                                        ->where('users.user_type','=','client')
                                        ->where('leadspeek_users.leadspeek_api_id','<>',$_leadspeek_api_id)
                                        ->where(function($query){
                                            $query->where(function($query){
                                                $query->where('leadspeek_users.active','=','T')
                                                    ->where('leadspeek_users.disabled','=','F')
                                                    ->where('leadspeek_users.active_user','=','T');
                                            })
                                            ->orWhere(function($query){
                                                $query->where('leadspeek_users.active','=','F')
                                                    ->where('leadspeek_users.disabled','=','F')
                                                    ->where('leadspeek_users.active_user','=','T');
                                            });
                                        })->get();
    
                        if (count($leadsuser) > 0) {
                            $otherCampaignPause = true;
                            foreach($leadsuser as $lds) {
                                $http = new \GuzzleHttp\Client;
                                
                                $organizationid = ($lds['leadspeek_organizationid'] != "")?$lds['leadspeek_organizationid']:"";
                                $campaignsid = ($lds['leadspeek_campaignsid'] != "")?$lds['leadspeek_campaignsid']:"";    
                                $userStripeID = $lds['customer_payment_id'];
    
                                $updateLeadspeekUser = LeadspeekUser::find($lds['id']);
                                $updateLeadspeekUser->active = 'F';
                                $updateLeadspeekUser->disabled = 'T';
                                $updateLeadspeekUser->active_user = 'T';
                                $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                                $updateLeadspeekUser->save();
                                /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                                
                                /** ACTIVATE CAMPAIGN SIMPLIFI */
                                if ($organizationid != '' && $campaignsid != '' && $lds['leadspeek_type'] == 'locator') {
                                    $camp = $this->startPause_campaign($organizationid,$campaignsid,'pause');
                                    if ($camp != true) {
                                        /** SEND EMAIL TO ME */
                                            $details = [
                                                'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                            ];
    
                                            $from = [
                                                'address' => 'noreply@sitesettingsapi.com',
                                                'name' => 'support',
                                                'replyto' => 'noreply@sitesettingsapi.com',
                                            ];
                                            // $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-due the payment failed - L2197) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                        /** SEND EMAIL TO ME */
                                    }
                                }
                                /** ACTIVATE CAMPAIGN SIMPLIFI */
    
                                $ListFailedCampaign = $ListFailedCampaign . $lds['leadspeek_api_id'] . '<br/>';
                                $_ListFailedCampaign = $_ListFailedCampaign . $lds['leadspeek_api_id'] . '|';
                            }
                        }
                        /** PAUSED THE OTHER ACTIVE CAMPAIGN FOR THIS CLIENT */
    
                        if ($otherCampaignPause) {
                            /** UPDATE ON INVOICE TABLE THAT FAILED WITH CAMPAIGN LIST THAT PAUSED */
                            $updateInvoiceCampaignPaused = LeadspeekInvoice::find($invoiceID);
                            $updateInvoiceCampaignPaused->campaigns_paused = rtrim($_ListFailedCampaign,"|");
                            $updateInvoiceCampaignPaused->save();
                            /** UPDATE ON INVOICE TABLE THAT FAILED WITH CAMPAIGN LIST THAT PAUSED */
    
                            $usrUpdate = User::find($_failedUserID);
                            $usrUpdate->failed_campaigns_paused = rtrim($_ListFailedCampaign,"|");
                            $usrUpdate->save();
                            
                            if (trim($ListFailedCampaign) != '' && (isset($userStripeID) && $userStripeID != '')) {
                                /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */
                                $from = [
                                    'address' => 'noreply@' . $defaultdomain,
                                    'name' => 'Invoice',
                                    'replyto' => 'support@' . $defaultdomain,
                                ];
                                
                                $details = [
                                    'campaignid'  => $_leadspeek_api_id,
                                    'stripeid' => (isset($userStripeID))?$userStripeID:'',
                                    'othercampaigneffected' => $ListFailedCampaign,
                                ];
                                
                                // $this->send_email($adminEmail,'Campaign ' . $companyName . ' #' . $_leadspeek_api_id . ' (has been pause due the payment failed)',$details,$attachement,'emails.invoicefailed',$from,"");
                                
                                /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */
                            }
                        }
    
                }
            }
        }
        /** CHECK IF FAILED PAYMENT THEN PAUSED THE CAMPAIGN AND SENT EMAIL*/
    }

    public function checkclientleadslimit() {
        date_default_timezone_set('America/New_York');

        $http = new \GuzzleHttp\Client;
        $appkey = config('services.trysera.api_id');

        /** CHECK IF LEADSPEEK USER CAN BE ACTIVE AGAIN */
        $clientList = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.user_id','leadspeek_users.lp_limit_leads','leadspeek_users.lp_limit_freq','leadspeek_users.admin_notify_to','leadspeek_users.campaign_name','leadspeek_users.company_id as company_parent',
                                'leadspeek_users.leadspeek_api_id','companies.id as company_id','companies.company_name','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','leadspeek_users.trysera','leadspeek_users.leadspeek_type')
                                ->join('users','leadspeek_users.user_id','=','users.id')
                                ->join('companies','users.company_id','=','companies.id')
                                ->where('leadspeek_users.active','=','F')
                                ->where('leadspeek_users.disabled','=','F')
                                ->where('leadspeek_users.archived','=','F')
                                ->where('users.user_type','=','client')
                                ->where('leadspeek_users.lp_limit_leads','>','0')
                                ->where('leadspeek_users.lp_limit_freq','<>','max')
                                ->get();
        
        foreach($clientList as $cl) {
            $clientLimitLeads = $cl['lp_limit_leads'];
            $clientLimitFreq = $cl['lp_limit_freq'];
            $_lp_user_id = $cl['id'];
            $_company_id = $cl['company_id'];
            $_company_parent = $cl['company_parent'];
            $_user_id = $cl['user_id'];
            $_leadspeek_api_id = $cl['leadspeek_api_id'];
            $clientAdminNotify = explode(',',$cl['admin_notify_to']);
            $organizationid = $cl['leadspeek_organizationid'];
            $campaignsid = $cl['leadspeek_campaignsid'];
            $tryseramethod = (isset($cl['trysera']) && $cl['trysera'] == "T")?true:false;
            $_leadspeek_type = $cl['leadspeek_type'];

            /** CHECK FOR LIMIT LEADS */
                if($clientLimitLeads > 0) {
                    $countleads = LeadspeekReport::select(DB::raw("(COUNT(*)) as total"))
                                                ->where('active','=','T')
                                                ->where('lp_user_id','=',$_lp_user_id)
                                                ->where('user_id','=',$_user_id);
                    if($clientLimitFreq == 'day') {
                        $allday = date('Ymd');
                        $countleads->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'=',$allday);
                    }else if($clientLimitFreq == 'month') {
                        $freqstartdate = date('Ym01');
                        $freqenddate = date('Ymt');
                        $countleads->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'>=',$freqstartdate)
                                    ->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'<=',$freqenddate);
                    }

                        $countleads = $countleads->get();
                        $TotalLimitLeads = $countleads[0]['total'];
                        
                        /** IF TOTAL COUNT LEADS DAY / MONTH BIGGER and EQUAL FROM LIMIT LEADS */
                        if ($TotalLimitLeads < $clientLimitLeads) {
                            /** GET COMPANY NAME AND CUSTOM ID */
                            $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                            /** GET COMPANY NAME AND CUSTOM ID */

                            $campaignName = '';
                            if (isset($cl['campaign_name']) && trim($cl['campaign_name']) != '') {
                                $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
                            }

                            $company_name = str_replace($_leadspeek_api_id,'',$cl['company_name']) . $campaignName;

                            /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                            if ($tryseramethod) {
                                try{
                                    $pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $cl['leadspeek_api_id'];
                                    $pauseoptions = [
                                        'headers' => [
                                            'Authorization' => 'Bearer ' . $appkey,
                                        ],
                                        'json' => [
                                            "SubClient" => [
                                                "ID" => $cl['leadspeek_api_id'],
                                                "Name" => trim($company_name),
                                                "CustomID" => $tryseraCustomID ,
                                                "Active" => true
                                            ]       
                                        ]
                                    ]; 
                                    $pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                                    $result =  json_decode($pauseresponse->getBody());
                                }catch(Exception $e) {
                                    /** SEND EMAIL TO ME */
                                    $details = [
                                        'errormsg'  => 'Trysera Error Leadspeek ID :' . $cl['leadspeek_api_id']. '<br/>Error Message:<br/>' . $e->getMessage(),
                                    ];

                                    $from = [
                                        'address' => 'newleads@leadspeek.com',
                                        'name' => 'support',
                                        'replyto' => 'harrison@uncommonreach.com',
                                    ];
                                    
                                    $this->send_email(array('harrison@uncommonreach.com'),'Trysera error checkclientleadslimit (INTERNAL) #' .$cl['leadspeek_api_id'],$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                    /** SEND EMAIL TO ME */
                                }
                            }
                            /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */

                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                            if ($organizationid != '' && $campaignsid != '') {
                                $camp = $this->startPause_campaign($organizationid,$campaignsid,'activate');
                                if ($camp == true) {
                                    $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                                    $updateLeadspeekUser->active = 'T';
                                    $updateLeadspeekUser->last_lead_start = date('Y-m-d H:i:s');
                                    $updateLeadspeekUser->save();
                                    //$this->notificationStartStopLeads(array('harrison@uncommonreach.com'),'Started',trim($company_name) . ' #' . $_leadspeek_api_id . ' (API)',$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$_company_parent);
                                }else{
                                    /** SEND EMAIL TO ME */
                                        $details = [
                                            'errormsg'  => 'Trysera Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                        ];

                                        $from = [
                                            'address' => 'noreply@sitesettingsapi.com',
                                            'name' => 'support',
                                            'replyto' => 'noreply@sitesettingsapi.com',
                                        ];
                                        $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-checkclientleadslimit - L2372) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                     /** SEND EMAIL TO ME */
                                }
                            }else if ($_leadspeek_type == 'local') {
                                $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                                $updateLeadspeekUser->active = 'T';
                                $updateLeadspeekUser->last_lead_start = date('Y-m-d H:i:s');
                                $updateLeadspeekUser->save();
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                            
                            /** SEND EMAIL NOTIFICATION */
                            //$this->notificationStartStopLeads($clientAdminNotify,'Started',trim($company_name) . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$_company_parent);
                            /** SEND EMAIL NOTIFICATION */

                        }
                }
                /** CHECK FOR LIMIT LEADS */
        }
    }

    public function notificationdailyleadsadded(Request $request) {
        date_default_timezone_set('America/Chicago');
        $datenow = date('Ymd');
        $clientlist = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.leadspeek_api_id','leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','leadspeek_users.spreadsheet_id','leadspeek_users.campaign_name','companies.company_name','users.company_parent')
                            ->join('users','leadspeek_users.user_id','=','users.id')
                            ->join('companies','users.company_id','=','companies.id')
                            ->where('leadspeek_users.disabled','=','F')
                            ->where('leadspeek_users.active_user','=','T')
                            ->where('users.user_type','=','client')
                            ->where('leadspeek_users.archived','=','F')
                            ->where('users.active','=','T')
                            ->where(DB::raw('DATE_FORMAT(leadspeek_users.last_lead_added, "%Y%m%d")'),'=',$datenow)
                            ->where(function($query) use ($datenow) {
                                $query->whereNull('leadspeek_users.last_lead_notified')
                                        ->orWhere(DB::raw('DATE_FORMAT(leadspeek_users.last_lead_notified, "%Y%m%d")'),'<',$datenow);
                            })
                            ->get();
                            
        foreach($clientlist as $cl) {
            $_leadspeek_api_id = $cl['leadspeek_api_id'];
            $campaignName = '';
            $agencyName = '';

            /** GET AGENCY NAME */
            $agency = Company::select('company_name')->where('id','=',$cl['company_parent'])->get();
            if (count($agency) > 0) {
                $agencyName = $agency[0]['company_name'];
            }
            /** GET AGENCY NAME */

            $AdminDefault = $this->get_default_admin($cl['company_parent']);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'newleads@leadspeek.com';

            if (isset($cl['campaign_name']) && trim($cl['campaign_name']) != '') {
                //$campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
                $campaignName = str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
            }

            //$company_name = str_replace($_leadspeek_api_id,'',$cl['company_name']) . $campaignName;
            $company_name = str_replace($_leadspeek_api_id,'',$cl['company_name']);

            //$clientEmail = explode(PHP_EOL, trim($cl['report_sent_to']));
            $_clientEmail = str_replace(["\r\n", "\r"], "\n", trim($cl['report_sent_to']));
            $clientEmail = explode("\n", $_clientEmail);

            $details = [
                'name'  => $company_name,
                'campaignname' => $campaignName  . ' #' . $_leadspeek_api_id,
                'links' => 'https://docs.google.com/spreadsheets/d/' . $cl['spreadsheet_id'] . '/edit?usp=sharing',
            ];

            $from = [
                'address' => $AdminDefaultEmail,
                'name' => 'New Leads',
                'replyto' => $AdminDefaultEmail,
            ];

            $this->send_email($clientEmail,'You have new leads from ' . $agencyName,$details,array(),'emails.tryseramatchlistclient',$from,$cl['company_parent']);

            $lpupdate = LeadspeekUser::find($cl['id']);
            $lpupdate->last_lead_notified = now();
            $lpupdate->save();
        }
    }

    public function checkcampaignended(Request $request) {
        date_default_timezone_set('America/New_York');

        $clientList = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.lp_enddate','leadspeek_users.campaign_enddate','leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','leadspeek_users.leadspeek_type','leadspeek_users.paymentterm',
                                            'leadspeek_users.user_id','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.company_parent','companies.id as company_id','companies.company_name','leadspeek_users.lp_min_cost_month','leadspeek_users.cost_perlead',
                                            'leadspeek_users.company_id as clientowner','leadspeek_users.lp_limit_startdate','leadspeek_users.leadspeek_api_id','leadspeek_users.lp_max_lead_month','leadspeek_users.campaign_name','leadspeek_users.lp_limit_leads','leadspeek_users.lp_limit_freq','users.customer_payment_id','users.customer_card_id','users.email','users.company_root_id')
                                    ->join('users','leadspeek_users.user_id','=','users.id')
                                    ->join('companies','users.company_id','=','companies.id')
                                    ->where('users.user_type','=','client')
                                    ->where('users.active','=','T')
                                    ->where('leadspeek_users.trysera','=','F')
                                    ->where('leadspeek_users.archived','=','F')
                                    ->where(function($query1){
                                        $query1->where(function($query1){
                                            $query1->where(DB::raw("DATE_FORMAT(`leadspeek_users`.`lp_enddate`,'%Y%m%d%H%i')"),'<',date('YmdHi'))
                                                  ->orWhere(DB::raw("DATE_FORMAT(`leadspeek_users`.`campaign_enddate`,'%Y%m%d%H%i')"),'<',date('YmdHi'));
                                        });
                                    })->where(function($query){
                                        $query->where(function($query){
                                            $query->where('leadspeek_users.active','=','T')
                                                ->where('leadspeek_users.disabled','=','F')
                                                ->where('leadspeek_users.active_user','=','T');
                                        })
                                        ->orWhere(function($query){
                                            $query->where('leadspeek_users.active','=','F')
                                                ->where('leadspeek_users.disabled','=','F')
                                                ->where('leadspeek_users.active_user','=','T');
                                        })
                                        ->orWhere(function($query){
                                            $query->where('leadspeek_users.active','=','F')
                                                ->where('leadspeek_users.disabled','=','T')
                                                ->where('leadspeek_users.active_user','=','T');
                                        });
                                    })->get();
        
        foreach($clientList as $cl) {
            //$clientEmail = explode(PHP_EOL, $cl['report_sent_to']);
            $_clientEmail = str_replace(["\r\n", "\r"], "\n", trim($cl['report_sent_to']));
            $clientEmail = explode("\n", $_clientEmail);
            $clientAdminNotify = explode(',',$cl['admin_notify_to']);
            $clientMaxperTerm = $cl['lp_max_lead_month'];
            //$clientLimitEndDate = ($cl['lp_enddate'] == null || $cl['lp_enddate'] == '0000-00-00 00:00:00')?'':$cl['lp_enddate'];
            $clientLimitEndDate = ($cl['campaign_enddate'] == null || $cl['campaign_enddate'] == '0000-00-00 00:00:00' || trim($cl['campaign_enddate']) == '')?'':$cl['campaign_enddate'];
            $clientCostPerLead = $cl['cost_perlead'];
            $clientPaymentTerm = $cl['paymentterm'];
            $organizationid = $cl['leadspeek_organizationid'];
            $campaignsid = $cl['leadspeek_campaignsid'];
            $_lp_user_id = $cl['id'];
            $_company_id = $cl['company_id'];
            $_user_id = $cl['user_id'];
            $clientMinCostMonth = $cl['lp_min_cost_month'];
            $clientLimitStartDate = ($cl['lp_limit_startdate'] == null || $cl['lp_limit_startdate'] == '0000-00-00 00:00:00')?'':$cl['lp_limit_startdate'];
            $_leadspeek_api_id = $cl['leadspeek_api_id'];

            $clientLimitLeads = $cl['lp_limit_leads'];
            $clientLimitFreq = $cl['lp_limit_freq'];
            $custStripeID = $cl['customer_payment_id'];
            $custStripeCardID = $cl['customer_card_id'];
            $custEmail = $cl['email'];

            $campaignName = '';
            $campaignNameOri = '';
            $companyNameOri = $cl['company_name'];
            if (isset($cl['campaign_name']) && trim($cl['campaign_name']) != '') {
                $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
                $campaignNameOri = str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
            }

            $company_name = str_replace($_leadspeek_api_id,'',$cl['company_name']) . $campaignName;

            /** GET PLATFORM MARGIN */
            $platformMargin = $this->getcompanysetting($cl['company_parent'],'costagency');
            $platform_LeadspeekCostperlead = 0;
            $platform_LeadspeekMinCostMonth = 0;
            $platform_LeadspeekPlatformFee = 0;

            $paymentterm = trim($cl['paymentterm']);
            $paymentterm = str_replace(' ','',$paymentterm);
            if ($platformMargin != '') {
                if ($cl['leadspeek_type'] == "local") {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:0;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:0;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:0;
                }else if ($cl['leadspeek_type'] == "locator") {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LocatorCostperlead))?$platformMargin->locator->$paymentterm->LocatorCostperlead:0;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LocatorMinCostMonth))?$platformMargin->locator->$paymentterm->LocatorMinCostMonth:0;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LocatorPlatformFee))?$platformMargin->locator->$paymentterm->LocatorPlatformFee:0;
                }
            }
            /** GET PLATFORM MARGIN */

            /** CHECK IF THERE END DATE ON WEEKLY OR MONTHLY PAYMENT TERM */
            if (($cl['leadspeek_type'] == "local" && $clientLimitEndDate == '') || $cl['leadspeek_type'] == "enhance") {
                $oneYearLater = date('Y-m-d', strtotime('+1 year', strtotime(date('Y-m-d'))));
                $clientLimitEndDate = ($cl['lp_enddate'] == null || $cl['lp_enddate'] == '0000-00-00 00:00:00' || $cl['lp_enddate'] == '' || $cl['leadspeek_type'] == "enhance")? $oneYearLater . ' 00:00:00':$cl['lp_enddate'];
            }

            if ($clientPaymentTerm != 'One Time' && $clientPaymentTerm != 'Prepaid' && $clientLimitEndDate != '') {
                $EndDate = date('YmdHis',strtotime($clientLimitEndDate));
                if (date('YmdHis') > $EndDate) {
                    
                    /** ACTIVATE CAMPAIGN SIMPLIFI */
                    if ($organizationid != '' && $campaignsid != '') {
                        $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                        if ($camp == true) {
                            /** PUT CLIENT TO ARCHIVE OR STOP */
                            $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                            $updateLeadspeekUser->active = 'F';
                            $updateLeadspeekUser->disabled = 'T';
                            $updateLeadspeekUser->active_user = 'F';
                            $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                            $updateLeadspeekUser->save();
                            /** PUT CLIENT TO ARCHIVE OR STOP */

                            /** UPDATE USER END DATE */
                            $updateUser = User::find($_user_id);
                            $updateUser->lp_enddate = null;
                            $updateUser->lp_limit_startdate = null;
                            $updateUser->save();
                            /** UPDATE USER END DATE */

                            /** CHECK IF THE CONTRACTED ENDED IN THE MIDDLE OF WEEK */
                            $LastBillDate = date('YmdHis',strtotime($updateLeadspeekUser->start_billing_date));
                            $platformFee = 0;

                            $clientStartBilling = date('YmdHis',strtotime($updateLeadspeekUser->start_billing_date));
                            $nextBillingDate = date("YmdHis");
                            //$nextBillingDate = date("YmdHis", strtotime("-1 days"));

                            /** CREATE INVOICE AND SENT IT */
                            $invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxperTerm,$clientCostPerLead,$platformFee,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl,$cl['clientowner']);
                            /** CREATE INVOICE AND SENT IT */

                            /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                            $TotalLimitLeads = '0';
                            $this->notificationStartStopLeads($clientAdminNotify,'Stopped (Campaign End on ' . date('m-d-Y',strtotime($clientLimitEndDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                            /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                            continue;
                        }else{
                            /** SEND EMAIL TO ME */
                                $details = [
                                    'errormsg'  => 'Trysera Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                ];

                                $from = [
                                    'address' => 'noreply@sitesettingsapi.com',
                                    'name' => 'support',
                                    'replyto' => 'noreply@sitesettingsapi.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-checkcampaignended - L2596) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            /** SEND EMAIL TO ME */
                            
                            continue;
                        }
                    }if ($cl['leadspeek_type'] == "local") {
                        /** PUT CLIENT TO ARCHIVE OR STOP */
                        $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                        $updateLeadspeekUser->active = 'F';
                        $updateLeadspeekUser->disabled = 'T';
                        $updateLeadspeekUser->active_user = 'F';
                        $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                        $updateLeadspeekUser->save();
                        /** PUT CLIENT TO ARCHIVE OR STOP */

                        /** UPDATE USER END DATE */
                        $updateUser = User::find($_user_id);
                        $updateUser->lp_enddate = null;
                        $updateUser->lp_limit_startdate = null;
                        $updateUser->save();
                        /** UPDATE USER END DATE */

                        /** CHECK IF THE CONTRACTED ENDED IN THE MIDDLE OF WEEK */
                        $LastBillDate = date('YmdHis',strtotime($updateLeadspeekUser->start_billing_date));
                        $platformFee = 0;

                        $clientStartBilling = date('YmdHis',strtotime($updateLeadspeekUser->start_billing_date));
                        $nextBillingDate = date("YmdHis");
                        //$nextBillingDate = date("YmdHis", strtotime("-1 days"));

                        /** CREATE INVOICE AND SENT IT */
                        $invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxperTerm,$clientCostPerLead,$platformFee,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl,$cl['clientowner']);
                        /** CREATE INVOICE AND SENT IT */

                        /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                        $TotalLimitLeads = '0';
                        $this->notificationStartStopLeads($clientAdminNotify,'Stopped (Campaign End on ' . date('m-d-Y',strtotime($clientLimitEndDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                        /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                        continue;
                    }
                    /** ACTIVATE CAMPAIGN SIMPLIFI */

                    // if (date('YmdHis') >= $LastBillDate) {
                    //     /** CHECK IF NEED TO BILLED PLATFORM FEE OR NOT */
                    //     if ($clientPaymentTerm == 'Weekly') {
                    //         $date1=date_create(date('Ymd'));
                    //         $date2=date_create($LastBillDate);
                    //         $diff=date_diff($date1,$date2);
                    //         if ($diff->format("%a") > 7) {
                    //             $platformFee = $clientMinCostMonth;
                    //         }
                    //     }else if ($clientPaymentTerm == 'Monthly') {
                    //         $maxday = date('t',strtotime($updateLeadspeekUser->start_billing_date));

                    //         $date1=date_create(date('Ymd'));
                    //         $date2=date_create($LastBillDate);
                    //         $diff=date_diff($date1,$date2);
                    //         if ($diff->format("%a") > $maxday) {
                    //             $platformFee = $clientMinCostMonth;
                    //         }
                    //         /*if(date('m') > date('m',strtotime($updateLeadspeekUser->start_billing_date))) {
                    //             $platformFee = $clientMinCostMonth;
                    //         }*/
                    //     }
                    //     /** CHECK IF NEED TO BILLED PLATFORM FEE OR NOT */
                    // }
                    /** CHECK IF THE CONTRACTED ENDED IN THE MIDDLE OF WEEK */

                    /** CHECK IF PLATFORM FEE NOT ZERO AND THEN PUT THE FORMULA */
                    // if ($platformFee != 0 && $platformFee != '' && $clientPaymentTerm == 'Weekly') {
                    //     $clientWeeksContract = 52; //assume will be one year if end date is null or empty
                    //     $clientMonthRange = 12;

                    //     /** PUT FORMULA TO DEVIDED HOW MANY TUESDAY FROM PLATFORM FEE COST */
                    //     if ($clientMinCostMonth != '' && $clientMinCostMonth > 0) {
                    //         if ($clientLimitEndDate != '') {
                    //             $d1 = new DateTime($clientLimitStartDate);
                    //             $d2 = new DateTime($clientLimitEndDate);
                    //             $interval = $d1->diff($d2);
                    //             $clientMonthRange = $interval->m;

                    //             $d1 = strtotime($clientLimitStartDate);
                    //             $d2 = strtotime($clientLimitEndDate);
                    //             $clientWeeksContract = $this->countDays(2, $d1, $d2);

                    //             $platformFee = ($clientMinCostMonth * $clientMonthRange) / $clientWeeksContract;

                    //         }else{
                    //             $platformFee = ($clientMinCostMonth * $clientMonthRange) / $clientWeeksContract;
                    //         }
                    //     }
                    //     /** PUT FORMULA TO DEVIDED HOW MANY TUESDAY FROM PLATFORM FEE COST */
                    // }
                    /** CHECK IF PLATFORM FEE NOT ZERO AND THEN PUT THE FORMULA*/

                    
                    //continue;
                }
            }else if ($clientPaymentTerm == 'Prepaid') {
                $EndDate = date('YmdHis',strtotime($clientLimitEndDate));
                if (date('YmdHis') > $EndDate) {
                    if ($organizationid != '' && $campaignsid != '') {
                        $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                        if ($camp === false) {
                           
                            /** SEND EMAIL TO ME */
                                $details = [
                                    'errormsg'  => 'Trysera Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                ];

                                $from = [
                                    'address' => 'noreply@sitesettingsapi.com',
                                    'name' => 'support',
                                    'replyto' => 'noreply@sitesettingsapi.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-checkcampaignended - L2596) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            /** SEND EMAIL TO ME */
                            
                            continue;
                        
                        }
                    }
                    /** PUT CLIENT TO ARCHIVE OR STOP */
                    $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                    $updateLeadspeekUser->active = 'F';
                    $updateLeadspeekUser->disabled = 'T';
                    $updateLeadspeekUser->active_user = 'F';
                    $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                    $updateLeadspeekUser->save();
                    /** PUT CLIENT TO ARCHIVE OR STOP */

                    /** UPDATE USER END DATE */
                    $updateUser = User::find($_user_id);
                    $updateUser->lp_enddate = null;
                    $updateUser->lp_limit_startdate = null;
                    $updateUser->save();
                    /** UPDATE USER END DATE */

                    /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                    $TotalLimitLeads = '0';
                    $this->notificationStartStopLeads($clientAdminNotify,'Stopped (Campaign End on ' . date('m-d-Y',strtotime($clientLimitEndDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                    /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                }
            }
            /** CHECK IF THERE END DATE ON WEEKLY OR MONTHLY PAYMENT TERM */


        }

    }
    
    public function getmatchlist(Request $request) {
        date_default_timezone_set('America/Chicago');

        $http = new \GuzzleHttp\Client;
        
        $appkey = config('services.trysera.api_id');
        $domain = config('services.trysera.domain');
        $campaignID = config('services.trysera.campaignid');
        
        $apiURL =  config('services.trysera.endpoint') . 'matches';

        $clientList = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.url_code','leadspeek_users.company_id as clientowner','leadspeek_users.report_type','leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','leadspeek_users.leadspeek_api_id','leadspeek_users.active','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','leadspeek_users.gtminstalled'
        ,'leadspeek_users.leadspeek_locator_state','leadspeek_users.leadspeek_locator_require','leadspeek_users.leadspeek_locator_zip','leadspeek_users.leads_amount_notification','leadspeek_users.total_leads','leadspeek_users.ongoing_leads','leadspeek_users.last_lead_added','leadspeek_users.spreadsheet_id','leadspeek_users.filename','leadspeek_users.report_frequency_id','leadspeek_users.lp_max_lead_month','leadspeek_users.lp_min_cost_month','leadspeek_users.cost_perlead'
        ,'users.customer_payment_id','users.customer_card_id','users.email','users.company_parent','leadspeek_users.paymentterm','leadspeek_users.leadspeek_type','leadspeek_users.lp_enddate','leadspeek_users.platformfee','leadspeek_users.hide_phone','leadspeek_users.campaign_name','leadspeek_users.campaign_enddate'
        ,'leadspeek_users.lp_limit_leads','leadspeek_users.lp_limit_freq','leadspeek_users.lp_limit_startdate','leadspeek_users.report_frequency','leadspeek_users.report_frequency_unit','leadspeek_users.last_lead_check','leadspeek_users.start_billing_date','users.name','leadspeek_users.user_id','companies.id as company_id','companies.company_name','users.company_root_id')
        ->selectRaw('TIMESTAMPDIFF(MINUTE,leadspeek_users.last_lead_check,NOW()) as minutesdiff')
        ->selectRaw('TIMESTAMPDIFF(HOUR,leadspeek_users.last_lead_check,NOW()) as hoursdiff')
                        ->join('users','leadspeek_users.user_id','=','users.id')
                        ->join('companies','users.company_id','=','companies.id')
                        ->where('leadspeek_users.active','=','T')
                        ->where('leadspeek_users.active_user','=','T')
                        ->where('leadspeek_users.archived','=','F')
                        ->where('users.user_type','=','client')
                        ->where('users.active','=','T')
                        ->where('leadspeek_users.trysera','=','T')
                        ->get();

        foreach($clientList as $cl) {
            //$clientEmail = explode(PHP_EOL, $cl['report_sent_to']);
            $_clientEmail = str_replace(["\r\n", "\r"], "\n", trim($cl['report_sent_to']));
            $clientEmail = explode("\n", $_clientEmail);
            $clientAdminNotify = explode(',',$cl['admin_notify_to']);
            $clientReportType = $cl['report_type'];
            $clientSpreadSheetID = $cl['spreadsheet_id'];
            $clientFilename = $cl['filename'];
            $clientTotalLeads = $cl['total_leads'];
            $clientOngoingLeads = $cl['ongoing_leads'];
            $limitleadsnotif = $cl['leads_amount_notification'];
            $clientURLcode = trim($cl['url_code']);
            $gtminstalled = ($cl['gtminstalled'] == 'T')?true:false;

            $clientLimitLeads = $cl['lp_limit_leads'];
            $clientLimitFreq = $cl['lp_limit_freq'];
            $clientLimitStartDate = ($cl['lp_limit_startdate'] == null || $cl['lp_limit_startdate'] == '0000-00-00')?'':$cl['lp_limit_startdate'];
            
            $clientPaymentTerm = $cl['paymentterm'];
            $clientPlatformfee = $cl['platformfee'];
            $clientMaxperTerm = $cl['lp_max_lead_month'];
            $clientLimitEndDate = ($cl['lp_enddate'] == null || $cl['lp_enddate'] == '0000-00-00')?'':$cl['lp_enddate'];
            $clientCostPerLead = $cl['cost_perlead'];
            $clientMinCostMonth = $cl['lp_min_cost_month'];
            $custStripeID = $cl['customer_payment_id'];
            $custStripeCardID = $cl['customer_card_id'];
            $custEmail = $cl['email'];

            $_lp_user_id = $cl['id'];
            $_company_id = $cl['company_id'];
            $_user_id = $cl['user_id'];
            $_leadspeek_api_id = $cl['leadspeek_api_id'];
            $campaignName = '';
            $campaignNameOri = '';
            $companyNameOri = $cl['company_name'];
            if (isset($cl['campaign_name']) && trim($cl['campaign_name']) != '') {
                $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
                $campaignNameOri = str_replace($_leadspeek_api_id,'',$cl['campaign_name']);
            }

            $company_name = str_replace($_leadspeek_api_id,'',$cl['company_name']) . $campaignName;

            $_last_lead_check = '';
            $_last_lead_added = '';

            $clientFilterState = explode(',',trim($cl['leadspeek_locator_state']));
            $clientFilterZipCode = explode(',',trim($cl['leadspeek_locator_zip']));
            $clientFilterRequire = explode(',',trim($cl['leadspeek_locator_require']));

            $req_fname = false;
            $req_lname = false;
            $req_mailingaddress = false;
            $req_phone = false;

            /** GET PLATFORM MARGIN */
            $platformMargin = $this->getcompanysetting($cl['company_parent'],'costagency');
            $platform_LeadspeekCostperlead = 0;
            $platform_LeadspeekMinCostMonth = 0;
            $platform_LeadspeekPlatformFee = 0;

            $paymentterm = trim($cl['paymentterm']);
            $paymentterm = str_replace(' ','',$paymentterm);
            if ($platformMargin != '') {
                // $rootcostagency = $this->getcompanysetting($cl['company_root_id'],'rootcostagency');

                if ($cl['leadspeek_type'] == "local") {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:0;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:0;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:0;
                
                    // $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:$rootcostagency->local->$paymentterm->LeadspeekCostperlead;
                    // $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:$rootcostagency->local->$paymentterm->LeadspeekMinCostMonth;
                    // $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:$rootcostagency->local->$paymentterm->LeadspeekPlatformFee;
                }else if ($cl['leadspeek_type'] == "locator") {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LocatorCostperlead))?$platformMargin->locator->$paymentterm->LocatorCostperlead:0;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LocatorMinCostMonth))?$platformMargin->locator->$paymentterm->LocatorMinCostMonth:0;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LocatorPlatformFee))?$platformMargin->locator->$paymentterm->LocatorPlatformFee:0;
                
                    // $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LeadspeekCostperlead))?$platformMargin->locator->$paymentterm->LeadspeekCostperlead:$rootcostagency->locator->$paymentterm->LeadspeekCostperlead;
                    // $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->locator->$paymentterm->LeadspeekMinCostMonth:$rootcostagency->locator->$paymentterm->LeadspeekMinCostMonth;
                    // $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LeadspeekPlatformFee))?$platformMargin->locator->$paymentterm->LeadspeekPlatformFee:$rootcostagency->locator->$paymentterm->LeadspeekPlatformFee;
                }else if($cl['leadspeek_type'] == "enhance") {
                    $rootcostagency = []; 
                    if(!isset($platformMargin->enhance)) {
                        $rootcostagency = $this->getcompanysetting($cl['company_root_id'],'rootcostagency');
                    }
                    $platform_LeadspeekCostperlead = (isset($platformMargin->enhance->$paymentterm->EnhanceCostperlead))?$platformMargin->enhance->$paymentterm->EnhanceCostperlead:$rootcostagency->enhance->$paymentterm->EnhanceCostperlead;
                    $platform_LeadspeekMinCostMonth = (isset($platformMargin->enhance->$paymentterm->EnhanceMinCostMonth))?$platformMargin->enhance->$paymentterm->EnhanceMinCostMonth:$rootcostagency->enhance->$paymentterm->EnhanceMinCostMonth;
                    $platform_LeadspeekPlatformFee = (isset($platformMargin->enhance->$paymentterm->EnhancePlatformFee))?$platformMargin->enhance->$paymentterm->EnhancePlatformFee:$rootcostagency->enhance->$paymentterm->EnhancePlatformFee;
                }
            }
            /** GET PLATFORM MARGIN */

            /** CHECK FIELD REQUIREDS */
            /*foreach($clientFilterRequire as $req) {
                if ($req == "FirstName") {
                    $req_fname = true;
                }
                if ($req == "LastName") {
                    $req_lname = true;
                }
                if ($req == "MailingAddress") {
                    $req_mailingaddress = true;
                }
                if ($req == "Phone") {
                    $req_phone = true;
                }
            }*/
            /** CHECK FIELD REQUIREDS */

            $organizationid = $cl['leadspeek_organizationid'];
            $campaignsid = $cl['leadspeek_campaignsid'];
            $price_lead = ($cl['cost_perlead'] != '')?$cl['cost_perlead']:0;
            $platform_price_lead = $platform_LeadspeekCostperlead;
            $clientHidePhone = $cl['hide_phone'];

            /** OVERRIDE PLATOFRM PRICE LEADS DANIEL REQUEST 2 June 2023 */
            $campaignIDexception = array("2530","2558","2559","2581","2560","2555","2546","2441","2563","2562");
            $leadpriceException = array("0.15","0.15","0.15","0.17","0.17","0.17","0.17","0.17","0.17","0.17");
            $searchexecption = "";
            $searchexecption = array_search(trim($_leadspeek_api_id),$campaignIDexception);
            if ($searchexecption != "") {
                $platform_price_lead = $leadpriceException[$searchexecption];
            }
            /** OVERRIDE PLATOFRM PRICE LEADS DANIEL REQUEST 2 June 2023 */

            $attachementlist = array();
            $attachementlink = array();
            $attachment = array();
            
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $appkey,
                ],
                'json' => [
                    //"StartDate" => date('Y-m-d') . "T" . "00:00:00",
                    //"StartDate" => "2021-05-28T" . "00:00:00",
                    "StartDate" => date('Y-m-d',strtotime(date('Y-m-d') .' -1 day')) . "T" . "00:00:00",
                    "EndDate" => date('Y-m-d') . "T" . "23:59:59.9999999",
                    "SubClient" => [
                        "ID" => $cl['leadspeek_api_id'],
                        "CustomID" => "",
                    ],
                    "ResultsPerPage" => 10000,
                    "Page" => 0,
                    "NonDownloaded" => false,
                    "MarkAsDownloaded" => false,
                    
                ]
            ]; 
            
            /** CHECK IF THERE END DATE ON WEEKLY OR MONTHLY PAYMENT TERM */
            if ($cl['leadspeek_type'] == "locator" && $clientLimitEndDate == '') {
                $clientLimitEndDate = $cl['campaign_enddate'];
            }

            if ($clientPaymentTerm != 'One Time' && $clientPaymentTerm != 'Prepaid' && $clientLimitEndDate != '' && $cl['leadspeek_type'] != "enhance") {
                $EndDate = date('Ymd',strtotime($clientLimitEndDate));
                if (date('Ymd') > $EndDate) {
                    /** GET COMPANY NAME AND CUSTOM ID */
                    $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                    /** GET COMPANY NAME AND CUSTOM ID */

                    /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                    $pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $cl['leadspeek_api_id'];
                    $pauseoptions = [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $appkey,
                        ],
                        'json' => [
                            "SubClient" => [
                                "ID" => $cl['leadspeek_api_id'],
                                "Name" => trim($company_name),
                                "CustomID" => $tryseraCustomID ,
                                "Active" => false
                            ]       
                        ]
                    ]; 
                    $pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                    $result =  json_decode($pauseresponse->getBody());

                    /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                    
                    /** ACTIVATE CAMPAIGN SIMPLIFI */
                    if ($organizationid != '' && $campaignsid != '') {
                        $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                    }
                    /** ACTIVATE CAMPAIGN SIMPLIFI */

                    /** PUT CLIENT TO ARCHIVE OR STOP */
                    $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                    $updateLeadspeekUser->active = 'F';
                    $updateLeadspeekUser->disabled = 'T';
                    $updateLeadspeekUser->active_user = 'F';
                    $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                    $updateLeadspeekUser->save();
                    /** PUT CLIENT TO ARCHIVE OR STOP */

                    /** UPDATE USER END DATE */
                    $updateUser = User::find($_user_id);
                    $updateUser->lp_enddate = null;
                    $updateUser->lp_limit_startdate = null;
                    $updateUser->save();
                    /** UPDATE USER END DATE */

                    /** CHECK IF THE CONTRACTED ENDED IN THE MIDDLE OF WEEK */
                    $LastBillDate = date('Ymd',strtotime($updateLeadspeekUser->start_billing_date));
                    $platformFee = 0;

                    if (date('Ymd') >= $LastBillDate) {
                        /** CHECK IF NEED TO BILLED PLATFORM FEE OR NOT */
                        if ($clientPaymentTerm == 'Weekly') {
                            $date1=date_create(date('Ymd'));
                            $date2=date_create($LastBillDate);
                            $diff=date_diff($date1,$date2);
                            if ($diff->format("%a") > 7) {
                                $platformFee = $clientMinCostMonth;
                            }
                        }else if ($clientPaymentTerm == 'Monthly') {
                            $maxday = date('t',strtotime($updateLeadspeekUser->start_billing_date));

                            $date1=date_create(date('Ymd'));
                            $date2=date_create($LastBillDate);
                            $diff=date_diff($date1,$date2);
                            if ($diff->format("%a") > $maxday) {
                                $platformFee = $clientMinCostMonth;
                            }
                            /*if(date('m') > date('m',strtotime($updateLeadspeekUser->start_billing_date))) {
                                $platformFee = $clientMinCostMonth;
                            }*/
                        }
                        /** CHECK IF NEED TO BILLED PLATFORM FEE OR NOT */
                    }
                    /** CHECK IF THE CONTRACTED ENDED IN THE MIDDLE OF WEEK */

                    /** CHECK IF PLATFORM FEE NOT ZERO AND THEN PUT THE FORMULA */
                    if ($platformFee != 0 && $platformFee != '' && $clientPaymentTerm == 'Weekly') {
                        $clientWeeksContract = 52; //assume will be one year if end date is null or empty
                        $clientMonthRange = 12;

                        /** PUT FORMULA TO DEVIDED HOW MANY TUESDAY FROM PLATFORM FEE COST */
                        if ($clientMinCostMonth != '' && $clientMinCostMonth > 0) {
                            if ($clientLimitEndDate != '') {
                                $d1 = new DateTime($clientLimitStartDate);
                                $d2 = new DateTime($clientLimitEndDate);
                                $interval = $d1->diff($d2);
                                $clientMonthRange = $interval->m;

                                $d1 = strtotime($clientLimitStartDate);
                                $d2 = strtotime($clientLimitEndDate);
                                $clientWeeksContract = $this->countDays(2, $d1, $d2);

                                $platformFee = ($clientMinCostMonth * $clientMonthRange) / $clientWeeksContract;

                            }else{
                                $platformFee = ($clientMinCostMonth * $clientMonthRange) / $clientWeeksContract;
                            }
                        }
                        /** PUT FORMULA TO DEVIDED HOW MANY TUESDAY FROM PLATFORM FEE COST */
                    }
                    /** CHECK IF PLATFORM FEE NOT ZERO AND THEN PUT THE FORMULA*/

                    $clientStartBilling = date('Ymd',strtotime($updateLeadspeekUser->start_billing_date));
                    //$nextBillingDate = date('Ymd');
                    $nextBillingDate = date("Ymd", strtotime("-1 days"));

                    /** CREATE INVOICE AND SENT IT */
                    $invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxperTerm,$clientCostPerLead,$platformFee,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl,$cl['clientowner']);
                    /** CREATE INVOICE AND SENT IT */

                    /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                    $TotalLimitLeads = '0';
                    $this->notificationStartStopLeads($clientAdminNotify,'Stopped (Campaign End on ' . date('m-d-Y',strtotime($clientLimitEndDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                    /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                    continue;
                }
            }
            /** CHECK IF THERE END DATE ON WEEKLY OR MONTHLY PAYMENT TERM */
            
            /** START CHECKING BASED ON FREQUENCY OF THE CLIENT */
            //$actionReportCheck = false;
            $actionReportCheck = true;
            /*if($cl['report_frequency_unit'] == 'minutes' && ($cl['minutesdiff'] >= $cl['report_frequency'] || $cl['last_lead_check'] == '0000-00-00 00:00:00' || $cl['last_lead_check'] == '')) {
                $actionReportCheck = true;
            }else if($cl['report_frequency_unit'] == 'hours' && ($cl['hoursdiff'] >= $cl['report_frequency'] || $cl['last_lead_check'] == '0000-00-00 00:00:00' || $cl['last_lead_check'] == '')) {
                $actionReportCheck = true;
            }*/

            /** SHOW OR FILL ALL DATA FOR SPECIFIC ID AGENCY CLIENT*/
            $fillAllList = false;
            $AgencySpecialList = array('90');
            if (in_array($cl['clientowner'],$AgencySpecialList)) {
                $fillAllList = true;
            }
            /** SHOW OR FILL ALL DATA FOR SPECIFIC ID AGENCY CLIENT */

            /** START CHECKING BASED ON FREQUENCY OF THE CLIENT */
            $leadcount = 0;
            
            if ($actionReportCheck) {
                $_last_lead_check = now();
                $leftLeadstoInvoice = 0;

                $response = $http->post($apiURL,$options);
                $jsonresult =  json_decode($response->getBody());
                
                /** IF MATCHED DATA FOUND */
                if (count((array) $jsonresult->Matches) > 0) {
                    $_last_lead_added = now();

                    /** PROCESSING BASED ON REPORT TYPE */
                    if($clientReportType == 'GoogleSheet' && $clientSpreadSheetID != '') {
                        $content = array();

                        /** IF CLIENT USER SPREADSHEET ONLINE */
                        $matches = $jsonresult->Matches;
                        foreach($matches as $row) {
                            /** CHECK IF REPORT ALREADY EXIST */
                                $chckLeadReport = LeadspeekReport::where('id','=',$row->ID)->get();
                                if (count($chckLeadReport) > 0) {
                                    continue;
                                }
                             /** CHECK IF REPORT ALREADY EXIST */

                             /** IF HIDE PHONE MEANS EMPTY */
                             /*if ($clientHidePhone == 'T') {
                                 $row->Phone = '';
                             }*/
                             /** IF HIDE PHONE MEANS EMPTY */

                            $requiredFilter = false;
                            /*if ($req_fname || $req_lname || $req_mailingaddress || $req_phone) {
                                $requiredFilter = true;
                            }*/

                            /** CHECK IF THERE IS FILTER ON SPECIFIC CLIENT */
                            if (false) {
                            //if ((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) != '') || (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) != '') || $requiredFilter) {
                                $statefilter = array_map('trim', $clientFilterState);
                                $statefilter = array_map('strtolower', $statefilter);

                                $zipfilter = array_map('trim', $clientFilterZipCode);
                                $zipfilter = array_map('strtolower', $zipfilter);
                                $requiredFilterPass = false;

                                $_FirstName = trim($row->FirstName);
                                $_LastName = trim($row->LastName);
                                $_Mailaddress = trim($row->Address1);
                                $_Phone = trim($row->Phone);

                                if ($requiredFilter) {
                                    if (($req_fname && trim($row->FirstName) != '') && ($req_lname && trim($row->LastName) != '') && ($req_mailingaddress && trim($row->Address1) != '') && ($req_phone && trim($row->Phone) != '')) {
                                        $requiredFilterPass = true;
                                    }else if (($req_fname && trim($row->FirstName) != '') && ($req_lname && trim($row->LastName) != '') && ($req_mailingaddress && trim($row->Address1) != '') && $req_phone == false) {
                                        $requiredFilterPass = true;
                                        $_Phone = "";
                                    }else if (($req_fname && trim($row->FirstName) != '') && ($req_lname && trim($row->LastName) != '') && $req_mailingaddress == false && $req_phone == false) {
                                        $requiredFilterPass = true;
                                        $_Phone = "";
                                        $_Mailaddress = "";
                                    }
                                    /*else if (($req_lname && trim($row->LastName) != '') && ($req_mailingaddress && trim($row->Address1) != '') && $req_fname == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_fname && trim($row->FirstName) != '') && ($req_mailingaddress && trim($row->Address1) != '') && $req_lname == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_fname && trim($row->FirstName) != '') && $req_lname == false && $req_mailingaddress == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_lname && trim($row->LastName) != '') && $req_fname == false && $req_mailingaddress == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_mailingaddress && trim($row->Address1) != '') && $req_fname == false && $req_lname == false) {
                                        $requiredFilterPass = true;
                                    }
                                    */
                                }

                                /** NEW LOGIC */
                                if ((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) != '') && (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) != '')) {
                                    
                                    if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') && (in_array(strtolower(trim($row->Zipcode)),$zipfilter)) && trim($row->Zipcode) != '') && ($requiredFilter == true && $requiredFilterPass == true)) {
                                        if ($fillAllList) {
                                            $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }else{
                                            $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'',$_Phone,ucfirst(strtolower($_FirstName)),ucfirst(strtolower($_LastName)),$_Mailaddress,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    
                                    }else if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') && (in_array(strtolower(trim($row->Zipcode)),$zipfilter) && trim($row->Zipcode) != '')) && $requiredFilter == false) {
                                        if ($fillAllList) {
                                            $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }else{
                                            $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'',$_Phone,ucfirst(strtolower($_FirstName)),ucfirst(strtolower($_LastName)),$_Mailaddress,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    
                                    }else if (((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) == '') && (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) == '')) && $requiredFilter == true && $requiredFilterPass == true) {
                                        if ($fillAllList) {
                                            $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }else{
                                            $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'',$_Phone,ucfirst(strtolower($_FirstName)),ucfirst(strtolower($_LastName)),$_Mailaddress,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    }else{
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'F',$price_lead,$platform_price_lead);
                                    }

                                }else{
                                    if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') || (in_array(strtolower(trim($row->Zipcode)),$zipfilter)) && trim($row->Zipcode) != '') && ($requiredFilter == true && $requiredFilterPass == true)) {
                                        if ($fillAllList) {
                                            $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }else{
                                            $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'',$_Phone,ucfirst(strtolower($_FirstName)),ucfirst(strtolower($_LastName)),$_Mailaddress,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    
                                    }else if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') || (in_array(strtolower(trim($row->Zipcode)),$zipfilter) && trim($row->Zipcode) != '')) && $requiredFilter == false) {
                                        if ($fillAllList) {
                                            $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }else{
                                            $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'',$_Phone,ucfirst(strtolower($_FirstName)),ucfirst(strtolower($_LastName)),$_Mailaddress,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    
                                    }else if (((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) == '') && (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) == '')) && $requiredFilter == true && $requiredFilterPass == true) {
                                        if ($fillAllList) {
                                            $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }else{
                                            $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'',$_Phone,ucfirst(strtolower($_FirstName)),ucfirst(strtolower($_LastName)),$_Mailaddress,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        }
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    }else{
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'F',$price_lead,$platform_price_lead);
                                    }
                                }

                                /** NEW LOGIC */

                                
                            }else{
                                /** CLIENT ADVORIA 2323 JUST WANT DATA THAT HAVE PHONE */
                                if ($_leadspeek_api_id == '2323') {
                                    if (trim($row->Phone) != '') {
                                        //$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    }else{
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'F',$price_lead,$platform_price_lead);
                                    }
                                }else{
                                /** CLIENT ADVORIA 2323 JUST WANT DATA THAT HAVE PHONE */
                                    if ($fillAllList) {
                                        $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                    }else{
                                        $content[] = array($row->ID,$row->Email,'','','',$row->ClickDate,'',$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                    }
                                    $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    $leadcount++;
                                }
                            }
                            /** CHECK IF THERE IS FILTER ON SPECIFIC CLIENT */
                            /*$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                            $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                            $leadcount++;
                            */
                            /** CHECK IF ALREADY REACH FOR NOTIFICATION OR INVOICE */
                            //$leftLeadstoInvoice = ($clientTotalLeads + $leadcount) % $limitleadsnotif;
                            //if ($leftLeadstoInvoice == 0) {    
                                /** CREATE INVOICE AND SENT IT */
                            //    $invoiceCreated = $this->createInvoice($row->ID,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$limitleadsnotif,$clientEmail,$clientAdminNotify,$company_name);
                                /** CREATE INVOICE AND SENT IT */
                            //}
                            /** CHECK IF ALREADY REACH FOR NOTIFICATION OR INVOICE */
                        }

                        if (count($content) > 0) {
                            $client = new GoogleSheet($clientSpreadSheetID,$cl['clientowner'],'3');
                            //$sheetID = $client->getSheetID(date('Y'));
                            $sheetID = $client->getSheetID('2021');
                            if ($sheetID === '') {
                                //$sheetID = $client->createSheet(date('Y'));
                                //$client->setSheetName(date('Y'));
                                /** INTIAL GOOGLE SPREADSHEET HEADER */
                                //$contentHeader[] = array('ID','Email','IP','Source','OptInDate','ClickDate','Referer','Phone','First Name','Last Name','Address1','Address2','City','State','Zipcode');
                                /** INTIAL GOOGLE SPREADSHEET HEADER */
                                //$savedData = $client->saveDataHeaderToSheet($contentHeader);
                                $sheetdefault = explode('|',$client->getDefaultSheetIDName());
                                $sheetID = $sheetdefault[0];
                                $client->setSheetName($sheetdefault[1]);
                            }else{
                                //$client->setSheetName(date('Y'));
                                $client->setSheetName('2021');
                            }

                            $attachementlink[] = $cl['name'] . ' Report : ' . 'https://docs.google.com/spreadsheets/d/' . $cl['spreadsheet_id'] . '/edit?usp=sharing';
                            $savedData = $client->saveDataToSheet($content);
                            
                            $details = [
                                'name'  => $company_name . ' #' . $_leadspeek_api_id,
                                'links' => $cl['name'] . ' Report : ' . 'https://docs.google.com/spreadsheets/d/' . $cl['spreadsheet_id'] . '/edit?usp=sharing',
                            ];

                            //$this->send_email($clientEmail,'LeadsPeek Report Match List ' . date('m-d-Y'),$details,array(),'emails.tryseramatchlistclient');
                        }
                        $content = array();
                        /** IF CLIENT USER SPREADSHEET ONLINE */

                    }else if($clientReportType == 'CSV') {
                        /** TO GENERATE CSV */
                        $content = array();

                        //$csvFileName = 'reports/' . $cl['name'] . '_' . $cl['user_id'] . '00' . $cl['id'] . date('Ymd') . '.csv';
                        $csvFileName = 'reports/' . $cl['name'] . '_' . $cl['user_id'] . '00' . $cl['id'] . '.csv';
                        $attachementlist[] = public_path($csvFileName);

                        $attachment[] = public_path($csvFileName);
                        
                        $fp = fopen($csvFileName, 'w');

                        $headerfield = $jsonresult->Fields;
                        fputcsv($fp, $headerfield);

                        $matches = $jsonresult->Matches;
                        foreach($matches as $row) {
                            /** CHECK IF REPORT ALREADY EXIST */
                            $chckLeadReport = LeadspeekReport::where('id','=',$row->ID)->get();
                            if (count($chckLeadReport) > 0) {
                                continue;
                            }
                            /** CHECK IF REPORT ALREADY EXIST */

                            $requiredFilter = false;
                            if ($req_fname || $req_lname || $req_mailingaddress) {
                                $requiredFilter = true;
                            }

                            /** CHECK IF THERE IS FILTER ON SPECIFIC CLIENT */
                            if ((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) != '') || (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) != '') || $requiredFilter) {
                                $statefilter = array_map('trim', $clientFilterState);
                                $statefilter = array_map('strtolower', $statefilter);

                                $zipfilter = array_map('trim', $clientFilterZipCode);
                                $zipfilter = array_map('strtolower', $zipfilter);
                                $requiredFilterPass = false;

                                if ($requiredFilter) {
                                    if (($req_fname && trim($row->FirstName) != '') && ($req_lname && trim($row->LastName) != '') && ($req_mailingaddress && trim($row->Address1) != '')) {
                                        $requiredFilterPass = true;
                                    }else if (($req_fname && trim($row->FirstName) != '') && ($req_lname && trim($row->LastName) != '') && $req_mailingaddress == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_lname && trim($row->LastName) != '') && ($req_mailingaddress && trim($row->Address1) != '') && $req_fname == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_fname && trim($row->FirstName) != '') && ($req_mailingaddress && trim($row->Address1) != '') && $req_lname == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_fname && trim($row->FirstName) != '') && $req_lname == false && $req_mailingaddress == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_lname && trim($row->LastName) != '') && $req_fname == false && $req_mailingaddress == false) {
                                        $requiredFilterPass = true;
                                    }else if (($req_mailingaddress && trim($row->Address1) != '') && $req_fname == false && $req_lname == false) {
                                        $requiredFilterPass = true;
                                    }
                                }

                                /** NEW LOGIC */
                                if ((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) != '') && (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) != '')) {
                                    
                                    if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') && (in_array(strtolower(trim($row->Zipcode)),$zipfilter)) && trim($row->Zipcode) != '') && ($requiredFilter == true && $requiredFilterPass == true)) {
                                        //$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $content = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        fputcsv($fp, $content);
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                        
                                    }else if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') && (in_array(strtolower(trim($row->Zipcode)),$zipfilter) && trim($row->Zipcode) != '')) && $requiredFilter == false) {
                                        //$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $content = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        fputcsv($fp, $content);
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    
                                    }else if (((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) == '') && (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) == '')) && $requiredFilter == true && $requiredFilterPass == true) {
                                        //$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $content = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        fputcsv($fp, $content);
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    }else{
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'F',$price_lead,$platform_price_lead);
                                    }

                                }else{
                                    if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') || (in_array(strtolower(trim($row->Zipcode)),$zipfilter)) && trim($row->Zipcode) != '') && ($requiredFilter == true && $requiredFilterPass == true)) {
                                        //$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $content = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        fputcsv($fp, $content);
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    
                                    }else if (((in_array(strtolower(trim($row->State)),$statefilter) && trim($row->State) != '') || (in_array(strtolower(trim($row->Zipcode)),$zipfilter) && trim($row->Zipcode) != '')) && $requiredFilter == false) {
                                        //$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $content = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        fputcsv($fp, $content);
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    
                                    }else if (((count($clientFilterState)>0 && isset($clientFilterState[0]) && trim($clientFilterState[0]) == '') && (count($clientFilterZipCode)>0 && isset($clientFilterZipCode[0]) && trim($clientFilterZipCode[0]) == '')) && $requiredFilter == true && $requiredFilterPass == true) {
                                        //$content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        $content = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                        fputcsv($fp, $content);
                                        $leadcount++;
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                    }else{
                                        $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'F',$price_lead,$platform_price_lead);
                                    }
                                }

                                /** NEW LOGIC */
                                
                            }else{

                                $content = array($row->ID,$row->Email,'','','',$row->ClickDate,'','',$row->FirstName,$row->LastName,$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                                fputcsv($fp, $content);
                                $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,$row->FirstName,$row->LastName,$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,'T',$price_lead,$platform_price_lead);
                                $leadcount++;

                            }
                            /** CHECK IF THERE IS FILTER ON SPECIFIC CLIENT */

                        }
                        fclose($fp);
                        
                        if(count($content) > 0) {
                            $details = [
                                'name'  => $company_name . ' #' . $_leadspeek_api_id,
                            ];

                            //$this->send_email($clientEmail,'LeadsPeek Report Match List ' . date('m-d-Y'),$details,$attachment,'emails.tryseramatchlistclientattach');
                        }

                        $attachment = array();
                        $content = array();
                        /** TO GENERATE CSV */
                    }else if($clientReportType == 'Excel') {

                    }
                    /** PROCESSING BASED ON REPORT TYPE */

                    
                    

                }else { 
                    /** IF NONE MATCHED THAT DAY CHECK THE EMBEDDED CODE STILL EXIST OR NOT */
                    if ($clientURLcode != "" && $gtminstalled == false) {
                        $chkembedded = $this->check_embeddedcode_exist($clientURLcode,$_leadspeek_api_id);
                        if ($chkembedded != '') {

                            /** CHECK TO EMAIL NOTIFICATION */
                                $chkemailnotif = EmailNotification::select('id','next_try',DB::raw("DATE_FORMAT(next_try, '%Y%m%d') as nexttry"))
                                                                    ->where('user_id','=',$_user_id)
                                                                    ->where('leadspeek_api_id','=',$_leadspeek_api_id)
                                                                    ->where('notification_name','getmatchlist-missingembeddedcode')
                                                                    ->get();
                                $actionNotify = false;

                                if (count($chkemailnotif) == 0) {
                                        $createEmailNotif = EmailNotification::create([
                                            'user_id' => $_user_id,
                                            'leadspeek_api_id' => $_leadspeek_api_id,
                                            'notification_name' => 'getmatchlist-missingembeddedcode',
                                            'notification_subject' => 'Embedded Code Missing For ' . $company_name . ' #' . $_leadspeek_api_id . ')',
                                            'description' => '',
                                            'next_try' => date('Y-m-d',strtotime(date('Y-m-d') . ' +1Days')),
                                        ]);

                                        $actionNotify = true;

                                }else if (count($chkemailnotif) > 0) {
                                        if ($chkemailnotif[0]['nexttry'] <= date('Ymd')) {
                                            $updateEmailNotif = EmailNotification::find($chkemailnotif[0]['id']);
                                            $updateEmailNotif->next_try = date('Y-m-d',strtotime(date('Y-m-d') . ' +1Days'));
                                            $updateEmailNotif->save();

                                            $actionNotify = true;

                                        }
                                }
                                
                                if ($actionNotify == true) {
                                    /** FIND ADMIN EMAIL */
                                    $tmp = User::select('email')->whereIn('id', $clientAdminNotify)->get();
                                    $adminEmail = array();
                                    foreach($tmp as $ad) {
                                        array_push($adminEmail,$ad['email']);
                                    }
                                    array_push($adminEmail,'harrison@uncommonreach.com');
                                    /** FIND ADMIN EMAIL */

                                    /** GET DOMAIN OR SUBDOMAIN FOR EMBEDDED CODE */
                                    $datacompany = Company::select('domain','subdomain','status_domain')
                                                        ->where('id','=',$cl['clientowner'])
                                                        ->get();

                                    $jsdomain = 'px.0o0o.io/px.min.js';
                                    /*if (config('services.appconf.devmode') === true) {
                                        $jsdomain = 'api.emmsandbox.com/px.min.js';
                                    }*/

                                    if (count($datacompany) > 0) {
                                        $jsdomain = trim($datacompany[0]['subdomain']);
                                        if ($datacompany[0]['domain'] != '' && $datacompany[0]['status_domain'] == 'ssl_acquired') {
                                            $jsdomain = trim($datacompany[0]['domain']);
                                        }
                                        /*if (config('services.appconf.devmode') === true) {
                                            $jsdomain = $jsdomain . '/px-sandbox.min.js';
                                        }else{
                                            $jsdomain = $jsdomain . '/px.min.js';
                                        }*/
                                        $jsdomain = $jsdomain . '/px.min.js';
                                    }
                                    /** GET DOMAIN OR SUBDOMAIN FOR EMBEDDED CODE */


                                    $AdminDefault = $this->get_default_admin($cl['clientowner']);
                                    $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'newleads@leadspeek.com';

                                    $details = [
                                        'companyname'  => $companyNameOri,
                                        'campaignname' => $campaignNameOri,
                                        'leadspeek_api_id' => $_leadspeek_api_id,
                                        'website' => $clientURLcode,
                                        'status' => $chkembedded,
                                        'jsdomain' => $jsdomain,
                                    ];
                                    $attachement = array();

                                    $from = [
                                        'address' => $AdminDefaultEmail,
                                        'name' => 'support',
                                        'replyto' => $AdminDefaultEmail,
                                    ];

                                    $this->send_email($adminEmail,'Your customers campaign is not working',$details,$attachement,'emails.embeddedcodemissing',$from,$cl['clientowner']);
                                }

                            
                        }
                    }
                    /** IF NONE MATCHED THAT DAY CHECK THE EMBEDDED CODE STILL EXIST OR NOT */
                }
                /** IF MATCHED DATA FOUND */
                
                /** UPDATE LEADS DATA AND DATE */
                $totalOngoing = $clientOngoingLeads + $leadcount;

                $lpupdate = LeadspeekUser::find($_lp_user_id);
                if ($_last_lead_check != '') {
                    $lpupdate->last_lead_check = $_last_lead_check;
                }
                if ($_last_lead_added != '') {
                    $lpupdate->last_lead_added = $_last_lead_added;
                }
                if ($leadcount != 0) {
                    $lpupdate->total_leads = $clientTotalLeads + $leadcount;
                    //$lpupdate->ongoing_leads = $leftLeadstoInvoice;
                    $lpupdate->ongoing_leads = $totalOngoing;
                }

                $lpupdate->save();
                /** UPDATE LEADS DATA AND DATE */

                $reachLimitFreq = false;
                /** CHECK FOR LIMIT LEADS */
                    if($clientLimitLeads > 0) {
                        $countleads = LeadspeekReport::select(DB::raw("(COUNT(*)) as total"))
                                                    ->where('active','=','T')
                                                    ->where('lp_user_id','=',$_lp_user_id)
                                                    ->where('user_id','=',$_user_id);
                        if($clientLimitFreq == 'day') {
                            $allday = date('Ymd');
                            $countleads->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'=',$allday);
                        }else if($clientLimitFreq == 'month') {
                            $freqstartdate = date('Ym01');
                            $freqenddate = date('Ymt');
                            $countleads->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'>=',$freqstartdate)
                                        ->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'<=',$freqenddate);
                        }else if($clientLimitFreq == 'max') {
                            if ($clientLimitStartDate != '') {
                                $clientLimitStartDate = date('Ymd',strtotime($clientLimitStartDate));
                                $countleads->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'>=',$clientLimitStartDate);
                            }
                        }

                            $countleads = $countleads->get();
                            $TotalLimitLeads = $countleads[0]['total'];
                            
                            /** IF TOTAL COUNT LEADS DAY / MONTH BIGGER and EQUAL FROM LIMIT LEADS */
                            if ($TotalLimitLeads >= $clientLimitLeads) {
                                $reachLimitFreq = true;

                                /** GET COMPANY NAME AND CUSTOM ID */
                                $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                                /** GET COMPANY NAME AND CUSTOM ID */

                                /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                                $pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $cl['leadspeek_api_id'];
                                $pauseoptions = [
                                    'headers' => [
                                        'Authorization' => 'Bearer ' . $appkey,
                                    ],
                                    'json' => [
                                        "SubClient" => [
                                            "ID" => $cl['leadspeek_api_id'],
                                            "Name" => trim($company_name),
                                            "CustomID" => $tryseraCustomID ,
                                            "Active" => false
                                        ]       
                                    ]
                                ]; 
                                $pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                                $result =  json_decode($pauseresponse->getBody());

                                $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                                $updateLeadspeekUser->active = 'F';
                                $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                                $updateLeadspeekUser->save();
                                /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                                
                                /** ACTIVATE CAMPAIGN SIMPLIFI */
                                if ($organizationid != '' && $campaignsid != '') {
                                    $camp = $this->startPause_campaign($organizationid,$campaignsid,'pause');
                                }
                                /** ACTIVATE CAMPAIGN SIMPLIFI */

                                /** SEND EMAIL NOTIFICATION */
                                //$this->notificationStartStopLeads($clientAdminNotify,'Paused',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                                /** SEND EMAIL NOTIFICATION */
                            }
                    }
                /** CHECK FOR LIMIT LEADS */

                /** CHECK IF THE PAYMENT IS ONE TERM AND SHOULD BE MAKE USER INACTIVE AND SENT INVOICE */
                if ($clientPaymentTerm == 'One Time' && $clientMaxperTerm > 0 && $clientMaxperTerm != '' && $clientLimitStartDate != '') {

                    $onetimeStartDate = date('Ymd',strtotime($clientLimitStartDate));
                    $countleads = LeadspeekReport::select(DB::raw("(COUNT(*)) as total"))
                                                    ->where('active','=','T')
                                                    ->where('lp_user_id','=',$_lp_user_id)
                                                    ->where('user_id','=',$_user_id)
                                                    ->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d")'),'>=',$onetimeStartDate)
                                                    ->get();
                    //$countleads = $countleads->get();
                    $TotalLimitLeads = $countleads[0]['total'];

                    /** SENT NOTIFICATION IF LIMIT ALMOST 10% */
                    /** SENT NOTIFICATION IF LIMIT ALMOST 10% */
                    
                    /** IF ONE TIME LIMIT FREQUENCY LESS THAN TOTAL LEADS SINCE START */
                    if ($TotalLimitLeads >= $clientMaxperTerm) {
                        if ($reachLimitFreq == false) {
                            /** GET COMPANY NAME AND CUSTOM ID */
                            $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                            /** GET COMPANY NAME AND CUSTOM ID */

                            /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                            $pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $cl['leadspeek_api_id'];
                            $pauseoptions = [
                                'headers' => [
                                    'Authorization' => 'Bearer ' . $appkey,
                                ],
                                'json' => [
                                    "SubClient" => [
                                        "ID" => $cl['leadspeek_api_id'],
                                        "Name" => trim($company_name),
                                        "CustomID" => $tryseraCustomID ,
                                        "Active" => false
                                    ]       
                                ]
                            ]; 
                            $pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                            $result =  json_decode($pauseresponse->getBody());

                            /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                            
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                            if ($organizationid != '' && $campaignsid != '') {
                                $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                        }

                        /** PUT CLIENT TO ARCHIVE OR STOP */
                        $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                        $updateLeadspeekUser->active = 'F';
                        $updateLeadspeekUser->disabled = 'T';
                        $updateLeadspeekUser->active_user = 'F';
                        $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                        $updateLeadspeekUser->save();
                        /** PUT CLIENT TO ARCHIVE OR STOP */

                        $updateUser = User::find($_user_id);
                        $updateUser->lp_enddate = null;
                        $updateUser->lp_limit_startdate = null;
                        $updateUser->save();
                        
                        $clientStartBilling = date('Ymd',strtotime($clientLimitStartDate));
                        $nextBillingDate = date('Ymd');

                        /** CREATE INVOICE AND SENT IT */
                        //$invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxperTerm,$clientCostPerLead,$clientMinCostMonth,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail);
                        /** CREATE INVOICE AND SENT IT */

                        /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                        $this->notificationStartStopLeads($clientAdminNotify,'Stopped (One Time ' . date('m-d-Y',strtotime($clientLimitStartDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                        /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                    }
                    /** IF ONE TIME LIMIT FREQUENCY LESS THAN TOTAL LEADS SINCE START */

                }
                /** CHECK IF THE PAYMENT IS ONE TERM AND SHOULD BE MAKE USER INACTIVE AND SENT INVOICE */

            }

        }
        

    }

    private function countDays($day, $start, $end)
    {        
        //get the day of the week for start and end dates (0-6)
        $w = array(date('w', $start), date('w', $end));
    
        //get partial week day count
        if ($w[0] < $w[1])
        {            
            $partialWeekCount = ($day >= $w[0] && $day <= $w[1]);
        }else if ($w[0] == $w[1])
        {
            $partialWeekCount = $w[0] == $day;
        }else
        {
            $partialWeekCount = ($day >= $w[0] || $day <= $w[1]);
        }
    
        //first count the number of complete weeks, then add 1 if $day falls in a partial week.
        return floor( ( $end-$start )/60/60/24/7) + $partialWeekCount;
    }

    private function notificationStartStopLeads($adminnotify,$status,$name,$leadslimit,$leadstype,$leadstotal,$companyID = '') {
        /** FIND ADMIN EMAIL */
        $tmp = User::select('email')->whereIn('id', $adminnotify)->get();
        $adminEmail = array();
        foreach($tmp as $ad) {
            array_push($adminEmail,$ad['email']);
        }
        array_push($adminEmail,'harrison@uncommonreach.com');
        /** FIND ADMIN EMAIL */

        $AdminDefault = $this->get_default_admin($companyID);
        $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'newleads@leadspeek.com';
                                    
        $details = [
            'status'  => $status,
            'name' => $name,
            'leadslimit' => $leadslimit,
            'leadstype' => $leadstype,
            'leadstotal' => $leadstotal,
        ];
        $attachement = array();

        $from = [
            'address' => $AdminDefaultEmail,
            'name' => 'campaign status',
            'replyto' => $AdminDefaultEmail,
        ];

        $this->send_email($adminEmail,'User Notification for ' . $name . ' (' . $status . ')',$details,$attachement,'emails.tryserastartstop',$from,$companyID);
    }

    private function insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$id,$email,$ip,$source,$optindate,$clickdate,
    $referer,$phone,$firstname,$lastname,$address1,$address2,$city,$state,$zipcode,$active = 'T',$pricelead = '0',$platform_pricelead = '0') {
        /** INSERT INTO LEADSPEEK REPORT */
        $leadspeekReport = LeadspeekReport::create([
            'id' => $id,
            'person_id' => 0,
            'lp_user_id' => $_lp_user_id,
            'company_id' => $_company_id,
            'user_id' => $_user_id,
            'leadspeek_api_id' => $_leadspeek_api_id,
            'email' => $email,
            'email2' => '',
            'original_md5' => '',
            'ipaddress' => $ip,
            'source' => $source,
            'optindate' => $optindate,
            'clickdate' => $clickdate,
            'referer' => $referer,
            'phone' => $phone,
            'phone2' => '',
            'firstname' => $firstname,
            'lastname' => $lastname,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'state' => $state,
            'zipcode' => $zipcode,
            'price_lead' => $pricelead,
            'platform_price_lead' => $platform_pricelead,
            'keyword' => '',
            'description' => '',
            'active' => $active,
        ]);
        /** INSERT INTO LEADSPEEK REPORT */

        return $leadspeekReport;
    }

    public function matcheslist(Request $request,$subclientID = '') {
        date_default_timezone_set('America/Chicago');
        exit;die();
        $http = new \GuzzleHttp\Client;
        
        $appkey = config('services.trysera.api_id');
        $domain = config('services.trysera.domain');
        $campaignID = config('services.trysera.campaignid');
        
        $apiURL =  config('services.trysera.endpoint') . 'matches';
        
        /** TEMPORARY REPORT */
        $subclientList = array('2283','2284','2285','2286','2287','2288','2289','2290','2291');
        $clientName = array('Reese Legal','Real Estate Newark','Agent Scout','Uncommon Reach','AAI','Rounded Benefits','Advoria','The Jackson Agency','Kelly Parker');
        $clientEmail = array('steve@reese.legal','carrie@uncommonreach.com',array('brenda.cyrus@realbraintechnology.com','kim.lewis@realbraintechnology.com','brian.kovacs@realbraintechnology.com'),'carrie@uncommonreach.com','sfernandez@insuranceaai.com','','','meljjackson@allstate.com','carrie@uncommonreach.com');
        $spreadSheetID = array('1nOxzSnZF_LyPyvAEKwtoTmL40UYfc3frW3ziQa8bZDc','1PRd16bMRs71yYZCPThjSU27c73sMiuN5axzU7XJr52M','1hwP82zV9vHgn5z46854RhHRUJoO7ypCgoQrLFDfZK1s','1MbGlb-GDRB_u10HngaOShpTi5e-9qPkL6ALIlix2a_8','1C_leM6hbDXJIq72ncslKohgugRwTv2Qz0ACxouX6nhs','1jm28MWQjxuQzBEFTHf7qHS1WvFx5Sv3mlZPKUpZZWoQ','1SSKb82JtrWUlV6KDRmhEEMsPu6GKp3elOwxbNkJgQ80','1qW3AEErk_E3E1KxIUNjtY5n9y2D2MDkpBJmFFqLM1r0','1RMwUis-zJKT_WXhcp_JEispPziFRRWg7zhATcY2IAkw');
       
        $reportExist = false;

        $i = 0;
        try {
            $attachementlist = array();
            $attachementlink = array();

            foreach ($subclientList as $subcl) {
                /** START GENERATE REPORT */
                $options = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $appkey,
                    ],
                    'json' => [
                        "StartDate" => date('Y-m-d') . "T" . "00:00:00",
                        "EndDate" => date('Y-m-d') . "T" . "23:59:59.9999999",
                        "SubClient" => [
                            "ID" => $subcl,
                            "CustomID" => "",
                        ],
                        "ResultsPerPage" => 10000,
                        "Page" => 0,
                        "NonDownloaded" => false,
                        "MarkAsDownloaded" => false,
                        
                    ]
                ]; 
            
            
                $response = $http->post($apiURL,$options);
                $jsonresult =  json_decode($response->getBody());
                
                if (count((array) $jsonresult->Matches) > 0) {
                    $reportExist = true;

                    /** TO GENERATE GOOGLE SHEET */
                    $client = new GoogleSheet($spreadSheetID[$i],date('Y'));
                    $attachementlink[] = $clientName[$i] . ' Report : ' . 'https://docs.google.com/spreadsheets/d/' . $spreadSheetID[$i] . '/edit?usp=sharing';
                    $matches = $jsonresult->Matches;

                    foreach($matches as $row) {
                        $content[] = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,$row->FirstName,$row->LastName,$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                    }

                    $savedData = $client->saveDataToSheet($content);
                    unset($content);
                    /** TO GENERATE GOOGLE SHEET */

                    /** SENT REPORT TO CLIENT */
                    /*if ($clientEmail[$i] != '') {
                        $_to = $clientEmail[$i];
                        $_to_name = $clientName[$i];

                        $details = [
                            'name'  => $_to_name,
                            'links' => 'https://docs.google.com/spreadsheets/d/' . $spreadSheetID[$i] . '/edit?usp=sharing',
                        ];
    
                        Mail::to($_to)->send(new Gmail('LeadsPeek Report Match List ' . date('m-d-Y'),'newleads@leadspeek.com',$details,'emails.tryseramatchlistclient'));

                    }*/
                    /** SENT REPORT TO CLIENT */

                    /** TO GENERATE CSV */
                    /*$csvFileName = 'reports/' . $clientName[$i] . '_' . $subcl . '_' . date('Y_m_d') . '.csv';
                    $attachementlist[] = public_path($csvFileName);

                    $fp = fopen($csvFileName, 'w');

                    $headerfield = $jsonresult->Fields;
                    $matches = $jsonresult->Matches;

                    fputcsv($fp, $headerfield);

                    foreach($matches as $row) {
                        $content = array($row->ID,$row->Email,$row->IP,$row->Source,$row->OptInDate,$row->ClickDate,$row->Referer,$row->Phone,$row->FirstName,$row->LastName,$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode);
                        fputcsv($fp, $content);
                    }

                    fclose($fp);
                    */
                    /** TO GENERATE CSV */
                }
            
                /** START GENERATE REPORT */

                $i++;
            }   
            

            /** SEND THE EMAIL */
            if($reportExist) {
                $_to = array('carrie@uncommonreach.com','daniel@uncommonreach.com','casey@uncommonreach.com','harrison.budiman@gmail.com');
                $_to_name = array('Carrie','Daniel','Casey','Harrison');
                //$_to = array('harrison.budiman@gmail.com');
                //$_to_name = array('Harrison Budiman');

                $i = 0;

                foreach($_to as $to) {

                    $details = [
                        'name'  => $_to_name[$i],
                        'links' => $attachementlink,
                    ];

                    //Mail::to($to)->send(new Gmail('Exact Match Marketing Report Match List ' . date('m-d-Y'),'noreplay.emm@gmail.com',$details,'emails.tryseramatchlist',$attachementlist));
                    Mail::to($to)->send(new Gmail('LeadsPeek Report Match List ' . date('m-d-Y'),'newleads@leadspeek.com',$details,'emails.tryseramatchlist'));
                    $i++;
                }
                    return response()->json('success', 200);
            }
            /** SEND THE EMAIL */


            //return response()->json($jsonresult,200);

        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            if ($e->getCode() === 400) {
                return response()->json('Invalid Request. Please enter a username or a password.', $e->getCode());
            } else if ($e->getCode() === 401) {
                return response()->json('Your credentials are incorrect. Please try again', $e->getCode());
            }

            return response()->json('Something went wrong on the server.', $e->getCode());
        }
    }

    /** TRYSERA API */

    /** EMAIL METRICS MINIMUM SPEND 1 MONTH AND 2 MONTH */
    public function MinimumSpendNotificationEmail() {
        $confAppSysID = config('services.application.systemid');
        $getcompanysetting = CompanySetting::where('company_id',$confAppSysID)->whereEncrypted('setting_name','rootminspend')->get();
        $companysetting = "";
        if (count($getcompanysetting) > 0) {
            $companysetting = json_decode($getcompanysetting[0]['setting_value']);
            $enabledMinSpend = ($companysetting->enabled == 'T')?true:false;

            if ($enabledMinSpend) {
                $minSpend = $companysetting->minspend;
                $excludecompanyid = explode(",",$companysetting->excludecompanyid);
                           
                // Get the users that meet the condition
                //$system_date = Carbon::parse('2024-08-01')->format('Y-m-d');  // Example system date
                //$system_date_not_format = Carbon::parse('2024-08-01');  // Example system date

                $system_date = Carbon::now()->format('Y-m-d');  // Example system date
                $system_date_not_format = Carbon::now();  // Example system date

                $oneMonthDates[] = $system_date_not_format->copy()->subMonth(1)->format('Y-m-d');
                $twoMonthDates[] = $system_date_not_format->copy()->subMonths(2)->format('Y-m-d');

                // Check if system_date is the 1st of the month
                if ($system_date_not_format->day == 1) {
                    $oneMonthDateStart = $system_date_not_format->copy()->subMonth(2)->startOfMonth();
                    $twoMonthDateStart = $system_date_not_format->copy()->subMonths(3)->startOfMonth();
                    // return response()->json(['oneMonthDates2' => $oneMonthDates], 404);

                    // Get the range for one month and two months prior
                    $oneMonthDates = [
                        $oneMonthDateStart->copy()->addDays(28)->format('Y-m-d'), // 29th
                        $oneMonthDateStart->copy()->addDays(29)->format('Y-m-d'), // 30th
                        $oneMonthDateStart->copy()->addDays(30)->format('Y-m-d'), // 31st
                    ];
                    $twoMonthDates = [
                        $twoMonthDateStart->copy()->addDays(28)->format('Y-m-d'), // 29th
                        $twoMonthDateStart->copy()->addDays(29)->format('Y-m-d'), // 30th
                        $twoMonthDateStart->copy()->addDays(30)->format('Y-m-d'), // 31st
                    ];
                }

                    $users = User::where('users.company_parent','=',$confAppSysID)
                                    ->where('users.active','=','T')
                                    ->where('users.user_type','=','userdownline')
                                    ->where('users.customer_payment_id','<>','')
                                    ->where('users.customer_card_id','<>','')
                                    ->whereNotNull('users.trial_end_date')
                                    ->whereNotIn('users.company_id',$excludecompanyid)
                    ->where(function($query) use ($oneMonthDates, $twoMonthDates) {
                        $query->whereIn(DB::raw('DATE(created_at)'), $oneMonthDates)
                            ->orWhereIn(DB::raw('DATE(created_at)'), $twoMonthDates);
                    })
                    ->get();

                if ($users->isEmpty()) {
                    //echo "Minimum Spend Notification 1 - 2 Month: None Agency to be notified " . Carbon::now();
                    Log::warning("Minimum Spend Notification 1 - 2 Month: None Agency to be notified " . Carbon::now());
                    //return response()->json(['message' => 'No users found'], 404);
                    return "";
                    exit;die();
                }
            
                $emailsSent = 0;
                $emailsFailed = 0;
                $failedUsers = [];
            
                foreach ($users as $user) {
                    $company_id = $user->company_id;
                    $company_root_id = $user->company_root_id;
                    $user_created_at = $user->created_at->format('Y-m-d');
            
                    // Calculate the 30th and 60th day after the user creation
                    $thirtyDaysAfterDate = $user->created_at->copy()->addMonth(1);
                    $sixtyDaysAfterDate = $user->created_at->copy()->addMonth(2);
            
                    // Adjust date to next month startdate if it falls on the 29th, 30th, or 31st
                    $chkThirtyDayFwd = $thirtyDaysAfterDate->day >= 29 ? $thirtyDaysAfterDate->copy()->addMonth(1)->startOfMonth()->format('Y-m-d') : $thirtyDaysAfterDate->format('Y-m-d');
                    $chkSixtyDayFwd = $sixtyDaysAfterDate->day >= 29 ? $sixtyDaysAfterDate->copy()->addMonth(1)->startOfMonth()->format('Y-m-d') : $sixtyDaysAfterDate->format('Y-m-d');
                    $nextMonthMinimumSpend = 0;
                    $minimumSpend = 0;
                    $shortFall = 0;
                    $totalSpend = 0;

                    $subject = '1st Month Platform Metric Report';
                    $track = "";

                    // Check if the email should be sent on the adjusted 30th or 60th day
                    if ($system_date == $chkThirtyDayFwd || $system_date == $chkSixtyDayFwd) {
                        if ($system_date == $chkThirtyDayFwd) {
                            $totalSpend = DB::table('leadspeek_reports as lr')
                                ->join('users as u', 'lr.company_id', '=', 'u.company_id')
                                ->where('lr.created_at', '>=', $user_created_at)
                                ->where('lr.created_at', '<', $chkThirtyDayFwd)
                                ->where('u.company_parent', $company_id)
                                ->where('u.company_root_id', $company_root_id)
                                ->sum('lr.platform_price_lead') ?: 0;

                                $subject = 'First Month Platform Metric Report';
                                $track = "First Month Date between >= " . $user_created_at . ' and < ' . $chkThirtyDayFwd;
                        } elseif ($system_date == $chkSixtyDayFwd) {
                            $totalSpend = DB::table('leadspeek_reports as lr')
                                ->join('users as u', 'lr.company_id', '=', 'u.company_id')
                                ->where('lr.created_at', '>=', $chkThirtyDayFwd)
                                ->where('lr.created_at', '<', $chkSixtyDayFwd)
                                ->where('u.company_parent', $company_id)
                                ->where('u.company_root_id', $company_root_id)
                                ->sum('lr.platform_price_lead') ?: 0;
                            $nextMonthMinimumSpend = $minSpend;

                            $subject = 'Second Month Platform Metric Report';
                            $track = "Second Month Date between >= " . $chkThirtyDayFwd . ' and < ' . $chkSixtyDayFwd;
                        }
            
                        $details = [
                            'totalSpend' => number_format($totalSpend,2,'.',''),
                            'minimumSpend' => number_format($minimumSpend,2,'.',''),
                            'nextMonthMinimumSpend' => number_format($nextMonthMinimumSpend,2,'.',''),
                            'shortFall' => number_format($shortFall,2,'.',''),
                            'thirtyDaysAfterDate' => $chkThirtyDayFwd,
                            'sixtyDaysAfterDate' => $chkSixtyDayFwd,
                            'system_date' => $system_date,
                        ];
            
                        $from = [
                            'address' => 'noreply@exactmatchmarketing.com',
                            'name' => 'support',
                            'replyto' => 'noreply@exactmatchmarketing.com',
                        ];
            
                        // Send the email
                        try {
                            //echo $subject;
                            $this->send_email(array($user->email,'harrison@uncommonreach.com'),$subject,$details,array(), 'emails.minspendnotificationFirstSecondMonth',$from,$confAppSysID);
                            Log::warning("Minimum Spend Notification " . $track . " EMAIL SENT  USER :" . $user->email . " Total Spend: $" . $totalSpend . " Minimum Spend: $" . $minimumSpend . " ShortFall: $" . $shortFall); 
                            $emailsSent++;
                        } catch (\Exception $e) {
                            $emailsFailed++;
                            $failedUsers[] = $user->email;
                            //echo $e->getMessage();
                            Log::warning("Minimum Spend Notification " . $track . " EMAIL FAILED  USER :" . $user->email . " Total Spend: $" . $totalSpend . " Minimum Spend: $" . $minimumSpend . " ShortFall: $" . $shortFall);
                        }
                    }else{
                        Log::warning("Minimum Spend Notification NOTHING To Send Nothing in 1 or 2 month");
                    }
                }
            
                if ($emailsSent > 0) {
                    // return response()->json([
                    //     'message' => 'Emails processing completed',
                    //     'emails_sent' => $emailsSent,
                    //     'emails_failed' => $emailsFailed,
                    //     'failed_users' => $failedUsers,
                    // ], 200);
                }else{
                    // return response()->json([
                    //     'message' => 'Emails processing completed but no email send',
                    //     'emails_sent' => $emailsSent,
                    //     'emails_failed' => $emailsFailed,
                    //     'failed_users' => $failedUsers,
                    // ], 404);        
                }

            }
        }

    }
    /** EMAIL METRICS MINIMUM SPEND 1 MONTH AND 2 MONTH */

    /** MINIMUM SPEND AGENCY FOR EMM PLATFORM ONLY */
    public function minimumspendinvoice(Request $request) {
        /** GET SETTING MINIMUM SPEND ROOT */
        $confAppSysID = config('services.application.systemid');
        $getcompanysetting = CompanySetting::where('company_id',$confAppSysID)->whereEncrypted('setting_name','rootminspend')->get();
        $companysetting = "";
        if (count($getcompanysetting) > 0) {
            $companysetting = json_decode($getcompanysetting[0]['setting_value']);
            $enabledMinSpend = ($companysetting->enabled == 'T')?true:false;

            if ($enabledMinSpend) {
                $minSpend = $companysetting->minspend;
                $excludecompanyid = explode(",",$companysetting->excludecompanyid);
                $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);

                $downline = User::select('users.id','users.email','users.company_id','users.company_parent','users.company_root_id','users.created_at','users.trial_end_date','users.last_invoice_minspend','users.customer_payment_id','users.customer_card_id',
                                        DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(`company_name`), '" . $salt . "') USING utf8mb4) as `company_name`"))
                            ->join('companies','companies.id','=','users.company_id')
                            ->where('users.company_parent','=',$confAppSysID)
                            ->where('users.active','=','T')
                            ->where('users.user_type','=','userdownline')
                            ->where('users.customer_payment_id','<>','')
                            ->where('users.customer_card_id','<>','')
                            ->whereNotNull('users.trial_end_date')
                            ->whereDate('users.last_invoice_minspend','<=', now()->subMonths(1))
                            //->whereDate('users.last_invoice_minspend','<=', '2024-05-01 11:20:08')
                            ->whereNotIn('users.company_id',$excludecompanyid)
                            //->with('children')
                           ->get();

                foreach($downline as $dl) {
                    //$startBill = date('Ymd', strtotime("-1 day",strtotime($dl['last_invoice_minspend'])));
                    $startBill = date('Ymd', strtotime($dl['last_invoice_minspend']));
                    $endBill = date('Ymd',strtotime('-1 day'));
                    $startBillDate = date('d', strtotime($dl['last_invoice_minspend']));

                    if (in_array($startBillDate, ['29', '30', '31'])) {
                        /** UPDATE THE LAST INVOICE TO FIRST OF THE NEXT MONTH */
                        $newlastInvoiceDate = date('Y-m-01 H:i:s',strtotime('+1 month',strtotime($dl['last_invoice_minspend'])));
                        $downlineUpdate = User::find($dl['id']);
                        $downlineUpdate->last_invoice_minspend = $newlastInvoiceDate;
                        $downlineUpdate->save();
                        Log::warning("MOVE TO 1 NEXT MONTH : " . $newlastInvoiceDate . ' Company Name:' . $dl['company_name'] . ' UserID:' . $dl['id']);
                        /** UPDATE THE LAST INVOICE TO FIRST OF THE NEXT MONTH */
                    }else{
                                       
                        $reportCalculation = LeadspeekReport::select(DB::raw("COUNT(*) as total"),DB::raw("SUM(platform_price_lead) as platform_costleadprice"))
                                            ->join('users','leadspeek_reports.company_id','=','users.company_id')
                                            ->where('users.active','T')
                                            ->where('users.user_type','=','client')
                                            ->where('users.company_parent','=',$dl['company_id'])
                                            ->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d%H%i%s")'),'>=',$startBill)
                                            ->where(DB::raw('DATE_FORMAT(clickdate,"%Y%m%d%H%i%s")'),'<=',$endBill)
                                            ->groupBy('users.company_parent')
                                            ->get();

                        $_minSpend = (float) $minSpend;

                        Log::warning("START CHARGE MINIMUM SPEND FOR " . $dl['company_name'] . ' Created: ' . $dl['created_at'] . ' LastInvoiceDate: ' . $dl['last_invoice_minspend'] . ' ReportStartDate: ' . date('d-m-Y',strtotime($startBill)) . ' ReportEndDate: ' . date('d-m-Y',strtotime($endBill)));
                        
                        if (count($reportCalculation) > 0) {
                            Log::warning("TOTAL COUNT DATA : " . $reportCalculation[0]['total']);
                            Log::warning("TOTAL COST DATA : " . $reportCalculation[0]['platform_costleadprice']);
                            $totalCost = (float) $reportCalculation[0]['platform_costleadprice'];

                            if ($totalCost < $_minSpend) {
                                /** CHARGE THE CLIENT WITH THE DIFFERENCE BETWEEN IT */
                                    $diffAmount = $_minSpend - $totalCost;
                                    Log::warning("AGENCY CHARGE MIN SPEND DIFFERENCE: " . $diffAmount);
                                    /** GET STRIPE KEY */
                                    $stripeseckey = config('services.stripe.secret');
                                    $stripepublish = $this->getcompanysetting($dl['company_root_id'],'rootstripe');
                                    if ($stripepublish != '') {
                                        $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
                                    }
                                    /** GET STRIPE KEY */

                                    $stripe = new StripeClient([
                                        'api_key' => $stripeseckey,
                                        'stripe_version' => '2020-08-27'
                                    ]);
                                    
                                    $descCharge = '';
                                    try{
                                        $paymentintentID = "";
                                        $descCharge = 'Monthly minimum spend - ' . $dl['company_name'] . ' ' . date('Y-m-d', strtotime($dl['last_invoice_minspend'])) . ' - ' .  date('Y-m-d',strtotime($endBill)) .' Wholesale Spend:$' . $totalCost . ' Min Spend:$' . $_minSpend;
                                        $payment_intent =  $stripe->paymentIntents->create([
                                            'payment_method_types' => ['card'],
                                            'customer' => trim($dl['customer_payment_id']),
                                            'amount' => ($diffAmount * 100),
                                            'currency' => 'usd',
                                            'receipt_email' => $dl['email'],
                                            'payment_method' => $dl['customer_card_id'],
                                            'confirm' => true,
                                            'description' =>  $descCharge,
                                        ]);
                                        $paymentintentID = $payment_intent->id;

                                        // /** UPDATe LAST INVOICE MINSPEND DAte */
                                        // $downlineUpdate = User::find($dl['id']);
                                        // $downlineUpdate->last_invoice_minspend = date("Y-m-d H:i:s");
                                        // $downlineUpdate->save();
                                        // /** UPDATe LAST INVOICE MINSPEND DAte */
                                    }catch (RateLimitException $e) {
                                        // Too many requests made to the API too quickly
                                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                        $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                                    } catch (InvalidRequestException $e) {
                                        // Invalid parameters were supplied to Stripe's API
                                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                        $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                                    } catch (ApiConnectionException $e) {
                                        // Network communication with Stripe failed
                                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                        $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                                    } catch (ApiErrorException $e) {
                                        // Display a very generic error to the user, and maybe send
                                        // yourself an email
                                        $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                        $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                                    } catch (Exception $e) {
                                        // Something else happened, completely unrelated to Stripe
                                        $errorstripe = 'error not stripe things : ' . $e->getMessage();
                                        $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                                    }

                                    /** UPDATE LAST INVOICE MINSPEND DATE SUCCESS OR NOT */
                                    $downlineUpdate = User::find($dl['id']);
                                    $downlineUpdate->last_invoice_minspend = date("Y-m-d H:i:s");
                                    $downlineUpdate->save();
                                    /** UPDATE LAST INVOICE MINSPEND DATE SUCCESS OR NOT */

                                    /** SEND PLATFORM METRICS REPORT */
                                    $title = "Monthly Platform Metric Report";
                                            
                                    $details = [
                                        'totalcost' => number_format($totalCost,2,'.',''),
                                        'minspend' => number_format($_minSpend,2,'.',''),
                                        'differencetotal' => number_format($diffAmount,2,'.',''),
                                    ];
                            
                                    $from = [
                                        'address' => 'noreply@exactmatchmarketing.com',
                                        'name' => 'support',
                                        'replyto' => 'noreply@exactmatchmarketing.com',
                                    ];

                                    $this->send_email(array($dl['email'],'harrison@uncommonreach.com'),$title,$details,array(),'emails.minspendnotification',$from,$confAppSysID);
                                    /** SEND PLATOFORM METRICS REPORT */

                                /** CHARGE THE CLIENT WITH THE DIFFERENCE BETWEEN IT */
                            }else{
                                /** UPDATE LAST INVOICE MINSPEND DATE SUCCESS OR NOT */
                                $downlineUpdate = User::find($dl['id']);
                                $downlineUpdate->last_invoice_minspend = date("Y-m-d H:i:s");
                                $downlineUpdate->save();
                                /** UPDATE LAST INVOICE MINSPEND DATE SUCCESS OR NOT */

                                /** CHECK IF THE TOTAL COST BELOW MINSPEND + $100 THEN STILL SEND EMAIL */
                                $minspend_padding = ($_minSpend + 100);
                                
                                if ($totalCost < $minspend_padding) {
                                    $diffAmount = $minspend_padding - $totalCost;

                                    $title = "Monthly Platform Metric Report";
                                    
                                    $details = [
                                        'totalcost' => number_format($totalCost,2,'.',''),
                                        'minspend' => number_format($_minSpend,2,'.',''),
                                        'differencetotal' => '0',
                                    ];
                            
                                    $from = [
                                        'address' => 'noreply@exactmatchmarketing.com',
                                        'name' => 'support',
                                        'replyto' => 'noreply@exactmatchmarketing.com',
                                    ];

                                    $this->send_email(array($dl['email'],'harrison@uncommonreach.com'),$title,$details,array(),'emails.minspendnotification',$from,$confAppSysID);
                                    Log::warning("TOTAL COST BELOW MINSPEND + $100 SPEND DIFFERENCE: " . $diffAmount);
                                }
                                /** CHECK IF THE TOTAL COST BELOW $600 THEN STILL SEND EMAIL */
                                Log::warning("AGENCY NOTHING to CHARGE, TOTAL DATA COST > MIN COST SPEND " . $totalCost);    
                            }

                        }else{ /** CHARGED BECAUSE NOT ANY REPORT GENERATED */

                            /** GET STRIPE KEY */
                            $stripeseckey = config('services.stripe.secret');
                            $stripepublish = $this->getcompanysetting($dl['company_root_id'],'rootstripe');
                            if ($stripepublish != '') {
                                $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
                            }
                            /** GET STRIPE KEY */

                            $stripe = new StripeClient([
                                'api_key' => $stripeseckey,
                                'stripe_version' => '2020-08-27'
                            ]);
                            
                            $descCharge = '';
                            try{
                                $paymentintentID = "";
                                $descCharge = 'Monthly minimum spend - ' . $dl['company_name'] . ' ' . date('Y-m-d', strtotime($dl['last_invoice_minspend'])) . ' - ' .  $endBill .' Wholesale Spend:$0 Min Spend:$' . $_minSpend;
                                $payment_intent =  $stripe->paymentIntents->create([
                                    'payment_method_types' => ['card'],
                                    'customer' => trim($dl['customer_payment_id']),
                                    'amount' => ($_minSpend * 100),
                                    'currency' => 'usd',
                                    'receipt_email' => $dl['email'],
                                    'payment_method' => $dl['customer_card_id'],
                                    'confirm' => true,
                                    'description' =>  $descCharge,
                                ]);
                                $paymentintentID = $payment_intent->id;

                                // /** UPDATe LAST INVOICE MINSPEND DAte */
                                // $downlineUpdate = User::find($dl['id']);
                                // $downlineUpdate->last_invoice_minspend = date("Y-m-d H:i:s");
                                // $downlineUpdate->save();
                                // /** UPDATe LAST INVOICE MINSPEND DAte */
                            }catch (RateLimitException $e) {
                                // Too many requests made to the API too quickly
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                            } catch (InvalidRequestException $e) {
                                // Invalid parameters were supplied to Stripe's API
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                            } catch (ApiConnectionException $e) {
                                // Network communication with Stripe failed
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                            } catch (ApiErrorException $e) {
                                // Display a very generic error to the user, and maybe send
                                // yourself an email
                                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                                $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                            } catch (Exception $e) {
                                // Something else happened, completely unrelated to Stripe
                                $errorstripe = 'error not stripe things : ' . $e->getMessage();
                                $this->send_notif_stripeerror('Agency Minimum Spend Charge Failed',$descCharge . '<br/><br/>Err :' . $errorstripe,$dl['company_root_id']);
                            }

                            /** UPDATE LAST INVOICE MINSPEND DATE SUCCESS OR NOT */
                            $downlineUpdate = User::find($dl['id']);
                            $downlineUpdate->last_invoice_minspend = date("Y-m-d H:i:s");
                            $downlineUpdate->save();
                            /** UPDATE LAST INVOICE MINSPEND DATE SUCCESS OR NOT */

                            /** SEND PLATFORM METRICS REPORT */
                            $title = "Monthly Platform Metric Report";
                                    
                            $details = [
                                'totalcost' => '0',
                                'minspend' => number_format($_minSpend,2,'.',''),
                                'differencetotal' => number_format($_minSpend,2,'.',''),
                            ];
                    
                            $from = [
                                'address' => 'noreply@exactmatchmarketing.com',
                                'name' => 'support',
                                'replyto' => 'noreply@exactmatchmarketing.com',
                            ];

                            $this->send_email(array($dl['email'],'harrison@uncommonreach.com'),$title,$details,array(),'emails.minspendnotification',$from,$confAppSysID);
                            /** SEND PLATOFORM METRICS REPORT */
                            Log::warning("AGENCY FULL MIN SPEND CHARGE BECAUSE NOT GENERATED ANY DATA ON THAT PERIOD: " . $_minSpend); 
                        }

                    }

                }
                
            }
        }
        

        /** GET SETTING MINIMUM SPEND ROOT */
    }
    /** MINIMUM SPEND AGENCY FOR EMM PLATFORM ONLY */
}
