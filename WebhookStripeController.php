<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LeadspeekInvoice;
use App\Models\LeadspeekUser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException as ExceptionAuthenticationException;
use Stripe\Exception\OAuth\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\StripeClient;

class WebhookStripeController extends Controller
{
    public function stripe(Request $request)
    {
        Log::info('webhook stripe', [
            'all' => $request->all()
        ]);

        $type = $request['type'] ?? '';
        $payment_method_types = $request['data']['object']['payment_method_types'][0] ?? '';
        $code_error = $request['data']['object']['last_payment_error']['code'] ?? '';

        if($payment_method_types == 'us_bank_account' && $type == 'payment_intent.succeeded') {
            $this->paymentIntentPaymentFailed_bankAccount($request);
        }
        else if($payment_method_types == 'us_bank_account' && $type == 'payment_intent.payment_failed' && $code_error != 'charge_exceeds_source_limit') {
            $this->paymentIntentPaymentFailed_bankAccount($request);
        }
        else if($payment_method_types == 'us_bank_account' && $type == 'charge.dispute.closed') {
            $this->chargeDisputeClosed_bankAccount($request);
        }
    }
    
    /* EVENT TYPE */
    private function paymentIntentPaymentFailed_bankAccount(Request $request)
    {
        Log::info("==================paymentIntentPaymentFailed_bankAccount==================");
        $metadata = $request['data']['object']['metadata'];
        // $leadspeek_api_id = $metadata['user']['leadspeek_api_id'] ?? "";
        // $customer_payment_id = $metadata['user']['customer_payment_id'] ?? "";
        // $customer_card_id = $metadata['user']['customer_card_id'] ?? "";
        // $acc_connect_id = $metadata['acc_connect_id'] ?? "";
        // $invoice_id = $metadata['invoice_id'] ?? "";
        // $total_amount = $metadata['total_amount'] ?? "";
        // $company_root_id = $metadata['company_root_id'] ?? "";
        // $admin_notify_to = $metadata['admin_notify_to'] ?? "";
        // $company_name = $metadata['company_name'] ?? "";

        Log::info('payment_intent_payment_failed');
        Log::info(['metadata' => $metadata]);

        /* TRY CHARGE CREDIT CARD */
        if($metadata['try_credit_'])
        $chargeCC = $this->tryChargeCreditCard($metadata);
        Log::info(['chargeCC' => $chargeCC]);
        /* TRY CHARGE CREDIT CARD */

        $this->processPauseCampaign_failedPayment($metadata);
        if($chargeCC['result' == 'failed']) {
        }
        Log::info("==================paymentIntentPaymentFailed_bankAccount==================");
    }
    
    private function chargeDisputeClosed_bankAccount(Request $request)
    {
        Log::info('charge_dispute_closed');
        Log::info(['all' => $request->all()]);
    }
    /* EVENT TYPE */

    /* PROCESS */
    private function tryChargeCreditCard($metadata = [])
    {
        $customer_payment_id = $metadata['user']['customer_payment_id'] ?? "";
        $customer_card_id = $metadata['user']['customer_card_id'] ?? "";
        $acc_connect_id = $metadata['acc_connect_id'] ?? "";
        $company_root_id = $metadata['user']['company_root_id'] ?? "";
        $company_parent = $metadata['user']['company_parent'] ?? "";
        $leadspeek_api_id = $metadata['user']['leadspeek_api_id'] ?? "";
        $cust_email = $metadata['user']['cust_email'] ?? "";
        $invoice_id = $metadata['invoice_id'] ?? "";
        $default_invoice = $metadata['user']['default_invoice'] ?? "";
        $total_amount = $metadata['total_amount'] ?? 0;
        $platformfee = $metadata['platformfee'] ?? 0;
        $company_name = $metadata['company_name'] ?? 0;

        $result = "success";
        $errorstripe = "";

        $platformfee = number_format($platformfee,2,'.','');
        $charge_amount = $total_amount * 100;

        Log::info([
            'action' => 'tryChargeCreditCard',
            'customer_payment_id' => $customer_payment_id,
            'customer_card_id' => $customer_card_id,
            'acc_connect_id' => $acc_connect_id,
            'company_root_id' => $company_root_id,
            'leadspeek_api_id' => $leadspeek_api_id,
            'cust_email' => $cust_email,
            'invoice_id' => $invoice_id,
            'default_invoice' => $default_invoice,
            'charge_amount' => $total_amount,
            'platformfee' => $platformfee
        ]);        

        /** GET STRIPE KEY */
        $stripeseckey = config('services.stripe.secret');
        $stripepublish = $this->getcompanysetting($company_root_id,'rootstripe');
        if ($stripepublish != '') {
            $stripeseckey = (isset($stripepublish->secretkey))?$stripepublish->secretkey:"";
        }
        /** GET STRIPE KEY */

        $stripe = new StripeClient([
            'api_key' => $stripeseckey,
            'stripe_version' => '2020-08-27'
        ]);

        $leadspeekInvoice = LeadspeekInvoice::where('id', $invoice_id)
                                            ->first();

        try {
            $payment_intent =  $stripe->paymentIntents->create([
                'payment_method_types' => ['card'],
                'customer' => trim($customer_payment_id),
                'amount' => $charge_amount,
                'currency' => 'usd',
                'receipt_email' => $cust_email,
                'payment_method' => $customer_card_id,
                'confirm' => true,
                'description' => $default_invoice,
                'application_fee_amount' => ($platformfee * 100),
            ],['stripe_account' => $acc_connect_id]);
            // Log::info('',['payment_intent' => $payment_intent]);
    
            /* CHECK STATUS PAYMENT INTENTS */
            $payment_intent_status = (isset($payment_intent->status))?$payment_intent->status:"";
            if($payment_intent_status == 'requires_action') {
                /* UPDATE STATUS INVOICE */
                $leadspeekInvoice->status = 'failed';
                $leadspeekInvoice->save();
                /* UPDATE STATUS INVOICE */

                $result = "failed";
                $errorstripe = "Payment for campaign $leadspeek_api_id was unsuccessful: Stripe status '$payment_intent_status' indicates further user action is needed.";
            }
            /* CHECK STATUS PAYMENT INTENTS */
        } catch (RateLimitException $e) {
            Log::info(['error' => $e->getMessage()]);
            
            /* UPDATE STATUS INVOICE */
            $leadspeekInvoice->status = 'failed';
            $leadspeekInvoice->save();
            /* UPDATE STATUS INVOICE */

            // Too many requests made to the API too quickly
            $result = "failed";
            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
        } catch (InvalidRequestException $e) {
            Log::info(['error' => $e->getMessage()]);
            
            /* UPDATE STATUS INVOICE */
            $leadspeekInvoice->status = 'failed';
            $leadspeekInvoice->save();
            /* UPDATE STATUS INVOICE */

            // Invalid parameters were supplied to Stripe's API
            $result = "failed";
            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
        } catch (ExceptionAuthenticationException $e) {
            Log::info(['error' => $e->getMessage()]);
            
            /* UPDATE STATUS INVOICE */
            $leadspeekInvoice->status = 'failed';
            $leadspeekInvoice->save();
            /* UPDATE STATUS INVOICE */

            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            $result = "failed";
            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
        } catch (ApiConnectionException $e) {
            Log::info(['error' => $e->getMessage()]);
            
            /* UPDATE STATUS INVOICE */
            $leadspeekInvoice->status = 'failed';
            $leadspeekInvoice->save();
            /* UPDATE STATUS INVOICE */

            // Network communication with Stripe failed
            $result = "failed";
            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
        } catch (ApiErrorException $e) {
            Log::info(['error' => $e->getMessage()]);
            
            /* UPDATE STATUS INVOICE */
            $leadspeekInvoice->status = 'failed';
            $leadspeekInvoice->save();
            /* UPDATE STATUS INVOICE */

            // Display a very generic error to the user, and maybe send
            // yourself an email
            $result = "failed";
            $errorstripe = $e->getHttpStatus() . '|' . $e->getError()->type . '|' . $e->getError()->code . '|' . $e->getError()->param . '|' . $e->getError()->message;
        } catch (Exception $e) {
            Log::info(['error' => $e->getMessage()]);
            
            /* UPDATE STATUS INVOICE */
            $leadspeekInvoice->status = 'failed';
            $leadspeekInvoice->save();
            /* UPDATE STATUS INVOICE */

            // Something else happened, completely unrelated to Stripe
            $result = "failed";
            $errorstripe = 'error not stripe things';
        }

        /* UPDATE STATUS INVOICE */
        $leadspeekInvoice->status = 'success';
        $leadspeekInvoice->save();
        /* UPDATE STATUS INVOICE */

        /* SEND EMAIL */
        $AdminDefault = $this->get_default_admin($company_root_id);
        $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';
        $rootCompanyInfo = $this->getCompanyRootInfo($company_root_id);
        $defaultdomain = $this->getDefaultDomainEmail($company_root_id);

        // $details = [
        //     'paymentterm' => $clientPaymentTerm,
        //     'name'  => $companyName,
        //     'cardlast' => trim($cardlast),
        //     'leadspeekapiid' =>$_leadspeek_api_id,
        //     'invoiceNumber' => $invoiceNum . '-' . $invoiceID,
        //     'invoiceStatus' => $statusPayment,
        //     'invoiceDate' => date('m-d-Y'),
        //     'onetimefee' => $oneTime,
        //     'cost_perlead' => (isset($topup['cost_perlead']))?$topup['cost_perlead']:'0',
        //     'total_leads' => (isset($topup['total_leads']))?$topup['total_leads']:'0',
        //     'platform_onetimefee' => $platform_LeadspeekPlatformFee,
        //     'platform_cost_perlead' => (isset($topup['platform_price']))?$topup['platform_price']:'0',
        //     'min_cost' => $minCostLeads,
        //     'platform_min_cost' => $platform_LeadspeekMinCostMonth,
        //     'min_leads'=> $clientMaxperTerm,
        //     'total_amount' => $totalAmount,  
        //     'platform_total_amount' => $platformfee_ori,
        //     'invoicetype' => 'agency',
        //     'agencyname' => $rootCompanyInfo['company_name'],
        //     'defaultadmin' => $AdminDefaultEmail,
        // ];
        // $attachement = array();

        // $from = [
        //     'address' => 'noreply@' . $defaultdomain,
        //     'name' => 'Invoice',
        //     'replyto' => 'support@' . $defaultdomain,
        // ];

        // $subjectFailed = "";
        // if ($result == 'failed') {
        //     $subjectFailed = "Failed Payment - ";
        // } else if($result == 'pending') {
        //     $subjectFailed = "Pending Payment - ";
        // }

        // $tmp = $this->send_email($adminEmail,$from,$subjectFailed . 'Invoice for ' . $company_name . ' #' . $leadspeek_api_id . ' (' . date('m-d-Y',strtotime($todayDate)) . ')',$details,$attachement,'emails.tryseramatchlistcharge',$company_parent);
        /* SEND EMAIL */

        return ['result' => 'success', 'message' => 'success charge with credit card'];
    }

    private function processPauseCampaign_failedPayment($metadata = [])
    {
        $leadspeek_api_id = $metadata['user']['leadspeek_api_id'] ?? "";
        $invoice_id = $metadata['invoice_id'] ?? "";
        $total_amount = $metadata['total_amount'] ?? "";
        $company_root_id = $metadata['company_root_id'] ?? "";
        $admin_notify_to = $metadata['admin_notify_to'] ?? "";
        $company_name = $metadata['company_name'] ?? "";

        Log::info([
            'action' => 'processPauseCampaign_failedPayment',
            'leadspeek_api_id' => $leadspeek_api_id,
            'invoice_id' => $invoice_id,
            'total_amount' => $total_amount,
            'company_root_id' => $company_root_id,
            'admin_notify_to' => $admin_notify_to,
            'company_name' => $company_name
        ]);
        

        if(trim($leadspeek_api_id) == '' || trim($invoice_id) == '' || trim($total_amount) == '' || trim($company_root_id) == '' || trim($company_name) == '') {
            return false;
        }

        $ClientCompanyIDFailed = "";
        $ListFailedCampaign = "";
        $_ListFailedCampaign = "";
        $_failedUserID = "";

        $leadsuser = LeadspeekUser::select('leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.trysera','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id','users.company_id')
                            ->join('users','leadspeek_users.user_id','=','users.id')
                            ->where('leadspeek_users.leadspeek_api_id','=',$leadspeek_api_id)
                            ->get();

        Log::info('', [
            'leadspeek_api_id' => $leadspeek_api_id,
            'invoice_id' => $invoice_id,
            'leadUser' => $leadsuser
        ]);

        if (count($leadsuser) > 0) {
            foreach($leadsuser as $lds) {
                $_lp_user_id = $lds['id'];
                $invoiceNum = date('Ymd') . '-' . $_lp_user_id;
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

                    $failedInvoice_id = $invoice_id;
                    $failedInvoiceNumber = $invoiceNum . '-' . $invoice_id;
                    $failedTotalAmount = $total_amount;
                    $failedCampaignID = $leadspeek_api_id;

                    if (trim($updateUser->failed_invoice_id) != '') {
                        $failedInvoice_id = $updateUser->failed_invoice_id . '|' . $failedInvoice_id;
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

                    
                    $updateUser->payment_ach_status = 'failed';
                    $updateUser->failed_invoice_id = $failedInvoice_id;
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
                                    'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $leadspeek_api_id. '<br/>',
                                ];

                                $from = [
                                    'address' => 'noreply@sitesettingsapi.com',
                                    'name' => 'support',
                                    'replyto' => 'noreply@sitesettingsapi.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-due the payment failed - L2197) #' .$leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
                            /** SEND EMAIL TO ME */
                        }
                    }
                    /** ACTIVATE CAMPAIGN SIMPLIFI */

                    $ListFailedCampaign = $ListFailedCampaign . $leadspeek_api_id . '<br/>';
                    $_ListFailedCampaign = $_ListFailedCampaign . $leadspeek_api_id . '|';

                }
            }

            /** PAUSED THE OTHER ACTIVE CAMPAIGN FOR THIS CLIENT */
            $otherCampaignPause = false;

            $leadsuser = LeadspeekUser::select('leadspeek_users.id','leadspeek_users.leadspeek_type','leadspeek_users.active','leadspeek_users.disabled','leadspeek_users.trysera','leadspeek_users.leadspeek_organizationid','leadspeek_users.leadspeek_campaignsid','users.customer_payment_id','leadspeek_users.user_id','users.company_id','leadspeek_users.leadspeek_api_id')
                                ->join('users','leadspeek_users.user_id','=','users.id')
                                ->where('users.company_id','=',$ClientCompanyIDFailed)
                                ->where('users.user_type','=','client')
                                ->where('leadspeek_users.leadspeek_api_id','<>',$leadspeek_api_id)
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
                                    'errormsg'  => 'Simpli.Fi Error Leadspeek ID :' . $leadspeek_api_id. '<br/>',
                                ];

                                $from = [
                                    'address' => 'noreply@sitesettingsapi.com',
                                    'name' => 'support',
                                    'replyto' => 'noreply@sitesettingsapi.com',
                                ];
                                $this->send_email(array('harrison@uncommonreach.com'),'Start Pause Campaign Failed (INTERNAL - CronAPI-due the payment failed - L2197) #' .$leadspeek_api_id,$details,array(),'emails.tryseramatcherrorlog',$from,'');
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
                $updateInvoiceCampaignPaused = LeadspeekInvoice::find($invoice_id);
                $updateInvoiceCampaignPaused->campaigns_paused = rtrim($_ListFailedCampaign,"|");
                $updateInvoiceCampaignPaused->save();
                /** UPDATE ON INVOICE TABLE THAT FAILED WITH CAMPAIGN LIST THAT PAUSED */

                $usrUpdate = User::find($_failedUserID);
                $usrUpdate->failed_campaigns_paused = rtrim($_ListFailedCampaign,"|");
                $usrUpdate->save();
                
                if (trim($ListFailedCampaign) != '' && (isset($userStripeID) && $userStripeID != '')) {
                    /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */
                    $adminnotify = explode(',',$admin_notify_to);
                    $tmp = User::select('email')->whereIn('id', $adminnotify)->get();
                    $adminEmail = array();
                    foreach($tmp as $ad) {
                        array_push($adminEmail,$ad['email']);
                    }
                    array_push($adminEmail,'harrison@uncommonreach.com');

                    $attachement = [];

                    $AdminDefault = $this->get_default_admin($company_root_id);
                    $AdminDefaultEmail = (isset($AdminDefault[0]['email']))?$AdminDefault[0]['email']:'';
                    $rootCompanyInfo = $this->getCompanyRootInfo($company_root_id);
                    $defaultdomain = $this->getDefaultDomainEmail($company_root_id);

                    $from = [
                        'address' => 'noreply@' . $defaultdomain,
                        'name' => 'Invoice',
                        'replyto' => 'support@' . $defaultdomain,
                    ];
                    
                    $details = [
                        'campaignid'  => $leadspeek_api_id,
                        'stripeid' => (isset($userStripeID))?$userStripeID:'',
                        'othercampaigneffected' => $ListFailedCampaign,
                    ];
                    
                    $this->send_email($adminEmail,'Campaign ' . $company_name . ' #' . $leadspeek_api_id . ' (has been pause due the payment failed)',$details,$attachement,'emails.invoicefailed',$from,"");
                    
                    /** SEND EMAIL TELL THIS CAMPAIN HAS BEEN PAUSED BECAUSE FAILED PAYMENT */
                }
            }
        }
    }
    /* PROCESS */
}
