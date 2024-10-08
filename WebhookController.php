<?php

namespace App\Http\Controllers;

use App\Jobs\BigDBMCreateListJob;
use App\Mail\Gmail;
use App\Models\BigDBMLeads;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\FailedRecord;
use App\Models\IntegrationSettings;
use App\Models\LeadsListsQueue;
use App\Models\LeadspeekInvoice;
use App\Models\LeadspeekReport;
use App\Models\LeadspeekUser;
use App\Models\OptoutList;
use App\Models\Person;
use App\Models\PersonAddress;
use App\Models\PersonEmail;
use App\Models\PersonName;
use App\Models\PersonPhone;
use App\Models\ReportAnalytic;
use App\Models\State;
use App\Models\SuppressionList;
use App\Models\User;
use App\Services\GoogleSheet;
use Carbon\Carbon;
use DateTime;
use Exception;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_Spreadsheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\OAuth\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\StripeClient;
use App\Models\ListAndTag;
use App\Models\Topup;
use Illuminate\Support\Facades\Cache;

class WebhookController extends Controller
{
    protected  $api_key;
    protected  $api_password;
    public function zapier() {
        // set dummy param 
        $company_id = 90;
        $leadspeek_api_id = '861487';
        $leadspeek_type = 'locator';
        // set dummy param 
        
        $chkZap = IntegrationSettings::select('api_key', 'enable_sendgrid')
            ->where('company_id', '=', $company_id)
            ->where('integration_slug', 'zapier')
            ->where('enable_sendgrid', 1)
            ->first();
    
        $campaign = LeadspeekUser::select('leadspeek_users.id', 'leadspeek_users.zap_tags', 'leadspeek_users.zap_is_active', 'leadspeek_users.zap_webhook', 'leadspeek_users.company_id')
            ->where('leadspeek_api_id', '=', $leadspeek_api_id)
            ->where('zap_is_active', 1)
            ->first();
        if ($chkZap) {
            if ($chkZap->api_key != '' && $chkZap->enable_sendgrid == 1) {
                $webhook = ($campaign && $campaign->zap_webhook != '') ? $campaign->zap_webhook : $chkZap->api_key;
                $tags = ($campaign && !empty($campaign->zap_tags)) ? json_decode($campaign->zap_tags) : '';
                if ($campaign && $campaign->zap_is_active == 1) {
                    $send_to_zapier = $this->zap_sendrecord(
                        $webhook,
                        date('Y-m-d'),
                        ucfirst(strtolower('agies')),
                        ucfirst(strtolower('wahyudi')),
                        'agieswahyudi@gmail.com',
                        'agiesganteng2gmail.com',
                        '081398257238',
                        '021524352689',
                        'plara',
                        'plara2',
                        'sukabumi',
                        'jawa barat',
                        '43356',
                        'keyword',
                        $tags,
                        $leadspeek_api_id,
                        $leadspeek_type
                    );
                    return response()->json(['message' => 'lead sent successfully to zapier']);
                }
            }
        } else {
            return response()->json(['message' => 'this agency has no any integrations with zapier']);
        }
        return response()->json(['message' => 'done']);
    }
    
    public function gohighlevel() {
        $campaigns = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.ghl_tags','leadspeek_users.ghl_is_active','leadspeek_users.company_id')->where('leadspeek_api_id','=','282263')->get();
        $_ghl_api_key = "";
        $_enable_ghl = "";
        $chkGhl = IntegrationSettings::select('api_key','enable_sendgrid')
                                ->where('company_id','=','58')
                                ->where('integration_slug','=','gohighlevel')
                                ->get();
        if (count($chkGhl) > 0) {
            $_ghl_api_key = $chkGhl[0]['api_key'];
            $_enable_ghl = $chkGhl[0]['enable_sendgrid'];
        }

        if ($_ghl_api_key != "" && $_enable_ghl == "1" && $campaigns[0]['ghl_is_active'] == "1") {
            /** CREATE GHL CONTACT AND ATTACHED THE TAG IF EXIST */

            /** CHECK IF TAG EXIST ON GHL */
            $tags = [];
            $saveTags = [];
            $removeTags = [];

            $ghltags = json_decode($campaigns[0]['ghl_tags']);
            if (isset($ghltags) && count($ghltags) > 0) {

                foreach($ghltags as $item) {
                    $partItem = explode('|',$item);
                    /** CHECK IF TAG STILL EXIST ON GOHIGHLEVEL */
                    $chkTag = $this->ghl_getTag($partItem[0],$_ghl_api_key);
                    /** CHECK IF TAG STILL EXIST ON GOHIGHLEVEL */
                    if ($chkTag != '' && $chkTag != 'sys_removed') {
                        $tags[] = $partItem[1];
                        $saveTags[] = $partItem[0] . '|' . $partItem[1];
                    }else if ($chkTag == 'sys_removed') {
                        $removeTags[] = $partItem[1];
                    }
                }

            }

            /** IF THERE ARE REMOVE TAG FROM SYSTEM THEN UPDATE/SYNC THE TAG ON THE CAMPAIGN */
            if (count($removeTags) > 0) {
                $update = LeadspeekUser::where('id',$campaigns[0]['id'])
                                        ->update(['ghl_tags' => json_encode($saveTags),'ghl_remove_tags' => $removeTags]);
            }
            /** IF THERE ARE REMOVE TAG FROM SYSTEM THEN UPDATE/SYNC THE TAG ON THE CAMPAIGN */

            /** CHECK IF TAG EXIST ON GHL */

            /** INSERT INTO GHL CONTACT */

            $this->ghl_createContact('58',$_ghl_api_key,'11111','2024-02-21','Ajoe','Doe','ajoedoe@gmail.com','ajoedoe2@gmail.com','111-111-1111','222-222-2222','Stree Address 1','Street Address 2','Dolorado','AL','2322','test key',$tags);
            /** INSERT INTO GHL CONTACT */

            /** CREATE GHL CONTACT AND ATTACHED THE TAG IF EXIST */
        }
    }

    /** GET LEADS FROM BIGDBM */
    public function get_bigdbm_leads(){
        /* SCRIPT UNTUK HAPUS LEADSLISTQUEUE SEHARI SEBELUMNYA */
        /* SCRIPT UNTUK HAPUS LEADSLISTQUEUE SEHARI SEBELUMNYA */

        $has_create_list = LeadsListsQueue::where('created_at','>=', Carbon::today())->get();
        if (count($has_create_list) == 0) {
            $campaigns = LeadspeekUser::select(
                'leadspeek_users.id', 
                'leadspeek_users.company_id', 
                'leadspeek_users.leadspeek_api_id', 
                'leadspeek_users.leadspeek_locator_zip', 
                'leadspeek_users.leadspeek_locator_state', 
                'leadspeek_users.leadspeek_locator_city', 
                'leadspeek_users.leadspeek_locator_keyword', 
                'leadspeek_users.lp_limit_leads', 
                'users.company_root_id'
            )
            ->leftJoin('users', 'leadspeek_users.company_id', '=', 'users.company_id')
            ->where('users.user_type','userdownline')
            ->where('leadspeek_users.leadspeek_type', 'enhance')
            ->where('leadspeek_users.active', 'T')
            ->where('leadspeek_users.disabled', 'F')
            ->where('leadspeek_users.active_user', 'T')
            ->get();
    
            if (!empty($campaigns)) {
                foreach ($campaigns as $campaign) {
                    if ($campaign->lp_limit_leads > 0) {
                        BigDBMCreateListJob::dispatch([
                            'company_id' => $campaign->company_id,
                            'leadspeek_api_id' => $campaign->leadspeek_api_id,
                            'lp_limit_leads' => $campaign->lp_limit_leads,
                            'leadspeek_locator_keyword' => $campaign->leadspeek_locator_keyword,
                            'leadspeek_locator_state' => $campaign->leadspeek_locator_state,
                            'leadspeek_locator_city' => $campaign->leadspeek_locator_city,
                            'leadspeek_locator_zip' => $campaign->leadspeek_locator_zip,
                            'company_root_id' => $campaign->company_root_id,
                        ]);
                    }
                }
                return response()->json([
                    'result' => 'success',
                    'msg' => 'task create list assigned',
                ]);
            }else {
                return response()->json([
                    'result' => 'success',
                    'msg' => 'no campaign Exist',
                ]);
            }
        }else{
            return response()->json([
                'result' => 'success',
                'msg' => 'list already created for today',
            ]);
        }
    }
    /** GET LEADS FROM BIGDBM */

    /** RENDERING TO GET LEADS FROM WEBHOOK TOWER DATA */
    public function renderingPixel(Request $request) {
        //$md5param = (isset($request->md5_email))?trim($request->md5_email):'';
        $label = (isset($request->label))?trim($request->label):'';
        $script = (isset($request->script))?trim($request->script):'';

        /** NEW METHOD TO ANTICIPATE ID PUT IT ON URL AS WEB URL */
        $queryParameters = $request->query();
        $labelValue = '';
        foreach ($queryParameters as $key => $value) {
            if (strpos($key, 'id') === 0) {
                $labelValue .= $value;
                unset($queryParameters[$key]);
            }
        }

        if ($labelValue !== '') {
            $queryParameters['label'] = $queryParameters['label'] . "&id=" . $labelValue;
        }

        // Construct the new URL with the modified query parameters
        $fullURL = $request->fullUrlWithQuery($queryParameters);
        /** NEW METHOD TO ANTICIPATE ID PUT IT ON URL AS WEB URL */

        $replaceURL = $request->url() . '?label=';
        //$fullURL = $request->fullUrl();
        $label = urldecode(str_replace(array($replaceURL,"&script=true"),array("",""),$fullURL));

        if ($label != "") {
            $data = explode("|",$label);
            $leadspeek_api_id = (isset($data[0]))?trim($data[0]):'';
            $keyword = (isset($data[1]))?trim($data[1]):'';

            /** BLOCK RENDERING IF TV as Keywored */
            if (trim(strtolower($keyword)) == "tv" || preg_match('/^[a-zA-Z]{2}$/', trim($keyword))) {
                return response()->json(array('result'=>'failed','msg'=>'bannedkeyword'));
                exit;die();
            }
            /** BLOCK RENDERING IF TV as Keywored */
            
            if ($leadspeek_api_id != "") {
                $campaign = LeadspeekUser::select('id')
                            ->where('leadspeek_api_id','=',$leadspeek_api_id)
                            ->where('active','=','T')
                            ->where('disabled','=','F')
                            ->where('active_user','=','T')
                            ->get();
                if (count($campaign) > 0) {
                    $webhookcode = 'abiant3a';
                    $_webhookcode = config('services.application.webhookcode');
                    if (isset($_webhookcode) && trim($_webhookcode) != '') {
                        $webhookcode = $_webhookcode;
                    }
                    $apiURL = 'https://p.alocdn.com/c/' . $webhookcode . '/a/xtarget/p.gif?label=' . $leadspeek_api_id . '|' . urlencode($keyword);
                    if ($script == "true") {
                        return response()->json(array('result'=>'success','url'=>$apiURL));
                        exit;die();
                    }else{
                        $getimg = file_get_contents($apiURL);
                        return  Response($getimg,200)->header('Content-Type','image/gif');
                    }

                    /** SEND EMAIL FOR NOTIFICATION HIT */
                    $details = [
                        'params' => '',
                        'paramsUrl'  => 'Label : ' . $label,
                    ];
                    $attachement = array();

                    $from = [
                        'address' => 'newleads@leadspeek.com',
                        'name' => 'webhook',
                        'replyto' => 'newleads@leadspeek.com',
                    ];

                    //$this->send_email(array('harrison@uncommonreach.com'),'HIT FROM RENDERING PIXEL',$details,$attachement,'emails.webhookleadnotification',$from,'');


                    /** SEND EMAIL FOR NOTIFICATON HIT */



                }else{
                    return response()->json(array('result'=>'failed','msg'=>'notActiveCampaign'));
                    exit;die();
                }
            }
        }

    }
    /** RENDERING TO GET LEADS FROM WEBHOOK TOWER DATA */

    /** FOR GET LEADS WEBHOOK */
    public function getleadwebhook(Request $request) {
        set_time_limit(0);

        $params = $_GET;
        $md5email = (isset($request->md5email))?$request->md5email:'';
        $md5param = (isset($request->md5_email))?$request->md5_email:'';
        $label = $request->label;

        $replaceURL = $request->url() . '?label=';
        $fullURL = $request->fullUrl();
        //$label = urldecode(str_replace(array($replaceURL,"&script=true","&md5_email=",$md5param),array("","","",""),$fullURL));

        /** PARSE LABEL FOR GET ID, KEYWORD,etc */

        /** LABEL PATTERN campaignID|keyword **/
        $data = explode("|",$label);
        $leadspeek_api_id = (isset($data[0]))?trim($data[0]):'';
        $keyword = (isset($data[1]))?trim($data[1]):'';
        $param = [];
        foreach ($params as $key => $value) {
            if ($key != "label" && $key != "md5_email") {
                //$param = $param . '&'. $key . '=' . $value;
                $param[$key] = $value;
            }
        }

        $finalparam = "";
        if (count($param) > 0) {
            $finalparam = '&' . http_build_query($param);
        }
        $keyword = $keyword . $finalparam;
        $keyword = preg_replace("/[^a-zA-Z0-9\-._~:\/?#\[\]@!$&'()*+,;= ]/", "",$keyword);
        /** LABEL PATTERN campaignID|keyword **/

        /** PARSE LABEL FOR GET ID, KEYWORD,etc */

        if ($leadspeek_api_id != "") {
            /** CHECK AGAIN IF THE LEADSPEEK API ID ACTIVE */
            $campaign = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.require_email','leadspeek_users.leadspeek_type','leadspeek_users.location_target','leadspeek_users.leadspeek_locator_zip','leadspeek_users.leadspeek_locator_state','leadspeek_users.leadspeek_locator_state_simplifi','leadspeek_users.leadspeek_locator_city','leadspeek_users.leadspeek_locator_city_simplifi','leadspeek_users.national_targeting','leadspeek_users.company_id','users.company_id as clientcompany_id','users.company_root_id as clientcompany_root_id','leadspeek_users.paymentterm')
                            ->join('users','leadspeek_users.user_id','=','users.id')
                            ->where('leadspeek_users.leadspeek_api_id','=',$leadspeek_api_id)
                            ->where('leadspeek_users.active','=','T')
                            ->where('leadspeek_users.disabled','=','F')
                            ->where('leadspeek_users.active_user','=','T')
                            ->get();
            /** CHECK AGAIN IF THE LEADSPEEK API ID ACTIVE */
            if (count($campaign) > 0) {
                $loctarget = $campaign[0]['location_target'];
                $leadspeektype = $campaign[0]['leadspeek_type'];
                $nationaltargeting = $campaign[0]['national_targeting'];
                $loczip = "";
                $locstate = "";
                $locstatesifi = "";
                $loccity = "";
                $loccitysifi = "";
                $compleadID = (isset($campaign[0]['company_id']) && trim($campaign[0]['company_id']) != "")?trim($campaign[0]['company_id']):"";
                $clientCompanyID = (isset($campaign[0]['clientcompany_id']) && trim($campaign[0]['clientcompany_id']) != "")?trim($campaign[0]['clientcompany_id']):"";
                $clientCompanyRootId = (isset($campaign[0]['clientcompany_root_id']) && trim($campaign[0]['clientcompany_root_id']) != "")?trim($campaign[0]['clientcompany_root_id']):"";

                if ($leadspeektype == "locator") {
                    $loctarget = "Lock";
                    $loczip = (isset($campaign[0]['leadspeek_locator_zip']) && trim($campaign[0]['leadspeek_locator_zip']) != "")?trim($campaign[0]['leadspeek_locator_zip']):"";
                    $locstate = (isset($campaign[0]['leadspeek_locator_state']) && trim($campaign[0]['leadspeek_locator_state']) != "")?trim($campaign[0]['leadspeek_locator_state']):"";
                    $locstatesifi = (isset($campaign[0]['leadspeek_locator_state_simplifi']) && trim($campaign[0]['leadspeek_locator_state_simplifi']) != "")?trim($campaign[0]['leadspeek_locator_state_simplifi']):"";
                    //$loccity =  (isset($campaign[0]['leadspeek_locator_city']) && trim($campaign[0]['leadspeek_locator_city']) != "")?trim($campaign[0]['leadspeek_locator_city']):"";
                    $loccity = "";
                    //$loccitysifi =  (isset($campaign[0]['leadspeek_locator_city_simplifi']) && trim($campaign[0]['leadspeek_locator_city_simplifi']) != "")?trim($campaign[0]['leadspeek_locator_city_simplifi']):"";
                    $loccitysifi = "";
                }else{
                    $loctarget = "Focus";
                }

                $dataMatch = $this->getDataMatch($md5param,$leadspeek_api_id,$data,$keyword,$loctarget,$loczip,$locstate,$locstatesifi,$loccity,$loccitysifi,$nationaltargeting,$leadspeektype,$compleadID,$clientCompanyID,$clientCompanyRootId);
                if (is_array($dataMatch) || is_object($dataMatch)) {
                    if (count($dataMatch) > 0) {
                        if (($campaign[0]['require_email'] == 'T' && trim($dataMatch[0]['Email']) != '') || ($campaign[0]['require_email'] == 'F')) {
                            if (count($dataMatch) > 0) {
                                // /** REPORT ANALYTIC */
                                //     $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'serveclient');
                                // /** REPORT ANALYTIC */

                                $matches = json_decode(json_encode($dataMatch), false);
                                $this->processDataMatch($leadspeek_api_id,$matches,$data,$campaign[0]['paymentterm']);
                            }
                        }
                    }else{
                        /** REPORT ANALYTIC NOT SERVE AND BIGBDMREMAININGLEADS IF LEADSPEEK_TYPE ENHANCE */
                            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'notserve');
                            if($leadspeektype == 'enhance') {
                                $bigbdmremainingleads = BigDBMLeads::where('leadspeek_api_id', $leadspeek_api_id)
                                                                   ->whereDate('created_at', date('Y-m-d'))
                                                                   ->count() - 1;
                                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'bigbdmremainingleads',$bigbdmremainingleads);
                            }
                        /** REPORT ANALYTIC NOT SERVE AND BIGBDMREMAININGLEADS IF LEADSPEEK_TYPE ENHANCE */
                    }
                }else{
                   /** REPORT ANALYTIC NOT SERVE AND BIGBDMREMAININGLEADS IF LEADSPEEK_TYPE ENHANCE */
                        $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'notserve');
                        if($leadspeektype == 'enhance') {
                            $bigbdmremainingleads = BigDBMLeads::where('leadspeek_api_id', $leadspeek_api_id)
                                                               ->whereDate('created_at', date('Y-m-d'))
                                                               ->count() - 1;
                            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'bigbdmremainingleads',$bigbdmremainingleads);
                        }
                   /** REPORT ANALYTIC NOT SERVE AND BIGBDMREMAININGLEADS IF LEADSPEEK_TYPE ENHANCE */
                }

                /** REPORT ANALYTIC */
                    $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'pixelfire');
                /** REPORT ANALYTIC */

                /** COUNT WEBHOOK BEEN FIRE FOR SPECIFIC LEADSPEEK ID */
                    $updatewebhook = LeadspeekUser::find($campaign[0]['id']);
                    $updatewebhook->webhookfire =  $updatewebhook->webhookfire + 1;
                    $updatewebhook->save();
                /** COUNT WEBHOOK BEEN FIRE FOR SPECIFIC LEADSPEEK ID */
            }
        }

        $details = [
            'params' => $keyword . '==>' . $leadspeek_api_id,
            'paramsUrl'  => json_encode($params) . '<br>Label : ' . $label . '<br>MD5 Email : ' . $md5param . '<br>Full URL : ' . $fullURL,
        ];
        $attachement = array();

        $from = [
            'address' => 'newleads@leadspeek.com',
            'name' => 'webhook',
            'replyto' => 'newleads@leadspeek.com',
        ];

        //$this->send_email(array('harrison@uncommonreach.com'),'SANBOX-GET WEBHOOK FROM TOWER DATA',$details,$attachement,'emails.webhookleadnotification',$from,'');

    }
    /** FOR GET LEADS WEBHOOK */

    private function processDataMatch($leadspeek_api_id,$matches,$data,$paymentTerm = "") {
        date_default_timezone_set('America/Chicago');

        /** LOCK PROCESS FOR PREPAID UNTIL DONE FIRST */
        if (trim($paymentTerm) != "" && trim($paymentTerm) == "Prepaid") {
            Log::info("START PREPAID LOCK");
            while (!$this->acquireLock('initPrepaidStart')) {
                Log::info("Initial Prepaid Processing. Waiting to acquire lock.");
                sleep(1); // Wait before trying again
            }
        }
        /** LOCK PROCESS FOR PREPAID UNTIL DONE FIRST */

        $clientList = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.url_code','leadspeek_users.company_id as clientowner','leadspeek_users.report_type','leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','leadspeek_users.leadspeek_api_id','leadspeek_users.active','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','leadspeek_users.gtminstalled'
        ,'leadspeek_users.leadspeek_locator_state','leadspeek_users.leadspeek_locator_require','leadspeek_users.leadspeek_locator_zip','leadspeek_users.leads_amount_notification','leadspeek_users.total_leads','leadspeek_users.ongoing_leads','leadspeek_users.last_lead_added','leadspeek_users.spreadsheet_id','leadspeek_users.filename','leadspeek_users.report_frequency_id','leadspeek_users.lp_max_lead_month','leadspeek_users.lp_min_cost_month','leadspeek_users.cost_perlead'
        ,'users.customer_payment_id','users.customer_card_id','users.email','users.company_parent','users.company_root_id','leadspeek_users.paymentterm','leadspeek_users.leadspeek_type','leadspeek_users.lp_enddate','leadspeek_users.platformfee','leadspeek_users.hide_phone','leadspeek_users.campaign_name','leadspeek_users.campaign_enddate','leadspeek_users.phoneenabled','leadspeek_users.homeaddressenabled'
        ,'leadspeek_users.lp_limit_leads','leadspeek_users.lp_limit_freq','leadspeek_users.lp_limit_startdate','leadspeek_users.report_frequency','leadspeek_users.report_frequency_unit','leadspeek_users.last_lead_check','leadspeek_users.start_billing_date','users.name','leadspeek_users.user_id','companies.id as company_id','companies.company_name'
        ,'leadspeek_users.created_at','leadspeek_users.embedded_lastreminder','leadspeek_users.trysera',
        'leadspeek_users.ghl_tags','leadspeek_users.ghl_is_active','leadspeek_users.sendgrid_is_active as sendgrid_is_active', 'leadspeek_users.sendgrid_action as sendgrid_action','leadspeek_users.sendgrid_list as sendgrid_list','leadspeek_users.topupoptions','leadspeek_users.leadsbuy','leadspeek_users.stopcontinual','leadspeek_users.continual_buy_options')
        ->selectRaw('TIMESTAMPDIFF(MINUTE,leadspeek_users.last_lead_check,NOW()) as minutesdiff')
        ->selectRaw('TIMESTAMPDIFF(HOUR,leadspeek_users.last_lead_check,NOW()) as hoursdiff')
                        ->join('users','leadspeek_users.user_id','=','users.id')
                        ->join('companies','users.company_id','=','companies.id')
                        //->leftjoin('companies_integration_settings','users.company_id','=','companies_integration_settings.company_id')
                        ->where('leadspeek_users.active','=','T')
                        ->where('leadspeek_users.active_user','=','T')
                        ->where('users.user_type','=','client')
                        ->where('users.active','=','T')
                        ->where('leadspeek_users.leadspeek_api_id','=',$leadspeek_api_id)
                        ->get();

        foreach($clientList as $cl) {

            $clientEmail = explode(PHP_EOL, $cl['report_sent_to']);
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
            //$clientLimitEndDate = ($cl['lp_enddate'] == null || $cl['lp_enddate'] == '0000-00-00 00:00:00')?'':$cl['lp_enddate'];
            $clientLimitEndDate = ($cl['campaign_enddate'] == null || $cl['campaign_enddate'] == '0000-00-00 00:00:00' || trim($cl['campaign_enddate']) == '')?'':$cl['campaign_enddate'];
            $clientCostPerLead = $cl['cost_perlead'];
            $clientMinCostMonth = $cl['lp_min_cost_month'];
            $custStripeID = $cl['customer_payment_id'];
            $custStripeCardID = $cl['customer_card_id'];
            $custEmail = $cl['email'];
            $phoneenabled = $cl['phoneenabled'];
            $homeaddressenabled = $cl['homeaddressenabled'];

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

            $companyRootID = (isset($cl['company_root_id']))?$cl['company_root_id']:'';
            $rootFeeCost = 0;
            $ori_rootFeeCost = 0;
            $platform_price_lead = 0;
            $ori_platform_price_lead = 0;

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
                }else if ($cl['leadspeek_type'] == "enhance") {
                    $rootcostagency = []; 
                    if(!isset($platformMargin->enhance)) {
                        $rootcostagency = $this->getcompanysetting($cl['company_root_id'],'rootcostagency');
                    }

                    $clientTypeLead = $this->getClientCapType($cl['company_root_id']);
                    if($clientTypeLead['type'] == 'clientcapleadpercentage') {
                        $rootcostagency = $this->getcompanysetting($cl['company_root_id'],'rootcostagency');
                        $costagency = $this->getcompanysetting($cl['company_parent'], 'costagency');
                        
                        if($cl['paymentterm'] == 'Weekly') {
                            $m_LeadspeekCostperlead = ($cl['cost_perlead'] * $clientTypeLead['value']) / 100;
                            $rootCostPerLeadMin = ($costagency->enhance->Weekly->EnhanceCostperlead > $rootcostagency->enhance->Weekly->EnhanceCostperlead) ? $costagency->enhance->Weekly->EnhanceCostperlead : $rootcostagency->enhance->Weekly->EnhanceCostperlead;
                            $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                            // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                            if(($cl['cost_perlead'] == 0) || ($cl['cost_perlead'] <= $rootCostPerLeadMax && $cl['cost_perlead'] >= $rootCostPerLeadMin)) { 
                                $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                            }
                            // jika lebih dari $rootCostPerLeadMax, maka dinamis
                            else if($cl['cost_perlead'] > $rootCostPerLeadMax) {
                                $platform_LeadspeekCostperlead = $m_LeadspeekCostperlead;
                            }
                            else {
                                $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                            }
                        } else if($cl['paymentterm'] == 'Monthly') {
                            $m_LeadspeekCostperlead = ($cl['cost_perlead'] * $clientTypeLead['value']) / 100;
                            $rootCostPerLeadMin = ($costagency->enhance->Monthly->EnhanceCostperlead > $rootcostagency->enhance->Monthly->EnhanceCostperlead) ? $costagency->enhance->Monthly->EnhanceCostperlead : $rootcostagency->enhance->Monthly->EnhanceCostperlead;
                            $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                            // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                            if(($cl['cost_perlead'] == 0) || ($cl['cost_perlead'] <= $rootCostPerLeadMax && $cl['cost_perlead'] >= $rootCostPerLeadMin)) { 
                                $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                            }
                            // jika lebih dari $rootCostPerLeadMax, maka dinamis
                            else if($cl['cost_perlead'] > $rootCostPerLeadMax) {
                                $platform_LeadspeekCostperlead = $m_LeadspeekCostperlead;
                            }
                            else {
                                $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                            }
                        } else if($cl['paymentterm'] == 'One Time') {
                            $m_LeadspeekCostperlead = ($cl['cost_perlead'] * $clientTypeLead['value']) / 100;
                            $rootCostPerLeadMin = ($costagency->enhance->OneTime->EnhanceCostperlead > $rootcostagency->enhance->OneTime->EnhanceCostperlead) ? $costagency->enhance->OneTime->EnhanceCostperlead : $rootcostagency->enhance->OneTime->EnhanceCostperlead;
                            $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                            // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                            if(($cl['cost_perlead'] == 0) || ($cl['cost_perlead'] <= $rootCostPerLeadMax && $cl['cost_perlead'] >= $rootCostPerLeadMin)) { 
                                $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                            }
                            // jika lebih dari $rootCostPerLeadMax, maka dinamis
                            else if($cl['cost_perlead'] > $rootCostPerLeadMax) {
                                $platform_LeadspeekCostperlead = $m_LeadspeekCostperlead;
                            }
                            else {
                                $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                            }
                        } else if($cl['paymentterm'] == 'Prepaid') {
                            $m_LeadspeekCostperlead = ($cl['cost_perlead'] * $clientTypeLead['value']) / 100;
                            $rootCostPerLeadMin = ($costagency->enhance->Prepaid->EnhanceCostperlead > $rootcostagency->enhance->Prepaid->EnhanceCostperlead) ? $costagency->enhance->Prepaid->EnhanceCostperlead : $rootcostagency->enhance->Prepaid->EnhanceCostperlead;
                            $rootCostPerLeadMax = ($rootCostPerLeadMin) / ($clientTypeLead['value'] / 100);

                            // jika cost_perlead 0 atau m_LeadspeekCostperlead berada di rentang $rootCostPerLeadMin ~ $rootCostPerLeadMax
                            if(($cl['cost_perlead'] == 0) || ($cl['cost_perlead'] <= $rootCostPerLeadMax && $cl['cost_perlead'] >= $rootCostPerLeadMin)) {
                                $platform_LeadspeekCostperlead = $rootCostPerLeadMin;
                            }
                            // jika lebih dari $rootCostPerLeadMax, maka dinamis
                            else if($cl['cost_perlead'] > $rootCostPerLeadMax) {
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

            $organizationid = $cl['leadspeek_organizationid'];
            $campaignsid = $cl['leadspeek_campaignsid'];
            $price_lead = ($cl['cost_perlead'] != '')?$cl['cost_perlead']:0;
            $platform_price_lead = $platform_LeadspeekCostperlead;
            $ori_platform_price_lead = $platform_price_lead;

            $clientHidePhone = $cl['hide_phone'];

            /** GET ROOT FEE PER LEADS FROM SUPER ROOT */
            $masterRootFee = $this->getcompanysetting($companyRootID,'rootfee');
            if ($masterRootFee != '') {
                if ($cl['leadspeek_type'] == "local") {
                    $rootFeeCost = (isset($masterRootFee->feesiteid))?$masterRootFee->feesiteid:0;
                }else if ($cl['leadspeek_type'] == "locator") {
                    $rootFeeCost = (isset($masterRootFee->feesearchid))?$masterRootFee->feesearchid:0;
                }else if ($cl['leadspeek_type'] == "enhance") {
                    $rootFeeCost = (isset($masterRootFee->feeenhance))?$masterRootFee->feeenhance:0;
                }

                $ori_rootFeeCost = $rootFeeCost;
            }
            /** GET ROOT FEE PER LEADS FROM SUPER ROOT */

            $attachementlist = array();
            $attachementlink = array();
            $attachment = array();

            /** CHECK IF THERE END DATE ON WEEKLY OR MONTHLY PAYMENT TERM */
            if ($cl['leadspeek_type'] == "local" && $clientLimitEndDate == '') {
                //$clientLimitEndDate = $cl['campaign_enddate'];
                $oneYearLater = date('Y-m-d', strtotime('+1 year', strtotime(date('Y-m-d'))));
                $clientLimitEndDate = ($cl['lp_enddate'] == null || $cl['lp_enddate'] == '0000-00-00 00:00:00' || $cl['lp_enddate'] == '')? $oneYearLater . ' 00:00:00':$cl['lp_enddate'];
            }

            if ($clientPaymentTerm != 'One Time' && $clientPaymentTerm != 'Prepaid' && $clientLimitEndDate != '') {
                $EndDate = date('YmdHis',strtotime($clientLimitEndDate));
                if (date('YmdHis') > $EndDate) {
                    /** GET COMPANY NAME AND CUSTOM ID */
                    $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                    /** GET COMPANY NAME AND CUSTOM ID */

                    /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                    /*$pauseApiURL =  config('services.trysera.endpoint') . 'subclients/' . $cl['leadspeek_api_id'];
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
                    */
                    /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */

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
                            //$nextBillingDate = date('Ymd');
                            $nextBillingDate = date("YmdHis", strtotime("-1 days"));

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
                                    'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                                ];

                                $from = [
                                    'address' => 'noreply@sitesettingsapi.com',
                                    'name' => 'support',
                                    'replyto' => 'noreply@sitesettingsapi.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - Webhook-ProcessDataMatch - L512) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            /** SEND EMAIL TO ME */
                            continue;
                        }
                    }else if ($cl['leadspeek_type'] == "local") {
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
                        //$nextBillingDate = date('Ymd');
                        $nextBillingDate = date("YmdHis", strtotime("-1 days"));

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


                    // if (date('Ymd') >= $LastBillDate) {
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
                /** CHECK IF LEADS EMPTY */
                    $dataContinualProgressIsNotDoneExists = Topup::where('leadspeek_api_id', $_leadspeek_api_id)
                                    ->whereIn('topup_status', ['progress', 'queue'])
                                    ->exists();

                    if(!$dataContinualProgressIsNotDoneExists) {

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

                        /** UPDATE STOP CONTINUAL */
                        $this->stop_continual_topup($_lp_user_id);
                        /** UPDATE STOP CONTINUAL */
                        
                        /** SEND EMAIL TO ME */
                        $details = [
                            'errormsg'  => 'PREPAID Stopped No TOP UP EXIST (L716) Leadspeek ID :' . $_leadspeek_api_id. '<br/>',
                        ];

                        $from = [
                            'address' => 'noreply@sitesettingsapi.com',
                            'name' => 'support',
                            'replyto' => 'noreply@sitesettingsapi.com',
                        ];
                        $this->send_email(array('harrison@uncommonreach.com'),'PREPAID STOPPED #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                    /** SEND EMAIL TO ME */
                        continue;
                    }
                /** CHECK IF LEADS EMPTY */
            }
            /** CHECK IF THERE END DATE ON WEEKLY OR MONTHLY PAYMENT TERM */

            $leadcount = 0;

            /** PROCESS MATCHED DATA */
            if (count($matches) > 0){
                $_last_lead_check = now();
                $_last_lead_added = now();

                /** PROCESSING BASED ON REPORT TYPE */
                if($clientReportType == 'GoogleSheet') {
                    $content = array();

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

                        if ($TotalLimitLeads == $clientLimitLeads) {
                            continue;
                        }

                    }
                    /** CHECK FOR LIMIT LEADS */

                    /** START GET THE DATA */
                    foreach($matches as $row) {
                        $_Phone = $row->Phone;
                        $_Phone2 = $row->Phone2;
                        $_Address1 = $row->Address1;
                        $_Address2 = $row->Address2;
                        $_City = $row->City;
                        $_State = $row->State;
                        $_Zipcode = $row->Zipcode;

                        if ($phoneenabled == 'F') {
                            $_Phone = '';
                            $_Phone2 = '';
                        }
                        if ($homeaddressenabled == 'F') {
                            $_Address1 = '';
                            $_Address2 = '';
                            $_City = '';
                            $_State = '';
                            $_Zipcode = '';
                        }

                        $row->Keyword = $this->replaceExclamationWithAsterisk($row->Keyword);
                        //$content[] = array($row->ID,date('m/d/Y h:i:s A',strtotime($row->ClickDate)),ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Email,$row->Email2,$row->Phone,$row->Phone2,$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,$row->Keyword);
                        $content[] = array($row->ID,date('m/d/Y h:i:s A',strtotime($row->ClickDate)),ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Email,$row->Email2,$_Phone,$_Phone2,$_Address1,$_Address2,$_City,$_State,$_Zipcode,$row->Keyword);
                        if ($clientPaymentTerm == 'Prepaid') {
                            $progressTopup = Topup::where('leadspeek_api_id', $_leadspeek_api_id)
                                                    ->where('topup_status', 'progress')
                                                    ->first();
                            $_topup_id = '0';
                            if ($progressTopup) {
                                $price_lead = (isset($progressTopup->cost_perlead))?$progressTopup->cost_perlead:$price_lead;
                                $platform_price_lead = (isset($progressTopup->platform_price))?$progressTopup->platform_price:$platform_price_lead;
                                $rootFeeCost = (isset($progressTopup->root_price))?$progressTopup->root_price:$rootFeeCost;
                                $_topup_id = (isset($progressTopup->id))?$progressTopup->id:'0';
                            }
                            Log::info("insertDB topupID : " . $_topup_id . " campaignID:" . $_leadspeek_api_id);
                            $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->Email2,$row->OriginalMD5,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,$row->Phone2,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,$row->LeadFrom,'T',$price_lead,$platform_price_lead,$row->PersonID,$row->Keyword,$row->Description,$rootFeeCost,$_topup_id);
                        }else{
                            $newleads = $this->insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$row->ID,$row->Email,$row->Email2,$row->OriginalMD5,$row->IP,$row->Source,date('Y-m-d H:i:s', strtotime($row->OptInDate)),date('Y-m-d H:i:s', strtotime($row->ClickDate)),$row->Referer,$row->Phone,$row->Phone2,ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Address1,$row->Address2,$row->City,$row->State,$row->Zipcode,$row->LeadFrom,'T',$price_lead,$platform_price_lead,$row->PersonID,$row->Keyword,$row->Description,$rootFeeCost);
                        }
                        $leadcount++;

                        /** REPORT ANALYTIC SERVE AND BIGBDMREMAININGLEADS IF LEADSPEEK_TYPE ENHANCE */
                        $this->UpsertReportAnalytics($_leadspeek_api_id,$cl['leadspeek_type'],'serveclient');
                        if($cl['leadspeek_type'] == 'enhance') {
                            $bigbdmremainingleads = BigDBMLeads::where('leadspeek_api_id', $leadspeek_api_id)
                                                               ->whereDate('created_at', date('Y-m-d'))
                                                               ->count() - 1;
                            $this->UpsertReportAnalytics($leadspeek_api_id,$cl['leadspeek_type'],'bigbdmremainingleads',$bigbdmremainingleads);
                        }
                        /** REPORT ANALYTIC SERVE AND BIGBDMREMAININGLEADS IF LEADSPEEK_TYPE ENHANCE */
                    }
                    /** START GET THE DATA */

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

                    /** WRITE THE DATA TO GOOGLE SPREADSHEET */
                    if (count($content) > 0 && trim($clientSpreadSheetID) != '') {
                    //if (false) {
                        /** DELAY EXECUTE FOR RATE LIMIT 60 req / second */
                        usleep(16666); // sleep for 1,000,000 microseconds / rateLimit

                        try {
                            $client = new GoogleSheet($clientSpreadSheetID,$cl['clientowner'],'3',true);
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
                        }catch(Exception $e) {
                            Log::warning("Google Spreadsheet Initial Failed (L811) ErrMsg:" . $e->getMessage() . " CampaignID:" . $_leadspeek_api_id . ' Campaign Name:' . $campaignName . ' Email:' . $custEmail . ' CompanyName:' .  $companyNameOri);
                        }

                    }
                    /** WRITE THE DATA TO GOOGLE SPREADSHEET */

                }
                /** PROCESSING BASED ON REPORT TYPE */

                /** CODE THAT WILL RUN WHEN LEADS INSERT INTO CONNECTED SENDGRID ACCOUNT */

                $_leadspeek_api_id = $cl['leadspeek_api_id'];
                $_sendgrid_action=$cl['sendgrid_action'];
                $_sendgrid_list=$cl['sendgrid_list'];
                $_sendgrid_is_active=$cl['sendgrid_is_active'];

                /** GET THE API KEY INTEGRATION SETTING */
                $_sendgrid_api_key = "";
                $_enable_sendgrid = "";
                $chkSendGrid = IntegrationSettings::select('api_key','enable_sendgrid')
                                        ->where('company_id','=',$_company_id)
                                        ->where('integration_slug','=','sendgrid')
                                        ->get();
                if (count($chkSendGrid) > 0) {
                    $_sendgrid_api_key = $chkSendGrid[0]['api_key'];
                    $_enable_sendgrid = $chkSendGrid[0]['enable_sendgrid'];
                }
                /** GET THE API KEY INTEGRATION SETTING */

                //Log::warning("sendgrid_api_key : " . $_sendgrid_api_key);
                if(!empty($_sendgrid_api_key)){
                    if($_enable_sendgrid == '1'){
                        if($_sendgrid_is_active == '1'){
                            $arraysendgrid = explode(',', $_sendgrid_action);
                            foreach($arraysendgrid as $value){
                                if($value == 'add-to-contact')
                                {
                                    foreach($matches as $row2) {
                                        $_keyword = "";
                                        $_url = "";

                                        $row2->Keyword = $this->replaceExclamationWithAsterisk($row2->Keyword);

                                        if ($cl['leadspeek_type'] == "local") {
                                            $_url = $row2->Keyword;
                                        }else if ($cl['leadspeek_type'] == "locator") {
                                            $_keyword = $row2->Keyword;
                                        }
                                        try {
                                            $this->sendContactToSendgrid($_sendgrid_api_key,$row2->Email, ucfirst(strtolower($row2->FirstName)),ucfirst(strtolower($row2->LastName)),$row2->Address1,$row2->Address2,$row2->City,$row2->State,$row2->Zipcode,$row2->Phone,$row2->Email2,$_keyword,$_url);
                                        }catch(Exception $e) {
                                                Log::warning("Send Contact to SendGrid (L860) ErrMsg:" . $e->getMessage() . " CampaignID:" . $_leadspeek_api_id . ' Campaign Name:' . $campaignName . ' Email:' . $custEmail . ' CompanyName:' .  $companyNameOri);
                                        }
                                    }
                                }
                                    if($value == 'add-to-list')
                                    {
                                        $arraysendgridlist = explode(',', $_sendgrid_list);
                                        foreach($arraysendgridlist as $list){
                                            if (!empty($list)) {
                                                foreach($matches as $row2) {
                                                    $_keyword = "";
                                                    $_url = "";

                                                    $row2->Keyword = $this->replaceExclamationWithAsterisk($row2->Keyword);
                                                    if ($cl['leadspeek_type'] == "local") {
                                                        $_url = $row2->Keyword;
                                                    }else if ($cl['leadspeek_type'] == "locator") {
                                                        $_keyword = $row2->Keyword;
                                                    }
                                                    try {
                                                            $this->sendContactToSendgridList($_sendgrid_api_key,$list,$row2->Email,ucfirst(strtolower($row2->FirstName)),ucfirst(strtolower($row2->LastName)),$row2->Address1,$row2->Address2,$row2->City,$row2->State,$row2->Zipcode,$row2->Phone,$row2->Email2,$_keyword,$_url);
                                                    }catch(Exception $e) {
                                                        Log::warning("Send Contact to SendGrid (L878) ErrMsg:" . $e->getMessage() . " CampaignID:" . $_leadspeek_api_id . ' Campaign Name:' . $campaignName . ' Email:' . $custEmail . ' CompanyName:' .  $companyNameOri);
                                                    }
                                                }
                                            }
                                        }

                                    }
                                }
                        }
                    }
                }

                /** CODE THAT WILL RUN WHEN LEADS INSERT INTO CONNECTED SENDGRID ACCOUNT */
                $_ghl_api_key = "";
                $_enable_ghl = "";
                $chkGhl = IntegrationSettings::select('api_key','enable_sendgrid')
                                        ->where('company_id','=',$_company_id)
                                        ->where('integration_slug','=','gohighlevel')
                                        ->get();
                if (count($chkGhl) > 0) {
                    $_ghl_api_key = $chkGhl[0]['api_key'];
                    $_enable_ghl = $chkGhl[0]['enable_sendgrid'];
                }

                if ($_ghl_api_key != "" && $_enable_ghl == "1" && $cl['ghl_is_active'] == "1") {
                    /** CREATE GHL CONTACT AND ATTACHED THE TAG IF EXIST */

                    /** CHECK IF TAG EXIST ON GHL */
                    $tags = [];
                    $saveTags = [];
                    $removeTags = [];

                    $ghltags = json_decode($cl['ghl_tags']);
                    if (isset($ghltags) && count($ghltags) > 0) {

                        foreach($ghltags as $item) {
                            $partItem = explode('|',$item);
                            /** CHECK IF TAG STILL EXIST ON GOHIGHLEVEL */
                            $chkTag = $this->ghl_getTag($partItem[0],$_ghl_api_key);
                            /** CHECK IF TAG STILL EXIST ON GOHIGHLEVEL */
                            if ($chkTag != '' && $chkTag != 'sys_removed') {
                                $tags[] = $partItem[1];
                                $saveTags[] = $partItem[0] . '|' . $partItem[1];
                            }else if ($chkTag == 'sys_removed') {
                                $removeTags[] = $partItem[1];
                            }
                        }

                    }

                    /** IF THERE ARE REMOVE TAG FROM SYSTEM THEN UPDATE/SYNC THE TAG ON THE CAMPAIGN */
                    if (count($removeTags) > 0) {
                        /** DISABLED BECAUSE SOMETIMES IT FAILED TO GET NOT BECAUSE IT REMOVED */
                        // $update = LeadspeekUser::where('id',$cl['id'])
                        //                         ->update(['ghl_tags' => json_encode($saveTags),'ghl_remove_tags' => $removeTags]);
                        /** DISABLED BECAUSE SOMETIMES IT FAILED TO GET NOT BECAUSE IT REMOVED */
                    }
                    /** IF THERE ARE REMOVE TAG FROM SYSTEM THEN UPDATE/SYNC THE TAG ON THE CAMPAIGN */

                    /** CHECK IF TAG EXIST ON GHL */

                    /** INSERT INTO GHL CONTACT */
                    foreach($matches as $row) {
                        $_Phone = $row->Phone;
                        $_Phone2 = $row->Phone2;
                        $_Address1 = $row->Address1;
                        $_Address2 = $row->Address2;
                        $_City = $row->City;
                        $_State = $row->State;
                        $_Zipcode = $row->Zipcode;

                        if ($phoneenabled == 'F') {
                            $_Phone = '';
                            $_Phone2 = '';
                        }
                        if ($homeaddressenabled == 'F') {
                            $_Address1 = '';
                            $_Address2 = '';
                            $_City = '';
                            $_State = '';
                            $_Zipcode = '';
                        }
                        
                        try {
                            //log::warning('Campaign: #' . $_leadspeek_api_id . ' - GHL Create Contact (L903)');
                            $errMsg = " CampaignID:" . $_leadspeek_api_id . ' Campaign Name:' . $campaignName . ' Email:' . $custEmail . ' CompanyName:' .  $companyNameOri;
                            $row->Keyword = $this->replaceExclamationWithAsterisk($row->Keyword);
                            $this->ghl_createContact($_company_id,$_ghl_api_key,$row->ID,date('Y-m-d',strtotime($row->ClickDate)),ucfirst(strtolower($row->FirstName)),ucfirst(strtolower($row->LastName)),$row->Email,$row->Email2,$_Phone,$_Phone2,$_Address1,$_Address2,$_City,$_State,$_Zipcode,$row->Keyword,$tags, $_leadspeek_api_id,$errMsg);
                        }catch(Exception $e) {
                            Log::warning("GHL Create Contact (L941) ErrMsg:" . $e->getMessage() . " CampaignID:" . $_leadspeek_api_id . ' Campaign Name:' . $campaignName . ' Email:' . $custEmail . ' CompanyName:' .  $companyNameOri);
                        }
                    }
                    /** INSERT INTO GHL CONTACT */

                    /** CREATE GHL CONTACT AND ATTACHED THE TAG IF EXIST */
                }
                /** CODE THAT WILL RUN WHEN LEADS INSERT INTO CONNECTED SENDGRID ACCOUNT */

                #KARTRA PUSHING START#######################################################################################

                $kartra = IntegrationSettings::select('api_key', 'enable_sendgrid', 'password')
                                ->where('company_id', '=', $_company_id)
                                ->where('integration_slug', '=', 'kartra')
                                ->where('enable_sendgrid', '=', '1')
                                ->first();

                if ($kartra) {
                    //Log::info("Level[0] Kartra settings found, proceeding with API key and password assignment.");
                    $this->api_key = $kartra->api_key;
                    $this->api_password = $kartra->password;
                
                    foreach ($matches as $row) {
                        $_Phone = $row->Phone;
                        $_Phone2 = $row->Phone2;
                        $_Address1 = $row->Address1;
                        $_Address2 = $row->Address2;
                        $_City = $row->City;
                        $_State = $row->State;
                        $_Zipcode = $row->Zipcode;

                        if ($phoneenabled == 'F') {
                            $_Phone = '';
                            $_Phone2 = '';
                        }
                        if ($homeaddressenabled == 'F') {
                            $_Address1 = '';
                            $_Address2 = '';
                            $_City = '';
                            $_State = '';
                            $_Zipcode = '';
                        }
                        
                        $_keyword = "";
                        $_url = "";
                        if ($cl['leadspeek_type'] == "local") {
                            $_url = $row->Keyword;
                        } else if ($cl['leadspeek_type'] == "locator") {
                            $_keyword = $row->Keyword;
                        }
                
                        try {
                            //Log::info("Level [1] Creating new Kartra lead for match ID: " . $row->ID . " with parameters: company_id: $_company_id, cl_id: " . $cl['id'] . ", ClickDate: " . date('Y-m-d', strtotime($row->ClickDate)) . ", FirstName: " . ucfirst(strtolower($row->FirstName)) . ", LastName: " . ucfirst(strtolower($row->LastName)) . ", Email: " . $row->Email . ", Phone: $_Phone, Address1: $_Address1, City: $_City, State: $_State, Zipcode: $_Zipcode, URL: $_url, Phone2: $_Phone2, Email2: " . $row->Email2 . ", Address2: $_Address2, Keyword: $_keyword");
                            
                            $KartraCreate = $this->NewKartraLead(
                                $_company_id,
                                $cl['id'],
                                date('Y-m-d', strtotime($row->ClickDate)),
                                ucfirst(strtolower($row->FirstName)),
                                ucfirst(strtolower($row->LastName)),
                                $row->Email,
                                $_Phone,
                                $_Address1,
                                $_City,
                                $_State,
                                $_Zipcode,
                                $_url,
                                $_Phone2,
                                $row->Email2,
                                $_Address2,
                                $_keyword
                            );
                
                            //Log::info("Level [2] New Kartra lead created successfully for match ID: " . $row->ID);
                        } catch (Exception $e) {
                            Log::error("Error creating new Kartra lead for match ID: " . $row->ID . " - " . $e->getMessage());
                        }
                    }
                } else {
                    //Log::warning("Level [0-1] No Kartra settings found for company_id: " . $_company_id);
                }
                
                ########################################################################################KARTRA PUSHING END#

                // ZAPIER PUSHING START 
                $chkZap = IntegrationSettings::select('api_key', 'enable_sendgrid')
                ->where('company_id', '=', $_company_id)
                ->where('integration_slug', 'zapier')
                ->where('enable_sendgrid', 1)
                ->first();

                $campaign = LeadspeekUser::select('leadspeek_users.zap_tags', 'leadspeek_users.zap_is_active', 'leadspeek_users.zap_webhook')
                ->where('leadspeek_api_id', '=', $_leadspeek_api_id)
                ->where('zap_is_active', 1)
                ->first();

                if ($chkZap) {
                    if ($chkZap->api_key != '' && $chkZap->enable_sendgrid == 1) {
                        $campaign_type = $cl['leadspeek_type'] == 'local' ? 'Site ID' : 'Search ID' ;
                        $custom_side_menu = $this->getcompanysetting($cl['company_parent'], 'customsidebarleadmenu');
                        if (!empty($custom_side_menu)) {
                            $campaign_type = ($cl['leadspeek_type'] == 'local') ? $custom_side_menu->local->name : $custom_side_menu->locator->name ;
                        }
                        $webhook = ($campaign && $campaign->zap_webhook != '') ? $campaign->zap_webhook : $chkZap->api_key;
                        $tags = ($campaign && !empty($campaign->zap_tags)) ? json_decode($campaign->zap_tags) : '';
                        if ($campaign && $campaign->zap_is_active == 1) {
                            foreach ($matches as $row) {
                                $_Phone = $row->Phone;
                                $_Phone2 = $row->Phone2;
                                $_Address1 = $row->Address1;
                                $_Address2 = $row->Address2;
                                $_City = $row->City;
                                $_State = $row->State;
                                $_Zipcode = $row->Zipcode;
                                $_Email1 = $row->Email;
                                $_Email2 = $row->Email2;

                                if ($phoneenabled == 'F') {
                                    $_Phone = '';
                                    $_Phone2 = '';
                                }
                                if ($homeaddressenabled == 'F') {
                                    $_Address1 = '';
                                    $_Address2 = '';
                                    $_City = '';
                                    $_State = '';
                                    $_Zipcode = '';
                                }

                                $keyword = '';
                                $url = '';
                                if ($cl['leadspeek_type'] === 'local') {
                                    $url = $row->Keyword;
                                } elseif ($cl['leadspeek_type'] === 'locator') {
                                    $keyword = $row->Keyword;
                                }
                                
                                try {
                                    $send_to_zapier = $this->zap_sendrecord(
                                        $webhook,
                                        date('Y-m-d H:i:s', strtotime($row->ClickDate)),
                                        ucfirst(strtolower($row->FirstName)),
                                        ucfirst(strtolower($row->LastName)),
                                        $_Email1,
                                        $_Email2,
                                        $_Phone,
                                        $_Phone2,
                                        $_Address1,
                                        $_Address2,
                                        $_City,
                                        $_State,
                                        $_Zipcode,
                                        $keyword,
                                        $url,
                                        $tags,
                                        $_leadspeek_api_id,
                                        $campaign_type
                                    );
                                } catch (\Throwable $th) {
                                    Log::error("Error creating new Zapier lead for match ID: " . $row->ID . " - " . $th->getMessage());
                                }
                            }
                        }
                    }
                }
                // ZAPIER PUSHING END

                
                // /** UPDATE LEADS DATA AND DATE */
                //     $totalOngoing = $clientOngoingLeads + $leadcount;

                //     $lpupdate = LeadspeekUser::find($_lp_user_id);
                //     if ($_last_lead_check != '') {
                //         $lpupdate->last_lead_check = $_last_lead_check;
                //     }
                //     if ($_last_lead_added != '') {
                //         $lpupdate->last_lead_added = $_last_lead_added;
                //     }
                //     if ($leadcount != 0) {
                //         $lpupdate->total_leads = $clientTotalLeads + $leadcount;
                //         //$lpupdate->ongoing_leads = $leftLeadstoInvoice;
                //         $lpupdate->ongoing_leads = $totalOngoing;
                //     }

                //     $lpupdate->save();
                // /** UPDATE LEADS DATA AND DATE */

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

                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                            if ($organizationid != '' && $campaignsid != '') {
                                $camp = $this->startPause_campaign($organizationid,$campaignsid,'pause');
                                if ($camp == true) {
                                    $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                                    $updateLeadspeekUser->active = 'F';
                                    $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                                    $updateLeadspeekUser->save();
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
                                        $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - Webhook-ProcessDataMatch-L825) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                    /** SEND EMAIL TO ME */
                                }
                            }else if ($cl['leadspeek_type'] == "local") {
                                $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                                $updateLeadspeekUser->active = 'F';
                                $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                                $updateLeadspeekUser->save();
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */

                            /** SEND EMAIL NOTIFICATION */
                            $this->notificationStartStopLeads(array('harrison@uncommonreach.com'),'Paused',$company_name . ' #' . $_leadspeek_api_id . ' (Webhook)',$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                            /** SEND EMAIL NOTIFICATION */
                        }
                        /** IF TOTAL COUNT LEADS DAY / MONTH BIGGER and EQUAL FROM LIMIT LEADS */

                    }
                /** CHECK FOR LIMIT LEADS */

                /** CHECK IF THE PAYMENT IS ONE TERM AND SHOULD BE MAKE USER INACTIVE AND SENT INVOICE */
                if ($clientPaymentTerm == 'One Time' && $clientMaxperTerm > 0 && $clientMaxperTerm != '' && $clientLimitStartDate != '') {

                    $lockKey = 'onetime_process';

                    while (!$this->acquireLock($lockKey)) {
                        Log::info("Another OneTime process is running. Waiting to acquire lock.");
                        sleep(1); // Wait before trying again
                    }

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

                            /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */

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

                                        $updateUser = User::find($_user_id);
                                        $updateUser->lp_enddate = null;
                                        $updateUser->lp_limit_startdate = null;
                                        $updateUser->save();

                                        $clientStartBilling = date('Ymd',strtotime($clientLimitStartDate));
                                        $nextBillingDate = date('Ymd');

                                        /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                                        $this->notificationStartStopLeads($clientAdminNotify,'Stopped (One Time ' . date('m-d-Y',strtotime($clientLimitStartDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                                        /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
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
                                        $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - Webhook-ProcessDataMatch-902) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                    /** SEND EMAIL TO ME */
                                }
                            }else if ($cl['leadspeek_type'] == "local") {
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

                                /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                                $this->notificationStartStopLeads($clientAdminNotify,'Stopped (One Time ' . date('m-d-Y',strtotime($clientLimitStartDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$clientLimitLeads,$clientLimitFreq,$TotalLimitLeads,$cl['clientowner']);
                                /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                        }



                        /** CREATE INVOICE AND SENT IT */
                        //$invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxperTerm,$clientCostPerLead,$clientMinCostMonth,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail);
                        /** CREATE INVOICE AND SENT IT */


                    }
                    /** IF ONE TIME LIMIT FREQUENCY LESS THAN TOTAL LEADS SINCE START */
                    $this->releaseLock($lockKey);
                    sleep(1);
                }
                /** CHECK IF THE PAYMENT IS ONE TERM AND SHOULD BE MAKE USER INACTIVE AND SENT INVOICE */

                /** CHECK IF PREPAID */
                $lockKey = 'topup_process_lock';
                
                if ($clientPaymentTerm == 'Prepaid') {
                    while (!$this->acquireLock($lockKey)) {
                        Log::info("Another top-up process is running. Waiting to acquire lock.");
                        sleep(1); // Wait before trying again
                    }

                    // Step 1: update status to 'done' for topups with status 'progress' and remaining balance 0
                    $progressTopups = Topup::where('leadspeek_api_id','=', $_leadspeek_api_id)
                                        ->where('topup_status','=','progress')
                                        ->first();

                    $remainingBalance = Topup::where('id','=',$progressTopups->id)
                                            ->sum('total_leads') - DB::table('leadspeek_reports')
                                            ->where('topup_id','=',$progressTopups->id)
                                            ->where('leadspeek_api_id','=',$_leadspeek_api_id)
                                            ->count();  

                    // change status to done
                    if ($remainingBalance == 0) {
                        Log::info("Balance 0 update status done topupID :" . $progressTopups->id . " Remaining Balance :" . $remainingBalance);
                        Topup::where('id','=',$progressTopups->id)
                                ->update([
                                    'balance_leads' => $remainingBalance,
                                    'topup_status' => 'done'
                                ]);
                    }else{
                        Topup::where('id','=',$progressTopups->id)
                                    ->update(['balance_leads' => $remainingBalance]);
                    }

                    // $remainingBalanceLeads = Topup::where('leadspeek_api_id', $progressTopups->leadspeek_api_id)
                    //                                 ->sum('balance_leads');

                    /**GET THE TOPUP WITH STATUS NOT DONE */
                    $idTopups = Topup::select(['id'])
                                    ->where('leadspeek_api_id','=', $progressTopups->leadspeek_api_id)
                                    ->where('topup_status','<>','done')
                                    ->get();

                    /** AFTER GET THE TOPU ID, COUNT remaining balance leads, sum total_leads from Toup up table based on leadspeek_api_id, and the top up status not done - sum row from report table based on leadspeek_api_id and topup_id */
                    $remainingBalanceLeads = Topup::where('leadspeek_api_id','=', $_leadspeek_api_id)
                                                ->where('topup_status','<>','done')
                                                ->sum('total_leads') - DB::table('leadspeek_reports')
                                                ->where('leadspeek_api_id','=',$progressTopups->leadspeek_api_id)
                                                ->whereIn('topup_id', $idTopups)
                                                ->count();

                    Log::info("remainingBalanceLeads : " . $remainingBalanceLeads . " clientLimitLeads : " . $clientLimitLeads);

                    if ($remainingBalanceLeads < $clientLimitLeads && $cl['stopcontinual'] == 'F' && $cl['topupoptions'] == 'continual') {

                        $freqContinualTopup = (isset($cl['continual_buy_options']) && $cl['continual_buy_options'] == 'Weekly')?1:4;

                        /** CREATE PAYMENT HERE */
                        $_usrInfo = User::select('id','customer_payment_id','customer_card_id','email','company_parent','company_id','payment_status')
                            ->where('id','=',$progressTopups->user_id)
                            ->where('active','=','T')
                            ->get();

                        $data['cost_perlead'] = $clientCostPerLead;
                        $data['total_leads'] = ($clientLimitLeads * 7) * $freqContinualTopup;
                        $data['platformfee'] = $clientPlatformfee;
                        //$data['platform_price'] = $progressTopups->platform_price;
                        $data['platform_price'] = $ori_platform_price_lead;
                        //$data['root_price'] = $progressTopup->root_price;
                        $data['root_price'] = $ori_rootFeeCost;

                        if (trim($_usrInfo[0]['payment_status']) == "") {
                            /** TOP UP ARRAY */
                            $paramTopup = [  
                                'user_id' => $progressTopups->user_id,
                                'lp_user_id' => $progressTopups->lp_user_id,
                                'company_id' => $progressTopups->company_id,
                                'leadspeek_api_id' => $progressTopups->leadspeek_api_id,
                                'leadspeek_type' => $progressTopups->leadspeek_type,
                                'topupoptions' => $cl['topupoptions'],
                                'platformfee' => $clientPlatformfee,
                                'cost_perlead' => $clientCostPerLead,
                                'lp_limit_leads' => $clientLimitLeads,
                                'total_leads' => ($clientLimitLeads * 7) * $freqContinualTopup,
                                'balance_leads' => ($clientLimitLeads * 7) * $freqContinualTopup,
                                //'platform_price' => $progressTopups->platform_price,
                                'platform_price' => $ori_platform_price_lead,
                                //'root_price' => $progressTopup->root_price,
                                'root_price' => $ori_rootFeeCost,
                                'treshold' => $clientLimitLeads,
                                'payment_amount' => '0',
                                'active' => 'T',
                                'stop_continue' => 'F',
                                'last_cost_perlead' => '0',
                                'last_limit_leads_day' => '0',
                                'topup_status' => 'queue',
                                ];
                            /** TOP UP ARRAY */
                            $totalFirstCharge = ($data['cost_perlead'] * $data['total_leads']) + $data['platformfee'];
                            $_platformFee = ($data['platform_price'] * $data['total_leads']);
                            $_rootPrice = ($data['root_price'] * $data['total_leads']);
                            $this->chargeClient($_usrInfo[0]['customer_payment_id'],$_usrInfo[0]['customer_card_id'],$_usrInfo[0]['email'],$totalFirstCharge,$cl['platformfee'],$_platformFee,$cl,$data,$_rootPrice,true,$paramTopup);
                        }   
                        /** CREATE PAYMENT HERE */

                        // if (trim($_usrInfo[0]['payment_status']) == "") {
                        //     $param = [  
                        //     'user_id' => $progressTopups->user_id,
                        //     'lp_user_id' => $progressTopups->lp_user_id,
                        //     'company_id' => $progressTopups->company_id,
                        //     'leadspeek_api_id' => $progressTopups->leadspeek_api_id,
                        //     'leadspeek_type' => $progressTopups->leadspeek_type,
                        //     'topupoptions' => $cl['topupoptions'],
                        //     'platformfee' => $clientPlatformfee,
                        //     'cost_perlead' => $clientCostPerLead,
                        //     'lp_limit_leads' => $clientLimitLeads,
                        //     'total_leads' => ($clientLimitLeads * 7) * 4,
                        //     'balance_leads' => ($clientLimitLeads * 7) * 4,
                        //     'platform_price' => $progressTopups->platform_price,
                        //     'root_price' => $progressTopup->root_price,
                        //     'treshold' => $clientLimitLeads,
                        //     'payment_amount' => '0',
                        //     'active' => 'T',
                        //     'stop_continue' => 'F',
                        //     'last_cost_perlead' => '0',
                        //     'last_limit_leads_day' => '0',
                        //     'topup_status' => 'queue',
                        //     ];    

                        //     $create = Topup::create($param);

                        //     Log::info("Buy Top Up ID: " . $create->id);
                        // }else{
                        //     Log::info("FAILED Buy Top Up Payment Status : " . $_usrInfo[0]['payment_status'] . " USER ID :" . $_usrInfo[0]['id'] . " Payment ID :" . $_usrInfo[0]['customer_payment_id']);
                        // }
                    }

                    if ($cl['topupoptions'] == 'continual') {
                        // $remainingBalanceLeads = Topup::where('leadspeek_api_id', $progressTopups->leadspeek_api_id)
                        //                             ->sum('balance_leads');
                        /**GET THE TOPUP WITH STATUS NOT DONE */
                        $idTopups = Topup::select(['id'])
                                        ->where('leadspeek_api_id','=', $progressTopups->leadspeek_api_id)
                                        ->where('topup_status','<>','done')
                                        ->get();

                        /** AFTER GET THE TOPU ID, COUNT remaining balance leads, sum total_leads from Toup up table based on leadspeek_api_id, and the top up status not done - sum row from report table based on leadspeek_api_id and topup_id */
                        $remainingBalanceLeads = Topup::where('leadspeek_api_id','=', $progressTopups->leadspeek_api_id)
                                                    ->where('topup_status','<>','done')
                                                    ->sum('total_leads') - DB::table('leadspeek_reports')
                                                    ->where('leadspeek_api_id','=',$progressTopups->leadspeek_api_id)
                                                    ->whereIn('topup_id', $idTopups)
                                                    ->count();
                    }

                    // Step 2: make sure there is  no topup with status 'progress' exists before updating next queued topup
                    $hasProgressTopup = Topup::where('leadspeek_api_id','=',$_leadspeek_api_id)
                                    ->where('topup_status', 'progress')
                                    ->exists();

                    if (!$hasProgressTopup) {
                        $nextQueuedTopup = Topup::where('leadspeek_api_id','=',$_leadspeek_api_id)
                                                ->where('topup_status','=','queue')
                                                ->orderBy('created_at', 'asc')
                                                ->first();

                        if ($nextQueuedTopup) {
                                Topup::where('id','=',$nextQueuedTopup->id)
                                            ->update(['topup_status' => 'progress']);
                        }
                    }

                    /** IF REMAINING BALANCE ZERO THEN STOP THE CAMPAIGN*/
                    if ($remainingBalanceLeads <= 0) {

                        /** STOP CONTINUAL TOPUP*/
                        $this->stop_continual_topup($_lp_user_id);
                        /** STOP CONTINUAL TOPUP*/
                        
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

                                    $updateUser = User::find($_user_id);
                                    $updateUser->lp_enddate = null;
                                    $updateUser->lp_limit_startdate = null;
                                    $updateUser->save();

                                    $clientStartBilling = date('Ymd',strtotime($clientLimitStartDate));
                                    $nextBillingDate = date('Ymd');

                                    /** CALCULATION TOTAL LEADS TOP UP AND REPORT TOP UP */
                                    $_idTopups = Topup::select(['id'])
                                                    ->where('leadspeek_api_id','=', $progressTopups->leadspeek_api_id)
                                                    ->where('topup_status','=','done')
                                                    ->get();
                                    $_TotalLimitLeads = LeadspeekReport::where('leadspeek_api_id','=',$progressTopups->leadspeek_api_id)
                                                    ->whereIn('topup_id', $_idTopups)
                                                    ->count();
                                    $_clientLimitLeads = Topup::where('leadspeek_api_id','=', $progressTopups->leadspeek_api_id)  
                                                                ->sum('total_leads');     

                                    /** CALCULATION TOTAL LEADS TOP UP AND REPORT TOP UP */

                                    /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                                    $this->notificationStartStopLeads($clientAdminNotify,'Stopped (Prepaid ' . date('m-d-Y',strtotime($clientLimitStartDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$_clientLimitLeads,$clientLimitFreq,$_TotalLimitLeads,$cl['clientowner']);
                                    /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
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
                                    $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - Webhook-ProcessDataMatch-1430) #' .$_leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                /** SEND EMAIL TO ME */
                            }
                        }else if ($cl['leadspeek_type'] == "local" || $cl['leadspeek_type'] == "enhance") {
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

                            /** CALCULATION TOTAL LEADS TOP UP AND REPORT TOP UP */
                            $_idTopups = Topup::select(['id'])
                                            ->where('leadspeek_api_id','=', $progressTopups->leadspeek_api_id)
                                            ->where('topup_status','=','done')
                                            ->get();
                            $_TotalLimitLeads = LeadspeekReport::where('leadspeek_api_id','=',$progressTopups->leadspeek_api_id)
                                            ->whereIn('topup_id', $_idTopups)
                                            ->count();
                            $_clientLimitLeads = Topup::where('leadspeek_api_id','=', $progressTopups->leadspeek_api_id)  
                                            ->sum('total_leads');     


                            /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                            $this->notificationStartStopLeads($clientAdminNotify,'Stopped (Prepaid ' . date('m-d-Y',strtotime($clientLimitStartDate)) . ' - ' . date('m-d-Y') . ')',$company_name . ' #' . $_leadspeek_api_id,$_clientLimitLeads,$clientLimitFreq,$_TotalLimitLeads,$cl['clientowner']);
                            /** SEND EMAIL NOTIFICATION ONE TIME FINISHED*/
                        }
                        /** ACTIVATE CAMPAIGN SIMPLIFI */
                    }
                    /** IF REMAINING BALANCE ZERO THEN STOP THE CAMPAIGN*/
                    $this->releaseLock($lockKey);
                    sleep(1);
                }
                /** CHECK IF PREPAID */

            }
            /** PROCESS MATCHED DATA */

        }

        /** RELEASE LOCK PROCESS FOR PREPAID */
        if (trim($paymentTerm) != "" && trim($paymentTerm) == "Prepaid") {
            Log::info("RELEASE PREPAID LOCK");
            $this->releaseLock('initPrepaidStart');
            //sleep(1);
        }
        /** RELEASE LOCK PROCESS FOR PREPAID */
    }

    
    // Function to acquire lock
    private function acquireLock($lockKey, $ttl = 10) {
        return Cache::add($lockKey, true, $ttl);
    }

    // Function to release lock
    private function releaseLock($lockKey) {
        Cache::forget($lockKey);
    }

    private function chargeClient($custStripeID,$custStripeCardID,$custEmail,$totalAmount,$oneTime,$platformFee,$usrInfo,$topup=array(),$rootFee=0,$rootFeeTransfer=false,$paramTopup = array()) {
        date_default_timezone_set('America/Chicago');
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
        $user[0]['company_root_id'] = $usrInfo['company_root_id'];

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
        $statusPayment = 'pending';
        $cardlast = '';
        $platform_paymentintentID = '';
        $sr_id = 0;
        $ae_id = 0;
        $ar_id = 0;
        $sales_fee = 0;
        $platformfee_charge = false;
        $_ongoingleads = "";

        /** CHARGE WITH STRIPE */
        if(trim($custStripeID) != '' && trim($custStripeCardID) != '' && ($totalAmount > 0 || $totalAmount != '' || $usrInfo['paymentterm'] == 'One Time' || $usrInfo['paymentterm'] == 'Prepaid') && $validuser) { 
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
            $platform_LeadspeekCostperlead = 0;
            $platform_LeadspeekMinCostMonth = 0;
            $platform_LeadspeekPlatformFee = 0;
            $platformfee_ori = 0;
            
            $paymentterm = trim($usrInfo['paymentterm']);
            $paymentterm = str_replace(' ','',$paymentterm);
            if ($usrInfo['leadspeek_type'] == "local") {
                $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:0;
                $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:0;
                $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:0;
            }else if ($usrInfo['leadspeek_type'] == "locator") {
                $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LocatorCostperlead))?$platformMargin->locator->$paymentterm->LocatorCostperlead:0;
                $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LocatorMinCostMonth))?$platformMargin->locator->$paymentterm->LocatorMinCostMonth:0;
                $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LocatorPlatformFee))?$platformMargin->locator->$paymentterm->LocatorPlatformFee:0;
            }else if ($usrInfo['leadspeek_type'] == "enhance") {
                $rootcostagency = []; 
                if(!isset($platformMargin->enhance)) {
                    $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');
                }

                $clientTypeLead = $this->getClientCapType($usrInfo['company_root_id']);
                if($clientTypeLead['type'] == 'clientcapleadpercentage') {
                    $platform_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
                } else {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->enhance->$paymentterm->EnhanceCostperlead))?$platformMargin->enhance->$paymentterm->EnhanceCostperlead:$rootcostagency->enhance->$paymentterm->EnhanceCostperlead;
                }

                $platform_LeadspeekMinCostMonth = (isset($platformMargin->enhance->$paymentterm->EnhanceMinCostMonth))?$platformMargin->enhance->$paymentterm->EnhanceMinCostMonth:$rootcostagency->enhance->$paymentterm->EnhanceMinCostMonth;
                $platform_LeadspeekPlatformFee = (isset($platformMargin->enhance->$paymentterm->EnhancePlatformFee))?$platformMargin->enhance->$paymentterm->EnhancePlatformFee:$rootcostagency->enhance->$paymentterm->EnhancePlatformFee;
            }else if ($usrInfo['leadspeek_type'] == "enhance") {
                $rootcostagency = []; 
                if(!isset($platformMargin->enhance)) {
                    $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');
                }

                $clientTypeLead = $this->getClientCapType($usrInfo['company_root_id']);
                if($clientTypeLead['type'] == 'clientcapleadpercentage') {
                    $platform_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
                } else {
                    $platform_LeadspeekCostperlead = (isset($platformMargin->enhance->$paymentterm->EnhanceCostperlead))?$platformMargin->enhance->$paymentterm->EnhanceCostperlead:$rootcostagency->enhance->$paymentterm->EnhanceCostperlead;
                }

                $platform_LeadspeekMinCostMonth = (isset($platformMargin->enhance->$paymentterm->EnhanceMinCostMonth))?$platformMargin->enhance->$paymentterm->EnhanceMinCostMonth:$rootcostagency->enhance->$paymentterm->EnhanceMinCostMonth;
                $platform_LeadspeekPlatformFee = (isset($platformMargin->enhance->$paymentterm->EnhancePlatformFee))?$platformMargin->enhance->$paymentterm->EnhancePlatformFee:$rootcostagency->enhance->$paymentterm->EnhancePlatformFee;
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
            //if (($totalAmount < $platformfee) && $platformfee > 0) {
            // if ($platformfee >= 0.5) {
            //     $agencystripe = $this->check_agency_stripeinfo($usrInfo[0]->company_parent,$platformfee,$usrInfo[0]->leadspeek_api_id,'Agency ' . $defaultInvoice);
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
                            $_cleanProfit = "";
                            if($rootFee != "0" && $rootFee != "") {
                                $_cleanProfit = $platformfee_ori - $rootFee;
                            }
                            $salesfee = $this->transfer_commission_sales($usrInfo['company_parent'],$platformfee,$usrInfo['leadspeek_api_id'],date('Y-m-d'),date('Y-m-d'),$stripeseckey,$_ongoingleads,$_cleanProfit);
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
            
            /** UPDATE USER CARD DIRECTLY IF PAYMENt FAILED */
            
            /** UPDATE USER CARD STATUS */
            if ($statusPayment == 'failed') {
                $updateUser = User::find($usrInfo['user_id']);
                $updateUser->payment_status = 'failed';
                $updateUser->save();
            }else{
                /** CREATE TOP UP */
                if (count($paramTopup) > 0) {
                    $create = Topup::create($paramTopup);
                    Log::info("Buy Top Up ID: " . $create->id);
                }
                /** CREATE TOP UP */
            }
            /** UPDATE USER CARD STATUS */

            
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
            // if ($platformfee_charge == false && $platformfee >= 0.5) { 
            //     $_cleanProfit = "";
            //     if($rootFee != "0" && $rootFee != "") {
            //         $_cleanProfit = $platformfee_ori - $rootFee;
            //     }
            //     $agencystripe = $this->check_agency_stripeinfo($usrInfo['company_parent'],$platformfee,$usrInfo['leadspeek_api_id'],'Agency ' . $defaultInvoice,date('Y-m-d 00:00:00'),date('Y-m-d 23:59:59'),$_ongoingleads,$_cleanProfit);
            //     $agencystriperesult = json_decode($agencystripe);

            //     if ($agencystriperesult->result == 'success') {
            //         $platform_paymentintentID = $agencystriperesult->payment_intentID;
            //         $sr_id = $agencystriperesult->srID;
            //         $ae_id = $agencystriperesult->aeID;
            //         $ar_id = $agencystriperesult->arID;
            //         $sales_fee = $agencystriperesult->salesfee;
            //         $platformfee = 0;
            //         $platform_errorstripe = '';
            //     }else{
            //         $platform_paymentintentID = $agencystriperesult->payment_intentID;
            //         $platform_errorstripe .= $agencystriperesult->error;
            //     }
            // }
            /** CHECK IF FAILED CHARGE CLIENT WE STILL CHARGE THE AGENCY */

            $statusClientPayment = $statusPayment;
   
            $_company_id = $usrInfo['company_id'];
            $_user_id = $usrInfo['user_id'];
            $_leadspeek_api_id = $usrInfo['leadspeek_api_id'];
            $clientPaymentTerm = $usrInfo['paymentterm'];
            //$minCostLeads = $usrInfo[0]->lp_min_cost_month;
            $minCostLeads = number_format($platformFee,2,'.','');
            $total_leads =  '0';
            $cost_perlead = '0';
            $platform_cost_leads = '0';
            $root_total_amount = '0';
            if ($usrInfo['paymentterm'] == 'Prepaid') {
                $tmpMinCost = (isset($usrInfo['lp_min_cost_month']))?$usrInfo['lp_min_cost_month']:'0';
                $minCostLeads = number_format($tmpMinCost,2,'.','');
                $total_leads =  (isset($usrInfo['leadsbuy']))?$usrInfo['leadsbuy']:'0';
                $cost_perlead = (isset($usrInfo['cost_perlead']))?$usrInfo['cost_perlead']:'0';
                $platform_cost_leads = $platform_LeadspeekCostperlead;
                $root_total_amount = (isset($usrInfo['root_price']))?$usrInfo['root_price']:'0';
                $root_total_amount = $total_leads * $root_total_amount;
            }
            $reportSentTo = explode(PHP_EOL, $usrInfo['report_sent_to']);
            $todayDate = date('Y-m-d H:i:s');
            $clientMaxperTerm = $usrInfo['lp_max_lead_month'];

            $oneTime = number_format($oneTime,2,'.','');

            /** CHECK IF ROOT FEE TRANSFER ENABLED */
            if ($rootFeeTransfer && $statusPayment == 'paid') {
                $this->root_fee_commission($usrInfo,$stripeseckey,$usrInfo['company_name'],$usrInfo['leadspeek_api_id'],$rootFee,$platformfee_ori);
            }
            /** CHECK IF ROOT FEE TRANSFER ENABLED */

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
                'total_leads' => $total_leads,
                'min_cost' => $minCostLeads,
                'platform_min_cost' => $platform_LeadspeekMinCostMonth,
                'cost_leads' => $cost_perlead,
                'platform_cost_leads' => $platform_cost_leads,
                'total_amount' => $totalAmount,
                'platform_total_amount' => $platformfee_ori,
                'root_total_amount' => $root_total_amount,
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

            
            /** CHECK IF FAILED PAYMENT THEN PAUSED THE CAMPAIGN AND SENT EMAIL*/
            if ($statusClientPayment == 'failed') {
                $ClientCompanyIDFailed = "";
                $ListFailedCampaign = "";

                $leadsuser = LeadspeekUser::select('leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.trysera','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id','users.company_id')
                                    ->join('users','leadspeek_users.user_id','=','users.id')
                                    ->where('leadspeek_users.leadspeek_api_id','=',$_leadspeek_api_id)
                                    ->get();
                if (count($leadsuser) > 0) {
                    foreach($leadsuser as $lds) {
                        $ClientCompanyIDFailed = $lds['company_id'];

                        if ($lds['active'] == "T" || ($lds['active'] == "F" && $lds['disabled'] == "F")) {
                            $tryseramethod = (isset($lds['trysera']) && $lds['trysera'] == "T")?true:false;

                            $http = new \GuzzleHttp\Client;
                            $appkey = config('services.trysera.api_id');
                            $organizationid = ($lds['leadspeek_organizationid'] != "")?$lds['leadspeek_organizationid']:"";
                            $campaignsid = ($lds['leadspeek_campaignsid'] != "")?$lds['leadspeek_campaignsid']:"";    
                            $userStripeID = $lds['customer_payment_id'];
                            
                            $updateLeadspeekUser = LeadspeekUser::find($_lp_user_id);
                            $updateLeadspeekUser->active = 'F';
                            $updateLeadspeekUser->disabled = 'T';
                            $updateLeadspeekUser->active_user = 'F';
                            $updateLeadspeekUser->last_lead_pause = date('Y-m-d H:i:s');
                            $updateLeadspeekUser->save();
                            /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                            
                            /** UPDATE USER CARD STATUS */
                            $updateUser = User::find($lds['user_id']);
                            $updateUser->payment_status = 'failed';
                            $updateUser->save();
                            /** UPDATE USER CARD STATUS */

                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                            if ($organizationid != '' && $campaignsid != '' && $lds['leadspeek_type'] == 'locator') {
                                $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */

                            $ListFailedCampaign = $ListFailedCampaign . $_leadspeek_api_id . '<br/>';
                        }
                    }

                    /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */
                    $from = [
                        'address' => 'noreply@' . $defaultdomain,
                        'name' => 'Billing Notifications',
                        'replyto' => 'support@' . $defaultdomain,
                    ];
                    

                    $details = [
                        'title' => 'Top Up Prepaid failed for Company Name: ' . $companyName . ' campaign ID #' . $_leadspeek_api_id,
                        'content'  => 'Top Up Prepaid failed for Company Name: ' . $companyName . ' campaign ID #' . $_leadspeek_api_id
                    ];
                
                    $tmp = $this->send_email($adminEmail,'Top Up Prepaid failed for campaign ID #' . $_leadspeek_api_id,$details,array(),'emails.customemail',$from,$usrInfo['company_parent']);

                    //$tmp = $this->send_email($adminEmail,'Campaign ' . $companyName . ' #' . $_leadspeek_api_id . ' (has been pause due the payment failed)',$details,$attachement,'emails.invoicefailed',$from,"");
                    //return response()->json(array('result'=>'failed','msg'=>'Sorry, this campaign can not be start, due the payment failed we paused the campaign ID : #' . $_leadspeek_api_id . ' (internal 2)'));
                    /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */

                }
            }
            /** CHECK IF FAILED PAYMENT THEN PAUSED THE CAMPAIGN AND SENT EMAIL*/

        }
        /** CHARGE WITH STRIPE */
    }

    public function root_fee_commission($usrInfo,$stripeseckey,$companyName,$_leadspeek_api_id,$rootFee = 0, $platformfee_ori = 0) {
        /** CHARGE ROOT FEE AGENCY */
        if($rootFee != "0" && $rootFee != "") {
            $rootCommissionFee = 0;
            $rootCommissionFee = ($rootFee * 0.05);
            $rootCommissionFee = number_format($rootCommissionFee,2,'.','');

            $todayDate = date('Y-m-d H:i:s');

            $cleanProfit = $platformfee_ori - $rootFee;
            //if ($cleanProfit > 0.5) {
                /** GET ROOT CONNECTED ACCOUNT TO BE TRANSFER FOR CLEAN PROFIT AFTER CUT BY ROOT FEE COST */
                $rootAccCon = "";
                $rootCommissionSRAcc = "";
                $rootCommissionAEAcc = "";
                $rootCommissionSRAccVal = $rootCommissionFee;
                $rootCommissionAEAccVal = $rootCommissionFee;

                $rootAccConResult = $this->getcompanysetting($usrInfo['company_root_id'],'rootfee');
                if ($rootAccConResult != '') {
                    $rootAccCon = (isset($rootAccConResult->rootfeeaccid))?$rootAccConResult->rootfeeaccid:"";
                    $rootCommissionSRAcc = (isset($rootAccConResult->rootcomsr))?$rootAccConResult->rootcomsr:"";
                    $rootCommissionAEAcc = (isset($rootAccConResult->rootcomae))?$rootAccConResult->rootcomae:"";
                    /** OVERRIDE IF EXIST ANOTHER VALUE NOT 5% from Root FEE */
                    if (isset($rootAccConResult->rootcomsrval) && $rootAccConResult->rootcomsrval != "") {
                        $rootCommissionSRAccVal = ($rootFee * (float) $rootAccConResult->rootcomsrval);
                        $rootCommissionSRAccVal = number_format($rootCommissionSRAccVal,2,'.','');
                    }
                    if (isset($rootAccConResult->rootcomaeval) && $rootAccConResult->rootcomaeval != "") {
                        $rootCommissionAEAccVal = ($rootFee * (float) $rootAccConResult->rootcomaeval);
                        $rootCommissionAEAccVal = number_format($rootCommissionAEAccVal,2,'.','');
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
                        $transferRootProfit = $stripe->transfers->create([
                            'amount' => ($cleanProfit * 100),
                            'currency' => 'usd',
                            'destination' => $rootAccCon,
                            'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                        ]);

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
                            'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ' Ended Campaign)',
                        ]);


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
                            'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ' Ended Campaign)',
                        ]);


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
    
    private function insertLeadspeekReport($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$id,$email,$email2,$originalmd5,$ip,$source,$optindate,$clickdate,
        $referer,$phone,$phone2,$firstname,$lastname,$address1,$address2,$city,$state,$zipcode,$lead_from,$active = 'T',$pricelead = '0',$platform_pricelead = '0',$personID = '',$keyword = '',$description = '',$rootFeeCost = '0',$topup_id = '0') {
        /** INSERT INTO LEADSPEEK REPORT */
        $leadspeekReport = LeadspeekReport::create([
            'id' => $id,
            'person_id' => $personID,
            'lp_user_id' => $_lp_user_id,
            'company_id' => $_company_id,
            'user_id' => $_user_id,
            'leadspeek_api_id' => $_leadspeek_api_id,
            'email' => $email,
            'email2' => $email2,
            'original_md5' => $originalmd5,
            'ipaddress' => $ip,
            'source' => $source,
            'optindate' => $optindate,
            'clickdate' => $clickdate,
            'referer' => $referer,
            'phone' => $phone,
            'phone2' => $phone2,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'state' => $state,
            'zipcode' => $zipcode,
            'price_lead' => $pricelead,
            'platform_price_lead' => $platform_pricelead,
            'root_price_lead' => $rootFeeCost,
            'keyword' => $keyword,
            'description' => $description,
            'topup_id' => $topup_id,
            'active' => $active,
            'lead_from' => $lead_from
        ]);
        /** INSERT INTO LEADSPEEK REPORT */

        return $leadspeekReport;
    }

    private function createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$minLeads,$costLeads,$minCostLeads,$clientPaymentTerm,$companyName,$reportSentTo,$adminnotify,$startBillingDate,$endBillingDate,$custStripeID,$custStripeCardID,$custEmail,$usrInfo,$companyParent = '') {
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
        }else if($clientPaymentTerm == 'One Time') {
            $totalAmount = $minCostLeads;
        }
        /** IF JUST WILL CHARGE PER LEAD */

        /** CHARGE WITH STRIPE */
        $paymentintentID = '';
        $errorstripe = '';
        $platform_errorstripe = '';
        $statusPayment = 'pending';
        $cardlast = '';
        $platform_paymentintentID = '';
        $sr_id = 0;
        $ae_id = 0;
        $ar_id = 0;
        $sales_fee = 0;
        $platformfee_charge = false;

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

            $stripe = new StripeClient([
                'api_key' => $stripeseckey,
                'stripe_version' => '2020-08-27'
            ]);

            /** GET PLATFORM MARGIN */
                $platformMargin = $this->getcompanysetting($usrInfo['company_parent'],'costagency');

                $paymentterm = trim($usrInfo['paymentterm']);
                $paymentterm = str_replace(' ','',$paymentterm);
                if ($platformMargin != '') {
                    if ($usrInfo['leadspeek_type'] == "local") {
                        $platform_LeadspeekCostperlead = (isset($platformMargin->local->$paymentterm->LeadspeekCostperlead))?$platformMargin->local->$paymentterm->LeadspeekCostperlead:0;
                        $platform_LeadspeekMinCostMonth = (isset($platformMargin->local->$paymentterm->LeadspeekMinCostMonth))?$platformMargin->local->$paymentterm->LeadspeekMinCostMonth:0;
                        $platform_LeadspeekPlatformFee = (isset($platformMargin->local->$paymentterm->LeadspeekPlatformFee))?$platformMargin->local->$paymentterm->LeadspeekPlatformFee:0;
                    }else if ($usrInfo['leadspeek_type'] == "locator") {
                        $platform_LeadspeekCostperlead = (isset($platformMargin->locator->$paymentterm->LocatorCostperlead))?$platformMargin->locator->$paymentterm->LocatorCostperlead:0;
                        $platform_LeadspeekMinCostMonth = (isset($platformMargin->locator->$paymentterm->LocatorMinCostMonth))?$platformMargin->locator->$paymentterm->LocatorMinCostMonth:0;
                        $platform_LeadspeekPlatformFee = (isset($platformMargin->locator->$paymentterm->LocatorPlatformFee))?$platformMargin->locator->$paymentterm->LocatorPlatformFee:0;
                    }else if ($usrInfo['leadspeek_type'] == "enhance") {
                        $rootcostagency = []; 
                        if(!isset($platformMargin->enhance)) {
                            $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');
                        }
    
                        $clientTypeLead = $this->getClientCapType($usrInfo['company_root_id']);
                        if($clientTypeLead['type'] == 'clientcapleadpercentage') {
                            $platform_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
                        } else {
                            $platform_LeadspeekCostperlead = (isset($platformMargin->enhance->$paymentterm->EnhanceCostperlead))?$platformMargin->enhance->$paymentterm->EnhanceCostperlead:$rootcostagency->enhance->$paymentterm->EnhanceCostperlead;
                        }
    
                        $platform_LeadspeekMinCostMonth = (isset($platformMargin->enhance->$paymentterm->EnhanceMinCostMonth))?$platformMargin->enhance->$paymentterm->EnhanceMinCostMonth:$rootcostagency->enhance->$paymentterm->EnhanceMinCostMonth;
                        $platform_LeadspeekPlatformFee = (isset($platformMargin->enhance->$paymentterm->EnhancePlatformFee))?$platformMargin->enhance->$paymentterm->EnhancePlatformFee:$rootcostagency->enhance->$paymentterm->EnhancePlatformFee;
                    }else if ($usrInfo['leadspeek_type'] == "enhance") {
                        $rootcostagency = []; 
                        if(!isset($platformMargin->enhance)) {
                            $rootcostagency = $this->getcompanysetting($usrInfo['company_root_id'],'rootcostagency');
                        }
    
                        $clientTypeLead = $this->getClientCapType($usrInfo['company_root_id']);
                        if($clientTypeLead['type'] == 'clientcapleadpercentage') {
                            $platform_LeadspeekCostperlead = ($usrInfo['cost_perlead'] * $clientTypeLead['value']) / 100;
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
            }else if($clientPaymentTerm == 'One Time') {
                $platformfee = $platform_LeadspeekMinCostMonth;
            }

            $platformfee = number_format($platformfee,2,'.','');
            $platformfee_ori = $platformfee;

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
                        $chargeAmount = $totalAmount * 100;
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

                        $paymentintentID = $payment_intent->id;
                        $statusPayment = 'paid';
                        $platformfee_charge = true;

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
            }else{
                $cardinfo = $stripe->customers->retrieveSource(trim($custStripeID),trim($custStripeCardID),[],['stripe_account' => $accConID]);
                $cardlast = $cardinfo->last4;
            }

            /** CHECK IF FAILED CHARGE CLIENT WE STILL CHARGE THE AGENCY */
            //if ($statusPayment == 'failed' && $platformfee_charge == false && $platformfee >= 0.5) {
            if ($platformfee_charge == false && $platformfee >= 0.5) {
                $_cleanProfit = "";
                if($rootFee != "0" && $rootFee != "") {
                    $_cleanProfit = $platformfee_ori - $rootFee;
                }
                $agencystripe = $this->check_agency_stripeinfo($usrInfo['company_parent'],$platformfee,$_leadspeek_api_id,'Agency ' . $defaultInvoice,$startBillingDate,$endBillingDate,$ongoingLeads,$_cleanProfit);
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

            /** CHARGE ROOT FEE AGENCY */
            if($rootFee != "0" && $rootFee != "") {
                $rootCommissionFee = 0;
                $rootCommissionFee = ($rootFee * 0.05);
                $rootCommissionFee = number_format($rootCommissionFee,2,'.','');

                $cleanProfit = $platformfee_ori - $rootFee;
                //if ($cleanProfit > 0.5) {
                    /** GET ROOT CONNECTED ACCOUNT TO BE TRANSFER FOR CLEAN PROFIT AFTER CUT BY ROOT FEE COST */
                    $rootAccCon = "";
                    $rootCommissionSRAcc = "";
                    $rootCommissionAEAcc = "";
                    $rootCommissionSRAccVal = $rootCommissionFee;
                    $rootCommissionAEAccVal = $rootCommissionFee;

                    $rootAccConResult = $this->getcompanysetting($usrInfo['company_root_id'],'rootfee');
                    if ($rootAccConResult != '') {
                        $rootAccCon = (isset($rootAccConResult->rootfeeaccid))?$rootAccConResult->rootfeeaccid:"";
                        $rootCommissionSRAcc = (isset($rootAccConResult->rootcomsr))?$rootAccConResult->rootcomsr:"";
                        $rootCommissionAEAcc = (isset($rootAccConResult->rootcomae))?$rootAccConResult->rootcomae:"";
                        /** OVERRIDE IF EXIST ANOTHER VALUE NOT 5% from Root FEE */
                        if (isset($rootAccConResult->rootcomsrval) && $rootAccConResult->rootcomsrval != "") {
                            $rootCommissionSRAccVal = ($rootFee * (float) $rootAccConResult->rootcomsrval);
                            $rootCommissionSRAccVal = number_format($rootCommissionSRAccVal,2,'.','');
                        }
                        if (isset($rootAccConResult->rootcomaeval) && $rootAccConResult->rootcomaeval != "") {
                            $rootCommissionAEAccVal = ($rootFee * (float) $rootAccConResult->rootcomaeval);
                            $rootCommissionAEAccVal = number_format($rootCommissionAEAccVal,2,'.','');
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
                            $transferRootProfit = $stripe->transfers->create([
                                'amount' => ($cleanProfit * 100),
                                'currency' => 'usd',
                                'destination' => $rootAccCon,
                                'description' => 'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',
                            ]);

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
                    if ($rootCommissionSRAcc != "" && $rootCommissionSRAcc > 0.5) {
                        $stripe = new StripeClient([
                            'api_key' => $stripeseckey,
                            'stripe_version' => '2020-08-27'
                        ]);

                        try {
                            $transferRootProfitSRAcc = $stripe->transfers->create([
                                'amount' => ($rootCommissionSRAccVal * 100),
                                'currency' => 'usd',
                                'destination' => $rootCommissionSRAcc,
                                'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ' Ended Campaign)',
                            ]);


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
                                'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ' Ended Campaign)',
                            ]);


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
            'customer_payment_id' => $paymentintentID,
            'customer_stripe_id' => $custStripeID,
            'customer_card_id' => $custStripeCardID,
            'platform_customer_payment_id' => $platform_paymentintentID,
            'error_payment' => $errorstripe,
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
        $lpupdate->start_billing_date = date('Y-m-d H:i:s',strtotime($endBillingDate));
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
        if ($statusClientPayment == 'failed') {
            $subjectFailed = "Failed Payment - ";
        }
        //if (($platformfee_ori != '0.00' || $platformfee_ori != '0') || ($totalAmount != '0.00' || $totalAmount != '0') ) {
            $this->send_email($adminEmail,$subjectFailed . 'Invoice for ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',$details,$attachement,'emails.tryseramatchlistinvoice',$from,$companyParent);
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
                            'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ' Ended Campaign)'
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
                            'description' => 'Commision Root app from  Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ' Ended Campaign)'
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
        //                                 ->where(function ($query) {
        //                                     $query->where('user_type','=','userdownline')
        //                                     ->orWhere('user_type','=','user');
        //                                 })->get();
        //     /** GET ROOT ADMIN */
        //     foreach($rootAdmin as $radm) {
        //         $this->send_email(array($radm['email']),'Profit Root App from Invoice ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')' ,$details,$attachement,'emails.rootProfitTransfer',$from,$usrInfo[0]->company_parent);
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

        $this->send_email(array($custEmail),'Invoice for ' . $companyName . ' #' . $_leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',$details,$attachement,'emails.tryseramatchlistinvoice',$from,$companyParent);
        /** SENT FOR CLIENT INVOICE */

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

    }

    /** SIMPLI.FI API */
    public function getStatefromCity($loccitysifi,$_state) {
        $http = new \GuzzleHttp\Client;

        $appkey = "86bb19a0-43e6-0139-8548-06b4c2516bae";
        $usrkey = "63c52610-87cd-0139-b15f-06a60fe5fe77";

                try {
                    $apiURL = "https://app.simpli.fi/api/geo_targets/" . $loccitysifi;
                    $options = [
                        'headers' => [
                            'X-App-Key' => $appkey,
                            'X-User-Key' => $usrkey,
                            'Content-Type' => 'application/json',
                        ],
                    ];

                    $response = $http->get($apiURL,$options);
                    $cityresult =  json_decode($response->getBody());

                    $tmpStateID = "";
                    for($i=0;$i<count($cityresult->geo_targets);$i++) {
                        $tmpStateID = $cityresult->geo_targets[$i]->parent_id;
                    }

                    $apiURL = "https://app.simpli.fi/api/geo_targets/" . $tmpStateID;

                    $response = $http->get($apiURL,$options);
                    $stateresult =  json_decode($response->getBody());

                    for($i=0;$i<count($stateresult->geo_targets);$i++) {
                        $statelist = State::select('state','state_code','sifi_state_id')
                                    ->where('sifi_state_id','=',$stateresult->geo_targets[$i]->id)
                                    ->orderBy('state')
                                    ->get();
                        if (count($statelist) > 0) {
                            if (strtolower(trim($_state)) ==  strtolower(trim($statelist[0]['state_code']))) {
                                return true;
                            }else{
                                return false;
                            }
                        }else{
                            return false;
                        }
                    }

                }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                    return false;
                }
    }

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

                // return response()->json(array("result"=>'failed','message'=>'Something went wrong on the server.'), $e->getCode());
            }

        }

        return $ProcessStatus;

    }
    /** SIMPLI.FI API */

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

    private function getDataMatch($md5param,$leadspeek_api_id,$data,$keyword = '',$loctarget = 'Focus',$loczip = "",$locstate = "",$locstatesifi = "",$loccity = "",$loccitysifi = "",$nationaltargeting = "F",$leadspeektype="local",$compleadID = "",$clientCompanyID = "",$clientCompanyRootId = "") {
        date_default_timezone_set('America/Chicago');

        $matches = array();
        $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
        $dataflow = "";

        /** RECORD ANY INCOMING MD5 PARAM THAT WE GET FROM TOWERDATA WEBHOOK FIRED */
        $fr = FailedRecord::create([
            'email_encrypt' => $md5param,
            'leadspeek_api_id' => $leadspeek_api_id,
            'description' => 'md5email fired|' . $keyword,
        ]);
        $failedRecordID = $fr->id;
        /** RECORD ANY INCOMING MD5 PARAM THAT WE GET FROM TOWERDATA WEBHOOK FIRED */

        /** FILTER IF THERE IS KEYWORD TV AND PUT IT ON OPTOUT LIST */
        $blockedkeyword = array("tv");
        $word = strtolower(trim($keyword));

        if (in_array($word,$blockedkeyword)) {
            $createoptout = OptoutList::create([
                'email' => '',
                'emailmd5' => $md5param,
                'blockedcategory' => 'keyword',
                'description' => 'blocked because keyword : ' . $word,
            ]);
        }
        /** FILTER IF THERE IS KEYWORD TV AND PUT IT ON OPTOUT LIST */

        /** FILTER IF SIMPLIFI GIVE NOT FIT KEYWORD */
        if ($word != "") {
            if (str_contains($word, '_audience')) {
                $keyword = "";
            }
        }
        /** FILTER IF SIMPLIFI GIVE NOT FIT KEYWORD */

        /** CHECK AGAINST EMM OPT OUT LIST */
        $notAgainstOptout = true;

        $optoutlist = OptoutList::select('emailmd5')
                                ->where('emailmd5','=',$md5param)
                                ->where('company_root_id','=',$clientCompanyRootId)
                                ->get();
        if (count($optoutlist) > 0) {
            $notAgainstOptout = false;
            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                $frupdate = FailedRecord::find($failedRecordID);
                $frupdate->description = $frupdate->description . '|AgainstEMMOptList';
                $frupdate->save();
            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
            return array();
            exit;die();
        }
        /** CHECK AGAINST EMM OPT OUT LIST */

        /** CHECK AGAINST CAMPAIGN OR ACCOUNT SUPPRESSION LIST */
        if ($notAgainstOptout) {
            $notOnSuppressionList = true;

            /** CHECK ACCOUNT / AGENCY LEVEL */
            $suppressionlistAccount = SuppressionList::select('emailmd5')
                                        ->where('emailmd5','=',$md5param)
                                        ->where('company_id','=',$compleadID)
                                        ->where('suppression_type','=','account')
                                        ->get();
            if (count($suppressionlistAccount) > 0) {
                $notOnSuppressionList = false;
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    $frupdate = FailedRecord::find($failedRecordID);
                    $frupdate->description = $frupdate->description . '|AgainstAccountOptList';
                    $frupdate->save();
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                return array();
                exit;die();
            }

            /** CHECK CLIENT LEVEL */

            $suppressionlistAccount = SuppressionList::select('emailmd5')
                                        ->where('emailmd5','=',$md5param)
                                        ->where('company_id','=',$clientCompanyID)
                                        ->where('suppression_type','=','client')
                                        ->get();
            if (count($suppressionlistAccount) > 0) {
                $notOnSuppressionList = false;
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    $frupdate = FailedRecord::find($failedRecordID);
                    $frupdate->description = $frupdate->description . '|AgainstClientOptList';
                    $frupdate->save();
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                return array();
                exit;die();
            }

            /** CHECK CAMPAIGN LEVEL */
             $suppressionlistCampaign = SuppressionList::select('emailmd5')
                                        ->where('emailmd5','=',$md5param)
                                        ->where('leadspeek_api_id','=',$leadspeek_api_id)
                                        ->where('suppression_type','=','campaign')
                                        ->get();
            if (count($suppressionlistCampaign) > 0) {
                $notOnSuppressionList = false;
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    $frupdate = FailedRecord::find($failedRecordID);
                    $frupdate->description = $frupdate->description . '|AgainstCampaignOptList';
                    $frupdate->save();
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                return array();
                exit;die();
            }

        }
        /** CHECK AGAINST CAMPAIGN OR ACCOUNT SUPPRESSION LIST */

        /** CHECK REGARDING RE-IDENTIFICATION FOR LEADS */
        if ($notOnSuppressionList) {
            $reidentification = 'never';
            $notOnReidentification = true;
            $applyreidentificationall = false;

            $chkreidentification = LeadspeekUser::select('reidentification_type','applyreidentificationall')
                                        ->where('leadspeek_api_id','=',$leadspeek_api_id)
                                        ->get();
            if (count($chkreidentification) > 0) {
                $reidentification = $chkreidentification[0]['reidentification_type'];
                $applyreidentificationall = ($chkreidentification[0]['applyreidentificationall'] == 'T')?true:false;
            }

            /** CHECK IF DATA ALREADY IN THAT CAMPAIGN OR NOT */
            $chkExistOnCampaign = array();

            if ($applyreidentificationall) {
                $chkExistOnCampaign = LeadspeekReport::select('leadspeek_reports.id','leadspeek_reports.clickdate','leadspeek_reports.created_at')
                                        ->join('leadspeek_users','leadspeek_reports.lp_user_id','=','leadspeek_users.id')
                                        ->where('leadspeek_users.applyreidentificationall','=','T')
                                        ->where('leadspeek_users.archived','=','F')
                                        ->where(function($query) use ($salt,$md5param){
                                            $query->where(DB::raw("MD5(CONVERT(AES_DECRYPT(FROM_bASE64(`leadspeek_reports`.`email`), '" . $salt . "') USING utf8mb4))"),'=',$md5param)
                                                    ->orWhere(DB::raw("MD5(CONVERT(AES_DECRYPT(FROM_bASE64(`leadspeek_reports`.`email2`), '" . $salt . "') USING utf8mb4))"),'=',$md5param)
                                                    ->orWhere('leadspeek_reports.original_md5','=',$md5param);
                                        })
                                        ->orderBy(DB::raw("DATE_FORMAT(leadspeek_reports.clickdate,'%Y%m%d')"),'DESC')
                                        ->limit(1)
                                        ->get();
            }else{
                $chkExistOnCampaign = LeadspeekReport::select('id','clickdate','created_at')
                                        ->where('leadspeek_api_id','=',$leadspeek_api_id)
                                        ->where(function($query) use ($salt,$md5param){
                                            $query->where(DB::raw("MD5(CONVERT(AES_DECRYPT(FROM_bASE64(`email`), '" . $salt . "') USING utf8mb4))"),'=',$md5param)
                                                    ->orWhere(DB::raw("MD5(CONVERT(AES_DECRYPT(FROM_bASE64(`email2`), '" . $salt . "') USING utf8mb4))"),'=',$md5param)
                                                    ->orWhere('original_md5','=',$md5param);
                                        })
                                        ->orderBy(DB::raw("DATE_FORMAT(clickdate,'%Y%m%d')"),'DESC')
                                        ->limit(1)
                                        ->get();
            }

            //if (count($chkExistOnCampaign) > 0 && $reidentification != 'never') {
            if (count($chkExistOnCampaign) > 0) {
                $clickDate = date('Ymd',strtotime($chkExistOnCampaign[0]['clickdate']));
                $date1=date_create(date('Ymd'));
                $date2=date_create($clickDate);
                $diff=date_diff($date1,$date2);

                if ($reidentification == 'never') {
                    $notOnReidentification = false;
                }else if ($diff->format("%a") <= 7 && $reidentification == '1 week') {
                    $notOnReidentification = false;
                }else if ($diff->format("%a") <= 30 && $reidentification == '1 month') {
                    $notOnReidentification = false;
                }else if ($diff->format("%a") <= 90 && $reidentification == '3 months') {
                    $notOnReidentification = false;
                }else if ($diff->format("%a") <= 120 && $reidentification == '6 months') {
                    $notOnReidentification = false;
                }else if ($diff->format("%a") <= 360 && $reidentification == '1 year') {
                    $notOnReidentification = false;
                }
            }
            /** CHECK IF DATA ALREADY IN THAT CAMPAIGN OR NOT */

        }
        /** CHECK REGARDING RE-IDENTIFICATION FOR LEADS */

        /** CHECK ON DATABASE IF EXIST */
        if ($notOnReidentification) {

            $chkEmailExist = PersonEmail::select('person_emails.email','person_emails.id as emailID','person_emails.permission','p.lastEntry','p.uniqueID',DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(`firstName`), '" . $salt . "') USING utf8mb4) as firstName"),
            DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(`lastName`), '" . $salt . "') USING utf8mb4) as lastName"),'p.id')
                                ->join('persons as p','person_emails.person_id','=','p.id')
                                ->where('person_emails.email_encrypt','=',$md5param)
                                ->get();

            if (count($chkEmailExist) > 0) {
                    $lastEntry = date('Ymd',strtotime($chkEmailExist[0]['lastEntry']));
                    $date1=date_create(date('Ymd'));
                    $date2=date_create($lastEntry);
                    $diff=date_diff($date1,$date2);

                    $persondata['id'] = $chkEmailExist[0]['id'];
                    $persondata['emailID'] = $chkEmailExist[0]['emailID'];
                    $persondata['uniqueID'] = $chkEmailExist[0]['uniqueID'];
                    $persondata['firstName'] = $chkEmailExist[0]['firstName'];
                    $persondata['lastName'] = $chkEmailExist[0]['lastName'];

                    /** CHECK FOR LOCATION LOCK */
                    $datalocation = PersonAddress::select('state','zip','city')->where('person_id','=',$persondata['id'])->get();
                    if (count($datalocation) > 0) {
                        $chkloc = $this->checklocationlock($loctarget,$datalocation[0]['zip'],$datalocation[0]['state'],$datalocation[0]['city'],$loczip,$locstate,$locstatesifi,$loccity,$loccitysifi,$nationaltargeting,$failedRecordID);
                        if ($chkloc) {
                            /** REPORT ANALYTIC */
                                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlockfailed');
                            /** REPORT ANALYTIC */
                            return array();
                            exit;die();
                        }else{
                            /** REPORT ANALYTIC */
                                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlock');
                            /** REPORT ANALYTIC */
                        }
                    }
                    /** CHECK FOR LOCATION LOCK */

                    $dataresult = array();
                    $personEmail = $chkEmailExist[0]['email'];

                    $dataflow = $dataflow . 'EmailExistonDB|';

                if ($diff->format("%a") <= 120 && $chkEmailExist[0]['permission'] == "T") { /** customer exist, permission YES, last Entry < 6 Month **/
                    // DATA EXIST ON DB
                    $dataflow = $dataflow . 'LastEntryLessSixMonthPermissionYes|';
                    $dataresult = $this->dataExistOnDB($personEmail,$persondata,$data,$keyword,$dataflow,$failedRecordID,$md5param);
                }else if ($diff->format("%a") > 120 && $chkEmailExist[0]['permission'] == "T") {  /** customer exist, permission YES, last Entry > 6 Month **/
                    // UPDATE OR QUERY TO ENDATO
                    $dataflow = $dataflow . 'LastEntryMoreSixMonthPermissionYes|';
                    $dataresult = $this->dataNotExistOnDBBIG($persondata['firstName'],$persondata['lastName'],$personEmail,"","","","","",$persondata['id'],$keyword,$dataflow,$failedRecordID,$md5param,$leadspeek_api_id,$leadspeektype);
                    //$dataresult = $this->dataNotExistOnDB($persondata['firstName'],$persondata['lastName'],$personEmail,"","","","","",$persondata['id'],$keyword,$dataflow,$failedRecordID,$md5param,$leadspeek_api_id,$leadspeektype);
                }else if ($diff->format("%a") > 120 && $chkEmailExist[0]['permission'] == "F") {  /** customer exist, permission NOT, last Entry > 6 Month **/
                    // UPDATE OR QUERY TO ENDATO
                    $dataflow = $dataflow . 'LastEntryMoreSixMonthPermissionNo|';
                    $dataresult = $this->dataNotExistOnDBBIG($persondata['firstName'],$persondata['lastName'],$personEmail,"","","","","",$persondata['id'],$keyword,$dataflow,$failedRecordID,$md5param,$leadspeek_api_id,$leadspeektype);
                    //$dataresult = $this->dataNotExistOnDB($persondata['firstName'],$persondata['lastName'],$personEmail,"","","","","",$persondata['id'],$keyword,$dataflow,$failedRecordID,$md5param,$leadspeek_api_id,$leadspeektype);
                }

                if (count($dataresult) > 0) {
                    array_push($matches,$dataresult);
                    return $matches;
                }else{
                    /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                        $frupdate = FailedRecord::find($failedRecordID);
                        $frupdate->description = $frupdate->description . '|NotAll4RequiredDataReturned';
                        $frupdate->save();
                    /** UPDATE FOR DESCRIPTION ON FAILED RECORD */

                    /** RECORD AS FAILURE */
                    /*$fr = FailedRecord::create([
                        'email_encrypt' => $md5param,
                        'leadspeek_api_id' => $leadspeek_api_id,
                        'description' => 'Not All 4 Required data returned',
                    ]);*/
                    return array();
                    /** RECORD AS FAILURE */
                }

            }else{ //IF NOT EXIST ON DB

                /** QUERY BIG BDM TO GET RESULT */
                    $dataresult = $this->process_BDM_TowerDATA($loctarget,$keyword,$dataflow,$failedRecordID,$md5param,$leadspeek_api_id,$loczip,$locstate,$locstatesifi,$loccity,$loccitysifi,$nationaltargeting,$leadspeektype);
                    if (count($dataresult) > 0) {
                        array_push($matches,$dataresult);
                        return $matches;
                    }else{
                        return array();
                        exit;die();
                    }
                /** QUERY BIG BDM TO GET RESULT */

                /** QUERYING TO TOWER DATA WITH DATA POSTAL TO GET SOME OTHER INFORMATION WITH MD5 EMAIL */
                    // $tower = $this->getTowerData("postal",$md5param);

                    // if (isset($tower->postal_address)) {
                    //     $_fname = $tower->postal_address->first_name;
                    //     $_lname = $tower->postal_address->last_name;
                    //     $_email = "";
                    //     $_phone = "";
                    //     $_address = $tower->postal_address->address;
                    //     $_city = $tower->postal_address->city;
                    //     $_state = $tower->postal_address->state;
                    //     $_zip = $tower->postal_address->zip;

                    //     /** REPORT ANALYTIC */
                    //         $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'towerpostal');
                    //     /** REPORT ANALYTIC */

                    //     $dataresult = $this->dataNotExistOnDB($_fname,$_lname,$_email,$_phone,$_address,$_city,$_state,$_zip,"",$keyword,$dataflow,$failedRecordID,$md5param,$leadspeek_api_id,$leadspeektype);

                    //     if (count($dataresult) > 0) {

                    //         /** REPORT ANALYTIC */
                    //             $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'endatoenrichment');
                    //         /** REPORT ANALYTIC */

                    //         /** CHECK FOR LOCATION LOCK */
                    //             $chkloc = $this->checklocationlock($loctarget,$dataresult['Zipcode'],$dataresult['State'],$dataresult['City'],$loczip,$locstate,$loccity,$nationaltargeting,$failedRecordID);
                    //             if ($chkloc) {
                    //                 /** REPORT ANALYTIC */
                    //                     $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlockfailed');
                    //                 /** REPORT ANALYTIC */
                    //                 return array();
                    //                 exit;die();
                    //             }else{
                    //                 /** REPORT ANALYTIC */
                    //                 $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlock');
                    //                 /** REPORT ANALYTIC */
                    //             }
                    //         /** CHECK FOR LOCATION LOCK */

                    //         if (trim($dataresult['Email']) != "") {
                    //             array_push($matches,$dataresult);
                    //             return $matches;
                    //         }else{
                    //             $tower = $this->getTowerData("md5",$md5param);
                    //             if (isset($tower->target_email)) {
                    //                 if ($tower->target_email != "") {
                    //                     $tmpEmail = strtolower(trim($tower->target_email));
                    //                     $tmpMd5 = md5($tmpEmail);
                    //                     $dataresult['Email'] = $tmpEmail;

                    //                     /** REPORT ANALYTIC */
                    //                         $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'toweremail');
                    //                     /** REPORT ANALYTIC */

                    //                     if(trim($tmpEmail) != "") {
                    //                         $zbcheck = $this->zb_validation($tmpEmail,"");
                    //                         if (isset($zbcheck->status)) {
                    //                             if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                    //                                 /** PUT IT ON OPTOUT LIST */
                    //                                 $createoptout = OptoutList::create([
                    //                                     'email' => $tmpEmail,
                    //                                     'emailmd5' => md5($tmpEmail),
                    //                                     'blockedcategory' => 'zbnotvalid',
                    //                                     'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email1fromTD',
                    //                                 ]);
                    //                                 /** PUT IT ON OPTOUT LIST */
                    //                                 $dataresult['Email'] = "";

                    //                                 /** REPORT ANALYTIC */
                    //                                 $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                    //                                 /** REPORT ANALYTIC */
                    //                             }else{
                    //                                 $newpersonemail = PersonEmail::create([
                    //                                     'person_id' => $dataresult['PersonID'],
                    //                                     'email' => $tmpEmail,
                    //                                     'email_encrypt' => $tmpMd5,
                    //                                     'permission' => 'T',
                    //                                     'zbvalidate' => date('Y-m-d H:i:s'),
                    //                                 ]);

                    //                                 /** REPORT ANALYTIC */
                    //                                     $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                    //                                 /** REPORT ANALYTIC */

                    //                             }

                    //                         }else{
                    //                             $newpersonemail = PersonEmail::create([
                    //                                 'person_id' => $dataresult['PersonID'],
                    //                                 'email' => $tmpEmail,
                    //                                 'email_encrypt' => $tmpMd5,
                    //                                 'permission' => 'T',
                    //                                 'zbvalidate' => null,
                    //                             ]);
                    //                         }
                    //                     }

                    //                 }
                    //             }

                    //             array_push($matches,$dataresult);
                    //             return $matches;
                    //         }
                    //     }else{
                    //         /** CHECK FOR LOCATION LOCK */
                    //         $chkloc = $this->checklocationlock($loctarget,$_zip,$_state,$_city,$loczip,$locstate,$loccity,$nationaltargeting,$failedRecordID);
                    //             if ($chkloc) {
                    //                 /** REPORT ANALYTIC */
                    //                 $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlockfailed');
                    //                 /** REPORT ANALYTIC */
                    //                 return array();
                    //                 exit;die();
                    //             }else{
                    //                 /** REPORT ANALYTIC */
                    //                 $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlock');
                    //                 /** REPORT ANALYTIC */
                    //             }
                    //         /** CHECK FOR LOCATION LOCK */

                    //         /** GET FROM MD5 TO QUERY AND GET WHATEVER WE CAN GET */
                    //         $tower = $this->getTowerData("md5",$md5param);
                    //         if (isset($tower->target_email)) {
                    //             if ($tower->target_email != "") {
                    //                 $tmpEmail = strtolower(trim($tower->target_email));
                    //                 $tmpMd5 = md5($tmpEmail);
                    //                 $_email = $tmpEmail;

                    //                 /** REPORT ANALYTIC */
                    //                     $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'toweremail');
                    //                 /** REPORT ANALYTIC */

                    //                 if(trim($tmpEmail) != "") {

                    //                     $uniqueID = uniqid();
                    //                     /** INSERT INTO DATABASE PERSON */
                    //                         $newPerson = Person::create([
                    //                             'uniqueID' => $uniqueID,
                    //                             'firstName' => $_fname,
                    //                             'middleName' => '',
                    //                             'lastName' => $_lname,
                    //                             'age' => '0',
                    //                             'identityScore' => '0',
                    //                             'lastEntry' => date('Y-m-d H:i:s'),
                    //                         ]);

                    //                         $newPersonID = $newPerson->id;
                    //                     /** INSERT INTO DATABASE PERSON */

                    //                     $zbcheck = $this->zb_validation($tmpEmail,"");
                    //                     if (isset($zbcheck->status)) {
                    //                         if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                    //                             /** PUT IT ON OPTOUT LIST */
                    //                             $createoptout = OptoutList::create([
                    //                                 'email' => $tmpEmail,
                    //                                 'emailmd5' => md5($tmpEmail),
                    //                                 'blockedcategory' => 'zbnotvalid',
                    //                                 'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email1fromTD',
                    //                             ]);
                    //                             /** PUT IT ON OPTOUT LIST */
                    //                             $_email = "";

                    //                             /** REPORT ANALYTIC */
                    //                             $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                    //                             /** REPORT ANALYTIC */
                    //                         }else{

                    //                             $newpersonemail = PersonEmail::create([
                    //                                 'person_id' => $newPersonID,
                    //                                 'email' => $tmpEmail,
                    //                                 'email_encrypt' => $tmpMd5,
                    //                                 'permission' => 'T',
                    //                                 'zbvalidate' => date('Y-m-d H:i:s'),
                    //                             ]);

                    //                             $_email = $tmpEmail;

                    //                             /** REPORT ANALYTIC */
                    //                                 $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                    //                             /** REPORT ANALYTIC */

                    //                         }

                    //                     }else{
                    //                         $newpersonemail = PersonEmail::create([
                    //                             'person_id' => $newPersonID,
                    //                             'email' => $tmpEmail,
                    //                             'email_encrypt' => $tmpMd5,
                    //                             'permission' => 'T',
                    //                             'zbvalidate' => null,
                    //                         ]);

                    //                         $_email = $tmpEmail;

                    //                     }

                    //                     /** INSERT INTO PERSON_ADDRESSES */
                    //                     $newpersonaddress = PersonAddress::create([
                    //                         'person_id' => $newPersonID,
                    //                         'street' => $_address,
                    //                         'unit' => '',
                    //                         'city' => $_city,
                    //                         'state' => $_state,
                    //                         'zip' => $_zip,
                    //                         'fullAddress' => $_address . ' ' . $_city . ',' . $_state . ' ' . $_zip,
                    //                         'firstReportedDate' => date('Y-m-d'),
                    //                         'lastReportedDate' => date('Y-m-d'),
                    //                     ]);
                    //                     /** INSERT INTO PERSON_ADDRESSES */

                    //                 }

                    //             }
                    //         }

                    //         if (trim($_email) != "") {
                    //             $_ID = $this->generateReportUniqueNumber();

                    //             $new = array(
                    //                 "ID" => $_ID,
                    //                 "Email" => $_email,
                    //                 "Email2" => '',
                    //                 "OriginalMD5" => $md5param,
                    //                 "IP" => '',
                    //                 "Source" => "",
                    //                 "OptInDate" => date('Y-m-d H:i:s'),
                    //                 "ClickDate" => date('Y-m-d H:i:s'),
                    //                 "Referer" => "",
                    //                 "Phone" => $_phone,
                    //                 "Phone2" => '',
                    //                 "FirstName" => $_fname,
                    //                 "LastName" => $_lname,
                    //                 "Address1" => $_address,
                    //                 "Address2" => '',
                    //                 "City" => $_city,
                    //                 "State" => $_state,
                    //                 "Zipcode" => $_zip,
                    //                 "PersonID" => $newPersonID,
                    //                 "Keyword" => $keyword,
                    //                 "Description" => 'TowerDataPostal|NotGetDataEndato',
                    //             );

                    //             array_push($matches,$new);
                    //             return $matches;
                    //         }else{
                    //             return array();
                    //         }
                    //         /** GET FROM MD5 TO QUERY AND GET WHATEVER WE CAN GET */

                    //     }
                    // }else{
                    //     /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    //         $frupdate = FailedRecord::find($failedRecordID);
                    //         $frupdate->description = $frupdate->description . '|NoDataReturnFromPostalTowerData';
                    //         $frupdate->save();
                    //     /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    //     /** RECORD AS FAILURE */
                    //     /*$fr = FailedRecord::create([
                    //         'email_encrypt' => $md5param,
                    //         'leadspeek_api_id' => $leadspeek_api_id,
                    //         'description' => 'No Data Return from Postal Tower Data',
                    //     ]);*/
                    //     return array();
                    //     /** RECORD AS FAILURE */
                    // }
                /** QUERYING TO TOWER DATA WITH DATA POSTAL TO GET SOME OTHER INFORMATION WITH MD5 EMAIL */
            }



        }else{
            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                $frupdate = FailedRecord::find($failedRecordID);
                $frupdate->description = $frupdate->description . '|MatchReidentification:' . $reidentification;
                $frupdate->save();
            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */

            return array();
        }
        /** CHECK ON DATABASE IF EXIST */
    }

    private function checklocationlock($loctarget = "Focus",$_zip = "",$_state = "",$_city = "",$loczip = "",$locstate = "",$locstatesifi = "",$loccity = "",$loccitysifi = "",$nationaltargeting,$failedRecordID) {
        /** CHECK FOR LOCATION LOCK */
        if ($loctarget == "Lock") {
            $validlock = true;
            $validlockState = true;

            $zipdata = explode("-",$_zip);
            $zipdata = strtolower(trim($zipdata[0]));
            $statedata = strtolower(trim($_state));
            $citydata = strtolower(trim($_city));

            $_ziplog = $zipdata;
            $_statedata = $statedata;

            if (trim($zipdata) != "") {
                if ($zipdata && str_contains(strtolower(trim($loczip)), $zipdata)) {
                    $validlock = false;
                }
            }

            if (trim($statedata) != "") {
                if ($statedata && str_contains(strtolower(trim($locstate)), $statedata)) {
                    $validlock = false;
                    $validlockState = false;
                }
            }

            if (trim($citydata) != "") {
                if ($citydata && str_contains(strtolower(trim($loccity)), $citydata)) {
                    /** CHECK THE STATE IF MATCHED CITY */
                    if ($validlockState) {
                        $tmploccity = explode(",",strtolower(trim($loccity)));
                        $sifiCityMatchedID = "";

                        foreach($tmploccity as $index=>$lc) {
                            if ($citydata == $lc) {
                                $sifiCityMatchedID = $loccitysifi[$index];
                                break;
                            }
                        }

                        if ($this->getStatefromCity($sifiCityMatchedID,$statedata)) {
                            $validlock = false;
                        }

                    }else{
                    /** CHECK THE STATE IF MATCHED CITY */
                        $validlock = false;
                    }
                }
            }


            if (trim($loccity) == "" && trim($locstate) == "" && trim($loczip) == "") {
                $validlock = false;
            }
            if ($nationaltargeting == "T") {
                $validlock = false;
            }

            if ($validlock) {
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    $frupdate = FailedRecord::find($failedRecordID);
                    $frupdate->description = $frupdate->description . '|Location Lock : No Match (zip:' . $_ziplog . ' state:' . $_statedata . ')';
                    $frupdate->save();
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
               return true;
            }else{
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    $frupdate = FailedRecord::find($failedRecordID);
                    $frupdate->description = $frupdate->description . '|Location Lock : Match (zip:' . $_ziplog . ' state:' . $_statedata . ')';
                    $frupdate->save();
                /** UPDATE FOR DESCRIPTION ON FAILED RECORD */

                return false;
            }
        }else{
            return false;
        }
        /** CHECK FOR LOCATION LOCK */
    }

    private function dataExistOnDB($personEmail,$persondata,$data,$keyword = "",$dataflow = "",$failedRecordID = "",$md5param = "") {
        date_default_timezone_set('America/Chicago');

        $new = array();
        $personID = $persondata['id'];
        $_ID = $this->generateReportUniqueNumber();
        $_FirstName = $persondata['firstName'];
        $_LastName = $persondata['lastName'];
        $_Email = $personEmail;
        $_Email2 = "";
        $_IP = "";
        $_Source = "";
        $_OptInDate = date('Y-m-d H:i:s');
        $_ClickDate = date('Y-m-d H:i:s');
        $_Referer = "";
        $_Phone = "";
        $_Phone2 = "";
        $_Address1 = "";
        $_Address2 = "";
        $_City = "";
        $_State = "";
        $_Zipcode = "";
        $_Description = $dataflow . "dataExistOnDB|" . $failedRecordID;

        $trackBigBDM = "DATAEXISTONDB";

        /** GET PHONE DATA */
            $personPhone = PersonPhone::where('person_id','=',$personID)->where('permission','=','T')->limit(2)->get();
            if (count($personPhone) > 0) {
                $_Phone = $personPhone[0]['number'];
                $_Phone2 = (isset($personPhone[1]['number']) && $personPhone[1]['number'] != "")?$personPhone[1]['number']:"";
            }
        /** GET PHONE DATA */

        /** GET ADDRESS DATA */
            $personAddress = PersonAddress::where('person_id','=',$personID)->limit(1)->get();
            if (count($personAddress) > 0) {
                $_Address1 = $personAddress[0]['street'];
                $_City = $personAddress[0]['city'];
                $_State = $personAddress[0]['state'];
                $_Zipcode = $personAddress[0]['zip'];
            }
        /** GET ADDRESS DATA */

        /** GET SECOND EMAIL DATA */
            $personEmail = PersonEmail::select('id','email')->where('person_id','=',$personID)->whereEncrypted('email','<>',$personEmail)->limit(1)->get();
            if (count($personEmail) > 0) {
                $_Email2 = $personEmail[0]['email'];
            }
        /** GET SECOND EMAIL DATA */

        /** CHECK ZERO BOUNCE FOR VALID EMAIL */
        // $invalidFirstEmail = false;
        // $invalidSecondEmail = false;

        // if (trim($_Email) != "") {
        //     $zbcheck = $this->zb_validation($_Email,"");
        //     if (isset($zbcheck->status)) {
        //         if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
        //             /** PUT IT ON OPTOUT LIST */
        //             $createoptout = OptoutList::create([
        //                 'email' => $_Email,
        //                 'emailmd5' => md5($_Email),
        //                 'blockedcategory' => 'zbnotvalid',
        //                 'description' => 'Zero Bounce Status : ' . $zbcheck->status,
        //             ]);
        //             /** PUT IT ON OPTOUT LIST */
        //             $invalidFirstEmail = true;

        //             /** REMOVE THE EMAIL FROM DATABASE */
        //             $delEmail = PersonEmail::where('id','=',$persondata['emailID'])->delete();
        //             /** REMOVE THE EMAIL FROM DATABASE */
        //         }else{
        //             $updateEmailValidate = PersonEmail::find($persondata['emailID']);
        //             $updateEmailValidate->zbvalidate = date('Y-m-d H:i:s');
        //             $updateEmailValidate->save();
        //         }
        //     }else{
        //         $_Description = $_Description . "|ZB Error Email1 : " . $zbcheck->error;
        //     }
        // }

        // if (trim($_Email2) != "") {
        //     $zbcheck = $this->zb_validation($_Email2,"");
        //     if (isset($zbcheck->status)) {
        //         if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
        //             /** PUT IT ON OPTOUT LIST */
        //             $createoptout = OptoutList::create([
        //                 'email' => $_Email2,
        //                 'emailmd5' => md5($_Email2),
        //                 'blockedcategory' => 'zbnotvalid',
        //                 'description' => 'Zero Bounce Status : ' . $zbcheck->status,
        //             ]);
        //             /** PUT IT ON OPTOUT LIST */
        //             $invalidSecondEmail = true;

        //             /** REMOVE THE EMAIL FROM DATABASE */
        //             $delEmail = PersonEmail::where('id','=',$personEmail[0]['id'])->delete();
        //             /** REMOVE THE EMAIL FROM DATABASE */
        //         }else{
        //             $updateEmailValidate = PersonEmail::find($personEmail[0]['id']);
        //             $updateEmailValidate->zbvalidate = date('Y-m-d H:i:s');
        //             $updateEmailValidate->save();
        //         }
        //     }else{
        //         $_Description = $_Description . "|ZB Email2 Error : " . $zbcheck->error;
        //     }
        // }

        // if ($invalidFirstEmail == true && $invalidSecondEmail == true) {
        //     $_Email = "";
        //     $_Email2 = "";
        // }else if ($invalidFirstEmail == true && $invalidSecondEmail == false) {
        //     $_Email = $_Email2;
        //     $_Email2 = "";
        // }else if ($invalidFirstEmail == false && $invalidSecondEmail == true) {
        //     $_Email2 = "";
        // }
        /** CHECK ZERO BOUNCE FOR VALID EMAIL */

        $new = array(
            "ID" => $_ID,
			"Email" => $_Email,
            "Email2" => $_Email2,
            "OriginalMD5" => $md5param,
			"IP" => $_IP,
			"Source" => $_Source,
			"OptInDate" => $_OptInDate,
			"ClickDate" => $_ClickDate,
			"Referer" => $_Referer,
			"Phone" => $_Phone,
            "Phone2" => $_Phone2,
			"FirstName" => $_FirstName,
			"LastName" => $_LastName,
			"Address1" => $_Address1,
			"Address2" => $_Address2,
			"City" => $_City,
			"State" => $_State,
			"Zipcode" => $_Zipcode,
            "PersonID" => $personID,
            "Keyword" => $keyword,
            "Description" => $_Description,
            "LeadFrom" => "person"
        );

        /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
            $frupdate = FailedRecord::find($failedRecordID);
            $frupdate->description = $frupdate->description . '|' . $trackBigBDM;
            $frupdate->save();
        /** UPDATE FOR DESCRIPTION ON FAILED RECORD */

        return $new;
    }

    public function process_BDM_TowerDATA($loctarget = 'Focus',$keyword = "",$dataflow = "",$failedRecordID = "",$md5param = "",$leadspeek_api_id = "",$loczip = "",$locstate = "",$locstatesifi = "",$loccity = "",$loccitysifi = "",$nationaltargeting = "F",$leadspeektype = "") {
        date_default_timezone_set('America/Chicago');

        $new = array();

        $_ID = "";
        $_FirstName = "";
        $_LastName = "";
        $_Email = "";
        $_Email2 = "";
        $_IP = "";
        $_Source = "";
        $_OptInDate = date('Y-m-d H:i:s');
        $_ClickDate = date('Y-m-d H:i:s');
        $_Referer = "";
        $_Phone = "";
        $_Phone2 = "";
        $_Address1 = "";
        $_Address2 = "";
        $_City = "";
        $_State = "";
        $_Zipcode = "";
        $_Description = $dataflow . "dataNotExistOnDBBDMTD|" . $failedRecordID;

        $trackBigBDM = "BDMTOWERDATA";

        $bigBDM_MD5 = $this->bigBDM_MD5($md5param,$leadspeek_api_id,$leadspeektype);
        /** IF BIG BDM MD5 HAVE RESULT */
        if (count((array)$bigBDM_MD5) > 0) {

            $trackBigBDM = $trackBigBDM . "->MD5";
            /** REPORT ANALYTIC */
                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'bigbdmemail');
            /** REPORT ANALYTIC */

            foreach ($bigBDM_MD5 as $rd => $a) {
                $bigEmail = (isset($a[0]->Email))?$a[0]->Email:'';
                $bigEmail = explode(",",$bigEmail);

                $bigPhone = (isset($a[0]->Phone))?$a[0]->Phone:'';
                $bigPhone = explode(",",$bigPhone);

                $_FirstName = (isset($a[0]->First_Name))?$a[0]->First_Name:'';
                $_LastName = (isset($a[0]->Last_Name))?$a[0]->Last_Name:'';
                //$_Email = $bigEmail[0];
                //$_Email2 = (isset($bigEmail[1]))?$bigEmail[1]:'';
                $_Phone = $bigPhone[0];
                $_Phone2 = (isset($bigPhone[1]))?$bigPhone[1]:'';
                $_Address1 = (isset($a[0]->Address))?$a[0]->Address:'';
                $_City =  (isset($a[0]->City))?$a[0]->City:'';
                $_State = (isset($a[0]->State))?$a[0]->State:'';
                $_Zipcode = (isset($a[0]->Zip))?$a[0]->Zip:'';
            }

            $uniqueID = uniqid();
            /** INSERT INTO DATABASE PERSON */
                $newPerson = Person::create([
                    'uniqueID' => $uniqueID,
                    'firstName' => $_FirstName,
                    'middleName' => '',
                    'lastName' => $_LastName,
                    'age' => '0',
                    'identityScore' => '0',
                    'lastEntry' => date('Y-m-d H:i:s'),
                ]);

                $newPersonID = $newPerson->id;
            /** INSERT INTO DATABASE PERSON */

            /** SEPARATE BETWEEN YAHOO/AOL AND OTHER EMAIL */
            $filteredEmails = [];
            $otherEmails = [];
            foreach ($bigEmail as $index => $email) {
                if (strpos($email, 'yahoo.com') !== false || strpos($email, 'aol.com') !== false) {
                    $filteredEmails[] = $email;
                } else {
                    $otherEmails[] = $email;
                }
            }
            /** SEPARATE BETWEEN YAHOO/AOL AND OTHER EMAIL */

            /** NEW METHOD TO CHECK AND GET EMAIL */
            foreach($otherEmails as $index => $be) {
                if (trim($be) != "") {
                    $tmpEmail = strtolower(trim($be));
                    $tmpMd5 = md5($tmpEmail);

                    $zbcheck = $this->zb_validation($tmpEmail,"");
                    if (isset($zbcheck->status)) {
                        if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                            /** PUT IT ON OPTOUT LIST */
                            $createoptout = OptoutList::create([
                                'email' => $tmpEmail,
                                'emailmd5' => md5($tmpEmail),
                                'blockedcategory' => 'zbnotvalid',
                                'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email' . $index . 'fromBigMD5',
                            ]);
                            /** PUT IT ON OPTOUT LIST */

                            $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBFailed";
                        }else{
                            $newpersonemail = PersonEmail::create([
                                'person_id' => $newPersonID,
                                'email' => $tmpEmail,
                                'email_encrypt' => $tmpMd5,
                                'permission' => 'T',
                                'zbvalidate' => date('Y-m-d H:i:s'),
                            ]);

                            if ($_Email ==  "") {
                                $_Email = $tmpEmail;
                            }else if ($_Email2 == "") {
                                $_Email2 = $tmpEmail;
                            }

                            $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBSuccess";
                        }
                    }else{
                        $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBNotValidate";
                    }

                }
            }
            /** NEW METHOD TO CHECK AND GET EMAIL */

            /** CHECK IF STANDARD EMAIL NOT GET ANY VALID EMAIL */
            if (trim($_Email) == '') {
                /** NEW METHOD TO CHECK AND GET EMAIL */
                foreach($filteredEmails as $index => $be) {
                    if (trim($be) != "") {
                        $tmpEmail = strtolower(trim($be));
                        $tmpMd5 = md5($tmpEmail);

                        $zbcheck = $this->zb_validation($tmpEmail,"");
                        if (isset($zbcheck->status)) {
                            if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                                /** PUT IT ON OPTOUT LIST */
                                $createoptout = OptoutList::create([
                                    'email' => $tmpEmail,
                                    'emailmd5' => md5($tmpEmail),
                                    'blockedcategory' => 'zbnotvalid',
                                    'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email' . $index . 'fromBigMD5',
                                ]);
                                /** PUT IT ON OPTOUT LIST */

                                $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBFailed";
                            }else{
                                $newpersonemail = PersonEmail::create([
                                    'person_id' => $newPersonID,
                                    'email' => $tmpEmail,
                                    'email_encrypt' => $tmpMd5,
                                    'permission' => 'T',
                                    'zbvalidate' => date('Y-m-d H:i:s'),
                                ]);

                                if ($_Email ==  "") {
                                    $_Email = $tmpEmail;
                                    break;
                                }else if ($_Email2 == "") {
                                    $_Email2 = $tmpEmail;
                                }

                                $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBSuccess";
                            }
                        }else{
                            $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBNotValidate";
                        }

                    }
                }
                /** NEW METHOD TO CHECK AND GET EMAIL */
            }
            /** CHECK IF STANDARD EMAIL NOT GET ANY VALID EMAIL */

            // if (trim($_Email) != '') {
            //     $tmpEmail = strtolower(trim($_Email));
            //     $tmpMd5 = md5($tmpEmail);

            //     $zbcheck = $this->zb_validation($tmpEmail,"");
            //     if (isset($zbcheck->status)) {
            //         if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
            //             /** PUT IT ON OPTOUT LIST */
            //             $createoptout = OptoutList::create([
            //                 'email' => $tmpEmail,
            //                 'emailmd5' => md5($tmpEmail),
            //                 'blockedcategory' => 'zbnotvalid',
            //                 'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email1fromBigMD5',
            //             ]);
            //             /** PUT IT ON OPTOUT LIST */
            //             $_Email = "";

            //             $trackBigBDM = $trackBigBDM . "->Email1ZBFailed";
            //             /** REPORT ANALYTIC */
            //             //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
            //             /** REPORT ANALYTIC */
            //         }else{

            //             $newpersonemail = PersonEmail::create([
            //                 'person_id' => $newPersonID,
            //                 'email' => $tmpEmail,
            //                 'email_encrypt' => $tmpMd5,
            //                 'permission' => 'T',
            //                 'zbvalidate' => date('Y-m-d H:i:s'),
            //             ]);

            //             $_Email = $tmpEmail;

            //             $trackBigBDM = $trackBigBDM . "->Email1ZBSuccess";
            //             /** REPORT ANALYTIC */
            //                 //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
            //             /** REPORT ANALYTIC */

            //         }

            //     }else{
            //         $newpersonemail = PersonEmail::create([
            //             'person_id' => $newPersonID,
            //             'email' => $tmpEmail,
            //             'email_encrypt' => $tmpMd5,
            //             'permission' => 'T',
            //             'zbvalidate' => null,
            //         ]);

            //         $_Email = $tmpEmail;

            //         $trackBigBDM = $trackBigBDM . "->Email1ZBNotValidate";

            //     }
            // }

            // if (trim($_Email2) != '') {
            //     $tmpEmail = strtolower(trim($_Email2));
            //     $tmpMd5 = md5($tmpEmail);

            //     $zbcheck = $this->zb_validation($tmpEmail,"");
            //     if (isset($zbcheck->status)) {
            //         if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
            //             /** PUT IT ON OPTOUT LIST */
            //             $createoptout = OptoutList::create([
            //                 'email' => $tmpEmail,
            //                 'emailmd5' => md5($tmpEmail),
            //                 'blockedcategory' => 'zbnotvalid',
            //                 'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email2fromBigMD5',
            //             ]);
            //             /** PUT IT ON OPTOUT LIST */
            //             $_Email2 = "";

            //             $trackBigBDM = $trackBigBDM . "->Email2ZBFailed";
            //             /** REPORT ANALYTIC */
            //             //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
            //             /** REPORT ANALYTIC */
            //         }else{

            //             $newpersonemail = PersonEmail::create([
            //                 'person_id' => $newPersonID,
            //                 'email' => $tmpEmail,
            //                 'email_encrypt' => $tmpMd5,
            //                 'permission' => 'T',
            //                 'zbvalidate' => date('Y-m-d H:i:s'),
            //             ]);

            //             $_Email2 = $tmpEmail;

            //             $trackBigBDM = $trackBigBDM . "->Email2ZBSuccess";
            //             /** REPORT ANALYTIC */
            //                 //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
            //             /** REPORT ANALYTIC */

            //         }

            //     }else{
            //         $newpersonemail = PersonEmail::create([
            //             'person_id' => $newPersonID,
            //             'email' => $tmpEmail,
            //             'email_encrypt' => $tmpMd5,
            //             'permission' => 'T',
            //             'zbvalidate' => null,
            //         ]);

            //         $_Email2 = $tmpEmail;

            //         $trackBigBDM = $trackBigBDM . "->Email2ZBNotValidate";

            //     }
            // }

            // if (trim($_Email) == "" && trim($_Email2) != "") {
            //     $_Email = $_Email2;
            //     $_Email2 = "";
            // }

            if (trim($_Email) == "" && trim($_Email2) == "") {
                /** REPORT ANALYTIC */
                    $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                    $trackBigBDM = $trackBigBDM . "->Email1andEmail2NotValid";
                /** REPORT ANALYTIC */
            }else{
                /** REPORT ANALYTIC */
                    $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                    $trackBigBDM = $trackBigBDM . "->Email1orEmail2Valid";
                /** REPORT ANALYTIC */
            }

            if (trim($_Phone) != "") {
                /** INSERT PERSON_PHONES */
                $newpersonphone = PersonPhone::create([
                    'person_id' => $newPersonID,
                    'number' => $this->format_phone($_Phone),
                    'type' => 'user',
                    'isConnected' => 'T',
                    'firstReportedDate' => date('Y-m-d'),
                    'lastReportedDate' => date('Y-m-d'),
                    'permission' => 'F',
                ]);
                /** INSERT PERSON_PHONES */
            }

            if (trim($_Phone2) != "") {
                /** INSERT PERSON_PHONES */
                $newpersonphone = PersonPhone::create([
                    'person_id' => $newPersonID,
                    'number' => $this->format_phone($_Phone2),
                    'type' => 'user',
                    'isConnected' => 'T',
                    'firstReportedDate' => date('Y-m-d'),
                    'lastReportedDate' => date('Y-m-d'),
                    'permission' => 'F',
                ]);
                /** INSERT PERSON_PHONES */
            }


            /** INSERT INTO PERSON_ADDRESSES */
            $newpersonaddress = PersonAddress::create([
                'person_id' => $newPersonID,
                'street' => $_Address1,
                'unit' => '',
                'city' => $_City,
                'state' => $_State,
                'zip' => $_Zipcode,
                'fullAddress' => $_Address1 . ' ' . $_City . ',' . $_State . ' ' . $_Zipcode,
                'firstReportedDate' => date('Y-m-d'),
                'lastReportedDate' => date('Y-m-d'),
            ]);
            /** INSERT INTO PERSON_ADDRESSES */

            $_ID = $this->generateReportUniqueNumber();

            $new = array(
                "ID" => $_ID,
                "Email" => $_Email,
                "Email2" => $_Email2,
                "OriginalMD5" => $md5param,
                "IP" => $_IP,
                "Source" => $_Source,
                "OptInDate" => $_OptInDate,
                "ClickDate" => $_ClickDate,
                "Referer" => $_Referer,
                "Phone" => $_Phone,
                "Phone2" => $_Phone2,
                "FirstName" => $_FirstName,
                "LastName" => $_LastName,
                "Address1" => $_Address1,
                "Address2" => $_Address2,
                "City" => $_City,
                "State" => $_State,
                "Zipcode" => $_Zipcode,
                "PersonID" => $newPersonID,
                "Keyword" => $keyword,
                "Description" => $_Description,
                "LeadFrom" => "bigbdmmd5"
            );

            /** IF BIG BDM MD5 HAVE RESULT */
        }else{
                /** BIG BDM NO RESULT CHECK TOWER DATA */
                if (isset($md5param) && trim($md5param) != "") {
                    $tower = $this->getTowerData("postal",$md5param,$leadspeek_api_id,$leadspeektype);

                    /* EMPTY TO FETCH DATA IN getTowerData FUNCTION */
                    if(count((array) $tower) == 0) {
                        $tower = "";
                    }
                    /* EMPTY TO FETCH DATA IN getTowerData FUNCTION */
                }

                if (isset($md5param) && trim($md5param) != "" && isset($tower->postal_address)) {
                    $_fname = $tower->postal_address->first_name;
                    $_lname = $tower->postal_address->last_name;
                    $_email = "";
                    $_phone = "";
                    $_address = $tower->postal_address->address;
                    $_city = $tower->postal_address->city;
                    $_state = $tower->postal_address->state;
                    $_zip = $tower->postal_address->zip;

                    $trackBigBDM = $trackBigBDM . "->TDPostal";
                    /** REPORT ANALYTIC */
                        $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'towerpostal');
                    /** REPORT ANALYTIC */

                    /** CHECK WITH BIG BDM PII */
                        $bigBDM_PII = $this->bigBDM_PII($_fname,$_lname,$_address,$_zip,$md5param,$leadspeek_api_id,$leadspeektype);

                        /** IF BIG BDM PII HAVE RESULT */
                        if (count((array)$bigBDM_PII) > 0) {

                            $trackBigBDM = $trackBigBDM . "->BIGPII";
                            /** REPORT ANALYTIC */
                                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'bigbdmpii');
                            /** REPORT ANALYTIC */

                            foreach ($bigBDM_PII as $rd => $a) {
                                $bigEmail = (isset($a[0]->Email))?$a[0]->Email:'';
                                $bigEmail = explode(",",$bigEmail);

                                $bigPhone = (isset($a[0]->Phone))?$a[0]->Phone:'';
                                $bigPhone = explode(",",$bigPhone);

                                $_FirstName = (isset($a[0]->First_Name))?$a[0]->First_Name:'';
                                $_LastName = (isset($a[0]->Last_Name))?$a[0]->Last_Name:'';
                                //$_Email = $bigEmail[0];
                                //$_Email2 = (isset($bigEmail[1]))?$bigEmail[1]:'';
                                $_Phone = $bigPhone[0];
                                $_Phone2 = (isset($bigPhone[1]))?$bigPhone[1]:'';
                                $_Address1 = (isset($a[0]->Address))?$a[0]->Address:'';
                                $_City =  (isset($a[0]->City))?$a[0]->City:'';
                                $_State = (isset($a[0]->State))?$a[0]->State:'';
                                $_Zipcode = (isset($a[0]->Zip))?$a[0]->Zip:'';
                            }

                            $uniqueID = uniqid();
                            /** INSERT INTO DATABASE PERSON */
                                $newPerson = Person::create([
                                    'uniqueID' => $uniqueID,
                                    'firstName' => $_FirstName,
                                    'middleName' => '',
                                    'lastName' => $_LastName,
                                    'age' => '0',
                                    'identityScore' => '0',
                                    'lastEntry' => date('Y-m-d H:i:s'),
                                ]);

                                $newPersonID = $newPerson->id;
                            /** INSERT INTO DATABASE PERSON */

                            /** SEPARATE BETWEEN YAHOO/AOL AND OTHER EMAIL */
                            $filteredEmails = [];
                            $otherEmails = [];
                            foreach ($bigEmail as $index => $email) {
                                if (strpos($email, 'yahoo.com') !== false || strpos($email, 'aol.com') !== false) {
                                    $filteredEmails[] = $email;
                                } else {
                                    $otherEmails[] = $email;
                                }
                            }
                            /** SEPARATE BETWEEN YAHOO/AOL AND OTHER EMAIL */

                            /** NEW METHOD TO CHECK AND GET EMAIL */
                            foreach($otherEmails as $index => $be) {
                                if (trim($be) != "") {
                                    $tmpEmail = strtolower(trim($be));
                                    $tmpMd5 = md5($tmpEmail);

                                    $zbcheck = $this->zb_validation($tmpEmail,"");
                                    if (isset($zbcheck->status)) {
                                        if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                                            /** PUT IT ON OPTOUT LIST */
                                            $createoptout = OptoutList::create([
                                                'email' => $tmpEmail,
                                                'emailmd5' => md5($tmpEmail),
                                                'blockedcategory' => 'zbnotvalid',
                                                'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email' . $index . 'fromBigMD5',
                                            ]);
                                            /** PUT IT ON OPTOUT LIST */

                                            $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBFailed";
                                        }else{
                                            $newpersonemail = PersonEmail::create([
                                                'person_id' => $newPersonID,
                                                'email' => $tmpEmail,
                                                'email_encrypt' => $tmpMd5,
                                                'permission' => 'T',
                                                'zbvalidate' => date('Y-m-d H:i:s'),
                                            ]);

                                            if ($_Email ==  "") {
                                                $_Email = $tmpEmail;
                                            }else if ($_Email2 == "") {
                                                $_Email2 = $tmpEmail;
                                            }

                                            $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBSuccess";
                                        }
                                    }else{
                                        $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBNotValidate";
                                    }

                                }
                            }
                            /** NEW METHOD TO CHECK AND GET EMAIL */

                            /** CHECK IF STANDARD EMAIL NOT GET ANY VALID EMAIL */
                            if (trim($_Email) == '') {
                                /** NEW METHOD TO CHECK AND GET EMAIL */
                                foreach($filteredEmails as $index => $be) {
                                    if (trim($be) != "") {
                                        $tmpEmail = strtolower(trim($be));
                                        $tmpMd5 = md5($tmpEmail);

                                        $zbcheck = $this->zb_validation($tmpEmail,"");
                                        if (isset($zbcheck->status)) {
                                            if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                                                /** PUT IT ON OPTOUT LIST */
                                                $createoptout = OptoutList::create([
                                                    'email' => $tmpEmail,
                                                    'emailmd5' => md5($tmpEmail),
                                                    'blockedcategory' => 'zbnotvalid',
                                                    'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email' . $index . 'fromBigMD5',
                                                ]);
                                                /** PUT IT ON OPTOUT LIST */

                                                $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBFailed";
                                            }else{
                                                $newpersonemail = PersonEmail::create([
                                                    'person_id' => $newPersonID,
                                                    'email' => $tmpEmail,
                                                    'email_encrypt' => $tmpMd5,
                                                    'permission' => 'T',
                                                    'zbvalidate' => date('Y-m-d H:i:s'),
                                                ]);

                                                if ($_Email ==  "") {
                                                    $_Email = $tmpEmail;
                                                    break;
                                                }else if ($_Email2 == "") {
                                                    $_Email2 = $tmpEmail;
                                                }

                                                $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBSuccess";
                                            }
                                        }else{
                                            $trackBigBDM = $trackBigBDM . "->Email" . $index . "ZBNotValidate";
                                        }

                                    }
                                }
                                /** NEW METHOD TO CHECK AND GET EMAIL */
                            }
                            /** CHECK IF STANDARD EMAIL NOT GET ANY VALID EMAIL */

                            // if (trim($_Email) != '') {
                            //     $tmpEmail = strtolower(trim($_Email));
                            //     $tmpMd5 = md5($tmpEmail);

                            //     $zbcheck = $this->zb_validation($tmpEmail,"");
                            //     if (isset($zbcheck->status)) {
                            //         if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                            //             /** PUT IT ON OPTOUT LIST */
                            //             $createoptout = OptoutList::create([
                            //                 'email' => $tmpEmail,
                            //                 'emailmd5' => md5($tmpEmail),
                            //                 'blockedcategory' => 'zbnotvalid',
                            //                 'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email1fromBigPII',
                            //             ]);
                            //             /** PUT IT ON OPTOUT LIST */
                            //             $_Email = "";

                            //             $trackBigBDM = $trackBigBDM . "->Email1ZBFailed";
                            //             /** REPORT ANALYTIC */
                            //             //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                            //             /** REPORT ANALYTIC */
                            //         }else{

                            //             $newpersonemail = PersonEmail::create([
                            //                 'person_id' => $newPersonID,
                            //                 'email' => $tmpEmail,
                            //                 'email_encrypt' => $tmpMd5,
                            //                 'permission' => 'T',
                            //                 'zbvalidate' => date('Y-m-d H:i:s'),
                            //             ]);

                            //             $_Email = $tmpEmail;

                            //             $trackBigBDM = $trackBigBDM . "->Email1ZBSuccess";
                            //             /** REPORT ANALYTIC */
                            //                 //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                            //             /** REPORT ANALYTIC */

                            //         }

                            //     }else{
                            //         $newpersonemail = PersonEmail::create([
                            //             'person_id' => $newPersonID,
                            //             'email' => $tmpEmail,
                            //             'email_encrypt' => $tmpMd5,
                            //             'permission' => 'T',
                            //             'zbvalidate' => null,
                            //         ]);

                            //         $_Email = $tmpEmail;
                            //         $trackBigBDM = $trackBigBDM . "->Email1ZBNotValidate";

                            //     }
                            // }

                            // if (trim($_Email2) != '') {
                            //     $tmpEmail = strtolower(trim($_Email2));
                            //     $tmpMd5 = md5($tmpEmail);

                            //     $zbcheck = $this->zb_validation($tmpEmail,"");
                            //     if (isset($zbcheck->status)) {
                            //         if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                            //             /** PUT IT ON OPTOUT LIST */
                            //             $createoptout = OptoutList::create([
                            //                 'email' => $tmpEmail,
                            //                 'emailmd5' => md5($tmpEmail),
                            //                 'blockedcategory' => 'zbnotvalid',
                            //                 'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email2fromBigPII',
                            //             ]);
                            //             /** PUT IT ON OPTOUT LIST */
                            //             $_Email2 = "";

                            //             $trackBigBDM = $trackBigBDM . "->Email2ZBFailed";
                            //             /** REPORT ANALYTIC */
                            //             //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                            //             /** REPORT ANALYTIC */
                            //         }else{

                            //             $newpersonemail = PersonEmail::create([
                            //                 'person_id' => $newPersonID,
                            //                 'email' => $tmpEmail,
                            //                 'email_encrypt' => $tmpMd5,
                            //                 'permission' => 'T',
                            //                 'zbvalidate' => date('Y-m-d H:i:s'),
                            //             ]);

                            //             $_Email2 = $tmpEmail;

                            //             $trackBigBDM = $trackBigBDM . "->Email2ZBSuccess";
                            //             /** REPORT ANALYTIC */
                            //                 //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                            //             /** REPORT ANALYTIC */

                            //         }

                            //     }else{
                            //         $newpersonemail = PersonEmail::create([
                            //             'person_id' => $newPersonID,
                            //             'email' => $tmpEmail,
                            //             'email_encrypt' => $tmpMd5,
                            //             'permission' => 'T',
                            //             'zbvalidate' => null,
                            //         ]);

                            //         $_Email2 = $tmpEmail;
                            //         $trackBigBDM = $trackBigBDM . "->Email2ZBNotValidate";

                            //     }
                            // }

                            // if (trim($_Email) == "" && trim($_Email2) != "") {
                            //     $_Email = $_Email2;
                            //     $_Email2 = "";
                            // }

                            if (trim($_Email) == "" && trim($_Email2) == "") {
                                /** REPORT ANALYTIC */
                                    $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                                    $trackBigBDM = $trackBigBDM . "->Email1andEmail2NotValid";
                                /** REPORT ANALYTIC */
                            }else{
                                /** REPORT ANALYTIC */
                                    $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                                    $trackBigBDM = $trackBigBDM . "->Email1orEmail2Valid";
                                /** REPORT ANALYTIC */
                            }

                            if (trim($_Phone) != "") {
                                /** INSERT PERSON_PHONES */
                                $newpersonphone = PersonPhone::create([
                                    'person_id' => $newPersonID,
                                    'number' => $this->format_phone($_Phone),
                                    'type' => 'user',
                                    'isConnected' => 'T',
                                    'firstReportedDate' => date('Y-m-d'),
                                    'lastReportedDate' => date('Y-m-d'),
                                    'permission' => 'F',
                                ]);
                                /** INSERT PERSON_PHONES */
                            }

                            if (trim($_Phone2) != "") {
                                /** INSERT PERSON_PHONES */
                                $newpersonphone = PersonPhone::create([
                                    'person_id' => $newPersonID,
                                    'number' => $this->format_phone($_Phone2),
                                    'type' => 'user',
                                    'isConnected' => 'T',
                                    'firstReportedDate' => date('Y-m-d'),
                                    'lastReportedDate' => date('Y-m-d'),
                                    'permission' => 'F',
                                ]);
                                /** INSERT PERSON_PHONES */
                            }


                            /** INSERT INTO PERSON_ADDRESSES */
                            $newpersonaddress = PersonAddress::create([
                                'person_id' => $newPersonID,
                                'street' => $_Address1,
                                'unit' => '',
                                'city' => $_City,
                                'state' => $_State,
                                'zip' => $_Zipcode,
                                'fullAddress' => $_Address1 . ' ' . $_City . ',' . $_State . ' ' . $_Zipcode,
                                'firstReportedDate' => date('Y-m-d'),
                                'lastReportedDate' => date('Y-m-d'),
                            ]);
                            /** INSERT INTO PERSON_ADDRESSES */

                            $_ID = $this->generateReportUniqueNumber();

                            $new = array(
                                "ID" => $_ID,
                                "Email" => $_Email,
                                "Email2" => $_Email2,
                                "OriginalMD5" => $md5param,
                                "IP" => $_IP,
                                "Source" => $_Source,
                                "OptInDate" => $_OptInDate,
                                "ClickDate" => $_ClickDate,
                                "Referer" => $_Referer,
                                "Phone" => $_Phone,
                                "Phone2" => $_Phone2,
                                "FirstName" => $_FirstName,
                                "LastName" => $_LastName,
                                "Address1" => $_Address1,
                                "Address2" => $_Address2,
                                "City" => $_City,
                                "State" => $_State,
                                "Zipcode" => $_Zipcode,
                                "PersonID" => $newPersonID,
                                "Keyword" => $keyword,
                                "Description" => $_Description,
                                "LeadFrom" => "gettowerdata"
                            );

                            /** IF BIG BDM PII HAVE RESULT */

                        }else{

                            $trackBigBDM = $trackBigBDM . "->BDMPIINoData";
                            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                                $frupdate = FailedRecord::find($failedRecordID);
                                $frupdate->description = $frupdate->description . '|NoDataReturnFromBigBDMPII';
                                $frupdate->save();
                            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                            $new = array();
                        }
                    /** CHECK WITH BIG BDM PII */

                }else{

                    $trackBigBDM = $trackBigBDM . "->TDPostalNoData";
                    /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                        $frupdate = FailedRecord::find($failedRecordID);
                        $frupdate->description = $frupdate->description . '|NoDataReturnFromPostalTowerData';
                        $frupdate->save();
                    /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                    $new = array();
                    /** RECORD AS FAILURE */
                }
                /** BIG BDM NO RESULT CHECK TOWER DATA */
        }

        if (count($new) > 0) {
            /** CHECK FOR LOCATION LOCK */
                $chkloc = $this->checklocationlock($loctarget,$new['Zipcode'],$new['State'],$new['City'],$loczip,$locstate,$locstatesifi,$loccity,$loccitysifi,$nationaltargeting,$failedRecordID);
                if ($chkloc) {
                    $trackBigBDM = $trackBigBDM . "->LocationLockFailed";
                    /** REPORT ANALYTIC */
                    $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlockfailed');
                    /** REPORT ANALYTIC */
                    $new = array();
                }else{
                    $trackBigBDM = $trackBigBDM . "->LocationLockSuccess";
                    /** REPORT ANALYTIC */
                    $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'locationlock');
                    /** REPORT ANALYTIC */
                }
            /** CHECK FOR LOCATION LOCK */
        }

            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
                $frupdate = FailedRecord::find($failedRecordID);
                $frupdate->description = $frupdate->description . '|' . $trackBigBDM;
                $frupdate->save();
            /** UPDATE FOR DESCRIPTION ON FAILED RECORD */

        return $new;

    }

    private function dataNotExistOnDBBIG($_fname,$_lname,$_email,$_phone,$_address,$_city,$_state,$_zip,$personID = "",$keyword = "",$dataflow = "",$failedRecordID = "",$md5param = "",$leadspeek_api_id = "",$leadspeektype = "") {
        date_default_timezone_set('America/Chicago');

        $new = array();

        $_ID = "";
        $_FirstName = "";
        $_LastName = "";
        $_Email = "";
        $_Email2 = "";
        $_IP = "";
        $_Source = "";
        $_OptInDate = date('Y-m-d H:i:s');
        $_ClickDate = date('Y-m-d H:i:s');
        $_Referer = "";
        $_Phone = "";
        $_Phone2 = "";
        $_Address1 = "";
        $_Address2 = "";
        $_City = "";
        $_State = "";
        $_Zipcode = "";
        $_Description = $dataflow . "dataNotExistOnDBBIG|" . $failedRecordID;

        $trackBigBDM = "NOTEXISTONDBBIG";

        if ($personID != "") {
            /** GET PHONE DATA */
                $personPhone = PersonPhone::where('person_id','=',$personID)->where('permission','=','T')->limit(1)->get();
                if (count($personPhone) > 0) {
                    $_phone = $personPhone[0]['number'];
                }
            /** GET PHONE DATA */

            /** GET ADDRESS DATA */
                $personAddress = PersonAddress::where('person_id','=',$personID)->limit(1)->get();
                if (count($personAddress) > 0) {
                    $_address = $personAddress[0]['street'];
                    $_city = $personAddress[0]['city'];
                    $_state = $personAddress[0]['state'];
                    $_zip = $personAddress[0]['zip'];
                }
            /** GET ADDRESS DATA */
        }

        /** CHECK WITH BIG BDM PII */
        $bigBDM_PII = $this->bigBDM_PII($_fname,$_lname,$_address,$_zip);

        /** IF BIG BDM PII HAVE RESULT */
        if (count((array)$bigBDM_PII) > 0) {

            /** REPORT ANALYTIC */
            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'bigbdmpii');
            /** REPORT ANALYTIC */
            $trackBigBDM = $trackBigBDM . "->BIGPII";

            foreach ($bigBDM_PII as $rd => $a) {
                $bigEmail = (isset($a[0]->Email))?$a[0]->Email:'';
                $bigEmail = explode(",",$bigEmail);

                $bigPhone = (isset($a[0]->Phone))?$a[0]->Phone:'';
                $bigPhone = explode(",",$bigPhone);

                $_FirstName = (isset($a[0]->First_Name))?$a[0]->First_Name:'';
                $_LastName = (isset($a[0]->Last_Name))?$a[0]->Last_Name:'';
                $_Email = $bigEmail[0];
                $_Email2 = (isset($bigEmail[1]))?$bigEmail[1]:'';
                $_Phone = $bigPhone[0];
                $_Phone2 = (isset($bigPhone[1]))?$bigPhone[1]:'';
                $_Address1 = (isset($a[0]->Address))?$a[0]->Address:'';
                $_City =  (isset($a[0]->City))?$a[0]->City:'';
                $_State = (isset($a[0]->State))?$a[0]->State:'';
                $_Zipcode = (isset($a[0]->Zip))?$a[0]->Zip:'';
            }

            if ($personID != "") {
                /** CLEAN UP CURRENT DATABASE AND UPDATE NEW ONE */
                $pad = PersonAddress::where('person_id','=',$personID)->delete();
                $pname = PersonName::where('person_id','=',$personID)->delete();
                $pphone = PersonPhone::where('person_id','=',$personID)->delete();
                $pemail = PersonEmail::where('person_id','=',$personID)->delete();
                $p = Person::where('id','=',$personID)->delete();
                /** CLEAN UP CURRENT DATABASE AND UPDATE NEW ONE */
            }

            $uniqueID = uniqid();
            /** INSERT INTO DATABASE PERSON */
                $newPerson = Person::create([
                    'uniqueID' => $uniqueID,
                    'firstName' => $_FirstName,
                    'middleName' => '',
                    'lastName' => $_LastName,
                    'age' => '0',
                    'identityScore' => '0',
                    'lastEntry' => date('Y-m-d H:i:s'),
                ]);

                $newPersonID = $newPerson->id;
            /** INSERT INTO DATABASE PERSON */

                if (trim($_Email) != '') {
                    $tmpEmail = strtolower(trim($_Email));
                    $tmpMd5 = md5($tmpEmail);

                    $zbcheck = $this->zb_validation($tmpEmail,"");
                    if (isset($zbcheck->status)) {
                        if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                            /** PUT IT ON OPTOUT LIST */
                            $createoptout = OptoutList::create([
                                'email' => $tmpEmail,
                                'emailmd5' => md5($tmpEmail),
                                'blockedcategory' => 'zbnotvalid',
                                'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email1fromBigPIIDataNotExist',
                            ]);
                            /** PUT IT ON OPTOUT LIST */
                            $_Email = "";

                            /** REPORT ANALYTIC */
                            //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                            /** REPORT ANALYTIC */

                             $trackBigBDM = $trackBigBDM . "->Email1ZBFailed";
                        }else{

                            $newpersonemail = PersonEmail::create([
                                'person_id' => $newPersonID,
                                'email' => $tmpEmail,
                                'email_encrypt' => $tmpMd5,
                                'permission' => 'T',
                                'zbvalidate' => date('Y-m-d H:i:s'),
                            ]);

                            $_Email = $tmpEmail;

                            /** REPORT ANALYTIC */
                                //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                            /** REPORT ANALYTIC */
                             $trackBigBDM = $trackBigBDM . "->Email1ZBSuccess";
                        }

                    }else{
                        $newpersonemail = PersonEmail::create([
                            'person_id' => $newPersonID,
                            'email' => $tmpEmail,
                            'email_encrypt' => $tmpMd5,
                            'permission' => 'T',
                            'zbvalidate' => null,
                        ]);

                        $_Email = $tmpEmail;
                        $trackBigBDM = $trackBigBDM . "->Email1ZBNotValidate";
                    }
                }

                if (trim($_Email2) != '') {
                    $tmpEmail = strtolower(trim($_Email2));
                    $tmpMd5 = md5($tmpEmail);

                    $zbcheck = $this->zb_validation($tmpEmail,"");
                    if (isset($zbcheck->status)) {
                        if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                            /** PUT IT ON OPTOUT LIST */
                            $createoptout = OptoutList::create([
                                'email' => $tmpEmail,
                                'emailmd5' => md5($tmpEmail),
                                'blockedcategory' => 'zbnotvalid',
                                'description' => 'Zero Bounce Status. : ' . $zbcheck->status . '|Email2fromBigPIIDataNotExist',
                            ]);
                            /** PUT IT ON OPTOUT LIST */
                            $_Email2 = "";

                            /** REPORT ANALYTIC */
                            //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                            /** REPORT ANALYTIC */
                             $trackBigBDM = $trackBigBDM . "->Email2ZBFailed";

                        }else{

                            $newpersonemail = PersonEmail::create([
                                'person_id' => $newPersonID,
                                'email' => $tmpEmail,
                                'email_encrypt' => $tmpMd5,
                                'permission' => 'T',
                                'zbvalidate' => date('Y-m-d H:i:s'),
                            ]);

                            $_Email2 = $tmpEmail;

                            /** REPORT ANALYTIC */
                                //$this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                            /** REPORT ANALYTIC */
                             $trackBigBDM = $trackBigBDM . "->Email2ZBSuccess";
                        }

                    }else{
                        $newpersonemail = PersonEmail::create([
                            'person_id' => $newPersonID,
                            'email' => $tmpEmail,
                            'email_encrypt' => $tmpMd5,
                            'permission' => 'T',
                            'zbvalidate' => null,
                        ]);

                        $_Email2 = $tmpEmail;
                         $trackBigBDM = $trackBigBDM . "->Email2ZBNotValidate";
                    }
                }

                if (trim($_Email) == "" && trim($_Email2) != "") {
                    $_Email = $_Email2;
                    $_Email2 = "";
                }

                if (trim($_Email) == "" && trim($_Email2) == "") {
                    /** REPORT ANALYTIC */
                        $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                        $trackBigBDM = $trackBigBDM . "->Email1andEmail2NotValid";
                    /** REPORT ANALYTIC */
                }else{
                    /** REPORT ANALYTIC */
                        $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                        $trackBigBDM = $trackBigBDM . "->Email1orEmail2Valid";
                    /** REPORT ANALYTIC */
                }

                if (trim($_Phone) != "") {
                    /** INSERT PERSON_PHONES */
                    $newpersonphone = PersonPhone::create([
                        'person_id' => $newPersonID,
                        'number' => $this->format_phone($_Phone),
                        'type' => 'user',
                        'isConnected' => 'T',
                        'firstReportedDate' => date('Y-m-d'),
                        'lastReportedDate' => date('Y-m-d'),
                        'permission' => 'F',
                    ]);
                    /** INSERT PERSON_PHONES */
                }

                if (trim($_Phone2) != "") {
                    /** INSERT PERSON_PHONES */
                    $newpersonphone = PersonPhone::create([
                        'person_id' => $newPersonID,
                        'number' => $this->format_phone($_Phone2),
                        'type' => 'user',
                        'isConnected' => 'T',
                        'firstReportedDate' => date('Y-m-d'),
                        'lastReportedDate' => date('Y-m-d'),
                        'permission' => 'F',
                    ]);
                    /** INSERT PERSON_PHONES */
                }


                /** INSERT INTO PERSON_ADDRESSES */
                $newpersonaddress = PersonAddress::create([
                    'person_id' => $newPersonID,
                    'street' => $_Address1,
                    'unit' => '',
                    'city' => $_City,
                    'state' => $_State,
                    'zip' => $_Zipcode,
                    'fullAddress' => $_Address1 . ' ' . $_City . ',' . $_State . ' ' . $_Zipcode,
                    'firstReportedDate' => date('Y-m-d'),
                    'lastReportedDate' => date('Y-m-d'),
                ]);
                /** INSERT INTO PERSON_ADDRESSES */

                $_ID = $this->generateReportUniqueNumber();

                $new = array(
                    "ID" => $_ID,
                    "Email" => $_Email,
                    "Email2" => $_Email2,
                    "OriginalMD5" => $md5param,
                    "IP" => $_IP,
                    "Source" => $_Source,
                    "OptInDate" => $_OptInDate,
                    "ClickDate" => $_ClickDate,
                    "Referer" => $_Referer,
                    "Phone" => $_Phone,
                    "Phone2" => $_Phone2,
                    "FirstName" => $_FirstName,
                    "LastName" => $_LastName,
                    "Address1" => $_Address1,
                    "Address2" => $_Address2,
                    "City" => $_City,
                    "State" => $_State,
                    "Zipcode" => $_Zipcode,
                    "PersonID" => $newPersonID,
                    "Keyword" => $keyword,
                    "Description" => $_Description,
                );

            /** IF BIG BDM PII HAVE RESULT */
        }else{
            $new = array();
            $trackBigBDM = $trackBigBDM . "->BIGBDMPIINOTFOUND";
        }

        /** UPDATE FOR DESCRIPTION ON FAILED RECORD */
            $frupdate = FailedRecord::find($failedRecordID);
            $frupdate->description = $frupdate->description . '|' . $trackBigBDM;
            $frupdate->save();
        /** UPDATE FOR DESCRIPTION ON FAILED RECORD */

        return $new;
    }

    private function dataNotExistOnDB($_fname,$_lname,$_email,$_phone,$_address,$_city,$_state,$_zip,$personID = "",$keyword = "",$dataflow = "",$failedRecordID = "",$md5param = "",$leadspeek_api_id = "",$leadspeektype = "") {
        date_default_timezone_set('America/Chicago');

        $new = array();

        $_ID = "";
        $_FirstName = $_fname;
        $_LastName = $_lname;
        $_Email = $_email;
        $_Email2 = "";
        $_IP = "";
        $_Source = "";
        $_OptInDate = date('Y-m-d H:i:s');
        $_ClickDate = date('Y-m-d H:i:s');
        $_Referer = "";
        $_Phone = "";
        $_Phone2 = "";
        $_Address1 = "";
        $_Address2 = "";
        $_City = "";
        $_State = "";
        $_Zipcode = "";
        $_Description = $dataflow . "dataNotExistOnDB|" . $failedRecordID;

        if ($personID != "") {
            /** GET PHONE DATA */
                $personPhone = PersonPhone::where('person_id','=',$personID)->where('permission','=','T')->limit(1)->get();
                if (count($personPhone) > 0) {
                    $_phone = $personPhone[0]['number'];
                }
            /** GET PHONE DATA */

            /** GET ADDRESS DATA */
                $personAddress = PersonAddress::where('person_id','=',$personID)->limit(1)->get();
                if (count($personAddress) > 0) {
                    $_address = $personAddress[0]['street'];
                    $_city = $personAddress[0]['city'];
                    $_state = $personAddress[0]['state'];
                    $_zip = $personAddress[0]['zip'];
                }
            /** GET ADDRESS DATA */
        }

        $de = $this->getDataEnrichment($_fname,$_lname,$_email,$_phone,$_address,$_city,$_state,$_zip);

        if (isset($de->message) && $de->message == "" && $de->isError == false) {

            if ($personID != "") {
                /** CLEAN UP CURRENT DATABASE AND UPDATE NEW ONE */
                $pad = PersonAddress::where('person_id','=',$personID)->delete();
                $pname = PersonName::where('person_id','=',$personID)->delete();
                $pphone = PersonPhone::where('person_id','=',$personID)->delete();
                $pemail = PersonEmail::where('person_id','=',$personID)->delete();
                $p = Person::where('id','=',$personID)->delete();
                /** CLEAN UP CURRENT DATABASE AND UPDATE NEW ONE */
            }

            /** GET PERSON DATA */
                $p_Age = $de->person->age;
                $p_firstName = $de->person->name->firstName;
                $p_middleName = $de->person->name->middleName;
                $p_lastName = $de->person->name->lastName;
                $p_identityScore = $de->identityScore;
            /** GET PERSON DATA */

            $uniqueID = uniqid();
            /** INSERT INTO DATABASE PERSON */
                $newPerson = Person::create([
                    'uniqueID' => $uniqueID,
                    'firstName' => $_fname,
                    'middleName' => '',
                    'lastName' => $_lname,
                    'age' => $p_Age,
                    'identityScore' => $p_identityScore,
                    'lastEntry' => date('Y-m-d H:i:s'),
                ]);

                $newPersonID = $newPerson->id;
            /** INSERT INTO DATABASE PERSON */

            $_ID = $this->generateReportUniqueNumber();
            $_FirstName = $_fname;
            $_LastName = $_lname;


            /** INSERT INTO PERSON NAMES */
                $newPersonName = PersonName::create([
                    'person_id' => $newPersonID,
                    'first_name' => $p_firstName,
                    'middle_name' => $p_middleName,
                    'last_name' => $p_lastName,
                    'endato_result' => 'T',
                ]);

                if (strtolower($_fname) != strtolower($p_firstName) || strtolower($_lname) != strtolower($p_lastName)) {
                    $newPersonName = PersonName::create([
                        'person_id' => $newPersonID,
                        'first_name' => $_fname,
                        'middle_name' => '',
                        'last_name' => $_lname,
                        'endato_result' => 'F',
                    ]);
                }
            /** INSERT INTO PERSON NAMES */

            /** GET EMAIL DATA*/
            $dataexist = false;
            $firstEmail = "";
            $secondEmail = "";
            $priorityEmail = "";
            $trackFirstEmail = "Email1fromED";

            $no = 0;
                foreach($de->person->emails as $em) {
                    $email = $em->email;
                    if ($no == 0) {
                        $firstEmail = $email;
                        $no++;
                    }else if ($no == 1) {
                        $secondEmail = $email;
                        $no++;
                    }
                    /** INSERT INTO PERSON_EMAILS */
                    /*$newpersonemail = PersonEmail::create([
                        'person_id' => $newPersonID,
                        'email' => $email,
                        'email_encrypt' => md5($email),
                        'permission' => 'T',
                    ]);*/
                    /** INSERT INTO PERSON_EMAILS */

                    /*if (trim($_email) == trim($email)) {
                        $dataexist = true;
                    }*/

                    if (trim($md5param) == trim(md5($email))) {
                        $priorityEmail = $email;
                    }
                }

                /** CHECK IF PRIORITY EMAIL STILL EMPTY THEN TRY TO GET FROM TOWER DATA */
                if ($priorityEmail == "" && isset($md5param) && trim($md5param) != "") {
                    $tower = $this->getTowerData("md5",$md5param);
                    if (isset($tower->target_email)) {
                        if ($tower->target_email != "") {
                            $tmpEmail = strtolower(trim($tower->target_email));
                            $tmpMd5 = md5($tmpEmail);

                            $secondEmail = $firstEmail;
                            $firstEmail = $tmpEmail;
                            $trackFirstEmail = "Email1fromTD";

                            /** REPORT ANALYTIC */
                            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'toweremail');
                            /** REPORT ANALYTIC */

                        }
                    }

                }else{
                    if (strtolower($priorityEmail) != strtolower($firstEmail)) {
                        $secondEmail = $firstEmail;
                        $firstEmail = $priorityEmail;
                    }
                    $trackFirstEmail = "Email1fromTD";
                }

                /** CHECK ZERO BOUNCE FOR VALID EMAIL */
                $invalidFirstEmail = false;
                $invalidSecondEmail = false;

                if(trim($firstEmail) != "") {
                    $zbcheck = $this->zb_validation($firstEmail,"");
                    if (isset($zbcheck->status)) {
                        if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                            /** PUT IT ON OPTOUT LIST */
                            $createoptout = OptoutList::create([
                                'email' => $firstEmail,
                                'emailmd5' => md5($firstEmail),
                                'blockedcategory' => 'zbnotvalid',
                                'description' => 'Zero Bounce Status : ' . $zbcheck->status . '|' . $trackFirstEmail,
                            ]);
                            /** PUT IT ON OPTOUT LIST */
                            $invalidFirstEmail = true;

                            /** REPORT ANALYTIC */
                                $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                            /** REPORT ANALYTIC */
                        }else{
                            /** REPORT ANALYTIC */
                            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                            /** REPORT ANALYTIC */
                        }
                    }else{
                        $_Description = $_Description . "|ZB Error Email1 : " . $zbcheck->error;
                    }
                }

                if(trim($secondEmail) != "") {
                    $zbcheck = $this->zb_validation($secondEmail,"");
                    if (isset($zbcheck->status)) {
                        if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                            /** PUT IT ON OPTOUT LIST */
                            $createoptout = OptoutList::create([
                                'email' => $secondEmail,
                                'emailmd5' => md5($secondEmail),
                                'blockedcategory' => 'zbnotvalid',
                                'description' => 'Zero Bounce Status : ' . $zbcheck->status . '|Email2fromED',
                            ]);
                            /** PUT IT ON OPTOUT LIST */
                            $invalidSecondEmail = true;

                            /** REPORT ANALYTIC */
                            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobouncefailed');
                            /** REPORT ANALYTIC */
                        }else{
                            /** REPORT ANALYTIC */
                            $this->UpsertReportAnalytics($leadspeek_api_id,$leadspeektype,'zerobounce');
                            /** REPORT ANALYTIC */
                        }
                    }else{
                        $_Description = $_Description . "|ZB Error Email2 : " . $zbcheck->error;
                    }
                }

                if ($invalidFirstEmail == true && $invalidSecondEmail == true) {
                    $firstEmail = "";
                    $secondEmail = "";
                }else if ($invalidFirstEmail == true && $invalidSecondEmail == false) {
                    $firstEmail = $secondEmail;
                    $secondEmail = "";
                }else if ($invalidFirstEmail == false && $invalidSecondEmail == true) {
                    $secondEmail = "";
                }
                /** CHECK ZERO BOUNCE FOR VALID EMAIL */

                $_Email = $firstEmail;
                $_Email2 = $secondEmail;

                if ($firstEmail != '') {
                    $newpersonemail = PersonEmail::create([
                        'person_id' => $newPersonID,
                        'email' => $firstEmail,
                        'email_encrypt' => md5($firstEmail),
                        'permission' => 'T',
                        'zbvalidate' => date('Y-m-d H:i:s'),
                    ]);
                }

                if ($secondEmail != '') {
                    $newpersonemail = PersonEmail::create([
                        'person_id' => $newPersonID,
                        'email' => $secondEmail,
                        'email_encrypt' => md5($secondEmail),
                        'permission' => 'T',
                        'zbvalidate' => date('Y-m-d H:i:s'),
                    ]);
                }

                /** CHECK IF PRIORITY EMAIL STILL EMPTY THEN TRY TO GET FROM TOWER DATA */

            /** GET EMAIL DATA */

            /** GET PHONE DATA*/
            $dataexist = false;
            $_phone = $this->format_phone($_phone);
            $firstPhone = "";
            $secondPhone = "";
            $no = 0;
                foreach($de->person->phones as $ph) {
                    $number = $ph->number;
                    $type = $ph->type;
                    $isConnected = ($ph->isConnected == true)?'T':'F';
                    $firstReportedDate = date('Y-m-d',strtotime($ph->firstReportedDate));
                    $lastReportedDate = date('Y-m-d',strtotime($ph->lastReportedDate));

                    if ($no == 0) {
                        $firstPhone = $number;
                        $no++;
                    }else if ($no == 1) {
                        $secondPhone = $number;
                        $no++;
                    }

                    /** INSERT INTO PERSON_PHONES */
                    $newpersonphone = PersonPhone::create([
                        'person_id' => $newPersonID,
                        'number' => $number,
                        'type' => $type,
                        'isConnected' => $isConnected,
                        'firstReportedDate' => $firstReportedDate,
                        'lastReportedDate' => $lastReportedDate,
                        'permission' => 'T',
                    ]);
                    /** INSERT INTO PERSON_PHONES */

                    if(trim($_phone) == trim($number)) {
                        $dataexist = true;
                    }
                }

                /** CHECK IF ORI PHONE EXIST */
                    if ($dataexist == false && trim($_phone) != "") {
                        $newpersonemail = PersonPhone::create([
                            'person_id' => $newPersonID,
                            'number' => $_phone,
                            'type' => 'user',
                            'isConnected' => 'T',
                            'firstReportedDate' => date('Y-m-d'),
                            'lastReportedDate' => date('Y-m-d'),
                            'permission' => 'F',
                        ]);

                        if ($firstPhone != "") {
                            $_Phone = $firstPhone;
                        }

                        if ($secondPhone != "") {
                            $_Phone2 = $secondPhone;
                        }
                    }
                /** CHECK IF ORI PHONE EXIST */

            /** GET PHONE DATA */

            $_Phone = (isset($de->person->phones[0]->number))?$de->person->phones[0]->number:'';
            $_Phone2 = (isset($de->person->phones[1]->number))?$de->person->phones[1]->number:'';

            /** GET ADDRESS DATA*/
            $dataexist = false;
                foreach($de->person->addresses as $ad) {
                    $street = $ad->street;
                    $unit = $ad->unit;
                    $city = $ad->city;
                    $state = $ad->state;
                    $zip = $ad->zip;
                    $firstReportedDate = date('Y-m-d',strtotime($ad->firstReportedDate));
                    $lastReportedDate = date('Y-m-d',strtotime($ad->lastReportedDate));

                    /** INSERT INTO PERSON_ADDRESSES */
                    $newpersonaddress = PersonAddress::create([
                        'person_id' => $newPersonID,
                        'street' => $street,
                        'unit' => $unit,
                        'city' => $city,
                        'state' => $state,
                        'zip' => $zip,
                        'fullAddress' => $street . ' ' . $city . ',' . $state . ' ' . $zip,
                        'firstReportedDate' => $firstReportedDate,
                        'lastReportedDate' => $lastReportedDate,
                    ]);
                    /** INSERT INTO PERSON_ADDRESSES */

                    if(trim($_address) == trim($street)) {
                        $dataexist = true;
                    }
                }

                /** CHECK IF ORI PHONE EXIST */
                    if ($dataexist == false && (trim($_address) != "" || trim($_city) != "" || trim($_state) != "" || trim($_zip) != "")) {
                        $newpersonaddress = PersonAddress::create([
                            'person_id' => $newPersonID,
                            'street' => $_address,
                            'unit' => 'user',
                            'city' => $_city,
                            'state' => $_state,
                            'zip' => $_zip,
                            'fullAddress' => $_address . ' ' . $_city . ',' . $_state . ' ' . $_zip,
                            'firstReportedDate' => date('Y-m-d'),
                            'lastReportedDate' => date('Y-m-d'),
                        ]);
                    }
                /** CHECK IF ORI PHONE EXIST */

            /** GET ADDRESS DATA*/

            $_Address1 = (isset($de->person->addresses[0]->street))?$de->person->addresses[0]->street:'';
            $_City = (isset($de->person->addresses[0]->city))?$de->person->addresses[0]->city:'';
            $_State = (isset($de->person->addresses[0]->state))?$de->person->addresses[0]->state:'';
            $_Zipcode = (isset($de->person->addresses[0]->zip))?$de->person->addresses[0]->zip:'';

            $new = array(
                "ID" => $_ID,
                "Email" => $_Email,
                "Email2" => $_Email2,
                "OriginalMD5" => $md5param,
                "IP" => $_IP,
                "Source" => $_Source,
                "OptInDate" => $_OptInDate,
                "ClickDate" => $_ClickDate,
                "Referer" => $_Referer,
                "Phone" => $_Phone,
                "Phone2" => $_Phone2,
                "FirstName" => $_FirstName,
                "LastName" => $_LastName,
                "Address1" => $_Address1,
                "Address2" => $_Address2,
                "City" => $_City,
                "State" => $_State,
                "Zipcode" => $_Zipcode,
                "PersonID" => $newPersonID,
                "Keyword" => $keyword,
                "Description" => $_Description,
            );

        }else{
            $new = array();
        }

        return $new;
    }

    private function format_phone(string $phone_no) {
        return preg_replace(
            "/.*(\d{3})[^\d]{0,7}(\d{3})[^\d]{0,7}(\d{4})/",
            '($1) $2-$3',
            $phone_no
        );
    }



    /** STRIPE WEBHOOK */
    public function stripe(Request $request) {
        $stripe = new StripeClient([
            'api_key' => config('services.stripe.secret'),
            'stripe_version' => '2020-08-27'
        ]);

        // retrieve the request's body and parse it as JSON
        $body = @file_get_contents('php://input');
        $event_json = json_decode($body);

        // for extra security, retrieve from the Stripe API
        $event_id = $event_json->id;

        $event = $stripe->events->retrieve($event_id,[]);

        /** payment_intent.succeeded */
        if($event->type == 'payment_intent.succeeded') {
            $this->payment_success($event->data->object);
        }
        /** payment_intent.succeeded */

        /** payment_intent.payment_failed */
        if($event->type == 'payment_intent.payment_failed') {
            $this->payment_failed($event->data->object);
        }
        /** payment_intent.payment_failed */

        /** payment_intent.requires_action */
        if($event->type == 'payment_intent.requires_action') {
            //$this->payment_requires_action($event->data->object);
        }
        /** payment_intent.requires_action */
    }

    private function payment_success($obj) {
        $paymentID = (isset($obj->id))?$obj->id:'';
        $customerID = (isset($obj->customer))?$obj->customer:'';
        $invoiceID = (isset($obj->invoice))?$obj->invoice:'';
        $chargeID = (isset($obj->charges->data[0]->id))?$obj->charges->data[0]->id:'';
        $reason = (isset($obj->charges->data[0]->outcome->reason))?$obj->charges->data[0]->outcome->reason:'';
        $msg = (isset($obj->charges->data[0]->outcome->seller_message))?$obj->charges->data[0]->outcome->seller_message:'';
        $last4digit = (isset($obj->charges->data[0]->payment_method_details->card->last4))?$obj->charges->data[0]->payment_method_details->card->last4:'';
        $cardtype = (isset($obj->charges->data[0]->payment_method_details->card->brand))?$obj->charges->data[0]->payment_method_details->card->brand:'';
        $transactionDate = (isset($obj->created))?$this->format_stripe_timestamp($obj->created):'';
        $receipt_email = (isset($obj->receipt_email))?$obj->receipt_email:'';
    }

    private function payment_failed($obj) {
        $paymentID = (isset($obj->id))?$obj->id:'';
        $customerID = (isset($obj->customer))?$obj->customer:'';
        $invoiceID = (isset($obj->invoice))?$obj->invoice:'';
        $chargeID = (isset($obj->charges->data[0]->id))?$obj->charges->data[0]->id:'';
        $reason = (isset($obj->charges->data[0]->outcome->reason))?$obj->charges->data[0]->outcome->reason:'';
        $msg = (isset($obj->charges->data[0]->outcome->seller_message))?$obj->charges->data[0]->outcome->seller_message:'';
        $last4digit = (isset($obj->charges->data[0]->payment_method_details->card->last4))?$obj->charges->data[0]->payment_method_details->card->last4:'';
        $cardtype = (isset($obj->charges->data[0]->payment_method_details->card->brand))?$obj->charges->data[0]->payment_method_details->card->brand:'';
        $transactionDate = (isset($obj->created))?$this->format_stripe_timestamp($obj->created):'';
        $receipt_email = (isset($obj->receipt_email))?$obj->receipt_email:'';
        $lperror_code = (isset($obj->last_payment_error->code))?$obj->last_payment_error->code:'';
        $lperror_msg = (isset($obj->last_payment_error->message))?$obj->last_payment_error->message:'';

        $details = [
            'errormsg'  => 'Stripe Payment Failed For Customer ID : ' . $customerID . ' Payment ID :' . $paymentID . ' (' . $reason . ')',
        ];

        $from = [
            'address' => 'newleads@leadspeek.com',
            'name' => 'stripe error',
            'replyto' => 'newleads@leadspeek.com',
        ];

        $this->send_email(array('harrison@uncommonreach.com'),'EMM-Stripe Payment Failed For Customer ID : ' . $customerID,$details,array(),'emails.tryseramatcherrorlog',$from,'');
        //$this->send_email('harrison@uncommonreach.com','Stripe Payment Failed For Customer ID : ' . $customerID,$details,array(),'emails.tryseramatcherrorlog');

    }

    public function cleanmasteremailzerobounce(Request $request) {
        date_default_timezone_set('America/Chicago');
        $chkdate = Carbon::now()->subDays(7)->toDateTimeString();

            $chkEmail = PersonEmail::select('id','email')
                                //->where('zbvalidate','=','')
                                ->whereNull('zbvalidate')
                                ->orWhere('zbvalidate','<=',$chkdate)
                                ->offset(0)
                                ->limit(100)
                                ->get();
        foreach($chkEmail as $chk) {
            $_Email = $chk['email'];

            $zbcheck = $this->zb_validation($_Email,"");
            if (isset($zbcheck->status)) {
                if($zbcheck->status == "invalid" || $zbcheck->status == "catch-all" || $zbcheck->status == "abuse" || $zbcheck->status == "do_not_mail" || $zbcheck->status == "spamtrap") {
                    /** PUT IT ON OPTOUT LIST */
                    $createoptout = OptoutList::create([
                        'email' => $_Email,
                        'emailmd5' => md5($_Email),
                        'blockedcategory' => 'zbnotvalid',
                        'description' => 'Zero Bounce Status : ' . $zbcheck->status,
                    ]);
                    /** PUT IT ON OPTOUT LIST */

                    /** REMOVE THE EMAIL FROM DATABASE */
                    $delEmail = PersonEmail::where('id','=',$chk['id'])->delete();
                    /** REMOVE THE EMAIL FROM DATABASE */
                }else{
                    $updateEmailValidate = PersonEmail::find($chk['id']);
                    $updateEmailValidate->zbvalidate = date('Y-m-d H:i:s');
                    $updateEmailValidate->save();
                }
            }
        }
    }

    /** TO SEND LEADS INTO SENDGRID ACCOUNT */

    public function sendContactToSendgrid($_sendgrid_api_key,$email,$firstname,$lastname,$address1,$address2,$city,$state,$zipcode,$phoneno,$email2,$keyword="",$url=""){
        $http = new \GuzzleHttp\Client;
        $apiEndpoint = "https://api.sendgrid.com/v3/marketing/contacts";

        $_alternate_emails = array();
        if (trim($email2) != "") {
            $_alternate_emails[] = $email2;
        }else{
            $_alternate_emails = array();
        }


        $contactData = [
                'email' => $email,
                'alternate_emails' => $_alternate_emails,
                'first_name' => $firstname,
                'last_name' => $lastname,
                'address_line_1' => $address1,
                'address_line_2' => $address2,
                'city' => $city,
                'state_province_region' => $state,
                'postal_code' => $zipcode,
                'phone_number' => (string)$phoneno,
                'keyword' => $keyword,
                'url' => $url
        ];



        $dataOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $_sendgrid_api_key,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'contacts' => [$contactData]
            ]
        ];

        try {
            $getcontact = $http->put($apiEndpoint,$dataOptions);
        }catch(Exception $e) {
            Log::warning('sendContactToSendgrid (' . $email . ') error add msg :' . $e);
        }
    }

    public function sendContactToSendgridList($_sendgrid_api_key,$list,$email,$firstname,$lastname,$address1,$address2,$city,$state,$zipcode,$phoneno,$email2,$keyword="",$url=""){
        $http = new \GuzzleHttp\Client;
        $apiEndpoint = "https://api.sendgrid.com/v3/marketing/contacts";

        $_alternate_emails = array();
        if (trim($email2) != "") {
            $_alternate_emails[] = $email2;
        }else{
            $_alternate_emails = array();
        }

        $contactData = [
            'email' => $email,
            'alternate_emails' => $_alternate_emails,
            'first_name' => $firstname,
            'last_name' => $lastname,
            'address_line_1' => $address1,
            'address_line_2' => $address2,
            'city' => $city,
            'state_province_region' => $state,
            'postal_code' => $zipcode,
            'phone_number' => (string)$phoneno,
            'keyword' => $keyword,
            'url' => $url
            ];

        $dataOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $_sendgrid_api_key,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'list_ids' => [$list],
                'contacts' => [$contactData]
            ]
        ];

        try {
            $getcontact = $http->put($apiEndpoint,$dataOptions);
        }catch(Exception $e) {
            Log::warning('sendContactToSendgridList (' . $email . ' | LIST : ' . $list . ') error add msg :' . $e);
        }

    }

     /** TO SEND LEADS INTO SENDGRID ACCOUNT */

     protected function NewKartraLead($company_id,$campaign_id,$transaction_date,$first_name,$last_name,$email,$phone,$address,$city,$state,$zip,$website,$phone2,$email2,$address2,$keyword){

            $company_id = (isset($company_id))?$company_id:'';
            $listAndTag = ListAndTag::select('list','tag')
                                    //->where("company_id","=",$company_id)
                                    ->where("campaign_id","=",$campaign_id)
                                    ->where("kartra_is_active","=","1")
                                    ->first();

            if ($listAndTag) {

                $tags=[];  $tags = json_decode($listAndTag->tag );
                $taglist=[];  foreach ($tags as $key => $tag) {array_push($taglist, ['cmd' => 'assign_tag', 'tag_name' =>$tag]);}
                #
                $leadLists = json_decode($listAndTag->list );
                $leadList=[];  foreach ($leadLists as $key => $listname) {array_push($leadList, ['cmd' => 'subscribe_lead_to_list', 'list_name' =>$listname]);}
                array_push($taglist, $leadList[0]);
                #
                array_push($taglist,  ['cmd' => 'subscribe_lead_to_list', 'list_name' =>"mynewlist"]);
                array_push($taglist,  ['cmd' => 'create_lead']);

                $LeadData= [
                    'transaction_date' => $transaction_date,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                    'website'=>   $website,
                ];
                $custom_fields= [
                    ['field_identifier' => 'keyword','field_value' => $keyword ,],
                    ['field_identifier' => 'secondphone','field_value' => $phone2 ,],
                    ['field_identifier' => 'secondemail','field_value' => $email2 ,],
                    ['field_identifier' => 'secondaddress','field_value' => $address2 ,],
                ];

                $LeadData["custom_fields"]=$custom_fields;
                #
                   $actions=   $taglist;
                return $list = $this->kartrLeadCreater($actions,$LeadData);
                #
            }
     }

    protected function kartrLeadCreater($actions,$lead){
            // Initialize cURL session
            $ch = curl_init();
            // Set the API endpoint
            $api_endpoint = "https://app.kartra.com/api";
            // Set the POST data with your API credentials and action
            $post_data =[
                            'app_id' =>  config('services.kartra.AppID'),
                            'api_key' => $this->api_key,
                            'api_password' => $this->api_password,
                            'lead' => $lead,
                            'actions' =>$actions
                    ];
            // Configure cURL options
            $curl_options = [
                CURLOPT_URL => $api_endpoint,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($post_data), // Convert the array to URL-encoded format
                CURLOPT_RETURNTRANSFER => true, // Return the response as a string
            ];
            // Apply cURL options
            curl_setopt_array($ch, $curl_options);
            // Execute the cURL request
            $server_output = curl_exec($ch);
            // Check for errors
            if (curl_errno($ch)) {
                Log::warning('cURL error: ' . curl_error($ch));
            } else {
                // Process the server output as needed
                Log::warning('kartaleadcreate curl : ' . $server_output);
                return  json_decode($server_output, true); // 'true' for associative arrays
            }
            // Close the cURL session
            curl_close($ch);
    }

    /** FUNCTION THAT RELATED WITH TOPUP / PREPAID*/
    public function stop_continual_topup($_lp_user_id = "")
    {
        if ($_lp_user_id != "") {
            /* UPDATE STOPCONTINUE IN TOPUP TO F */
            $data = Topup::where('lp_user_id', $_lp_user_id)
                        ->where('topupoptions', 'continual')
                        ->where('topup_status', '<>', 'done')
                        ->get();

            foreach($data as $d) 
            {
                // ubah stop_continue menjadi 'T' 
                $d->stop_continue = 'F';
                $d->save();
            }
            /* UPDATE STOPCONTINUE IN TOPUP TO F */


            /* UPDATE STOPCONTINUE IN LEADSPEEK USER TO F */
            $leadspeekUser = LeadspeekUser::where('id', $_lp_user_id)->first();
            $leadspeekUser->stopcontinual = 'F';
            $leadspeekUser->save();
            /* UPDATE STOPCONTINUE IN LEADSPEEK USER TO F */
        }
    }
    /** FUNCTION THAT RELATED WITH TOPUP / PREPAID */
}
