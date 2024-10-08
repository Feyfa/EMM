<?php

namespace App\Http\Controllers;

use App\Mail\Gmail;
use App\Models\Company;
use App\Models\CompanyStripe;
use App\Models\LeadspeekInvoice;
use App\Models\LeadspeekUser;
use App\Models\User;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException as ExceptionAuthenticationException;
use Stripe\Exception\OAuth\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\StripeClient;
use Throwable;

class PaymentController extends Controller
{
    public function setbillingmethod(Request $request) {
        $companyID = (isset($request->companyID) && $request->companyID != '')?$request->companyID:'';
        $manualBill = (isset($request->manualBill) && $request->manualBill != '')?$request->manualBill:'F';
        $userID = $request->user()->id;

        if ($companyID != '') {

            /** STOP ALL CAMPAIGN FIRST AND CHARGE DIRECTLY TO AGENCY AND CLEAN UP THE CC OF CLIENT BEFORE TURN OFF THE MANUALLY BILLING */
            if (trim($manualBill) == "F") {
                $campaignlist = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.leadspeek_api_id','leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.active_user','users.company_id','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id',
                'leadspeek_users.paymentterm','leadspeek_users.lp_min_cost_month','leadspeek_users.report_sent_to','leadspeek_users.admin_notify_to','users.email','leadspeek_users.lp_limit_startdate','leadspeek_users.lp_enddate','leadspeek_users.lp_max_lead_month','leadspeek_users.cost_perlead','users.customer_card_id','leadspeek_users.start_billing_date',
                'companies.company_name','leadspeek_users.campaign_name','leadspeek_users.company_id as company_parent','users.active','users.company_root_id')
                                            ->join('users','leadspeek_users.user_id','=','users.id')
                                            ->join('companies','users.company_id','=','companies.id')
                                            ->where('leadspeek_users.company_id','=',$companyID)
                                            ->where('leadspeek_users.archived','=','F')
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

                foreach($campaignlist as $cl) {
                    $campaignStatus = 'stop';
                    $leadspeekID = $cl['leadspeek_api_id'];
                    
                    $organizationid = (isset($cl['leadspeek_organizationid']) && $cl['leadspeek_organizationid'] != '')?$cl['leadspeek_organizationid']:'';
                    $campaignsid = (isset($cl['leadspeek_campaignsid']) && $cl['leadspeek_campaignsid'] != '')?$cl['leadspeek_campaignsid']:'';
                    $start_billing_date = (isset($cl['start_billing_date']) && $cl['start_billing_date'] != '')?$cl['start_billing_date']:'';

                    /** LOG ACTION */
                    $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
                    $loguser = $this->logUserAction($userID,'Campaign Stopped - Manual Billing OFF','Campaign Status : ' . $campaignStatus . ' | CampaignID :' . $leadspeekID,$ipAddress);
                    /** LOG ACTION */

                    /** ACTIVATE CAMPAIGN SIMPLIFI */
                    if ($organizationid != '' && $campaignsid != '') {
                        $camp = $this->startPause_campaign($organizationid,$campaignsid,$campaignStatus);
                    }
                    /** ACTIVATE CAMPAIGN SIMPLIFI */

                    $clientEmail = "";
                    $clientAdminNotify = "";
                    $custEmail = "";

                    /** CREATE INVOICE */

                    /** CHECK IF THERE END DATE ON WEEKLY OR MONTHLY PAYMENT TERM */
                    $clientPaymentTerm = $cl['paymentterm'];
                    $_company_id = $cl['company_id'];
                    $_user_id = $cl['user_id'];
                    $_leadspeek_api_id = $cl['leadspeek_api_id'];
                    $clientPaymentTerm = $cl['paymentterm'];
                    $minCostLeads = $cl['lp_min_cost_month'];
                    $_lp_user_id = $cl['id'];
                    $company_name = $cl['company_name'];
                    $clientEmail = explode(PHP_EOL, $cl['report_sent_to']);
                    $clientAdminNotify = explode(',',$cl['admin_notify_to']);
                    $custEmail = $cl['email'];    
                    
                    $clientMaxperTerm = $cl['lp_max_lead_month'];
                    $clientCostPerLead = $cl['cost_perlead'];
                    
                    $custStripeID = $cl['customer_payment_id'];
                    $custStripeCardID = $cl['customer_card_id'];

                    if ($clientPaymentTerm != 'One Time' && $start_billing_date != '') {
                        $EndDate = date('YmdHis',strtotime($start_billing_date));
                        $platformFee = 0;
                        
                        if (date('YmdHis') >= $EndDate) {
                            

                            /** UPDATE USER END DATE */
                            $updateUser = User::find($userID);
                            $updateUser->lp_enddate = null;
                            $updateUser->lp_limit_startdate = null;
                            $updateUser->save();
                            /** UPDATE USER END DATE */

                            $clientStartBilling = date('YmdHis',strtotime($start_billing_date));
                            $nextBillingDate = date('YmdHis');

                            /** HACKED ENDED CLIENT NO PLATFORM FEE */
                            $platformFee = 0;
                            /** HACKED ENDED CLIENT NO PLATFORM FEE */

                            /** CREATE INVOICE AND SENT IT */
                            $invoiceCreated = $this->createInvoice($_lp_user_id,$_company_id,$_user_id,$_leadspeek_api_id,$clientMaxperTerm,$clientCostPerLead,$platformFee,$clientPaymentTerm,$company_name,$clientEmail,$clientAdminNotify,$clientStartBilling,$nextBillingDate,$custStripeID,$custStripeCardID,$custEmail,$cl);
                            /** CREATE INVOICE AND SENT IT */
                        }       
                    }
                    /** CREATE INVOICE */     
                    
                    /** STOP CAMPAIGN STATUS */
                    $leads = LeadspeekUser::find($_lp_user_id);
                    $leads->active_user = 'F';
                    $leads->active = 'F';
                    $leads->disabled = 'T';
                    $leads->save();
                    /** STOP CAMPAIGN STATUS */
                }

                /** SET CLIENT CREDIT CARD EMPTY TO MAKE INACTIVE THAT CLIENT HAVE REGISTER ON DIRECT BILLING BEFORE */
                $usrdirect = User::where("company_parent","=",$companyID)
                                    ->where("user_type","=","client")
                                    ->where("active","=","T")
                                    ->where("customer_payment_id","=","agencyDirectPayment")
                                    ->update(["customer_payment_id" => "","customer_card_id" => ""]);
                /** SET CLIENT CREDIT CARD EMPTY TO MAKE INACTIVE THAT CLIENT HAVE REGISTER ON DIRECT BILLING BEFORE */
                
            }
            /** STOP ALL CAMPAIGN FIRST AND CHARGE DIRECTLY TO AGENCY AND CLEAN UP THE CC OF CLIENT BEFORE TURN OFF THE MANUALLY BILLING */

            $compUpdate = Company::find($companyID);
            $compUpdate->manual_bill = $manualBill;
            $compUpdate->save();

            return response()->json(array('result'=>'success','params'=>'','msg'=>''));
        }else{
            return response()->json(array('result'=>'failed','params'=>'','msg'=>''));
        }
    }

    public function updatecard(Request $request) {
        $tokenid = $request->tokenid;
        $userID = (isset($request->usrID) && $request->usrID != '')?$request->usrID:$request->user()->id;
        $cardholder = $request->cardholder;
        $address = $request->address;
        $city = $request->city;
        $state = $request->state;
        $country = $request->country;
        $zip = $request->zip;
        $companyParentID = (isset($request->companyParentID))?$request->companyParentID:'';
        $action = (isset($request->action))?$request->action:'';

        $_payment_status = "";
        $_failed_campaignid = "";
        $_failed_invoiceid = "";
        $_failed_total_amount = "";
        $organizationid = "";

        $user = User::select('customer_card_id','customer_payment_id','company_id','name','company_root_id','user_type','payment_status','failed_campaignid','failed_invoiceid','failed_total_amount','email')
                    ->where('id','=',$userID)
                    ->get();

        if(count($user) > 0) {
            $_payment_status = $user[0]['payment_status'];
            $_failed_campaignid = $user[0]['failed_campaignid'];
            $_failed_invoiceid = $user[0]['failed_invoiceid'];
            $_failed_total_amount = $user[0]['failed_total_amount'];

            /** CHECK IF ONLY USER / ADMIN FIND THE OWNER */
            if ($user[0]['user_type'] == 'user') {
                $_companyID = $user[0]['company_id'];
                $user = User::select('id','customer_card_id','customer_payment_id','company_parent','company_id','company_root_id','user_type','email')
                    ->where('company_id','=',$_companyID)
                    ->where('user_type','=','userdownline')
                    ->where('active','=','T')
                    ->get();

                if (isset($user[0]['company_parent'])) {
                    $companyParentID = $user[0]['company_parent'];
                    $userID = $user[0]['id'];
                }
            }
            /** CHECK IF ONLY USER / ADMIN FIND THE OWNER */
            
            $name = (isset($user[0]['name']))?$user[0]['name']:'';
            $company_id = (isset($user[0]['company_id']) && $user[0]['company_id'] != '')?$user[0]['company_id']:'';
            $company_name = '';
            $companyrslt = Company::select('company_name','simplifi_organizationid')
                                    ->where('id','=',$company_id)
                                    ->get();
            if (count($companyrslt) > 0) {
                //$company_name = ' (' . $companyrslt[0]['company_name'] . ')';
                $company_name = $companyrslt[0]['company_name'];
                $organizationid = $companyrslt[0]['simplifi_organizationid'];
            }

            /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */
            $accConID = '';
            if ($companyParentID != '') {
                $accConID = $this->check_connected_account($companyParentID,$user[0]['company_root_id']);
            }
            /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */

            if ($user[0]['customer_card_id'] != '' && $user[0]['customer_payment_id'] != '') {
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

                $custStripeID = $user[0]['customer_payment_id'];
                $custStripeCardID = $user[0]['customer_card_id'];
                $custEmail = $user[0]['email'];
                /** CHECK IF THERE IS FAILED PAYMENT AND WHAT ACTION */

                if ($action != 'existcard') {
                    /** CREATE NEW CARD */
                    try {
                        $newCard = $stripe->customers->createSource(
                            $user[0]['customer_payment_id'],
                            ['source' => $tokenid], 
                            ['stripe_account' => $accConID]
                        );
                    } catch (Exception $th) {
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Oh No!','msg'=> $th->getMessage()));
                    }

                    $newCardID = $newCard->id;

                    /** CREATE NEW CARD */

                    /** REMOVE OLD CARD */
                    try {
                        $removeOldCard = $stripe->customers->deleteSource(
                            $user[0]['customer_payment_id'],
                            $user[0]['customer_card_id'],
                            [],['stripe_account' => $accConID]
                        );
                    } catch (Exception $e) {
                        //return response()->json(array('result'=>'failed','params'=>'','title'=>$e->getMessage(),'msg'=> $e->getMessage()));
                    }
                    /** REMOVE OLD CARD */

                    /** ATTACHED NEW CARD INTO CUSTOMER */
                    try {
                        $updateCC = $stripe->customers->updateSource($user[0]['customer_payment_id'],$newCardID,[
                            'name' => $cardholder,
                            'address_line1' => $address,
                            'address_city' => $city,
                            'address_state' => $state,
                            'address_country' => $country,
                            'address_zip' => $zip,
                        ],['stripe_account' => $accConID]);
                    } catch (Exception $e) {
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Oh No!','msg'=> $e->getMessage()));
                    }
                    /** ATTACHED NEW CARD INTO CUSTOMER */

                    /** UPDATE CUSTOMER INFORMATION STRIPE */
                    try {
                        $customerupdateStripe = $stripe->customers->update($user[0]['customer_payment_id'],[
                            'name' => $name . ' (' . $company_name . ')',
                            'description' =>  $company_name,
                        ],['stripe_account' => $accConID]);
                    } catch (Exception $e) {
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Oh No!','msg'=> $e->getMessage()));
                    }
                    /** UPDATE CUSTOMER INFORMATION STRIPE */

                    /** UPDATE CREDIT CARD INFORMATION TO CUSTOMER */
                    $updateUser = User::find($userID);
                    $updateUser->customer_card_id = $newCardID;
                    if ($action == '' && trim($_payment_status) == "") {
                        $updateUser->payment_status = "";
                        $updateUser->failed_campaignid = "";
                        //$updateUser->failed_invoiceid = "";
                        $updateUser->failed_invoicenumber = "";
                        $updateUser->failed_total_amount = "";
                    } else if($action == '' && trim($_payment_status) == "failed") {
                        $updateUser->payment_status = "";
                    }
                    $updateUser->save();
                    /** UPDATE CREDIT CARD INFORMATION TO CUSTOMER */

                    /** LOG ACTION */
                    $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
                    $loguser = $this->logUserAction($userID,'Credit Card Update','Credit Card update new one to : ' . $user[0]['customer_payment_id'] . ' | cardID :' . $newCardID . ' | username :' . $name . ' | email :' . $user[0]['email'],$ipAddress);
                    $loguser = $this->logUserAction($userID,'Service and Billing Agreement','The user has agreed to the Service and Billing Agreement. Name : ' . $name . ' | Company Name :' . $company_name . ' | Email :' . $user[0]['email'] . ' | userID :' . $user[0]['customer_payment_id'] ,$ipAddress);
                    /** LOG ACTION */

                    $custStripeCardID = $newCardID;
                }else{
                     /** LOG ACTION */
                     $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
                     $loguser = $this->logUserAction($userID,'Credit Card Recharge','Recharge Credit Card with existing card : ' . $user[0]['customer_payment_id'] . ' | cardID :' . $user[0]['customer_card_id'] . ' | username :' . $name . ' | email :' . $user[0]['email'],$ipAddress);
                     $loguser = $this->logUserAction($userID,'Service and Billing Agreement','The user has agreed to the Service and Billing Agreement. Name : ' . $name . ' | Company Name :' . $company_name . ' | Email :' . $user[0]['email'] . ' | userID :' . $user[0]['customer_payment_id'] ,$ipAddress);
                     /** LOG ACTION */
                }/** CHECK IF THERE IS FAILED PAYMENT AND WHAT ACTION */

                /** CHARGE THE FAILED PAYMENT */
                if ($action != '' && $_payment_status == 'failed' && trim($_failed_campaignid) != '') {
                    Log::info('updateCard block 4');
                    $_totalFailedAmount = 0;
                    $_descriptionInvoice = "";

                    $_failed_total_amount = explode('|',$_failed_total_amount);
                    $_failed_campaignid = explode('|',$_failed_campaignid);
                    $_failed_invoiceid = explode('|',$_failed_invoiceid);

                    for($i=0;$i<count($_failed_total_amount);$i++) {
                        $_totalFailedAmount = $_totalFailedAmount + (float)$_failed_total_amount[$i];
                        $_descriptionInvoice = $_descriptionInvoice . '#' . $_failed_campaignid[$i] . ' ';
                    }
                    
                    $_descriptionInvoice = "Invoice for Failed Payment on Campaigns: " . $_descriptionInvoice;
                    
                    $statusRecharge = true;
                    $paymentintentID = "";

                    try {
                        $chargeAmount = $_totalFailedAmount * 100;
                        $payment_intent =  $stripe->paymentIntents->create([
                            'payment_method_types' => ['card'],
                            'customer' => trim($custStripeID),
                            'amount' => $chargeAmount,
                            'currency' => 'usd',
                            'receipt_email' => $custEmail,
                            'payment_method' => $custStripeCardID,
                            'confirm' => true,
                            'description' => $_descriptionInvoice,
                        ],['stripe_account' => $accConID]);

                        $paymentintentID = $payment_intent->id;
                    
                        /* CHECK STATUS PAYMENT INTENTS */
                        $payment_intent_status = (isset($payment_intent->status))?$payment_intent->status:"";
                        if($payment_intent_status == 'requires_action') {
                            $statusRecharge = false;
                            return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused.'));
                        }
                        /* CHECK STATUS PAYMENT INTENTS */
                    
                    }catch (RateLimitException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    } catch (InvalidRequestException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    } catch (AuthenticationException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    } catch (ExceptionAuthenticationException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    } catch (ApiConnectionException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    } catch (ApiErrorException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    } catch (Exception $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    }  catch (Throwable $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=> 'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. (' . $e->getMessage() . ')'));
                    } 

                    if ($statusRecharge == true && $paymentintentID != "") {
                        Log::info('updateCard block 5');
                        /** UPDATE ALL STATUS TO NORMAL */
                        $_failed_campaigns_paused = "";
                        $listOfFailedCampaign = "";

                        $updateUser = User::find($userID);
                        $_failed_campaigns_paused = explode('|',$updateUser->failed_campaigns_paused);
                        $listOfFailedCampaign = $updateUser->failed_campaigns_paused;
                        
                        $updateUser->payment_status = "";
                        $updateUser->failed_campaignid = "";
                        //$updateUser->failed_invoiceid = "";
                        $updateUser->failed_invoicenumber = "";
                        $updateUser->failed_total_amount = "";
                        $updateUser->save();

                         /** LOG ACTION */
                         $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
                         $loguser = $this->logUserAction($userID,'Credit Card Recharge or Updated Success','Recharge Credit Card with card : ' . $custStripeID . ' | cardID :' . $custStripeCardID . ' | username :' . $name . ' | email :' . $user[0]['email'] . ' | Description:' . $_descriptionInvoice,$ipAddress);
                         /** LOG ACTION */

                        for($i=0;$i<count($_failed_invoiceid);$i++) {
                            try {
                                $updateInvoice = LeadspeekInvoice::find($_failed_invoiceid[$i]);
                                $updateInvoice->status = 'paid';
                                $updateInvoice->save();
                            }catch (Exception $e) {
                                $loguser = $this->logUserAction($userID,'Credit Card Update','Failed update Leadspeek Invoice Status InvoiceID : ' . $_failed_invoiceid[$i],$ipAddress);
                            }
                        }

                        for($i=0;$i<count($_failed_campaigns_paused);$i++) {
                             /** ACTIVATE CAMPAIGN SIMPLIFI */
                             if ($organizationid != '' && $_failed_campaigns_paused[$i] != '') {
                                $getLeadspeekCampaignsID = LeadspeekUser::select('leadspeek_campaignsid')->where('leadspeek_api_id','=',$_failed_campaigns_paused[$i])->get();

                                $_leadspeek_campaignsid = "";

                                if (count($getLeadspeekCampaignsID) > 0) {
                                    $_leadspeek_campaignsid = $getLeadspeekCampaignsID[0]['leadspeek_campaignsid'];
                                } 
                                if ($_leadspeek_campaignsid != '') {
                                    $camp = $this->startPause_campaign($organizationid,$_leadspeek_campaignsid,'activate');
                                    if ($camp != true) {
                                        /** SEND EMAIL TO ME */
                                            $details = [
                                                'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_failed_campaigns_paused[$i]. '<br/>',
                                            ];

                                            $from = [
                                                'address' => 'noreply@sitesettingsapi.com',
                                                'name' => 'support',
                                                'replyto' => 'noreply@sitesettingsapi.com',
                                            ];
                                            $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - PAYMENT CONTROLLER (restart campaign pause due the payment failed - L208) #' .$_failed_campaigns_paused[$i],$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                        /** SEND EMAIL TO ME */
                                    }else{
                                        $updateCampaignStatus = LeadspeekUser::where('leadspeek_api_id','=',$_failed_campaigns_paused[$i])->update(['active' => 'T','disabled' => 'F','active_user' => 'T']);
                                    }
                                }
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                        }

                        /** LOG ACTION */
                        $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
                        $loguser = $this->logUserAction($userID,'Credit Card Recharge or Updated Success','Activate Paused Campaigns : ' . $listOfFailedCampaign,$ipAddress);
                        /** LOG ACTION */

                        /** UPDATE ALL STATUS TO NORMAL */
                        return response()->json(array('result'=>'success','params'=>'','title'=>'Payment Successfully!','msg'=>'Your payment retry was successful. The transaction has been processed, and your account has been updated accordingly. Thank you for your prompt attention.'));

                    }else{
                        Log::info('updateCard block 6');
                        /** LOG ACTION */
                        $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
                        $loguser = $this->logUserAction($userID,'Credit Card Recharge or Updated Failed','Recharge Credit Card with card : ' . $custStripeID . ' | cardID :' . $custStripeCardID . ' | username :' . $name . ' | email :' . $user[0]['email'] . ' | Description:' . $_descriptionInvoice,$ipAddress);
                        /** LOG ACTION */
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=> 'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience'));
                    }

                }
                /** CHARGE THE FAILED PAYMENT */
                

                return response()->json(array('result'=>'success','params'=>'','title' => 'Thank You','msg'=>'Your credit card information has been updated.'));

            }
        }

        return response()->json(array('result'=>'failed','params'=>'','title'=>'','msg'=>''));
    }

    public function getcardinfo(Request $request) {
        $userID = (isset($request->usrID) && $request->usrID != '')?$request->usrID:$request->user()->id;
        
        $user = User::select('id','customer_card_id','customer_payment_id','company_parent','company_id','company_root_id','user_type')
                    ->where('id','=',$userID)
                    ->get();
                    
        if(count($user) > 0) {
            /** CHECK IF ONLY USER / ADMIN FIND THE OWNER */
            if ($user[0]['user_type'] == 'user') {
                $_companyID = $user[0]['company_id'];
                $user = User::select('id','customer_card_id','customer_payment_id','company_parent','company_id','company_root_id','user_type')
                    ->where('company_id','=',$_companyID)
                    ->where('user_type','=','userdownline')
                    ->where('active','=','T')
                    ->get();
            }
            /** CHECK IF ONLY USER / ADMIN FIND THE OWNER */

            if ($user[0]['customer_card_id'] != '' && $user[0]['customer_payment_id'] != '') {
                $customerPaymentID = $user[0]['customer_payment_id'];
                $customerCardID = $user[0]['customer_card_id'];

                /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */
                $accConID = '';
                if ($user[0]['company_parent'] != '') {
                    $accConID = $this->check_connected_account($user[0]['company_parent'],$user[0]['company_root_id']);
                }
                /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */

                /** CHECK IF USER DATA STILL ON PLATFORM */
                $chkStripeUser = $this->check_stripe_customer_platform_exist($user,$accConID);
                $chkResultUser = json_decode($chkStripeUser);
                if ($chkResultUser->result == 'success') {
                    $customerPaymentID = $chkResultUser->custStripeID;
                    $customerCardID = $chkResultUser->CardID;

                /** CHECK IF USER DATA STILL ON PLATFORM */

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

                        $cardinfo = $stripe->customers->retrieveSource(
                                        $customerPaymentID,
                                        $customerCardID,
                                        [],
                                        ['stripe_account' => $accConID],
                                    );
                        return response()->json(array('result'=>'success','params'=>$cardinfo));    
                }else{
                    return response()->json(array('result'=>'failed','params'=>''));
                }
            }
        }

        return response()->json(array('result'=>'failed','params'=>''));
    }

    private function createOrganization($organizationName,$parentOrganization = "",$customID="") {
        $http = new \GuzzleHttp\Client;

        $appkey = config('services.simplifi.app_key');
        $usrkey = config('services.simplifi.usr_key');
        $apiURL = config('services.simplifi.endpoint') . "organizations";
        
        $parentID = (trim($parentOrganization) == "")?config('services.sifidefaultorganization.organizationid'):trim($parentOrganization);

        $sifiEMMStatus = "";

        $organizationName = $this->makeSafeTitleName($organizationName);
        
        if (!str_contains($organizationName,'[CLIENT') && !str_contains($organizationName,'[AGENCY')) {
            $sifiEMMStatus = "[EMM]";
            if (config('services.appconf.devmode') === true) {
                $sifiEMMStatus = "[EMM BETA]";
            }
        }

        if ($sifiEMMStatus != "") {
            $organizationName = $organizationName . ' - ' . date('His');
        }

        try {
            $options = [
                'headers' => [
                    'X-App-Key' => $appkey,        
                    'X-User-Key' => $usrkey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    "organization" =>[
                        "name" => $organizationName,
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
            $this->send_email(array('harrison@uncommonreach.com'),$from,'Error Log SIFI Create Organization :' . $organizationName . ' parent ID:' . $parentID . '(Apps DATA - createOrganization - PaymentCont - L234) ',$details,array(),'emails.tryseramatcherrorlog','');


            return "";
        }

    }

    public function directClientRegister(Request $request) {
        $userID = (isset($request->usrID) && $request->usrID != '')?$request->usrID:$request->user()->id;

        if (isset($userID) && $userID != '') {
            $usrUpdt = User::find($userID);
            $usrUpdt->customer_payment_id = 'agencyDirectPayment';
            $usrUpdt->customer_card_id = 'agencyDirectPayment';
            $usrUpdt->save();

            $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();

            $loguser = $this->logUserAction($usrUpdt->id,'Payment Direct Setup services',"Agreed with Service and Billing Agreement, user name:" . $usrUpdt->name . ", email :" . $usrUpdt->email,$ipAddress);

            return response()->json(array('result'=>'success','params'=>'','msg'=>''));
        }
    }

    public function createcustomer(Request $request) {
        $tokenid = $request->tokenid;
        $userID = (isset($request->usrID) && $request->usrID != '')?$request->usrID:$request->user()->id;

        $cardholder = $request->cardholder;
        $address = $request->address;
        $city = $request->city;
        $state = $request->state;
        $country = (isset($request->country))?$request->country:'US';
        $zip = $request->zip;
        $action = (isset($request->action))?$request->action:'';

        $_payment_status = "";
        $_failed_campaignid = "";
        $_failed_invoiceid = "";
        $_failed_total_amount = "";
        $simplifi_organizationid = "";
       

        $user = User::select('name','phonenum','email','company_id','user_type','company_parent','company_root_id','payment_status','failed_campaignid','failed_invoiceid','failed_total_amount','email')
                    ->where('id','=',$userID)
                    ->get();
        //return response()->json(array('result'=>'success'));
        //exit;die();
        if(count($user) > 0) {
            $name = (isset($user[0]['name']))?$user[0]['name']:'';
            $phone = (isset($user[0]['phonenum']))?$user[0]['phonenum']:'';
            $email = (isset($user[0]['email']))?$user[0]['email']:'';
            $usertype = (isset($user[0]['user_type']))?$user[0]['user_type']:'';
            $simplifi_organizationid = '';
            $defaultParentOrganization = config('services.sifidefaultorganization.organizationid');

            $_payment_status = $user[0]['payment_status'];
            $_failed_campaignid = $user[0]['failed_campaignid'];
            $_failed_invoiceid = $user[0]['failed_invoiceid'];
            $_failed_total_amount = $user[0]['failed_total_amount'];

            $company_id = (isset($user[0]['company_id']) && $user[0]['company_id'] != '')?$user[0]['company_id']:'';
            $company_name = '';
            $_company_name = '';
            
            $companyrslt = Company::select('company_name','simplifi_organizationid')
                                    ->where('id','=',$company_id)
                                    ->get();
            if (count($companyrslt) > 0) {
                $company_name = ' (' . $companyrslt[0]['company_name'] . ')';
                $_company_name = $companyrslt[0]['company_name'];
                $simplifi_organizationid = $companyrslt[0]['simplifi_organizationid'];
            }

            /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */
            $accConID = '';
            if ($user[0]['company_parent']) {
                $accConID = $this->check_connected_account($user[0]['company_parent'],$user[0]['company_root_id']);
            }
            /** CHECK IF THIS COMPANY USERDOWNLINE OR CLIENT */

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
            
            if ($tokenid != '') {
                
                try {
                    $createCustomer = $stripe->customers->create([
                        'name' => $name . $company_name,
                        'phone' => $phone,
                        'email' => $email,
                        'description' => $_company_name,
                        'source' => $tokenid,
                    ], ['stripe_account' => $accConID]);
                } catch (\Throwable $th) {
                    return response()->json(array('result'=>'failed','params'=>'','title'=>'Oh no!','msg'=> $th->getMessage()));
                }
              

                $custID = $createCustomer->id;
                $cardID = $createCustomer->default_source;

                $updateCust = User::find($userID);
                $updateCust->customer_payment_id = $custID;
                $updateCust->customer_card_id = $cardID;
                $updateCust->save();

                $custStripeID = $custID;
                $custStripeCardID = $cardID;
                $custEmail = $email;

                /** UPDATE CARD INFO */
                try {
                    $updateCC = $stripe->customers->updateSource($custID,$cardID,[
                        'name' => $cardholder,
                        'address_line1' => $address,
                        'address_city' => $city,
                        'address_state' => $state,
                        'address_country' => $country,
                        'address_zip' => $zip,
                    ], ['stripe_account' => $accConID]);
                } catch (\Throwable $th) {
                    return response()->json(array('result'=>'failed','params'=>'','title'=>'Oh no!','msg'=> $th->getMessage()));
                }
               
                /** UPDATE CARD INFO */

                /** LOG ACTION */
                    $ipAddress = ($request->header('Clientip') !== null)?$request->header('Clientip'):$request->ip();
                    $loguser = $this->logUserAction($userID,'Credit Card Added','Credit Card Added : ' . $custID . ' | cardID :' . $cardID . ' | username :' . $name . ' | email :' . $email ,$ipAddress);
                    $loguser = $this->logUserAction($userID,'Service and Billing Agreement','The user has agreed to the Service and Billing Agreement. Name : ' . $name . ' | Company Name :' . $company_name . ' | Email :' . $email . ' | userID :' . $custID ,$ipAddress);
                /** LOG ACTION */

                /** CREATE SIMPLIFI IF DOWNLINE / AGENCY */
                if ($usertype == 'userdownline' && $simplifi_organizationid == '') {
        
                    $sifiEMMStatus = "[AGENCY]";
                    if (config('services.appconf.devmode') === true) {
                        $sifiEMMStatus = "[AGENCY BETA]";
                    }
                    

                    /** CREATE ORGANIZATION */
                    $companyOrganizationID = $this->createOrganization(trim($_company_name) . ' ' . $sifiEMMStatus,$defaultParentOrganization);
                    if ($companyOrganizationID != '') {
                        $companyupdate = Company::find($company_id);
                        $companyupdate->simplifi_organizationid = $companyOrganizationID;
                        $companyupdate->update();

                        $simplifi_organizationid = $companyOrganizationID;
                    }
                    /** CREATE ORGANIZATION */
                }
                /** CREATE SIMPLIFI IF DOWNLINE / AGENCY */

                /** Make DEFAULT SUBSCRIBE PAYMENT FREE FOR NEW AGENCY */

                /** CHECK IF THERE IS ANY OTHER ROOT HAVE FREE PLAN AS DEFAULT */
                $getRootSetting = $this->getcompanysetting($user[0]['company_root_id'],'rootsetting');
                $anotherRootDefaultFreePlan = false;
                if ($getRootSetting != '') {
                    if (isset($getRootSetting->defaultfreeplan) && $getRootSetting->defaultfreeplan == 'T') {
                        $anotherRootDefaultFreePlan = true;
                    }
                }

                if ($usertype == 'userdownline' && ($user[0]['company_root_id'] == config('services.application.systemid') || $anotherRootDefaultFreePlan)) {
                    /** GET FREE PACKAGE PLAN */
                    
                    $companycheck = CompanyStripe::where('company_id','=',$company_id)
                                        ->get();
                    if($companycheck->count() == 0) {
                        $freeplanID = "";
                        $getfreePlan = $this->getcompanysetting($user[0]['company_root_id'],'agencyplan');
                        if ($getfreePlan != '') {
                                $freeplanID = (isset($getfreePlan->livemode->free))?$getfreePlan->livemode->free:"";
                            if (config('services.appconf.devmode') === true) {
                                $freeplanID = (isset($getfreePlan->testmode->free))?$getfreePlan->testmode->free:"";
                            }
                        }

                        try{
                            /** CREATE SUBSCRIPTION */
                           $createSub = $stripe->subscriptions->create([
                               "customer" => trim($custStripeID),
                               "items" => [
                                   ["price" => $freeplanID],
                               ],
                               "default_source" => $custStripeCardID,

                           ]);
                           /** CREATE SUBSCRIPTION */

                           /** CREATE COMPANY STRIPE */
                           $createCompany = CompanyStripe::create([
                                'company_id' => $company_id,
                                'acc_connect_id' => '',
                                'acc_prod_id' => '',
                                'acc_email' => '',
                                'acc_ba_id' => '',
                                'acc_holder_name' => '',
                                'acc_holder_type' => '',
                                'ba_name' => '',
                                'ba_route' => '',
                                'status_acc' => '',
                                'ipaddress' => '',
                                'package_id' => $freeplanID,
                                'subscription_id' => $createSub->id,
                                'subscription_item_id' => $createSub->items->data[0]['id'],
                                'plan_date_created' => date('Y-m-d'),
                                'plan_next_date' => date('Y-m-d',strtotime(date('Y-m-d') . ' +1 years'))
                            ]);
                           
                           /** CREATE COMPANY STRIPE */
                       }catch (Exception $e) {
                       }

                    }
                    
                    /** GET FREE PACKAGE PLAN */
                }
                /** Make DEFAULT SUBSCRIBE PAYMENT FREE FOR NEW AGENCY */

                /** CHARGE THE FAILED PAYMENT */
                if ($action != '' && $_payment_status == 'failed' && trim($_failed_campaignid) != '') {
                    $_totalFailedAmount = 0;
                    $_descriptionInvoice = "";

                    $_failed_total_amount = explode('|',$_failed_total_amount);
                    $_failed_campaignid = explode('|',$_failed_campaignid);
                    $_failed_invoiceid = explode('|',$_failed_invoiceid);

                    for($i=0;$i<count($_failed_total_amount);$i++) {
                        $_totalFailedAmount = $_totalFailedAmount + (float)$_failed_total_amount[$i];
                        $_descriptionInvoice = $_descriptionInvoice . '#' . $_failed_campaignid[$i] . ' ';
                    }
                    
                    $_descriptionInvoice = "Invoice for Failed Payment on Campaigns: " . $_descriptionInvoice;
                    
                    $statusRecharge = true;
                    $paymentintentID = "";

                    try {
                        $chargeAmount = $_totalFailedAmount * 100;
                        $payment_intent =  $stripe->paymentIntents->create([
                            'payment_method_types' => ['card'],
                            'customer' => trim($custStripeID),
                            'amount' => $chargeAmount,
                            'currency' => 'usd',
                            'receipt_email' => $custEmail,
                            'payment_method' => $custStripeCardID,
                            'confirm' => true,
                            'description' => $_descriptionInvoice,
                        ],['stripe_account' => $accConID]);
    
                        $paymentintentID = $payment_intent->id;

                        /* CHECK STATUS PAYMENT INTENTS */
                        $payment_intent_status = (isset($payment_intent->status))?$payment_intent->status:"";
                        if($payment_intent_status == 'requires_action') {
                            $statusRecharge = false;
                            return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused.'));
                        }
                        /* CHECK STATUS PAYMENT INTENTS */
                    
                    }catch (RateLimitException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. <br/>(' . $e->getMessage() . ')'));
                    } catch (InvalidRequestException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. <br/>(' . $e->getMessage() . ')'));
                    } catch (AuthenticationException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. <br/>(' . $e->getMessage() . ')'));
                    } catch (ExceptionAuthenticationException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. <br/>(' . $e->getMessage() . ')'));
                    } catch (ApiConnectionException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. <br/>(' . $e->getMessage() . ')'));
                    } catch (ApiErrorException $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. <br/>(' . $e->getMessage() . ')'));
                    } catch (Exception $e) {
                        $statusRecharge = false;
                        return response()->json(array('result'=>'failed','params'=>'','title'=>'Payment retry unsuccessful!','msg'=>'Regrettably, the attempt to retry your payment was not successful. Please review and verify your payment information. If the issue persists, kindly reach out to our support team for further assistance. We apologize for any inconvenience this may have caused. <br/>(' . $e->getMessage() . ')'));
                    } 

                    if ($statusRecharge == true && $paymentintentID != "") {
                        /** UPDATE ALL STATUS TO NORMAL */
                        $_failed_campaigns_paused = "";
                        
                        $updateUser = User::find($userID);
                        $_failed_campaigns_paused = explode('|',$updateUser->failed_campaigns_paused);

                        $updateUser->payment_status = "";
                        $updateUser->failed_campaignid = "";
                        //$updateUser->failed_invoiceid = "";
                        $updateUser->failed_invoicenumber = "";
                        $updateUser->failed_total_amount = "";
                        $updateUser->save();

                        for($i=0;$i<count($_failed_invoiceid);$i++) {
                            $updateInvoice = LeadspeekInvoice::find($_failed_invoiceid[$i]);
                            $updateInvoice->status = 'paid';
                            $updateInvoice->save();
                        }

                        for($i=0;$i<count($_failed_campaigns_paused);$i++) {
                             /** ACTIVATE CAMPAIGN SIMPLIFI */
                             if ($simplifi_organizationid != '' && $_failed_campaigns_paused[$i] != '') {
                                $getLeadspeekCampaignsID = LeadspeekUser::select('leadspeek_campaignsid')->where('leadspeek_api_id','=',$_failed_campaigns_paused[$i])->get();

                                $_leadspeek_campaignsid = "";

                                if (count($getLeadspeekCampaignsID) > 0) {
                                    $_leadspeek_campaignsid = $getLeadspeekCampaignsID[0]['leadspeek_campaignsid'];
                                } 
                                if ($_leadspeek_campaignsid != '') {
                                    $camp = $this->startPause_campaign($simplifi_organizationid,$_leadspeek_campaignsid,'activate');
                                    if ($camp != true) {
                                        /** SEND EMAIL TO ME */
                                            $details = [
                                                'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $_failed_campaigns_paused[$i]. '<br/>',
                                            ];

                                            $from = [
                                                'address' => 'noreply@sitesettingsapi.com',
                                                'name' => 'support',
                                                'replyto' => 'noreply@sitesettingsapi.com',
                                            ];
                                            $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - PAYMENT CONTROLLER (restart campaign pause due the payment failed - L208) #' .$_failed_campaigns_paused[$i],$details,array(),'emails.tryseramatcherrorlog',$from,'');
                                        /** SEND EMAIL TO ME */
                                    }else{
                                        $updateCampaignStatus = LeadspeekUser::where('leadspeek_api_id','=',$_failed_campaigns_paused[$i])->update(['active' => 'T','disabled' => 'F','active_user' => 'T']);
                                    }
                                }
                            }
                            /** ACTIVATE CAMPAIGN SIMPLIFI */
                        }
                        /** UPDATE ALL STATUS TO NORMAL */
                        return response()->json(array('result'=>'success','params'=>'','title'=>'Payment Successfully!','msg'=>'Your payment retry was successful. The transaction has been processed, and your account has been updated accordingly. Thank you for your prompt attention.'));
                    }
                }
                /** CHARGE THE FAILED PAYMENT */
                
                return response()->json(array('result'=>'success','params'=>'','title' => 'Thank You','msg'=>'Your Credit Card Information Has been updated.'));
            }
        }

        return response()->json(array('result'=>'failed','params'=>'','msg'=>''));
        
    }
}
