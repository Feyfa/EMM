<?php

namespace App\Http\Controllers;

use App\Mail\Gmail;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanyStripe;
use App\Models\LeadspeekUser;
use App\Models\PackagePlan;
use App\Models\State;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Stripe\StripeClient;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\PermissionException;

class GeneralController extends Controller
{
    public function updatebudgetplan() {

        $a = LeadspeekUser::find('104');
        echo $a->campaign_name;
        exit;die();
        $http = new \GuzzleHttp\Client;

        $appkey = config('services.simplifi.app_key');
        $usrkey = config('services.simplifi.usr_key');
        $apiURL = config('services.simplifi.endpoint');

        $campaignID = 3243222;

        /** FIND BUDGET PLANS */
        $options = [
            'headers' => [
                'X-App-Key' => $appkey,        
                'X-User-Key' => $usrkey,
                'Content-Type' => 'application/json',
            ],
        ]; 

        $response = $http->get($apiURL . 'campaigns/' . $campaignID . '/budget_plans',$options);
        $result =  json_decode($response->getBody());

        $budgetPlanID = $result->budget_plans[0]->id;
        $startdate = "";
        $tmp = array();
        $tmp['end_date'] = "2023-01-16";
        $options = [
            'headers' => [
                'X-App-Key' => $appkey,        
                'X-User-Key' => $usrkey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                
                "end_date" => "2023-01-16",
            ]
        ]; 

        $response = $http->put($apiURL . 'budget_plans/' . $budgetPlanID ,$options);
        $result =  json_decode($response->getBody());

    }

    /** CRAWLING WEBSITE EMBEDDED CODE */
    private function dateDiffInDays($date1, $date2) 
    {
      // Calculating the difference in timestamps
      $diff = strtotime($date2) - strtotime($date1);
  
      // 1 day = 24 hours
      // 24 * 60 * 60 = 86400 seconds
      return abs(round($diff / 86400));
    }
    
    public function checkembededcode(Request $request) {
        $http = new \GuzzleHttp\Client;

        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
                "capture_peer_cert" => false,
            ),
        );

        $http = new \GuzzleHttp\Client;
        $appkey = config('services.trysera.api_id');

        $datenow = date('Y-m-d');
        $dayremind = array(3,6,9,12);

        $sitelist = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.url_code','leadspeek_users.user_id','leadspeek_users.leadspeek_api_id','leadspeek_users.campaign_name','leadspeek_users.spreadsheet_id',
                                          'leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','leadspeek_users.lp_max_lead_month','leadspeek_users.paymentterm',
                                          'leadspeek_users.active_user','leadspeek_users.lp_enddate','leadspeek_users.platformfee','leadspeek_users.lp_min_cost_month','leadspeek_users.lp_limit_leads','leadspeek_users.lp_limit_freq',
                                          'leadspeek_users.created_at', 'leadspeek_users.embedded_lastreminder',
                                          'companies.company_name','companies.id as company_id',
                                          'users.customer_payment_id','users.customer_card_id','users.email')
                                ->join('users','leadspeek_users.user_id','=','users.id')
                                ->join('companies','users.company_id','=','companies.id')
                                ->where('leadspeek_users.embeddedcode_crawl','=','F')
                                ->where('leadspeek_users.active','=','F')
                                ->where('leadspeek_users.disabled','=','T')
                                ->where('leadspeek_users.active_user','=','F')
                                ->where('leadspeek_users.leadspeek_type','=','local')
                                ->where('users.active','=','T')
                                ->get();

        foreach($sitelist as $st) {
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
            $clientEmail = explode(PHP_EOL, $st['report_sent_to']);
            $clientAdminNotify = explode(',',$st['admin_notify_to']);
            $spreadSheetID = $st['spreadsheet_id'];
            $daydiff = 0;

            $adminEmail = array();
            $adminNotify = array();
            $clientAdmin = array();
            foreach($clientEmail as $value) {
                array_push($adminEmail,$value);
                array_push($clientAdmin,$value);
            }

            $tmp = User::select('email')->whereIn('id', $clientAdminNotify)->get();
            foreach($tmp as $ad) {
                array_push($adminEmail,$ad['email']);
                array_push($adminNotify,$ad['email']);
            }

            $dashboardlogin = 'https://app.exactmatchmarketing.com/login';
            if (config('services.appconf.devmode') === true) {
                $dashboardlogin = 'https://appbeta.exactmatchmarketing.com/login';
            }

            $campaignName = '';
            if (isset($st['campaign_name']) && trim($st['campaign_name']) != '') {
                $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$st['campaign_name']);
            }
            
            $company_name = str_replace($_leadspeek_api_id,'',$st['company_name']) . $campaignName;
            $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');

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
                    $details = [
                        'website' => $_urlcrawl,
                        'dashboardlogin' => $dashboardlogin,
                    ];
                    $attachement = array();
                    
                    $from = [
                        'address' => 'noreply@exactmatchmarketing.com',
                        'name' => 'System',
                        'replyto' => 'support@exactmatchmarketing.com',
                    ];
            
                    $this->send_email($adminNotify,$from,'Blocked from crawling notice for ' . $company_name . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseracrawlfailed',$st['company_id']);
                
                    /** SENT EMAIL NOTIFICATION FAILED CRAWL */
                }
                if (str_contains($contents,'https://tag.leadspeek.com/i/14798651632618831906/s/') && $contents != "") {
                    
                    /** RUN THE CAMPAIGN */

                            /** ENABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
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
                            //$pauseresponse = $http->put($pauseApiURL,$pauseoptions);
                            //$result =  json_decode($pauseresponse->getBody());

                            $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                            $updateLeadspeekUser->active = 'T';
                            $updateLeadspeekUser->disabled = 'F';
                            $updateLeadspeekUser->active_user = 'T';
                            $updateLeadspeekUser->embeddedcode_crawl = 'T';
                            $updateLeadspeekUser->embedded_status = 'Campaign is running.';
                            $updateLeadspeekUser->last_lead_start = date('Y-m-d H:i:s');
                            //$updateLeadspeekUser->save();
                            /** ENABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */

                            /** IF THIS FIRST TIME ACTIVE THEN WILL CHARGE CLIENT FOR ONE TIME CREATIVE AND FIRST PLATFORM FEE */
                            if($activeUser != '' && $activeUser == 'F') {
                                /** CHARGE ONE TIME CREATIVE / SET UP FEE WHEN STATUS ACTIVATED (ONE TIME) */
                                    date_default_timezone_set('America/Chicago');
                                    $updateLeadspeekUser->lp_limit_startdate = date('Y-m-d');
                                
                                if(isset($st['customer_payment_id']) && $st['customer_payment_id'] != '' && $st['customer_card_id'] != '' && ($st['platformfee'] > 0 || $st['lp_min_cost_month'] > 0)) {
                                    /** PUT FORMULA FOR PLATFORM FEE CALCULATION */
                                    $clientPaymentTerm = $st['paymentterm'];
                                    //$clientLimitStartDate = ($updateclient[0]->lp_limit_startdate == null || $updateclient[0]->lp_limit_startdate == '0000-00-00 00:00:00')?'':$updateclient[0]->lp_limit_startdate;
                                    $clientLimitEndDate = ($st['lp_enddate'] == null || $st['lp_enddate'] == '0000-00-00 00:00:00')?'':$st['lp_enddate'];

                                    if ($clientPaymentTerm == 'Weekly' && $st['lp_min_cost_month'] > 0 && $st['lp_min_cost_month'] != '') {
                                        $clientWeeksContract = 52; //assume will be one year if end date is null or empty
                                        $clientMonthRange = 12;

                                        if ($clientLimitEndDate != '') {
                                            $d1 = new DateTime(date('Y-m-d'));
                                            $d2 = new DateTime($clientLimitEndDate);
                                            $interval = $d1->diff($d2);
                                            $clientMonthRange = $interval->m;

                                            $d1 = strtotime(date('Y-m-d'));
                                            $d2 = strtotime($clientLimitEndDate);
                                            $clientWeeksContract = $this->countDays(2, $d1, $d2);

                                            $st['lp_min_cost_month'] = ($st['lp_min_cost_month'] * $clientMonthRange) / $clientWeeksContract;

                                        }else{
                                            $st['lp_min_cost_month'] = ($st['lp_min_cost_month'] * $clientMonthRange) / $clientWeeksContract;
                                        }
                                    }

                                    /** PUT FORMULA FOR PLATFORM FEE CALCULATION */
                                    $totalFirstCharge = $st['platformfee'] + $st['lp_min_cost_month'];
                                    //$this->chargeClient($st['customer_payment_id'],$st['customer_card_id'],$st['email'],$totalFirstCharge,$st['platformfee'],$st['lp_min_cost_month'],$st);
                                }
                            
                            /** CHARGE ONE TIME CREATIVE / SET UP FEE WHEN STATUS ACTIVATED (ONE TIME) */
                            
                        }

                        $updateLeadspeekUser->save();
                        /** IF THIS FIRST TIME ACTIVE THEN WILL CHARGE CLIENT FOR ONE TIME CREATIVE AND FIRST PLATFORM FEE */
                    
                    /** RUN THE CAMPAIGN */

                    /** SENT NOTIFICATION TO CLIENT AND ADMIN */    
                    
                    $details = [
                        'companyname'  => $st['company_name'],
                        'campaignanme' => str_replace($_leadspeek_api_id,'',$st['campaign_name']),
                        'website' => $_urlcrawl,
                        'dashboardlogin' => $dashboardlogin,
                        'googlesheetlink' => 'https://docs.google.com/spreadsheets/d/' . $spreadSheetID . '/edit?usp=sharing',
                    ];
                    $attachement = array();
                    
                    $from = [
                        'address' => 'noreply@exactmatchmarketing.com',
                        'name' => 'System',
                        'replyto' => 'support@exactmatchmarketing.com',
                    ];
            
                    $this->send_email($adminEmail,$from,'Leadspeek Notification for ' . $company_name . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryserastartstop',$st['company_id']);
                

                    /** SENT NOTIFICATION TO CLIENT AND ADMIN */

                }else{
                    $embeddedupdate = LeadspeekUser::find($st['id']);
                    $embeddedupdate->embedded_status = $_urlcrawl .'<br/>(Waiting for the embedded code to be placed.)';
                    $embeddedupdate->save();

                    /** RUN EMAIL REMINDER TO CHECK*/
                    $EmailReminderEmbedded = false;

                    if ($st['embedded_lastreminder'] == null || $st['embedded_lastreminder'] == '0000-00-00') {
                        $datelast = date('Y-m-d',strtotime($st['created_at']));
                        $daydiff = $this->dateDiffInDays($datenow,$datelast);
                        if ($daydiff > 0) {
                            /** UPDATE THE FIRST REMINDER */
                            $embeddedreminder = LeadspeekUser::find($st['id']);
                            $embeddedreminder->embedded_lastreminder = $datenow;
                            $embeddedreminder->save();
                            /** UPDATE THE FIRST REMINDER */
        
                            /** SENT EMAIL REMINDER EMBEDDED CODE */
                            $EmailReminderEmbedded = true;
                            /** SENT EMAIL REMINDER EMBEDDED CODE */
                        }
                    }else{
                        $datelast = date('Y-m-d',strtotime($st['embedded_lastreminder']));
                        $daydiff = $this->dateDiffInDays($datenow,$datelast);
        
                        /** SENT EMAIL AS LIKE DAYS PATTERN */
                        if (in_array($daydiff,$dayremind)) {
                            /** SENT EMAIL REMINDER EMBEDDED CODE */
                            $EmailReminderEmbedded = true;
                            /** SENT EMAIL REMINDER EMBEDDED CODE */
                        }
        
                    }

                    if ($EmailReminderEmbedded) {
                        $details = [
                            'website' => $_urlcrawl,
                            'dashboardlogin' => $dashboardlogin,
                        ];
                        $attachement = array();
                        
                        $from = [
                            'address' => 'noreply@exactmatchmarketing.com',
                            'name' => 'System',
                            'replyto' => 'support@exactmatchmarketing.com',
                        ];
                
                        $this->send_email($adminEmail,$from,'Waiting for the embedded code to be placed - ' . $company_name . ' #' . $_leadspeek_api_id ,$details,$attachement,'emails.tryseraembeddedreminder',$st['company_id']);
                
                    }
                    /** RUN EMAIL REMINDER TO CHECK */
                }
            }else{
                $embeddedupdate = LeadspeekUser::find($st['id']);
                $embeddedupdate->embedded_status = $_urlcrawl . '<br/>(Make sure the domain begins with https:// or domain can not be found.)';
                $embeddedupdate->save();
            }
            /** CHECK IF SSL AND EMBEDDED CODE EXIST */
        }
    }

    private function is_ssl_exists($url)
    {
        try {
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
        }
    }

    public function getMainDomain($hostname) {
        $parsed = parse_url($hostname);
        
        if (isset($parsed['host'])) {
            $hostParts = explode('.', $parsed['host']);
            
            // Check if the host has enough parts to consider it a valid domain
            if (count($hostParts) >= 2) {
                $mainDomain = $hostParts[count($hostParts) - 2] . '.' . $hostParts[count($hostParts) - 1];
                return $mainDomain;
            }
        }
        
        return null;
    }
    
    public function setInitialCompanyVal($company,$params) {
            $params["pathlogo"] = ($company[0]["logo"] != '')?$company[0]["logo"]:'https://emmspaces.nyc3.cdn.digitaloceanspaces.com/systems/yourlogohere.png';
            $params['sifiorganizationid'] = $company[0]["simplifi_organizationid"];
            $params['ownedcompanyid'] = $company[0]["id"];
            $params['domain'] = $company[0]["domain"];
            $params['subdomain'] = $company[0]["subdomain"];
            $params['companyname'] = $company[0]["company_name"];
            $params['companyphone'] = $company[0]["phone"];

            $params['company_address'] = $company[0]["company_address"];
            $params['company_city'] = $company[0]["company_city"];
            $params['company_zip'] = $company[0]["company_zip"];
            $params['company_country_code'] = $company[0]["company_country_code"];
            $params['company_state_name'] = $company[0]["company_state_name"];


            $AdminDefault = $this->get_default_admin($company[0]["id"]);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';

            $params['companyemail'] = $AdminDefaultEmail;
            $params['customercarename'] = (isset($AdminDefault[0]['name']))?$AdminDefault[0]['name']:'';

            $params['template_bgcolor'] = ($company[0]["template_bgcolor"] != '' && $company[0]["template_bgcolor"] != 'null')?$company[0]["template_bgcolor"]:'';
            $params['box_bgcolor'] = ($company[0]["box_bgcolor"] != '' && $company[0]["box_bgcolor"] != 'null')?$company[0]["box_bgcolor"]:'';
            $params['font_theme'] = ($company[0]["font_theme"] != '' && $company[0]["font_theme"] != 'null')?$company[0]["font_theme"]:'';
            $params['login_image'] = ($company[0]["login_image"] != '' && $company[0]["login_image"] != 'null')?$company[0]["login_image"]:$params['login_image'];
            $params['client_register_image'] = ($company[0]["client_register_image"] != '' && $company[0]["client_register_image"] != 'null')?$company[0]["client_register_image"]:$params['client_register_image'];
            $params['agency_register_image'] = ($company[0]["agency_register_image"] != '' && $company[0]["agency_register_image"] != 'null')?$company[0]["agency_register_image"]:$params['agency_register_image'];
            $params['logo_login_register'] = ($company[0]["logo_login_register"] != '' && $company[0]["logo_login_register"] != 'null')?$company[0]["logo_login_register"]:$params['pathlogo'];

            $params['paymentgateway'] = ($company[0]["paymentgateway"] != '' && $company[0]["paymentgateway"] != 'null')?$company[0]["paymentgateway"]:'stripe';

            return $params;
    }

    public function getCompanyInfoDomain(Request $request) {
        $domain_subdomain =  (isset($request->domainorsub))?$request->domainorsub:'';
        $hostID =  (isset($request->hostID))?$request->hostID:'';
        /** DEFAULT SETUP FOR EMM AS PARENT */
        $params = array();
        $params['idsys'] = "";
        $params['sppubkey'] = "";
        $params['recapkey'] = "";
        $params['rootcomp'] = "F";
        $params['companyrootname'] = "";
        $params["pathlogo"] = "/img/EMMLogo.png";
        $params['sifiorganizationid'] = config('services.sifidefaultorganization.organizationid');
        $params['ownedcompanyid'] = "";
        $params['domain'] =  config('services.application.domain');
        $params['subdomain'] = config('services.application.subdomain');
        
        $params['template_bgcolor'] = '#1E1E2F';
        $params['box_bgcolor'] = '#ffffff';
        $params['font_theme'] = '';
        $params['login_image'] = 'https://emmspaces.nyc3.cdn.digitaloceanspaces.com/systems/EMMLogin.png';
        $params['client_register_image'] = 'https://emmspaces.nyc3.cdn.digitaloceanspaces.com/systems/EMMLogin.png';
        $params['agency_register_image'] = 'https://emmspaces.nyc3.cdn.digitaloceanspaces.com/systems/agencyregister.png';
        $params['logo_login_register'] = '/img/EMMLogo.png';
        /** DEFAULT SETUP FOR EMM AS PARENT */
        $params['companyname'] = 'Exact Match Marketing';
        $params['companyemail'] = '';
        $params['companyphone'] = '';

        $params['companyrootlegalname'] = '';
        $params['companyrootnameshort'] = '';
        $params['companyrootaddres'] = '';
        $params['companyrootcity'] = '';
        $params['companyrootzip'] = '';
        $params['companyrootstatecode'] = '';
        $params['companyrootstatename'] = '';
        $params['companyrootcountrycode'] = '';

        $params['userrootname'] = '';
        $params['userrootaddres'] = '';
        $params['userrootcity'] = '';
        $params['userrootzip'] = '';
        $params['userrootstatecode'] = '';
        $params['userrootstatename'] = '';
        $params['userrootcountrycode'] = '';
        $params['disabledclientregister'] = 'F';
        $params['clientredirecturl'] = '';

        $params['paymentgateway'] = 'stripe';

        $params['agencyplatformroot'] = 'F';

        $company = array();
        
        if (trim($hostID) == '') {
        $company = Company::select('id','logo','simplifi_organizationid','domain','subdomain','template_bgcolor','box_bgcolor','font_theme','login_image','client_register_image','agency_register_image','logo_login_register','company_name','phone','company_address','company_city','company_zip','company_country_code','company_state_name','company_state_code','paymentgateway','disabled_clientregister','clientredirecturl')
                            ->where(function ($query) use ($domain_subdomain) {
                                $query->where('domain','=',$domain_subdomain)
                                        ->orWhere('subdomain','=',$domain_subdomain);
                            })->where('approved','=','T')
                            ->get();
        }else {
            $company = Company::select('id','logo','simplifi_organizationid','domain','subdomain','template_bgcolor','box_bgcolor','font_theme','login_image','client_register_image','agency_register_image','logo_login_register','company_name','phone','company_address','company_city','company_zip','company_country_code','company_state_name','company_state_code','paymentgateway','disabled_clientregister','clientredirecturl')
                            ->where('id','=',$hostID)
                            ->where('approved','=','T')
                            ->get();
        }

        if(count($company) > 0) {
            /** FIND ID SYS */
            if (trim($hostID) == '') {
            $usr = User::select('company_parent')
                        ->where('company_id','=',$company[0]["id"])
                        ->where('user_type','=','userdownline')
                        ->where('active','=','T')
                        ->get();
            }else{
                $usr = User::select('company_parent','name','email')
                        ->where('company_id','=',$company[0]["id"])
                        ->where('user_type','=','client')
                        ->where('active','=','T')
                        ->get();
            }

            if (count($usr) > 0) {
                if ($usr[0]["company_parent"] != "" || $usr[0]["company_parent"] != null) {
                    $params['idsys'] = $usr[0]["company_parent"];
                }else{
                    $params['idsys'] = $company[0]["id"];
                }
            }
            /** FIND ID SYS */

            /** GET STRIPE KEY */
            $stripepublish = $this->getcompanysetting($params['idsys'],'rootstripe');
            if ($stripepublish != '') {
                $params['sppubkey'] = (isset($stripepublish->publishablekey))?$stripepublish->publishablekey:"";
            }
            /** GET STRIPE KEY */

            /** GET RECAPTCHA SECRET KEY */
            $recapkey = $this->getcompanysetting($params['idsys'],'rootrecaptcha');
            if ($recapkey != '') {
                $params['recapkey'] = (isset($recapkey->sitekey))?$recapkey->sitekey:"";
            }
            /** GET RECAPTCHA SECRET KEY */

            $params = $this->setInitialCompanyVal($company,$params);
            
            if (trim($hostID) != '') {
                $params['customercarename'] = $usr[0]["name"];
                $params['companyemail'] = $usr[0]["email"];
            }

            $params['disabledclientregister'] = $company[0]["disabled_clientregister"];
            $params['clientredirecturl'] = $company[0]["clientredirecturl"];
        }else{

            /** FIND ROOT SYS */
            $rootdomain = $this->getMainDomain('https://' . $domain_subdomain);
            $rootSubdomain = "";

            $company = Company::select('id','logo','simplifi_organizationid','domain','subdomain','template_bgcolor','box_bgcolor','font_theme','login_image','client_register_image','agency_register_image','logo_login_register','company_name','phone','company_address','company_city','company_zip','company_state_name','company_country_code','company_state_code','disabled_clientregister','clientredirecturl')
                            ->where('domain','=',$rootdomain)
                            ->where('approved','=','T')
                            ->get();
           

            if (count($company) > 0) {
                $params['idsys'] = $company[0]["id"];
                $params['disabledclientregister'] = $company[0]["disabled_clientregister"];
                $params['clientredirecturl'] = $company[0]["clientredirecturl"];
                $params = $this->setInitialCompanyVal($company,$params);
                $rootSubdomain = $company[0]["subdomain"];
            }
            /** FIND ROOT SYS */

            /** GET STRIPE KEY */
            $stripepublish = $this->getcompanysetting($params['idsys'],'rootstripe');
            if ($stripepublish != '') {
                $params['sppubkey'] = (isset($stripepublish->publishablekey))?$stripepublish->publishablekey:"";
            }
            /** GET STRIPE KEY */

             /** GET RECAPTCHA SECRET KEY */
             $recapkey = $this->getcompanysetting($params['idsys'],'rootrecaptcha');
             if ($recapkey != '') {
                 $params['recapkey'] = (isset($recapkey->sitekey))?$recapkey->sitekey:"";
             }
             /** GET RECAPTCHA SECRET KEY */

            if ($rootSubdomain != $domain_subdomain) {
                $params['ownedcompanyid'] = "";
            }
        }

        try {
        $paymenttermcontrol = CompanySetting::where('company_id', trim($params['ownedcompanyid']))
            ->whereEncrypted('setting_name', 'agencypaymentterm')
            ->get();

        } catch (\Throwable $th) {
                // return response()->json(['result' => 'failed', 'msG' => $th->getMessage(), 'ID' => $companyStripe->acc_connect_id ]);
        }
        if (count($paymenttermcontrol) > 0) {
            /** GET PAYMENT TERM ROOT FILTERED BY PAYMENTTERMCONTROL */
            try {
                $root_paymenttermlist = "";
                $paymenttermlist = CompanySetting::where('company_id', trim($params['idsys']))
                    ->whereEncrypted('setting_name', 'rootpaymentterm')
                    ->get();
        
                if (count($paymenttermlist) > 0) {
                    $root_paymenttermlist = json_decode($paymenttermlist[0]['setting_value']);
                }

                $_paymenttermcontrol = "";
                if (count($paymenttermcontrol) > 0) {
                    $_paymenttermcontrol = json_decode($paymenttermcontrol[0]['setting_value']);
                }
        
        
                // Filter rootpaymentterm based on paymenttermcontrol
                $filteredPaymentTerms = [];
                if ($root_paymenttermlist && $_paymenttermcontrol) {
                    // Create a map of terms and their statuses
                    $termStatus = [];
                    foreach ($_paymenttermcontrol->SelectedPaymentTerm as $control) {
                        $termStatus[$control->term] = $control->status;
                    }
        
                    // Filter rootpaymentterm
                    foreach ($root_paymenttermlist->PaymentTerm as $term) {
                        if (isset($termStatus[$term->value]) && $termStatus[$term->value]) {
                            $filteredPaymentTerms[] = $term;
                        }
                    }
                }
            } catch (\Throwable $th) {
                // return response()->json(['filteredPaymentTerms'=> $filteredPaymentTerms, '_paymenttermcontrol' => $_paymenttermcontrol, 'errmsg' => $th->getMessage()]);
            }
            /** GET PAYMENT TERM ROOT FILTERED BY PAYMENTTERMCONTROL */
                $paymentTerms = $filteredPaymentTerms;
            }else {

                        // /** GET PAYMENT TERM ROOT */
                    $_paymenttermlist = "";
                    $paymenttermlist = CompanySetting::where('company_id',trim($params['idsys']))->whereEncrypted('setting_name','rootpaymentterm')->get();
                    if (count($paymenttermlist) > 0) {
                        $_paymenttermlist = json_decode($paymenttermlist[0]['setting_value']);
                    }
                    /** GET PAYMENT TERM ROOT */

                $paymentTerms = $_paymenttermlist->PaymentTerm ?? [];
        }

        /** GET REDIRECT URL REGISTER */
        $_redirecturl = "";
        $redirecturl = CompanySetting::where('company_id',trim($params['idsys']))->whereEncrypted('setting_name','rootregisterurl')->get();
        if (count($redirecturl) > 0) {
            $_redirecturl = json_decode($redirecturl[0]['setting_value']);
        }
        /** GET REDIRECT URL REGISTER */

        $customsidebarleadmenu = "";
        $rootsidebarleadmenu = "";
        if (trim($params['ownedcompanyid']) != '') {
             /** CHECK IF STILL WHITELABELLING PACKAGE OR NOT */
            //  $customsidebarleadmenu = [
            //     'local' => [
            //         'name' => 'Site ID',
            //         'url' => 'siteid',
            //     ],
            //     'locator' => [
            //         'name' => 'Search ID',
            //         'url' => 'searchid',
            //     ]
            // ];

            $rootcompanysetting = CompanySetting::where('company_id',trim($params['idsys']))->whereEncrypted('setting_name','rootcustomsidebarleadmenu')->get();
            if (count($rootcompanysetting) > 0) {
                $rootsidebarleadmenu = json_decode($rootcompanysetting[0]['setting_value']);
                $customsidebarleadmenu = $rootsidebarleadmenu;
            }

             if($this->checkwhitelabellingpackage(trim($params['ownedcompanyid']))) {
                /** GET SETTING MENU MODULE */
                $companysetting = CompanySetting::where('company_id',trim($params['ownedcompanyid']))->whereEncrypted('setting_name','customsidebarleadmenu')->get();
                if (count($companysetting) > 0) {
                    $customsidebarleadmenu = json_decode($companysetting[0]['setting_value']);
                }
                /** GET SETTING MENU MODULE */
             }
             /** CHECK IF STILL WHITELABELLING PACKAGE OR NOT */
        }

        /** CHECK ROOT COMPANY NAME */
        if ($params['idsys'] != '') {
            $companyroot = Company::select('company_name','company_legalname','company_address','company_city','company_zip','company_country_code','company_state_code','company_state_name','domain','subdomain','phone')
                                    ->where('id','=',$params['idsys'])
                                    ->get();
            if (count($companyroot) > 0) {
                $text = ucfirst($companyroot[0]['company_name']);
                $words = explode(" ", $text);
                $firstTwoWords = implode(" ", array_slice($words, 0, 2));
                
                $params['companyrootname'] = ucfirst($companyroot[0]['company_name']);
                $params['companyrootlegalname'] = ucfirst($companyroot[0]['company_legalname']);
                $params['companyrootnameshort'] = $firstTwoWords;
                $params['companyrootaddres'] = $companyroot[0]['company_address'];
                $params['companyrootcity'] = $companyroot[0]['company_city'];
                $params['companyrootzip'] = $companyroot[0]['company_zip'];
                $params['companyrootcountrycode'] = $companyroot[0]['company_country_code'];
                $params['companyrootstatecode'] = $companyroot[0]['company_state_code'];
                $params['companyrootstatename'] = $companyroot[0]['company_state_name'];

                $params['companyrootdomain'] = ucfirst($companyroot[0]['domain']);
                $params['companyrootsubdomain'] = ucfirst($companyroot[0]['subdomain']);
                $params['companyrootphone'] = ucfirst($companyroot[0]['phone']);
    
            }

            $rootAdminDefault = $this->get_default_admin($params['idsys']);
            if (count($rootAdminDefault) > 0) {
                $params['userrootname'] = ucfirst($rootAdminDefault[0]['name']);
                $params['userrootemail'] = $rootAdminDefault[0]['email'];
                $params['userrootaddres'] = $rootAdminDefault[0]['address'];
                $params['userrootcity'] = $rootAdminDefault[0]['city'];
                $params['userrootzip'] = $rootAdminDefault[0]['zip'];
                $params['userrootcountrycode'] = $rootAdminDefault[0]['country_code'];
                $params['userrootstatecode'] = $rootAdminDefault[0]['state_code'];
                $params['userrootstatename'] = $rootAdminDefault[0]['state_name'];
            }
        }

        
        $isAgency = User::where('company_id','=',$company[0]["id"])
                        ->where('user_type','=','userdownline')
                        ->where('company_parent', '<>', null)
                        ->where('active','=','T')
                        ->get();
                            
        if (count($isAgency) > 0) {
            // CHECK USER THIRD PARTY STATUS
            $companyStripe = CompanyStripe::where('company_id','=',$params['ownedcompanyid'])
                                            ->where('status_acc','<>','')
                                            ->first();

            // return response()->json(['companyStripe' => $companyStripe]);
            /** GET STRIPE KEY */
            $stripeseckey = config('services.stripe.secret');
            $stripepublish = $this->getcompanysetting($params['idsys'],'rootstripe');
            if ($stripepublish != '') {
                $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
            }
            /** GET STRIPE KEY */
            $stripe = new StripeClient([
                'api_key' => $stripeseckey,
                'stripe_version' => '2020-08-27'
            ]);
            $params['charges_enabled'] = '';
            $params['payouts_enabled'] = '';
            $params['account_requirements'] = '';
            try {
                $chkAcc  = $stripe->accounts->retrieve(
                $companyStripe->acc_connect_id,
                []
                );
                if ($chkAcc) {
                    $params['charges_enabled'] = $chkAcc->charges_enabled;
                    $params['payouts_enabled'] = $chkAcc->payouts_enabled;
                    $params['account_requirements'] = $chkAcc->requirements->errors[0]->reason;            
                }
            } catch (\Throwable $th) {
                // return response()->json(['result' => 'failed', 'msh' => $th->getMessage(), 'ID' => $companyStripe->acc_connect_id ]);
            }
            // CHECK USER THIRD PARTY STATUS
        }
        /** CHECK ROOT COMPANY NAME */

        /** CHECK IS MASTER ROOT COMPANY */
        $masterRoot = config('services.application.systemid');
        //if ($params['ownedcompanyid'] == $masterRoot && $params['idsys'] == $masterRoot) {
        if ($params['idsys'] == $masterRoot) {
            $params['rootcomp'] = "T";
        }
        /** CHECK IS MASTER ROOT COMPANY */

        /** CHECK FOR AGREEMENT AGENCY EMM */
        if ($masterRoot == $params['idsys']) {
            $params['agencyplatformroot'] = 'T';
        }
        /** CHECK FOR AGREEMENT AGENCY EMM */
            

        return response()->json(array('result'=>'success','params'=>$params,'sidemenu'=>$customsidebarleadmenu,'paymenttermlist'=>$paymentTerms,'urlredirect'=>$_redirecturl,'rootsidemenu'=>$rootsidebarleadmenu));
    }

    public function getstate(Request $request) {
        $state_code = (isset($request->statecode))?$request->statecode:'';
       
        if($state_code == '') {
            $statelist = State::select('state','state_code','sifi_state_id')
                                ->where('country_code','=','US')
                                ->orderBy('state')
                                ->get();
        }else if($state_code != '') {
            $state = State::select('state','sifi_state_id','sifi_country_id')
                                ->where('country_code','=','US')
                                ->where('state_code','=',$state_code)
                                ->get();
            if (count($state) > 0) {
                $http = new \GuzzleHttp\Client;

                $appkey = "86bb19a0-43e6-0139-8548-06b4c2516bae";
                $usrkey = "63c52610-87cd-0139-b15f-06a60fe5fe77";
                
                try {
                    $apiURL = "https://app.simpli.fi/api/geo_targets?parent_id=" . $state[0]['sifi_state_id'];
                    $options = [
                        'headers' => [
                            'X-App-Key' => $appkey,        
                            'X-User-Key' => $usrkey,
                            'Content-Type' => 'application/json',
                        ],
                    ];

                    $response = $http->get($apiURL,$options);
                    $statelist =  json_decode($response->getBody());

                }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                    if ($e->getCode() === 400) {
                        return response()->json('Invalid Request. Please enter a username or a password.', $e->getCode());
                    } else if ($e->getCode() === 401) {
                        return response()->json('Your credentials are incorrect. Please try again', $e->getCode());
                    }
        
                    return response()->json('Something went wrong on the server.', $e->getCode());
                }

            }
        }
        return response()->json(array('result'=>'success','params'=>$statelist));
    }

    public function checkSalesConnectedAccount(Request $request) {
        $usrID = (isset($request->userID) && $request->userID != "")?$request->userID:"";
        $accBankStatus = "";
        $accBank = "";
        $accBankList = "";

        $userStripe = User::select('id','acc_connect_id','acc_email','acc_ba_id','status_acc')
                            ->where('id','=',$usrID)
                            ->where('active','=','T')
                            ->get();

        
        $userStripeID = (isset($userStripe[0]->id))?$userStripe[0]->id:'';
        $accConID = (isset($userStripe[0]->acc_connect_id))?$userStripe[0]->acc_connect_id:'';
        $accBaID = (isset($userStripe[0]->acc_ba_id))?$userStripe[0]->acc_ba_id:'';

        if($userStripe->count() == 0) {
            return response()->json(array('result'=>'failed','message'=>'Company Not Registered','params'=>''));
        }else{
            /** GET STRIPE KEY */
            $stripeseckey = config('services.stripe.secret');
            $stripepublish = $this->getcompanysetting($request->idsys,'rootstripe');
            if ($stripepublish != '') {
                $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
            }
            /** GET STRIPE KEY */

            /** CHECK IF BANK ACCOUNT ALREADY HAVE */
            if ($userStripe[0]->acc_ba_id == '' && $userStripe[0]->acc_connect_id != '') {
                $stripe = new StripeClient([
                    'api_key' => $stripeseckey,
                    'stripe_version' => '2020-08-27'
                ]);

                $chkAcc  = $stripe->accounts->retrieve(
                    $userStripe[0]->acc_connect_id,
                    []
                );

                if(isset($chkAcc->external_accounts->data[0])) {
                    if($chkAcc->external_accounts->data[0]->object == 'bank_account') {
                        $ba_id = $chkAcc->external_accounts->data[0]->id;
                        $userStripeUpdate = User::find($userStripe[0]->id);
                        $userStripeUpdate->acc_ba_id = $ba_id;
                        $userStripeUpdate->save();
                    }
                }


            } 
            /** CHECK IF BANK ACCOUNT ALREADY HAVE */

            if(($userStripe[0]->status_acc == 'pending' || $userStripe[0]->status_acc == 'inverification') && $userStripe[0]->acc_connect_id != '') {
                /** GET FROM STRIPE AND CHECK THE STATUS */
                $stripe = new StripeClient([
                    'api_key' => $stripeseckey,
                    'stripe_version' => '2020-08-27'
                ]);

                $chkAcc  = $stripe->accounts->retrieve(
                    $userStripe[0]->acc_connect_id,
                    []
                );
                
                $charges_enabled = $chkAcc->charges_enabled;
                $payouts_enabled = $chkAcc->payouts_enabled;
                $disabled_reason = $chkAcc->requirements->disabled_reason;
                $pending_verification = $chkAcc->requirements->pending_verification;
                
                
                if($charges_enabled && $payouts_enabled && count($pending_verification) == 0 && $disabled_reason == '') {
                    
                    /** UPDATE COMPANY STATUS TO COMPLETE */
                    $companyupdate = User::find($userStripeID);
                    $companyupdate->status_acc = "completed";
                    $companyupdate->save();
                    /** UPDATE COMPANY STATUS TO COMPLETE */
                    $accBankStatus = "";
                    $accBank = "";
                    $accBankList = "";

                    return response()->json(array('result'=>'success','message'=>'Company Completed Registered','params'=>$userStripe,'bstatus'=>$accBankStatus,'bacc'=>$accBank,'blist'=>$accBankList));
                }else if($charges_enabled === false && $payouts_enabled === false && count($pending_verification) > 0 && $disabled_reason == 'requirements.pending_verification') {
                    if ($userStripe[0]->status_acc != 'inverification') {
                        /** UPDATE CHURCHS STATUS TO COMPLETE */
                        $churchupdate = User::find($userStripeID);
                        $churchupdate->status_acc = "inverification";
                        $churchupdate->save();
                        /** UPDATE CHURCHS STATUS TO COMPLETE */
                    }

                    //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church in Verification',URL::current(),$request,$request->ip());
                    return response()->json(array('result'=>'pending-verification','message'=>'Company in Verification','params'=>$userStripe));
                }else if($userStripe[0]->status_acc == 'pending' && $charges_enabled === false && $payouts_enabled === false  && $disabled_reason != '') {
                    

                    //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church need to complete the registration',URL::current(),$request,$request->ip());
                    return response()->json(array('result'=>'pending','message'=>'Company need to complete the registration','params'=>$userStripe));
                }else if ($userStripe[0]->status_acc == 'inverification') {
                    //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church in Verification blue pending',URL::current(),$request,$request->ip());
                    return response()->json(array('result'=>'pending-verification','message'=>'Company in Verification','params'=>$userStripe));
                }
                /** GET FROM STRIPE AND CHECK THE STATUS */
                
            }else if($userStripe[0]->acc_connect_id == '') {
                //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church Not Registered Acc Connect ID Empty',URL::current(),$request,$request->ip());
                return response()->json(array('result'=>'failed','message'=>'Company Not Registered','params'=>''));
            }else{
                
                return response()->json(array('result'=>'success','params'=>$userStripe,'bstatus'=>$accBankStatus,'bacc'=>$accBank,'blist'=>$accBankList));
            }

        }

    }

    public function checkpaymentconnection(Request $request) {
        $companyID = (isset($request->companyID) && $request->companyID != "")?$request->companyID:"";
        $typeConnection = (isset($request->typeConnection) && $request->typeConnection != "")?$request->typeConnection:"";
        $companyStripe = CompanyStripe::where('company_id','=',$companyID)->get();
        if (count($companyStripe) > 0) {
            return response()->json(array('result'=>'success'));
        }else{
            return response()->json(array('result'=>'failed'));
        }
    }

    public function resetpaymentconnection(Request $request) {
        $companyID = (isset($request->companyID) && $request->companyID != "")?$request->companyID:"";
        $typeConnection = (isset($request->typeConnection) && $request->typeConnection != "")?$request->typeConnection:"";
        $campaignList = array();

        $loguser = $this->logUserAction($companyID,'Reset Connection',"Company ID : " . $companyID . ' clicked reset connection');

        /** CHECK IF COMPANY PAYMENT BY KARTRA */
        $chkCompanyPayment = Company::select('id','paymentgateway')->where('id','=',$companyID)->get();
        if (count($chkCompanyPayment) > 0) {
            if (isset($chkCompanyPayment[0]['paymentgateway']) && $chkCompanyPayment[0]['paymentgateway'] == 'kartra') {
                return response()->json(array('result'=>'failed'));
                exit;die();
            }
        }
        /** CHECK IF COMPANY PAYMENT BY KARTRA */

        if ($companyID != "" && $typeConnection == "stripe") {
            /** FIND ACTIVE CAMPAIGN AND MAKE IT PAUSE */
            $activeCampaign = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.user_id','leadspeek_users.leadspeek_api_id','leadspeek_users.leadspeek_type','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid')
                                ->join('users','leadspeek_users.user_id','=','users.id')
                                ->where('leadspeek_users.active','=','T')
                                ->where('leadspeek_users.disabled','=','F')
                                ->where('leadspeek_users.active_user','=','T')
                                ->where('leadspeek_users.archived','=','F')
                                ->where('leadspeek_users.company_id','=',$companyID)
                                ->where('users.active','=','T')
                                ->get();
            foreach($activeCampaign as $ac) {
                if ($ac['leadspeek_type'] == "locator") {
                   if ($ac['leadspeek_organizationid'] != "" && $ac['leadspeek_campaignsid'] != "") {
                        $http = new \GuzzleHttp\Client;

                        $appkey = "86bb19a0-43e6-0139-8548-06b4c2516bae";
                        $usrkey = "63c52610-87cd-0139-b15f-06a60fe5fe77";
                        $organizationID = $ac['leadspeek_organizationid'];
                        $campaignsID = $ac['leadspeek_campaignsid'];

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
                                
                                for($j=0;$j<count($result->campaigns[0]->actions);$j++) {
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
                                                'address' => 'noreply@sitesettingsapi.com',
                                                'name' => 'Support',
                                                'replyto' => 'support@sitesettingsapi.com',
                                            ];
                                            $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log for Pause Campaign (Reset Payment Connection) ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog','');
                                        }
                                    }
                                }

                        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                            $details = [
                                'errormsg'  => 'Error when trying to get campaign information Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID . '(' . $e->getCode() . ')',
                            ];
                            $from = [
                                'address' => 'noreply@esitesettingsapi.com',
                                'name' => 'Support',
                                'replyto' => 'support@sitesettingsapi.com',
                            ];
                            $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log for Pause Campaign (Reset Payment Connection) ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog','');
                        }

                   }
                }

                $campaignList[] = $ac['leadspeek_api_id'];

                $leads = LeadspeekUser::find($ac['leadspeek_api_id']);
                $leads->active = 'F';
                $leads->disabled = 'T';
                $leads->active_user = 'T';
                $leads->paused_dueresetcon = 'T';
                $leads->save();
            }
            /** FIND ACTIVE CAMPAIGN AND MAKE IT PAUSE */

            /** FIND ALL ACTIVE CLIENT AND REMOVE THE PAYMENT CUST ID AND CARD */
            $activeClient = User::where('company_parent','=',$companyID)
                                ->where('user_type','=','client')
                                ->where('active','T')
                                ->whereNotNull('company_id')
                                ->where('customer_payment_id','<>','')
                                ->where('customer_card_id','<>','')
                                ->update(["customer_payment_id" => "","customer_card_id" => ""]);

            /** FIND ALL ACTIVE CLIENT AND REMOVE THE PAYMENT CUST ID AND CARD */

            /** REMOVE AGENCY STRIPE CONNECTION */
                //$companyStripe = CompanyStripe::where('company_id','=',$companyID)->delete();
                $companyStripe = CompanyStripe::where('company_id','=',$companyID)->update(["acc_connect_id" => "","acc_ba_id" => "","acc_holder_name" => "","acc_holder_type" => "", "ba_name" => "", "ba_route" => "","status_acc"=>"pending"]);
            /** REMOVE AGENCY STRIPE CONNECTION */

            /** SENT NOTIFICATION TO ALL ADMIN */
            $adminList = User::where('company_id','=',$companyID)->where('active','T')->whereRaw("user_type IN ('user','userdownline')")->where('isAdmin','=','T')->orderByEncrypted('name')->get();
            foreach($adminList as $adl) {
                $details = [
                    'campaignlist' => implode("<br/>",$campaignList),
                ];
                $attachement = array();
                
                $from = [
                    'address' => 'noreply@sitesettingsapi.com',
                    'name' => 'Support',
                    'replyto' => 'noreply@sitesettingsapi.com',
                ];
        
                $this->send_email(array($adl['email']),$from,'Due the Payment Connection Reset' ,$details,$attachement,'emails.campaignpausedueresetcon','');
            }
            /** SENT NOTIFICATION TO ALL ADMIN */
            
            return response()->json(array('result'=>'success'));
        }else{
            return response()->json(array('result'=>'failed'));
        }

    }

    public function checkconnectedaccount(Request $request) {
        $companyID = (isset($request->companyID) && $request->companyID != "")?$request->companyID:"";
        $idsys = (isset($request->idsys) && $request->idsys != "")?$request->idsys:"";

        $accBankStatus = "";
        $accBank = "";
        $accBankList = "";

        /** CHECK OF PAYMENT TYPE OF COMPANY */
        $paymentgateway = 'stripe';
        $chkCompPay = Company::select('paymentgateway')->where('id','=',$companyID)
                            ->get();
        if (count($chkCompPay) > 0) {
            $paymentgateway = $chkCompPay[0]['paymentgateway'];
        }
        /** CHECK OF PAYMENT TYPE OF COMPANY */

        $companyStripe = CompanyStripe::where('company_id','=',$companyID)
                                        ->where('status_acc','<>','')
                                        ->get();
        
        
        $companystripeID = (isset($companyStripe[0]->id))?$companyStripe[0]->id:'';
        $accConID = (isset($companyStripe[0]->acc_connect_id))?$companyStripe[0]->acc_connect_id:'';
        $accProdID = (isset($companyStripe[0]->acc_prod_id))?$companyStripe[0]->acc_prod_id:'';
        $accBaID = (isset($companyStripe[0]->acc_ba_id))?$companyStripe[0]->acc_ba_id:'';
        $packageID = (isset($companyStripe[0]->package_id))?$companyStripe[0]->package_id:'';

        $whitelabellingpackage = 'F';
        $openallplan = 'F';
        $plannextbill = '';
        
        $packageName = '';
        
        if (isset($companyStripe[0]->plan_next_date) && $companyStripe[0]->plan_next_date != '0000-00-00' && $companyStripe[0]->plan_next_date != null) {
            $plannextbill = date('F j, Y',strtotime($companyStripe[0]->plan_next_date));

            if (date('Ymd',strtotime($companyStripe[0]->plan_next_date)) <= date('Ymd')) {
                $openallplan = 'T';
            }
        }

        //return URL::current();
        if($companyStripe->count() == 0) {
            //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church Not Registered',URL::current(),$request,$request->ip());
            return response()->json(array('result'=>'failed','message'=>'Company Not Registered','params'=>''));
        }else{
            /** GET STRIPE KEY */
            $stripeseckey = config('services.stripe.secret');

            /** CHECK IF IDSYS NOT PASS THEN FIND THE IDSYS */
            if ($idsys == "") {
                $findIdsys = User::select('company_parent')
                                ->where('company_id','=',$companyID)
                                ->where('user_type','=','userdownline')
                                ->first();
                if ($findIdsys) {
                    $idsys = $findIdsys->company_parent;
                }
            }
            /** CHECK IF IDSYS NOT PASS THEN FIND THE IDSYS */
            
            $stripepublish = $this->getcompanysetting($idsys,'rootstripe');
            if ($stripepublish != '') {
                $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
            }
            /** GET STRIPE KEY */

            /** CHECK IF BANK ACCOUNT ALREADY HAVE */
            if ($companyStripe[0]->acc_ba_id == '' && $companyStripe[0]->acc_connect_id != '') {
                
                $stripe = new StripeClient([
                    'api_key' => $stripeseckey,
                    'stripe_version' => '2020-08-27'
                ]);

                try {
                    $chkAcc  = $stripe->accounts->retrieve(
                        $companyStripe[0]->acc_connect_id,
                        []
                    );
                }catch(PermissionException $e){
                    return response()->json(array('result'=>'failedkey : ' . $idsys,'message'=>'Company Not Registered','msg'=>$e->getError()->message));
                }

                if(isset($chkAcc->external_accounts->data[0])) {
                    if($chkAcc->external_accounts->data[0]->object == 'bank_account') {
                        $ba_id = $chkAcc->external_accounts->data[0]->id;
                        $companyStripeUpdate = CompanyStripe::find($companyStripe[0]->id);
                        $companyStripeUpdate->acc_ba_id = $ba_id;
                        $companyStripeUpdate->save();
                    }
                }


            } 
            /** CHECK IF BANK ACCOUNT ALREADY HAVE */

            if((($companyStripe[0]->status_acc == 'pending' || $companyStripe[0]->status_acc == 'inverification') || ($paymentgateway == 'kartra' && $companyStripe[0]->status_acc == '')) && $companyStripe[0]->acc_connect_id != '') {
                /** GET FROM STRIPE AND CHECK THE STATUS */
                $stripe = new StripeClient([
                    'api_key' => $stripeseckey,
                    'stripe_version' => '2020-08-27'
                ]);

                $chkAcc  = $stripe->accounts->retrieve(
                    $companyStripe[0]->acc_connect_id,
                    []
                );
                
                $charges_enabled = $chkAcc->charges_enabled;
                $payouts_enabled = $chkAcc->payouts_enabled;
                $disabled_reason = $chkAcc->requirements->disabled_reason;
                $pending_verification = $chkAcc->requirements->pending_verification;
                
                
                //if($charges_enabled && $payouts_enabled && count($pending_verification) == 0 && $disabled_reason == '') {
                if($charges_enabled && $payouts_enabled) {

                    /** UPDATE COMPANY STATUS TO COMPLETE */
                    $companyupdate = companyStripe::find($companystripeID);
                    $companyupdate->status_acc = "completed";
                    $companyupdate->save();
                    /** UPDATE COMPANY STATUS TO COMPLETE */
                    $accBankStatus = "";
                    $accBank = "";
                    $accBankList = "";
                    
                    /** GET PACKAGE WHITELABELLING OR NON */
                    if (trim($companyStripe[0]->package_id) != '') {
                        $chkPackage = PackagePlan::select('whitelabelling','package_name')
                                                ->where('package_id','=',trim($companyStripe[0]->package_id))
                                                ->get();
                        foreach($chkPackage as $chkpak) {
                            $whitelabellingpackage = $chkpak['whitelabelling'];
                            $packageName = $chkpak['package_name'];
                        }

                    }
                    /** GET PACKAGE WHITELABELLING OR NON */
                    if ($paymentgateway != 'stripe') {
                        $companyStripe[0]->status_acc = 'completed';
                        return response()->json(array('result'=>'success','params'=>$companyStripe,'bstatus'=>$accBankStatus,'bacc'=>$accBank,'blist'=>$accBankList,'packagewhite'=>$whitelabellingpackage,'openallplan'=>$openallplan,'plannextbill'=>"",'paymentgateway'=>$paymentgateway,'packagename'=>$packageName));
                    }else{
                        $freeplanID = "";
                        $usrChk = User::select('customer_payment_id','company_root_id')
                                    ->where('company_id','=',$companyID)
                                    ->where('user_type','=','userdownline')
                                    ->get();
                            if (count($usrChk) > 0) {
                                /** GET FREE PACKAGE PLAN */
                                $getfreePlan = $this->getcompanysetting($usrChk[0]['company_root_id'],'agencyplan');
                                
                                if ($getfreePlan != '') {
                                    $freeplanID = (isset($getfreePlan->livemode->free))?$getfreePlan->livemode->free:"";
                                    if (config('services.appconf.devmode') === true) {
                                        $freeplanID = (isset($getfreePlan->testmode->free))?$getfreePlan->testmode->free:"";
                                    }
                                }
                                /** GET FREE PACKAGE PLAN */
                            }

                        if ($packageID == $freeplanID) {
                            $plannextbill = "free";
                        }
                        return response()->json(array('result'=>'success','message'=>'Company Completed Registered','params'=>$companyStripe,'bstatus'=>$accBankStatus,'bacc'=>$accBank,'blist'=>$accBankList,'packagewhite'=>$whitelabellingpackage,'openallplan'=>$openallplan,'plannextbill'=>$plannextbill,'paymentgateway'=>$paymentgateway));
                    }
                }else if($charges_enabled === false && $payouts_enabled === false && count($pending_verification) > 0 && $disabled_reason == 'requirements.pending_verification') {
                    if ($companyStripe[0]->status_acc != 'inverification') {
                        /** UPDATE CHURCHS STATUS TO COMPLETE */
                        $churchupdate = CompanyStripe::find($companystripeID);
                        $churchupdate->status_acc = "inverification";
                        $churchupdate->save();
                        /** UPDATE CHURCHS STATUS TO COMPLETE */
                    }

                    //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church in Verification',URL::current(),$request,$request->ip());
                    return response()->json(array('result'=>'pending-verification','message'=>'Company in Verification','params'=>$companyStripe,'packagewhite'=>$whitelabellingpackage,'openallplan'=>$openallplan,'plannextbill'=>$plannextbill));
                }else if($companyStripe[0]->status_acc == 'pending' && $charges_enabled === false && $payouts_enabled === false  && $disabled_reason != '') {
                    

                    //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church need to complete the registration',URL::current(),$request,$request->ip());
                    return response()->json(array('result'=>'pending','message'=>'Company need to complete the registration','params'=>$companyStripe,'packagewhite'=>$whitelabellingpackage,'openallplan'=>$openallplan,'plannextbill'=>$plannextbill));
                }else if ($companyStripe[0]->status_acc == 'inverification') {
                    //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church in Verification blue pending',URL::current(),$request,$request->ip());
                    return response()->json(array('result'=>'pending-verification','message'=>'Company in Verification','params'=>$companyStripe,'packagewhite'=>$whitelabellingpackage,'openallplan'=>$openallplan,'plannextbill'=>$plannextbill));
                }else if ($charges_enabled === false || $payouts_enabled === false) {
                    return response()->json(array(
                        'result'=>'success',
                        'params'=>$companyStripe,
                        'bstatus'=>$accBankStatus,
                        'bacc'=>$accBank,
                        'blist'=>$accBankList,
                        'packagewhite'=>$whitelabellingpackage,
                        'openallplan'=>$openallplan,
                        'plannextbill'=>$plannextbill,
                        'paymentgateway'=>$paymentgateway,
                        'packagename'=>$packageName,
                        'charges_enabled'=>$chkAcc->charges_enabled,
                        'payouts_enabled'=>$chkAcc->payouts_enabled,
                        'account_requirements'=>$chkAcc->requirements,
                    ));
                }
                /** GET FROM STRIPE AND CHECK THE STATUS */
                
            }else if($companyStripe[0]->acc_connect_id == '') {
                //$this->mylog->write($request->usrID,'Church Check Status','ChurchController','Church Not Registered Acc Connect ID Empty',URL::current(),$request,$request->ip());
                return response()->json(array('result'=>'failed','message'=>'Company Not Registered','params'=>''));
            }else{
                
                /** GET PACKAGE WHITELABELLING OR NON */
                if (trim($companyStripe[0]->package_id) != '') {
                    $chkPackage = PackagePlan::select('whitelabelling','package_name')
                                            ->where('package_id','=',trim($companyStripe[0]->package_id))
                                            ->get();
                    foreach($chkPackage as $chkpak) {
                        $whitelabellingpackage = $chkpak['whitelabelling'];
                        $packageName = $chkpak['package_name'];
                    }

                }
                /** GET PACKAGE WHITELABELLING OR NON */

                /** GET PAYMENT GATEWAY TYPE */
                $compInfo = Company::select('paymentgateway')->where('id','=',$companyID)->get();
                if (count($compInfo) > 0) {
                    $paymentgateway = $compInfo[0]['paymentgateway'];
                    
                }
                /** GET PAYMENT GATEWAY TYPE */

                /** GET NEXT BILLING SUBCRIPTION */
                $usr = User::select('customer_payment_id','company_root_id')
                            ->where('company_id','=',$companyID)
                            ->where('user_type','=','userdownline')
                            ->get();
                if (count($usr) > 0) {
                    /** GET FREE PACKAGE PLAN */
                    $getfreePlan = $this->getcompanysetting($usr[0]['company_root_id'],'agencyplan');
                    
                    if ($getfreePlan != '') {
                        $freeplanID = (isset($getfreePlan->livemode->free))?$getfreePlan->livemode->free:"";
                        if (config('services.appconf.devmode') === true) {
                            $freeplanID = (isset($getfreePlan->testmode->free))?$getfreePlan->testmode->free:"";
                        }
                    }

                    $stripe = new StripeClient([
                        'api_key' => $stripeseckey,
                        'stripe_version' => '2020-08-27'
                    ]);
                    try {
                        $chkAcc  = $stripe->accounts->retrieve(
                            $companyStripe[0]->acc_connect_id,
                            []
                        );
                    } catch (\Throwable $th) {
                    }
                    
                    /** GET FREE PACKAGE PLAN */
                    if ($packageID == $freeplanID) {
                        $plannextbill = "free";
                    }else{
                        try {
                            $nextInv = $stripe->invoices->upcoming([
                                    'customer' => $usr[0]['customer_payment_id'],
                                ]);

                            $plannextbill = date('F j, Y',$nextInv->next_payment_attempt);
                        }catch (InvalidRequestException $e) {
                            $plannextbill = "";
                        }
                    }
                }

                // check if packagewhite exists
                $getIsWhitelabelingByCompany = Company::select('is_whitelabeling')->where('id', '=', $companyID)->first();
                $is_whitelabeling = $getIsWhitelabelingByCompany->is_whitelabeling ? $getIsWhitelabelingByCompany->is_whitelabeling : $whitelabellingpackage;

                /** GET NEXT BILLING SUBCRIPTION */
                
                return response()->json(array(
                    'result'=>'success',
                    'params'=>$companyStripe,
                    'bstatus'=>$accBankStatus,
                    'bacc'=>$accBank,
                    'blist'=>$accBankList,
                    'packagewhite'=>$whitelabellingpackage,
                    'openallplan'=>$openallplan,
                    'plannextbill'=>$plannextbill,
                    'paymentgateway'=>$paymentgateway,
                    'packagename'=>$packageName,
                    'charges_enabled'=>$chkAcc->charges_enabled,
                    'payouts_enabled'=>$chkAcc->payouts_enabled,
                    'account_requirements'=>$chkAcc->requirements,
                    'is_whitelabeling'=>$is_whitelabeling,
                ));
            }
        }
    }
    
    public function createSalesStripeConnect(Request $request) {
        $usrID = (isset($request->userID))?trim($request->userID):'';
        $stripeConnectAccID = "";
        
        $userStripe = User::select('id','acc_connect_id','acc_email','acc_ba_id','status_acc','name','email','address','city','state_code','zip','country_code','phone_country_code')
                            ->where('id','=',$usrID)
                            ->where('active','=','T')
                            ->get();

        /** GET STRIPE KEY */
        $stripeseckey = config('services.stripe.secret');
        $stripepublish = $this->getcompanysetting($request->idsys,'rootstripe');
        if ($stripepublish != '') {
            $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
        }
        /** GET STRIPE KEY */

        $stripe = new StripeClient([
            'api_key' => $stripeseckey,
			'stripe_version' => '2020-08-27'
        ]);
        
        if($userStripe->count() == 0 || ($userStripe->count() > 0 && $userStripe[0]->acc_connect_id == '') || (!isset($userStripe[0]->acc_connect_id) && is_null($userStripe[0]->acc_connect_id))) {
            
            $tmpname = explode(" ",$userStripe[0]->name);
            $fname = (isset($tmpname[0]))?$tmpname[0]:"";
            $lname = (isset($tmpname[1]))?$tmpname[1]:"";

            $defaultCountry = "US";

            if (trim($userStripe[0]->country_code) != '') {
                $defaultCountry = trim($userStripe[0]->country_code);
            }else if (trim($userStripe[0]->phone_country_code) != '') {
                $defaultCountry = trim($userStripe[0]->phone_country_code);
            }

            if (trim($userStripe[0]->country_code) == '') {
                $queryUpdateUsr = User::find($usrID);
                $queryUpdateUsr->country_code = $defaultCountry;
                $queryUpdateUsr->save();
            }
            
            $stripeCreateAccount = $stripe->accounts->create([
                    'type' => 'standard',
                    //'country' => $defaultCountry,
                    //'email' => $userStripe[0]->email,
    
                    'business_type' => 'individual',
                    'individual' => [
                        // 'email' => $userStripe[0]->email,
                        // 'first_name' => $fname,
                        // 'last_name' => $lname,
                        // 'address' => [
                        //     'line1' => $userStripe[0]->address,
                        //     'city' => $userStripe[0]->city,
                        //     'state' => $userStripe[0]->state_code,
                        //     'country' => $userStripe[0]->country_code,
                        //     'postal_code' => $userStripe[0]->zip,
                        // ]
                    ],
                ]);
    
                $stripeConnectAccID = $stripeCreateAccount->id;            
                
            }else{
                $stripeConnectAccID = $userStripe[0]->acc_connect_id;
            }

            $userStripeUpdate = User::find($userStripe[0]->id);

            $userStripeUpdate->acc_connect_id = $stripeConnectAccID;
            $userStripeUpdate->status_acc = 'pending';
            
            $userStripeUpdate->save();
     
            $resultData = array('userStripeID'=>$userStripe[0]->id,'ConnectAccID'=>$stripeConnectAccID);
     
            return response()->json(array('result'=>'success','message'=>'','params'=>$resultData));

    }

    public function createStripeConnect(Request $request) {
        
        $companyID = (isset($request->companyID))?trim($request->companyID):'';
        $company_name = (isset($request->companyname))?trim($request->companyname):'';
        $company_phone = (isset($request->companyphone))?trim($request->companyphone):'';
        $company_address = (isset($request->companyaddress))?trim($request->companyaddress):'';
        $company_city = (isset($request->companycity))?trim($request->companycity):'';
        $company_state = (isset($request->companystate))?trim($request->companystate):'';
        $company_country = (isset($request->companycountry))?trim($request->companycountry):'US';
        $company_zip = (isset($request->companyzip))?trim($request->companyzip):'';
        $company_email = (isset($request->companyemail))?trim($request->companyemail):'';
        $web_url = (isset($request->weburl))?trim($request->weburl):'';

        $companycheck = CompanyStripe::where('company_id','=',$companyID)
                            ->get();
        
        if($companycheck->count() == 0) {
            $createCompany = CompanyStripe::create([
                'company_id' => $companyID,
                'acc_connect_id' => '',
                'acc_prod_id' => '',
                'acc_email' => $company_email,
                'acc_ba_id' => '',
                'acc_holder_name' => '',
                'acc_holder_type' => '',
                'ba_name' => '',
                'ba_route' => '',
                'status_acc' => 'pending',
                'ipaddress' => '',
            ]);

            $newCompanyID = $createCompany->id;
        }else{
            $newCompanyID = $companycheck[0]->id;
            $status_acc = $companycheck[0]->status_acc;

            if ($companycheck[0]->acc_email == "") {
                $update = CompanyStripe::find($newCompanyID);
                $update->acc_email = $company_email;
                if ($status_acc == '') {
                    $update->status_acc = 'pending';
                }
                $update->save();
            }
        }
        /** CREATE ACCOUNT ON STRIPE CONNECT */
        //$this->mylog->write($user_id,'Create Stripe Connect Account','createStripeConnect','this create Stripe Connect Account ' . $newchurchID,URL::current(),$request,$request->ip());

        /** GET STRIPE KEY */
        $stripeseckey = config('services.stripe.secret');
        $stripepublish = $this->getcompanysetting($request->idsys,'rootstripe');
        if ($stripepublish != '') {
            $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
        }
        /** GET STRIPE KEY */

        $stripe = new StripeClient([
            'api_key' => $stripeseckey,
			'stripe_version' => '2020-08-27'
        ]);

        if($companycheck->count() == 0 || ($companycheck->count() > 0 && $companycheck[0]->acc_connect_id == '') || (!isset($companycheck[0]->acc_connect_id) && is_null($companycheck[0]->acc_connect_id))) {
	    $stripeCreateAccount = $stripe->accounts->create([
                'type' => 'standard',
                //'country' => $company_country,
                //'email' => $company_email,
                /*'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],*/
                'business_type' => 'non_profit',
                //'external_account' => $data->external_account,
                /*'tos_acceptance' => [
                    'date' => time(),
                    'ip' => $request->ip(),
                ],*/
                'business_profile' => [
                    'mcc' => 8661,
                    'url' => $web_url,
                ],
                'company' => [
                    //'name' => $company_name,
                    //'phone' => $church_phone,
                    //'tax_id' => $data->tax_id,
                    // 'address' => [
                    //     'line1' => $company_address,
                    //     'city' => $company_city,
                    //     'state' => $company_state,
                    //     'country' => $company_country,
                    //     'postal_code' => $company_zip,
                    // ]
                ],
            ]);

            $stripeConnectAccID = $stripeCreateAccount->id;            
            
        }else{
            $stripeConnectAccID = $companycheck[0]->acc_connect_id;
            //$productID = $companycheck[0]->acc_prod_id;
        }
        
        $companyStripe = CompanyStripe::find($newCompanyID);

        /** IF SITE ID FROM CLIENT EMPTY, SITE ID WILL SAME WITH THE ORIGINAL ID */
        if ($companyID == '') {
            $companyStripe->company_id = $newCompanyID;
        }
        /** IF SITE ID FROM CLIENT EMPTY, SITE ID WILL SAME WITH THE ORIGINAL ID */

        $companyStripe->acc_connect_id = $stripeConnectAccID;
       //$companyStripe->acc_prod_id = $productID;
        $companyStripe->save();

        //$this->mylog->write($user_id,'Create Church Account & Stripe Connect','createStripeConnect','Success',URL::current(),$request,$request->ip());

        $resultData = array('companyStripeID'=>$newCompanyID,'ConnectAccID'=>$stripeConnectAccID);

        return response()->json(array('result'=>'success','message'=>'','params'=>$resultData));
        /** CREATE ACCOUNT ON STRIPE CONNECT */

    }

    public function getAcccountLink(Request $request) {

       // $this->mylog->write($request->usrID,'Create Church AccountLink','getAcccountLink','start create account link',URL::current(),$request,$request->ip());
       
       /** GET STRIPE KEY */
       $stripeseckey = config('services.stripe.secret');
       $stripepublish = $this->getcompanysetting($request->idsys,'rootstripe');
       if ($stripepublish != '') {
           $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
       }
       /** GET STRIPE KEY */

        $stripe = new StripeClient([
            'api_key' => $stripeseckey,
			'stripe_version' => '2020-08-27'
        ]);
        
        $onboarding = $stripe->accountLinks->create([
						'account' => $request->connectid,
						'refresh_url' => $request->refreshurl,
						'return_url' => $request->returnurl,
						'type' => 'account_onboarding',
					]);
        
       //$this->mylog->write($request->usrID,'Create Church AccountLink','getAcccountLink','Success create account link',URL::current(),$request,$request->ip());

		return response()->json(array('result'=>'success','message'=>'linkcreated','params'=>$onboarding));
    }

    public function createreferallink(Request $request) {
        $usrID = (isset($request->userID) && $request->userID != "")?$request->userID:"";

        $referralcode = $this->generateReferralCode('salesref' . $usrID);

        $update =  User::find($usrID);
        $update->referralcode = $referralcode;
        $update->save();
        
        return response()->json(array('result'=>'success','refcode'=>$referralcode));
    }

    public function checkreferallink(Request $request) {
        $chkref = User::select('id')
                    ->where('active','=','T')
                    ->where('referralcode','=',trim($request->refcode))
                    ->get();
        if(count($chkref) > 0) {
            return response()->json(array('result'=>'success'));
        }else{
            return response()->json(array('result'=>'failed'));
        }
    }
}
