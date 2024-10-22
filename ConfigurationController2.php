<?php

namespace App\Http\Controllers;

use App\Exports\AnalyticExport;
use App\Mail\Gmail;
use App\Models\Company;
use App\Models\CompanySale;
use App\Models\PackagePlan;
use App\Models\CompanySetting;
use App\Models\CompanyStripe;
use App\Models\DomainRemove;
use App\Models\LeadspeekReport;
use App\Models\LeadspeekUser;
use App\Models\Module;
use App\Models\ReportAnalytic;
use App\Models\Role;
use App\Models\RoleModule;
use App\Models\User;
use App\Models\Topup;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use ESolution\DBEncryption\Encrypter;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use stdClass;
use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_TransportException;

class ConfigurationController extends Controller
{   
    public function getminleaddayenhance(Request $request)
    {
        $idSys = (isset($request->idSys))?$request->idSys:"";

        /* GET CLIENT MIN LEAD DAYS */
        $rootSetting = $this->getcompanysetting($idSys, 'rootsetting');
        $clientMinLeadDayEnhance = (isset($rootSetting->clientminleadday))?$rootSetting->clientminleadday:"";
        /* GET CLIENT MIN LEAD DAYS */
    
        return response()->json(['clientMinLeadDayEnhance'=>$clientMinLeadDayEnhance]);
    }

    public function manual_pagination($downline,$Page=0,$PerPage=0) {
        $total = $downline->count(); // Total number of items
        $offset = ($Page - 1) * $PerPage; // Calculate the offset
            
        // Get a subset of items for the current page
        $currentPageItems = $downline->slice($offset, $PerPage);
        
        // Create a paginator instance
        $paginator = new LengthAwarePaginator(
            $currentPageItems, // Items for the current page
            $total, // Total number of items
            $PerPage, // Items per page
            $Page, // Current page
            ['path' => LengthAwarePaginator::resolveCurrentPath()] // Path for generating URLs
        );
        
        // Convert the paginator to an array with pagination data
        return $paginator->toArray();
    }

    public function usermodule_show(Request $request) {
        
    }

    public function updatesubdomain(Request $request) {
        $companyID = (isset($request->companyID))?$request->companyID:'';
        $subdomain = (isset($request->subdomain))?$request->subdomain:'';
        $idsys = (isset($request->idsys))?$request->idsys:'';

        /** GET ROOT SYS CONF */
        $confAppDomain =  config('services.application.domain');
        if ($idsys != "") {
            $conf = $this->getCompanyRootInfo($idsys);
            $confAppDomain = $conf['domain'];
        }
        /** GET ROOT SYS CONF */

        if ($companyID != '' && $subdomain != '') {
            $subdomain = str_replace('http://','',$subdomain);
            $subdomain = trim(str_replace('https://','',$subdomain));
            $subdomain = $subdomain . '.' . $confAppDomain;
            if ($this->check_subordomain_exist($subdomain)) {
                return response()->json(array('result'=>'failed','message'=>'This subdomain already exists'));
            }else{
                $subdomainupdate = Company::find($companyID);
                $subdomainupdate->subdomain = $subdomain;
                $subdomainupdate->save();

                return response()->json(array('result'=>'success','message'=>'This subdomain has been update','domain'=>$subdomainupdate->domain,'subdomain'=>$subdomainupdate->subdomain));
            }


        }
    }

    public function costmodule(Request $request) {
        date_default_timezone_set('America/Chicago');

        //$user = User::find($request->ClientID);
        $user = LeadspeekUser::find($request->ClientID);
        if ($request->ModuleName == 'LeadsPeek') {
            $user->cost_perlead = $request->CostSet;
            $user->lp_max_lead_month = $request->CostMaxLead;
            $user->lp_min_cost_month = $request->CostMonth;
            $user->lp_limit_leads = $request->LimitLead;
            $user->lp_limit_freq = $request->LimitLeadFreq;
            $user->paymentterm = $request->PaymentTerm;
            $user->continual_buy_options = $request->contiDurationSelection ? 'Monthly' : 'Weekly';
            $user->topupoptions = $request->topupoptions;
            $user->leadsbuy = $request->leadsbuy;
            $user->platformfee = $request->PlatformFee;
            if (isset($request->LimitLeadStart) || $request->LimitLeadStart != '') {
                if ($request->PaymentTerm == 'One Time') {
                    $user->lp_limit_startdate = date('Y-m-d');
                }else{
                    $user->lp_limit_startdate = $request->LimitLeadStart;
                }
            }
            if (isset($request->LimitLeadEnd) && $request->LimitLeadEnd != '' && $request->LimitLeadEnd != 'Invalid date') {
                $user->lp_enddate = $request->LimitLeadEnd;
            }else{
                $user->lp_enddate = null;
            }
        }
        $user->save();

        /** CHECK IF PREPAID ALREADY ON COSTAGENCY */
        $getcompanysetting = CompanySetting::where('company_id',$user->company_id)->whereEncrypted('setting_name','costagency')->get();
        $companysetting = "";
        if (count($getcompanysetting) > 0) {
            $companysetting = json_decode($getcompanysetting[0]['setting_value']);
        }
        if ($companysetting != "") {
            $comset_val = $this->getcompanysetting($request->idSys,'rootcostagency');

            if(!isset($companysetting->enhance)) {
                $companysetting->enhance = new stdClass();
                $companysetting->enhance->Weekly = new stdClass();
                $companysetting->enhance->Monthly = new stdClass();
                $companysetting->enhance->OneTime = new stdClass();
                $companysetting->enhance->Prepaid = new stdClass();
        
                /* WEEKLY */
                $companysetting->enhance->Weekly->EnhanceCostperlead = $comset_val->enhance->Weekly->EnhanceCostperlead;
                $companysetting->enhance->Weekly->EnhanceMinCostMonth = $comset_val->enhance->Weekly->EnhanceMinCostMonth;
                $companysetting->enhance->Weekly->EnhancePlatformFee = $comset_val->enhance->Weekly->EnhancePlatformFee;
                /* WEEKLY */
                
                /* MONTHLY */
                $companysetting->enhance->Monthly->EnhanceCostperlead = $comset_val->enhance->Monthly->EnhanceCostperlead;
                $companysetting->enhance->Monthly->EnhanceMinCostMonth = $comset_val->enhance->Monthly->EnhanceMinCostMonth;
                $companysetting->enhance->Monthly->EnhancePlatformFee = $comset_val->enhance->Monthly->EnhancePlatformFee;
                /* MONTHLY */
                
                /* ONETIME */
                $companysetting->enhance->OneTime->EnhanceCostperlead = $comset_val->enhance->OneTime->EnhanceCostperlead;
                $companysetting->enhance->OneTime->EnhanceMinCostMonth = $comset_val->enhance->OneTime->EnhanceMinCostMonth;
                $companysetting->enhance->OneTime->EnhancePlatformFee = $comset_val->enhance->OneTime->EnhancePlatformFee;
                /* ONETIME */
        
                /* PREPAID */
                $companysetting->enhance->Prepaid->EnhanceCostperlead = $comset_val->enhance->Prepaid->EnhanceCostperlead;
                $companysetting->enhance->Prepaid->EnhanceMinCostMonth = $comset_val->enhance->Prepaid->EnhanceMinCostMonth;
                $companysetting->enhance->Prepaid->EnhancePlatformFee = $comset_val->enhance->Prepaid->EnhancePlatformFee;
                /* PREPAID */
            }

            if (!isset($companysetting->local->Prepaid)) {
                $newPrepaidLocal = [
                    "LeadspeekCostperlead" => $companysetting->local->Weekly->LeadspeekCostperlead,
                    "LeadspeekMinCostMonth" => $companysetting->local->Weekly->LeadspeekMinCostMonth,
                    "LeadspeekPlatformFee" => $companysetting->local->Weekly->LeadspeekPlatformFee
                ];

                $newPrepaidLocator = [
                    "LocatorCostperlead" => $companysetting->locator->Weekly->LocatorCostperlead,
                    "LocatorMinCostMonth" => $companysetting->locator->Weekly->LocatorMinCostMonth,
                    "LocatorPlatformFee" => $companysetting->locator->Weekly->LocatorPlatformFee
                ];
                
                $newPrepaidEnhance = [
                    "EnhanceCostperlead" => $comset_val->enhance->Prepaid->EnhanceCostperlead,
                    "EnhanceMinCostMonth" => $comset_val->enhance->Prepaid->EnhanceMinCostMonth,
                    "EnhancePlatformFee" => $comset_val->enhance->Prepaid->EnhancePlatformFee
                ];

                $companysetting->local->Prepaid = $newPrepaidLocal;
                $companysetting->locator->Prepaid = $newPrepaidLocator;
                $companysetting->enhance->Prepaid = $newPrepaidEnhance;
            }
            
            /** UPDATE COMPANY SETTING VALUE (COSTAGENCY) */
            $updatesetting = CompanySetting::find($getcompanysetting[0]['id']);
            $updatesetting->setting_value = json_encode($companysetting);
            $updatesetting->save();
            /** UPDATE COMPANY SETTING VALUE (COSTAGENCY) */
        }
        /** CHECK IF PREPAID ALREADY ON COSTAGENCY */
        
        if($request->PaymentTerm === 'Prepaid') {
                $topupCampaignExist = Topup::where('leadspeek_api_id', $request->LeadspeekApiId)
                                        ->where('topup_status', '<>', 'done')
                                        ->exists();
    
            if($topupCampaignExist) {
                Topup::where('leadspeek_api_id', $request->LeadspeekApiId)
                     ->where('topup_status', '<>', 'done')
                     ->update([
                        'treshold' => $request->LimitLead
                     ]);
            }
        }

        /** LOG ACTION */
        $idUser = (isset($request->idUser))?$request->idUser:''; 
        $ipAddress = (isset($request->ipAddress))?$request->ipAddress:'';
        $description = "CampaignID : {$request->LeadspeekApiId} | Billing Frequency : {$request->PaymentTerm} | ";

        // jika prepaid 
        if($request->PaymentTerm === 'Prepaid') {
            $description .= "Topup Options : {$request->topupoptions} | ";
        }

        $description .= "Setup Fee : {$request->PlatformFee} | Campaign Fee : {$request->CostMonth} | Cost per lead : {$request->CostSet} | Leads Per Day : {$request->LimitLead}";

        $this->logUserAction($idUser, "Setup Campaigns Financial", $description, $ipAddress);
        /** LOG ACTION */

        return response()->json(array('result'=>'success'));
    }

    public function removerolemodule(Request $request,$CompanyID,$RoleID) {
        RoleModule::where('role_id','=',$RoleID)->delete();
        $usr = User::where('role_id','=',$RoleID)->get();
        if (count($usr) > 0) {
            $usr->role_id = 0;
            $usr->save();
        }
        Role::find($RoleID)->delete();
    }

    public function rolemoduleaddupdate(Request $request) {
        $RoleID = '';
        if($request->roleID == '') {
            $Role = Role::create([
                'role_name' => $request->roleName,
                'role_icon' => $request->roleIcon,
                'company_id' => $request->companyID,
            ]);
    
            $RoleID = $Role->id;

            foreach($request->roledata as $item) {
                $rolemodule = RoleModule::create([
                    'role_id' => $RoleID,
                    'module_id' => $item['id'],
                    'create_permission' => ($item['create_permission'])?'T':'F',
                    'read_permission' => ($item['read_only'])?'T':'F',
                    'update_permission' => ($item['update_permission'])?'T':'F',
                    'delete_permission' => ($item['delete_permission'])?'T':'F',
                    'enable_permission' => ($item['enable_permission'])?'T':'F',
                ]);
            }

        }else{
            $RoleID = $request->roleID;

            $Role = Role::find($RoleID);
            $Role->role_name = $request->roleName;
            $Role->role_icon = $request->roleIcon;
            $Role->save();

            $del_rolemodule = RoleModule::where('role_id','=',$request->roleID)->delete();
            foreach($request->roledata as $item) {
                $rolemodule = RoleModule::create([
                    'role_id' => $request->roleID,
                    'module_id' => $item['id'],
                    'create_permission' => ($item['create_permission'])?'T':'F',
                    'read_permission' => ($item['read_only'])?'T':'F',
                    'update_permission' => ($item['update_permission'])?'T':'F',
                    'delete_permission' => ($item['delete_permission'])?'T':'F',
                    'enable_permission' => ($item['enable_permission'])?'T':'F',
                ]);
            }
        }
        
        return array('roleID'=>$RoleID);
        //return $request->roledata[0]['module_name'];
    }

    public function rolemodule_show(Request $request,$GetType='',$CompanyID='',$ID='') {
        if($GetType == 'getrole') {
            if ($ID != '') {
                return Role::find($ID);
            }else {
                return Role::where('company_id','=',$CompanyID)->get();
            }
        }else if ($GetType == 'getmodule') {
            if ($ID != '') {
                return Module::find($ID);
            }else{
                return Module::get();
            }
        }else if ($GetType == 'getrolemodule') {
            /** CHECK IF USER SETUP COMPLETED */
            $usrCompleteProfileSetup = 'F';

            if(isset($request->usrID) && $request->usrID != '') {
                $usrSetup = User::select('profile_setup_completed','user_type','status_acc')
                            ->where('active','=','T')
                            ->where('id','=',$request->usrID)
                            ->get();
                if(count($usrSetup) > 0) {
                    $usrCompleteProfileSetup = $usrSetup[0]['profile_setup_completed'];

                }
            }
            /** CHECK IF USER SETUP COMPLETED */

            /** CHECK STRIPE CONNECTED ACCOUNT */
            $companyConnectStripe = CompanyStripe::select('status_acc','acc_connect_id','package_id')->where('company_id','=',$CompanyID)
                                    ->get();

            $checkPaymentGateway = Company::select('paymentgateway')
                                        ->where('id','=',$CompanyID)
                                        ->get();


            $accountConnected = '';
            $package_id = '';
            $paymentgateway = 'stripe';

            if (count($companyConnectStripe) > 0) {
                $accountConnected = $companyConnectStripe[0]['status_acc'];
                $package_id = ($companyConnectStripe[0]['package_id'] != '')?$companyConnectStripe[0]['package_id']:"";
                if ($accountConnected == "" && count($checkPaymentGateway) > 0) {
                    $paymentgateway = $checkPaymentGateway[0]['paymentgateway'];
                }
            }
            
            if (isset($usrSetup[0]['user_type']) && $usrSetup[0]['user_type'] == "sales") {
                $accountConnected = $usrSetup[0]['status_acc'];
                $package_id = "";
            }
            /** CHECK STRIPE CONNECTED ACCOUNT */

            // Check is_whitelabeling exists
            $companyStripe = CompanyStripe::where('company_id','=',$CompanyID)
                        ->get();
            $getCurrentCompany = Company::where('id', '=', $CompanyID)->get();
            $getUserCurrentCompany = User::where('company_id', '=', $CompanyID)->get();
            $getColorsParentCompany = Company::select('sidebar_bgcolor', 'text_color')->where('id', '=', $getUserCurrentCompany[0]['company_root_id'])->first();

            $whitelabellingpackage = 'F';

            if(count($companyStripe) > 0){
                if(trim($companyStripe[0]->package_id) != ''){
                    $chkPackage = PackagePlan::select('whitelabelling')
                                        ->where('package_id','=',trim($companyStripe[0]->package_id))
                                        ->get();
                    foreach($chkPackage as $chkpak) {
                        $whitelabellingpackage = $chkpak['whitelabelling'];
                    }
                }
            }

            $is_whitelabeling = $getCurrentCompany[0]['is_whitelabeling'] ? $getCurrentCompany[0]['is_whitelabeling'] : $whitelabellingpackage;
            // Check is_whitelabeling exists

            // agency payment term setting 
            // $getUserCurrentCompany = User::where('company_id', '=', $CompanyID)->get();
            try {
                $paymenttermcontrol = CompanySetting::where('company_id', $CompanyID)
                    ->whereEncrypted('setting_name', 'agencypaymentterm')
                    ->get();
        
                } catch (\Throwable $th) {
                        return response()->json(['result' => 'failed', 'msG' => $th->getMessage(), 'ID' => $companyStripe->acc_connect_id ]);
                }
                if (count($paymenttermcontrol) > 0) {
                    /** GET PAYMENT TERM ROOT FILTERED BY PAYMENTTERMCONTROL */
                    try {
                        $root_paymenttermlist = "";
                        $paymenttermlist = CompanySetting::where('company_id', $getUserCurrentCompany[0]['company_root_id'])
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
                            $paymenttermlist = CompanySetting::where('company_id',$getUserCurrentCompany[0]['company_root_id'])->whereEncrypted('setting_name','rootpaymentterm')->get();
                            if (count($paymenttermlist) > 0) {
                                $_paymenttermlist = json_decode($paymenttermlist[0]['setting_value']);
                            }
                            /** GET PAYMENT TERM ROOT */
        
                        $paymentTerms = $_paymenttermlist->PaymentTerm ?? [];
                }
            // agency payment term setting 

            // root default payment term for new agencies
            $settingPaymentTermsNewAgencies = $this->getcompanysetting($CompanyID,'rootPaymentTermsNewAgencies');
            $rootPaymentTermsNewAgencies = $settingPaymentTermsNewAgencies ?? [];
            // root default payment term for new agencies

            //agency default modules for create checkbox
            $agencyDefaultModules = [];
            $agencyDefaultModules_setting = $this->getcompanysetting($CompanyID,'agencydefaultmodules');
            if (!empty($agencyDefaultModules_setting)) {
                    $agencyDefaultModules = $agencyDefaultModules_setting->DefaultModules;
            }else {
                $root_clientsidebar = $this->getcompanysetting($getUserCurrentCompany[0]['company_root_id'], 'rootcustomsidebarleadmenu');
                $agency_clientsidebar = $this->getcompanysetting($getUserCurrentCompany[0]['company_id'], 'customsidebarleadmenu');
                if (!empty($agency_clientsidebar)) {
                    foreach ($agency_clientsidebar as $key => $value) {
                        if($key == 'enhance' && !isset($root_clientsidebar->enhance)) {
                            $agencyDefaultModules[] = [
                                'type' => $key,
                                'status' => false
                            ];
                            continue;
                        } else {
                            $agencyDefaultModules[] = [
                                'type' => $key,
                                'status' => true
                            ];
                        }
                    }
                    if (!isset($agency_clientsidebar->enhance) && isset($root_clientsidebar->enhance)) {
                        $agencyDefaultModules[] = [
                            'type' => 'enhance',
                            'status' => true
                        ];
                    }
                }else {
                    foreach($root_clientsidebar as $key => $value){
                            $agencyDefaultModules[] = [
                                'type' => $key,
                                'status' => true
                            ];
                    }
                }
            }

            //agency default modules for create checkbox


            //agency default module for client
            $rootcustomsidebarleadmenu = CompanySetting::where('company_id',trim($getUserCurrentCompany[0]['company_root_id']))->whereEncrypted('setting_name','rootcustomsidebarleadmenu')->get();
            $customsidebarleadmenu = CompanySetting::where('company_id',trim($CompanyID))->whereEncrypted('setting_name','customsidebarleadmenu')->get();
            $rootcustomsidebarleadmenuArr = [];
            $customsidebarleadmenuArr = [];
            $agencyFilteredModules = '';
            // if (count($customsidebarleadmenu) > 0) {
            //     if(count($rootcustomsidebarleadmenu) > 0){
            //         $rootcustomsidebarleadmenuArr = json_decode($rootcustomsidebarleadmenu[0]['setting_value'], true);
            //         $customsidebarleadmenuArr = json_decode($customsidebarleadmenu[0]['setting_value'], true);
            //         $diff_root_to_custom = array_diff_key($customsidebarleadmenuArr, $rootcustomsidebarleadmenuArr);
            //         $diff_custom_to_root = array_diff_key($rootcustomsidebarleadmenuArr, $customsidebarleadmenuArr);
            //         $array_diff = array_merge($diff_root_to_custom, $diff_custom_to_root);
            //         // Remove differences from $customsidebarleadmenuArr
            //         foreach (array_keys($array_diff) as $key) {
            //             unset($customsidebarleadmenuArr[$key]);
            //         }
            //         $agencyFilteredModules = $customsidebarleadmenuArr;
            //     }
            // }else {
            //     if(count($rootcustomsidebarleadmenu) > 0){
                    $rootcustomsidebarleadmenuArr = json_decode($rootcustomsidebarleadmenu[0]['setting_value'], true);
                    $agencyFilteredModules = json_decode($rootcustomsidebarleadmenu[0]['setting_value'], true);
            //     } 
            // }
            //agency default module for client

            /* SIDEBARMENU */
            $customsidebarleadmenu = "";
            $rootcompanysetting = CompanySetting::where('company_id',trim($getUserCurrentCompany[0]['company_root_id']))->whereEncrypted('setting_name','rootcustomsidebarleadmenu')->get();
            $rootsidebarleadmenu = json_decode($rootcompanysetting[0]['setting_value']);
            if (count($rootcompanysetting) > 0) {
                $customsidebarleadmenu = $rootsidebarleadmenu;
            }
            
            $companysetting = CompanySetting::where('company_id',trim($CompanyID))->whereEncrypted('setting_name','customsidebarleadmenu')->get();
            if (count($companysetting) > 0) {
                $customsidebarleadmenu = json_decode($companysetting[0]['setting_value']);
            }
            /* SIDEBARMENU */
            
        
            //$modules = Module::get();
            $modules = Module::where('active','=','T')->get();
            if ($ID !== '') {
               
                foreach($modules as $mdl) {
                    $mdl->create_permission = false;
                    $mdl->update_permission = false;
                    $mdl->delete_permission = false;
                    $mdl->enable_permission = false;
                    $mdl->entry_only = false;
                    $mdl->read_only = false;
                    $mdl->role_id = $ID;
                    $mdl->company_id = $CompanyID;

                    $permission = Module::find($mdl->id)->rolesmodules()->where('role_id','=',$ID)->where('company_id','=',$CompanyID)->get();
                    if (count($permission) > 0 ){
                        $mdl->create_permission = ($permission[0]->create_permission == 'T')?true:false;
                        $mdl->update_permission = ($permission[0]->update_permission == 'T')?true:false;
                        $mdl->delete_permission = ($permission[0]->delete_permission == 'T')?true:false;
                        $mdl->enable_permission = ($permission[0]->enable_permission == 'T')?true:false;   
                        
                        if ($permission[0]->create_permission == 'F' && $permission[0]->update_permission == 'F' 
                        && $permission[0]->delete_permission == 'F' && $permission[0]->enable_permission == 'T') {
                            $mdl->read_only = true;
                        }

                        if ($permission[0]->create_permission == 'T' && $permission[0]->update_permission == 'T' 
                        && $permission[0]->delete_permission == 'F' && $mdl->read_only == false && $permission[0]->enable_permission == 'T') {
                            $mdl->entry_only = true;
                        }



                    }
                    
                }
                
                return response()->json(array('result'=>'success','setupcomplete'=>$usrCompleteProfileSetup,'accountconnected'=>$accountConnected,'modules'=>$modules,'package_id'=>$package_id,'paymentgateway'=>$paymentgateway, 'is_whitelabeling' => $is_whitelabeling,'paymenttermlist' => $paymentTerms,'rootPaymentTermsNewAgencies' => $rootPaymentTermsNewAgencies, 'colors_parent' => $getColorsParentCompany,'rootsidemenu' => $rootsidebarleadmenu,'sidemenu' => $customsidebarleadmenu,'agencyDefaultModules' => $agencyDefaultModules, 'agencyFilteredModules' => $agencyFilteredModules));
                //return $modules;
            }else {
                foreach($modules as $mdl) {
                    $mdl->create_permission = false;
                    $mdl->update_permission = false;
                    $mdl->delete_permission = false;
                    $mdl->enable_permission = false;
                    $mdl->entry_only = false;
                    $mdl->read_only = false;
                    $mdl->role_id = $ID;
                    $mdl->company_id = $CompanyID;
                }
                return response()->json(array('result'=>'success','setupcomplete'=>$usrCompleteProfileSetup,'accountconnected'=>$accountConnected,'modules'=>$modules,'package_id'=>$package_id,'paymentgateway'=>$paymentgateway,'rootsidemenu'=>$rootsidebarleadmenu,'sidemenu'=>$customsidebarleadmenu));
                //return $modules;
            }
        }
      
    }

    public function salesdownline(Request $request) {
        $userID = (isset($request->usrID))?$request->usrID:'';
        $CompanyID = (isset($request->CompanyID))?$request->CompanyID:'';
        $idsys = (isset($request->idsys))?$request->idsys:'';
        $PerPage = $PerPage ?? $request->input('PerPage', 10);
        $Page = (isset($request->Page))?$request->Page:'';
        $searchKey = (isset($request->searchKey))?$request->searchKey:'';
        
        /** GET ROOT SYS CONF */
        $confAppDomain =  config('services.application.domain');
        if ($idsys != "") {
            $conf = $this->getCompanyRootInfo($idsys);
            $confAppDomain = $conf['domain'];
        }
        /** GET ROOT SYS CONF */

        $downline = User::select('users.*',DB::raw('"" as sales'),DB::raw('"" as salesrep'),DB::raw('"" as accountexecutive'),'companies.company_name','companies.simplifi_organizationid','companies.domain',DB::raw('REPLACE(companies.subdomain,".' . $confAppDomain . '","") as subdomain'))
                    ->distinct()
                    ->join('companies','companies.id','=','users.company_id')
                    ->join('company_sales','users.company_id','=','company_sales.company_id')
                    ->where('company_parent','=',$CompanyID)
                    ->where('company_sales.sales_id','=',$userID)
                    ->where('company_sales.sales_title','=','Account Executive')
                    ->where('active','T')->where('user_type','=','userdownline')
                    ->with('children')
                    ->orderBy('sort')
                    ->orderByEncrypted('companies.company_name');
                    //->get();

                    if (trim($searchKey) != '') {
                        $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
                        
                        $downline->where(function($query) use ($searchKey,$salt) {
                            $query->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(companies.company_name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                            ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                            ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.email), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                            ->orWhere(DB::raw("DATE_FORMAT(users.created_at,'%m-%d-%Y')"),'like','%' . $searchKey . '%');
                        });
                    }

                    $downline = $downline->paginate($PerPage, ['*'], 'page', $Page);
                    
                    foreach($downline as $dl) {
                        /** CHECK STRIPE CONNECTED ACCOUNT */
                        $companyConnectStripe = CompanyStripe::select('status_acc','acc_connect_id','package_id')->where('company_id','=',$dl['company_id'])
                                                ->get();
        
                        $accountConnected = '';
                        $package_id = '';
        
                        if (count($companyConnectStripe) > 0) {
                            $accountConnected = $companyConnectStripe[0]['status_acc'];
                            $package_id = ($companyConnectStripe[0]['package_id'] != '')?$companyConnectStripe[0]['package_id']:"";
                        }
                        /** CHECK STRIPE CONNECTED ACCOUNT */
        
                        $dl['status_acc'] = $accountConnected;
                        $dl['package_id'] = $package_id;
        
                        /** CHECK SALES  */
                        $chksales = User::select('users.id','users.name','company_sales.sales_title')
                                            ->join('company_sales','users.id','=','company_sales.sales_id')
                                            ->where('company_sales.company_id','=',$dl['company_id'])
                                            ->where('users.active','=','T')
                                            ->get();
        
                        $compsalesrepID = "";
                        $compsalesrep = "";
                        $compaccountexecutive = "";
                        $compaccountexecutiveID = "";
                        $compaccountref = "";
                        $compaccountrefID = "";
        
                        foreach($chksales as $sl) {
                            /*$tmpsales = [
                                'name' => $sl['name'],
                                'title' => $sl['sales_title'],
                            ];*/
                            //$compsales = $compsales .  $sl['name'] . '-' . $sl['sales_title'] . '|';
                            //array_push($compsales,$tmpsales);
                            if ($sl['sales_title'] == "Sales Representative") {
                                $compsalesrepID = $sl['id'];
                                $compsalesrep = $sl['name'];
                            }
                            if ($sl['sales_title'] == "Account Executive") {
                                $compaccountexecutiveID = $sl['id'];
                                $compaccountexecutive = $sl['name'];
                            }
                            if ($sl['sales_title'] == "Account Referral") {
                                $compaccountrefID = $sl['id'];
                                $compaccountref = $sl['name'];
                            }
                        }
                        //$compsales = rtrim($compsales,"|");
                        $dl['salesrepid'] = $compsalesrepID;
                        $dl['salesrep'] = $compsalesrep;
                        $dl['accountexecutiveid'] = $compaccountexecutiveID;
                        $dl['accountexecutive'] = $compaccountexecutive;
                        $dl['accountrefid'] = $compaccountrefID;
                        $dl['accountref'] = $compaccountref;
                        /** CHECK SALES */
                    }
        
                    return $downline;

    }

    public function show(Request $request) {
        $UserType = (isset($request->UserType))?$request->UserType:'';
        $PerPage = $PerPage ?? $request->input('PerPage', 10);
        $Page = (isset($request->Page))?$request->Page:'';
        $CardStatus = json_decode($request->input('CardStatus', '{}'), true);
        $CampaignStatus = json_decode($request->input('CampaignStatus', '{}'), true);
        
        $CompanyID = (isset($request->CompanyID))?$request->CompanyID:'';
        $sortby = (isset($request->SortBy))?$request->SortBy:'';
        $order = (isset($request->OrderBy))?$request->OrderBy:'';
        $idsys = (isset($request->idsys))?$request->idsys:'';
        $searchKey = (isset($request->searchKey))?$request->searchKey:'';

        /** GET ROOT SYS CONF */
        $confAppDomain =  config('services.application.domain');
        if (trim($idsys) != "") {
            $conf = $this->getCompanyRootInfo(trim($idsys));
            if (isset($conf['domain'])) {
                $confAppDomain = $conf['domain'];
            }
        }
        /** GET ROOT SYS CONF */
        
        /*$givelist->where(function($query) use ($searchText)
                {
                    $query->orWhere('givers.name','LIKE','%' . $searchText . '%')
                            ->orWhere('funds.fund_name','LIKE','%' . $searchText . '%')
                            ->orWhere('givers.status','LIKE','%' . $searchText . '%')
                            ->orWhere('givers.gift_type','LIKE','%' . $searchText . '%')
                            ->orWhere('givers.schedule_type','LIKE','%' . $searchText . '%')
                            ->orWhere('givers.amount','LIKE', floatval($searchText))
                            ->orWhere('givers.fee','LIKE',floatval($searchText))
                            ->orWhere(DB::raw('DATE_FORMAT(givers.transaction_date,"%Y-%m-%d")'),'LIKE','%' . $searchText . '%');
                }
            );
        */
        if($UserType == 'client') {
            $UserModule = (isset($request->UserModule))?$request->UserModule:'';
            $groupCompanyID = (isset($request->groupCompanyID))?$request->groupCompanyID:'';
            $SortBy = (isset($request->SortBy))?$request->SortBy:'';

            /** GET IF AGENCY HAVE MANUAL BILL */
            $agencyManualBill = 'F';
            $agency = Company::select('manual_bill')->where('id','=',$CompanyID)->get();
            if (count($agency) > 0) {
                $agencyManualBill = $agency[0]['manual_bill'];
            }
            /** GET IF AGENCY HAVE MANUAL BILL */

            //return  User::select('users.*','companies.company_name')->join('companies','companies.id','=','users.company_id')->where('company_parent',$CompanyID)->where('active','T')->where('user_type','=','client')->get();
            //$user =  User::select('users.*','companies.company_name','sites.domain',DB::raw('"" as newpassword'),'leadspeek_users.active_user','leadspeek_users.leadspeek_type','leadspeek_users.leadspeek_api_id')
            //$user =  User::select('users.*','companies.company_name','sites.domain',DB::raw('"" as newpassword'))
            $user =  User::select('users.*','companies.manual_bill','companies.paymentterm_default','companies.company_name', 'companies.simplifi_organizationid','companies.optoutfile','sites.domain','companies.subdomain as orisubdomain',DB::raw('REPLACE(companies.subdomain,".' . $confAppDomain . '","") as subdomain'),DB::raw('"" as newpassword'))
                            ->leftjoin('companies','companies.id','=','users.company_id')
                            ->leftjoin('sites','users.site_id','=','sites.id');
                            //->leftjoin('leadspeek_users','users.id','=','leadspeek_users.user_id')
                            if ($groupCompanyID != 'all') {
                                $user->where('users.id',$groupCompanyID);
                            }else{
                                $user->where('users.company_parent',$CompanyID);
                            }
                            $user = $user->where('users.active','T')
                            ->where('users.user_type','=','client');
            if ($SortBy == "LeadsPeek") {
                if ($agencyManualBill == 'F') {
                    $user->where('users.customer_payment_id','<>','')
                            ->where('users.customer_card_id','<>','');
                }
            }
            /*if($UserModule != '' && $UserModule == 'LeadsPeek') {
                $user->whereNotIn('users.id',function($query) {

                    $query->select('user_id')->from('leadspeek_users');
                 
                 });
            }*/

            /*if(trim($groupCompanyID) != '' && trim($groupCompanyID) != 'all') {
                $user->where('leadspeek_users.group_company_id','=',trim($groupCompanyID));
            }*/
            if (trim($searchKey) != '') {
                $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
                
                $user->where(function($query) use ($searchKey,$salt) {
                    $query->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(companies.company_name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.email), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.phonenum), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("DATE_FORMAT(users.created_at,'%m-%d-%Y')"),'like','%' . $searchKey . '%');
                });
            }

            if (trim($order) != '') {
                if (trim($order) == 'descending') {
                    $order = "DESC";
                }else{
                    $order = "ASC";
                }
            }

            // Filter card status
            $cardStatus = (object) array_merge([
                'active' => false,
                'failed' => false,
                'inactive' => false,
            ], $CardStatus);

            $activeCardStatus = $cardStatus->active;
            $failedCardStatus = $cardStatus->failed;
            $inactiveCardStatus = $cardStatus->inactive;

            if($activeCardStatus && $inactiveCardStatus &&  $failedCardStatus){
                $user->where(function($query) {
                    $query
                        ->orWhere('users.customer_payment_id', '')
                        ->orWhere('users.customer_card_id', '')
                        ->orWhere('users.customer_payment_id', 'LIKE', '%cus%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%card%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%agency%')
                        ->orWhere('users.customer_payment_id', 'LIKE', '%agency%');
                });
            } else if($activeCardStatus && $inactiveCardStatus){
                $user->where(function($query) {
                    $query
                        ->orWhere('users.customer_payment_id', '')
                        ->orWhere('users.customer_card_id', '')
                        ->orWhere('users.customer_payment_id', 'LIKE', '%cus%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%card%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%agency%')
                        ->orWhere('users.customer_payment_id', 'LIKE', '%agency%');
                })->where(function($query) {
                    $query->where('users.payment_status', '!=', 'failed')
                          ->orWhereNull('users.payment_status')
                          ->orWhere('users.payment_status', '=', '');
                });
            } else if($activeCardStatus &&  $failedCardStatus){
                $user->where(function($query) {
                    $query
                        ->orWhere('users.customer_payment_id', 'LIKE', '%cus%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%card%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%agency%')
                        ->orWhere('users.customer_payment_id', 'LIKE', '%agency%')
                        ->orWhere('users.payment_status', '=', 'failed');
                });
            } else if ($inactiveCardStatus &&  $failedCardStatus) {
                $user->where(function($query) {
                    $query->where('users.customer_payment_id', '')
                            ->orWhere('users.customer_card_id', '')
                            ->orWhere('users.payment_status', '=', 'failed');
                });
            } else if ($inactiveCardStatus) {
                $user->where(function($query) {
                    $query->where('users.customer_payment_id', '')
                          ->orWhere('users.customer_card_id', '');
                });
            } elseif ($activeCardStatus) {
                $user->where(function($query) {
                    $query
                        ->where('users.customer_payment_id', 'LIKE', '%cus%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%card%')
                        ->orWhere('users.customer_card_id', 'LIKE', '%agency%')
                        ->orWhere('users.customer_payment_id', 'LIKE', '%agency%');
                })->where(function($query) {
                    $query->where('users.payment_status', '!=', 'failed')
                          ->orWhereNull('users.payment_status')
                          ->orWhere('users.payment_status', '=', '');
                });
            } elseif ($failedCardStatus) {
                $user->where(function($query) {
                    $query->where('users.payment_status', 'failed');
                });
            }
            // Filter card status

            // Filter campaign status
            $campaignStatus = (object) array_merge([
                'active' => false,
                'inactive' => false,
            ], $CampaignStatus);

            $activeCampaignStatus = $campaignStatus->active;
            $inactiveCampaignStatus = $campaignStatus->inactive;

            if($activeCampaignStatus && $inactiveCampaignStatus){
                $user->whereIn('users.id', function($query) {
                    $query->select('user_id')
                          ->from('leadspeek_users')
                          ->where('archived', '=', 'F')
                          ->groupBy('user_id')
                          ->havingRaw('SUM(CASE WHEN (active = "T" OR active = "F") AND disabled = "F" AND active_user = "T" THEN 1 ELSE 0 END) > 0')
                          ->havingRaw('SUM(CASE WHEN active = "F" AND disabled = "T" AND (active_user = "T" OR active_user = "F") THEN 1 ELSE 0 END) > 0');
                });
            } else if ($activeCampaignStatus){
                $user->whereIn('users.id', function($query) {
                    $query->select('user_id')
                          ->from('leadspeek_users')
                          ->where('archived', '=', 'F')
                          ->groupBy('user_id')
                          ->havingRaw('SUM(CASE WHEN (active = "T" OR active = "F") AND disabled = "F" AND active_user = "T" THEN 1 ELSE 0 END) > 0');
                });
            } else if ($inactiveCampaignStatus){
                $user->whereIn('users.id', function($query) {
                    $query->select('user_id')
                          ->from('leadspeek_users')
                          ->where('archived', '=', 'F')
                          ->groupBy('user_id')
                          ->havingRaw('SUM(CASE WHEN (active = "T" OR active = "F") AND disabled = "F" AND active_user = "T" THEN 1 ELSE 0 END) < 1')
                          ->havingRaw('SUM(CASE WHEN active = "F" AND disabled = "T" AND (active_user = "T" OR active_user = "F") THEN 1 ELSE 0 END) > 0');
                });
            }
            // Filter campaign status

            if (trim($sortby) != '') {
                if (trim($sortby) == "company_name") {
                    $user = $user->orderByEncrypted('companies.company_name',$order);
                }else if (trim($sortby) == "full_name") {
                    $user = $user->orderByEncrypted('users.name',$order);
                }else if (trim($sortby) == "email") {
                    $user = $user->orderByEncrypted('users.email',$order);
                }else if (trim($sortby) == "phone") {
                    $user = $user->orderByEncrypted('users.phonenum',$order);
                }else if (trim($sortby) == "created_at") {
                    $user = $user->orderBy(DB::raw('CAST(users.created_at AS DATETIME)'),$order);
                }

                if ($Page == '') { 
                    $user = $user->get();
                }else{
                    $user = $user->paginate($PerPage, ['*'], 'page', $Page);
                }
            }else{
                if ($Page == '') { 
                    $user = $user->orderByEncrypted('companies.company_name')->get();
                }else{
                    $user = $user->orderByEncrypted('companies.company_name')->paginate($PerPage, ['*'], 'page', $Page);
                }
            }

            foreach($user as $a => $us) {
                
                $user[$a]['manual_bill'] = $agencyManualBill;
                
                /** GET ACTIVE CAMPAIGN*/
                $activeCampaign = LeadspeekUser::select(DB::raw('COUNT(*) as activecampaign'))
                                            ->where(function($query){
                                                $query->where(function($query){
                                                    $query->where('active','=','T')
                                                        ->where('disabled','=','F')
                                                        ->where('active_user','=','T');
                                                })
                                                ->orWhere(function($query){
                                                    $query->where('active','=','F')
                                                        ->where('disabled','=','F')
                                                        ->where('active_user','=','T');
                                                });
                                            })
                                            ->where('archived','=','F')
                                            ->where('user_id','=',$us['id'])
                                            ->get();
                if (count($activeCampaign) > 0) {
                    $user[$a]['campaign_active'] = $activeCampaign[0]['activecampaign'];
                }else{
                    $user[$a]['campaign_active'] = 0;
                }
                /** GET ACTIVE CAMPAIGN */

                /** GET PAUSE / STOP CAMPAIGN*/
                $notActiveCampaign = LeadspeekUser::select(DB::raw('COUNT(*) as notactivecampaign'))
                                            ->where(function($query){
                                                $query->where(function($query){
                                                    $query->where('active','=','F')
                                                        ->where('disabled','=','T')
                                                        ->where('active_user','=','T');
                                                })
                                                ->orWhere(function($query){
                                                    $query->where('active','=','F')
                                                        ->where('disabled','=','T')
                                                        ->where('active_user','=','F');
                                                });
                                            })
                                            ->where('archived','=','F')
                                            ->where('user_id','=',$us['id'])
                                            ->get();
                if (count($notActiveCampaign) > 0) {
                    $user[$a]['campaign_not_active'] = $notActiveCampaign[0]['notactivecampaign'];
                }else{
                    $user[$a]['campaign_not_active'] = 0;
                }
                /** GET PAUSE / STOP CAMPAIGN*/

                // SELECTED MODULES
                $clientsidebar = $this->getcompanysetting($user[$a]['company_id'], 'clientsidebar');
                $client_side_menu = [];

                if (!empty($clientsidebar)) {
                    $client_side_menu = $clientsidebar->SelectedSideBar;
                }else {
                    $root_clientselect = $this->getcompanysetting($user[$a]['company_root_id'], 'rootexistclientmoduleselect');
                    if ($root_clientselect) {
                        foreach ($root_clientselect->SelectedModules as $key => $value) {
                            $client_side_menu[] = [
                                'type' => $value->type,
                                'status' => $value->status
                            ];
                        } 
                    }else {
                        $root_clientsidebar = $this->getcompanysetting($user[$a]['company_root_id'], 'rootcustomsidebarleadmenu');
                        // $agency_clientsidebar = $this->getcompanysetting($user[$a]['company_parent'], 'customsidebarleadmenu');
                        // if (!empty($agency_clientsidebar)) {
                        //     foreach ($agency_clientsidebar as $key => $value) {
                        //     if(($key == 'enhance' && !isset($root_clientsidebar->enhance)) ) {
                        //             $client_side_menu[] = [
                        //                 'type' => $key,
                        //                 'status' => false
                        //             ];
                        //             continue;
                        //     } else {
                        //         $client_side_menu[] = [
                        //             'type' => $key,
                        //             'status' => true
                        //         ];
                        //     }
                        //     }
                        // }else {
                            if (!empty($root_clientsidebar)) {
                                foreach($root_clientsidebar as $key => $value){
                                    // if ($key == 'enhance') {
                                    //     $client_side_menu[] = [
                                    //         'type' => $key,
                                    //         'status' => false
                                    //     ];
                                    // }else {
                                        $client_side_menu[] = [
                                            'type' => $key,
                                            'status' => true
                                        ];
                                    // }
                                }
                            }
                        // }
                    }
                }

                $user[$a]['selected_side_bar'] = $client_side_menu;
                // SELECTED MODULES
            }
            
            return $user;
        }else if($UserType == 'userdownline') {
            //return User::select('users.*','companies.company_name')->join('companies','companies.id','=','users.company_id')->where('company_parent','=',$CompanyID)->where('user_type','=','userdownline')->with('child')->get();
            //return User::select('users.*','companies.company_name','companies.simplifi_organizationid','companies.domain',DB::raw('REPLACE(companies.subdomain,".' . config('services.application.domain') . '","") as subdomain'))->join('companies','companies.id','=','users.company_id')->where('company_parent','=',$CompanyID)->where('active','T')->where('user_type','=','userdownline')->with('children')->orderBy('sort')->orderByEncrypted('companies.company_name')->get();
            $childrensearch = false;

            $downline = User::select('users.*',DB::raw('"" as sales'),DB::raw('"" as salesrep'),DB::raw('"" as accountexecutive'),'companies.manual_bill','companies.company_name','companies.simplifi_organizationid','companies.domain','companies.is_whitelabeling','companies.subdomain as orisubdomain',DB::raw('REPLACE(companies.subdomain,".' . $confAppDomain . '","") as subdomain'))->join('companies','companies.id','=','users.company_id')->where('company_parent','=',$CompanyID)->where('active','T')->where('user_type','=','userdownline')->with('children');

            if (trim($searchKey) != '') {
                $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);

                $downline->where(function($query) use ($searchKey,$salt) {
                    $query->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(companies.company_name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                        ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.email), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                        ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%');
                });
            }

            if (trim($order) != '') {
                if (trim($order) == 'descending') {
                    $order = "DESC";
                }else{
                    $order = "ASC";
                }
            }

            if(trim($sortby) != ''){
                if (trim($sortby) == "full_name") {
                    $downline = $downline->orderByEncrypted('users.name',$order);
                }else if (trim($sortby) == "company_name") {
                    $downline = $downline->orderByEncrypted('companies.company_name',$order);
                }else if (trim($sortby) == "email") {
                    $downline = $downline->orderByEncrypted('users.email',$order);
                }else if (trim($sortby) == "created_at") {
                    $downline = $downline->orderBy(DB::raw('CAST(users.created_at AS DATETIME)'),$order);
                }

                if ($Page == '') { 
                    $downline = $downline->orderBy('sort')->orderByEncrypted('companies.company_name')->get();
                }else{
                    $downline = $downline->orderBy('sort')->orderByEncrypted('companies.company_name')->paginate($PerPage, ['*'], 'page', $Page);
                }
            } else {
                if ($Page == '') { 
                    $downline = $downline->orderBy('sort')->orderByEncrypted('companies.company_name')->get();
                }else{
                    $downline = $downline->orderBy('sort')->orderByEncrypted('companies.company_name')->paginate($PerPage, ['*'], 'page', $Page);
                }
            }

            /** IF SEARCH ON AGENCY EMPTY RESULT */
            if (count($downline) == 0 && trim($searchKey) != '') {
                $childrensearch = true;

                $downline = User::select('users.*',DB::raw('"" as sales'),DB::raw('"" as salesrep'),DB::raw('"" as accountexecutive'),'companies.manual_bill','companies.company_name','companies.simplifi_organizationid','companies.domain','companies.subdomain as orisubdomain',DB::raw('REPLACE(companies.subdomain,".' . $confAppDomain . '","") as subdomain'))->join('companies','companies.id','=','users.company_id')->where('company_parent','=',$CompanyID)->where('active','T')->where('user_type','=','userdownline')
                ->with(['children' => function ($query) use ($searchKey, $salt) {
                    if ($searchKey !== "") {
                        $query->where(function($subQuery) use ($searchKey, $salt) {
                            $subQuery->where(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(companies.company_name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                                ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.email), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                                ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(users.name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                                ->orWhere('leadspeek_users.leadspeek_api_id','like','%' . $searchKey . '%')
                                ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(leadspeek_users.campaign_name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%');
                        });
                    }
                }]);

                $downline = $downline->orderBy('sort')->orderByEncrypted('companies.company_name')->get();
                                
                if (count($downline) > 0) {
                    $downline = $downline->filter(function ($user) {
                        return $user->children->isNotEmpty();
                    });
                }

            }
             /** IF SEARCH ON AGENCY EMPTY RESULT */

            foreach($downline as $dl) {
                /** CHECK STRIPE CONNECTED ACCOUNT */
                $companyConnectStripe = CompanyStripe::select('status_acc','acc_connect_id','package_id')->where('company_id','=',$dl['company_id'])
                                        ->get();

                $accountConnected = '';
                $package_id = '';

                if (count($companyConnectStripe) > 0) {
                    $accountConnected = $companyConnectStripe[0]['status_acc'];
                    $package_id = ($companyConnectStripe[0]['package_id'] != '')?$companyConnectStripe[0]['package_id']:"";
                }
                /** CHECK STRIPE CONNECTED ACCOUNT */

                $dl['status_acc'] = $accountConnected;
                $dl['package_id'] = $package_id;

                // Check white labeling
                $whitelabelingByPackageplans = PackagePlan::select('whitelabelling')
                ->where('package_id', $dl['package_id'])
                ->where('company_root_id', $CompanyID)
                ->first();
                $getIsWhitelabelingByCompany = Company::select('is_whitelabeling')
                ->where('id', '=', $dl['company_id'])
                ->first();
                
                    $whitelabelling = 'F';
                    if($whitelabelingByPackageplans){
                        $whitelabelling = $whitelabelingByPackageplans->whitelabelling;
                    } else {
                        $whitelabelling = 'F';
                    }
                
                    $is_whitelabeling = $getIsWhitelabelingByCompany->is_whitelabeling ? $getIsWhitelabelingByCompany->is_whitelabeling : $whitelabelling;
                    $dl['is_whitelabeling'] = $is_whitelabeling;

                /** CHECK SALES  */
                $chksales = User::select('users.id','users.name','company_sales.sales_title')
                                    ->join('company_sales','users.id','=','company_sales.sales_id')
                                    ->where('company_sales.company_id','=',$dl['company_id'])
                                    ->where('users.active','=','T')
                                    ->get();

                $compsalesrepID = "";
                $compsalesrep = "";
                $compaccountexecutive = "";
                $compaccountexecutiveID = "";
                $compaccountref = "";
                $compaccountrefID = "";

                foreach($chksales as $sl) {
                    /*$tmpsales = [
                        'name' => $sl['name'],
                        'title' => $sl['sales_title'],
                    ];*/
                    //$compsales = $compsales .  $sl['name'] . '-' . $sl['sales_title'] . '|';
                    //array_push($compsales,$tmpsales);
                    if ($sl['sales_title'] == "Sales Representative") {
                        $compsalesrepID = $sl['id'];
                        $compsalesrep = $sl['name'];
                    }
                    if ($sl['sales_title'] == "Account Executive") {
                        $compaccountexecutiveID = $sl['id'];
                        $compaccountexecutive = $sl['name'];
                    }

                    if ($sl['sales_title'] == "Account Referral") {
                        $compaccountrefID = $sl['id'];
                        $compaccountref = $sl['name'];
                    }
                }
                //$compsales = rtrim($compsales,"|");
                $dl['salesrepid'] = $compsalesrepID;
                $dl['salesrep'] = $compsalesrep;
                $dl['accountexecutiveid'] = $compaccountexecutiveID;
                $dl['accountexecutive'] = $compaccountexecutive;
                $dl['accountrefid'] = $compaccountrefID;
                $dl['accountref'] = $compaccountref;
                /** CHECK SALES */
                
                // ROOT PAYMENT TERM
                $root_payment_term = $this->getcompanysetting($dl['company_root_id'], 'rootpaymentterm');
                $dl['rootpaymentterm'] = $root_payment_term->PaymentTerm;
                // ROOT PAYMENT TERM

                // SELECTED PAYMENT TERM
                $selected_payment_term = $this->getcompanysetting($dl['company_id'], 'agencypaymentterm');

                $root_term = $this->getcompanysetting($dl['company_root_id'], 'rootpaymentterm');
                if ($selected_payment_term) {
                    $dl['selected_payment_term'] = $selected_payment_term->SelectedPaymentTerm;
                }else {
                    // set value to true for agency that doesn't have agencypaymentterm
                    try {
                        foreach ($root_term->PaymentTerm as $term) {
                            $term->term = $term->value;
                            unset($term->label);
                            $term->status = true;
                        }
                        $dl['selected_payment_term'] = $root_term->PaymentTerm;
                    } catch (\Throwable $th) {
                     return response()->json(['selected_payment_term'=> $dl['selected_payment_term'], 'errmsg' => $th->getMessage()]);
                     }
                }
                // SELECTED PAYMENT TERM
            }
            
            if ($childrensearch) {
                return $this->manual_pagination($downline,$Page,$PerPage);
            }
            return $downline;

        }else if($UserType == 'user') {
            $user = User::where('company_id','=',$CompanyID)->where('active','T')->whereRaw("user_type IN ('user','userdownline')")->where('isAdmin','=','T');

            if (trim($searchKey) != '') {
                $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
                
                $user->where(function($query) use ($searchKey,$salt) {
                    $query->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(email), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(phonenum), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("DATE_FORMAT(created_at,'%m-%d-%Y')"),'like','%' . $searchKey . '%');
                });
            }

            if (trim($order) != '') {
                if (trim($order) == 'descending') {
                    $order = "DESC";
                }else{
                    $order = "ASC";
                }
            }

            if (trim($sortby) != '') {
                if (trim($sortby) == "full_name") {
                    $user = $user->orderByEncrypted('name',$order);
                }else if (trim($sortby) == "email") {
                    $user = $user->orderByEncrypted('email',$order);
                }else if (trim($sortby) == "phone") {
                    $user = $user->orderByEncrypted('phonenum',$order);
                }else if (trim($sortby) == "created_at") {
                    $user = $user->orderBy(DB::raw('CAST(created_at AS DATETIME)'),$order);
                }

                if ($Page == '') { 
                    $user = $user->orderByEncrypted('name')->get();
                }else{
                    $user = $user->paginate($PerPage, ['*'], 'page', $Page);
                }
            }else{
                if ($Page == '') { 
                    $user = $user->orderByEncrypted('name')->get();
                }else{
                    $user = $user->orderByEncrypted('name')->paginate($PerPage, ['*'], 'page', $Page);
                }
            }
            
            return $user;
        }else if($UserType == 'sales') {
            $user = User::where('active','T')->whereRaw("user_type IN ('sales')")->where('isAdmin','=','T')->where('company_id','=',$CompanyID);

            if (trim($searchKey) != '') {
                $salt = substr(hash('sha256', env('APP_KEY')), 0, 16);
                
                $user->where(function($query) use ($searchKey,$salt) {
                    $query->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(name), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(email), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("CONVERT(AES_DECRYPT(FROM_bASE64(phonenum), '" . $salt . "') USING utf8mb4)"),'like','%' . $searchKey . '%')
                    ->orWhere(DB::raw("DATE_FORMAT(created_at,'%m-%d-%Y')"),'like','%' . $searchKey . '%');
                });
            }

            if (trim($order) != '') {
                if (trim($order) == 'descending') {
                    $order = "DESC";
                }else{
                    $order = "ASC";
                }
            }

            if (trim($sortby) != '') {
                if (trim($sortby) == "full_name") {
                    $user = $user->orderByEncrypted('name',$order);
                }else if (trim($sortby) == "email") {
                    $user = $user->orderByEncrypted('email',$order);
                }else if (trim($sortby) == "phone") {
                    $user = $user->orderByEncrypted('phonenum',$order);
                }else if (trim($sortby) == "created_at") {
                    $user = $user->orderBy(DB::raw('CAST(created_at AS DATETIME)'),$order);
                }

                if ($Page == '') { 
                    $user = $user->orderByEncrypted('name')->get();
                }else{
                    $user = $user->paginate($PerPage, ['*'], 'page', $Page);
                }
            }else{
                if ($Page == '') { 
                    $user = $user->orderByEncrypted('name')->get();
                }else{
                    $user = $user->orderByEncrypted('name')->paginate($PerPage, ['*'], 'page', $Page);
                }
            }
            
            return $user;
            
        }
    }

    public function sorting(Request $request) {
        $parentCompany = $request->ParentCompanyID;
        $tmp = $request->dataSort;
        parse_str($tmp,$rowList);
        
        $dataSort = $rowList['rowList'];
       
        foreach ($dataSort as $id => $parentID) {
            if (!isset($position[$parentID])) {
				$position[$parentID] = 0;
			}

            $companyParentID = $parentID;
            if(!isset($parentID) || $parentID === "null") {
                $companyParentID = $parentCompany;
                //echo "IN : " . $companyParentID . '<br>';
               
            }

           //echo $id . ' | ' . $parentID . ' : ' . $position[$parentID] . '<br>';

            /** UPDATE USER SORT */
            $user = User::where("company_id","=",$id)
                            ->where("user_type","=","userdownline")
                            ->update(["company_parent" => $companyParentID,"sort" => $position[$parentID]]);
            //$user->company_parent = $parentID;
            //$user->sort = $position[$parentID];
            //$user->save();
            /** UPDATE USER SORT */

            $count = $position[$parentID];
			$position[$parentID] = $count + 1;

        }
        
    }

    public function updatecustomdomain(Request $request) {
        $companyID = (isset($request->companyID) && $request->companyID != '')?$request->companyID:'';
        $DownlineDomain = (isset($request->DownlineDomain) && $request->DownlineDomain != '')?$request->DownlineDomain:'';
        $whitelabelling = ($request->whitelabelling === true)?'T':'F';

        $company = Company::find($companyID);

        $DownlineDomain = str_replace('http://','',$DownlineDomain);
        $DownlineDomain = trim(str_replace('https://','',$DownlineDomain));
        $DownlineDomain = strtolower($DownlineDomain);

        $msg = '';

        if ($DownlineDomain != "" && $whitelabelling == 'T') {
            $msg = 'White Labelling has been enabled.';

            /** GET CURRENT DOMAIN BEFORE UPDATE **/
                $getcurrdomain = Company::select('domain','status_domain','status_domain_error')->where('id','=',$request->companyID)->get();
                $currdomain = trim((isset($getcurrdomain[0]['domain']) && $getcurrdomain[0]['domain'] != '')?$getcurrdomain[0]['domain']:'');
                $statusdomain = trim((isset($getcurrdomain[0]['status_domain']) && $getcurrdomain[0]['status_domain'] != '')?$getcurrdomain[0]['status_domain']:'');
                $statusdomainerror = trim((isset($getcurrdomain[0]['status_domain_error']) && $getcurrdomain[0]['status_domain_error'] != '')?$getcurrdomain[0]['status_domain_error']:'');
                
                $currdomain = strtolower($currdomain);

                if ($currdomain != $DownlineDomain) {
                    if ($this->check_subordomain_exist($DownlineDomain)) {
                        return response()->json(array('result'=>'failed','message'=>'Domain name already exist'));
                    }else{
                        $statusdomain = "";
                        $statusdomainerror = "";
                    }

                    /** PUT CURRENT DOMAIN TO TABLE DOMAIN THAT NEED TO BE REMOVE */
                    if ($currdomain != '') {
                        date_default_timezone_set('America/Chicago');

                        $datenow = date('Y-m-d');
                        $date5more = date('Y-m-d',strtotime('+5 day',strtotime($datenow)));
                        
                        $chkdomainremoved = DomainRemove::select('id')->where('domain','=',$currdomain)->get();
                        if (count($chkdomainremoved) == 0 && $currdomain != '') {
                            $domainremoved = DomainRemove::create([
                                'company_id' => $companyID,
                                'domain' => $currdomain,
                                'date_removed' => $date5more,
                            ]);
                        }
                    }
                    /** PUT CURRENT DOMAIN TO TABLE DOMAIN THAT NEED TO BE REMOVE */
                }

                $chkdomainremoved = DomainRemove::select('id')->where('domain','=',$DownlineDomain)->get();
                if (count($chkdomainremoved) > 0) {
                    $removedomain = DomainRemove::where('domain','=',$DownlineDomain)->delete();
                }

                $company->domain = $DownlineDomain;
                $company->status_domain = $statusdomain;
                $company->status_domain_error = $statusdomainerror;
                $company->whitelabelling = 'T';
                $company->save();

            /** GET CURRENT DOMAIN BEFORE UPDATE **/
        }else if($whitelabelling == 'F'){
            $msg = 'White Labelling has been disabled.';

            $getcurrdomain = Company::select('domain','status_domain','status_domain_error')->where('id','=',$request->companyID)->get();
            $currdomain = trim((isset($getcurrdomain[0]['domain']) && $getcurrdomain[0]['domain'] != '')?$getcurrdomain[0]['domain']:'');
            
            $currdomain = strtolower($currdomain);
            
            $company->whitelabelling = 'F';
            $company->save();

            $DownlineDomain = $currdomain;

            /** PUT CURRENT DOMAIN TO TABLE DOMAIN THAT NEED TO BE REMOVE */
            if ($currdomain != '') {
                date_default_timezone_set('America/Chicago');

                $datenow = date('Y-m-d');
                $date5more = date('Y-m-d',strtotime('+5 day',strtotime($datenow)));
                
                $chkdomainremoved = DomainRemove::select('id')->where('domain','=',$currdomain)->get();
                if (count($chkdomainremoved) == 0 && $currdomain != '') {
                    $domainremoved = DomainRemove::create([
                        'company_id' => $companyID,
                        'domain' => $currdomain,
                        'date_removed' => $date5more,
                    ]);
                }
            }
            /** PUT CURRENT DOMAIN TO TABLE DOMAIN THAT NEED TO BE REMOVE */
        }

       
        return response()->json(array('result'=>'success','message'=>$msg,'activated'=>$whitelabelling,'domain'=>$DownlineDomain));
        
    }

    public function update(Request $request) {

            $defaultAdmin = 'F';
            $customercare = 'F';
            $adminGetNotification = "F";
            $disabledrecieveemail = (isset($request->disabledreceivedemail))?$request->disabledreceivedemail:'F';
            $disabledaddcampaign = (isset($request->disabledaddcampaign))?$request->disabledaddcampaign:'F';

            if(isset($request->defaultAdmin) && $request->defaultAdmin == 'T') {
                $defaultAdmin = 'T';
            }

            if(isset($request->customercare) && $request->customercare == 'T') {
                $customercare = 'T';
               
                /** UPDATE CUSTOMER CARE ONLY CAN BE ONLY ONE */
                $updcustcare = User::where('company_id','=',$request->companyID)
                                    ->where(function ($query) {
                                        $query->where('user_type','=','user')
                                        ->orWhere('user_type','=','userdownline');
                                    })
                                    ->update(['customercare' => 'F']);
                /** UPDATE CUSTOMER CARE ONLY CAN BE ONLY ONE */
            }

            if(isset($request->adminGetNotification) && $request->adminGetNotification == 'T') {
                $adminGetNotification = 'T';
            }

            $action = (isset($request->action) && $request->action != '')?$request->action:'';

            $DownlineDomain = (isset($request->DownlineDomain) && $request->DownlineDomain != '')?$request->DownlineDomain:'';
            $DownlineSubDomain = (isset($request->DownlineSubDomain) && $request->DownlineSubDomain != '')?$request->DownlineSubDomain:'';
            $statusdomain = "";
            $statusdomainerror = "";

            $idsys = (isset($request->idsys))?$request->idsys:'';

            /** GET ROOT SYS CONF */
            $confAppDomain =  config('services.application.domain');
            if ($idsys != "") {
                $conf = $this->getCompanyRootInfo($idsys);
                $confAppDomain = $conf['domain'];
            }
            /** GET ROOT SYS CONF */

            $getcurrdomain = Company::select('domain','status_domain','status_domain_error','subdomain')->where('id','=',$request->companyID)->get();
            $currdomain = (isset($getcurrdomain[0]['domain']))?trim($getcurrdomain[0]['domain']):'';
            $statusdomain = (isset($getcurrdomain[0]['status_domain']))?trim($getcurrdomain[0]['status_domain']):'';
            $statusdomainerror = (isset($getcurrdomain[0]['status_domain_error']))?trim($getcurrdomain[0]['status_domain_error']):'';
            $currsubdomain = (isset($getcurrdomain[0]['subdomain']))?trim($getcurrdomain[0]['subdomain']):'';

            if ($DownlineDomain != "" && isset($request->DownlineDomain)) {
                $DownlineDomain = str_replace('http://','',$DownlineDomain);
                $DownlineDomain = trim(str_replace('https://','',$DownlineDomain));
                /** GET CURRENT DOMAIN BEFORE UPDATE **/                    
                    if ($currdomain != $DownlineDomain) {
                        if ($this->check_subordomain_exist($DownlineDomain)) {
                            return response()->json(array('result'=>'failed','message'=>'Domain name already exist'));
                        }else{
                            $statusdomain = "";
                            $statusdomainerror = "";
                        }
                    }
                /** GET CURRENT DOMAIN BEFORE UPDATE **/
            }

            if ($DownlineSubDomain != "" && isset($request->DownlineSubDomain)) {
                $DownlineSubDomain = str_replace('http://','',$DownlineSubDomain);
                $DownlineSubDomain = trim(str_replace('https://','',$DownlineSubDomain));
                $DownlineSubDomain = $DownlineSubDomain . '.' . $confAppDomain;

                if($currsubdomain != $DownlineSubDomain){
                    if ($this->check_subordomain_exist($DownlineSubDomain)) {
                        return response()->json(array('result'=>'failed','message'=>'This subdomain already exists'));
                    }
                }
                
            }

            $DownlineOrganizationID = (isset($request->DownlineOrganizationID) && $request->DownlineOrganizationID != '')?$request->DownlineOrganizationID:'';

            $NewcompanyID = "";

            $company = Company::find($request->companyID);
            if ($company) {
                if (isset($request->ClientCompanyName)) {
                    $company->company_name = $request->ClientCompanyName;
                }
                if (trim($DownlineDomain) != ''&& isset($request->DownlineDomain) ) {
                    $company->domain = $DownlineDomain;
                }
                if (trim($DownlineSubDomain) != ''  && isset($request->DownlineSubDomain)) {
                    $company->subdomain = $DownlineSubDomain;
                }

                if (trim($DownlineOrganizationID != '') && isset($request->DownlineOrganizationID)) {
                    $company->simplifi_organizationid = $DownlineOrganizationID;
                }

                if (trim($DownlineDomain) != ''&& isset($request->DownlineDomain) ) {
                    $company->status_domain = $statusdomain;
                    $company->status_domain_error = $statusdomainerror;
                }
                if(isset($request->ClientWhiteLabeling)) {
                    $company->is_whitelabeling = $request->ClientWhiteLabeling;
                }
                $company->save();
            }else{
                $newCompany = Company::create([
                    'company_name' => $request->ClientCompanyName,
                    'company_address' => '',
                    'company_city' => '',
                    'company_zip' => '',
                    'company_country_code' => '',
                    'company_state_code' => '',
                    'company_state_name' => '',
                    'phone_country_code' => '',
                    'phone_country_calling_code' => '',
                    'phone' => '',
                    'email' => '',
                    'logo' => '',
                    'sidebar_bgcolor' => '',
                    'template_bgcolor' => '',
                    'box_bgcolor' => '',
                    'font_theme' => '',
                    'login_image' => '',
                    'client_register_image' => '',
                    'agency_register_image' => '',
                    'subdomain' => '',
                    'approved' => 'T',
                    'user_create_id' => $request->ClientID,
                    
                ]);
    
                $NewcompanyID = $newCompany->id;
            }

            $user = User::find($request->ClientID);
            $user->name = $request->ClientFullName;
            $user->email = strtolower($request->ClientEmail);
            $user->phonenum = $request->ClientPhone;
            $user->phone_country_code = $request->ClientPhoneCountryCode;
            $user->phone_country_calling_code = $request->ClientPhoneCountryCallingCode;
            $user->defaultadmin = $defaultAdmin;
            $user->customercare = $customercare;
            $user->admin_get_notification = $adminGetNotification;
            $user->disabled_receive_email = $disabledrecieveemail;
            $user->disable_client_add_campaign = $disabledaddcampaign;

            if ($NewcompanyID != '') {
                $user->company_id = $NewcompanyID;
            }
            
            if (isset($request->ClientRole) && $request->ClientRole != '') {
                $user->role_id = $request->ClientRole;
            }

            /** SITE DOMAIN NAME */
            if(isset($request->ClientDomain) && $request->ClientDomain != '') {
                $userdetail = User::select('site_id')
                                ->where('id','=',$request->ClientID)
                                ->get();
                if(count($userdetail) > 0) {
                    $newdomain = str_replace('http://','',$request->ClientDomain);
                    $newdomain = trim(str_replace('https://','',$newdomain));

                    if($userdetail[0]['site_id'] == '' || $userdetail[0]['site_id'] == null) {
                        /** CHECK FIRST IF DOMAIN EXIST */
                        $siteExist = Site::select('id')
                                        ->where('domain','=',$newdomain)
                                        ->get();
                        if (count($siteExist) == 0) {                
                            $newSite = Site::create([
                                'company_name' => $request->ClientCompanyName,
                                'domain' => $newdomain,
                            ]);

                            $newsiteID = $newSite->id;
                            $user->site_id = $newsiteID;
                        }else{
                            $siteupdate = Site::find($siteExist[0]['id']);
                            $siteupdate->domain = $newdomain;
                            $siteupdate->company_name = $request->ClientCompanyName;
                            $siteupdate->save();

                            $user->site_id = $siteExist[0]['id'];
                        }
                    }else{
                        $siteupdate = Site::find($userdetail[0]['site_id']);
                        $siteupdate->domain = $newdomain;
                        $siteupdate->save();
                    }
                }
            }
            /** SITE DOMAIN NAME */
            
            if(isset($request->ClientPass) && $request->ClientPass != '') {
                $user->password = Hash::make($request->ClientPass);
            }
            
            /** CHECK IF USER TYPE IS SALES AND REFCODE IS EMPTY */
            if ($user->user_type == 'sales' &&  trim($user->referralcode) == '') {
                $user->referralcode = $this->generateReferralCode('salesref' . $request->ClientID);
            }
            /** CHECK IF USER TYPE IS SALES AND REFCODE IS EMPTY */

            // SAVE PAYMENTTERMCONTROL
            $paymentterm_setting = CompanySetting::where('company_id', $request->companyID)
                    ->whereEncrypted('setting_name', 'agencypaymentterm')
                    ->first();

                $paymentterm = [];
                // return response()->json(['data', $request->selectedterms]);
            if (!empty($request->selectedterms)) {
                $paymentterm = [
                    "SelectedPaymentTerm" => $request->selectedterms,
                ];
            }

            if ($paymentterm_setting && $request->selectedterms != []) {
            // Update data yang ada
                $paymentterm_setting->setting_value = json_encode($paymentterm);
                $paymentterm_setting->save();
            } else {
                if ($paymentterm != []) {
                    $createsetting = CompanySetting::create([
                    'company_id' => $request->companyID,
                    'setting_name' => 'agencypaymentterm',
                    'setting_value' => json_encode($paymentterm),
                    ]);
                }
            }
            // SAVE PAYMENTTERMCONTROL

            //SAVE CLIENT SIDEBAR CONTROL
            $clientsidebar_setting = CompanySetting::where('company_id', $request->companyID)
                    ->whereEncrypted('setting_name', 'clientsidebar')
                    ->first();

                $clientsidebar = [];

            if (!empty($request->selectedsidebar)) {
                $clientsidebar = [
                    "SelectedSideBar" => $request->selectedsidebar,
                ];
            }

            if ($clientsidebar_setting && !empty($request->selectedsidebar)) {
                $clientsidebar_setting->setting_value = json_encode($clientsidebar);
                $clientsidebar_setting->save();
            } else {
                if ($clientsidebar != []) {
                    $createsetting = CompanySetting::create([
                    'company_id' => $request->companyID,
                    'setting_name' => 'clientsidebar',
                    'setting_value' => json_encode($clientsidebar),
                    ]);
                }
            }
            //SAVE CLIENT SIDEBAR CONTROL
            
            $user->save();

    }

    public function testsmtp(Request $request) {
        $companyID = (isset($request->companyID) && $request->companyID != '')?$request->companyID:'';
        $emailsent = (isset($request->emailsent) && $request->emailsent != '')?$request->emailsent:'';

        if ($companyID != '' && $emailsent != '') {
            $getcompanysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name','customsmtpmenu')->get();
            $companysetting = "";
            $smtpusername = "";

            if (count($getcompanysetting) > 0) {
                $companysetting = json_decode($getcompanysetting[0]['setting_value']);
                
                $security = 'ssl';
                $tmpsearch = $this->searchInJSON($companysetting,'security');

                if ($tmpsearch !== null) {
                    $security = $companysetting->security;
                    if ($companysetting->security == 'none') {
                        $security = null;
                    }
                }
                
                try {
                    $transport = (new Swift_SmtpTransport(
                        $companysetting->host, 
                        $companysetting->port, 
                        $security))
                        ->setUsername($companysetting->username)
                        ->setPassword($companysetting->password);
        
            
                        $maildoll = new Swift_Mailer($transport);
                        Mail::setSwiftMailer($maildoll);
                        $smtpusername = $companysetting->username;

                        $from = [
                            'address' => $companysetting->username,
                            'name' => 'SMTP Test',
                            'replyto' => $companysetting->username,
                        ];
                        $details = [
                            'title' => "Test Email SMTP",
                            'content' => "Test Email template",
                        ];

                        Mail::to($emailsent)->send(new Gmail("Test Email SMTP",$from,$details,'emails.customemail',array()));

                        return response()->json(array('result'=>'success','msg'=>'SMTP configuration successfully sent the email. Please check the spam folder if you did not receive it in your inbox.'));
                }catch(Swift_TransportException $e) {
                    return response()->json(array('result'=>'failed','msg'=>'SMTP configuration failed to send the email. (' . $e->getMessage() . ')'));
                }
            }else{
                return response()->json(array('result'=>'failed','msg'=>'Before proceeding, kindly save your SMTP configuration. Thank you!'));
            }

        }else{
            return response()->json(array('result'=>'failed','msg'=>'no data'));
        }

    }

    public function resendInvitation(Request $request) {
        $clientID = (isset($request->ClientID) && $request->ClientID != '')?$request->ClientID:'';
        if ($clientID != '') {
            $user = User::find($clientID);

            $_usertype = $user->user_type;

            $parent_company = $user->company_parent;
            $companyID = $user->company_id;

            $clientEmail = $user->email;
            $clientFullName = $user->name;
            $genpass = Str::random(10);
            $newpassword = Hash::make($genpass);
            $user->password = $newpassword;
            $user->save();

            /** EMAIL SENT TO CLIENTS */
            
            $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                        ->where('id','=',$parent_company)
                                        ->get();
            $agencyname = "";
            $agencyurl = "";

            if($_usertype == 'client') {
                if (count($agencycompany) > 0) {
                    $agencyname = $agencycompany[0]['company_name'];
                    $agencyurl = $agencycompany[0]['subdomain'];
                    if ($agencycompany[0]['domain'] != '' && $agencycompany[0]['status_domain'] == 'ssl_acquired') {
                        $agencyurl = $agencycompany[0]['domain'];
                        /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                        $ip = gethostbyname(trim($agencycompany[0]['domain']));
                        if ($ip == '157.230.213.72') {
                            $agencyurl = $agencycompany[0]['domain'];
                        }else{
                            $agencyurl = $agencycompany[0]['subdomain'];
                        }
                        /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                    }
                }
    
                /** START NEW METHOD EMAIL */
                $from = [
                    'address' => 'noreply@sitesettingsapi.com',
                    'name' => 'Welcome',
                    'replyto' => 'noreply@sitesettingsapi.com',
                ];
    
                $smtpusername = $this->set_smtp_email($parent_company);
                $emailtype = 'em_clientwelcomeemail';
    
                $customsetting = $this->getcompanysetting($parent_company,$emailtype);
                $chkcustomsetting = $customsetting;
    
                if ($customsetting == '') {
                    $customsetting =  json_decode(json_encode($this->check_email_template($emailtype,$parent_company)));
                }
    
                $finalcontent = nl2br($this->filterCustomEmail($user,$parent_company,$customsetting->content,$genpass,$agencyurl));
                $finalsubject = $this->filterCustomEmail($user,$parent_company,$customsetting->subject,$genpass,$agencyurl);
                $finalfrom = $this->filterCustomEmail($user,$parent_company,$customsetting->fromName,$genpass,$agencyurl);
    
                $details = [
                    'title' => ucwords($finalsubject),
                    'content' => $finalcontent,
                ];
                
                $from = [
                    'address' => (isset($customsetting->fromAddress) && $customsetting->fromAddress != '')?$customsetting->fromAddress:'noreply@sitesettingsapi.com',
                    'name' => (isset($finalfrom) && $finalfrom != '')?$finalfrom:'Welcome',
                    'replyto' => (isset($customsetting->fromReplyto) && $customsetting->fromReplyto != '')?$customsetting->fromReplyto:'support@sitesettingsapi.com',
                ];
    
                if ($smtpusername != "" && $chkcustomsetting == "") {
                    $from = [
                        'address' => $smtpusername,
                        'name' => (isset($finalfrom) && $finalfrom != '')?$finalfrom:'Welcome',
                        'replyto' => $smtpusername,
                    ];
                }
                
                $this->send_email(array($clientEmail),$from,ucwords($finalsubject),$details,array(),'emails.customemail',$parent_company);
                /** START NEW METHOD EMAIL */
                //$this->send_email(array($request->ClientEmail),$from,ucwords($agencyname) . ' Account Setup',$details,array(),'emails.userinvitation',$request->companyID);
            }else if ($_usertype == 'userdownline' || $_usertype == 'user') {

                /** START NEW EMAIL METHOD */
                if (count($agencycompany) > 0) {
                    $agencyname = $agencycompany[0]['company_name'];
                    $agencyurl = $agencycompany[0]['subdomain'];
                    if ($agencycompany[0]['domain'] != '') {
                        $agencyurl = $agencycompany[0]['domain'];
                        /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                        $ip = gethostbyname(trim($agencycompany[0]['domain']));
                        if ($ip == '157.230.213.72' || $ip == '146.190.186.110' || $ip == '143.244.212.205') {
                            $agencyurl = $agencycompany[0]['domain'];
                        }else{
                            $agencyurl = $agencycompany[0]['subdomain'];
                        }
                        /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                    }
                }

                $AdminDefault = $this->get_default_admin($parent_company);
                $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';

                $defaultdomain = $this->getDefaultDomainEmail($parent_company);
    
                $details = [
                    'defaultadmin' => $AdminDefaultEmail,
                    'agencyname' => ucwords($agencyname),
                    'agencyurl' => 'https://' . $agencyurl,
                    'username' => $clientEmail,
                    'name'  => $clientFullName,
                    'newpass' => $genpass,
                ];
                    
                $from = [
                    'address' => 'noreply@' . $defaultdomain,
                        'name' => 'Welcome',
                    'replyto' => 'support@' . $defaultdomain,
                ];
                                
                $this->send_email(array($user->email),$from,'Your Agency Admin Account Setup',$details,array(),'emails.admininvitation',$request->companyID);
                /** START NEW EMAIL METHOD */
                   
            }
            /** EMAIL SENT TO CLIENTS */

            return response()->json(array('result'=>'success','message'=>'Invitation has been sent'));
        }else{
            return response()->json(array('result'=>'failed','message'=>'Invitation failed to sent'));
        }
    }

    public function resendInvitation2(Request $request) {
        $clientID = (isset($request->ClientID) && $request->ClientID != '')?$request->ClientID:'';
        if ($clientID != '') {
            $user = User::find($clientID);

            $_usertype = $user->user_type;

            $parent_company = $user->company_parent;
            $companyID = $user->company_id;

            $clientEmail = $user->email;
            $clientFullName = $user->name;
            $genpass = Str::random(10);
            $newpassword = Hash::make($genpass);
            $user->password = $newpassword;
            $user->save();

            /** EMAIL SENT TO CLIENTS */
            
            $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                        ->where('id','=',$parent_company)
                                        ->get();
            $agencyname = "";
            $agencyurl = "";
            if (count($agencycompany) > 0) {
                $agencyname = $agencycompany[0]['company_name'];
                $agencyurl = $agencycompany[0]['subdomain'];
                if ($agencycompany[0]['domain'] != '' && $agencycompany[0]['status_domain'] == 'ssl_acquired') {
                    $agencyurl = $agencycompany[0]['domain'];
                }
            }

            $companyInfo = Company::select('company_name')
                                    ->where('id','=',$companyID)
                                    ->get();

            $companyName = "";
            if (count($companyInfo) > 0) {
                $companyName = $companyInfo[0]['company_name'];
            }

            $AdminDefault = $this->get_default_admin($parent_company);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';

            $defaultdomain = $this->getDefaultDomainEmail($parent_company);

            if ($_usertype == 'client') {
                $details = [
                    'agencyname' => ucwords($agencyname),
                    'agencyurl' => 'https://' . $agencyurl,
                    'defaultadmin' => $AdminDefaultEmail,
                    'username' => $clientEmail,
                    'name'  => $clientFullName,
                    'newpass' => $genpass,
                ];

                $from = [
                    'address' => 'noreply@' . $defaultdomain,
                    'name' => 'Welcome',
                    'replyto' => 'support@' . $defaultdomain,
                ];


                    $company_id = $user->company_parent;
                    if ($user->user_type == 'userdownline') {
                        $company_id = $user->commpany_id;
                    }
                    
                    $this->send_email(array($user->email),$from,$companyName . ' Account Set Up from ' . $agencyname,$details,array(),'emails.userinvitation',$company_id);
            }else if ($_usertype == 'user' || $_usertype == 'userdownline') {
                $details = [
                    'defaultadmin' => $AdminDefaultEmail,
                    'agencyname' => ucwords($agencyname),
                    'agencyurl' => 'https://' . $agencyurl,
                    'username' => $clientEmail,
                    'name'  => $clientFullName,
                    'newpass' => $genpass,
                ];
    
                $from = [
                    'address' => 'noreply@' . $defaultdomain,
                    'name' => 'Welcome',
                    'replyto' => 'support@' . $defaultdomain,
                ];
                
                $this->send_email(array($user->email),$from,ucwords($agencyname) . ' Administrator Setup',$details,array(),'emails.admininvitation',$request->companyID);
            }
            /** EMAIL SENT TO CLIENTS */

            return response()->json(array('result'=>'success','message'=>'Invitation has been sent'));
        }else{
            return response()->json(array('result'=>'failed','message'=>'Invitation failed to sent'));
        }
    }
    
    private function check_subordomain_exist($subordomain) {
        $chksubordomain = Company::select('id','logo','simplifi_organizationid','domain','subdomain')
                                    ->where(function ($query) use ($subordomain) {
                                        $query->where('domain','=',$subordomain)
                                                ->orWhere('subdomain','=',$subordomain);
                                    })->where('approved','=','T')
                                    ->get();

        if(count($chksubordomain) > 0) {
            return true;
        }else{
            return false;
        }

    }
 
    public function create(Request $request) {
        $defaultParentOrganization = config('services.sifidefaultorganization.organizationid');
        $DownlineDomain = (isset($request->DownlineDomain) && $request->DownlineDomain != '')?$request->DownlineDomain:'';
        $DownlineSubDomain = (isset($request->DownlineSubDomain) && $request->DownlineSubDomain != '')?$request->DownlineSubDomain:'';
        $disabledrecieveemail = (isset($request->disabledreceivedemail))?$request->disabledreceivedemail:'F';
        $disabledaddcampaign = (isset($request->disabledaddcampaign))?$request->disabledaddcampaign:'F';

        $idsys = (isset($request->idsys))?$request->idsys:'';
        $ownedcompanyid = (isset($request->companyID))?$request->companyID:'';
        $is_whitelabeling = (isset($request->ClientWhiteLabeling))?$request->ClientWhiteLabeling:'F';

        $salesRep = (isset($request->salesRep))?$request->salesRep:'';
        $salesAE = (isset($request->salesAE))?$request->salesAE:'';
        $salesRef = (isset($request->salesRef))?$request->salesRef:'';

        /** CHECK IF EMAIL ALREADY EXIST */
        $chkusrname = strtolower($request->ClientEmail);
        $chkEmailExist = User::where(function ($query) use ($ownedcompanyid,$chkusrname) {
            $query->where('company_id','=',$ownedcompanyid)
                    ->where('email',Encrypter::encrypt($chkusrname))
                    ->where('user_type','=','user');
            })->orWhere(function ($query) use ($ownedcompanyid,$chkusrname) {
                $query->where('company_id','=',$ownedcompanyid)
                        ->where('email',Encrypter::encrypt($chkusrname))
                        ->where('user_type','=','userdownline');
            })->orWhere(function ($query) use ($ownedcompanyid,$chkusrname) {
                $query->where('company_parent','=',$ownedcompanyid)
                        ->where('email',Encrypter::encrypt($chkusrname))
                        ->where('user_type','=','client');
            })->orWhere(function ($query) use ($ownedcompanyid,$chkusrname) {
                $query->where('company_parent','=',$ownedcompanyid)
                        ->where('email',Encrypter::encrypt($chkusrname))
                        ->where('user_type','=','sales');
            })
            ->where('active','T')
            ->get();

        //$chkEmailExist = User::where('email','=',trim(Encrypter::encrypt($request->email)))->get();
        if (count($chkEmailExist) > 0 || $ownedcompanyid == '') {
            return response()->json(array('result'=>'error','message'=>'Sorry Email already registered, Please use another email Thank you!','error'=>''));
        }
        /** CHECK IF EMAIL ALREADY EXIST */

        /** GET ROOT SYS CONF */
        $confAppDomain =  config('services.application.domain');
        if ($idsys != "") {
            $conf = $this->getCompanyRootInfo($idsys);
            $confAppDomain = $conf['domain'];
        }
        /** GET ROOT SYS CONF */

        if ($DownlineDomain != "") {
            $DownlineDomain = str_replace('http://','',$DownlineDomain);
            $DownlineDomain = trim(str_replace('https://','',$DownlineDomain));
        }

        if ($DownlineSubDomain != "") {
            $DownlineSubDomain = str_replace('http://','',$DownlineSubDomain);
            $DownlineSubDomain = trim(str_replace('https://','',$DownlineSubDomain));
            $DownlineSubDomain = $DownlineSubDomain . '.' . $confAppDomain;
            if ($this->check_subordomain_exist($DownlineSubDomain)) {
                return response()->json(array('result'=>'failed','message'=>'This subdomain already exists'));
            }
        }

        $DownlineOrganizationID = (isset($request->DownlineOrganizationID) && $request->DownlineOrganizationID != '')?$request->DownlineOrganizationID:'';

        $sortorder = 0;

        if($request->userType == 'userdownline') {
            /** FIND THE LAST SORT */
           $sortResult = User::select('id','sort')->where('user_type','=','userdownline')->where('company_parent','=',$request->companyID)->orderByDesc('sort')->first();
           if (isset($sortResult)) {
            $sortorder = ($sortResult->sort + 1);
           }
           /** FIND THE LAST SORT */
        }

        $newpassword = Str::random(10);
        $company_id = '';
        $company_parent = $request->companyID;
        $isAdmin = 'T';
        $defaultAdmin = 'F';
        $customercare = 'F';
        $adminGetNotification = "F";
        $usrCompleteProfileSetup = 'F';
        $refcode = '';

        if(isset($request->defaultAdmin) && $request->defaultAdmin == 'T') {
            $defaultAdmin = 'T';
        }

        if(isset($request->adminGetNotification) && $request->adminGetNotification == 'T') {
            $adminGetNotification = 'T';
        }

        //if($request->userType == 'client' || $request->userType == 'userdownline') {
        if($request->userType == 'client') {
            $isAdmin = 'F';
        }

        if($request->userType == 'sales') {
            $isAdmin = 'T';
            $company_id = $request->companyID;
            if(isset($request->ClientPass) && $request->ClientPass != '') {
                $newpassword = $request->ClientPass;
            }
            $usrCompleteProfileSetup = 'T';
        }

        if($request->userType == 'user') {
            if(isset($request->ClientPass) && $request->ClientPass != '') {
                $newpassword = $request->ClientPass;
            }
            //$company_parent = null;
            $company_id = $request->companyID;
            if ($isAdmin == 'T') {
                $usrCompleteProfileSetup = 'T';
            }

            if(isset($request->customercare) && $request->customercare == 'T') {
                $customercare = 'T';
               
                /** UPDATE CUSTOMER CARE ONLY CAN BE ONLY ONE */
                $updcustcare = User::where('company_id','=',$company_id)
                                    ->where(function ($query) {
                                        $query->where('user_type','=','user')
                                        ->orWhere('user_type','=','userdownline');
                                    })
                                    ->update(['customercare' => 'F']);
                /** UPDATE CUSTOMER CARE ONLY CAN BE ONLY ONE */
            }
        }

        if($request->userType == 'userdownline') {
            $isAdmin = 'T';
            $defaultAdmin = 'T';
            $customercare = 'T';
            $adminGetNotification = 'T';
        }

        //two factor authenctication
        $tfa_active = 0;
        $tfa_type = null;
        if ($request->userType == 'userdownline' || $request->userType == 'user' && !empty($request->twoFactorAuth)) {
            $tfa_active = 1;
            $tfa_type = $request->twoFactorAuth;
        }
        //two factor authenctication

        /** CHECK IF EMAIL ALREADY EXIST */
        // $chkEmailExist = User::where('email','=',trim($request->ClientEmail))->get();
        // if (count($chkEmailExist) > 0) {
        //     return response()->json(array('result'=>'failed','message'=>'Sorry, email is already exist. Email will be use for your client as the username','data'=>array()));
        // }
        /** CHECK IF EMAIL ALREADY EXIST */

        // Create company is_whitelabeling
        
        $usr = User::create([
            'name' => $request->ClientFullName,
            'email' => strtolower($request->ClientEmail),
            'phonenum' => $request->ClientPhone,
            'phone_country_code' => $request->ClientPhoneCountryCode,
            'phone_country_calling_code' => $request->ClientPhoneCountryCallingCode,
            'password' => Hash::make($newpassword),
            'role_id' => $request->ClientRole,
            'company_id' => $company_id,
            'company_parent' => $company_parent,
            'company_root_id' => $idsys,
            'user_type' => $request->userType,
            'isAdmin' => $isAdmin,
            'profile_setup_completed' => $usrCompleteProfileSetup,
            'sort' => $sortorder,
            'city' => '',
            'zip' => '',
            'country_code' => '',
            'state_code' => '',
            'state_name' => '',
            'lp_limit_freq' => 'day',
            'defaultadmin' => $defaultAdmin,
            'customercare' => $customercare,
            'admin_get_notification' => $adminGetNotification,
            'acc_connect_id' => '',
            'acc_email' => '',
            'acc_ba_id' => '',
            'status_acc' => '',
            'customer_payment_id' => '',
            'customer_card_id' => '',
            'disabled_receive_email' => $disabledrecieveemail,
            'disable_client_add_campaign' => $disabledaddcampaign,
            'tfa_active' => $tfa_active,
            'tfa_type' => $tfa_type,
        ]);

        $usrID = $usr->id;

        /** CHECK IF USER DOWNLINE AND IS EMM AGENCY */
        if($request->userType == 'userdownline' && $idsys == config('services.application.systemid')) {
            $updateTrialDate = User::where('id',$usrID)
                                    ->where('active','=','T')
                                    ->where('user_type','=','userdownline')
                                    ->update([
                                        'trial_end_date' => DB::raw("DATE_ADD(created_at, INTERVAL 2 MONTH)"),
                                        'last_invoice_minspend' => DB::raw("
                                            CASE
                                                WHEN DAY(created_at) IN (29, 30, 31) THEN
                                                    DATE_ADD(LAST_DAY(created_at) + INTERVAL 1 DAY, INTERVAL 2 MONTH)
                                                ELSE
                                                    DATE_ADD(created_at, INTERVAL 2 MONTH)
                                            END
                                        "),
                                    ]);
            
        }
        /** CHECK IF USER DOWNLINE AND IS EMM AGENCY */
        
        /** CREATE REFERRAL CODE IF SALES */
        if($request->userType == 'sales') {
            $updrefcode = User::find($usrID);
            $updrefcode->referralcode = $this->generateReferralCode('salesref' . $usrID);
            $updrefcode->save();
        }
        /** CREATE REFERRAL CODE IF SALES */

        if(isset($request->ClientCompanyName) && $request->ClientCompanyName != '') {

            /** IF SUBDOMAIN EMPTY AUTO CREATE */
            if($request->userType == 'userdownline' && $DownlineSubDomain == "") {
                $_comname = explode(' ',strtolower($request->ClientCompanyName));
                $subresult = '';

                foreach ($_comname as $w) {
                    $subresult .= mb_substr($w, 0, 1);
                }

                $subresult = preg_replace('/[^a-zA-Z0-9]/', '', $subresult);

                $DownlineSubDomain = $subresult . date('ynjis') . '.' . $confAppDomain;

                while ($this->check_subordomain_exist($DownlineSubDomain)) {
                     $DownlineSubDomain = $subresult . date('ynjis') . '.' . $confAppDomain;
                }
            }
            /** IF SUBDOMAIN EMPTY AUTO CREATE */

            /** CREATE ORGANIZATION ON SIMPLI.FI */
            if (trim($DownlineOrganizationID) == "") {
                $companyParent = Company::select('simplifi_organizationid')
                                    ->where('id','=',$company_parent)
                                    ->get();
                if(count($companyParent) > 0) {
                    if ($companyParent[0]['simplifi_organizationid'] != '') {
                        $defaultParentOrganization = $companyParent[0]['simplifi_organizationid'];
                    }
                }

                /** CREATE ORGANIZATION */
                if ($request->userType == 'userdownline') { 
                    $sifiEMMStatus = "[AGENCY]";
                    if (config('services.appconf.devmode') === true) {
                        $sifiEMMStatus = "[AGENCY BETA]";
                    }
                }else if ($request->userType == 'client') {
                    $sifiEMMStatus = "[CLIENT]";
                    if (config('services.appconf.devmode') === true) {
                        $sifiEMMStatus = "[CLIENT BETA]";
                    }
                }
                $DownlineOrganizationID = $this->createOrganization(trim($request->ClientCompanyName) . ' ' . $sifiEMMStatus,$defaultParentOrganization);
                /** CREATE ORGANIZATION */
            }
            /** CREATE ORGANIZATION ON SIMPLI.FI */

            /** UPDATE DEFAULT PAYMENT IF SETTING EXIST IF NOT THEN USE DEFAULT DB */
            $_paymentterm_default = "Weekly";

            if ($request->userType == 'userdownline') { 
                $getRootSetting = $this->getcompanysetting($idsys,'rootsetting');
                if ($getRootSetting != '') {
                    if (isset($getRootSetting->defaultpaymentterm) && $getRootSetting->defaultpaymentterm != '') {
                        $_paymentterm_default = trim($getRootSetting->defaultpaymentterm);
                    }
                }
            }
            /** UPDATE DEFAULT PAYMENT IF SETTING EXIST IF NOT THEN USE DEFAULT DB */

            $newCompany = Company::create([
                'company_name' => $request->ClientCompanyName,
                'company_city' => '',
                'company_zip' => '',
                'company_country_code' => '',
                'company_state_code' => '',
                'company_state_name' => '', 
                'simplifi_organizationid' => $DownlineOrganizationID,
                'domain' => $DownlineDomain,
                'subdomain' => $DownlineSubDomain,
                'sidebar_bgcolor' => '',
                'template_bgcolor' => '',
                'box_bgcolor' => '',
                'font_theme' => '',
                'login_image' => '',
                'client_register_image' => '',
                'agency_register_image' => '',
                'approved' => 'T',
                'paymentterm_default' => $_paymentterm_default
            ]);

            $newCompanyID = $newCompany->id;

            $usr->company_id = $newCompanyID;
            $usr->save();

            if ($request->userType == 'userdownline') { 
                /** CREATE DEFAULT PRICE FOR AGENCY */

                    $comset_val = [
                        "local" => [
                            "Monthly" => [
                                    "LeadspeekCostperlead" => '0.10',
                                    "LeadspeekMinCostMonth" => '0',
                                    "LeadspeekPlatformFee" => '0'
                            ],
                            "OneTime" => [
                                "LeadspeekCostperlead" => "0.10",
                                "LeadspeekMinCostMonth" => "0",
                                "LeadspeekPlatformFee" => "0",
                            ],
                            "Weekly" => [
                                "LeadspeekCostperlead" => "0.10",
                                "LeadspeekMinCostMonth" => "0",
                                "LeadspeekPlatformFee" => "0",
                            ]
                        ],

                        "locator" => [
                            "Monthly" => [
                                    "LocatorCostperlead" => '1.29',
                                    "LocatorMinCostMonth" => '0',
                                    "LocatorPlatformFee" => '0'
                            ],
                            "OneTime" => [
                                "LocatorCostperlead" => "1.29",
                                "LocatorMinCostMonth" => "0",
                                "LocatorPlatformFee" => "0",
                            ],
                            "Weekly" => [
                                "LocatorCostperlead" => "1.29",
                                "LocatorMinCostMonth" => "0",
                                "LocatorPlatformFee" => "0",
                            ]
                        ],

                        "enhance" => [
                            "Monthly" => [
                                    "EnhanceCostperlead" => '0.10',
                                    "EnhanceMinCostMonth" => '0',
                                    "EnhancePlatformFee" => '0'
                            ],
                            "OneTime" => [
                                "EnhanceCostperlead" => "0.10",
                                "EnhanceMinCostMonth" => "0",
                                "EnhancePlatformFee" => "0",
                            ],
                            "Weekly" => [
                                "EnhanceCostperlead" => "0.10",
                                "EnhanceMinCostMonth" => "0",
                                "EnhancePlatformFee" => "0",
                            ]
                        ],
                    ];

                    /** GET DEFAULT ROOT COST AGENCY */
                    $comset_val = $this->getcompanysetting($idsys,'rootcostagency');
                    /** GET DEFAULT ROOT COST AGENCY */

                    $createsetting = CompanySetting::create([
                        'company_id' => $newCompanyID,
                        'setting_name' => 'costagency',
                        'setting_value' => json_encode($comset_val),
                    ]);
                
                /** CREATE DEFAULT PRICE FOR AGENCY */

                // AGENCY PAYMENT TERM

                // Create company is_whitelabeling
                if($is_whitelabeling == 'T'){
                    $company = Company::find($newCompanyID);
                    if ($company) {
                        $company->is_whitelabeling = 'T';
                        $company->save();
                    }
                } else {
                    $company = Company::find($newCompanyID);
                    if ($company) {
                        $company->is_whitelabeling = 'F';
                        $company->save();
                    }
                }

                // SAVE PAYMENTTERMCONTROL
                try {
                    $paymentterm = $request->selectedterms;
                    $allFalse = true;
                    foreach ($paymentterm as $term) {
                        if ($term['status'] === true) {
                            $allFalse = false;
                            break;
                        }
                    }
                    if (!$allFalse) {
                        $paymentterm = [
                            "SelectedPaymentTerm" => $request->selectedterms,
                        ];
                        $createsetting = CompanySetting::create([
                            'company_id' => $newCompanyID,
                            'setting_name' => 'agencypaymentterm',
                            'setting_value' => json_encode($paymentterm),
                        ]);
                    }
                } catch (\Throwable $th) {
                    return response()->json(['error' => $th->getMessage()]);
                }
                // SAVE PAYMENTTERMCONTROL

                /** SET SALES, AE OR REFERRAL IF ANY */
                if (trim($salesRep) != '') {
                    /** FOR SALE REPS */
                    $chkSalesCompany = CompanySale::select('id')
                                                    ->where('company_id','=',$newCompanyID)
                                                    ->where('sales_title','=','Sales Representative')
                                                    ->get();
        
                    if (count($chkSalesCompany) == 0) {
                        if (trim($salesRep) != "") {
                            $createSalesRep = CompanySale::create([
                                                    'company_id' => $newCompanyID,
                                                    'sales_id' => $salesRep,
                                                    'sales_title' => 'Sales Representative',
                                                ]);
                        }
                    }else{
                        if (trim($salesRep) != "") {
                            $updateSalesRep = CompanySale::find($chkSalesCompany[0]['id']);
                            $updateSalesRep->sales_id = $salesRep;
                            $updateSalesRep->save();
                        }else{
                            $deleteSalesRep = CompanySale::find($chkSalesCompany[0]['id']);
                            $deleteSalesRep->delete();
                        }
                    }
                    /** FOR SALE REPS */
                }

                if (trim($salesAE) != '') {
                    /** FOR Account Executive */
                    $chkSalesCompany = CompanySale::select('id')
                                                    ->where('company_id','=',$newCompanyID)
                                                    ->where('sales_title','=','Account Executive')
                                                    ->get();
        
                    if (count($chkSalesCompany) == 0) {
                        if (trim($salesAE) != "") {
                            $createSalesAE = CompanySale::create([
                                                    'company_id' => $newCompanyID,
                                                    'sales_id' => $salesAE,
                                                    'sales_title' => 'Account Executive',
                                                ]);
                        }
                    }else{
                        if (trim($salesAE) != "") {
                            $updateSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                            $updateSalesAE->sales_id = $salesAE;
                            $updateSalesAE->save();
                        }else{
                            $deleteSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                            $deleteSalesAE->delete();
                        }
                    }
                    /** FOR Account Executive */
                }

                if (trim($salesRef) != '') {
                    
                    /** FOR SALES REFERRAL */
                    $chkSalesCompany = CompanySale::select('id')
                                                    ->where('company_id','=',$newCompanyID)
                                                    ->where('sales_title','=','Account Referral')
                                                    ->get();
        
                    if (count($chkSalesCompany) == 0) {
                        if (trim($salesRef) != "") {
                            
                            $createSalesRef = CompanySale::create([
                                                    'company_id' => $newCompanyID,
                                                    'sales_id' => $salesRef,
                                                    'sales_title' => 'Account Referral',
                                                ]);
                        }
                    }else{
                        if (trim($salesRef) != "") {
                            $updateSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                            $updateSalesAE->sales_id = $salesRef;
                            $updateSalesAE->save();
                        }else{
                            $deleteSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                            $deleteSalesAE->delete();
                        }
                    }
                    /** FOR SALES REFERRAL */
                }
                
                /** SET SALES, AE OR REFERRAL IF ANY */
            } elseif ($request->userType == 'client') {
                //SAVE CLIENT SIDEBAR CONTROL
                $clientsidebar_setting = CompanySetting::where('company_id', $newCompanyID)
                ->whereEncrypted('setting_name', 'clientsidebar')
                ->first();

                $clientsidebar = [];

                if (!empty($request->selectedsidebar)) {
                    $clientsidebar = [
                    "SelectedSideBar" => $request->selectedsidebar,
                    ];
                }

                if ($clientsidebar_setting && !empty($request->selectedsidebar)) {
                    // Update data yang ada
                    $clientsidebar_setting->setting_value = json_encode($clientsidebar);
                    $clientsidebar_setting->save();
                } else {
                    if ($clientsidebar != []) {
                        $createsetting = CompanySetting::create([
                        'company_id' => $newCompanyID,
                        'setting_name' => 'clientsidebar',
                        'setting_value' => json_encode($clientsidebar),
                        ]);
                    }
                } 
            }
        }

        if(isset($request->ClientDomain) && $request->ClientDomain != '') {
            $newdomain = str_replace('http://','',$request->ClientDomain);
            $newdomain = trim(str_replace('https://','',$newdomain));

            $newSite = Site::create([
                'company_name' => $request->ClientCompanyName,
                'domain' => $newdomain,
            ]);

            $newsiteID = $newSite->id;

            $usr->site_id = $newsiteID;
            $usr->save();
        }

        /** EMAIL SENT TO CLIENTS */
        if($request->userType == 'client') {
            $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                        ->where('id','=',$request->companyID)
                                        ->get();
            $agencyname = "";
            $agencyurl = "";
            if (count($agencycompany) > 0) {
                $agencyname = $agencycompany[0]['company_name'];
                $agencyurl = $agencycompany[0]['subdomain'];
                if ($agencycompany[0]['domain'] != '' && $agencycompany[0]['status_domain'] == 'ssl_acquired') {
                    $agencyurl = $agencycompany[0]['domain'];
                    /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                    $ip = gethostbyname(trim($agencycompany[0]['domain']));
                    if ($ip == '157.230.213.72') {
                        $agencyurl = $agencycompany[0]['domain'];
                    }else{
                        $agencyurl = $agencycompany[0]['subdomain'];
                    }
                    /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                }
            }

            // $AdminDefault = $this->get_default_admin($request->companyID);
            // $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';

            // $details = [
            //     'agencyname' => ucwords($agencyname),
            //     'agencyurl' => 'https://' . $agencyurl,
            //     'defaultadmin' => $AdminDefaultEmail,
            //     'username' => $request->ClientEmail,
            //     'name'  => $request->ClientFullName,
            //     'newpass' => $newpassword,
            // ];
            /** START NEW METHOD EMAIL */
            $from = [
                'address' => 'noreply@sitesettingsapi.com',
                'name' => 'Welcome',
                'replyto' => 'noreply@sitesettingsapi.com',
            ];

            $smtpusername = $this->set_smtp_email($company_parent);
            $emailtype = 'em_clientwelcomeemail';

            $customsetting = $this->getcompanysetting($company_parent,$emailtype);
            $chkcustomsetting = $customsetting;

            if ($customsetting == '') {
                $customsetting =  json_decode(json_encode($this->check_email_template($emailtype,$company_parent)));
            }

            $finalcontent = nl2br($this->filterCustomEmail($usr,$company_parent,$customsetting->content,$newpassword,$agencyurl));
            $finalsubject = $this->filterCustomEmail($usr,$company_parent,$customsetting->subject,$newpassword,$agencyurl);
            $finalfrom = $this->filterCustomEmail($usr,$company_parent,$customsetting->fromName,$newpassword,$agencyurl);

            $details = [
                'title' => ucwords($finalsubject),
                'content' => $finalcontent,
            ];
            
            $from = [
                'address' => (isset($customsetting->fromAddress) && $customsetting->fromAddress != '')?$customsetting->fromAddress:'noreply@sitesettingsapi.com',
                'name' => (isset($finalfrom) && $finalfrom != '')?$finalfrom:'Welcome',
                'replyto' => (isset($customsetting->fromReplyto) && $customsetting->fromReplyto != '')?$customsetting->fromReplyto:'support@sitesettingsapi.com',
            ];

            if ($smtpusername != "" && $chkcustomsetting == "") {
                $from = [
                    'address' => $smtpusername,
                    'name' => (isset($finalfrom) && $finalfrom != '')?$finalfrom:'Welcome',
                    'replyto' => $smtpusername,
                ];
            }
            
            $this->send_email(array($request->ClientEmail),$from,ucwords($finalsubject),$details,array(),'emails.customemail',$company_parent);
            /** START NEW METHOD EMAIL */
            //$this->send_email(array($request->ClientEmail),$from,ucwords($agencyname) . ' Account Setup',$details,array(),'emails.userinvitation',$request->companyID);
        }else if ($request->userType == 'userdownline') {
            $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                ->where('id','=',$idsys)
                                ->get();

            // $agencyname = " ";
            // $defaultdomain = $this->getDefaultDomainEmail($idsys);
            // if (count($agencycompany) > 0) {
            //     $agencyname = $agencycompany[0]['company_name'] . " ";
            // }

            // $details = [
            //     'username' => $request->ClientEmail,
            //     'name'  => $request->ClientFullName,
            //     'newpass' => $newpassword,
            //     'domain' => $DownlineDomain,
            //     'subdomain' => $DownlineSubDomain,
            // ];

            // $from = [
            //     'address' => 'noreply@' . $defaultdomain,
            //     'name' => 'Welcome',
            //     'replyto' => 'support@' . $defaultdomain,
            // ];
            // $this->send_email(array($request->ClientEmail),$from, $agencyname . 'Agency Setup',$details,array(),'emails.agencysetup','');

            /** START NEW EMAIL METHOD */
            $agencyname = "";
            $agencyurl = "";
            if (count($agencycompany) > 0) {
                $agencyname = $agencycompany[0]['company_name'];
                $agencyurl = $agencycompany[0]['subdomain'];
                if ($agencycompany[0]['domain'] != '') {
                    $agencyurl = $agencycompany[0]['domain'];
                    /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                    $ip = gethostbyname(trim($agencycompany[0]['domain']));
                    if ($ip == '157.230.213.72' || $ip == '146.190.186.110' || $ip == '143.244.212.205') {
                        $agencyurl = $agencycompany[0]['domain'];
                    }else{
                        $agencyurl = $agencycompany[0]['subdomain'];
                    }
                    /** CHECK IF DOMAIN ALREADY POINTED TO OUR IP */
                }
            }

            if($DownlineSubDomain != "") {
                $agencyurl = 'https://' . $DownlineSubDomain;
            }
                

            $from = [
                'address' => 'noreply@sitesettingsapi.com',
                'name' => 'Welcome',
                'replyto' => 'noreply@sitesettingsapi.com',
            ];

            $smtpusername = $this->set_smtp_email($company_parent);
            $emailtype = 'em_agencywelcomeemail';

            $customsetting = $this->getcompanysetting($company_parent,$emailtype);
            $chkcustomsetting = $customsetting;

            if ($customsetting == '') {
                $customsetting =  json_decode(json_encode($this->check_email_template($emailtype,$company_parent)));
            }

            $finalcontent = nl2br($this->filterCustomEmail($usr,$company_parent,$customsetting->content,$newpassword,$agencyurl));
            $finalsubject = $this->filterCustomEmail($usr,$company_parent,$customsetting->subject,$newpassword,$agencyurl);
            $finalfrom = $this->filterCustomEmail($usr,$company_parent,$customsetting->fromName,$newpassword,$agencyurl);

            $details = [
                'title' => ucwords($finalsubject),
                'content' => $finalcontent,
            ];
            
            $from = [
                'address' => (isset($customsetting->fromAddress) && $customsetting->fromAddress != '')?$customsetting->fromAddress:'noreply@sitesettingsapi.com',
                'name' => (isset($finalfrom) && $finalfrom != '')?$finalfrom:'Welcome',
                'replyto' => (isset($customsetting->fromReplyto) && $customsetting->fromReplyto != '')?$customsetting->fromReplyto:'support@sitesettingsapi.com',
            ];

            if ($smtpusername != "" && $chkcustomsetting == "") {
                $from = [
                    'address' => $smtpusername,
                    'name' => (isset($finalfrom) && $finalfrom != '')?$finalfrom:'Welcome',
                    'replyto' => $smtpusername,
                ];
            }
            
            $this->send_email(array($request->ClientEmail),$from,ucwords($finalsubject),$details,array(),'emails.customemail',$company_parent);
            /** START NEW EMAIL METHOD */

            /** SEND EMAIL NOTIFICATION NEW REGISTER */
            $AccountType = 'Agency account';
             
            $tmp = User::select('email')->where('company_id','=',$ownedcompanyid)->where('active','T')
                    ->where(function($query) {
                        $query->where('user_type','=','user')
                                ->orWhere('user_type','=','userdownline');
                    })
                    ->where('isAdmin','=','T')
                    ->where('active','=','T')
                    ->orderByEncrypted('name')->get();
            $adminEmail = array();
            foreach($tmp as $ad) {
                array_push($adminEmail,$ad['email']);
            }

            $AdminDefault = $this->get_default_admin($ownedcompanyid);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';
            $defaultdomain = $this->getDefaultDomainEmail($ownedcompanyid);

            $details = [
            'username' => strtolower($request->ClientEmail),
            'name'  => $request->ClientFullName,
            'domain' => $DownlineSubDomain,
            'accounttype' => $AccountType,
            'defaultadmin' => $AdminDefaultEmail,
            ];

            $from = [
            'address' => 'noreply@' . $defaultdomain,
            'name' => 'New Account Registered',
            'replyto' => 'support@' . $defaultdomain,
            ];

            $this->send_email($adminEmail,$from,'New ' . $AccountType . ' Registered',$details,array(),'emails.adminnewaccountregister',$ownedcompanyid);
            /** SEND EMAIL NOTIFICATION NEW REGISTER */

            

        }else if($request->userType == 'user') {
            $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                        ->where('id','=',$request->companyID)
                                        ->get();
            $agencyname = "";
            $agencyurl = "";
            if (count($agencycompany) > 0) {
                $agencyname = $agencycompany[0]['company_name'];
                $agencyurl = $agencycompany[0]['subdomain'];
                if ($agencycompany[0]['domain'] != '' && $agencycompany[0]['status_domain'] == 'ssl_acquired') {
                    $agencyurl = $agencycompany[0]['domain'];
                }
            }

            $AdminDefault = $this->get_default_admin($request->companyID);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';

            $defaultdomain = $this->getDefaultDomainEmail($request->companyID);

            $details = [
                'defaultadmin' => $AdminDefaultEmail,
                'agencyname' => ucwords($agencyname),
                'agencyurl' => 'https://' . $agencyurl,
                'username' => $request->ClientEmail,
                'name'  => $request->ClientFullName,
                'newpass' => $newpassword,
            ];

            $from = [
                'address' => 'noreply@' . $defaultdomain,
                'name' => 'Welcome',
                'replyto' => 'support@' . $defaultdomain,
            ];
            
            $this->send_email(array($request->ClientEmail),$from,ucwords($agencyname) . ' Administrator Setup',$details,array(),'emails.admininvitation',$request->companyID);
        }else if($request->userType == 'sales') {
            $agencycompany = Company::select('company_name','domain','subdomain','status_domain')
                                        ->where('id','=',$request->companyID)
                                        ->get();
            $agencyname = "";
            $agencyurl = "";
            if (count($agencycompany) > 0) {
                $agencyname = $agencycompany[0]['company_name'];
                $agencyurl = $agencycompany[0]['subdomain'];
                if ($agencycompany[0]['domain'] != '' && $agencycompany[0]['status_domain'] == 'ssl_acquired') {
                    $agencyurl = $agencycompany[0]['domain'];
                }
            }

            $AdminDefault = $this->get_default_admin($request->companyID);
            $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';

            $defaultdomain = $this->getDefaultDomainEmail($request->companyID);

            $details = [
                'defaultadmin' => $AdminDefaultEmail,
                'agencyname' => ucwords($agencyname),
                'agencyurl' => 'https://' . $agencyurl,
                'username' => $request->ClientEmail,
                'name'  => $request->ClientFullName,
                'newpass' => $newpassword,
            ];

            $from = [
                'address' => 'noreply@' . $defaultdomain,
                'name' => 'Welcome',
                'replyto' => 'support@' . $defaultdomain,
            ];
            
            $this->send_email(array($request->ClientEmail),$from,ucwords($agencyname) . ' Sales Account Setup',$details,array(),'emails.salesinvitation',$request->companyID);
        }
        
        /** EMAIL SENT TO CLIENTS */

        if($request->userType == 'client') {
            $temp = User::select('users.*','companies.company_name')->join('companies','companies.id','=','users.company_id')->where('users.id',$usrID)->get();
        }else if($request->userType == 'userdownline') {
            $temp =  User::select('users.*','companies.company_name')->join('companies','companies.id','=','users.company_id')->where('users.id',$usrID)->get();
            if (trim($salesRep) != '' || trim($salesAE) != '' || trim($salesRef) != '') {
                /** CHECK SALES  */
                $chksales = User::select('users.id','users.name','company_sales.sales_title')
                                    ->join('company_sales','users.id','=','company_sales.sales_id')
                                    ->where('company_sales.company_id','=',$temp[0]['company_id'])
                                    ->where('users.active','=','T')
                                    ->get();

                $compsalesrepID = "";
                $compsalesrep = "";
                $compaccountexecutive = "";
                $compaccountexecutiveID = "";
                $compaccountref = "";
                $compaccountrefID = "";

                foreach($chksales as $sl) {
                    if ($sl['sales_title'] == "Sales Representative") {
                        $compsalesrepID = $sl['id'];
                        $compsalesrep = $sl['name'];
                    }
                    if ($sl['sales_title'] == "Account Executive") {
                        $compaccountexecutiveID = $sl['id'];
                        $compaccountexecutive = $sl['name'];
                    }

                    if ($sl['sales_title'] == "Account Referral") {
                        $compaccountrefID = $sl['id'];
                        $compaccountref = $sl['name'];
                    }
                }
                foreach($temp as $tmp) {
                    $tmp['salesrepid'] = $compsalesrepID;
                    $tmp['salesrep'] = $compsalesrep;
                    $tmp['accountexecutiveid'] = $compaccountexecutiveID;
                    $tmp['accountexecutive'] = $compaccountexecutive;
                    $tmp['accountrefid'] = $compaccountrefID;
                    $tmp['accountref'] = $compaccountref;
                }
                /** CHECK SALES */
            }
        }else if($request->userType == 'user') {
            $temp = User::where('users.id',$usrID)->where('active','T')->where('user_type','=','user')->get();
        }else if($request->userType == 'sales') {
            $temp = User::where('users.id',$usrID)->where('active','T')->where('user_type','=','sales')->get();
        }

        return response()->json(array('result'=>'success','message'=>'','data'=>$temp));

    }

    public function remove(Request $request) {
        $UserID = (isset($request->UserID))?$request->UserID:'';
        $CompanyID = (isset($request->CompanyID))?$request->CompanyID:'';
        $user = User::find($UserID);
        //$user->active = 'F';
        //$user->save();
        
        /** CHECK RUN OR PAUSE CAMPAIGN THAT STILL ACTIVE */
        $http = new \GuzzleHttp\Client;
        $appkey = config('services.trysera.api_id');
        $domain = config('services.trysera.domain');

        if ($user->user_type == "client") {
            $chkinvalidusr = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.user_id','leadspeek_users.leadspeek_api_id','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','leadspeek_users.campaign_name','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.active_user',
                                            'companies.id as company_id','companies.company_name','leadspeek_users.trysera')
                                        ->join('users','leadspeek_users.user_id','=','users.id')
                                        ->join('companies','users.company_id','=','companies.id')
                                        ->where('users.user_type','=','client')
                                        ->where('leadspeek_users.user_id','=',$UserID)
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
                return response()->json(array('result'=>'failed','message'=>"There are active campaigns still running for this client, Please stop all campaigns prior to deleting."));
                exit;die();

                foreach($chkinvalidusr as $inv) {
                    /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                    $updateleadusr = LeadspeekUser::find($inv['id']);
                    $updateleadusr->activex = 'F';
                    $updateleadusr->disabled = 'T';
                    $updateleadusr->active_user = 'F';
                    $updateleadusr->save();
                    
                    /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                    $_company_id = $inv['company_id'];
                    $_user_id = $inv['user_id'];
                    $_lp_user_id = $inv['id'];
                    $_leadspeek_api_id = $inv['leadspeek_api_id'];
                    $organizationid = $inv['leadspeek_organizationid'];
                    $campaignsid = $inv['leadspeek_campaignsid'];

                    /** GET COMPANY NAME AND CUSTOM ID */
                    $tryseraCustomID =  '3_' . $_company_id . '00' . $_user_id . '_' . $_lp_user_id . '_' . date('His');
                    /** GET COMPANY NAME AND CUSTOM ID */

                    $campaignName = '';
                    if (isset($inv['campaign_name']) && trim($inv['campaign_name']) != '') {
                        $campaignName = ' - ' . str_replace($_leadspeek_api_id,'',$inv['campaign_name']);
                    }

                    $company_name = str_replace($_leadspeek_api_id,'',$inv['company_name']) . $campaignName;

                    if ($inv['trysera'] == 'T') {
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

                    if ($organizationid != '' && $campaignsid != '') {
                        $this->startPause_campaign($organizationid,$campaignsid,'stop');
                    }
                
                }

            }else{
                $user->active = 'F';
                $user->save();
                return response()->json(array('result'=>'success'));
            }

        }else if($user->user_type == "userdownline") {
            $chkNotActiveAgency =  User::select('company_id')
                                        ->where('id','=',$UserID)
                                        ->where('user_type','=','userdownline')
                                        ->get();;

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
                                        return response()->json(array('result'=>'failed','message'=>"There are some agency's client still have campaign running, please stop the campaign before remove the client"));
                                        exit;die();

                                        foreach($chkinvalidusr as $inv) {
                                            /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                                            $updateleadusr = LeadspeekUser::find($inv['id']);
                                            $updateleadusr->activex = 'F';
                                            $updateleadusr->disabled = 'T';
                                            $updateleadusr->active_user = 'F';
                                            $updateleadusr->save();
                            
                                             /** DISABLED THE TRYSERA ALSO MAKE IT IN ACTIVE */
                                             $_company_id = $inv['company_id'];
                                             $_user_id = $inv['user_id'];
                                             $_lp_user_id = $inv['id'];
                                             $_leadspeek_api_id = $inv['leadspeek_api_id'];
                                             $organizationid = $inv['leadspeek_organizationid'];
                                             $campaignsid = $inv['leadspeek_campaignsid'];
                                             $tryseramethod = (isset($inv['trysera']) && $inv['trysera'] == "T")?true:false;

                                             /** DISABLED CLIENT */
                                                $updateUser = User::find($_user_id);
                                                $updateUser->active = "F";
                                                $updateUser->save();
                                             /** DISABLED CLIENT */
                            
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
                                             
                                             /** ACTIVATE CAMPAIGN SIMPLIFI */
                                             if ($organizationid != '' && $campaignsid != '' && $inv['leadspeek_type'] == "locator") {
                                                 $camp = $this->startPause_campaign($organizationid,$campaignsid,'stop');
                                             }
                                             /** ACTIVATE CAMPAIGN SIMPLIFI */
                            
                                            /** MAKE IT THE CAMPAIGN PAUSED AND STOP THE SIMPLIFI AND TRYSERA */
                            
                                        }
                                    }else{
                                        $user->active = 'F';
                                        $user->save();
                                        return response()->json(array('result'=>'success'));
                                    }
                                    

                }
            }

        }else if($user->user_type == "user" || $user->user_type == "sales") {
            $user->active = 'F';
            $user->save();
            return response()->json(array('result'=>'success'));
        }
        /** CHECK RUN OR PAUSE CAMPAIGN THAT STILL ACTIVE */
    }

    private function _startPause_campaign($_organizationID,$_campaignsID,$status='') {
        $http = new \GuzzleHttp\Client;

        $appkey = "86bb19a0-43e6-0139-8548-06b4c2516bae";
        $usrkey = "63c52610-87cd-0139-b15f-06a60fe5fe77";
        $organizationID = $_organizationID;
        $campaignsID = explode(PHP_EOL, $_campaignsID);

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
                                    'address' => 'noreply@exactmatchmarketing.com',
                                    'name' => 'Support',
                                    'replyto' => 'support@exactmatchmarketing.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log for Activate Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog','');
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
                                    'address' => 'noreply@exactmatchmarketing.com',
                                    'name' => 'Support',
                                    'replyto' => 'support@exactmatchmarketing.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log for Pause Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog','');
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
                                    'address' => 'noreply@exactmatchmarketing.com',
                                    'name' => 'Support',
                                    'replyto' => 'support@exactmatchmarketing.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log for Pause Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog','');
                            }
                        }
                    }
                    //echo $result->campaigns[0]->actions[$j]->activate[0];
                }
                
                //return response()->json(array("result"=>'success','message'=>'xx','param'=>$result));
                /** CHECK ACTIONS IF CAMPAIGN ALLOW TO RUN STATUS  */
            }catch (\GuzzleHttp\Exception\BadResponseException $e) {
                $details = [
                    'errormsg'  => 'Error when trying to get campaign information Organization ID : ' . $organizationID . ' Campaign ID :' . $campaignsID[$i] . '(' . $e->getCode() . ')',
                ];
                $from = [
                    'address' => 'noreply@exactmatchmarketing.com',
                    'name' => 'Support',
                    'replyto' => 'support@exactmatchmarketing.com',
                ];
                $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log for Start / Pause Get Campaign ' . $e->getCode(),$details,array(),'emails.tryseramatcherrorlog','');

                if ($e->getCode() === 400) {
                    return response()->json(array("result"=>'failed','message'=>'Invalid Request. Please enter a username or a password.'), $e->getCode());
                } else if ($e->getCode() === 401) {
                    return response()->json(array("result"=>'failed','message'=>'Your credentials are incorrect. Please try again'), $e->getCode());
                }

                return response()->json(array("result"=>'failed','message'=>'Something went wrong on the server.'), $e->getCode());
            }
            
        }
        
        
    }

    private function createOrganization($organizationName,$parentOrganization = "",$customID="") {
        $http = new \GuzzleHttp\Client;

        $appkey = config('services.simplifi.app_key');
        $usrkey = config('services.simplifi.usr_key');
        $apiURL = config('services.simplifi.endpoint') . "organizations";
        
        $parentID = (trim($parentOrganization) == "")?config('services.sifidefaultorganization.organizationid'):trim($parentOrganization);

        $organizationName = $this->makeSafeTitleName($organizationName);

        try {
            $options = [
                'headers' => [
                    'X-App-Key' => $appkey,        
                    'X-User-Key' => $usrkey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    "organization" =>[
                        "name" => $organizationName . ' - ' . date('His'),
                        "parent_id" => $parentID,
                        "custom_id" => $customID
                    ]
                ]
            ]; 
            
           
            $response = $http->post($apiURL,$options);
            $result =  json_decode($response->getBody());
            
            return $result->organizations[0]->id;

        }catch (\GuzzleHttp\Exception\BadResponseException $e) {
            // if ($e->getCode() === 400) {
            //     return "";
            // } else if ($e->getCode() === 401) {
            //     return "";
            // }

            $details = [
                'errormsg'  => 'Error when trying to create SIFI Organization : ' . $organizationName . ' parent ID :' . $parentID . ' (' . $e->getMessage() . ')',
            ];

            $from = [
                'address' => 'noreply@exactmatchmarketing.com',
                'name' => 'Support',
                'replyto' => 'support@exactmatchmarketing.com',
            ];
            $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log SIFI Create Organization :' . $organizationName . ' parent ID:' . $parentID . '(Apps DATA - createOrganization - ConfigurationCont - L1940) ',$details,array(),'emails.tryseramatcherrorlog','');

            return "";
        }

    }

    public function testemail(Request $request) {

        // Validate the request
        $request->validate([
            'fromAddress' => 'required',
            'fromName' => 'required|string',
            'fromReplyto' => 'required|email',
            'subject' => 'required|string',
            'content' => 'required|string',
            'testEmailAddress' => 'required|email',
            'companyID' => 'required|integer'
        ]);

        // Extract data from request
        $fromAddress = $request->fromAddress;
        $fromName = $request->fromName;
        $fromReplyto = $request->fromReplyto;
        $subject = $request->subject;
        $content = $request->content;
        $testEmailAddress = $request->testEmailAddress;
        $companyID = $request->companyID;
        $userType = $request->userType;

        // Setup SMTP configuration based on companyID
        $smtpusername = "";
        if ($userType == 'userdownline' || $userType == 'user') {
            $smtpusername = $this->set_smtp_email($companyID);
        }
        // Prepare email details
        $details = [
            'title' => ucwords($subject),
            'content' => nl2br($content),
        ];
    
        $from = [
            'address' => $fromAddress,
            'name' => $fromName,
            'replyto' => $fromReplyto,
        ];
            // Override 'from' address if SMTP username is set
        if ($smtpusername != "") {
            $from['address'] = $smtpusername;
            $from['replyto'] = $smtpusername;
        }
    
        // Send the email
        try {
            $this->send_email([$testEmailAddress], $from, ucwords($subject), $details, [], 'emails.customemail');
            return response()->json('Test email sent successfully', 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to send test email', 'error' => $e->getMessage()], 500);
        }
    }


    public function updategeneralsetting(Request $request) {
        $companyID = (isset($request->companyID))?$request->companyID:'';
        $actionType = (isset($request->actionType))?$request->actionType:'';

        $sidebarcolor = (isset($request->sidebarcolor))?$request->sidebarcolor:'';
        $templatecolor = (isset($request->templatecolor))?$request->templatecolor:'';
        $boxcolor = (isset($request->boxcolor))?$request->boxcolor:'';
        $textcolor = (isset($request->textcolor))?$request->textcolor:'';
        $linkcolor = (isset($request->linkcolor))?$request->linkcolor:'';

        $fonttheme = (isset($request->fonttheme))?$request->fonttheme:'';

        $paymenttermDefault = (isset($request->paymenttermDefault))?$request->paymenttermDefault:'';

        $comset_name = (isset($request->comsetname))?$request->comsetname:'';
        $comset_val = (isset($request->comsetval))?$request->comsetval:'';

        if ($actionType == 'colortheme') {
            $company = Company::find($companyID);
            $company->sidebar_bgcolor = $sidebarcolor;
            $company->template_bgcolor = $templatecolor;
            $company->box_bgcolor = $boxcolor;
            $company->text_color = $textcolor;
            $company->link_color = $linkcolor;
            $company->save();
        }else if ($actionType == 'fonttheme') {
            $company = Company::find($companyID);
            $company->font_theme = $fonttheme;
            $company->save();
        }else if ($actionType == 'paymenttermDefault') {
            $company = Company::find($companyID);
            $company->paymentterm_default = $paymenttermDefault;
            $company->save();
        }else if ($actionType == 'custommenumodule' || $actionType == 'customsmtpmodule') {
            $companysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name',$comset_name)->get();

            if (count($companysetting) > 0) {
                $updatesetting = CompanySetting::find($companysetting[0]['id']);
                $updatesetting->setting_value = json_encode($comset_val);
                $updatesetting->save();
            }else{
                $createsetting = CompanySetting::create([
                    'company_id' => $companyID,
                    'setting_name' => $comset_name,
                    'setting_value' => json_encode($comset_val),
                ]);
            }   
        }else if ($actionType == 'agencyDefaultModule') {
            $clientsidebar_setting = CompanySetting::where('company_id', $request->companyID)
            ->whereEncrypted('setting_name', 'agencydefaultmodules')
            ->first();

            $clientsidebar = [];

            if (!empty($request->comsetval)) {
                $clientsidebar = [
                "DefaultModules" => $request->comsetval,
                ];
            }

            if ($clientsidebar_setting && $request->comsetval != []) {
                $clientsidebar_setting->setting_value = json_encode($clientsidebar);
                $clientsidebar_setting->save();
            } else {
                if ($clientsidebar != []) {
                    $createsetting = CompanySetting::create([
                    'company_id' => $request->companyID,
                    'setting_name' => 'agencydefaultmodules',
                    'setting_value' => json_encode($clientsidebar),
                    ]);
                }
            }
        }

        //$a = CompanySetting::where('id',1)->get();
        //$jr = json_decode($a[0]['setting_value']);
        return response()->json(array('result'=>'success'));
    }

    public function getgeneralsetting(Request $request) {
        $settingname = (isset($request->settingname))?$request->settingname:'';
        $companyID = (isset($request->CompanyID))?$request->CompanyID:'';
        $idSys = (isset($request->idSys))?$request->idSys:'';
        $clientdefaultprice = false;
        $rootcostagency = "";
        $clientTypeLead = [
            'type' => '',
            'value' => ''
        ];
        $clientMinLeadDayEnhance = 0;

        /** IF FOR CLIENT COST AGENCY */
        if ($settingname == "clientdefaultprice") {
            /* GET CLIENT MIN LEAD DAYS */
            $rootSetting = $this->getcompanysetting($idSys, 'rootsetting');
            $clientMinLeadDayEnhance = (isset($rootSetting->clientminleadday))?$rootSetting->clientminleadday:"";
            /* GET CLIENT MIN LEAD DAYS */
            
            $getcompanysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name',$settingname)->get();
            $companysetting = "";
            if (count($getcompanysetting) > 0) {
                $companysetting = json_decode($getcompanysetting[0]['setting_value']);
                $clientdefaultprice = true;

                if ($companysetting != "") {
                    $comset_val = $this->getcompanysetting($idSys,'rootcostagency');

                    if(!isset($companysetting->enhance)) {
    
                        $companysetting->enhance = new stdClass();
                        $companysetting->enhance->Weekly = new stdClass();
                        $companysetting->enhance->Monthly = new stdClass();
                        $companysetting->enhance->OneTime = new stdClass();
                        $companysetting->enhance->Prepaid = new stdClass();
    
                        /* WEEKLY */
                        $companysetting->enhance->Weekly->EnhanceCostperlead = $comset_val->enhance->Weekly->EnhanceCostperlead;
                        $companysetting->enhance->Weekly->EnhanceMinCostMonth = $comset_val->enhance->Weekly->EnhanceMinCostMonth;
                        $companysetting->enhance->Weekly->EnhancePlatformFee = $comset_val->enhance->Weekly->EnhancePlatformFee;
                        /* WEEKLY */
                        
                        /* MONTHLY */
                        $companysetting->enhance->Monthly->EnhanceCostperlead = $comset_val->enhance->Monthly->EnhanceCostperlead;
                        $companysetting->enhance->Monthly->EnhanceMinCostMonth = $comset_val->enhance->Monthly->EnhanceMinCostMonth;
                        $companysetting->enhance->Monthly->EnhancePlatformFee = $comset_val->enhance->Monthly->EnhancePlatformFee;
                        /* MONTHLY */
                        
                        /* ONETIME */
                        $companysetting->enhance->OneTime->EnhanceCostperlead = $comset_val->enhance->OneTime->EnhanceCostperlead;
                        $companysetting->enhance->OneTime->EnhanceMinCostMonth = $comset_val->enhance->OneTime->EnhanceMinCostMonth;
                        $companysetting->enhance->OneTime->EnhancePlatformFee = $comset_val->enhance->OneTime->EnhancePlatformFee;
                        /* ONETIME */
    
                        /* PREPAID */
                        $companysetting->enhance->Prepaid->EnhanceCostperlead = $comset_val->enhance->Prepaid->EnhanceCostperlead;
                        $companysetting->enhance->Prepaid->EnhanceMinCostMonth = $comset_val->enhance->Prepaid->EnhanceMinCostMonth;
                        $companysetting->enhance->Prepaid->EnhancePlatformFee = $comset_val->enhance->Prepaid->EnhancePlatformFee;
                        /* PREPAID */
                    }


                    if (!isset($companysetting->local->Prepaid)) {
                        $newPrepaidLocal = (object) [
                            "LeadspeekCostperlead" => $companysetting->local->Weekly->LeadspeekCostperlead,
                            "LeadspeekMinCostMonth" => $companysetting->local->Weekly->LeadspeekMinCostMonth,
                            "LeadspeekPlatformFee" => $companysetting->local->Weekly->LeadspeekPlatformFee,
                            "LeadspeekLeadsPerday" => "10"
                        ];
    
                        $newPrepaidLocator = (object) [
                            "LocatorCostperlead" => $companysetting->locator->Weekly->LocatorCostperlead,
                            "LocatorMinCostMonth" => $companysetting->locator->Weekly->LocatorMinCostMonth,
                            "LocatorPlatformFee" => $companysetting->locator->Weekly->LocatorPlatformFee,
                            "LocatorLeadsPerday" => "10"
                        ];

                        $newPrepaidEnhance = (object) [
                            "EnhanceCostperlead" => $comset_val->enhance->Prepaid->EnhanceCostperlead,
                            "EnhanceMinCostMonth" => $comset_val->enhance->Prepaid->EnhanceMinCostMonth,
                            "EnhancePlatformFee" => $comset_val->enhance->Prepaid->EnhancePlatformFee,
                            "EnhanceLeadsPerday" => "10"
                        ];

                        $companysetting->local->Prepaid = $newPrepaidLocal;
                        $companysetting->locator->Prepaid = $newPrepaidLocator;
                        $companysetting->enhance->Prepaid = $newPrepaidEnhance;
                    }
                }
            }else{
                /** FIND COMPANY PARENT AND FIND COST AGENCY DEFAULT PRICE*/
                $userClient = User::select('company_parent')->where('company_id','=',$companyID)->where('user_type','=','client')->get();
                /** FIND COMPANY PARENT AND FIND COST AGENCY DEFAULT PRICE*/
                if (count($userClient) > 0) {
                    $companyParentID = $userClient[0]['company_parent'];
                    $getcompanysetting = CompanySetting::where('company_id',$companyParentID)->whereEncrypted('setting_name','agencydefaultprice')->get();
                    
                    if (count($getcompanysetting) > 0) {
                        $companysetting = json_decode($getcompanysetting[0]['setting_value']);

                        if ($companysetting != "") {
                            $comset_val = $this->getcompanysetting($idSys,'rootcostagency');

                            if(!isset($companysetting->enhance)) {
            
                                $companysetting->enhance = new stdClass();
                                $companysetting->enhance->Weekly = new stdClass();
                                $companysetting->enhance->Monthly = new stdClass();
                                $companysetting->enhance->OneTime = new stdClass();
                                $companysetting->enhance->Prepaid = new stdClass();
            
                                /* WEEKLY */
                                $companysetting->enhance->Weekly->EnhanceCostperlead = $comset_val->enhance->Weekly->EnhanceCostperlead;
                                $companysetting->enhance->Weekly->EnhanceMinCostMonth = $comset_val->enhance->Weekly->EnhanceMinCostMonth;
                                $companysetting->enhance->Weekly->EnhancePlatformFee = $comset_val->enhance->Weekly->EnhancePlatformFee;
                                /* WEEKLY */
                                
                                /* MONTHLY */
                                $companysetting->enhance->Monthly->EnhanceCostperlead = $comset_val->enhance->Monthly->EnhanceCostperlead;
                                $companysetting->enhance->Monthly->EnhanceMinCostMonth = $comset_val->enhance->Monthly->EnhanceMinCostMonth;
                                $companysetting->enhance->Monthly->EnhancePlatformFee = $comset_val->enhance->Monthly->EnhancePlatformFee;
                                /* MONTHLY */
                                
                                /* ONETIME */
                                $companysetting->enhance->OneTime->EnhanceCostperlead = $comset_val->enhance->OneTime->EnhanceCostperlead;
                                $companysetting->enhance->OneTime->EnhanceMinCostMonth = $comset_val->enhance->OneTime->EnhanceMinCostMonth;
                                $companysetting->enhance->OneTime->EnhancePlatformFee = $comset_val->enhance->OneTime->EnhancePlatformFee;
                                /* ONETIME */
            
                                /* PREPAID */
                                $companysetting->enhance->Prepaid->EnhanceCostperlead = $comset_val->enhance->Prepaid->EnhanceCostperlead;
                                $companysetting->enhance->Prepaid->EnhanceMinCostMonth = $comset_val->enhance->Prepaid->EnhanceMinCostMonth;
                                $companysetting->enhance->Prepaid->EnhancePlatformFee = $comset_val->enhance->Prepaid->EnhancePlatformFee;
                                /* PREPAID */
                            }


                            if (!isset($companysetting->local->Prepaid)) {
                                $newPrepaidLocal = (object) [
                                    "LeadspeekCostperlead" => $companysetting->local->Weekly->LeadspeekCostperlead,
                                    "LeadspeekMinCostMonth" => $companysetting->local->Weekly->LeadspeekMinCostMonth,
                                    "LeadspeekPlatformFee" => $companysetting->local->Weekly->LeadspeekPlatformFee,
                                    "LeadspeekLeadsPerday" => "10"
                                ];
            
                                $newPrepaidLocator = (object) [
                                    "LocatorCostperlead" => $companysetting->locator->Weekly->LocatorCostperlead,
                                    "LocatorMinCostMonth" => $companysetting->locator->Weekly->LocatorMinCostMonth,
                                    "LocatorPlatformFee" => $companysetting->locator->Weekly->LocatorPlatformFee,
                                    "LocatorLeadsPerday" => "10"
                                ];

                                $newPrepaidEnhance = (object) [
                                    "EnhanceCostperlead" => $comset_val->enhance->Prepaid->EnhanceCostperlead,
                                    "EnhanceMinCostMonth" => $comset_val->enhance->Prepaid->EnhanceMinCostMonth,
                                    "EnhancePlatformFee" => $comset_val->enhance->Prepaid->EnhancePlatformFee,
                                    "EnhanceLeadsPerday" => "10"
                                ];

                                $companysetting->local->Prepaid = $newPrepaidLocal;
                                $companysetting->locator->Prepaid = $newPrepaidLocator;
                                $companysetting->enhance->Prepaid = $newPrepaidEnhance;
                            }
                        }

                        $companysetting->local->Monthly->LeadspeekLeadsPerday = '10';
                        $companysetting->local->Weekly->LeadspeekLeadsPerday = '10';
                        $companysetting->local->OneTime->LeadspeekLeadsPerday = '10';
                        $companysetting->local->Prepaid->LeadspeekLeadsPerday = '10';

                        $companysetting->locator->Monthly->LocatorLeadsPerday = '10';
                        $companysetting->locator->Weekly->LocatorLeadsPerday = '10';
                        $companysetting->locator->OneTime->LocatorLeadsPerday = '10';
                        $companysetting->locator->Prepaid->LocatorLeadsPerday = '10';

                        $companysetting->enhance->Monthly->EnhanceLeadsPerday = ($clientMinLeadDayEnhance !== '') ? $clientMinLeadDayEnhance : "10";
                        $companysetting->enhance->Weekly->EnhanceLeadsPerday = ($clientMinLeadDayEnhance !== '') ? $clientMinLeadDayEnhance : "10";
                        $companysetting->enhance->OneTime->EnhanceLeadsPerday = ($clientMinLeadDayEnhance !== '') ? $clientMinLeadDayEnhance : "10";
                        $companysetting->enhance->Prepaid->EnhanceLeadsPerday = ($clientMinLeadDayEnhance !== '') ? $clientMinLeadDayEnhance : "10";

                        // $companysetting->local->Monthly->LeadspeekCostperlead = $companysetting->locatorlead->FirstName_LastName_MailingAddress_Phone;
                        // $companysetting->local->Weekly->LeadspeekCostperlead = $companysetting->locatorlead->FirstName_LastName_MailingAddress_Phone;
                        // $companysetting->local->OneTime->LeadspeekCostperlead = $companysetting->locatorlead->FirstName_LastName_MailingAddress_Phone;

                        $companysetting->locator->Monthly->LeadspeekCostperlead = $companysetting->locatorlead->FirstName_LastName_MailingAddress_Phone;
                        $companysetting->locator->Weekly->LeadspeekCostperlead = $companysetting->locatorlead->FirstName_LastName_MailingAddress_Phone;
                        $companysetting->locator->OneTime->LeadspeekCostperlead = $companysetting->locatorlead->FirstName_LastName_MailingAddress_Phone;

                    }
                }
            }
        }else{
        /** IF FOR CLIENT COST AGENCY */

            /** GET SETTING MENU MODULE */
            $getcompanysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name',$settingname)->get();
            $companysetting = "";
            if (count($getcompanysetting) > 0) {
                $companysetting = json_decode($getcompanysetting[0]['setting_value']);
            }
        }

        if ($companysetting == "") {
            $companysetting = $this->check_email_template($settingname,$companyID);
        }
        /** GET SETTING MENU MODULE */
        
        /** GET DEFAULT PAYMENT TERM */
        $defaultpaymentterm = 'Weekly';
        if($settingname == "agencydefaultprice") {
            $getdefpay = Company::find($companyID);
           
            if ($getdefpay->count() > 0) {
                $defaultpaymentterm = $getdefpay->paymentterm_default;
                
            }

            $getcompanysetting = CompanySetting::where('company_id',$companyID)->whereEncrypted('setting_name','costagency')->get();
            $rootcostagency = "";
            if (count($getcompanysetting) > 0) {
                $rootcostagency = json_decode($getcompanysetting[0]['setting_value']);
            }

            if ($rootcostagency != "") {
                $comset_val = $this->getcompanysetting($idSys,'rootcostagency');

                if(!isset($rootcostagency->enhance)) {
                    $rootcostagency->enhance = new stdClass();
                    $rootcostagency->enhance->Weekly = new stdClass();
                    $rootcostagency->enhance->Monthly = new stdClass();
                    $rootcostagency->enhance->OneTime = new stdClass();
                    $rootcostagency->enhance->Prepaid = new stdClass();

                    /* WEEKLY */
                    $rootcostagency->enhance->Weekly->EnhanceCostperlead = $comset_val->enhance->Weekly->EnhanceCostperlead;
                    $rootcostagency->enhance->Weekly->EnhanceMinCostMonth = $comset_val->enhance->Weekly->EnhanceMinCostMonth;
                    $rootcostagency->enhance->Weekly->EnhancePlatformFee = $comset_val->enhance->Weekly->EnhancePlatformFee;
                    /* WEEKLY */
                    
                    /* MONTHLY */
                    $rootcostagency->enhance->Monthly->EnhanceCostperlead = $comset_val->enhance->Monthly->EnhanceCostperlead;
                    $rootcostagency->enhance->Monthly->EnhanceMinCostMonth = $comset_val->enhance->Monthly->EnhanceMinCostMonth;
                    $rootcostagency->enhance->Monthly->EnhancePlatformFee = $comset_val->enhance->Monthly->EnhancePlatformFee;
                    /* MONTHLY */
                    
                    /* ONETIME */
                    $rootcostagency->enhance->OneTime->EnhanceCostperlead = $comset_val->enhance->OneTime->EnhanceCostperlead;
                    $rootcostagency->enhance->OneTime->EnhanceMinCostMonth = $comset_val->enhance->OneTime->EnhanceMinCostMonth;
                    $rootcostagency->enhance->OneTime->EnhancePlatformFee = $comset_val->enhance->OneTime->EnhancePlatformFee;
                    /* ONETIME */

                    /* PREPAID */
                    $rootcostagency->enhance->Prepaid->EnhanceCostperlead = $comset_val->enhance->Prepaid->EnhanceCostperlead;
                    $rootcostagency->enhance->Prepaid->EnhanceMinCostMonth = $comset_val->enhance->Prepaid->EnhanceMinCostMonth;
                    $rootcostagency->enhance->Prepaid->EnhancePlatformFee = $comset_val->enhance->Prepaid->EnhancePlatformFee;
                    /* PREPAID */
                }

                if (!isset($rootcostagency->local->Prepaid)) {
                    $newPrepaidLocal = (object) [
                        "LeadspeekCostperlead" => $rootcostagency->local->Weekly->LeadspeekCostperlead,
                        "LeadspeekMinCostMonth" => $rootcostagency->local->Weekly->LeadspeekMinCostMonth,
                        "LeadspeekPlatformFee" => $rootcostagency->local->Weekly->LeadspeekPlatformFee
                    ];

                    $newPrepaidLocator = (object) [
                        "LocatorCostperlead" => $rootcostagency->locator->Weekly->LocatorCostperlead,
                        "LocatorMinCostMonth" => $rootcostagency->locator->Weekly->LocatorMinCostMonth,
                        "LocatorPlatformFee" => $rootcostagency->locator->Weekly->LocatorPlatformFee
                    ];
                    
                    $newPrepaidEnhance = (object) [
                        "EnhanceCostperlead" => $comset_val->enhance->Prepaid->EnhanceCostperlead,
                        "EnhanceMinCostMonth" => $comset_val->enhance->Prepaid->EnhanceMinCostMonth,
                        "EnhancePlatformFee" => $comset_val->enhance->Prepaid->EnhancePlatformFee
                    ];

                    $rootcostagency->local->Prepaid = $newPrepaidLocal;
                    $rootcostagency->locator->Prepaid = $newPrepaidLocator;
                    $rootcostagency->enhance->Prepaid = $newPrepaidEnhance;
                }
            }

            if ($companysetting != "") {
                $comset_val = $this->getcompanysetting($idSys,'rootcostagency');

                if(!isset($companysetting->enhance)) {
                    $companysetting->enhance = new stdClass();
                    $companysetting->enhance->Weekly = new stdClass();
                    $companysetting->enhance->Monthly = new stdClass();
                    $companysetting->enhance->OneTime = new stdClass();
                    $companysetting->enhance->Prepaid = new stdClass();

                    /* WEEKLY */
                    $companysetting->enhance->Weekly->EnhanceCostperlead = $comset_val->enhance->Weekly->EnhanceCostperlead;
                    $companysetting->enhance->Weekly->EnhanceMinCostMonth = $comset_val->enhance->Weekly->EnhanceMinCostMonth;
                    $companysetting->enhance->Weekly->EnhancePlatformFee = $comset_val->enhance->Weekly->EnhancePlatformFee;
                    /* WEEKLY */
                    
                    /* MONTHLY */
                    $companysetting->enhance->Monthly->EnhanceCostperlead = $comset_val->enhance->Monthly->EnhanceCostperlead;
                    $companysetting->enhance->Monthly->EnhanceMinCostMonth = $comset_val->enhance->Monthly->EnhanceMinCostMonth;
                    $companysetting->enhance->Monthly->EnhancePlatformFee = $comset_val->enhance->Monthly->EnhancePlatformFee;
                    /* MONTHLY */
                    
                    /* ONETIME */
                    $companysetting->enhance->OneTime->EnhanceCostperlead = $comset_val->enhance->OneTime->EnhanceCostperlead;
                    $companysetting->enhance->OneTime->EnhanceMinCostMonth = $comset_val->enhance->OneTime->EnhanceMinCostMonth;
                    $companysetting->enhance->OneTime->EnhancePlatformFee = $comset_val->enhance->OneTime->EnhancePlatformFee;
                    /* ONETIME */

                    /* PREPAID */
                    $companysetting->enhance->Prepaid->EnhanceCostperlead = $comset_val->enhance->Prepaid->EnhanceCostperlead;
                    $companysetting->enhance->Prepaid->EnhanceMinCostMonth = $comset_val->enhance->Prepaid->EnhanceMinCostMonth;
                    $companysetting->enhance->Prepaid->EnhancePlatformFee = $comset_val->enhance->Prepaid->EnhancePlatformFee;
                    /* PREPAID */
                }

                if (!isset($companysetting->local->Prepaid)) {
                    $newPrepaidLocal = (object) [
                        "LeadspeekCostperlead" => $companysetting->local->Weekly->LeadspeekCostperlead,
                        "LeadspeekMinCostMonth" => $companysetting->local->Weekly->LeadspeekMinCostMonth,
                        "LeadspeekPlatformFee" => $companysetting->local->Weekly->LeadspeekPlatformFee
                    ];

                    $newPrepaidLocator = (object) [
                        "LocatorCostperlead" => $companysetting->locator->Weekly->LocatorCostperlead,
                        "LocatorMinCostMonth" => $companysetting->locator->Weekly->LocatorMinCostMonth,
                        "LocatorPlatformFee" => $companysetting->locator->Weekly->LocatorPlatformFee
                    ];

                    $newPrepaidEnhance = (object) [
                        "EnhanceCostperlead" => $comset_val->enhance->Prepaid->EnhanceCostperlead,
                        "EnhanceMinCostMonth" => $comset_val->enhance->Prepaid->EnhanceMinCostMonth,
                        "EnhancePlatformFee" => $comset_val->enhance->Prepaid->EnhancePlatformFee
                    ];

                    $companysetting->local->Prepaid = $newPrepaidLocal;
                    $companysetting->locator->Prepaid = $newPrepaidLocator;
                    $companysetting->enhance->Prepaid = $newPrepaidEnhance;
                }
            }

        }else if ($settingname == "clientdefaultprice") {
            $getdefpay = Company::find($companyID);
           
            if ($getdefpay->count() > 0 && $clientdefaultprice === true) {
                $defaultpaymentterm = $getdefpay->paymentterm_default;
            }else{
                /** FIND COMPANY PARENT AND FIND COST AGENCY DEFAULT PRICE*/
                $userClient = User::select('company_parent')->where('company_id','=',$companyID)->where('user_type','=','client')->get();
                /** FIND COMPANY PARENT AND FIND COST AGENCY DEFAULT PRICE*/
                if (count($userClient) > 0) {
                    $companyParentID = $userClient[0]['company_parent'];
                    $getdefpay = Company::find($companyParentID);
                    if ($getdefpay->count() > 0) {
                        $defaultpaymentterm = $getdefpay->paymentterm_default;
                    }
                }
            }
        }else if ($settingname == 'costagency'){
            /* GET CLIENT MIN LEAD DAYS */
            $rootSetting = $this->getcompanysetting($idSys, 'rootsetting');
            $clientMinLeadDayEnhance = (isset($rootSetting->clientminleadday))?$rootSetting->clientminleadday:"";
            /* GET CLIENT MIN LEAD DAYS */

            /* GET ROOT COST AGENCY */
            $rootcostagency = $this->getcompanysetting($idSys, 'rootcostagency');
            /* GET ROOT COST AGENCY */
            
            /* GET CLIENT TYPE LEAD */
            // $rootSetting = $this->getcompanysetting($idSys, 'rootsetting');

            if(!empty($rootSetting->clientcaplead)) {
                $clientTypeLead['type'] = 'clientcaplead';
                $clientTypeLead['value'] = $rootSetting->clientcaplead;
            }
            if(!empty($rootSetting->clientcapleadpercentage)) {
                $clientTypeLead['type'] = 'clientcapleadpercentage';
                $clientTypeLead['value'] = $rootSetting->clientcapleadpercentage;
            }
            /* GET CLIENT TYPE LEAD */

            $getdefpay = Company::find($companyID);
            if ($getdefpay->count() > 0) {
                $defaultpaymentterm = $getdefpay->paymentterm_default;
            }

            if ($companysetting != "") {
                $comset_val = $this->getcompanysetting($idSys,'rootcostagency');

                if(!isset($companysetting->enhance)) {
                    $companysetting->enhance = new stdClass();
                    $companysetting->enhance->Weekly = new stdClass();
                    $companysetting->enhance->Monthly = new stdClass();
                    $companysetting->enhance->OneTime = new stdClass();
                    $companysetting->enhance->Prepaid = new stdClass();

                    /* WEEKLY */
                    $companysetting->enhance->Weekly->EnhanceCostperlead = $comset_val->enhance->Weekly->EnhanceCostperlead;
                    $companysetting->enhance->Weekly->EnhanceMinCostMonth = $comset_val->enhance->Weekly->EnhanceMinCostMonth;
                    $companysetting->enhance->Weekly->EnhancePlatformFee = $comset_val->enhance->Weekly->EnhancePlatformFee;
                    /* WEEKLY */
                    
                    /* MONTHLY */
                    $companysetting->enhance->Monthly->EnhanceCostperlead = $comset_val->enhance->Monthly->EnhanceCostperlead;
                    $companysetting->enhance->Monthly->EnhanceMinCostMonth = $comset_val->enhance->Monthly->EnhanceMinCostMonth;
                    $companysetting->enhance->Monthly->EnhancePlatformFee = $comset_val->enhance->Monthly->EnhancePlatformFee;
                    /* MONTHLY */
                    
                    /* ONETIME */
                    $companysetting->enhance->OneTime->EnhanceCostperlead = $comset_val->enhance->OneTime->EnhanceCostperlead;
                    $companysetting->enhance->OneTime->EnhanceMinCostMonth = $comset_val->enhance->OneTime->EnhanceMinCostMonth;
                    $companysetting->enhance->OneTime->EnhancePlatformFee = $comset_val->enhance->OneTime->EnhancePlatformFee;
                    /* ONETIME */

                    /* PREPAID */
                    $companysetting->enhance->Prepaid->EnhanceCostperlead = $comset_val->enhance->Prepaid->EnhanceCostperlead;
                    $companysetting->enhance->Prepaid->EnhanceMinCostMonth = $comset_val->enhance->Prepaid->EnhanceMinCostMonth;
                    $companysetting->enhance->Prepaid->EnhancePlatformFee = $comset_val->enhance->Prepaid->EnhancePlatformFee;
                    /* PREPAID */
                }

                if (!isset($companysetting->local->Prepaid)) {
                    $newPrepaidLocal = (object) [
                        "LeadspeekCostperlead" => $companysetting->local->Weekly->LeadspeekCostperlead,
                        "LeadspeekMinCostMonth" => $companysetting->local->Weekly->LeadspeekMinCostMonth,
                        "LeadspeekPlatformFee" => $companysetting->local->Weekly->LeadspeekPlatformFee
                    ];

                    $newPrepaidLocator = (object) [
                        "LocatorCostperlead" => $companysetting->locator->Weekly->LocatorCostperlead,
                        "LocatorMinCostMonth" => $companysetting->locator->Weekly->LocatorMinCostMonth,
                        "LocatorPlatformFee" => $companysetting->locator->Weekly->LocatorPlatformFee
                    ];

                    $newPrepaidEnhance = (object) [
                        "EnhanceCostperlead" => $comset_val->enhance->Prepaid->EnhanceCostperlead,
                        "EnhanceMinCostMonth" => $comset_val->enhance->Prepaid->EnhanceMinCostMonth,
                        "EnhancePlatformFee" => $comset_val->enhance->Prepaid->EnhancePlatformFee
                    ];

                    $companysetting->local->Prepaid = $newPrepaidLocal;
                    $companysetting->locator->Prepaid = $newPrepaidLocator;
                    $companysetting->enhance->Prepaid = $newPrepaidEnhance;
                }
            }

        }else if ($settingname == 'agencyplan'){
            
            $getRootSetting = $this->getcompanysetting($companyID,'rootsetting');
            if ($getRootSetting != '') {
                if (isset($getRootSetting->defaultpaymentterm) && $getRootSetting->defaultpaymentterm != '') {
                    $defaultpaymentterm = trim($getRootSetting->defaultpaymentterm);
                }
            }
            
        }
        /** GET DEFAULT PAYMENT TERM */

        if ($settingname == "rootstripe") {
            $companysetting = $companysetting->publishablekey;
            $defaultpaymentterm = "";
        }
        
        return response()->json(array('result'=>'success','data'=>$companysetting,'dpay'=>$defaultpaymentterm,'rootcostagency'=>$rootcostagency,'clientTypeLead'=>$clientTypeLead,'clientMinLeadDayEnhance'=>$clientMinLeadDayEnhance));

    }

    public function updatepackageplan(Request $request) {
        $companyID = (isset($request->CompanyID))?$request->CompanyID:'';
        $packageID = (isset($request->packageID))?$request->packageID:'';

        return $this->create_subscription($companyID,$packageID);
    }

    public function updatefreepackageplan(Request $request) {
        $companyID = (isset($request->CompanyID))?$request->CompanyID:'';
        $packageID = (isset($request->packageID))?$request->packageID:'';

        return $this->create_freesubscription($companyID,$packageID);
    }

    public function getReportAnalytic(Request $request) {
        date_default_timezone_set('America/Chicago');

        $startDate = (isset($request->startDate))?date('Ymd',strtotime($request->startDate)):"";
        $endDate = (isset($request->endDate))?date('Ymd',strtotime($request->endDate)):"";

        $companyid = (isset($request->companyid))?trim($request->companyid):"";
        $campaignid = (isset($request->campaignid))?trim($request->campaignid):"";

        $companyrootid = (isset($request->companyrootid))?trim($request->companyrootid):"";

        $profitLocal = "";
        $profitLocator = "";
        $profitEnhance = "";
        $confAppSysID = config('services.application.systemid');

        $getExcludeAgency = CompanySetting::where('company_id',$companyrootid)->whereEncrypted('setting_name','rootAnalyticsExcludeAgency')->get();
        $ExcludeAgency = array();
        if (count($getExcludeAgency) > 0) {
            $getExcludeAgencyResult = json_decode($getExcludeAgency[0]['setting_value']);
            $ExcludeAgency = explode(",",$getExcludeAgencyResult->companyAgencyId);
        }

        //if ($confAppSysID != $companyrootid && ($companyrootid != "" && $companyid == "0" && $campaignid == '0')) {
        if (false) {
            $rootAccConResult = $this->getcompanysetting($companyrootid,'rootfee');
                    if ($rootAccConResult != '') {
                        $profitLocal = (isset($rootAccConResult->feesiteid))?$rootAccConResult->feesiteid:"";
                        $profitLocator = (isset($rootAccConResult->feesearchid))?$rootAccConResult->feesearchid:"";
                        $profitEnhance = (isset($rootAccConResult->feeenhanceid))?$rootAccConResult->feeenhanceid:"";
                    }
        }

        $rpanalytics = ReportAnalytic::select('report_analytics.leadspeek_type',DB::raw('SUM(pixelfire) as pixelfire'),DB::raw('SUM(towerpostal) as towerpostal'),DB::raw('SUM(bigbdmemail) as bigbdmemail'),DB::raw('SUM(bigbdmpii) as bigbdmpii'),DB::raw('SUM(endatoenrichment) as endatoenrichment'),
                                        DB::raw('SUM(toweremail) as toweremail'),DB::raw('SUM(zerobouncefailed) as zerobouncefailed'),DB::raw('SUM(locationlockfailed) as locationlockfailed'),DB::raw('SUM(serveclient) as serveclient'),
                                        DB::raw('SUM(notserve) as notserve'),DB::raw('"0" as platformfee'),DB::raw('COUNT(report_analytics.leadspeek_api_id) as activecampaign'),
                                        DB::raw('SUM(bigbdmhems) as bigbdmhems'),DB::raw('SUM(bigbdmtotalleads) as bigbdmtotalleads'),DB::raw('SUM(bigbdmremainingleads) as bigbdmremainingleads'),
                                        DB::raw('SUM(getleadfailed) as getleadfailed'),DB::raw('SUM(getleadfailed_bigbdmmd5) as getleadfailed_bigbdmmd5'),DB::raw('SUM(getleadfailed_gettowerdata) as getleadfailed_gettowerdata'),DB::raw('SUM(getleadfailed_bigbdmpii) as getleadfailed_bigbdmpii')
                                    );
            if($startDate != "" && $endDate != "") {
                $rpanalytics->where(function($query) use ($startDate,$endDate) {
                    $query->where(DB::raw('DATE_FORMAT(date,"%Y%m%d")'),'>=',$startDate)
                                ->where(DB::raw('DATE_FORMAT(date,"%Y%m%d")'),'<=',$endDate);
                });
            }else{
                $rpanalytics->where(DB::raw('DATE_FORMAT(date,"%Y%m%d")'),'=',date('Ymd'));
            }
            
            if ($companyid != "" && $companyid != "0") {
                $rpanalytics->join('leadspeek_users','report_analytics.leadspeek_api_id','=','leadspeek_users.leadspeek_api_id')
                            ->where('leadspeek_users.company_id','=',$companyid);
            }

            if ($companyrootid != "" && $companyid == "0" && $campaignid == '0') {
                $rpanalytics->join('leadspeek_users',DB::raw('TRIM(report_analytics.leadspeek_api_id)'),'=',DB::raw('TRIM(leadspeek_users.leadspeek_api_id)'))
                            ->join('users','leadspeek_users.user_id','=','users.id')
                            ->where('users.company_root_id','=',$companyrootid);
                /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
                if (count($ExcludeAgency) > 0) {
                    $rpanalytics = $rpanalytics->whereNotIn('leadspeek_users.company_id',$ExcludeAgency);
                }
                /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
            }

            if ($campaignid != '' && $campaignid != '0') {
                $rpanalytics->where('report_analytics.leadspeek_api_id','=',$campaignid);
            }

            $rpanalytics = $rpanalytics->groupBy('leadspeek_type')
                                        ->get();

            foreach($rpanalytics as $a => $ra) {
                if ($ra['leadspeek_type'] == 'local') {
                    if ($profitLocal == "") { /** IF SUPER ROOT */
                        $platformlocal = LeadspeekReport::select(DB::raw('SUM(leadspeek_reports.platform_price_lead) as platformfee'),DB::raw('SUM(leadspeek_reports.root_price_lead) as rootplatformfee'))
                                                        ->whereIn('leadspeek_reports.leadspeek_api_id', function($query) use($startDate,$endDate) {
                                                            $query->select('report_analytics.leadspeek_api_id')
                                                                    ->from('report_analytics')
                                                                    ->where('report_analytics.leadspeek_type','=','local')
                                                                    ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'>=',$startDate)
                                                                    ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'<=',$endDate);
                                                        });

                                                        if ($companyid != "" && $companyid != "0") {
                                                            $platformlocal->join('leadspeek_users','leadspeek_reports.leadspeek_api_id','=','leadspeek_users.leadspeek_api_id')
                                                                        ->where('leadspeek_users.company_id','=',$companyid);
                                                        }
                                            
                                                        if ($campaignid != '' && $campaignid != '0') {
                                                            $platformlocal->where('leadspeek_reports.leadspeek_api_id','=',$campaignid);
                                                        }

                                                        if ($companyrootid != "" && $companyid == "0" && $campaignid == '0') {
                                                            $platformlocal->join('leadspeek_users',DB::raw('TRIM(leadspeek_reports.leadspeek_api_id)'),'=',DB::raw('TRIM(leadspeek_users.leadspeek_api_id)'))
                                                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                                                            ->where('users.company_root_id','=',$companyrootid);
                                                            /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
                                                            if (count($ExcludeAgency) > 0) {
                                                                $platformlocal = $platformlocal->whereNotIn('leadspeek_users.company_id',$ExcludeAgency);
                                                            }
                                                            /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
                                                        }

                                                        $platformlocal = $platformlocal->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'>=',$startDate)
                                                                            ->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'<=',$endDate)
                                                                            ->get();
                        if (count($platformlocal) > 0) {
                            if ($confAppSysID != $companyrootid && ($companyrootid != "" && $companyid == "0" && $campaignid == '0')) {
                                $rpanalytics[$a]['platformfee'] =  number_format($platformlocal[0]['rootplatformfee'],2,'.','');
                            }else if ($confAppSysID == $companyrootid && ($companyrootid != "" && $companyid == "0" && $campaignid == '0')) {
                                $rpanalytics[$a]['platformfee'] =  number_format($platformlocal[0]['platformfee'],2,'.','');
                            }else if ($companyrootid == "" && ($companyid != "" && $companyid != "0") && ($campaignid != "" && $campaignid != "0")) {
                                /** GET ROOT COMPANY */
                                $_companyrootid = ""; 
                                $getCompanyRoot = User::select('company_root_id')->where('user_type','=','userdownline')->where('company_id','=',$companyid)->get();
                                if (count($getCompanyRoot) > 0) {
                                    $_companyrootid = $getCompanyRoot[0]['company_root_id'];
                                }
                                if ($confAppSysID == $_companyrootid) {
                                    $rpanalytics[$a]['platformfee'] =  number_format($platformlocal[0]['platformfee'],2,'.','');
                                }else{
                                    $rpanalytics[$a]['platformfee'] =  number_format($platformlocal[0]['rootplatformfee'],2,'.','');
                                }
                                /** GET ROOT COMPANY */
                            }else{
                                
                                $adjustPlatformFee = LeadspeekReport::select(DB::raw('SUM(leadspeek_reports.platform_price_lead) as platformfee'))
                                                    ->whereIn('leadspeek_reports.leadspeek_api_id', function($query) use($startDate,$endDate) {
                                                        $query->select('report_analytics.leadspeek_api_id')
                                                                ->from('report_analytics')
                                                                ->where('report_analytics.leadspeek_type','=','local')
                                                                ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'>=',$startDate)
                                                                ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'<=',$endDate);
                                                    });
                                $adjustPlatformFee->join('leadspeek_users',DB::raw('TRIM(leadspeek_reports.leadspeek_api_id)'),'=',DB::raw('TRIM(leadspeek_users.leadspeek_api_id)'))
                                                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                                                            ->where('users.company_root_id','<>',$confAppSysID);
                                $adjustPlatformFee = $adjustPlatformFee->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'>=',$startDate)
                                                                            ->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'<=',$endDate)
                                                                            ->get();

                                $_totalFee = ($platformlocal[0]['platformfee'] - $adjustPlatformFee[0]['platformfee']) + $platformlocal[0]['rootplatformfee'];
                                $rpanalytics[$a]['platformfee'] = number_format($_totalFee,2,'.','');
                            }
                            
                        }

                    }else{ /** IF NOT SUPER ROOT */
                        $_rootplatformfee = $ra['serveclient'] * $profitLocal;
                        $rpanalytics[$a]['platformfee'] =  number_format($_rootplatformfee,2,'.','');
                    }
                                                   
                }

                if ($ra['leadspeek_type'] == 'locator') {
                    if ($profitLocator == "") { /** IF SUPER ROOT */
                        $platformlocator = LeadspeekReport::select(DB::raw('SUM(leadspeek_reports.platform_price_lead) as platformfee'),DB::raw('SUM(leadspeek_reports.root_price_lead) as rootplatformfee'))
                                                        ->whereIn('leadspeek_reports.leadspeek_api_id', function($query) use($startDate,$endDate) {
                                                            $query->select('report_analytics.leadspeek_api_id')
                                                                    ->from('report_analytics')
                                                                    ->where('report_analytics.leadspeek_type','=','locator')
                                                                    ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'>=',$startDate)
                                                                    ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'<=',$endDate);
                                                        });
                                                        if ($companyid != "" && $companyid != "0") {
                                                            $platformlocator->join('leadspeek_users','leadspeek_reports.leadspeek_api_id','=','leadspeek_users.leadspeek_api_id')
                                                                        ->where('leadspeek_users.company_id','=',$companyid);
                                                        }
                                            
                                                        if ($campaignid != '' && $campaignid != '0') {
                                                            $platformlocator->where('leadspeek_reports.leadspeek_api_id','=',$campaignid);
                                                        }

                                                        if ($companyrootid != "" && $companyid == "0" && $campaignid == '0') {
                                                            $platformlocator->join('leadspeek_users',DB::raw('TRIM(leadspeek_reports.leadspeek_api_id)'),'=',DB::raw('TRIM(leadspeek_users.leadspeek_api_id)'))
                                                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                                                            ->where('users.company_root_id','=',$companyrootid);
                                                                            /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
                                                                            if (count($ExcludeAgency) > 0) {
                                                                                $platformlocator = $platformlocator->whereNotIn('leadspeek_users.company_id',$ExcludeAgency);
                                                                            }
                                                                            /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
                                                        }

                                                        $platformlocator = $platformlocator->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'>=',$startDate)
                                                                            ->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'<=',$endDate)
                                                                            ->get();
                        if (count($platformlocator) > 0) {
                            if ($confAppSysID != $companyrootid && ($companyrootid != "" && $companyid == "0" && $campaignid == '0')) {
                                $rpanalytics[$a]['platformfee'] =  number_format($platformlocator[0]['rootplatformfee'],2,'.','');
                            }else if ($confAppSysID == $companyrootid && ($companyrootid != "" && $companyid == "0" && $campaignid == '0')) {
                                $rpanalytics[$a]['platformfee'] =  number_format($platformlocator[0]['platformfee'],2,'.','');
                            }else if ($companyrootid == "" && ($companyid != "" && $companyid != "0") && ($campaignid != "" && $campaignid != "0")) {
                                /** GET ROOT COMPANY */
                                $_companyrootid = ""; 
                                $getCompanyRoot = User::select('company_root_id')->where('user_type','=','userdownline')->where('company_id','=',$companyid)->get();
                                if (count($getCompanyRoot) > 0) {
                                    $_companyrootid = $getCompanyRoot[0]['company_root_id'];
                                }
                                if ($confAppSysID == $_companyrootid) {
                                    $rpanalytics[$a]['platformfee'] =  number_format($platformlocator[0]['platformfee'],2,'.','');
                                }else{
                                    $rpanalytics[$a]['platformfee'] =  number_format($platformlocator[0]['rootplatformfee'],2,'.','');
                                }
                                /** GET ROOT COMPANY */
                            }else{
                                $adjustPlatformFee = LeadspeekReport::select(DB::raw('SUM(leadspeek_reports.platform_price_lead) as platformfee'))
                                                                ->whereIn('leadspeek_reports.leadspeek_api_id', function($query) use($startDate,$endDate) {
                                                                    $query->select('report_analytics.leadspeek_api_id')
                                                                            ->from('report_analytics')
                                                                            ->where('report_analytics.leadspeek_type','=','locator')
                                                                            ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'>=',$startDate)
                                                                            ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'<=',$endDate);
                                                                });
                                $adjustPlatformFee->join('leadspeek_users',DB::raw('TRIM(leadspeek_reports.leadspeek_api_id)'),'=',DB::raw('TRIM(leadspeek_users.leadspeek_api_id)'))
                                                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                                                            ->where('users.company_root_id','<>',$confAppSysID);
                                $adjustPlatformFee = $adjustPlatformFee->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'>=',$startDate)
                                                                            ->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'<=',$endDate)
                                                                            ->get();

                                $_totalFee = ($platformlocator[0]['platformfee'] - $adjustPlatformFee[0]['platformfee']) + $platformlocator[0]['rootplatformfee'];
                                $rpanalytics[$a]['platformfee'] = number_format($_totalFee,2,'.','');
                            }
                        }

                    }else{/** IF NOT SUPER ROOT */
                        $_rootplatformfee = $ra['serveclient'] * $profitLocator;
                        $rpanalytics[$a]['platformfee'] =  number_format($_rootplatformfee,2,'.','');
                    }
                                                   
                }

                if ($ra['leadspeek_type'] == 'enhance') {
                    if ($profitEnhance == "") { /** IF SUPER ROOT */
                        $platformenhance = LeadspeekReport::select(DB::raw('SUM(leadspeek_reports.platform_price_lead) as platformfee'),DB::raw('SUM(leadspeek_reports.root_price_lead) as rootplatformfee'))
                                                        ->whereIn('leadspeek_reports.leadspeek_api_id', function($query) use($startDate,$endDate) {
                                                            $query->select('report_analytics.leadspeek_api_id')
                                                                    ->from('report_analytics')
                                                                    ->where('report_analytics.leadspeek_type','=','enhance')
                                                                    ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'>=',$startDate)
                                                                    ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'<=',$endDate);
                                                        });

                                                        if ($companyid != "" && $companyid != "0") {
                                                            $platformenhance->join('leadspeek_users','leadspeek_reports.leadspeek_api_id','=','leadspeek_users.leadspeek_api_id')
                                                                        ->where('leadspeek_users.company_id','=',$companyid);
                                                        }
                                            
                                                        if ($campaignid != '' && $campaignid != '0') {
                                                            $platformenhance->where('leadspeek_reports.leadspeek_api_id','=',$campaignid);
                                                        }

                                                        if ($companyrootid != "" && $companyid == "0" && $campaignid == '0') {
                                                            $platformenhance->join('leadspeek_users',DB::raw('TRIM(leadspeek_reports.leadspeek_api_id)'),'=',DB::raw('TRIM(leadspeek_users.leadspeek_api_id)'))
                                                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                                                            ->where('users.company_root_id','=',$companyrootid);
                                                            /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
                                                            if (count($ExcludeAgency) > 0) {
                                                                $platformenhance = $platformenhance->whereNotIn('leadspeek_users.company_id',$ExcludeAgency);
                                                            }
                                                            /** FOR EXCLUDE AGENCY FROM REPORT ANALYTICS */
                                                        }

                                                        $platformenhance = $platformenhance->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'>=',$startDate)
                                                                            ->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'<=',$endDate)
                                                                            ->get();

                        if (count($platformenhance) > 0) {
                            if ($confAppSysID != $companyrootid && ($companyrootid != "" && $companyid == "0" && $campaignid == '0')) {
                                $rpanalytics[$a]['platformfee'] =  number_format($platformenhance[0]['rootplatformfee'],2,'.','');
                            }else if ($confAppSysID == $companyrootid && ($companyrootid != "" && $companyid == "0" && $campaignid == '0')) {
                                $rpanalytics[$a]['platformfee'] =  number_format($platformenhance[0]['platformfee'],2,'.','');
                            }else if ($companyrootid == "" && ($companyid != "" && $companyid != "0") && ($campaignid != "" && $campaignid != "0")) {
                                /** GET ROOT COMPANY */
                                $_companyrootid = ""; 
                                $getCompanyRoot = User::select('company_root_id')->where('user_type','=','userdownline')->where('company_id','=',$companyid)->get();
                                if (count($getCompanyRoot) > 0) {
                                    $_companyrootid = $getCompanyRoot[0]['company_root_id'];
                                }
                                if ($confAppSysID == $_companyrootid) {
                                    $rpanalytics[$a]['platformfee'] =  number_format($platformenhance[0]['platformfee'],2,'.','');
                                }else{
                                    $rpanalytics[$a]['platformfee'] =  number_format($platformenhance[0]['rootplatformfee'],2,'.','');
                                }
                                /** GET ROOT COMPANY */
                            }else{
                                
                                $adjustPlatformFee = LeadspeekReport::select(DB::raw('SUM(leadspeek_reports.platform_price_lead) as platformfee'))
                                                    ->whereIn('leadspeek_reports.leadspeek_api_id', function($query) use($startDate,$endDate) {
                                                        $query->select('report_analytics.leadspeek_api_id')
                                                                ->from('report_analytics')
                                                                ->where('report_analytics.leadspeek_type','=','enhance')
                                                                ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'>=',$startDate)
                                                                ->where(DB::raw('DATE_FORMAT(report_analytics.date,"%Y%m%d")'),'<=',$endDate);
                                                    });
                                $adjustPlatformFee->join('leadspeek_users',DB::raw('TRIM(leadspeek_reports.leadspeek_api_id)'),'=',DB::raw('TRIM(leadspeek_users.leadspeek_api_id)'))
                                                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                                                            ->where('users.company_root_id','<>',$confAppSysID);
                                $adjustPlatformFee = $adjustPlatformFee->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'>=',$startDate)
                                                                            ->where(DB::raw('DATE_FORMAT(leadspeek_reports.clickdate,"%Y%m%d")'),'<=',$endDate)
                                                                            ->get();
                                $_totalFee = ($platformenhance[0]['platformfee'] - $adjustPlatformFee[0]['platformfee']) + $platformenhance[0]['rootplatformfee'];
                                $rpanalytics[$a]['platformfee'] = number_format($_totalFee,2,'.','');
                            }
                            
                        }

                    }else{ /** IF NOT SUPER ROOT */
                        $_rootplatformfee = $ra['serveclient'] * $profitEnhance;
                        $rpanalytics[$a]['platformfee'] =  number_format($_rootplatformfee,2,'.','');
                    }
                                                   
                }
            }
                        
            return response()->json(array('result'=>'success','data'=>$rpanalytics));
    }

    public function downloadReportAnalytic(Request $request) {
        date_default_timezone_set('America/Chicago');

        $startDate = (isset($request->startDate))?date('Ymd',strtotime($request->startDate)):"";
        $endDate = (isset($request->endDate))?date('Ymd',strtotime($request->endDate)):"";
        $companyid = (isset($request->companyid))?trim($request->companyid):"";
        $campaignid = (isset($request->campaignid))?trim($request->campaignid):"";
        $companyrootid = (isset($request->companyrootid))?trim($request->companyrootid):"";

        return (new AnalyticExport)->betweenDate($startDate,$endDate,$companyid,$campaignid,$companyrootid)->download('reportAnalytics_' . $startDate . '_' . $endDate . '.csv');
    }

    public function getRootList(Request $request) {
        $rootList = User::select('users.company_id as id','companies.company_name as name')
                        ->join('companies','users.company_id','=','companies.id')
                        ->where('users.active','=','T')
                        ->whereNull('company_parent')
                        ->get();
        return response()->json(array('result'=>'success','params'=>$rootList));
    }

    public function getSalesList(Request $request) {
        $CompanyID = (isset($request->CompanyID))?$request->CompanyID:'';
        $saleslist = User::select('id','name','status_acc')
                        ->where('user_type','=','sales')
                        ->where('active','=','T')
                        ->where('status_acc','=','completed');
        if ($CompanyID != "") {
            $saleslist->where('company_id','=',$CompanyID);
        }
            $saleslist = $saleslist->get();
        return response()->json(array('result'=>'success','params'=>$saleslist));
    }

    public function setSalesPerson(Request $request) {
        $companyID = (isset($request->companyID))?$request->companyID:'';
        $salesRep = (isset($request->salesRep))?$request->salesRep:'';
        $salesAE = (isset($request->salesAE))?$request->salesAE:'';
        $salesRef = (isset($request->salesRef))?$request->salesRef:'';
       
        
        if ($companyID != "") {
            /** FOR SALE REPS */
            $chkSalesCompany = CompanySale::select('id')
                                            ->where('company_id','=',$companyID)
                                            ->where('sales_title','=','Sales Representative')
                                            ->get();

            if (count($chkSalesCompany) == 0) {
                if (trim($salesRep) != "") {
                    $createSalesRep = CompanySale::create([
                                            'company_id' => $companyID,
                                            'sales_id' => $salesRep,
                                            'sales_title' => 'Sales Representative',
                                        ]);
                }
            }else{
                if (trim($salesRep) != "") {
                    $updateSalesRep = CompanySale::find($chkSalesCompany[0]['id']);
                    $updateSalesRep->sales_id = $salesRep;
                    $updateSalesRep->save();
                }else{
                    $deleteSalesRep = CompanySale::find($chkSalesCompany[0]['id']);
                    $deleteSalesRep->delete();
                }
            }
            /** FOR SALE REPS */

            /** FOR Account Executive */
            $chkSalesCompany = CompanySale::select('id')
                                            ->where('company_id','=',$companyID)
                                            ->where('sales_title','=','Account Executive')
                                            ->get();

            if (count($chkSalesCompany) == 0) {
                if (trim($salesAE) != "") {
                    $createSalesAE = CompanySale::create([
                                            'company_id' => $companyID,
                                            'sales_id' => $salesAE,
                                            'sales_title' => 'Account Executive',
                                        ]);
                }
            }else{
                if (trim($salesAE) != "") {
                    $updateSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                    $updateSalesAE->sales_id = $salesAE;
                    $updateSalesAE->save();
                }else{
                    $deleteSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                    $deleteSalesAE->delete();
                }
            }
            /** FOR Account Executive */
            
            /** FOR SALES REFERRAL */
            $chkSalesCompany = CompanySale::select('id')
                                            ->where('company_id','=',$companyID)
                                            ->where('sales_title','=','Account Referral')
                                            ->get();

            if (count($chkSalesCompany) == 0) {
                if (trim($salesRef) != "") {
                    
                    $createSalesRef = CompanySale::create([
                                            'company_id' => $companyID,
                                            'sales_id' => $salesRef,
                                            'sales_title' => 'Account Referral',
                                        ]);
                }
            }else{
                if (trim($salesRef) != "") {
                    $updateSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                    $updateSalesAE->sales_id = $salesRef;
                    $updateSalesAE->save();
                }else{
                    $deleteSalesAE = CompanySale::find($chkSalesCompany[0]['id']);
                    $deleteSalesAE->delete();
                }
            }
            /** FOR SALES REFERRAL */

        }

        return response()->json(array('result'=>'success'));
    }

    public function cancelsubscription(Request $request) {
        try {
            $companyID = (isset($request->companyID) && $request->companyID != '')?$request->companyID:'';
            $userID = $request->user()->id;
            $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
            
            $disabledCampaign = $this->stopAllCampaignAndBill($companyID,$ipAddress,$userID,true);

            /** DEACTIVE ALL THE CLIENTS */
            $deactiveClients = User::where('company_parent','=',$companyID)
                                        ->where('user_type','=','client')
                                        ->update(['active' => 'F']);
            /** DEACTIVE ALL THE CLIENTS */

            /** DEACTIVE AGENCY AND ADMIN */
            $deactiveAgencyAndAdmin = User::where('company_id','=',$companyID)
                                        ->where(function ($query) {
                                            $query->where('user_type','=','user')
                                            ->orWhere('user_type','=','userdownline');
                                        })
                                        ->update(['active' => 'F']);
            /** DEACTIVE AGENCY AND ADMIN */

            /** LOG ACTION */
            $loguser = $this->logUserAction($userID,'Agency Cancel Subscription Success','CompanyID : ' . $companyID . ' | userID :' . $userID,$ipAddress);
            /** LOG ACTION */

            return response()->json(array('result'=>'success'));
        }catch(Exception $e) {
            /** LOG ACTION */
            $loguser = $this->logUserAction($userID,'Agency Cancel Subscription FAILED','CompanyID : ' . $companyID . ' | userID :' . $userID . ' | ErrMessage: ' . $e->getMessage(),$ipAddress);
            /** LOG ACTION */
            return response()->json(array('result'=>'failed'));
        }
    }

}
