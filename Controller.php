<?php

namespace App\Http\Controllers;

use App\Mail\Gmail;
use App\Models\Company;
use App\Models\CompanySale;
use App\Models\CompanySetting;
use App\Models\CompanyStripe;
use App\Models\EmailNotification;
use App\Models\FailedLeadRecord;
use App\Models\FailedRecord;
use App\Models\IntegrationSettings;
use App\Models\LeadspeekReport;
use App\Models\LeadspeekUser;
use App\Models\ReportAnalytic;
use App\Models\User;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Client\RequestException;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException as ExceptionAuthenticationException;
use Stripe\Exception\OAuth\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\StripeClient;
use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_TransportException;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function createFailedLeadRecord($email_encrypt,$leadspeek_api_id,$function,$url,$type,$description)
    {
        FailedLeadRecord::create([
            'email_encrypt' => $email_encrypt,
            'leadspeek_api_id' => $leadspeek_api_id,
            'function' => $function,
            'url' => $url,
            'type' => $type,
            'description' => $description,
        ]);
    }

    public function UpsertReportAnalytics($leadspeek_api_id,$leadspeekType = "",$typeReport,$value = '') {

        $chkExist = ReportAnalytic::select('id')
                        ->where('leadspeek_api_id','=',$leadspeek_api_id)
                        ->where('date','=',date('Y-m-d'))
                        ->get();

        if (count($chkExist) > 0) {
            $reportAnalytic = ReportAnalytic::find($chkExist[0]['id']);
            if ($typeReport == "pixelfire") {
                $reportAnalytic->pixelfire = $reportAnalytic->pixelfire + 1;
            }else if ($typeReport == "towerpostal") {
                $reportAnalytic->towerpostal = $reportAnalytic->towerpostal + 1;
            }else if ($typeReport == "endatoenrichment") {
                $reportAnalytic->endatoenrichment = $reportAnalytic->endatoenrichment + 1;
            }else if ($typeReport == "toweremail") {
                $reportAnalytic->toweremail = $reportAnalytic->toweremail + 1;
            }else if ($typeReport == "zerobounce") {
                $reportAnalytic->zerobounce = $reportAnalytic->zerobounce + 1;
            }else if ($typeReport == "zerobouncefailed") {
                $reportAnalytic->zerobouncefailed = $reportAnalytic->zerobouncefailed + 1;
            }else if ($typeReport == "locationlock") {
                $reportAnalytic->locationlock = $reportAnalytic->locationlock + 1;
            }else if ($typeReport == "locationlockfailed") {
                $reportAnalytic->locationlockfailed = $reportAnalytic->locationlockfailed + 1;
            }else if ($typeReport == "serveclient") {
                $reportAnalytic->serveclient = $reportAnalytic->serveclient + 1;
            }else if ($typeReport == "notserve") {
                $reportAnalytic->notserve = $reportAnalytic->notserve + 1;
            }else if ($typeReport == "bigbdmemail") {
                $reportAnalytic->bigbdmemail = $reportAnalytic->bigbdmemail + 1;
            }else if ($typeReport == "bigbdmpii") {
                $reportAnalytic->bigbdmpii = $reportAnalytic->bigbdmpii + 1;
            }else if($typeReport == 'bigbdmhems') {
                $reportAnalytic->bigbdmhems = $value;
            }else if($typeReport == 'bigbdmtotalleads') {
                $reportAnalytic->bigbdmtotalleads = $value;
            }else if($typeReport == 'bigbdmremainingleads') {
                $reportAnalytic->bigbdmremainingleads = $value;
            }else if($typeReport == 'getleadfailed') {
                $reportAnalytic->getleadfailed = $reportAnalytic->getleadfailed + 1;
            }else if($typeReport == 'getleadfailed_bigbdmmd5') {
                $reportAnalytic->getleadfailed_bigbdmmd5 = $reportAnalytic->getleadfailed_bigbdmmd5 + 1;
            }else if($typeReport == 'getleadfailed_gettowerdata') {
                $reportAnalytic->getleadfailed_gettowerdata = $reportAnalytic->getleadfailed_gettowerdata + 1;
            }else if($typeReport == 'getleadfailed_bigbdmpii') {
                $reportAnalytic->getleadfailed_bigbdmpii = $reportAnalytic->getleadfailed_bigbdmpii + 1;
            }

            $reportAnalytic->save();

        }else{
            $pixelfire = ($typeReport == "pixelfire")?1:0;
            $towerpostal = ($typeReport == "towerpostal")?1:0;
            $endatoenrichment =($typeReport == "endatoenrichment")?1:0;
            $toweremail = ($typeReport == "toweremail")?1:0;
            $zerobounce = ($typeReport == "zerobounce")?1:0;
            $zerobouncefailed = ($typeReport == "zerobouncefailed")?1:0;
            $locationlock = ($typeReport == "locationlock")?1:0;
            $locationlockfailed = ($typeReport == "locationlockfailed")?1:0;
            $leadspeek_type = ($leadspeekType != "")?$leadspeekType:"local";
            $serveclient = ($typeReport == "serveclient")?1:0;
            $notserve = ($typeReport == "notserve")?1:0;
            $bigbdmhems = ($typeReport == 'bigbdmhems')?$value:0;
            $bigbdmtotalleads = ($typeReport == 'bigbdmtotalleads')?$value:0;
            $bigbdmremainingleads = ($typeReport == 'bigbdmremainingleads')?$value:0;
            $getleadfailed = ($typeReport == 'getleadfailed')?1:0;
            $getleadfailed_bigbdmmd5 = ($typeReport == 'getleadfailed_bigbdmmd5')?1:0;
            $getleadfailed_gettowerdata = ($typeReport == 'getleadfailed_gettowerdata')?1:0;
            $getleadfailed_bigbdmpii = ($typeReport == 'getleadfailed_bigbdmpii')?1:0;

            $reportAnalytic = ReportAnalytic::create([
                'date' => date('Y-m-d'),
                'leadspeek_api_id'=>$leadspeek_api_id,
                'pixelfire' => $pixelfire,
                'towerpostal' => $towerpostal,
                'endatoenrichment' => $endatoenrichment,
                'toweremail' => $toweremail,
                'zerobounce' => $zerobounce,
                'zerobouncefailed' => $zerobouncefailed,
                'locationlock' => $locationlock,
                'locationlockfailed' => $locationlockfailed,
                'leadspeek_type' => $leadspeek_type,
                'serveclient' => $serveclient,
                'notserve' => $notserve,
                'bigbdmhems' => $bigbdmhems,
                'bigbdmtotalleads' => $bigbdmtotalleads,
                'bigbdmremainingleads' => $bigbdmremainingleads,
                'getleadfailed' => $getleadfailed,
                'getleadfailed_bigbdmmd5' => $getleadfailed_bigbdmmd5,
                'getleadfailed_gettowerdata' => $getleadfailed_gettowerdata,
                'getleadfailed_bigbdmpii' => $getleadfailed_bigbdmpii,
            ]);
        }
    }
    
    public function getClientCapType($company_root_id)
    {
        $clientTypeLead = [
            'type' => '',
            'value' => ''
        ];

        $rootsetting = $this->getcompanysetting($company_root_id, 'rootsetting');

        if(!empty($rootsetting->clientcaplead)) {
            $clientTypeLead['type'] = 'clientcaplead';
            $clientTypeLead['value'] = $rootsetting->clientcaplead;
        } 
        if(!empty($rootsetting->clientcapleadpercentage)) {
            $clientTypeLead['type'] = 'clientcapleadpercentage';
            $clientTypeLead['value'] = $rootsetting->clientcapleadpercentage;
        }

        return $clientTypeLead;
    }

    Public function __construct()
    {
        date_default_timezone_set('America/Chicago');
    }

    public function check_connected_account($companyParentID,$idsys = "") {
        $accConID = '';
        $confAppSysID = config('services.application.systemid');
        if ($idsys != "") {
            $confAppSysID = $idsys;
        }

        if ($companyParentID != '' && $companyParentID != $confAppSysID) {
            $usrchk = User::select('user_type')
                            ->where('company_id','=',$companyParentID)
                            ->where('company_parent','=',$confAppSysID)
                            ->where('isAdmin','=','T')
                            ->get();

            if (count($usrchk) > 0) {
                if ($usrchk[0]['user_type'] == 'userdownline') {
                    $companyStripe = CompanyStripe::select('acc_connect_id')
                                            ->where('company_id','=',$companyParentID)
                                            ->where('status_acc','=','completed')
                                            ->get();

                    if (count($companyStripe) > 0) {
                        $accConID = $companyStripe[0]['acc_connect_id'];
                    }
                }
            }

        }
        return $accConID;
}

    public function send_notif_stripeerror($title,$content,$idsys = "") {

        $details = [
            'title' => $title,
            'content'  => $content,
        ];
    
        $attachement = array();
    
        $from = [
            'address' => 'noreply@exactmatchmarketing.com',
            'name' => 'Charge Error',
            'replyto' => 'support@exactmatchmarketing.com',
        ];
    
        //$CompanyID = config('services.application.systemid');
        $confAppSysID = config('services.application.systemid');
        if ($idsys != "") {
            $confAppSysID = $idsys;
        }
    
        $rootAdmin = User::select('name','email')->where('company_id','=',$confAppSysID)->where('active','T')->whereRaw("user_type IN ('user','userdownline')")->where('isAdmin','=','T')->get();
    
        $adminEmail = array();
        foreach($rootAdmin as $ad) {
            array_push($adminEmail,$ad['email']);
        }
    
        //$this->send_email($adminEmail,$title,$details,$attachement,'emails.customemail',$from,'');
        $this->send_email(array('harrison@uncommonreach.com'),$title,$details,$attachement,'emails.customemail',$from,'');
    }
    
    /** FOR STRIPE THINGS */
    public function transfer_commission_sales($companyParentID,$platformfee,$_leadspeek_api_id = "",$startdate = "0000-00-00 00:00:00",$enddate = "0000-00-00 00:00:00",$stripeseckey = "",$ongoingleads = "",$cleanProfit = "") {
        $stripe = new StripeClient([
            'api_key' => $stripeseckey,
            'stripe_version' => '2020-08-27'
        ]);

        $srID = 0;
        $aeID = 0;
        $arID = 0;

        /** CHECK IF THERE ARE SALES AND ACCOUNT EXECUTIVE */
        $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
        $saleslist = CompanySale::select(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.custom_commission), '" . $salt . "') USING utf8mb4) as `custom_commission`"),'users.custom_commission_enabled','company_sales.id','company_sales.sales_id','company_sales.sales_title','users.company_root_id',DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(`company_name`), '" . $salt . "') USING utf8mb4) as `company_name`"),DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.acc_connect_id), '" . $salt . "') USING utf8mb4) as `accconnectid`"),
                    DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.name), '" . $salt . "') USING utf8mb4) as `name`"),DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.email), '" . $salt . "') USING utf8mb4) as `email`"))
                                ->join('users','company_sales.sales_id','=','users.id')
                                ->join('companies','company_sales.company_id','=','companies.id')
                                ->where('company_sales.company_id','=',$companyParentID)
                                ->where('users.active','=','T')
                                ->where('users.user_type','=','sales')
                                ->where('users.status_acc','=','completed')
                                ->get();
    
        /** CHECK DEFAULT SALES COMMISSION */
        $AgencyPercentageCommission = 0.05;
        if (count($saleslist) > 0) {
            $rootAgencyPercentageCommission = $this->getcompanysetting($saleslist[0]['company_root_id'],'rootsetting');
            if ($rootAgencyPercentageCommission != '') {
                if(isset($rootAgencyPercentageCommission->defaultagencypercentagecommission) && $rootAgencyPercentageCommission->defaultagencypercentagecommission != "") {
                    $AgencyPercentageCommission = $rootAgencyPercentageCommission->defaultagencypercentagecommission;
                }
            }
        }
        /** CHECK DEFAULT SALES COMMISSION */

        $salesfee = ($platformfee * (float) $AgencyPercentageCommission);
        //$salesfee = ($cleanProfit != "")?($cleanProfit * (float) $AgencyPercentageCommission):$salesfee;
        $salesfee = number_format($salesfee,2,'.','');

        if (count($saleslist) > 0 && $platformfee > 0 && $salesfee > 0) {
            
            /** GET OTHER DETAILS */
            $_campaign_name = "";
            $_client_name = "";
            $_leadspeek_type = "";

            $campaigndetails = LeadspeekUser::select('leadspeek_users.campaign_name','companies.company_name','leadspeek_users.leadspeek_type')
                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                            ->join('companies','users.company_id','=','companies.id')
                                            ->where('leadspeek_users.leadspeek_api_id','=',$_leadspeek_api_id)
                                            ->get();
            if (count($campaigndetails) > 0) {
                $_campaign_name = $campaigndetails[0]['campaign_name'];
                $_client_name = $campaigndetails[0]['company_name'];
                $_leadspeek_type = $campaigndetails[0]['leadspeek_type'];
            }
            /** GET OTHER DETAILS */

            foreach($saleslist as $sale) {

                $overrideCommission = array();

                $salesfee = ($platformfee * (float) $AgencyPercentageCommission);
                //$salesfee = ($cleanProfit != "")?($cleanProfit * (float) $AgencyPercentageCommission):$salesfee;
                $salesfee = number_format($salesfee,2,'.','');

                /** OVERRIDE THE COMMISSION IF ENABLED */
                if ($sale['custom_commission_enabled'] == ' ' && $ongoingleads != "") {
                    $chkCommissionOverride = json_decode($sale['custom_commission']);
                    if ($_leadspeek_type == 'local') {
                        $overrideCommission['srSiteID'] = ($chkCommissionOverride->sr->siteid > 0)?$platformfee - ($chkCommissionOverride->sr->siteid * $ongoingleads):0;
                        $overrideCommission['aeSiteID'] = ($chkCommissionOverride->ae->siteid > 0)?$platformfee - ($chkCommissionOverride->ae->siteid * $ongoingleads):0;
                        $overrideCommission['arSiteID'] = ($chkCommissionOverride->ar->siteid > 0)?$platformfee - ($chkCommissionOverride->ar->siteid * $ongoingleads):0;
                    }else if ($_leadspeek_type == 'locator') {
                        $overrideCommission['srSearchID'] = ($chkCommissionOverride->sr->searchid > 0)?$platformfee - ($chkCommissionOverride->sr->searchid * $ongoingleads):0;
                        $overrideCommission['aeSearchID'] = ($chkCommissionOverride->ae->searchid > 0)?$platformfee - ($chkCommissionOverride->ae->searchid * $ongoingleads):0;
                        $overrideCommission['arSearchID'] = ($chkCommissionOverride->ar->searchid > 0)?$platformfee - ($chkCommissionOverride->ar->searchid * $ongoingleads):0;
                    }
                }
                /** OVERRIDE THE COMMISSION IF ENABLED */

                /** RETRIVE BALANCE */
                    $balance = $stripe->balance->retrieve([]);
                    $currbalance = $balance->available[0]->amount / 100;
                    $currbalance = number_format($currbalance,2,'.','');
                /** RETRIVE BALANCE */

                if ($currbalance >= $salesfee) {
                    $tmp = explode(" ",$sale['name']);

                    $details = [
                        'firstname' => $tmp[0],
                        'salesfee'  => $salesfee,
                        'companyname' =>  $sale['company_name'],
                        'clientname' => $_client_name,
                        'campaignname' =>  $_campaign_name,
                        'campaignid' =>$_leadspeek_api_id,
                        'start' => date('Y-m-d',strtotime($startdate)),
                        'end' => date('Y-m-d',strtotime($enddate)),
                    ];
                    $attachement = array();
        
                    $from = [
                        'address' => 'noreply@exactmatchmarketing.com',
                        'name' => 'Commission Fee',
                        'replyto' => 'support@exactmatchmarketing.com',
                    ];
                    
                    if ($sale['sales_title'] == "Sales Representative") {
                        try {

                            /** CHECK IF OVERRIDE COMMISSION ENABLED */
                            if ($sale['custom_commission_enabled'] == 'T' && $ongoingleads != "") {
                                if ($_leadspeek_type == 'local') {
                                    $salesfee = (isset($overrideCommission['srSiteID']))?number_format($overrideCommission['srSiteID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }else if ($_leadspeek_type == 'locator') {
                                    $salesfee = (isset($overrideCommission['srSearchID']))?number_format( $overrideCommission['srSearchID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }
                            }
                             /** CHECK IF OVERRIDE COMMISSION ENABLED */

                            if ($salesfee > 0) {
                                $transferSales = $stripe->transfers->create([
                                    'amount' => ($salesfee * 100),
                                    'currency' => 'usd',
                                    'destination' => $sale['accconnectid'],
                                    'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                ]);

                                if (isset($transferSales->destination_payment)) {
                                    $despay = $transferSales->destination_payment;

                                    $transferSalesDesc =  $stripe->charges->update($despay,
                                            [
                                                'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                            ],['stripe_account' => $sale['accconnectid']]);
                                }

                                $srID = $sale['sales_id'];
                                $this->send_email(array($sale['email']),'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id . '(SR)',$details,$attachement,'emails.salesfee',$from,$companyParentID);
                            }

                        }catch (Exception $e) {
                            //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>'SE error transfer'));
                            $this->send_notif_stripeerror('SE error transfer','SE error transfer for ' . $sale['company_name'] . ' #' . $_leadspeek_api_id,$sale['company_root_id']);
                        }
                        
                    }else if ($sale['sales_title'] == "Account Executive") {
                        try {

                            /** CHECK IF OVERRIDE COMMISSION ENABLED */
                            if ($sale['custom_commission_enabled'] == 'T' && $ongoingleads != "") {
                                if ($_leadspeek_type == 'local') {
                                    $salesfee = (isset($overrideCommission['aeSiteID']))?number_format( $overrideCommission['aeSiteID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }else if ($_leadspeek_type == 'locator') {
                                    $salesfee = (isset($overrideCommission['aeSearchID']))?number_format( $overrideCommission['aeSearchID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }
                            }
                             /** CHECK IF OVERRIDE COMMISSION ENABLED */

                            if ($salesfee > 0) { 
                                $transferSales = $stripe->transfers->create([
                                    'amount' => ($salesfee * 100),
                                    'currency' => 'usd',
                                    'destination' => $sale['accconnectid'],
                                    'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                ]);
                                
                                if (isset($transferSales->destination_payment)) {
                                    $despay = $transferSales->destination_payment;

                                    $transferSalesDesc =  $stripe->charges->update($despay,
                                            [
                                                'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                            ],['stripe_account' => $sale['accconnectid']]);
                                }

                                $aeID = $sale['sales_id'];
                                $this->send_email(array($sale['email']),'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id . '(AE)',$details,$attachement,'emails.salesfee',$from,$companyParentID);
                            }

                        }catch (Exception $e) {
                            //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>'AE error transfer'));
                            $this->send_notif_stripeerror('AE error transfer','AE error transfer for ' . $sale['company_name'] . ' #' . $_leadspeek_api_id,$sale['company_root_id']);
                        }
            
                    }else if ($sale['sales_title'] == "Account Referral") {
                        try {

                            /** CHECK IF OVERRIDE COMMISSION ENABLED */
                            if ($sale['custom_commission_enabled'] == 'T' && $ongoingleads != "") {
                                if ($_leadspeek_type == 'local') {
                                    $salesfee = (isset($overrideCommission['arSiteID']))?number_format( $overrideCommission['arSiteID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }else if ($_leadspeek_type == 'locator') {
                                    $salesfee = (isset($overrideCommission['arSearchID']))?number_format( $overrideCommission['arSearchID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }
                            }
                             /** CHECK IF OVERRIDE COMMISSION ENABLED */

                            if ($salesfee > 0) { 

                                $transferSales = $stripe->transfers->create([
                                    'amount' => ($salesfee * 100),
                                    'currency' => 'usd',
                                    'destination' => $sale['accconnectid'],
                                    'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                ]);
                                
                                if (isset($transferSales->destination_payment)) {
                                    $despay = $transferSales->destination_payment;

                                    $transferSalesDesc =  $stripe->charges->update($despay,
                                            [
                                                'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                            ],['stripe_account' => $sale['accconnectid']]);
                                }

                                $arID = $sale['sales_id'];
                                $this->send_email(array($sale['email']),'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id . '(AR)',$details,$attachement,'emails.salesfee',$from,$companyParentID);
                            }

                        }catch (Exception $e) {
                            //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>'AE error transfer'));
                            $this->send_notif_stripeerror('ACREF error transfer','ACREF error transfer for ' . $sale['company_name'] . ' #' . $_leadspeek_api_id,$sale['company_root_id']);
                        }
            
                    }
                }else{
                    $tmp = explode(" ",$sale['name']);
                    $this->send_notif_stripeerror('insufficient balance Commision for ' . $tmp[0],'Insufficient balance to transfer for ' . $sale['company_name'] . ' #' . $_leadspeek_api_id,$sale['company_root_id']);
                }
            }

            
        }

        return json_encode(array('result'=>'success','payment_intentID'=>'','srID'=>$srID,'aeID'=>$aeID,'arID'=>$arID,'salesfee'=>$salesfee,'error'=>''));
        
    }
    
    public function check_agency_stripeinfo($companyParentID,$platformfee,$_leadspeek_api_id = "",$defaultInvoice = "",$startdate = "0000-00-00 00:00:00",$enddate = "0000-00-00 00:00:00",$ongoingleads = "",$cleanProfit = "") {

        $chkUser = User::select('id','customer_payment_id','customer_card_id','email','company_root_id')
                    ->where('company_id','=',$companyParentID)
                    ->where('company_parent','<>',$companyParentID)
                    ->where('user_type','=','userdownline')
                    ->where('isAdmin','=','T')
                    ->where('active','=','T')
                    ->get();

        if(count($chkUser) > 0) {
            /** GET STRIPE KEY */
            $stripeseckey = config('services.stripe.secret');
            $stripepublish = $this->getcompanysetting($chkUser[0]['company_root_id'],'rootstripe');
            if ($stripepublish != '') {
                $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
            }
            /** GET STRIPE KEY */

            $stripe = new StripeClient([
                'api_key' => $stripeseckey,
                'stripe_version' => '2020-08-27'
            ]);

            $transferGroup = 'AI_' . $chkUser[0]['id'] . '_' . $_leadspeek_api_id . uniqid();
            $srID = "";
            $aeID = "";
            $arID = "";
            $salesfee = 0;

            /** CHECK IF THERE ARE SALES AND ACCOUNT EXECUTIVE */
            $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
            $saleslist = CompanySale::select(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.custom_commission), '" . $salt . "') USING utf8mb4) as `custom_commission`"),'users.custom_commission_enabled','company_sales.id','company_sales.sales_id','company_sales.sales_title','users.company_root_id',DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(`company_name`), '" . $salt . "') USING utf8mb4) as `company_name`"),DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.acc_connect_id), '" . $salt . "') USING utf8mb4) as `accconnectid`"),
                         DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.name), '" . $salt . "') USING utf8mb4) as `name`"),DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.email), '" . $salt . "') USING utf8mb4) as `email`"))
                                    ->join('users','company_sales.sales_id','=','users.id')
                                    ->join('companies','company_sales.company_id','=','companies.id')
                                    ->where('company_sales.company_id','=',$companyParentID)
                                    ->where('users.active','=','T')
                                    ->where('users.user_type','=','sales')
                                    ->where('users.status_acc','=','completed')
                                    ->get();

            /*if(count($saleslist) > 0) {
                $transferGroup = 'AI_' . $chkUser[0]['id'] . '_' . $_leadspeek_api_id . uniqid();
            }*/
            /** CHECK IF THERE ARE SALES AND ACCOUNT EXECUTIVE */
            $statusPayment = "";
            $errorstripe = "";

            try{
                $payment_intent =  $stripe->paymentIntents->create([
                    'payment_method_types' => ['card'],
                    'customer' => trim($chkUser[0]['customer_payment_id']),
                    'amount' => ($platformfee * 100),
                    'currency' => 'usd',
                    'receipt_email' => $chkUser[0]['email'],
                    'payment_method' => $chkUser[0]['customer_card_id'],
                    'confirm' => true,
                    'description' => $defaultInvoice,
                    'transfer_group' => $transferGroup,
                ]);
                $statusPayment = 'paid';
            }catch (RateLimitException $e) {
                $statusPayment = 'failed';
                // Too many requests made to the API too quickly
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                $this->send_notif_stripeerror('Agency Stripe error for ' . $defaultInvoice,'<p>Dear Admin,</p><p>Stripe Error when processing the payment for this Agency</p><p>error : ' . $errorstripe . '</p>',$chkUser[0]['company_root_id']);
                //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>$errorstripe));
            } catch (InvalidRequestException $e) {
                $statusPayment = 'failed';
                // Invalid parameters were supplied to Stripe's API
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                $this->send_notif_stripeerror('Agency Stripe error for ' . $defaultInvoice,'<p>Dear Admin,</p><p>Stripe Error when processing the payment for this Agency</p><p>error : ' . $errorstripe . '</p>',$chkUser[0]['company_root_id']);
                //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>$errorstripe));
            } catch (ExceptionAuthenticationException $e) {
                $statusPayment = 'failed';
                // Authentication with Stripe's API failed
                // (maybe you changed API keys recently)
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                $this->send_notif_stripeerror('Agency Stripe error for ' . $defaultInvoice,'<p>Dear Admin,</p><p>Stripe Error when processing the payment for this Agency</p><p>error : ' . $errorstripe . '</p>',$chkUser[0]['company_root_id']);
                //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>$errorstripe));
            } catch (ApiConnectionException $e) {
                $statusPayment = 'failed';
                // Network communication with Stripe failed
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                $this->send_notif_stripeerror('Agency Stripe error for ' . $defaultInvoice,'<p>Dear Admin,</p><p>Stripe Error when processing the payment for this Agency</p><p>error : ' . $errorstripe . '</p>',$chkUser[0]['company_root_id']);
                //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>$errorstripe));
            } catch (ApiErrorException $e) {
                $statusPayment = 'failed';
                // Display a very generic error to the user, and maybe send
                // yourself an email
                $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
                $this->send_notif_stripeerror('Agency Stripe error for ' . $defaultInvoice,'<p>Dear Admin,</p><p>Stripe Error when processing the payment for this Agency</p><p>error : ' . $errorstripe . '</p>',$chkUser[0]['company_root_id']);
                //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>$errorstripe));
            } catch (Exception $e) {
                $statusPayment = 'failed';
                // Something else happened, completely unrelated to Stripe
                $errorstripe = 'error not stripe things';
                $this->send_notif_stripeerror('Agency Stripe error for ' . $defaultInvoice,'<p>Dear Admin,</p><p>Stripe Error when processing the payment for this Agency</p><p>error : ' . $errorstripe . '</p>',$chkUser[0]['company_root_id']);
                //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>'','error'=>$errorstripe));
            }
            
            /** CHECK DEFAULT SALES COMMISSION */
            $AgencyPercentageCommission = 0.05;
            if (count($saleslist) > 0) {
                $rootAgencyPercentageCommission = $this->getcompanysetting($saleslist[0]['company_root_id'],'rootsetting');
                if ($rootAgencyPercentageCommission != '') {
                    if(isset($rootAgencyPercentageCommission->defaultagencypercentagecommission) && $rootAgencyPercentageCommission->defaultagencypercentagecommission != "") {
                        $AgencyPercentageCommission = $rootAgencyPercentageCommission->defaultagencypercentagecommission;
                    }
                }
            }
            /** CHECK DEFAULT SALES COMMISSION */

            $salesfee = ($platformfee * (float) $AgencyPercentageCommission);
            //$salesfee = ($cleanProfit != "")?($cleanProfit * (float) $AgencyPercentageCommission):$salesfee;
            $salesfee = number_format($salesfee,2,'.','');

            //if (count($saleslist) > 0 && $platformfee > 0 && $statusPayment == "" && $salesfee > 0) {
            if (count($saleslist) > 0 && $platformfee > 0  && $salesfee > 0) {

                /** GET OTHER DETAILS */
                $_campaign_name = "";
                $_client_name = "";
                $_leadspeek_type = "";

                $campaigndetails = LeadspeekUser::select('leadspeek_users.campaign_name','companies.company_name','leadspeek_users.leadspeek_type')
                                                ->join('users','leadspeek_users.user_id','=','users.id')
                                                ->join('companies','users.company_id','=','companies.id')
                                                ->where('leadspeek_users.leadspeek_api_id','=',$_leadspeek_api_id)
                                                ->get();
                if (count($campaigndetails) > 0) {
                    $_campaign_name = $campaigndetails[0]['campaign_name'];
                    $_client_name = $campaigndetails[0]['company_name'];
                    $_leadspeek_type = $campaigndetails[0]['leadspeek_type'];
                }
                /** GET OTHER DETAILS */
                
                foreach($saleslist as $sale) {

                    $overrideCommission = array();

                    $salesfee = ($platformfee * (float) $AgencyPercentageCommission);
                    //$salesfee = ($cleanProfit != "")?($cleanProfit * (float) $AgencyPercentageCommission):$salesfee;
                    $salesfee = number_format($salesfee,2,'.','');

                    /** OVERRIDE THE COMMISSION IF ENABLED */
                    if ($sale['custom_commission_enabled'] == 'T' && $ongoingleads != "") {
                        $chkCommissionOverride = json_decode($sale['custom_commission']);
                        if ($_leadspeek_type == 'local') {
                            $overrideCommission['srSiteID'] = ($chkCommissionOverride->sr->siteid > 0)?$platformfee - ($chkCommissionOverride->sr->siteid * $ongoingleads):0;
                            $overrideCommission['aeSiteID'] = ($chkCommissionOverride->ae->siteid > 0)?$platformfee - ($chkCommissionOverride->ae->siteid * $ongoingleads):0;
                            $overrideCommission['arSiteID'] = ($chkCommissionOverride->ar->siteid > 0)?$platformfee - ($chkCommissionOverride->ar->siteid * $ongoingleads):0;
                        }else if ($_leadspeek_type == 'locator') {
                            $overrideCommission['srSearchID'] = ($chkCommissionOverride->sr->searchid > 0)?$platformfee - ($chkCommissionOverride->sr->searchid * $ongoingleads):0;
                            $overrideCommission['aeSearchID'] = ($chkCommissionOverride->ae->searchid > 0)?$platformfee - ($chkCommissionOverride->ae->searchid * $ongoingleads):0;
                            $overrideCommission['arSearchID'] = ($chkCommissionOverride->ar->searchid > 0)?$platformfee - ($chkCommissionOverride->ar->searchid * $ongoingleads):0;
                        }
                    }
                    /** OVERRIDE THE COMMISSION IF ENABLED */


                    $tmp = explode(" ",$sale['name']);

                    $details = [
                        'firstname' => $tmp[0],
                        'salesfee'  => $salesfee,
                        'companyname' =>  $sale['company_name'],
                        'clientname' => $_client_name,
                        'campaignname' =>  $_campaign_name,
                        'campaignid' =>$_leadspeek_api_id,
                        'start' => date('Y-m-d',strtotime($startdate)),
                        'end' => date('Y-m-d',strtotime($enddate)),
                    ];
                    $attachement = array();
        
                    $from = [
                        'address' => 'noreply@exactmatchmarketing.com',
                        'name' => 'Commission Fee',
                        'replyto' => 'support@exactmatchmarketing.com',
                    ];
                    
                    if ($sale['sales_title'] == "Sales Representative") {
                        try {

                            /** CHECK IF OVERRIDE COMMISSION ENABLED */
                            if ($sale['custom_commission_enabled'] == 'T' && $ongoingleads != "") {
                                if ($_leadspeek_type == 'local') {
                                    $salesfee = (isset($overrideCommission['srSiteID']))?number_format($overrideCommission['srSiteID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }else if ($_leadspeek_type == 'locator') {
                                    $salesfee = (isset($overrideCommission['srSearchID']))?number_format( $overrideCommission['srSearchID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }
                            }
                             /** CHECK IF OVERRIDE COMMISSION ENABLED */

                            if ($salesfee > 0) {

                                $transferSales = $stripe->transfers->create([
                                    'amount' => ($salesfee * 100),
                                    'currency' => 'usd',
                                    'destination' => $sale['accconnectid'],
                                    'source_transaction' => $payment_intent->charges->data[0]->id,
                                    'transfer_group' => $transferGroup,
                                    'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                ]);

                                if (isset($transferSales->destination_payment)) {
                                    $despay = $transferSales->destination_payment;

                                    $transferSalesDesc =  $stripe->charges->update($despay,
                                            [
                                                'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                            ],['stripe_account' => $sale['accconnectid']]);
                                }

                                $srID = $sale['sales_id'];
                                $this->send_email($sale['email'],'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id . '(SR)',$details,$attachement,'emails.salesfee',$from,$companyParentID);
                            }

                        }catch (Exception $e) {
                            //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>''));
                            $this->send_notif_stripeerror('SE error transfer','SE error transfer for ' . $sale['company_name'] . ' #' . $_leadspeek_api_id,$sale['company_root_id']);
                        }
                        
                    }else if ($sale['sales_title'] == "Account Executive") {
                        try {

                            /** CHECK IF OVERRIDE COMMISSION ENABLED */
                            if ($sale['custom_commission_enabled'] == 'T' && $ongoingleads != "") {
                                if ($_leadspeek_type == 'local') {
                                    $salesfee = (isset($overrideCommission['aeSiteID']))?number_format( $overrideCommission['aeSiteID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }else if ($_leadspeek_type == 'locator') {
                                    $salesfee = (isset($overrideCommission['aeSearchID']))?number_format( $overrideCommission['aeSearchID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }
                            }
                             /** CHECK IF OVERRIDE COMMISSION ENABLED */

                            if ($salesfee > 0) {
                                $transferSales = $stripe->transfers->create([
                                    'amount' => ($salesfee * 100),
                                    'currency' => 'usd',
                                    'destination' => $sale['accconnectid'],
                                    'source_transaction' => $payment_intent->charges->data[0]->id,
                                    'transfer_group' => $transferGroup,
                                    'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                ]);

                                if (isset($transferSales->destination_payment)) {
                                    $despay = $transferSales->destination_payment;

                                    $transferSalesDesc =  $stripe->charges->update($despay,
                                            [
                                                'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                            ],['stripe_account' => $sale['accconnectid']]);
                                }

                                $aeID = $sale['sales_id'];
                                $this->send_email($sale['email'],'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id . '(AE)',$details,$attachement,'emails.salesfee',$from,$companyParentID);
                            }

                        }catch (Exception $e) {
                            //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>''));
                            $this->send_notif_stripeerror('AE error transfer','AE error transfer for ' . $sale['company_name'] . ' #' . $_leadspeek_api_id,$sale['company_root_id']);
                        }
            
                    }else if ($sale['sales_title'] == "Account Referral") {
                        try {

                            /** CHECK IF OVERRIDE COMMISSION ENABLED */
                            if ($sale['custom_commission_enabled'] == 'T' && $ongoingleads != "") {
                                if ($_leadspeek_type == 'local') {
                                    $salesfee = (isset($overrideCommission['arSiteID']))?number_format( $overrideCommission['arSiteID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }else if ($_leadspeek_type == 'locator') {
                                    $salesfee = (isset($overrideCommission['arSearchID']))?number_format( $overrideCommission['arSearchID'],2,'.',''):$salesfee;
                                    $details['salesfee'] = $salesfee;
                                }
                            }
                             /** CHECK IF OVERRIDE COMMISSION ENABLED */
                            
                            if ($salesfee > 0) {
                                $transferSales = $stripe->transfers->create([
                                    'amount' => ($salesfee * 100),
                                    'currency' => 'usd',
                                    'destination' => $sale['accconnectid'],
                                    'source_transaction' => $payment_intent->charges->data[0]->id,
                                    'transfer_group' => $transferGroup,
                                    'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                ]);

                                if (isset($transferSales->destination_payment)) {
                                    $despay = $transferSales->destination_payment;

                                    $transferSalesDesc =  $stripe->charges->update($despay,
                                            [
                                                'description' => 'Commision from ' . $sale['company_name'] . '-' . $_client_name  . '-' . $_campaign_name . ' #' . $_leadspeek_api_id,
                                            ],['stripe_account' => $sale['accconnectid']]);
                                }

                                $arID = $sale['sales_id'];
                                $this->send_email($sale['email'],'Commission fee from ' . $sale['company_name'] . ' #' . $_leadspeek_api_id . '(AR)',$details,$attachement,'emails.salesfee',$from,$companyParentID);
                            }

                        }catch (Exception $e) {
                            //return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','salesfee'=>''));
                            $this->send_notif_stripeerror('ACREF error transfer','ACREF error transfer for ' . $sale['company_name'] . ' #' . $_leadspeek_api_id,$sale['company_root_id']);
                        }
            
                    }

                }
            }

            $_paymentID = (isset($payment_intent->id))?$payment_intent->id:'';
            return json_encode(array('result'=>'success','payment_intentID'=>$_paymentID,'srID'=>$srID,'aeID'=>$aeID,'arID'=>$arID,'salesfee'=>$salesfee,'error'=>$errorstripe,'statusPayment'=>$statusPayment));
        }

        return json_encode(array('result'=>'failed','payment_intentID'=>'','srID'=>'','aeID'=>'','arID'=>'','salesfee'=>'','error'=>'','statusPayment'=>''));
    }

    public function searchInJSON($json, $searchKey) {
        foreach ($json as $key => $value) {
            if ($key === $searchKey) {
                return $value;
            }
            if (is_array($value) || is_object($value)) {
                $result = $this->searchInJSON($value, $searchKey);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        return null;
    }
    
    public function send_email($sentTo = array(),$title='',$details = array(),$attachment = array() ,$emailtemplate = '',$from = array(),$companyID = '') {
        $companysetting = "";
        $smtpusername = "";
        $AdminDefaultSMTP = "";
        $AdminDefaultSMTPEmail = "";
        
        if ($companyID != "") {
            $getcompanysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name','customsmtpmenu')->get();
            $AdminDefaultSMTP = $this->get_default_admin($companyID);
            $AdminDefaultSMTPEmail = (isset($AdminDefaultSMTP[0]['email']))?$AdminDefaultSMTP[0]['email']:'';
        }

        if (count($from) == 0) {
            $from = [
                'address' => 'noreply@sitesettingsapi.com',
                'name' => 'newleads',
                'replyto' => 'noreply@sitesettingsapi.com',
            ];
        }

        $chktemplate = false;
        // $templatecheck = array("emails.salesfee","emails.embeddedcodemissing","emails.tryseracrawlfailed","emails.tryseracrawlsuccess","emails.tryseraembeddedreminder","emails.tryseramatchlist","emails.tryseramatchlistcharge","emails.tryseramatchlistclient","emails.tryseramatchlistclientattach","emails.tryseramatchlistinvoice","emails.tryserastartstop");
        // if (in_array($emailtemplate,$templatecheck)) {
        //     $chktemplate = true;
        // }

        /** HACKED DANIEL SAID ALL EMAIL */
        $chktemplate = true;
        
        foreach($sentTo as $to) {
            if(trim($to) != '' && strpos($to, "@") !== false) {
                 $smtpusername = "";
                 /** CHECK IF USER EMAIL IS DISABLED TO RECEIVED EMAIL */
                
                 if ($emailtemplate != "" && $chktemplate) {
                    
                    $chkdisabledemail = User::select('id')
                                            ->whereEncrypted('email','=',trim($to))
                                            ->where('active','=','T')
                                            ->where('disabled_receive_email','=','T')
                                            ->get();
                    if(count($chkdisabledemail) > 0) {
                        continue;
                    }
                }
                /** CHECK IF USER EMAIL IS DISABLED TO RECEIVED EMAIL */
                $statusSender = "";

                /** CHECK RECEPIENT IS CLIENT OR NOT */
                $isRecepientClient = false;
                $chkRecipient = User::select('id','user_type')
                                    ->whereEncrypted('email','=',trim($to))
                                    ->where('active','=','T')
                                    ->where('user_type','=','client')
                                    ->first();
                if ($chkRecipient) {
                    if ($chkRecipient->user_type == "client") {
                        $isRecepientClient = true;
                    }
                }
                /** CHECK RECEPIENT IS CLIENT OR NOT */
                
                try {
                    /** SET SMTP EMAIL */
                    if ($companyID != '') {
                        if (count($getcompanysetting) > 0) {
                            $companysetting = json_decode($getcompanysetting[0]['setting_value']);
                            if (!isset($companysetting->default)) {
                                $companysetting->default = false;
                            }
                            if (!$companysetting->default) {
                                $statusSender = 'agencysmtp';
                                $security = 'ssl';
                                $tmpsearch = $this->searchInJSON($companysetting,'security');

                                if ($tmpsearch !== null) {
                                    $security = $companysetting->security;
                                    if ($companysetting->security == 'none') {
                                        $security = null;
                                    }
                                }

                                $transport = (new Swift_SmtpTransport(
                                    $companysetting->host, 
                                    $companysetting->port, 
                                    $security))
                                    ->setUsername($companysetting->username)
                                    ->setPassword($companysetting->password);
                    
                        
                                    $maildoll = new Swift_Mailer($transport);
                                    Mail::setSwiftMailer($maildoll);
                                    
                                    $smtpusername = (isset($companysetting->username))?$companysetting->username:'';
                                    if ($smtpusername == '') {
                                        $smtpusername = $AdminDefaultSMTPEmail;
                                    }

                            }else{
                                /** FIND ROOT DEFAULT EMAIL */
                                $_security = 'ssl';
                                $_host = config('services.defaultemail.host');
                                $_port = config('services.defaultemail.port');
                                $_usrname = config('services.defaultemail.username');
                                $_password = config('services.defaultemail.password');

                                $smtpusername = (isset($companysetting->username))?$companysetting->username:'';
                                if ($smtpusername == '') {
                                    $smtpusername = $AdminDefaultSMTPEmail;
                                }

                                if ($isRecepientClient == false) {
                                    $rootuser = User::select('company_root_id')
                                            ->where('company_id','=',$companyID)
                                            ->where('user_type','=','userdownline')
                                            ->where('active','=','T')
                                            ->get();
                                    if(count($rootuser) > 0) {
                                        $rootsmtp = CompanySetting::where('company_id',$rootuser[0]['company_root_id'])->whereEncrypted('setting_name','rootsmtp')->get();
                                        if (count($rootsmtp) > 0) {
                                            $smtproot = json_decode($rootsmtp[0]['setting_value']);
                                            $statusSender = 'rootsmtp';

                                            $security = $smtproot->security;
                                            if ($smtproot->security == 'none') {
                                                $security = null;
                                            }

                                            $_host = $smtproot->host;
                                            $_port = $smtproot->port;
                                            $_usrname = $smtproot->username;
                                            $_password = $smtproot->password;
                                            $_security = $security;

                                            $smtpusername = (isset($smtproot->username))?$smtproot->username:'';
                                            $AdminDefaultSMTPEmail = "";

                                        }
                                        /** FIND ROOT DEFAULT EMAIL */
                                    }
                                }

                                $transport = (new Swift_SmtpTransport(
                                        $_host, 
                                        $_port, 
                                        $_security))
                                        ->setUsername($_usrname)
                                        ->setPassword($_password);
                        
                            
                                        $maildoll = new Swift_Mailer($transport);
                                        Mail::setSwiftMailer($maildoll);
                                        
                            }
                        }else{

                            /** FIND ROOT DEFAULT EMAIL */
                            $_security = 'ssl';
                            $_host = config('services.defaultemail.host');
                            $_port = config('services.defaultemail.port');
                            $_usrname = config('services.defaultemail.username');
                            $_password = config('services.defaultemail.password');

                            $smtpusername = $_usrname;

                            if ($isRecepientClient == false) {
                                $rootuser = User::select('company_root_id')
                                            ->where('company_id','=',$companyID)
                                            ->where('user_type','=','userdownline')
                                            ->where('active','=','T')
                                            ->get();
                                if(count($rootuser) > 0) {
                                    $rootsmtp = CompanySetting::where('company_id',$rootuser[0]['company_root_id'])->whereEncrypted('setting_name','rootsmtp')->get();
                                    if (count($rootsmtp) > 0) {
                                        $smtproot = json_decode($rootsmtp[0]['setting_value']);
                                        $statusSender = 'rootsmtp';

                                        $security = $smtproot->security;
                                        if ($smtproot->security == 'none') {
                                            $security = null;
                                        }

                                        $_host = $smtproot->host;
                                        $_port = $smtproot->port;
                                        $_usrname = $smtproot->username;
                                        $_password = $smtproot->password;
                                        $_security = $security;

                                        $smtpusername = (isset($smtproot->username))?$smtproot->username:'';
                                        $AdminDefaultSMTPEmail = "";

                                    }
                                    /** FIND ROOT DEFAULT EMAIL */
                                }
                            }
                            
                            $transport = (new Swift_SmtpTransport(
                                $_host, 
                                $_port, 
                                $_security))
                                ->setUsername($_usrname)
                                ->setPassword($_password);
                
                                $maildoll = new Swift_Mailer($transport);
                                Mail::setSwiftMailer($maildoll);

                        }

                        if ($smtpusername != '') {
                            $from['address'] = $smtpusername;
                            $from['replyto'] = (isset($AdminDefaultSMTPEmail) && $AdminDefaultSMTPEmail != "")?$AdminDefaultSMTPEmail:$smtpusername;
                        }
                        
                    }
                    /** SET SMTP EMAIL */
                    
                    Mail::to($to)->send(new Gmail($title,$from,$details,$emailtemplate,$attachment));
                }catch(Swift_TransportException $e) {
                    try {
                        /** FIND ROOT DEFAULT EMAIL */
                        $_security = 'ssl';
                        $_host = config('services.defaultemail.host');
                        $_port = config('services.defaultemail.port');
                        $_usrname = config('services.defaultemail.username');
                        $_password = config('services.defaultemail.password');

                        $smtpusername = $_usrname;

                        if ($statusSender == 'agencysmtp' && $isRecepientClient == false) {
                            $rootuser = User::select('company_root_id')
                                    ->where('company_id','=',$companyID)
                                    ->where('user_type','=','userdownline')
                                    ->where('active','=','T')
                                    ->get();
                            if(count($rootuser) > 0) {
                                    $rootsmtp = CompanySetting::where('company_id',$rootuser[0]['company_root_id'])->whereEncrypted('setting_name','rootsmtp')->get();
                                    if (count($rootsmtp) > 0) {
                                        $smtproot = json_decode($rootsmtp[0]['setting_value']);

                                        $security = $smtproot->security;
                                        if ($smtproot->security == 'none') {
                                            $security = null;
                                        }

                                        $_host = $smtproot->host;
                                        $_port = $smtproot->port;
                                        $_usrname = $smtproot->username;
                                        $_password = $smtproot->password;
                                        $_security = $security;

                                        $smtpusername = (isset($smtproot->username))?$smtproot->username:'';
                                        $AdminDefaultSMTPEmail = "";
                                        
                                    }
                            }
                        }
                        /** FIND ROOT DEFAULT EMAIL */

                        $transport = (new Swift_SmtpTransport(
                                    $_host, 
                                    $_port, 
                                    $_security))
                                    ->setUsername($_usrname)
                                    ->setPassword($_password);
                    
                        
                        $maildoll = new Swift_Mailer($transport);
                        Mail::setSwiftMailer($maildoll);
                        
                        if ($smtpusername != '') {
                            $from['address'] = $smtpusername;
                            $from['replyto'] = (isset($AdminDefaultSMTPEmail) && $AdminDefaultSMTPEmail != "")?$AdminDefaultSMTPEmail:$smtpusername;
                        }
                        
                        Mail::to($to)->send(new Gmail($title,$from,$details,$emailtemplate,$attachment));
                        
                        $errmsg = $e->getMessage();
                        $this->send_email_smtp_problem_notification($companyID,$errmsg,trim($to));
                    }catch(Swift_TransportException $e) {
                        $transport = (new Swift_SmtpTransport(
                                    config('services.defaultemail.host'), 
                                    config('services.defaultemail.port'), 
                                    'ssl'))
                                    ->setUsername(config('services.defaultemail.username'))
                                    ->setPassword(config('services.defaultemail.password'));
                    
                        $smtpusername = config('services.defaultemail.username');
                        $AdminDefaultSMTPEmail = "";

                        $maildoll = new Swift_Mailer($transport);
                        Mail::setSwiftMailer($maildoll);
                        
                        if ($smtpusername != '') {
                            $from['address'] = $smtpusername;
                            $from['replyto'] = (isset($AdminDefaultSMTPEmail) && $AdminDefaultSMTPEmail != "")?$AdminDefaultSMTPEmail:$smtpusername;
                        }

                        Mail::to($to)->send(new Gmail($title,$from,$details,$emailtemplate,$attachment));

                        //$this->send_email_smtp_problem_notification($companyID);
                        $details = [
                            'title' => 'SMTP PROBLEM COMPANY ID:' . $companyID,
                            'content'  => 'HOST : ' . (isset($_host)?$_host:'') . ' | ' . 'PORT : ' . (isset($_port)?$_port:'') . ' | ' . 'SECURITY : ' . (isset($_security)?$_security:'') . ' | ' .  'USERNAME : ' . (isset($_usrname)?$_usrname:'') . ' | ' .  'PASS : ' . (isset($_password)?$_password:''),
                        ];
                    
                        $attachement = array();
                    
                        $from = [
                            'address' => 'noreply@exactmatchmarketing.com',
                            'name' => 'AGENCY SMTP PROBLEM',
                            'replyto' => 'support@exactmatchmarketing.com',
                        ];

                        $this->send_email(array('harrison@uncommonreach.com'),$title,$details,$attachement,'emails.customemail',$from,'');

                    }

                    
                }
            } 
        }
    }

    public function check_stripe_customer_platform_exist($user,$accConID) {
        $custStripeID = $user[0]['customer_payment_id'];
        $companyID = $user[0]['company_id'];
        $usrID = $user[0]['id'];
        
        /** GET STRIPE KEY */
        $stripeseckey = config('services.stripe.secret');
        $stripepublish = $this->getcompanysetting($user[0]['company_root_id'],'rootstripe');
        if ($stripepublish != '') {
            $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
        }
        /** GET STRIPE KEY */
        
        $stripe = new StripeClient([
            'api_key' => $stripeseckey,
            'stripe_version' => '2020-08-27'
        ]);
        
        try {
            $custInfo = $stripe->customers->retrieve($custStripeID,[],['stripe_account' => $accConID]);
            return json_encode(array('result'=>'success','params'=>'','custStripeID'=>$custInfo->id,'CardID'=>$custInfo->default_source));
        }catch(Exception $e) {
            try{
                $custInfo = $stripe->customers->retrieve($custStripeID,[]);
        
                $custStripeID = (isset($custInfo->id))?$custInfo->id:'';
        
                $token = $stripe->tokens->create(
                    ['customer' => $custStripeID],
                    ['stripe_account' => $accConID],
                );
        
                $company_name = "";
                
                $companyrslt = Company::select('company_name','simplifi_organizationid')
                                    ->where('id','=',$companyID)
                                    ->get();
        
                if(count($companyrslt) > 0) {
                    $company_name = $companyrslt[0]['company_name'];
                }
        
                $name = (isset($custInfo->name))?$custInfo->name:'';
                $phone = (isset($custInfo->phone))?$custInfo->phone:'';
                $email = (isset($custInfo->email))?$custInfo->email:'';
                
                $newCardID = $stripe->customers->create(
                    [   
                        'name' => $name,
                        'phone' => $phone,
                        'email' => $email,
                        'description' => $company_name,
                        'source' => $token
                    ],
                    ['stripe_account' => $accConID],
                );
        
                /** UPDATE USER STRIPE INFO */
                $usrupdte = User::find($usrID);
                $usrupdte->customer_payment_id = $newCardID->id;
                $usrupdte->customer_card_id = $newCardID->default_source;
                $usrupdte->save();
                /** UPDATE USER STRIPE INFO */
        
                return json_encode(array('result'=>'success','params'=>'','custStripeID'=>$newCardID->id,'CardID'=>$newCardID->default_source));
        
            }catch(Exception $e) {
                return json_encode(array('result'=>'failed','params'=>'','custStripeID'=>'','CardID'=>''));
            }
            
        }
    }

    public function getcompanysetting($companyID,$settingname) {

        /** GET SETTING MENU MODULE */
        $getcompanysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name',$settingname)->get();
        $companysetting = "";
        if (count($getcompanysetting) > 0) {
            $companysetting = json_decode($getcompanysetting[0]['setting_value']);
        }
        /** GET SETTING MENU MODULE */
        
        return $companysetting;

    }
    /** FOR STRIPE THINGS */

    /** FOR GENERAL */
    public function replaceExclamationWithAsterisk($input)
    {
        return preg_replace('/!(\w+)/', '*$1', $input);
    }

    public function get_default_admin($companyID) {
        $defaultAdmin = User::where('company_id','=',$companyID)
                            ->where('isAdmin','=','T')
                            //->where('defaultadmin','=','T')
                            ->where('customercare','=','T')
                            ->get();
            
        if (count($defaultAdmin) > 0) {
            return $defaultAdmin;
        }else{
            $defaultAdmin = User::where('company_id','=',$companyID)
                            ->where('isAdmin','=','T')
                            ->where('defaultadmin','=','T')
                            ->get();
            
            if (count($defaultAdmin) > 0) {
                return $defaultAdmin;
            }else{
                $defaultAdmin = User::where('company_id','=',$companyID)
                                ->where('isAdmin','=','T')
                                ->where('user_type','=','userdownline')
                                ->get();

                if (count($defaultAdmin) > 0) {
                    return $defaultAdmin;
                }else{
                    return '';
                }
            }

        }
    }

    public function send_email_smtp_problem_notification($companyID,$errmsg="",$recipient = "") {
        /** FIND AGENCY INFO */
        $agencyemail = '';
        $agencyfirstname = '';
        $_user_id = '';

        /** NO NEED TO HAVE WARN IF THIS IS LOG EMAIL */
        if (trim($recipient) == 'harrison@uncommonreach.com' || trim($recipient) == 'carrie@uncommonreach.com') {
            return "";
            exit;die();
        }
        /** NO NEED TO HAVE WARN IF THIS IS LOG EMAIL */

        $agencyinfo = User::select('id','name','email')->where('company_id','=',$companyID)->where('user_type','=','userdownline')->get();
        if (count($agencyinfo) > 0) {
            $agencyemail = $agencyinfo[0]['email'];
            $tmp = explode(' ',$agencyinfo[0]['name']);
            $agencyfirstname = $tmp[0];
            $_user_id = $agencyinfo[0]['id'];
            
            /** CHECK TO EMAIL NOTIFICATION */
            $chkemailnotif = EmailNotification::select('id','next_try',DB::raw("DATE_FORMAT(next_try, '%Y%m%d') as nexttry"))
                            ->where('user_id','=',$_user_id)
                            ->where('notification_name','smtp-problem')
                            ->get();

            $actionNotify = false;

            if (count($chkemailnotif) == 0) {
                $createEmailNotif = EmailNotification::create([
                    'user_id' => $_user_id,
                    'notification_name' => 'smtp-problem',
                    'notification_subject' => 'SMTP Email Configuration Information need attention',
                    'description' => 'email failed to send possibility because of password updated or turn on 2FA',
                    'next_try' => date('Y-m-d',strtotime(date('Y-m-d') . ' +5Days')),
                ]);

                $actionNotify = true;
            }else if (count($chkemailnotif) > 0) {
                    if ($chkemailnotif[0]['nexttry'] <= date('Ymd')) {
                        $updateEmailNotif = EmailNotification::find($chkemailnotif[0]['id']);
                        $updateEmailNotif->next_try = date('Y-m-d',strtotime(date('Y-m-d') . ' +5Days'));
                        $updateEmailNotif->save();

                        $actionNotify = true;

                    }
            }

            if ($actionNotify == true) {
                $company = Company::select('domain','subdomain')->where('id','=',$companyID)->get();
                $from = [
                    'address' => 'noreply@sitesettingsapi.com',
                    'name' => 'Support',
                    'replyto' => 'noreply@sitesettingsapi.com',
                ];

                $details = [
                    'firstname' => $agencyfirstname,
                    'urlsetting' => 'https://' . $company[0]['subdomain'] . '/configuration/general-setting',
                ];

                /** ONLY SENT EMAIL IF SMTP AUTHENTIFICATION FAILED */
                if (strpos($errmsg, "Failed to authenticate on SMTP server") !== false) {
                    Mail::to($agencyemail)->send(new Gmail('SMTP Email Configuration need attention',$from,$details,'emails.smtptrouble',array()));
                }
                /** ONLY SENT EMAIL IF SMTP AUTHENTIFICATION FAILED */
                
                /** GET DETAILS ABOUT USER */
                if (trim($recipient) != '') {
                    $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
                    $detailRecipient = User::select('users.name',DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(companies.company_name), '" . $salt . "') USING utf8mb4) as `company_name`"))
                                        ->join('companies','users.company_id','=','companies.id')
                                        //->whereEncrypted('email','=',trim($recipient))
                                        ->where(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.email), '" . $salt . "') USING utf8mb4)"),'=',trim($recipient))
                                        ->where('users.active','=','T')
                                        ->get();
                    $recipient_name = "";
                    $recipient_company = "";
                    if (count($detailRecipient) > 0) {
                        $recipient_name = $detailRecipient[0]['name'];
                        $recipient_company = $detailRecipient[0]['company_name'];
                    }
                    /** GET DETAILS ABOUT USER */
                    $_msg = "Name : " . $recipient_name . '<br/>';
                    $_msg .= "Email : " . $recipient . '<br/>';
                    $_msg .= "Company : " . $recipient_company . '<br/>';
                    $_msg .= "Error message : " . $errmsg;
                }else{
                    $_msg = "Error message : " . $errmsg;
                }
                
                $details = [
                    'title' => 'SMTP Email Configuration need attention',
                    'content'  => $_msg,
                ];

                Mail::to('harrison@uncommonreach.com')->send(new Gmail('SMTP Email Configuration need attention',$from,$details,'emails.customemail',array()));
                Mail::to('daniel@exactmatchmarketing.com')->send(new Gmail('SMTP Email Configuration need attention',$from,$details,'emails.customemail',array()));
            }
            /** CHECK TO EMAIL NOTIFICATION */

        }
        
        /** FIND AGENCY INFO */
    }

    public function set_smtp_email($companyID) {
        /** GET SETTING MENU MODULE */
        $getcompanysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name','customsmtpmenu')->get();
        $companysetting = "";
        if (count($getcompanysetting) > 0) {
            $companysetting = json_decode($getcompanysetting[0]['setting_value']);
            if (!$companysetting->default) {
               $config = [
                   'driver' => 'smtp',
                   'host' => $companysetting->host,
                   'port' => $companysetting->port,
                   'encryption' => 'ssl',
                   'username' => $companysetting->username,
                   'password' => $companysetting->password,
               ];
               
               Config::set('mail',$config);
               return $companysetting->username;
           }else{
                /** FIND ROOT DEFAULT EMAIL */
                $_security = 'ssl';
                $_host = config('services.defaultemail.host');
                $_port = config('services.defaultemail.port');
                $_usrname = config('services.defaultemail.username');
                $_password = config('services.defaultemail.password');

                $rootuser = User::select('company_root_id')
                            ->where('company_id','=',$companyID)
                            ->where('user_type','=','userdownline')
                            ->where('active','=','T')
                            ->get();
                    if(count($rootuser) > 0) {
                        $rootsmtp = CompanySetting::where('company_id',$rootuser[0]['company_root_id'])->whereEncrypted('setting_name','rootsmtp')->get();
                        if (count($rootsmtp) > 0) {
                            $smtproot = json_decode($rootsmtp[0]['setting_value']);

                            $_host = $smtproot->host;
                            $_port = $smtproot->port;
                            $_usrname = $smtproot->username;
                            $_password = $smtproot->password;
                            $_security = $smtproot->security;
                        }
                    }
                /** FIND ROOT DEFAULT EMAIL */
                
                $config = [
                    'driver' => 'smtp',
                    'host' => $_host,
                    'port' => $_port,
                    'encryption' => $_security,
                    'username' => $_usrname,
                    'password' => $_password,
                ];
                
                Config::set('mail',$config);
                return $companysetting->username;

           }
        }else{
            /** FIND ROOT DEFAULT EMAIL */
            $_security = 'ssl';
            $_host = config('services.defaultemail.host');
            $_port = config('services.defaultemail.port');
            $_usrname = config('services.defaultemail.username');
            $_password = config('services.defaultemail.password');

            $rootuser = User::select('company_root_id')
                            ->where('company_id','=',$companyID)
                            ->where('user_type','=','userdownline')
                            ->where('active','=','T')
                            ->get();
                    if(count($rootuser) > 0) {
                        $rootsmtp = CompanySetting::where('company_id',$rootuser[0]['company_root_id'])->whereEncrypted('setting_name','rootsmtp')->get();
                        if (count($rootsmtp) > 0) {
                            $smtproot = json_decode($rootsmtp[0]['setting_value']);

                            $_host = $smtproot->host;
                            $_port = $smtproot->port;
                            $_usrname = $smtproot->username;
                            $_password = $smtproot->password;
                            $_security = $smtproot->security;
                        }

                    }
            
            /** FIND ROOT DEFAULT EMAIL */

             $config = [
                'driver' => 'smtp',
                'host' => $_host,
                'port' => $_port,
                'encryption' => $_security,
                'username' => $_usrname,
                'password' => $_password,
            ];
            
            Config::set('mail',$config);
           return "";
        }
        /** GET SETTING MENU MODULE */
   }
    /** FOR GENERAL */

    /** FOR ENDATO, TOWER DATA AND OTHER API CALL */
    public function getDataEnrichment($firstname,$lastname,$email,$phone = '',$address = '',$city = '',$state = '',$zip = '') {
        $http = new Client();

        $appkey = config('services.endato.appkey');
        $apppass = config('services.endato.apppass');

        $apiURL =  config('services.endato.endpoint') . 'Contact/Enrich';

        $email = strtolower($email);
        $email = str_replace(' ','',$email);
        
        try {
            $options = [
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                    'galaxy-ap-name' => $appkey,
                    'galaxy-ap-password' => $apppass,
                    'galaxy-search-type' => 'DevAPIContactEnrich',
                ],
                'json' => [
                    "FirstName" => $firstname,
                    "LastName" => $lastname,
                    "Email" => $email,
                    "Phone" => $phone,
                    "Address" => [
                        "addressLine1" => $address,
                        "addressLine2" => $city . ", " . $state . " " . $zip,
                    ],
                    
                ]
            ]; 
           
            $response = $http->post($apiURL,$options);
            
            return json_decode($response->getBody());
        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            if ($e->getCode() === 400) {
                Log::warning("Endato Error 400 when find :" . $firstname . ' - ' . $lastname . ' - ' . $email . ' - ' .  $phone);
                return "";
            } else if ($e->getCode() === 401) {
                Log::warning("Endato Error 401 when find :" . $firstname . ' - ' . $lastname . ' - ' . $email . ' - ' .  $phone);
                return "";
            }else {
                Log::warning("Endato Error " . $e->getCode() . " when find :" . $firstname . ' - ' . $lastname . ' - ' . $email . ' - ' .  $phone);
                return "";
            }
        }catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            Log::warning("Endato Client Exception : " . $responseBodyAsString);
            return "";
        }catch (\GuzzleHttp\Exception\ServerException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            Log::warning("Endato Server Exception : " . $responseBodyAsString);
            return "";
        }


    }

    public function getTowerData($method="postal",$md5_email = "",$leadspeek_api_id = "",$leadspeektype = "") {
        $http = new Client();

        $appkey = config('services.tower.postal');
        if ($method == "md5") {
            $appkey = config('services.tower.md5');
        }

        try {
            // Log::info("getTowerData start");

            $apiURL =  config('services.tower.endpoint') . '?api_key=' . $appkey . '&md5_email=' . $md5_email;
            $options = [];
            $response = $http->get($apiURL,$options);
            $result = json_decode($response->getBody());

            // Log::info("getTowerData end", ['result' => $result]);

            if(count((array) $result) == 0) {
                $this->createFailedLeadRecord($md5_email, $leadspeek_api_id, 'getTowerData', $apiURL, 'empty', 'Lead Empty');
            }

            return $result;
        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            // Log::info("getTowerData error");
            
            FailedRecord::create([
                'email_encrypt' => $md5_email,
                'leadspeek_api_id' => $leadspeek_api_id,
                'description' => 'Failed to fetch data in getTowerData function',
            ]);

            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'getleadfailed');
            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'getleadfailed_gettowerdata');
            
            $this->createFailedLeadRecord($md5_email, $leadspeek_api_id, 'getTowerData', $apiURL, 'error', json_encode($e->getMessage()));

            return "";
        }
    }
    /** FOR ENDATO, TOWER DATA AND OTHER API CALL */

    public function generateReportUniqueNumber() {
        $randomCode = mt_rand(100000,999999);
        while(LeadspeekReport::where('id','=',$randomCode)->count() > 0) {
                $randomCode = mt_rand(100000,999999);
        }

        return $randomCode;
    }

    public function zb_validation($email,$ipaddress="") {
        try {
            $http = new Client();
            $appkey = config('services.zb.appkey');

            $apiURL = config('services.zb.endpoint') . "?api_key=" . $appkey . '&email=' . urlencode($email) . '&ip_address=' . $ipaddress;
            $options = [];
            $response = $http->get($apiURL,$options);
            return json_decode($response->getBody());
        }catch (RequestException $e) {
            return "";
        }catch (Exception $e) {
            return "";
        }
    }

    public function bigBDM_getToken() {
        $http = new \GuzzleHttp\Client;
        $bigbdm_clientID = config('services.bigbdm.clientid');
        $bigbdm_secretKey = config('services.bigbdm.clientsecret');
        $bigbdm_url_token = config('services.bigbdm.endpoint_token');

        try {
            $formoptions = [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'scope' => '',
                    'client_id' => $bigbdm_clientID,
                    'client_secret' => $bigbdm_secretKey,
                ],
            ]; 
            $tokenresponse = $http->post($bigbdm_url_token,$formoptions);
            $result =  json_decode($tokenresponse->getBody());
            return $result->access_token;
        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            return "";
        }

    }

    public function bigBDM_MD5($md5email,$leadspeek_api_id="",$leadspeektype = "") {
        $http = new \GuzzleHttp\Client;
        $bigbdm_url_md5 = config('services.bigbdm.endpoint_md5');

        $accessToken = $this->bigBDM_getToken();
        
        try {
            // Log::info("bigBDM_MD5 start");

            $md5options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => [
                    'RequestId' => uniqid(),
                    'ObjectList' => [$md5email],
                    'OutputId' => 2,
                ]
            ]; 
        
                $tokenresponse = $http->post($bigbdm_url_md5,$md5options);
                $result =  json_decode($tokenresponse->getBody());
                // Log::info("bigBDM_MD5 end", ['result' => $result]);

                if(count((array)$result->returnData) == 0) {
                    $this->createFailedLeadRecord($md5email, $leadspeek_api_id, 'bigBDM_MD5', $bigbdm_url_md5, 'empty', 'Lead Empty');
                }

                return $result->returnData;
            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                // Log::info("bigBDM_MD5 error");

                FailedRecord::create([
                    'email_encrypt' => $md5email,
                    'leadspeek_api_id' => $leadspeek_api_id,
                    'description' => 'Failed to fetch data in bigBDM_MD5 function',
                ]);
                
                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'getleadfailed');
                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'getleadfailed_bigbdmmd5');
                
                $this->createFailedLeadRecord($md5email, $leadspeek_api_id, 'bigBDM_MD5', $bigbdm_url_md5, 'error', json_encode($e->getMessage()));
                
                return array();
            }
    }

    public function bigBDM_PII($_fname,$_lname,$_address,$_zip,$md5param = "",$leadspeek_api_id = "", $leadspeektype = "") {
        $http = new \GuzzleHttp\Client;
        $bigbdm_url_pii = config('services.bigbdm.endpoint_pii');

        $accessToken = $this->bigBDM_getToken();

        try {
            // Log::info("bigBDM_PII start");

            $piioptions = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => [
                    'RequestId' => uniqid(),
                    'ObjectList' => [
                        [
                            'FirstName' => $_fname,
                            'LastName' => $_lname,
                            'Address' => $_address,
                            'Zip' => $_zip,
                            'Sequence' => uniqid()
                        ]
                    ],
                    'OutputId' => 2
                ]
            ]; 

            $tokenresponse = $http->post($bigbdm_url_pii,$piioptions);
            $result =  json_decode($tokenresponse->getBody());

            // Log::info("bigBDM_PII end", ['result' => $result]);

            if(count((array)$result->returnData) == 0) {
                $this->createFailedLeadRecord($md5param, $leadspeek_api_id, 'bigBDM_PII', $bigbdm_url_pii, 'empty', 'Lead Empty');
            }

            return $result->returnData;
        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            // Log::info("bigBDM_PII error");

            FailedRecord::create([
                'email_encrypt' => $md5param,
                'leadspeek_api_id' => $leadspeek_api_id,
                'description' => 'Failed to fetch data in bigBDM_PII function',
            ]);

            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'getleadfailed');
            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'getleadfailed_bigbdmpii');
            
            $this->createFailedLeadRecord($md5param, $leadspeek_api_id, 'bigBDM_PII', $bigbdm_url_pii, 'error', json_encode($e->getMessage()));
            
            return "";
        }
    }

    public function getCompanyRootInfo($companyID) {
        $company = Company::select('id','logo','simplifi_organizationid','domain','subdomain','template_bgcolor','box_bgcolor','font_theme','login_image','client_register_image','agency_register_image','company_name','phone','company_address','company_city','company_zip','company_state_name')
                            ->where('id','=',$companyID)
                            ->where('approved','=','T')
                            ->get();
        return $company[0];
    }

    public function getDefaultDomainEmail($companyID) {
        $defaultdomain = "sitesettingsapi.com";
        $customsmtp = CompanySetting::where('company_id',trim($companyID))->whereEncrypted('setting_name','customsmtpmenu')->get();
        if (count($customsmtp) > 0) {
            $csmtp = json_decode($customsmtp[0]['setting_value']);
            if (!$csmtp->default) {
                $tmpdomain = explode('@',$csmtp->username);
                $defaultdomain = $tmpdomain[1];
            }else{
                $rootsmtp = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name','rootsmtp')->get();
                if (count($rootsmtp) > 0) {
                    $smtproot = json_decode($rootsmtp[0]['setting_value']);
                    $tmpdomain = explode('@',$smtproot->username);
                    $defaultdomain = $tmpdomain[1];
                }
            }
        }else{
            $rootsmtp = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name','rootsmtp')->get();
            if (count($rootsmtp) > 0) {
                $smtproot = json_decode($rootsmtp[0]['setting_value']);
                $tmpdomain = explode('@',$smtproot->username);
                $defaultdomain = $tmpdomain[1];
            }
        }

        return $defaultdomain;
    }

    /** GOHIGHLEVEL FUNCTIONS */
    public function ghl_createContact($company_id = "",$api_key = "",$ID = "",$ClickDate = "",$FirstName = "",$LastName = "",$Email = "",$Email2 = "",$Phone = "",$Phone2 = "",$Address = "",$Address2 = "",$City = "",$State = "",$Zipcode = "",$Keyword = "",$tags = array(),$campaignID="",$errMsg="") {
        if($api_key != '') {
            $http = new \GuzzleHttp\Client;

            $comset_name = 'gohlcustomfields';
            /** GET IF CUSTOM FIELD ALREADY EXIST */
            $email2Id = "";
            $phone2Id = "";
            $address2Id = "";
            $keywordId = "";
            //$urlId = "";
            $contactId = "";
            $clickDateId = "";
            
            $customfields = CompanySetting::where('company_id','=',$company_id)->whereEncrypted('setting_name',$comset_name)->get();
            if (count($customfields) > 0) {
                $_customfields = json_decode($customfields[0]['setting_value']);
                $email2Id = (isset($_customfields->email2Id))?$_customfields->email2Id:'';
                $phone2Id = (isset($_customfields->phone2Id))?$_customfields->phone2Id:'';
                $address2Id = (isset($_customfields->address2Id))?$_customfields->address2Id:'';
                $keywordId = (isset($_customfields->keywordId))?$_customfields->keywordId:'';
                //$urlId = (isset($_customfields->urlId))?$_customfields->urlId:'';
                $contactId = (isset($_customfields->contactId))?$_customfields->contactId:'';
                $clickDateId = (isset($_customfields->clickDateId))?$_customfields->clickDateId:'';
            }
            /** GET IF CUSTOM FIELD ALREADY EXIST */

            $custom_fields = [
                $contactId => $ID,
                $clickDateId => $ClickDate,
                $email2Id => $Email2,
                $phone2Id => $Phone2,
                $address2Id => $Address2,
                $keywordId => $Keyword,
                //$urlId => $Url
            ];
            
            //$custom_fields = json_decode($custom_fields);
            //$tags = json_encode($tags);
            try {
                $apiEndpoint =  "https://rest.gohighlevel.com/v1/contacts/";
                $dataOptions = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        "firstName" => $FirstName,
                        "lastName" => $LastName,
                        "name" => $FirstName . ' ' . $LastName,
                        "email" => $Email,
                        "phone" => $Phone,
                        "address1" => $Address,
                        "country" => "US",
                        "city" => $City,
                        "state" => $State,
                        "postalCode" => $Zipcode,
                        "customField" => $custom_fields,
                        "source" => "Campaign ID : #" . $campaignID,
                        "tags" => $tags,
                    ]
                ];
                
                
                $createContact = $http->post($apiEndpoint,$dataOptions);
                $result =  json_decode($createContact->getBody());
            //    echo "<pre>";
            //    print_r($dataOptions);
            //    echo "</pre>";
            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                if ($errMsg != "") {
                    Log::warning("GHL Create Contact (L941) ErrMsg:" . $e->getMessage() . $errMsg);
                }else{
                    log::warning('GHL Failed Create Contact : ' . $e->getMessage() . ' CampaignID : #' . $campaignID);
                }
            }
        }
    }

    public function ghl_getTag($tagID,$api_key = '') {
        // $apiSetting = IntegrationSettings::where('company_id','=',$company_id)
        //                 ->where('integration_slug','=','gohighlevel')
        //                 ->first();
        
        if($api_key != '') {
            $http = new \GuzzleHttp\Client;
            try {

                $apiEndpoint =  "https://rest.gohighlevel.com/v1/tags/" . $tagID;
                $dataOptions = [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        
                    ]
                ];

                $getTags = $http->get($apiEndpoint,$dataOptions);
                $result =  json_decode($getTags->getBody());
                return $result->name;
            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                return "sys_removed";
            }

        }else{
            return "";
        }
    }
    /** GOHIGHLEVEL FUNCTIONS */


    // ZAPIER FUNCTIONS 
    public function zap_sendrecord($webhook = "",$ClickDate = "",$FirstName = "",$LastName = "",$Email = "",$Email2 = "",$Phone = "",$Phone2 = "",$Address = "",$Address2 = "",$City = "",$State = "",$Zipcode = "",$Keyword = "", $url = "",$tags = array(),$campaignID="", $campaign_type = "") {
        if($webhook != '') {
            $http = new \GuzzleHttp\Client;
            // $newTags = [];
            // if (!empty($tags)) {
            //     foreach ($tags as $index => $tag) {
            //         $newTags[$index + 1] = $tag; 
            //     }
            // }
            try {
                $dataOptions = [
                    'json' => [
                        "clickdate" => $ClickDate,
                        "firstName" => $FirstName,
                        "lastName" => $LastName,
                        "name" => $FirstName . ' ' . $LastName,
                        "email1" => $Email,
                        "email2" => $Email2,
                        "phone1" => $Phone,
                        "phone2" => $Phone2,
                        "address1" => $Address,
                        "address2" => $Address2,    
                        "city" => $City,
                        "state" => $State,
                        "postalCode" => $Zipcode,
                        'keyword' => $Keyword,
                        'url' => $url,
                        "campaignID" => $campaignID,
                        "tags" => $tags,
                        "campaignType" => $campaign_type,
                    ]
                ];
                $send_record = $http->post($webhook,$dataOptions);
                $result =  json_decode($send_record->getBody());
            //    echo "<pre>";
            //    print_r($dataOptions);
            //    echo "</pre>";
            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                //log::warning('GHL Failed Create Contact : ' . $e->getMessage());
                echo $e->getMessage();
            }
        }
    }

    // ZAPIER FUNCTIONS 

}
