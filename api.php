<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function () {
    /*Route::get('/user', function (Request $request) {
        return $request->user();
    });*/
    Route::get('/user/only/{usrID?}','UserController@onlyuser');
    Route::get('/user/{usrID?}','UserController@show');
    Route::post('/user/update','UserController@update');
    Route::post('/user/resetpassword','UserController@resetpassword');
    //Route::put('/user/setupcomplete','UserController@setupcomplete');
    Route::get('/user/checksetupcomplete/{usrID?}','UserController@checksetupcomplete');
    //Route::delete('/users/{id}','UserController@destroy');
});

Route::middleware('auth:api')->group(function () {
    Route::get('/industry/autocomplete/{searchtext}','IndustryController@autocomplete');
    Route::get('/industry/search/{searchtext}','IndustryController@search');
});

Route::middleware('auth:api')->group(function () {
    Route::delete('/company/group/remove/{companyGroupID?}','CompanyController@removeCompanyGroup');
    Route::post('/company/group','CompanyController@addEditCompanyGroup');
    Route::get('/company/group/{CompanyID?}/{UserModule?}','CompanyController@getCompanyGroup');
    Route::get('/company/autocomplete/{searchtext}','CompanyController@autocomplete');
    Route::get('/company/search/{searchtext}','CompanyController@search');
});

Route::post('/banner/startbannercreate','BannerController@requestbannercreate');
Route::post('/banner/show','BannerController@show');

Route::get('/marketing/getorganization/{OrganizationID?}/{RequestType?}','MarketingController@getorganization');
Route::post('/marketing/createorganization','MarketingController@create_organization');
Route::post('/marketing/createupdate-organization-tag','MarketingController@create_update_organization_tag');
Route::post('/marketing/create-audience-organization-tag','MarketingController@create_audiences_organization_tag');

Route::delete('/marketing/deleteorganization/{OrganizationID}','MarketingController@deleteorganization');

/** CONFIGURATION */
Route::middleware('auth:api')->group(function () {
    Route::get('/configuration/sales-downline/{CompanyID?}/{usrID?}/{idsys?}/{searchKey?}','ConfigurationController@salesdownline');
    Route::get('/configuration/user/{CompanyID?}/{groupCompanyID?}/{idsys?}/{UserType?}/{SortBy?}/{OrderBy?}/{searchKey?}','ConfigurationController@show');
    Route::post('/configuration/user/resendinvitation','ConfigurationController@resendInvitation');
    Route::post('/configuration/user/testsmtp','ConfigurationController@testsmtp');
    Route::post('/configuration/user/create','ConfigurationController@create');
    Route::put('/configuration/user/update','ConfigurationController@update');
    Route::delete('/configuration/user/remove/{CompanyID?}/{UserID?}','ConfigurationController@remove');
    Route::put('/configuration/user/costmodule','ConfigurationController@costmodule');
    Route::get('/configuration/role-module/{GetType?}/{CompanyID?}/{ID?}/{usrID?}','ConfigurationController@rolemodule_show');
    Route::put('/configuration/role-module/addupdate','ConfigurationController@rolemoduleaddupdate');
    Route::delete('/configuration/role-module/remove/{CompanyID?}/{RoleID?}','ConfigurationController@removerolemodule');

    Route::get('/configuration/user-module/{userID?}','ConfigurationController@usermodule_show');

    Route::put('/configuration/subdomain','ConfigurationController@updatesubdomain');

    Route::put('/configuration/setting','ConfigurationController@updategeneralsetting');
    Route::post('/configuration/setting/test-email','ConfigurationController@testemail');
    Route::get('/configuration/setting/minleaddayenhance','ConfigurationController@getminleaddayenhance');
    Route::put('/configuration/setting/freepackage/{CompanyID?}/{packageID?}','ConfigurationController@updatefreepackageplan');
    Route::put('/configuration/setting/package/{CompanyID?}/{packageID?}','ConfigurationController@updatepackageplan');
    Route::get('/configuration/setting/{CompanyID?}/{settingname?}','ConfigurationController@getgeneralsetting');
    Route::put('/configuration/update-custom-domain','ConfigurationController@updatecustomdomain');

    Route::get('/configuration/report-analytics/{startDate?}/{endDate?}/{companyid?}/{campaignid?}/{companyrootid?}','ConfigurationController@getReportAnalytic');

    Route::put('/configuration/sales','ConfigurationController@setSalesPerson');
    Route::get('/configuration/sales/list/{CompanyID?}','ConfigurationController@getSalesList');
    Route::get('/configuration/root/list','ConfigurationController@getRootList');
    Route::put('/configuration/cancel-subscription','ConfigurationController@cancelsubscription');
   
});

Route::post('/configuration/user/sort','ConfigurationController@sorting');
/** CONFIGURATION */

/** TRYSERA */
Route::get('/marketing/tsr/getsubclients/{id?}','MarketingController@getsubclients');
Route::post('/marketing/tsr/createsubclients/','MarketingController@createsubclients');
Route::post('/marketing/tsr/matcheslist/{id?}','MarketingController@matcheslist');
/** TRYSERA */

/** LEADSPEEK */
Route::middleware('auth:api')->group(function () {
    Route::put('/leadspeek/client/local/stopcontinualtopup', 'LeadspeekController@stop_continual_topup');
    Route::get('/leadspeek/client/local/remainingbalanceleads/{leadspeek_api_id}', 'LeadspeekController@remaining_balance_leads');
    Route::get('/leadspeek/googlespreadsheet/check-connect/{CompanyID?}','LeadspeekController@googlespreadsheet_checkconnect');
    Route::get('/leadspeek/googlespreadsheet/revoke/{CompanyID?}','LeadspeekController@googlespreadsheet_revoke');
    Route::post('/leadspeek/client/resendgooglelink','LeadspeekController@resentspreadsheetlink');
    Route::post('/leadspeek/client/create','LeadspeekController@createclient');
    Route::get('/leadspeek/suppressionlist/progress','LeadspeekController@suppressionprogress');
    Route::post('/leadspeek/suppressionlist/upload','LeadspeekController@suppressionupload');
    Route::delete('/leadspeek/suppressionlist/purge/{paramID?}/{campaignType?}','LeadspeekController@suppressionpurge');
    Route::post('/leadspeek/suppressionlisttrysera/upload','LeadspeekController@suppressionuploadTrysera');
    Route::put('/leadspeek/client/update','LeadspeekController@updateclient');
    Route::put('/leadspeek/client/locator/update','LeadspeekController@updateclientlocator');
    Route::put('/leadspeek/client/local/update','LeadspeekController@updateclientlocal');
    Route::put('/leadspeek/client/activepause','LeadspeekController@activepauseclient');
    Route::put('/leadspeek/client/archive','LeadspeekController@archivecampaign');
    Route::put('/leadspeek/client/remove','LeadspeekController@removeclient');
    Route::put('/leadspeek/user/setupcomplete','LeadspeekController@setupcomplete');
    Route::get('/leadspeek/client/{groupCompanyID?}/{leadspeekType?}/{CompanyID?}/{clientID?}/{SortBy?}/{OrderBy?}/{searchKey?}','LeadspeekController@getclient');

    /** CAMPAIGN SIMPLIFI */
    Route::get('/leadspeek/getcampaignresource/{organizatonID?}/{campaignID?}','LeadspeekController@getcampaignresource');
    /** CAMPAIGN SIMPLIFI */

    /** REPORT */
    Route::get('/leadspeek/report/initdate/{CompanyID?}/{clientID?}/','LeadspeekController@getinitdatechart');
    Route::get('/leadspeek/report/chart/{CompanyID?}/{clientID?}/{startDate?}/{endDate?}','LeadspeekController@getreportchart');
    Route::get('/leadspeek/report/lead/{CompanyID?}/{clientID?}/{startDate?}/{endDate?}','LeadspeekController@getreportlead');
    //Route::get('/leadspeek/report/lead/export/{CompanyID?}/{clientID?}/{startDate?}/{endDate?}','LeadspeekController@getreportleadexport');
    Route::get('/leadspeek/report/invoice/{CompanyID?}/{clientID?}/{startDate?}/{endDate?}','LeadspeekController@getreportinvoice');
    /** REPORT */

    
});
/** LEADSPEEK */

/** UPLOAD BIG FILE */
Route::middleware('auth:api')->group(function () {
    Route::post('/file/upload','FileController@uploadLargeFiles');
});
/** UPLOAD BIG FILE */

/** PAYMENT GATEWAY */
Route::middleware('auth:api')->group(function () {
    Route::post('/payment/customer','PaymentController@createcustomer');
    Route::post('/payment/customer-direct','PaymentController@directClientRegister');
    Route::get('/payment/card/{usrID?}','PaymentController@getcardinfo');
    Route::put('/payment/card','PaymentController@updatecard');
    Route::put('/payment/set-billing-method','PaymentController@setbillingmethod');
});
/** PAYMENT GATEWAY */

/** GENERAL */
Route::middleware('auth:api')->group(function () {
    Route::get('/general/state/{statecode?}/cities','GeneralController@getstate');
    Route::get('/general/state','GeneralController@getstate');
    Route::get('/general/check-connected-account/{companyID?}/{idsys?}','GeneralController@checkconnectedaccount');
    Route::get('/general/check-sales-connected-account/{userID?}/{idsys?}','GeneralController@checkSalesConnectedAccount');
    Route::get('/general/check-payment-connection/{companyID?}/typeConnection?}','GeneralController@checkpaymentconnection');
    Route::post('/general/create-connected-account','GeneralController@createStripeConnect');
    Route::post('/general/create-sales-connected-account','GeneralController@createSalesStripeConnect');
    Route::post('/general/get-connected-account-link','GeneralController@getAcccountLink');
    Route::delete('/general/reset-payment-connection/{companyID?}/{typeConnection?}','GeneralController@resetpaymentconnection');
    Route::post('/general/referralink/create','GeneralController@createreferallink');
});

Route::get('/general/referralink/check/{refcode}','GeneralController@checkreferallink');
/** GENERAL */

/** GOHIGHLEVEL */
Route::middleware('auth:api')->group(function () {
    Route::get('/gohighlevel/tags/{company_id}','GohighlevelController@getTags');
    Route::get('/gohighlevel/usertags/{campaignid}/{companyid}','GohighlevelController@getUserTags');
});
/** GOHIGHLEVEL */

/** Kartra */
Route::middleware('auth:api')->group(function () {
    Route::get('/GetKartraListAndTag/{company_id}','KartraController@GetKartraListAndTag');
    Route::post('/kartra/save_list_tag','ListAndTagController@store');
    Route::post('/integration/details','ListAndTagController@showKartraListTag');
    Route::post('/newlead','KartraController@newlead');


});
/** Kartra */

// ZAPIER WEBHOOK
Route::middleware('auth:api')->group(function () {
    Route::get('/getcampaignwebhook/{campaign_id}','ZapierController@get_campaign_details');
    Route::get('/getcampaigntags/{campaign_id}','ZapierController@get_campaign_tags');
    Route::get('/getagencytags/{company_id}','ZapierController@get_agency_tags');
    Route::get('/getagencywebhook/{company_id}','ZapierController@get_agency_details');
});
// ZAPIER WEBHOOK


/** TOOLS */
Route::middleware('auth:api')->group(function () {
    Route::post('/tools/dataenrichment/upload','ToolController@dataenrichment');
    Route::get('/tools/dataenrichment/checkfile/{userid}/{filename}/{urlfile?}','ToolController@checkFileExist');
    Route::get('/tools/dataenrichment/data/{userid}/{id?}','ToolController@getDataEnrichmentList');
    Route::post('/tools/optout/upload','ToolController@optout');
    Route::post('/tools/optout-client/upload','ToolController@ClientOptout');
    Route::delete('/tools/optout/purge/{companyRootId}','ToolController@purgeOptout');
});
/** TOOLS */

Route::post('/docauth','ToolController@docauth');
Route::post('/login','AuthController@login');
Route::get('/social/login','AuthController@sociallogin');
Route::post('/register','AuthController@register');
Route::post('/forgetpass','AuthController@forgetpass');
Route::middleware('auth:api')->post('/logout','AuthController@logout');
Route::get('/checkembededcode','GeneralController@checkembededcode');
Route::get('/domainsubdomain/{domainorsub}/{hostID?}','GeneralController@getCompanyInfoDomain');
//Route::get('/chargeclient','GeneralController@chargeclient');
//Route::post('/google/auth','AuthController@googleauth');
//Route::post('/images','AuthController@login');

/** FOR KARTRA WEBHOOK */
Route::post('/webhook/kartra/{app_id?}','KartraController@webhook');
Route::post('/ipn/kartra/{app_id?}','KartraController@ipnhook');
/** FOR KARTRA WEBHOOK */

Route::middleware('auth:api')->group(function () {
//Routes used for SendGrid API integration


Route::get('/getintegration','IntegrationController@getIntegration');
Route::get('/getsendgridkey','IntegrationController@getSendgridApiKey');
Route::get('/getcompanyintegrationdetail/{company_id}/{slug}','IntegrationController@getClientIntegrationDetails');
Route::get('/sendgridcontact','SendgridController@getSendGridContact');   //Routes used for getting SendGrid Contact

Route::get('/sendmailsendgrid','SendgridController@sendMail');   //Routes used for mailing via SendGrid 
Route::get('/testmail','SendgridController@testMail');
Route::post('/addcampaign','SendgridController@addCampaign');   

Route::get('/sendgridlist/{company_id}','SendgridController@getSendGridList');   //Routes used for getting SendGrid List
Route::post('/savecampaignintegrationdetails','SendgridController@saveSendgridDeatils'); // To save the sendgrid integration settings for each campaign 
Route::get('/getcompanyintegrationlist/{company_id}','IntegrationController@getIntegrationList'); // to get all integration list for each campaign
Route::get('/getcampaignsendgriddetails/{id}/{company}','SendgridController@getSendgridDeatils');
Route::post('/saveintegration','IntegrationController@saveIntegration'); // to save sendgrid api 

/** SENDGRID SETUP FOR EACH CAMPAIGN */



// Route::post('/savecampaignsendgriddetails','SendgridController@saveSendgridDeatils');


/** SENDGRID SETUP FOR EACH CAMPAIGN */
});

//** TWO FACTOR AUTHENTICATION */
Route::post('/resendcode','TwoFactorAuthController@resend_code');
Route::post('/verifylogin','TwoFactorAuthController@verify_login');

Route::middleware('auth:api')->group(function () {
    Route::get('/setting/twofactorauth/{userId}','TwoFactorAuthController@show');
    Route::put('/setting/twofactorauth/{userId}','TwoFactorAuthController@update');
    Route::get('/setting/get-google-tfa/{userId}/{companyId}','TwoFactorAuthController@getGoogleTfa');
});

//** TWO FACTOR AUTHENTICATION */


// BigDBM Services
Route::middleware('auth:api')->group(function () {
    Route::get('/bigdbm/services/configdata/{company_root_id}', 'BigDBMController@get_config_data'); 
});
// BigDBM Services 
