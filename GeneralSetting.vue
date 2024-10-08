<template>
    <div>

        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                <h2><i class="fas fa-sliders-h"></i>&nbsp;&nbsp;General Setting</h2>
            </div>
        </div>
        <div class="pt-3 pb-3">&nbsp;</div>
        <div class="sticky-top general-setting-side-nav d-none d-lg-block" style="position: fixed;top: 160px; font-size: 12px;" :style="{left: $sidebar.isMinimized ?'250px':'50px'}">
              <ul class="nav flex-column">
                <li v-if="!this.$global.systemUser" class="nav-item">
                  <span class="nav-link" @click="scrollToSection('billing-plan')" :class="{ '--active': activeSection === 'billing-plan' }">Connect Your Account</span>
                </li>
                <li class="nav-item">
                  <span class="nav-link" @click="scrollToSection('payment-method')" :class="{ '--active': activeSection === 'payment-method' }">Default Retail Prices</span>
                </li>
             
                <li class="nav-item" v-if="!this.$global.systemUser">
                  <span class="nav-link" @click="scrollToSection('subdomain-settings')" :class="{ '--active': activeSection === 'subdomain-settings' }">Set your default subdomain</span>
                </li>
                <li class="nav-item" v-if="!this.$global.systemUser">
                  <span class="nav-link" @click="scrollToSection('white-label-domain-settings')" :class="{ '--active': activeSection === 'white-label-domain-settings' }">White Label Your Domain</span>
                </li>
                <li class="nav-item">
                  <span class="nav-link" @click="scrollToSection('color-theme')" :class="{ '--active': activeSection === 'color-theme' }">Color Theme</span>
                </li>
                <li class="nav-item">
                  <span class="nav-link" @click="scrollToSection('company-logo')" :class="{ '--active': activeSection === 'company-logo' }">Company Logo</span>
                </li>
                <li class="nav-item">
                  <span class="nav-link" @click="scrollToSection('company-product-names')" :class="{ '--active': activeSection === 'company-product-names' }">Select Your Product Names</span>
                </li>
                <li class="nav-item" v-if="!this.$global.systemUser">
                  <span class="nav-link" @click="scrollToSection('clients-default-products')" :class="{ '--active': activeSection === 'clients-default-products' }">Set Default Products for Clients</span>
                </li>
                <li class="nav-item" >
                  <span class="nav-link" @click="scrollToSection('email-settings')" :class="{ '--active': activeSection === 'email-settings' }">Email Settings</span>
                </li>
                <li class="nav-item" v-if="!this.$global.systemUser">
                  <span class="nav-link" @click="scrollToSection('email-templates')" :class="{ '--active': activeSection === 'email-templates' }">Email Templates</span>
                </li>
                <li class="nav-item">
                  <span class="nav-link" @click="scrollToSection('support-widget')" :class="{ '--active': activeSection === 'support-widget' }">Embed your support widget</span>
                </li>
              </ul>
            </div>
        <div class="row processingArea" v-if="!this.$global.systemUser">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
          
            <div class="col-sm-12 col-md-12 col-lg-6 text-center">
                <card>
                    <h4 class="pt-3 pb-3" v-if="!this.$global.systemUser">You need to setup / connect your Stripe</h4>
                    <div class="row" v-if="!this.$global.systemUser">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center" v-if="(ActionBtnConnectedAccount == 'createAccount' || ActionBtnConnectedAccount == 'createAccountLink') && userData.manual_bill == 'F'">
                                <base-button :class="statusColorConnectedAccount" :disabled="DisabledBtnConnectedAccount" size="sm" style="height:40px" id="btnCreateConnectedAccount" @click="processConnectedAccount();">
                                    <i class="fas fa-link"></i> <span v-html="txtStatusConnectedAccount">Connect Stripe Account</span>
                                </base-button>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center" v-if="(ActionBtnConnectedAccount == 'inverification' || ActionBtnConnectedAccount == 'accountConnected') && userData.manual_bill == 'F'">
                                <div class="d-flex justify-content-center">
                                    <div class="d-flex">
                                        <h4 :style="{color:statusColorConnectedAccount}" v-html="txtStatusConnectedAccount">&nbsp;
                                        </h4>
                                            <i v-if="(!txtPayoutsEnabled || !txtpaymentsEnabled) && (txtErrorRequirements.length > 0)" class="fas fa-exclamation-circle ml-2" style="color: yellow; font-size: 20px; cursor: pointer;" @click="showError()"></i>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center">
                                        <div class="d-flex align-middle mr-2">
                                            <i :class="txtpaymentsEnabled ? 'el-icon-success' : 'el-icon-error'" style="font-size: 20px; margin-right: 4px;" :style="{color: txtpaymentsEnabled ? 'green' : 'red'}"></i>
                                            <span v-if="txtpaymentsEnabled"> Payouts Enabled</span>
                                            <span v-else> Payouts Disabled</span>
                                        </div>
                                        <div class="d-flex align-middle">
                                            <i :class="txtpaymentsEnabled ? 'el-icon-success' : 'el-icon-error'" style="font-size: 20px; margin-right: 4px;" :style="{color: txtpaymentsEnabled ? 'green' : 'red'}"></i>
                                            <span v-if="txtpaymentsEnabled"> Payments Enabled</span>
                                            <span v-else> Payments Disabled</span>
                                        </div>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center" v-if="userData.manual_bill == 'T'">
                                <h4>Stripe Connection Disabled.</h4>
                            </div>
                    </div>

                    <div class="row" v-if="(!this.$global.systemUser && ActionBtnConnectedAccount == 'accountConnected' && defaultPaymentMethod == 'stripe' && plannextbill != 'free') && userData.manual_bill == 'F'" >
                        <div class="pt-3 pb-3" style="height:50px">&nbsp;</div>

                        <div class="col-sm-12 col-md-12 col-lg-12 text-center mt-4">
                            <h4>Select your plan and click save: </h4>
                        </div>
                        
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left" :class="{'disabled-area':this.radios.packageID == this.radios.freeplan}">
                            <base-radio :name="radios.nonwhitelabelling.monthly" v-model="radios.packageID" :disabled="radios.nonwhitelabelling.monthly_disabled">${{radios.nonwhitelabelling.monthlyprice}} / month - Standard Account</base-radio>
                            <base-radio :name="radios.nonwhitelabelling.yearly" v-model="radios.packageID" :disabled="radios.nonwhitelabelling.yearly_disabled">${{radios.nonwhitelabelling.yearlyprice}} / year - Standard Account</base-radio>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left" :class="{'disabled-area':this.radios.packageID == this.radios.freeplan}">
                            <base-radio :name="radios.whitelabeling.monthly" v-model="radios.packageID" :disabled="radios.whitelabeling.monthly_disabled">${{radios.whitelabeling.monthlyprice}} / month - White Labeled Account</base-radio>
                            <base-radio :name="radios.whitelabeling.yearly" v-model="radios.packageID" :disabled="radios.whitelabeling.yearly_disabled">${{radios.whitelabeling.yearlyprice}} / year - White Labeled Account</base-radio>
                        </div>
                    </div>

                    <div class="row" v-if="(!this.$global.systemUser && ActionBtnConnectedAccount == 'accountConnected' && defaultPaymentMethod != 'stripe') && userData.manual_bill == 'F'">
                        <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                            <h4>The subscription account of yours is connected to</h4>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                            <img src="https://d2uolguxr56s4e.cloudfront.net/img/shared/kartra_logo_color.svg" style="max-width:180px"/>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 text-center pt-4">
                                <p>&ldquo;{{packageName}}&rdquo;</p>
                        </div>
                    </div>

                    <div class="row" v-if="(defaultPaymentMethod == 'stripe' && plannextbill == 'free') && userData.manual_bill == 'F'">
                        <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                            <img src="https://emmspaces.nyc3.cdn.digitaloceanspaces.com/systems/stripelogo.png" style="max-width:180px"/>
                        </div>
                    </div>

                    <div class="pt-3 pb-3">&nbsp;</div>

                    <div class="row" v-if="plannextbill != '' && userData.manual_bill == 'F' && plannextbill != 'free'">
                         <div class="col-sm-12 col-md-12 col-lg-12 text-left"><small>Next Billing is on : <strong>{{plannextbill}}</strong></small></div>
                    </div>

                    <div class="row" v-if="plannextbill != '' && userData.manual_bill == 'F' && plannextbill == 'free' && notPassSixtyDays">
                        <div class="col-sm-12 col-md-12 col-lg-12 text-center">For questions or cancelations, contact your account representative.</div>
                    </div>
                   
                     <div class="pt-3 pb-3">&nbsp;</div>

                    <template slot="footer" v-if="ActionBtnConnectedAccount == 'accountConnected' && userData.manual_bill == 'F'">
                        <div class="row justify-content-end" style="gap: 8px; padding-inline: 15px;">
                            <div v-if="this.$global.menuLeadsPeek_update && false">
                                <base-button type="info" round icon @click="show_helpguide('whitelabelling')">
                                <i class="fas fa-question"></i>
                                </base-button>
                                
                                
                            </div>
                            <div :class="{'disabled-area':this.radios.packageID == this.radios.freeplan && false}">
                                <el-tooltip
                                    content="Cancel Subscription"
                                    effect="light"
                                    :open-delay="300"
                                    placement="top" 
                                    v-if="this.$global.menuLeadsPeek_update && defaultPaymentMethod == 'stripe' && this.$global.rootcomp"  
                                >
                                    <base-button type="danger" round icon @click="cancel_subscription()">
                                    <i class="fas fa-strikethrough"></i>
                                    </base-button>
                                </el-tooltip>
                            </div>
                            <div v-if="this.$global.menuLeadsPeek_update && defaultPaymentMethod == 'stripe'" :class="{'disabled-area':this.radios.packageID == this.radios.freeplan && false}">
                                <el-tooltip
                                    content="Reset Account Connection"
                                    effect="light"
                                    :open-delay="300"
                                    placement="top"   
                                >
                                    <base-button type="danger" round icon @click="reset_stripeconnection()">
                                    <i class="fas fa-unlink"></i>
                                    </base-button>
                                </el-tooltip>
                            </div>

                            <div v-if="this.$global.menuLeadsPeek_update && defaultPaymentMethod == 'stripe'" :class="{'disabled-area':this.radios.packageID == this.radios.freeplan}">
                                <base-button class="btn-green" round icon  @click="save_plan_package()" >
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>

                    <div class="col-sm-12 col-md-12 col-lg-12 text-right" ref="btnglobalreset" style="display:none" v-if="(this.$global.menuLeadsPeek_update  && defaultPaymentMethod == 'stripe') && (ActionBtnConnectedAccount != 'accountConnected' && ActionBtnConnectedAccount != 'createAccount') && userData.manual_bill == 'F'" :class="{'disabled-area':this.radios.packageID == this.radios.freeplan && false}">
                        <el-tooltip
                            content="Reset Account Connection"
                            effect="light"
                            :open-delay="300"
                            placement="top"   
                        >
                            <base-button type="danger" round icon @click="reset_stripeconnection()">
                            <i class="fas fa-unlink"></i>
                            </base-button>
                        </el-tooltip>
                    </div>
                </card>
            </div>
        </div>

         <div class="row processingArea" v-if="!this.$global.systemUser">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="billing-plan" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area': (this.radios.lastpackageID == '' && !this.$global.systemUser)}">
                <card>
                    <template slot="header">
                        <div>
                            <h4 class="d-inline">Connect Your Account <i @click="openHelpModal(0)" class="fa fa-question-circle d-none" style="cursor: pointer; margin-left: 12px;"></i></h4> 
                        </div>
                        <h5 class="card-category">Connect your Google account to enable spreadsheet reporting ability.</h5>
                        <h5 class="card-category">Please make sure to check all permissions requested when you connect your Google account</h5>
                    </template>

                    <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                                <base-button v-if="GoogleConnectFalse" size="sm" style="height:40px" @click="connect_googleSpreadSheet()" :disabled="!this.$global.menuLeadsPeek_update">
                                    <i class="fas fa-link"></i> Connect Google Sheet
                                </base-button>
                                <base-button v-if="GoogleConnectTrue" size="sm" style="height:40px" @click="disconnect_googleSpreadSheet()" :disabled="!this.$global.menuLeadsPeek_update">
                                    <i class="fas fa-unlink"></i> Disconnect Google Sheet
                                </base-button>
                            </div>
                    </div>
                    <div class="row pt-4">
                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2 text-left">
                            <h5 class="card-category">* {{ this.$global.companyrootname }} Uses Google OAuth To Securely Access Your Google Sheets And Google Drive. By Authorizing Our App, You Allow Us To:<br/><strong>Create, Update, Write To Spreadsheets And Manage Permissions</strong></h5>
                            <h5 class="card-category">{{ this.$global.companyrootname }}'s Use And Transfer Of Any Information Received From Google APIs Will Comply With The <a href="https://developers.google.com/terms/api-services-user-data-policy#additional_requirements_for_specific_api_scopes" target="_blank">Google API Services User Data Policy</a>, Including the Limited Use Requirements.</h5>
                            <h5 class="card-category">Your Privacy And Security Are Important To Us. We Use Secure OAuth 2.0 To Access Your Data And We Do Not Store Your Google Account Credentials. You Can Revoke Our Access At Any Time From Your <a href="http://myaccount.google.com/connections" target="_blank">Google Account Settings</a> Or Click The Button Above To Disconnect.</h5>
                            <h5 class="card-category">By Using Our Services, You Agree To Let Us Access Your Google Sheets And Google Drive. You Can Revoke This Access At Any Time From Your Google Account Settings.<br/>
                                If You Have Any Questions, Please Contact Us At <a :mailto="this.$global.userrootemail" style="cursor: pointer;text-decoration: underline;">{{ this.$global.userrootemail }}</a></h5>
                        </div>
                    </div>
                </card>
            </div>
         </div>

        <div class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="payment-method" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':(!this.radios.packageID != '') && !this.$global.systemUser}" >
                <card>
                    <template slot="header">
                        <h4>Default Retail Prices</h4>
                        <h5 class="card-category" style="text-transform:none;">Set your default retail pricing and billing frequency for all new campaigns. <br>You can adjust individual campaign pricing and billing frequency as needed during campaign setup and later in the individual campaign's settings.</h5>
                        <h5 class="pt-4" v-if="!this.$global.systemUser">Your Wholesale cost per lead is <strong>${{ formatPrice(rootSiteIDCostPerLead) }}</strong> for {{ this.$global.globalModulNameLink.local.name }} <span v-if="this.$global.globalModulNameLink.enhance.name !== null && this.$global.globalModulNameLink.enhance.url !== null">,</span><span v-if="this.$global.globalModulNameLink.enhance.name === null && this.$global.globalModulNameLink.enhance.url === null">and</span> <strong>${{ formatPrice(rootSearchIDCostPerLead) }}</strong> for {{ this.$global.globalModulNameLink.locator.name }}<span v-if="this.$global.globalModulNameLink.enhance.name !== null && this.$global.globalModulNameLink.enhance.url !== null"> and 50% of retail price, minimum of <strong>${{ formatPrice(rootEnhanceIDCostPerLead) }}</strong> for {{ this.$global.globalModulNameLink.enhance.name }}</span>.</h5>
                    </template>
                    <div style="border:solid 1px;opacity:0.5">&nbsp;</div>
                  
                    <div class="d-flex align-items-center my-4">
                        <div @click="activePriceSettingTab = 1" class="pricing-setting-item-toggle" :class="{'--active': activePriceSettingTab === 1}">
                            <h5 v-html="this.$global.globalModulNameLink.local.name" style="text-transform:uppercase;font-weight:bold">:&nbsp;</h5>
                        </div> 
                        <div @click="activePriceSettingTab = 2" class="pricing-setting-item-toggle" :class="{'--active': activePriceSettingTab === 2}">
                            <h5 v-html="this.$global.globalModulNameLink.locator.name" style="text-transform:uppercase;font-weight:bold">&nbsp;</h5>
                        </div>
                        <div v-if="this.$global.globalModulNameLink.enhance.name !== null && this.$global.globalModulNameLink.enhance.url != null" @click="activePriceSettingTab = 3" class="pricing-setting-item-toggle" :class="{'--active': activePriceSettingTab === 3}">
                            <h5 v-html="this.$global.globalModulNameLink.enhance.name" style="text-transform:uppercase;font-weight:bold">&nbsp;</h5>
                        </div>
                    </div>

                    <div v-show="activePriceSettingTab === 1">
                        <div class="leadspeek-pricing-setting-form-wrapper" v-if="selectsAppModule.AppModuleSelect == 'LeadsPeek'">
                            <div class="flex-grow-1 price-setting-form-item">
                                <base-input
                                    
                                    label="One time setup fee"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="LeadspeekPlatformFee"    
                                    style="width:120px"  
                                    @keyup="set_fee('local','LeadspeekPlatformFee');"    
                                >
                                </base-input>
                            </div>
                            <div  class="flex-grow-1 price-setting-form-item">
                                <base-input
                                    :label="`${txtLeadService.charAt(0).toUpperCase() + txtLeadService.slice(1)} campaign fee`"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"  
                                    v-model="LeadspeekMinCostMonth"    
                                    style="width:120px"    
                                    @keyup="set_fee('local','LeadspeekMinCostMonth');"      
                                >
                                </base-input>
                            </div>
                            <div class="flex-grow-1 price-setting-form-item">
                                <base-input
                                v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time'"
                                            label="Cost per lead"
                                            type="text"
                                            placeholder="0"
                                            addon-left-icon="fas fa-dollar-sign"
                                            v-model="LeadspeekCostperlead"    
                                            style="width:100px"      
                                            @keyup="set_fee('local','LeadspeekCostperlead');"   
                                        >
                                </base-input>
                            </div>
                        </div>
                    </div>

<!-- lead locator settings -->
                    <div v-show="activePriceSettingTab === 2">
                        <div class="leadspeek-pricing-setting-form-wrapper" v-if="selectsAppModule.AppModuleSelect == 'LeadsPeek'">
                            <div class="flex-grow-1 price-setting-form-item">
                                <base-input
                                    label="One time setup fee"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="LocatorPlatformFee"    
                                    style="width:120px"      
                                    @keyup="set_fee('locator','LocatorPlatformFee');"   
                                >
                                </base-input>
                            </div>

                            <div class="flex-grow-1 price-setting-form-item">
                                <base-input
                                    :label="`${txtLeadService.charAt(0).toUpperCase() + txtLeadService.slice(1)} campaign fee`"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="LocatorMinCostMonth"    
                                    style="width:120px"    
                                    @keyup="set_fee('locator','LocatorMinCostMonth');"   
                                >
                                </base-input>
                            </div>
                            <div class="flex-grow-1 price-setting-form-item" >
                                <base-input
                                    label="Cost per lead"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="lead_FirstName_LastName_MailingAddress_Phone"    
                                    style="width:120px"      
                                    @keyup="set_fee('locatorlead','FirstName_LastName_MailingAddress_Phone');"   
                                >
                                </base-input>
                        </div>
                            <div class="flex-grow-1 price-setting-form-item" v-if="false">
                                <base-input
                                :label="`cost per lead ${txtLeadOver ? txtLeadOver : 'from the monthly charge'}?`"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="LocatorCostperlead"    
                                    style="width:100px"      
                                    @keyup="set_fee('locator','LocatorCostperlead');"    
                                >
                                </base-input>
                            </div>
                        </div>
                    </div>

                    <div v-if="this.$global.globalModulNameLink.enhance.name !== null && this.$global.globalModulNameLink.enhance.url != null" v-show="activePriceSettingTab === 3">
                        <div class="leadspeek-pricing-setting-form-wrapper" v-if="selectsAppModule.AppModuleSelect == 'LeadsPeek'">
                            <div class="flex-grow-1 price-setting-form-item">
                                <base-input
                                    label="One time setup fee"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="EnhancePlatformFee"    
                                    style="width:120px"      
                                    @keyup="set_fee('enhance','EnhancePlatformFee');"   
                                >
                                </base-input>
                            </div>
                            <div class="flex-grow-1 price-setting-form-item">
                                <base-input
                                    :label="`${txtLeadService.charAt(0).toUpperCase() + txtLeadService.slice(1)} campaign fee`"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="EnhanceMinCostMonth"    
                                    style="width:120px"    
                                    @keyup="set_fee('enhance','EnhanceMinCostMonth');"   
                                >
                                </base-input>
                            </div>
                            <!-- <div class="flex-grow-1 price-setting-form-item" >
                                <base-input
                                    label="Cost per lead"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="lead_FirstName_LastName_MailingAddress_Phone"    
                                    style="width:120px"      
                                    @keyup="set_fee('locatorlead','FirstName_LastName_MailingAddress_Phone');"   
                                >
                                </base-input>
                            </div> -->
                            <div class="flex-grow-1 price-setting-form-item">
                                <!-- :label="`cost per lead ${txtLeadOver ? txtLeadOver : 'from the monthly charge'}?`" -->
                                <base-input
                                    label="Cost per lead"
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="EnhanceCostperlead"    
                                    style="width:100px"      
                                    @keyup="set_fee('enhance','EnhanceCostperlead');"    
                                >
                                </base-input>
                            </div>
                        </div>
                    </div>

                    <div class="pricing-duration-dropdown-wrapper mt-4">
                            <!-- <div  class="col-sm-12 col-md-12 col-lg-12 text-center">
                                <h5>Please choose the default billing cycle for your agency.</h5>
                            </div> -->
                            <label>Billing Frequency</label>
                            <el-select
                                class="select-primary"
                                size="small"
                                placeholder="Select Modules"
                                v-model="selectsPaymentTerm.PaymentTermSelect"
                                @change="paymentTermChange()"
                                >
                                <el-option
                                    v-for="option in selectsPaymentTerm.PaymentTerm"
                                    class="select-primary"
                                    :value="option.value"
                                    :label="option.label"
                                    :key="option.label"
                                >
                                </el-option>
                            </el-select>
                    </div>
                    <div style="border:solid 1px;opacity:0.5;margin-top:24px;">&nbsp;</div>
                    <!-- temp remove -->
                     <!-- <div class="row pt-3">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                                <h5>Please choose your default price settings for {{ this.$global.globalModulNameLink.locator.name}}</h5>
                            </div>
                     </div>
                  
                     <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-12" v-if="false">
                            <h5 class="d-inline pr-3" style="float:left;line-height:40px">
                            &#x2022;&nbsp;Emails and Names
                            </h5>
                            <div class="d-inline" style="float:left;">
                                <base-input
                                    label=""
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="lead_FirstName_LastName"    
                                    style="width:120px"      
                                    @keyup="set_fee('locatorlead','FirstName_LastName');"   
                                >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-12 col-lg-12" v-if="false">
                            <h5 class="d-inline pr-3" style="float:left;line-height:40px">
                            &#x2022;&nbsp;Emails, Names, and Mailing Addresses
                            </h5>
                            <div class="d-inline" style="float:left;">
                                <base-input
                                    label=""
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign" 
                                    v-model="lead_FirstName_LastName_MailingAddress"    
                                    style="width:120px"      
                                    @keyup="set_fee('locatorlead','FirstName_LastName_MailingAddress');"   
                                >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-12 col-lg-12" >
                            <h5 class="d-inline pr-3" style="float:left;line-height:40px">
                            Default Price per lead
                            </h5>
                            <div class="d-inline" style="float:left;">
                                <base-input
                                    label=""
                                    type="text"
                                    placeholder="0"
                                    addon-left-icon="fas fa-dollar-sign"
                                    v-model="lead_FirstName_LastName_MailingAddress_Phone"    
                                    style="width:120px"      
                                    @keyup="set_fee('locatorlead','FirstName_LastName_MailingAddress_Phone');"   
                                >
                                </base-input>
                            </div>
                        </div>

                     </div> -->
  <!-- temp remove -->

                    <template slot="footer" v-if="ActionBtnConnectedAccount == 'accountConnected' || this.$global.systemUser || userData.manual_bill == 'T'">
                        <div class="row pull-right">
                            <div class="col-sm-6 col-md-6 col-lg-6 text-right" v-if="this.$global.menuLeadsPeek_update && false">
                                <base-button type="info" round icon @click="show_helpguide('defaultprice')">
                                <i class="fas fa-question"></i>
                                </base-button>
                                
                                
                            </div>
                            <div class="col-sm-6 col-md-6 col-lg-6 text-left" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon  @click="save_default_price()" >
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>

                </card>
            </div>
         </div>

         <div class="row processingArea" v-if="!this.$global.systemUser">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="subdomain-settings"class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':(!this.radios.packageID != '')}" v-if="!domainSetupCompleted || !Whitelabellingstatus">
                <card>
                    <template slot="header">
                        <h4>Set your default subdomain</h4>
                        <h5 class="card-category" style="text-transform:none;">This is your default subdomain, you can change this to your own domain or subdomain if you choose the white labeling plan by entering it below under "White Labeling Options".</h5>
                    </template>
                   
                    <div class="row pt-3">
                            <div class="col-sm-4 col-md-4 col-lg-4 pr-0 mr-0">
                            <base-input
                                label=""
                                type="text"
                                placeholder="yoursubdomain"
                                v-model="DownlineSubDomain"
                                inline
                                >
                            </base-input>
                            
                          </div>
                          <div class="col-sm-5 col-md-5 col-lg-5 ml-0 pl-2 text-left" style="padding-top:10px;">
                            .{{$global.companyrootdomain.toLowerCase()}}
                          </div>
                            <div class="col-sm-3 col-md-3 col-lg-3">
                               &nbsp;
                            </div>
                    </div>

                    <template slot="footer" v-if="ActionBtnConnectedAccount == 'accountConnected' || userData.manual_bill == 'T'">
                        <div class="row pull-right">
                            <div class="col-sm-6 col-md-6 col-lg-6 text-right" v-if="this.$global.menuLeadsPeek_update && false">
                                <base-button type="info" round icon @click="show_helpguide('setdefaultsubdomain')">
                                <i class="fas fa-question"></i>
                                </base-button>
                                
                                
                            </div>
                            <div class="col-sm-6 col-md-6 col-lg-6 text-left" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon  @click="save_default_subdomain()" >
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>

                </card>
            </div>
        </div>

        <div class="row processingArea" v-if="!this.$global.systemUser">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="white-label-domain-settings" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':(is_whitelabeling == 'F' || !this.radios.packageID != '')}">
                <card>
                    <template slot="header">
                        <h4>White Label Your Domain<i @click="openHelpModal(2)" class="fa fa-question-circle d-none" style="cursor: pointer; margin-left: 12px;"></i></h4>
                        <h5 class="card-category">Use your own domain to White Label the system and customize the service product names.</h5>
                    </template>
                    <div class="pt-3 pb-3">&nbsp;</div>
                    <ul class="text-left list-unstyled d-flex flex-column" style="padding-left: 0px;gap:8px ">
                        <li><span style="font-weight:700;">Step 1:</span> Point your A record to our IP address using the settings found at this <a href="javascript:void(0);" @click="modals.whitelabelingguide = true">link</a>.</li>

                        <li><span style="font-weight:700;">Step 2:</span> Verify that your A record is correctly pointed by<a href=" https://dnschecker.org/#A/" target="_blank"> clicking here</a>, Putting in your personal domain, and verifying that it has been propagated to our IP address. This may take up to 12 hours to fully propagate.</li>

                       <li><span style="font-weight:700;">Step 3:</span> After confirming that your DNS settings are correct and fully propagated, enter your personal URL here and be sure to click Save.</li>
                    </ul>
                     <div class="row">
                        <div class="col-sm-10 col-md-10 col-lg-10 form-group">
                            <label class="col-form-label pull-left pr-2">Domain / subdomain Name:</label>
                            <base-input
                                type="text"
                                placeholder="yourdomain.com"
                                addon-left-icon="fas fa-globe-americas"
                                v-model="DownlineDomain"
                                id="dwdomain"
                                >
                            </base-input>

                            <!-- <small class="pull-left text-left">*You need to set your domain host to our server, <a href="javascript:void(0);" @click="modals.whitelabelingguide = true">click here for more information</a></small> -->
                            <div class="pull-left d-line" v-if="DownlineDomain != '' && DownlineDomainStatus != ''"><small>* Domain Status : <span v-html="DownlineDomainStatus"></span></small></div>

                        </div>
                        <div class="col-sm-2 col-md-2 col-lg-2">&nbsp;</div>

                        <div class="col-sm-12 col-md-12 col-lg-12">
                            <base-checkbox v-model="chkagreewl" :class="{'has-danger': agreewhitelabelling}" class="pull-left" v-if="false">
                                I agree with the white labelling term and condition and enabled this feature
                            </base-checkbox>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 pt-5" v-if="false">
                            <small><em>* Full White Labeling is an additional $100 a month.<br/>All Options Below will be customizable with full white labeling</em></small><br>
                        </div>
                     </div>
                     
                     <template slot="footer">
                        <div class="row pull-right">
                            <div class="col-sm-6 col-md-6 col-lg-6 text-right" v-if="this.$global.menuLeadsPeek_update && false">
                                <base-button type="info" round icon @click="show_helpguide('whitelabelling')">
                                <i class="fas fa-question"></i>
                                </base-button>
                                
                                
                            </div>
                            <div class="col-sm-6 col-md-6 col-lg-6 text-left" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon @click="save_general_whitelabelling()">
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>

                </card>
            </div>

        </div>
 
        <div class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="color-theme" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':((is_whitelabeling == 'F' || !this.radios.packageID != '') && !this.$global.systemUser)}">
                <card id="themecolor" style="width:100%">
                    <template slot="header">
                        <h4>Select Your Color Palette</h4>
                    </template>

                    
                    <div class="row">
                        <label class="col-sm-4 col-md-4 col-lg-4 col-form-label">Sidebar Background Color:</label>
                            <div class="col-sm-4 col-md-4 col-lg-4 d-inline-flex">
                                <input class="form-control" id="sidebarcolor" type="text" value="" /><a class="pl-2 pt-2" href="javascript:void(0);" @click="reverthistory('sidebar');"><i class="fas fa-history"></i></a>
                            </div>
                            <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>    
                        
                    </div>
                    <div v-if="false" class="row">
                        <label class="col-sm-4 col-md-4 col-lg-4 col-form-label">Template Background Color:</label>
                            <div class="col-sm-4 col-md-4 col-lg-4 d-inline-flex pt-2">
                                <input class="form-control" id="backgroundtemplatecolor" type="text" value="" /><a class="pl-2 pt-2" href="javascript:void(0);" @click="reverthistory('template');"><i class="fas fa-history"></i></a>
                            </div>
                            <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>    
                        
                    </div>
                    <div v-if="false" class="row">
                        <label class="col-sm-4 col-md-4 col-lg-4 col-form-label">Box Background Color:</label>
                            <div class="col-sm-4 col-md-4 col-lg-4 d-inline-flex pt-2">
                                <input class="form-control" id="boxcolor" type="text" value="" /><a class="pl-2 pt-2" href="javascript:void(0);" @click="reverthistory('box');"><i class="fas fa-history"></i></a>
                            </div>
                            <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>    
                        
                    </div>
                    <div class="row">
                        <label class="col-sm-4 col-md-4 col-lg-4 col-form-label">Text Color:</label>
                            <div class="col-sm-4 col-md-4 col-lg-4 d-inline-flex pt-2">
                                <input class="form-control" id="textcolor" type="text" value="" /><a class="pl-2 pt-2" href="javascript:void(0);" @click="reverthistory('text');"><i class="fas fa-history"></i></a>
                            </div>
                            <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>    
                        
                    </div>
                    <div v-if="false" class="row">
                        <label class="col-sm-4 col-md-4 col-lg-4 col-form-label">Link Color:</label>
                            <div class="col-sm-4 col-md-4 col-lg-4 d-inline-flex pt-2">
                                <input class="form-control" id="linkcolor" type="text" value="" /><a class="pl-2 pt-2" href="javascript:void(0);" @click="reverthistory('link');"><i class="fas fa-history"></i></a>
                            </div>
                            <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>    
                        
                    </div>
                    <template slot="footer">
                        <div class="row pull-right">
                            <div class="col-sm-6 col-md-6 col-lg-6 text-right" v-if="this.$global.menuLeadsPeek_update && false">
                                <base-button type="info" round icon @click="show_helpguide('colorthemeinfo')">
                                <i class="fas fa-question"></i>
                                </base-button>
                                
                                
                            </div>
                            <div class="col-sm-6 col-md-6 col-lg-6 text-left" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon @click="save_general_colortheme()">
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>
                   <!--<div class="pull-left p-2">
                        <div style="width:120px;border:4px green solid;">
                                <div class="btn-primary" style="height:20px;width:100%">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#FFF">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#1e1e2f">&nbsp;</div>
                        </div>
                   </div>

                   <div class="pull-left p-2">
                        <div style="width:120px;border:2px #FFF solid;">
                                <div style="height:20px;width:100%;background-color:#344675">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#FFF">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#1e1e2f">&nbsp;</div>
                        </div>
                   </div>

                    <div class="pull-left p-2">
                        <div style="width:120px;border:2px #FFF solid;">
                                <div style="height:20px;width:100%;background-color:#ff8d72">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#344675">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#1e1e2f">&nbsp;</div>
                        </div>
                   </div>

                   <div class="pull-left p-2">
                        <div style="width:120px;border:2px #FFF solid;">
                                <div style="height:20px;width:100%;background-color:#f4f5f7">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#42b883">&nbsp;</div>
                                <div style="height:20px;width:100%;background-color:#942434">&nbsp;</div>
                        </div>
                   </div>-->
                           
                </card>
            </div>

            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>

        <div class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':((is_whitelabeling == 'F' || !this.radios.packageID != '') && !this.$global.systemUser)}">
                <card id="themecolor">
                    <template slot="header">
                        <h4>Select Your Preferred Font</h4>
                    </template>

                    <div style="display:inline-block;margin:0 auto">
                        <div class="row">
                                <div class="pull-left p-2" style="cursor:pointer">
                                    <div class="fontoption" id="Poppins" style="width:120px;border:4px #FFF solid;" @click="changefont('Poppins',$event);">
                                            <div style="height:60px;width:100%;background-color:white;color:black;padding:14px;font-family:'sans-serif';font-size:0.95em;">Sans Serif Font</div>
                                    </div>
                                </div>
                                
                                <div class="pull-left p-2" style="cursor:pointer">
                                        <div class="fontoption" id="nucleo" style="width:120px;border:4px #FFF solid;" @click="changefont('nucleo',$event);">
                                                <div style="height:60px;width:100%;background-color:white;color:black;padding:14px;font-family:'nucleo';font-size:0.95em;">Nucleo Font</div>
                                        </div>
                                </div>

                                    <div class="pull-left p-2" style="cursor:pointer">
                                        <div class="fontoption" id="Montserrat" style="width:120px;border:4px #FFF solid;" @click="changefont('Montserrat',$event);">
                                                <div style="height:60px;width:100%;background-color:white;color:black;padding:14px;font-family:'Montserrat';font-size:0.95em;">Montserrat Font</div>
                                        </div>
                                </div>

                                <div class="pull-left p-2" style="cursor:pointer">
                                        <div class="fontoption" id="Helvetica Neue" style="width:120px;border:4px #FFF solid;" @click="changefont('Helvetica Neue',$event);">
                                            <div style="height:60px;width:100%;background-color:white;color:black;padding:10px;font-family:'Helvetica Neue';font-size:0.95em;">Helvetica Neue Font</div>
                                        </div>
                                </div>
                        </div>

                        <div class="row">
                            
                            <div class="pull-left p-2" style="cursor:pointer">
                                    <div class="fontoption" id="Arial" style="width:120px;border:4px  #FFF solid;" @click="changefont('Arial',$event);">
                                            <div style="height:60px;width:100%;background-color:white;color:black;padding:14px;font-family:'Arial';font-size:0.95em;">Arial Font</div>
                                    </div>
                            </div>

                            <div class="pull-left p-2" style="cursor:pointer">
                                    <div class="fontoption" id="Courier New" style="width:120px;border:4px #FFF solid;" @click="changefont('Courier New',$event);">
                                            <div style="height:60px;width:100%;background-color:white;color:black;padding:10px;font-family:'Courier New';font-size:0.95em;">Courier New Font</div>
                                    </div>
                            </div>

                                <div class="pull-left p-2" style="cursor:pointer">
                                    <div class="fontoption" id="monospace" style="width:120px;border:4px #FFF solid;" @click="changefont('monospace',$event);">
                                            <div style="height:60px;width:100%;background-color:white;color:black;padding:14px;font-family:'monospace';font-size:0.95em;">Monospace Font</div>
                                    </div>
                            </div>

                            <div class="pull-left p-2" style="cursor:pointer">
                                    <div class="fontoption" id="courier" style="width:120px;border:4px #FFF solid;" @click="changefont('courier',$event);">
                                        <div style="height:60px;width:100%;background-color:white;color:black;padding:10px;font-family:'courier';font-size:0.95em;">Courier Font</div>
                                    </div>
                            </div>
                        </div>
                    </div>

                   <template slot="footer">
                        <div class="row pull-right">
                            <div class="col-sm-6 col-md-6 col-lg-6 text-right" v-if="this.$global.menuLeadsPeek_update && false">
                                <base-button type="info" round icon @click="show_helpguide('fontthemeinfo')">
                                <i class="fas fa-question"></i>
                                </base-button>
                                
                                
                            </div>
                            <div class="col-sm-6 col-md-6 col-lg-6 text-left" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon @click="save_general_fontheme()">
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>
                           
                </card>
            </div>

            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>

        <div class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
            <div id="company-logo" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':((is_whitelabeling == 'F' || !this.radios.packageID != '') && !this.$global.systemUser)}">
                <card id="themecolor">
                    <template slot="header">
                        <h4>Customize Your Logo</h4>
                        <br>Supported file type jpeg,jpg,png,gif. Max file size is 1080kb <br>recommended dimensions 120x120</span>
                    </template>

                    <div class="row mt-2">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-center" style="padding-block: 8px;">
                            <div>
                                <label>Login / Register Logo</label>
                            </div>
                            <div style="height:120px;display: flex;align-items: center;justify-content: center">
                                <img :src="logo.loginAndRegister" alt="logo login and register" style="max-width: 100%;max-height: 100%;" />
                            </div>
                            <div class="pt-2" id="progressmsgshow3" style="display:none">
                                <div class="progress mt-3" style="height: 5px">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; height: 100%">0%</div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-12 pt-2" id="progressmsg">
                                    <label style="color:#942434">* Please wait while your image is being uploaded. This may take a few minutes.</label>
                                </div>
                            </div>
                            <div class="pt-2">
                                <button id="browseFileLogoLoginAndRegister" ref="browseFileLogoLoginAndRegister" class="btn btn-simple btn-file">Update Logo</button>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-center" style="padding-block: 8px;">
                            <div>
                                <label>Sidebar Menu Logo</label>
                            </div>
                            <div style="height:120px;display: flex;align-items: center;justify-content: center">
                                <img :src="logo.sidebar" alt="logo sidebar" style="max-width: 100%;max-height: 100%;" />
                            </div>
                            <div class="pt-2" id="progressmsgshow4" style="display:none">
                                <div class="progress mt-3" style="height: 5px">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; height: 100%">0%</div>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-12 pt-2" id="progressmsg">
                                    <label style="color:#942434">* Please wait while your image uploads. (It might take a couple of minutes.)</label>
                                </div>
                            </div>
                            <div class="pt-2">
                                <button  id="browseFileLogoSidebar" ref="browseFileLogoSidebar" class="btn btn-simple btn-file">Update Logo</button>
                            </div>
                        </div>
                    </div>
                </card>
            </div>
        </div>

        <div class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':((is_whitelabeling == 'F' || !this.radios.packageID != '') && !this.$global.systemUser)}">
                <card id="themecolor">
                    <template slot="header">
                        <h4>Customize Your Images</h4>
                        <br>Supported file type jpeg,jpg,png,gif. Max file size is 1080kb <br>recommended dimensions 460x720</span>
                    </template>
                   
                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-center" style="padding-block: 8px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label>Login Image</label>
                                <img :src="images.login" alt="image login" style="max-width: 100%; max-height: 100%; margin-top: 4px;" />
                            </div>
                            <div class="text-center">
                                <div class="pt-2" id="progressmsgshow" style="display:none">
                                    <div class="progress mt-3" style="height: 5px">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; height: 100%">0%</div>
                                    </div>
                                    <div class="col-sm-12 col-md-12 col-lg-12 pt-2" id="progressmsg">
                                        <label style="color:#942434">* Please wait while your image uploads. (It might take a couple of minutes.)</label>
                                    </div>
                                </div>
                                <div class="pt-2">
                                    <button id="browseFileLogin" ref="browseFileLogin" class="btn btn-simple btn-file">Update Image</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-center" v-if="!this.$global.systemUser" style="padding-block: 8px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label>Register Image</label>
                                <img :src="images.register" alt="image register" style="max-width: 100%; max-height: 100%; margin-top: 4px;" />
                            </div>
                            <div class="text-center">
                                <div class="pt-2" id="progressmsgshow1" style="display:none">
                                    <div class="progress mt-3" style="height: 5px">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; height: 100%">0%</div>
                                    </div>
                                    <div class="col-sm-12 col-md-12 col-lg-12 pt-2" id="progressmsg">
                                        <label style="color:#942434">* Please wait while your image uploads. (It might take a couple of minutes.)</label>
                                    </div>
                                </div>
                                <div class="pt-2">
                                    <button id="browseFileRegister" ref="browseFileRegister" class="btn btn-simple btn-file">Update Image</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-center" v-if="this.$global.systemUser" style="padding-block: 8px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <label>Agency Register Image</label>
                                <img :src="images.agency" alt="image agency" style="max-width: 100%; max-height: 100%; margin-top: 4px;" />
                            </div>
                            <div class="text-center">
                                <div class="pt-2" id="progressmsgshow2" style="display:none">
                                    <div class="progress mt-3" style="height: 5px">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%; height: 100%">0%</div>
                                    </div>
                                    <div class="col-sm-12 col-md-12 col-lg-12 pt-2" id="progressmsg">
                                        <label style="color:#942434">* Please wait while your image uploads. (It might take a couple of minutes.)</label>
                                    </div>
                                </div>
                                <div class="pt-2">
                                    <button id="browseFileAgency" ref="browseFileAgency" class="btn btn-simple btn-file">Update Image</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </card>
            </div>

            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>

        <div class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="company-product-names" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':((is_whitelabeling == 'F' || !this.radios.packageID != '') && !this.$global.systemUser)}">
                <card>
                    <template slot="header">
                        <h4>Select Your Product Names</h4>
                        <h5 class="card-category">Rename Your Product Offerings</h5>
                    </template>
                    <div class="pt-3 pb-3">&nbsp;</div>

                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                :label= "this.$global.globalModulNameLink.local.name + ' Module Name:'"
                                placeholder="Enter New Module Name"
                                v-model="leadsLocalName"
                                v-on:keyup="keyup_modulename('leadname','local');"
                            >
                            </base-input>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                :label= "this.$global.globalModulNameLink.local.name + ' Module URL:'"
                                placeholder="Enter URL Path ex. siteid"
                                v-model="leadsLocalUrl"
                                v-on:keyup="keyup_modulename('leadurl','local');"
                            >
                            </base-input>
                        </div>
                    </div>
                    <div class="pt-3 pb-3">&nbsp;</div>
                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                :label= "this.$global.globalModulNameLink.locator.name + ' Module Name:'"
                                placeholder="Enter New Module Name"
                                value = "Search ID"
                                v-model="leadsLocatorName"
                                v-on:keyup="keyup_modulename('leadname','locator');"
                            >
                            </base-input>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                :label= "this.$global.globalModulNameLink.locator.name + ' Module URL:'"
                                placeholder="Enter URL Path ex. searchid"
                                v-model="leadsLocatorUrl"
                                v-on:keyup="keyup_modulename('leadurl','locator');"
                            >
                            </base-input>
                        </div>
                    </div>
                    <div v-if="this.$global.globalModulNameLink.enhance.name !== null && this.$global.globalModulNameLink.enhance.url !== null" class="pt-3 pb-3">&nbsp;</div>
                    <div v-if="this.$global.globalModulNameLink.enhance.name !== null && this.$global.globalModulNameLink.enhance.url !== null" class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                :label= "this.$global.globalModulNameLink.enhance.name + ' Module Name:'"
                                placeholder="Enter New Module Name"
                                value = "Enhance ID"
                                v-model="leadsEnhanceName"
                                v-on:keyup="keyup_modulename('leadname','enhance');"
                            >
                            </base-input>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                :label= "this.$global.globalModulNameLink.enhance.name + ' Module URL:'"
                                placeholder="Enter URL Path ex. searchid"
                                v-model="leadsEnhanceUrl"
                                v-on:keyup="keyup_modulename('leadurl','enhance');"
                            >
                            </base-input>
                        </div>
                    </div>

                    <template slot="footer">
                        <div class="row pull-right">
                            <div class="col-sm-6 col-md-6 col-lg-6 text-right" v-if="this.$global.menuLeadsPeek_update && false">
                                <base-button type="info" round icon @click="show_helpguide('custommoduleinfo')">
                                <i class="fas fa-question"></i>
                                </base-button>
                                
                                
                            </div>
                            <div class="col-sm-6 col-md-6 col-lg-6 text-left" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon @click="save_general_custommenumodule()">
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>

                </card>
            </div>

            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>
        <div class="row processingArea" v-if="!this.$global.systemUser">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
            <div id="clients-default-products" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':((is_whitelabeling == 'F' || !this.radios.packageID != '') && !this.$global.systemUser)}">
               <card>
                    <template slot="header">
                        <h4>Set Default Products for Clients</h4>
                        <h5 class="card-category">New clients will automatically have the selected default products assigned upon creation.</h5>
                    </template>
                    <div class="pt-3 pb-3">&nbsp;</div>

                    <div class="row" style="align-items: center;">
                        <div :class="cssDefaultModuleByLength()" v-for="(item, index) in defaultModule" :key="index" v-if="item.name" @click="handleDefaultModule(item.type, !item.status)" style="cursor: pointer; padding-block: 8px; padding-left: 8px; padding-right: 8px;">
                            <div class="product__default__module" :class="[item.status ? 'active__default__module' : '']">
                                <i :class="[item.icon, item.status ? 'active__default__module__text' : 'default__module__text']" style="font-size: 18px;"></i>
                                <span style="margin-top: 4px;" :class="[item.status ? 'active__default__module__text' : 'default__module__text']">
                                    {{ item.name }}
                                </span>
                            </div>
                        </div>
                        <div class="col-12" style="display: flex; justify-content: flex-end; margin-top: 32px;">
                            <base-button :disabled="isLoadingSaveDefaultModule" class="btn-green" round :icon="isLoadingSaveDefaultModule ? false : true" @click="saveDefaultModule">
                                <i class="fas fa-save"></i> {{ isLoadingSaveDefaultModule ? 'Loading...' : '' }}
                            </base-button>
                        </div>
                    </div>
                </card>
            </div>
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>

        <div class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="email-settings" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':((is_whitelabeling == 'F' || !this.radios.packageID != '') && !this.$global.systemUser)}">
                <card id="st_smtp">
                    <template slot="header">
                        <h4>Email Settings<i @click="openHelpModal(1)" class="fa fa-question-circle d-none" style="cursor: pointer; margin-left: 12px;"></i></h4>
                        <h5 class="card-category">By default, outbound emails to Administrators and Customers use the email address of {{ this.$global.companyrootname }}. To customize the sending email address, connect your email service provider below and UNCHECK "Use Default Email SMTP".</h5>
                    </template>
                    <div class="pt-3 pb-3">&nbsp;</div>
                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                label="Email Host"
                                placeholder="ex. smtp.gmail.com"
                                v-model="customsmtp.host"
                            >
                            </base-input>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                label="Port Number"
                                placeholder="ex. 465"
                                v-model="customsmtp.port"
                            >
                            </base-input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                label="Username"
                                placeholder="ex. youremail@gmail.com"
                                v-model="customsmtp.username"
                            >
                            </base-input>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left">
                            <base-input
                                label="Email Password"
                                placeholder="ex. mypassword"
                                type="password"
                                 v-model="customsmtp.password"
                            >
                            </base-input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-12 text-left pt-2" >
                            <div class="d-inline"><label>Security Protocol:</label></div>
                            <base-radio name="none" v-model="customsmtp.security" class="d-inline pl-2">None</base-radio>
                            <base-radio name="ssl" v-model="customsmtp.security"  class="d-inline pl-2">SSL</base-radio>
                            <base-radio name="tls" v-model="customsmtp.security"  class="d-inline pl-2">TLS</base-radio>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-12 text-left pt-2">
                            <div>
                                <base-radio :name="true" v-model="customsmtp.default" :class="{ 'radio-inactive': !customsmtp.default }">Use Default Email SMTP</base-radio>
                            </div>
                            <div>
                                <base-radio :name="false" v-model="customsmtp.default" :class="{ 'radio-inactive': customsmtp.default }">Use Personal Email SMTP</base-radio>
                            </div>
                        </div>
                    </div>

                
                      <template slot="footer">
                        <div class="row">
                            <div class="col-sm-6 col-md-6 col-lg-6 text-left" v-if="this.$global.menuLeadsPeek_update">
                                <!--<base-button type="info" round icon @click="show_helpguide('smtpemailinfo')">
                                <i class="fas fa-question"></i>
                                </base-button>-->
                                <base-button :disabled="isSendingTestSMTP" class="btn-green" @click="test_smtpemail()" v-if="!customsmtp.default">
                                {{btnTestSMTP}}
                                </base-button>
                                
                            </div>
                            <div class="col-sm-6 col-md-6 col-lg-6 text-right" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon @click="save_general_smtpemail()">
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>
                           
                </card>
            </div>

            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>
        
        <div class="row processingArea" v-if="!this.$global.systemUser">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div id="email-templates" class="col-sm-12 col-md-12 col-lg-6 text-center" :class="{'disabled-area':(is_whitelabeling == 'F' || !this.radios.packageID != '')}">
                <card>
                    <template slot="header">
                        <h4>Email Templates</h4>
                        <h5 class="card-category">Below are all of the outbound email templates that will be sent to your clients and administrators.<br/>Click on any template to customize it.</h5>
                    </template>
                    <div class="pt-3 pb-3">&nbsp;</div>
                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left email-template-item">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><span class="cursor-pointer" @click="get_email_template('forgetpassword');">Forgot Password</span> 
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left email-template-item">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><span class="cursor-pointer" @click="get_email_template('clientwelcome');">Account Created</span> 
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left email-template-item">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><span class="cursor-pointer" @click="get_email_template('campaigncreated');">Campaign Created</span> 
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left email-template-item">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><span class="cursor-pointer" @click="get_email_template('billingunsuccessful');">Billing Unsuccessful</span> 
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 text-left email-template-item">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><span class="cursor-pointer" @click="get_email_template('archivecampaign');">Campaign Archived</span> 
                        </div>
                        <!--<div class="col-sm-4 col-md-4 col-lg-4 text-left">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><a href="#" @click="get_email_template('agencyclientwelcome');">Agency account setup email</a> 
                        </div>-->
                    </div>
                    <div class="row pt-2">
                        <!--<div class="col-sm-4 col-md-4 col-lg-4 text-left">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><a href="">Questionairre Result Email</a>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-4 text-left">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><a href="">Leads Local Embedded Code Email</a>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-4 text-left">
                             <i class="fas fa-circle pr-2" style="font-size:11px"></i><a href="">Leads Locator Embedded Code Email</a>
                        </div>-->
                    </div>
                    
                    <template slot="footer">
                        <div>&nbsp;</div>
                    </template>
                </card>
            </div>

            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>

        <div id="support-widget" class="row processingArea">
            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>

            <div  class="col-sm-12 col-md-12 col-lg-6 text-center">
                <card>
                    <template slot="header">
                        <h4>Embed your support widget</h4>
                        <h5 class="card-category">You may embed your support widget by inserting the embed code below. We recommend adjusting your widget settings so it appears in the lower right corner.</h5>
                    </template>
                    <div class="pt-3 pb-3">&nbsp;</div>
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-12">
                            <div class="form-group has-label">
                                                        
                                <textarea
                                    id="agencyEmbeddedCode"
                                    class="form-control"
                                    v-model="agencyEmbeddedCode.embeddedcode"
                                    placeholder="" 
                                    rows="5"
                                    style="border:solid 1px;"
                                >
                                </textarea>
                                                        
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2 text-left">
                            <label class="pl-2">Place Code on :</label>
                                <el-select
                                    class="select-primary pl-2"
                                    size="small"
                                    placeholder="Select Modules"
                                    v-model="agencyEmbeddedCode.placeEmbedded"
                                    >
                                    <el-option
                                        v-for="option in selectsPlaceEmbeddedCode.PlaceEmbededCodeList"
                                        class="select-primary"
                                        :value="option.value"
                                        :label="option.label"
                                        :key="option.label"
                                    >
                                    </el-option>
                                </el-select>
                        </div>
                        
                    </div>
                    <template slot="footer">
                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12 text-right" v-if="this.$global.menuLeadsPeek_update">
                                <base-button class="btn-green" round icon @click="save_general_agencyembeddedcode()">
                                <i class="fas fa-save"></i>
                                </base-button>
                            </div>
                        </div>
                    </template>
                </card>
            </div>

            <div class="col-sm-0 col-md-0 col-lg-3">&nbsp;</div>
        </div>
        
        <!-- MODAL DOMAIN/SUBDOMAIN WHITELABELING TEMPLATES-->
            <modal :show.sync="modals.whitelabelingguide" headerClasses="justify-content-center">
                <h4 slot="header" class="title title-up">Whitelabeling URL instructions</h4>
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 text-left">
                       <p>To have full white labeling of your URL. Point your URL A Record to <strong><em>157.230.213.72</em></strong></p>
                       <p>For more information on how to do this, Please see the following help articles. Also feel free to reach out to:</p>
                       <p>
                        - <a href="https://support.rocketspark.com/hc/en-us/articles/115010277387-How-to-change-your-DNS-settings-in-GoDaddy" target="_blank">https://support.rocketspark.com/hc/en-us/articles/115010277387-How-to-change-your-DNS-settings-in-GoDaddy</a><br/>
                        - <a href="https://www.bluehost.com/help/article/dns-management-add-edit-or-delete-dns-entries" target="_blank">https://www.bluehost.com/help/article/dns-management-add-edit-or-delete-dns-entries</a><br/>
                        - <a href="https://support.google.com/domains/answer/3290309?hl=en" target="_blank">https://support.google.com/domains/answer/3290309?hl=en</a><br/>
                       </p>
                    </div>
                </div>
                
                <template slot="footer">
                    <div class="container text-center pb-4">
                    <base-button @click="modals.whitelabelingguide = false">Close</base-button>
                    </div>
                </template>
            </modal>
        <!-- MODAL DOMAIN/SUBDOMAIN WHITELABELING TEMPLATES-->

        <!-- MODAL FOR EMAIL TEMPLATES-->
            <modal :show.sync="modals.emailtemplate" headerClasses="justify-content-center">
                <h4 slot="header" class="title title-up" v-html="emailtemplate.title">Welcome Client Email Template</h4>
                <div class="row">
                    <div class="col-sm-4 col-md-4 col-lg-4 text-left">
                        <base-input
                                label="From Address:"
                                placeholder="ex. noreply@eyourdomain.com"
                                value=""
                                v-model="emailtemplate.fromAddress"
                                id="fromAddress"
                                @click="activeElement = 'fromAddress'"
                            >
                            </base-input>
                    </div>
                    <div class="col-sm-4 col-md-4 col-lg-4 text-left">
                        <base-input
                                label="From Name:"
                                placeholder="ex. Reset Password"
                                value=""
                                v-model="emailtemplate.fromName"
                                id="fromName"
                                @click="activeElement = 'fromName'"
                            >
                            </base-input>
                    </div>
                    <div class="col-sm-4 col-md-4 col-lg-4 text-left">
                        <base-input
                                label="Reply To:"
                                placeholder="ex. support@yourdomain.com"
                                value=""
                                v-model="emailtemplate.fromReplyto"
                                id="fromReplyto"
                                @click="activeElement = 'fromReplyto'"
                            >
                            </base-input>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 text-left">
                        <base-input
                                label="Email Subject:"
                                placeholder="ex. Welcome Account Setup"
                                value=""
                                v-model="emailtemplate.subject"
                                id="emailsubject"
                                @click="activeElement = 'emailsubject'"
                            >
                            </base-input>
                    </div>
                </div>
                <div class="row pt-2">
                    <div class="col-sm-12 col-md-12 col-lg-12 text-left">
                        <label>Email Content:</label>
                        <base-input>
                            <textarea
                            class="form-control"
                            id="emailcontent"
                            @click="activeElement = 'emailcontent'"
                            placeholder="Describe your target customer here" rows="50" style="min-height:180px" v-model="emailtemplate.content">
                            </textarea>
                        </base-input>
                    </div>
                    <div class="col-sm-12 col-md-12 col-lg-12 text-left">
                        Short Code:
                    </div>
                    <div class="col-sm-12 col-md-12 col-lg-12 text-left">
                        <a href="#" @click="insertShortCode('[client-name]');">[client-name]</a> <a href="#" @click="insertShortCode('[client-firstname]');">[client-firstname]</a> <a href="#" @click="insertShortCode('[client-email]');">[client-email]</a> <a href="#" @click="insertShortCode('[client-new-password]');">[client-new-password]</a>&nbsp;
                        <a href="#" @click="insertShortCode('[client-company-name]');">[client-company-name]</a><br/>
                        <a href="#" @click="insertShortCode('[company-name]');">[company-name]</a> <a href="#" @click="insertShortCode('[company-domain]');">[company-domain]</a> <a href="#" @click="insertShortCode('[company-subdomain]');">[company-subdomain]</a> <a href="#" @click="insertShortCode('[company-email]');">[company-email]</a><br/>
                        <a href="#" @click="insertShortCode('[campaign-module-name]');">[campaign-module-name]</a> <a href="#" @click="insertShortCode('[campaign-name]');">[campaign-name]</a> <a href="#" @click="insertShortCode('[campaign-id]');">[campaign-id]</a> <a href="#" @click="insertShortCode('[campaign-spreadsheet-url]');">[campaign-spreadsheet-url]</a>&nbsp;
                    </div>
                </div>
                <template slot="footer">
                    <div class="container text-center pb-4">
                    <base-button :disabled="isSendingTestEmail" class="btn-danger m-2" @click="test_email_content();"> <i class="fas fa-eye"></i> {{ btnTestEmail }}</base-button>
                    <base-button @click="save_email_content();">Update Email Template</base-button>
                    </div>
                </template>
            </modal>
        <!-- MODAL FOR EMAIL TEMPLATES-->

         <!-- MODAL CONFIRMATION RESET CONNECTION-->
            <modal :show.sync="modals.resetstripeconnection" headerClasses="justify-content-center">
                <h4 slot="header" class="title title-up">Reset Your Payment Connection</h4>
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                       <p>Resetting your stripe account will make not active all campaign until your Stripe connection is re-established.<br/>Do you wish to continue?</p>
                       <p>Please type "RESET" to confirm you want to do this.</p>
                       <div class="row">
                       <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>
                       <div class="col-sm-4 col-md-4 col-lg-4 text-center">
                            <base-input
                                placeholder="Type RESET"
                                v-model="confirmreset"
                                style="width:120px;margin:0 auto"
                                id="ConfirmResetConnection"
                            >
                            </base-input>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>
                       </div>
                       <small v-if="confirmreseterror">* Type "RESET" to confirm resetting the connection. (case sensitive)</small>
                    </div>
                </div>
                
                <template slot="footer">
                    <div class="container text-center pb-4">
                    <base-button id="btnconfirmreset" @click="process_resetconnection();">Confirm</base-button>
                    </div>
                </template>
            </modal>
        <!-- MODAL CONFIRMATION RESET CONNECTION-->

        <!-- MODAL CONFIRMATION CANCEL SUBSCRIPTION-->
            <modal :show.sync="modals.cancelsubscription" headerClasses="justify-content-center">
                <h4 slot="header" class="title title-up">Cancel subscription</h4>
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12 text-center">
                       <p>Canceling your subscription will result in the removal of your campaigns, settings, and account from our system.<br/>Are you sure you want to proceed?</p>
                       <p>Please type "CANCEL" to confirm you want to do this.</p>
                       <div class="row">
                       <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>
                       <div class="col-sm-4 col-md-4 col-lg-4 text-center">
                            <base-input
                                placeholder="Type CANCEL"
                                v-model="confirmcancel"
                                style="width:130px;margin:0 auto"
                                id="ConfirmCancel"
                            >
                            </base-input>
                        </div>
                        <div class="col-sm-4 col-md-4 col-lg-4">&nbsp;</div>
                       </div>
                       <small v-if="confirmcancelerror">* Type "Cancel" to confirm cancelling subscription. (case sensitive)</small>
                    </div>
                </div>
                
                <template slot="footer">
                    <div class="container text-center pb-4">
                    <base-button id="btnconfirmcancel" @click="process_cancelsubscription();">Confirm</base-button>
                    </div>
                </template>
            </modal>
        <!-- MODAL CONFIRMATION CANCEL SUBSCRIPTION-->
        <modal :show.sync="modals.help" headerClasses="">
            <div slot="header" class="d-flex flex-column gap-4"><h4  class="title title-up">{{activeHelpItem.title}}</h4> 
                <!-- <p class="text-dark mt-2">Please go through the toutorial for further information. If you have question reach out to <a class="text-underline" href="mailto:danial@exactmatchmarketing.com">danial@exactmatchmarketing.com</a> </p> -->
             </div>
            <div v-if="modals.help" v-html="activeHelpItem.embedVideoCode"></div>
        </modal>
        <div id="popProcessing" class="popProcessing" style="display:none" v-html="popProcessingTxt">Please wait, cancelling subscription ....</div>

    </div>
</template>
<script>
import BaseInput from '../../../../components/Inputs/BaseInput.vue';
import { Select, Option } from 'element-ui';
import { Modal,BaseRadio } from '/src/components';
import Resumable from 'resumablejs'
import swal from 'sweetalert2';

export default {
  components: { 
    BaseInput,
    Modal, 
    BaseRadio,
    [Option.name]: Option,
    [Select.name]: Select,
},
    data() {
      return {
        charges_enabled: false,
        payouts_enabled: false,

        popProcessingTxt: 'Please wait, cancelling subscription ....',
        
        selectsPlaceEmbeddedCode: {
            PlaceEmbededCodeList: [
                { value: 'head', label: 'head tag'},
                { value: 'body', label: 'body tag'},
                { value: 'footer', label: 'footer tag'},
            ],
        },

        agencyEmbeddedCode: {
            embeddedcode: '',
            placeEmbedded: 'head',
        },

        embeddedCodeTitle: 'Agency',
        btnTestSMTP: 'Send test email',
        btnTestEmail: 'Send test email',
        isSendingTestSMTP: false,
        isSendingTestEmail: false,
        activePriceSettingTab:1,
        ru: false,
        ru1: false,
        ru2: false,
        ruLogoLoginAndRegister: false,
        ruLogoSidebar: false,
        apiurl: process.env.VUE_APP_DATASERVER_URL + '/api',
        images: {
            login: '/img/EMMLogin.png',
            register: '/img/EMMLogin.png',
            agency: 'https://emmspaces.nyc3.cdn.digitaloceanspaces.com/systems/agencyregister.png',
        },
        logo: {
            loginAndRegister: '/img/logoplaceholder.png',
            sidebar: this.$global.globalCompanyPhoto || '/img/logoplaceholder.png',
        },
        modals: {
            emailtemplate: false,
            whitelabelingguide:false,
            resetstripeconnection:false,
            cancelsubscription:false,
            help:false,
        },
        emailtemplate: {
            title:'',
            subject: '',
            content: '',
            fromAddress: 'noreply@yourdomain.com',
            fromName: 'Reset Password',
            fromReplyto: 'support@yourdomain.com',
        },   
        helpContentMap:[
            {title:'How to connet google sheet account',embedVideoCode:'<iframe src="https://drive.google.com/file/d/1EGGoIsSt5F9kjBWkrXA0uU6iBvHbymMb/preview" width="100%" height="480" allow="autoplay"></iframe>'},
            {title:'How to configure email settings',embedVideoCode:'<iframe src="https://drive.google.com/file/d/18XHG_iJsOzK0YuMU7DsoW9M4bEI8Bfcp/preview" width="100%" height="480" allow="autoplay"></iframe>'},
            {title:'How to configure whitelabel settings',embedVideoCode:'<iframe src="https://drive.google.com/file/d/1CWmfwNToJ1gmzLrHvfBh-Q6b2xRsa-4P/preview" width="100%" height="480" allow="autoplay"></iframe>'},
        ],
        activeHelpItem:{title:'How to connet google sheet account',embedVideoCode:'<iframe src="https://drive.google.com/file/d/1C43I4mH5et25dhyOZwCT2KjVybWPBQnG/preview" width="100%" height="480" allow="autoplay"></iframe>'},
        activeElement: '',
        emailupdatemodule: '',

        userData: '',
        sidebarcolor:'#942434',
        backgroundtemplatecolor: '#1e1e2f',
        boxcolor: '#ffffff',
        textcolor: '#FFFFFF',
        linkcolor: '#942434',
        fonttheme: 'Poppins',
        fontthemeactive: 'Poppins',

        GoogleConnectFalse: false,
        GoogleConnectTrue: false,

        leadsLocalName : this.$global.globalModulNameLink.local.name,
        leadsLocalUrl : this.$global.globalModulNameLink.local.url,
        leadsLocatorName: this.$global.globalModulNameLink.locator.name,
        leadsLocatorUrl: this.$global.globalModulNameLink.locator.url,
        leadsEnhanceName: this.$global.globalModulNameLink.enhance.name,
        leadsEnhanceUrl: this.$global.globalModulNameLink.enhance.url,

        customsmtp: {
            default: true,
            host: '',
            port: '',
            username: '',
            password: '',
            security: 'ssl',
        },

        txtStatusConnectedAccount: 'Connect your stripe account',
        ActionBtnConnectedAccount: '',
        DisabledBtnConnectedAccount: false,
        statusColorConnectedAccount: '',
        refreshURL: '/configuration/general-setting/',
        returnURL: '/configuration/general-setting/',

        accConID: '',

        DownlineDomain:'',
        DownlineSubDomain:'',
        DownlineDomainStatus:'',
        
        Whitelabellingstatus:false,
        agreewhitelabelling:true,
        chkagreewl:true,
        domainSetupCompleted: false,

        radios: {
            packageID: '',
            lastpackageID: '',
            freeplan: '',
            whitelabeling: {
                monthly: '',
                monthlyprice: '',
                yearly: '',
                yearlyprice: '',
                monthly_disabled: false,
                yearly_disabled:false,
            },
            nonwhitelabelling: {
                monthly: '',
                monthlyprice: '',
                yearly: '',
                yearlyprice: '',
                monthly_disabled: false,
                yearly_disabled:false,
            }
        },

        plannextbill:'free',

        /** FOR SET PRICE */
        CompanyActiveID: '',
        AgencyCompanyName: '',

        LeadspeekPlatformFee: '0',
        LeadspeekCostperlead: '0',
        LeadspeekMinCostMonth: '0',

        LocatorPlatformFee: '0',
        LocatorCostperlead: '0',
        LocatorMinCostMonth: '0',

        EnhancePlatformFee: '0',
        EnhanceCostperlead: '0',
        EnhanceMinCostMonth: '0',

        lead_FirstName_LastName : '0',
        lead_FirstName_LastName_MailingAddress: '0',
        lead_FirstName_LastName_MailingAddress_Phone: '0',

        defaultPaymentMethod: 'stripe',
        packageName: '',

        costagency : {
            local : {
                'Weekly' : {
                    LeadspeekPlatformFee: '0',
                    LeadspeekCostperlead: '0',
                    LeadspeekMinCostMonth: '0',
                },
                'Monthly' : {
                    LeadspeekPlatformFee: '0',
                    LeadspeekCostperlead: '0',
                    LeadspeekMinCostMonth: '0',
                },
                'OneTime' : {
                    LeadspeekPlatformFee: '0',
                    LeadspeekCostperlead: '0',
                    LeadspeekMinCostMonth: '0',
                },
                'Prepaid' : {
                    LeadspeekPlatformFee: '0',
                    LeadspeekCostperlead: '0',
                    LeadspeekMinCostMonth: '0',
                }
            },

            locator : {
                'Weekly' : {
                    LocatorPlatformFee: '0',
                    LocatorCostperlead: '0',
                    LocatorMinCostMonth: '0',
                },
                'Monthly' : {
                    LocatorPlatformFee: '0',
                    LocatorCostperlead: '0',
                    LocatorMinCostMonth: '0',
                },
                'OneTime' : {
                    LocatorPlatformFee: '0',
                    LocatorCostperlead: '0',
                    LocatorMinCostMonth: '0',
                },
                'Prepaid' : {
                    LocatorPlatformFee: '0',
                    LocatorCostperlead: '0',
                    LocatorMinCostMonth: '0',
                }
            },

            enhance : {
                'Weekly' : {
                    EnhancePlatformFee: '0',
                    EnhanceCostperlead: '0',
                    EnhanceMinCostMonth: '0',
                },
                'Monthly' : {
                    EnhancePlatformFee: '0',
                    EnhanceCostperlead: '0',
                    EnhanceMinCostMonth: '0',
                },
                'OneTime' : {
                    EnhancePlatformFee: '0',
                    EnhanceCostperlead: '0',
                    EnhanceMinCostMonth: '0',
                },
                'Prepaid' : {
                    EnhancePlatformFee: '0',
                    EnhanceCostperlead: '0',
                    EnhanceMinCostMonth: '0',
                }
            },

            locatorlead: {
                FirstName_LastName: '0',
                FirstName_LastName_MailingAddress: '0',
                FirstName_LastName_MailingAddress_Phone: '0',
            }
        },

        txtLeadService: 'weekly',
        txtLeadIncluded: 'in that weekly charge',
        txtLeadOver: 'from the weekly charge',

        selectsPaymentTerm: {
            PaymentTermSelect: 'Weekly',
            PaymentTerm: [
                // { value: 'One Time', label: 'One Time billing'},
                // { value: 'Weekly', label: 'Weekly Billing'},
                // { value: 'Monthly', label: 'Monthly Billing'},
            ],
        },
        selectsAppModule: {
                AppModuleSelect: 'LeadsPeek',
                AppModule: [
                    { value: 'LeadsPeek', label: 'LeadsPeek' },
                ],
                LeadsLimitSelect: 'Day',
                LeadsLimit: [
                    { value: 'Day', label: 'Day'},
                ],
        },
        /** FOR SET PRICE */

        confirmreset: '',
        confirmreseterror: false,
        confirmcancel: '',
        confirmcancelerror: false,
        trialEndDate:'',
        notPassSixtyDays:false,

        txtPayoutsEnabled: false, 
        txtpaymentsEnabled: false, 
        txtErrorRequirements: '', 
        is_whitelabeling: null,

        rootSiteIDCostPerLead: 0,
        rootSearchIDCostPerLead:0,
        rootEnhanceIDCostPerLead:0,
        rootCostAgency : {
            local : {
                'Weekly' : {
                    LeadspeekCostperlead: '0',
                },
                'Monthly' : {
                    LeadspeekCostperlead: '0',
                },
                'OneTime' : {
                    LeadspeekCostperlead: '0',
                },
                'Prepaid' : {
                    LeadspeekCostperlead: '0',
                }
            },

            locator : {
                'Weekly' : {
                    LocatorCostperlead: '0',
                },
                'Monthly' : {
                    LocatorCostperlead: '0',
                },
                'OneTime' : {
                    LocatorCostperlead: '0',
                },
                'Prepaid' : {
                    LocatorCostperlead: '0',
                }
            },

            enhance : {
                'Weekly' : {
                    EnhanceCostperlead: '0',
                },
                'Monthly' : {
                    EnhanceCostperlead: '0',
                },
                'OneTime' : {
                    EnhanceCostperlead: '0',
                },
                'Prepaid' : {
                    EnhanceCostperlead: '0',
                }
            },
        },
        defaultModule: [
            {
                type: 'local',
                name: '',
                status: true,
                icon: 'far fa-eye'
            },
            {
                type: 'locator',
                name: '',
                status: true,
                icon: 'fas fa-map-marked'
            },
            {
                type: 'enhance',
                name: '',
                status: true,
                icon: 'fa-solid fa-angles-up'
            },
        ],
        isLoadingSaveDefaultModule: false,
        activeSection: '',
      };
    },
    methods: {
        scrollToSection(sectionId) {
            const element = document.getElementById(sectionId);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
                this.activeSection = sectionId;
            }
        },
        openHelpModal(index){
            this.activeHelpItem = this.helpContentMap[index]
            this.modals.help = true;
        },
        formatPrice(value) {
            //let val = (value/1).toFixed(2).replace(',', '.')
            let val = (value/1).toFixed(2)
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        },
        hasPassedFreeTrial() {
            const today = new Date();
            
            if (this.trialEndDate < today) {
                this.notPassSixtyDays = false;
            }else{
                this.notPassSixtyDays = true;
            }
        },
        test_smtpemail() {
            this.btnTestSMTP = 'Sending test email...';
            this.isSendingTestSMTP = true;
            this.$store.dispatch('testsmtp', {
                     companyID: this.userData.company_id,
                     emailsent: this.userData.email,
                 }).then(response => {
                    this.btnTestSMTP = 'Send test email';
                    this.isSendingTestSMTP = false;
                    
                    if (response.result == 'success') {
                        this.$notify({
                            type: 'success',
                            message: response.msg,
                            icon: 'tim-icons icon-bell-55'
                        }); 
                    }else{
                        this.$notify({
                            type: 'danger',
                            message: response.msg,
                            icon: 'tim-icons icon-bell-55'
                        }); 
                    }
                 },error => {
                    this.btnTestSMTP = 'Send test email';
                    this.isSendingTestSMTP = false;

                     this.$notify({
                            type: 'danger',
                            message: 'SMTP configuration failed to send the email',
                            icon: 'tim-icons icon-bell-55'
                        });  
                 });
        },
        revertViewMode() {
            const oriUsr = this.$global.getlocalStorage('userDataOri');
            //this.$global.SetlocalStorage('userData',oriUsr);
            localStorage.removeItem('userData');
            localStorage.removeItem('userDataOri');
            
            localStorage.setItem('userData',JSON.stringify(oriUsr));
            localStorage.removeItem('userDataOri');
            this.$store.dispatch('setUserData', {
                    user: oriUsr,
            });
            window.document.location = "/configuration/agency-list/";
        },
        cancel_subscription() {
            this.confirmcancel = "";
            this.confirmcancelerror = false;
            this.modals.cancelsubscription = true;
        },
        process_cancelsubscription() {
            if (this.confirmcancel == "CANCEL") {
                $('#btnconfirmcancel').attr("disabled",true);
                this.modals.cancelsubscription = false;
                $('.processingArea').addClass('disabled-area');
                $('#popProcessing').show();
                
                this.$store.dispatch('cancelsubscription', {
                     companyID: this.userData.company_id,
                 }).then(response => {
                     if (response.result == 'success') {
                         this.revertViewMode();
                         return false;
                     }else{
                        ('#btnconfirmcancel').attr("disabled",false);
                        this.confirmcancelerror = false;
                        this.modals.cancelsubscription = false;
                        $('.processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();

                        this.$notify({
                            type: 'warning',
                            message: 'We are unable to process your subscription cancellation request at the moment. Please contact support for assistance.',
                            icon: 'tim-icons icon-bell-55'
                        });     

                     }
                 },error => {
                     ('#btnconfirmreset').attr("disabled",false);
                     this.confirmcancelerror = false;
                     this.modals.cancelsubscription = false;
                     $('.processingArea').removeClass('disabled-area');
                     $('#popProcessing').hide();

                     this.$notify({
                        type: 'warning',
                        message: 'We are unable to process your subscription cancellation request at the moment. Please contact support for assistance.',
                        icon: 'tim-icons icon-bell-55'
                    });   

                 });
            }else{
                this.confirmcancelerror = true;
            }
        },
        reset_stripeconnection() {
            this.confirmreset = "";
            this.confirmreseterror = false;
            this.modals.resetstripeconnection = true;
        },
        process_resetconnection() {
            if (this.confirmreset == "RESET") {
                $('#btnconfirmreset').attr("disabled",true);
                this.$store.dispatch('resetpaymentconnection', {
                     companyID: this.userData.company_id,
                     typeConnection: 'stripe',
                 }).then(response => {
                     if (response.result == 'success') {
                        this.checkConnectedAccount();
                     }else{
                        ('#btnconfirmreset').attr("disabled",false);
                        this.confirmreseterror = true;
                     }
                 },error => {
                     ('#btnconfirmreset').attr("disabled",false);
                     this.confirmreseterror = true;
                 });
            }else{
                this.confirmreseterror = true;
            }
        },
        getAgencyPlanPrice() {
            this.$store.dispatch('getGeneralSetting', {
                companyID: this.$global.idsys,
                settingname: 'agencyplan',
            }).then(response => {
               
                if (response.data != '') {

                    if (process.env.VUE_APP_DEVMODE == 'true') {
                        this.radios.nonwhitelabelling.monthly = response.data.testmode.nonwhitelabelling.monthly;
                        this.radios.nonwhitelabelling.monthlyprice = response.data.testmode.nonwhitelabelling.monthlyprice;
                        this.radios.nonwhitelabelling.yearly = response.data.testmode.nonwhitelabelling.yearly;
                        this.radios.nonwhitelabelling.yearlyprice = response.data.testmode.nonwhitelabelling.yearlyprice;
                        this.radios.whitelabeling.monthly = response.data.testmode.whitelabeling.monthly;
                        this.radios.whitelabeling.monthlyprice = response.data.testmode.whitelabeling.monthlyprice;
                        this.radios.whitelabeling.yearly = response.data.testmode.whitelabeling.yearly;
                        this.radios.whitelabeling.yearlyprice = response.data.testmode.whitelabeling.yearlyprice;
                        this.radios.freeplan = response.data.testmode.free;
                    }else{
                        this.radios.nonwhitelabelling.monthly = response.data.livemode.nonwhitelabelling.monthly;
                        this.radios.nonwhitelabelling.monthlyprice = response.data.livemode.nonwhitelabelling.monthlyprice;
                        this.radios.nonwhitelabelling.yearly = response.data.livemode.nonwhitelabelling.yearly;
                        this.radios.nonwhitelabelling.yearlyprice = response.data.livemode.nonwhitelabelling.yearlyprice;
                        this.radios.whitelabeling.monthly = response.data.livemode.whitelabeling.monthly;
                        this.radios.whitelabeling.monthlyprice = response.data.livemode.whitelabeling.monthlyprice;
                        this.radios.whitelabeling.yearly = response.data.livemode.whitelabeling.yearly;
                        this.radios.whitelabeling.yearlyprice = response.data.livemode.whitelabeling.yearlyprice;
                        this.radios.freeplan = response.data.livemode.free;
                    }

                }
            },error => {
                    
            });
        },
        initial_default_price() {
            this.resetAgencyCost();
            var _settingname = 'agencydefaultprice';
            if (this.$global.systemUser) {
                _settingname = 'rootcostagency';
            }

            this.$store.dispatch('getGeneralSetting', {
                companyID: this.userData.company_id,
                settingname: _settingname,
                idSys: this.$global.idsys
            }).then(response => {
                //console.log(response.data);
                if (response.data != '') {
                    this.costagency = response.data;
                    this.rootCostAgency = response.rootcostagency;
                    //this.selectsPaymentTerm.PaymentTermSelect = 'Weekly';
                    if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                        this.LeadspeekPlatformFee = this.costagency.local.Weekly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.costagency.local.Weekly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.costagency.local.Weekly.LeadspeekMinCostMonth;

                        this.LocatorPlatformFee  = this.costagency.locator.Weekly.LocatorPlatformFee;
                        this.LocatorCostperlead = this.costagency.locator.Weekly.LocatorCostperlead;
                        this.LocatorMinCostMonth = this.costagency.locator.Weekly.LocatorMinCostMonth;

                        this.EnhancePlatformFee  = this.costagency.enhance.Weekly.EnhancePlatformFee;
                        this.EnhanceCostperlead = this.costagency.enhance.Weekly.EnhanceCostperlead;
                        this.EnhanceMinCostMonth = this.costagency.enhance.Weekly.EnhanceMinCostMonth;
                        
                        this.rootSiteIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.local.Weekly) !== 'undefined')?this.rootCostAgency.local.Weekly.LeadspeekCostperlead:0;
                        this.rootSearchIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.locator.Weekly) !== 'undefined')?this.rootCostAgency.locator.Weekly.LocatorCostperlead:0;
                        this.rootEnhanceIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.enhance.Weekly) !== 'undefined')?this.rootCostAgency.enhance.Weekly.EnhanceCostperlead:0;

                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                        this.LeadspeekPlatformFee = this.costagency.local.Monthly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.costagency.local.Monthly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.costagency.local.Monthly.LeadspeekMinCostMonth;

                        this.LocatorPlatformFee  = this.costagency.locator.Monthly.LocatorPlatformFee;
                        this.LocatorCostperlead = this.costagency.locator.Monthly.LocatorCostperlead;
                        this.LocatorMinCostMonth = this.costagency.locator.Monthly.LocatorMinCostMonth;

                        this.EnhancePlatformFee  = this.costagency.enhance.Monthly.EnhancePlatformFee;
                        this.EnhanceCostperlead = this.costagency.enhance.Monthly.EnhanceCostperlead;
                        this.EnhanceMinCostMonth = this.costagency.enhance.Monthly.EnhanceMinCostMonth;

                        this.rootSiteIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.local.Monthly) !== 'undefined')?this.rootCostAgency.local.Monthly.LeadspeekCostperlead:0;
                        this.rootSearchIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.locator.Monthly) !== 'undefined')?this.rootCostAgency.locator.Monthly.LocatorCostperlead:0;
                        this.rootEnhanceIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.enhance.Monthly) !== 'undefined')?this.rootCostAgency.enhance.Monthly.EnhanceCostperlead:0;

                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                        this.LeadspeekPlatformFee = this.costagency.local.OneTime.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.costagency.local.OneTime.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.costagency.local.OneTime.LeadspeekMinCostMonth;

                        this.LocatorPlatformFee  = this.costagency.locator.OneTime.LocatorPlatformFee;
                        this.LocatorCostperlead = this.costagency.locator.OneTime.LocatorCostperlead;
                        this.LocatorMinCostMonth = this.costagency.locator.OneTime.LocatorMinCostMonth;
                        
                        this.EnhancePlatformFee  = this.costagency.enhance.OneTime.EnhancePlatformFee;
                        this.EnhanceCostperlead = this.costagency.enhance.OneTime.EnhanceCostperlead;
                        this.EnhanceMinCostMonth = this.costagency.enhance.OneTime.EnhanceMinCostMonth;

                        this.rootSiteIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.local.OneTime) !== 'undefined')?this.rootCostAgency.local.OneTime.LeadspeekCostperlead:0;
                        this.rootSearchIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.locator.OneTime) !== 'undefined')?this.rootCostAgency.locator.OneTime.LocatorCostperlead:0;
                        this.rootEnhanceIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.enhance.OneTime) !== 'undefined')?this.rootCostAgency.enhance.OneTime.EnhanceCostperlead:0;

                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {

                        this.LeadspeekPlatformFee = (typeof(this.costagency.local.Prepaid) !== 'undefined')?this.costagency.local.Prepaid.LeadspeekPlatformFee:0;
                        this.LeadspeekCostperlead = (typeof(this.costagency.local.Prepaid) !== 'undefined')?this.costagency.local.Prepaid.LeadspeekCostperlead:0;
                        this.LeadspeekMinCostMonth = (typeof(this.costagency.local.Prepaid) !== 'undefined')?this.costagency.local.Prepaid.LeadspeekMinCostMonth:0;

                        this.LocatorPlatformFee  = (typeof(this.costagency.locator.Prepaid) !== 'undefined')?this.costagency.locator.Prepaid.LocatorPlatformFee:0;
                        this.LocatorCostperlead = (typeof(this.costagency.locator.Prepaid) !== 'undefined')?this.costagency.locator.Prepaid.LocatorCostperlead:0;
                        this.LocatorMinCostMonth = (typeof(this.costagency.locator.Prepaid) !== 'undefined')?this.costagency.locator.Prepaid.LocatorMinCostMonth:0;

                        this.EnhancePlatformFee  = (typeof(this.costagency.enhance.Prepaid) !== 'undefined')?this.costagency.enhance.Prepaid.EnhancePlatformFee:0;
                        this.EnhanceCostperlead = (typeof(this.costagency.enhance.Prepaid) !== 'undefined')?this.costagency.enhance.Prepaid.EnhanceCostperlead:0;
                        this.EnhanceMinCostMonth = (typeof(this.costagency.enhance.Prepaid) !== 'undefined')?this.costagency.enhance.Prepaid.EnhanceMinCostMonth:0;

                        this.rootSiteIDCostPerLead =  (this.rootCostAgency != '' && typeof(this.rootCostAgency.local.Prepaid) !== 'undefined')?this.rootCostAgency.local.Prepaid.LeadspeekCostperlead:0; 
                        this.rootSearchIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.locator.Prepaid) !== 'undefined')?this.rootCostAgency.locator.Prepaid.LocatorCostperlead:0;
                        this.rootEnhanceIDCostPerLead = (this.rootCostAgency != '' && typeof(this.rootCostAgency.enhance.Prepaid) !== 'undefined')?this.rootCostAgency.enhance.Prepaid.EnhanceCostperlead:0;

                    }

                    this.lead_FirstName_LastName = this.costagency.locatorlead.FirstName_LastName;
                    this.lead_FirstName_LastName_MailingAddress  = this.costagency.locatorlead.FirstName_LastName_MailingAddress;
                    this.lead_FirstName_LastName_MailingAddress_Phone = this.costagency.locatorlead.FirstName_LastName_MailingAddress_Phone;

                }else if (response.rootcostagency != "") {
                    this.rootCostAgency = response.rootcostagency;
                    if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                        this.rootSiteIDCostPerLead = this.rootCostAgency.local.Weekly.LeadspeekCostperlead;
                        this.rootSearchIDCostPerLead = this.rootCostAgency.locator.Weekly.LocatorCostperlead;
                        this.rootEnhanceIDCostPerLead = this.rootCostAgency.enhance.Weekly.EnhanceCostperlead;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                        this.rootSiteIDCostPerLead = this.rootCostAgency.local.Monthly.LeadspeekCostperlead;
                        this.rootSearchIDCostPerLead = this.rootCostAgency.locator.Monthly.LocatorCostperlead;
                        this.rootEnhanceIDCostPerLead = this.rootCostAgency.enhance.Monthly.EnhanceCostperlead;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                        this.rootSiteIDCostPerLead =  (typeof(this.rootCostAgency.local.Prepaid) !== 'undefined')?this.rootCostAgency.local.Prepaid.LeadspeekCostperlead:0; 
                        this.rootSearchIDCostPerLead = (typeof(this.rootCostAgency.locator.Prepaid) !== 'undefined')?this.rootCostAgency.locator.Prepaid.LocatorCostperlead:0;
                        this.rootEnhanceIDCostPerLead = (typeof(this.rootCostAgency.enhance.Prepaid) !== 'undefined')?this.rootCostAgency.enhance.Prepaid.EnhanceCostperlead:0;
                    }
                }
                
            },error => {
                    
            });
        },
        save_default_subdomain() {
            // Define a regular expression for a valid subdomain
            const subdomainRegex = /^[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/;

            // Check if the entered subdomain matches the regular expression
            var isValidSubdomain = subdomainRegex.test(this.DownlineSubDomain);
           
            if (isValidSubdomain) {
            
                this.$store.dispatch('updateDefaultSubdomain', {
                    companyID: this.userData.company_id,
                    subdomain: this.DownlineSubDomain,
                    idsys: this.$global.idsys,
                }).then(response => {
                    if (response.result == "success") {
                        this.userData.subdomain = this.DownlineSubDomain;

                        localStorage.setItem('userData',JSON.stringify(this.userData));

                        this.$notify({
                            type: 'success',
                            message: 'Default subdomain has been saved.',
                            icon: 'tim-icons icon-bell-55'
                        }); 

                        if (window.location.hostname != response.domain) {
                            window.document.location = 'https://' + this.DownlineSubDomain + '.' + this.$global.companyrootdomain.toLowerCase();
                        }
                    }else{
                        this.$notify({
                            type: 'warning',
                            message: response.message,
                            icon: 'tim-icons icon-bell-55'
                        });     
                    }
                },error => {
                    this.$notify({
                        type: 'warning',
                        message: 'We are unable to save your update, please try again later or contact the support',
                        icon: 'tim-icons icon-bell-55'
                    });     
                });
            }else{
                this.$notify({
                        type: 'danger',
                        message: 'Invalid subdomain name. Please enter a valid subdomain name.',
                        icon: 'tim-icons icon-bell-55'
                    });     
            }
        },
        save_default_price() {
            var _settingname = 'agencydefaultprice';
            if (this.$global.systemUser) {
                _settingname = 'rootcostagency';
            }
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'customsmtpmodule',
                comsetname: _settingname,
                comsetval: this.costagency,
            }).then(response => {
                if (response.result == "success") {
                    this.$notify({
                        type: 'success',
                        message: 'Default Prices has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  
                }
            },error => {
                        
            });
        },

        resetAgencyCost() {

            this.LeadspeekPlatformFee = '0';
            this.LeadspeekCostperlead = '0';
            this.LeadspeekMinCostMonth = '0';
            this.LocatorPlatformFee = '0';
            this.LocatorCostperlead = '0';
            this.LocatorMinCostMonth = '0';
            this.lead_FirstName_LastName = '0';
            this.lead_FirstName_LastName_MailingAddress = '0';
            this.lead_FirstName_LastName_MailingAddress_Phone = '0';

            this.costagency.local.Weekly.LeadspeekPlatformFee = '0';
            this.costagency.local.Weekly.LeadspeekCostperlead = '0';
            this.costagency.local.Weekly.LeadspeekMinCostMonth = '0';

            this.costagency.local.Monthly.LeadspeekPlatformFee = '0';
            this.costagency.local.Monthly.LeadspeekCostperlead = '0';
            this.costagency.local.Monthly.LeadspeekMinCostMonth = '0';

            this.costagency.local.OneTime.LeadspeekPlatformFee = '0';
            this.costagency.local.OneTime.LeadspeekCostperlead = '0';
            this.costagency.local.OneTime.LeadspeekMinCostMonth = '0';

            if (typeof(this.costagency.local.Prepaid) !== 'undefined') {
                this.costagency.local.Prepaid.LeadspeekPlatformFee = '0';
                this.costagency.local.Prepaid.LeadspeekCostperlead = '0';
                this.costagency.local.Prepaid.LeadspeekMinCostMonth = '0';
            }

            this.costagency.locator.Weekly.LocatorPlatformFee = '0';
            this.costagency.locator.Weekly.LocatorCostperlead = '0';
            this.costagency.locator.Weekly.LocatorMinCostMonth = '0';

            this.costagency.locator.Monthly.LocatorPlatformFee = '0';
            this.costagency.locator.Monthly.LocatorCostperlead = '0';
            this.costagency.locator.Monthly.LocatorMinCostMonth = '0';

            this.costagency.locator.OneTime.LocatorPlatformFee = '0';
            this.costagency.locator.OneTime.LocatorCostperlead = '0';
            this.costagency.locator.OneTime.LocatorMinCostMonth = '0';

            if (typeof(this.costagency.locator.Prepaid) !== 'undefined') {
                this.costagency.locator.Prepaid.LocatorPlatformFee = '0';
                this.costagency.locator.Prepaid.LocatorCostperlead = '0';
                this.costagency.locator.Prepaid.LocatorMinCostMonth = '0';
            }
            
            this.costagency.locatorlead.FirstName_LastName = '0';
            this.costagency.locatorlead.FirstName_LastName_MailingAddress = '0';
            this.costagency.locatorlead.FirstName_LastName_MailingAddress_Phone = '0';

            this.costagency.enhance.Weekly.EnhancePlatformFee = '0';
            this.costagency.enhance.Weekly.EnhanceCostperlead = '0';
            this.costagency.enhance.Weekly.EnhanceMinCostMonth = '0';

            this.costagency.enhance.Monthly.EnhancePlatformFee = '0';
            this.costagency.enhance.Monthly.EnhanceCostperlead = '0';
            this.costagency.enhance.Monthly.EnhanceMinCostMonth = '0';

            this.costagency.enhance.OneTime.EnhancePlatformFee = '0';
            this.costagency.enhance.OneTime.EnhanceCostperlead = '0';
            this.costagency.enhance.OneTime.EnhanceMinCostMonth = '0';

            if (typeof(this.costagency.enhance.Prepaid) !== 'undefined') {
                this.costagency.enhance.Prepaid.EnhancePlatformFee = '0';
                this.costagency.enhance.Prepaid.EnhanceCostperlead = '0';
                this.costagency.enhance.Prepaid.EnhanceMinCostMonth = '0';
            }
        },

        set_fee(type,typevalue) {
            // console.log(this.selectsPaymentTerm.PaymentTermSelect);
            // console.log(type);
            // console.log(typevalue);
            if (type == 'local') {

                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.Weekly.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.Weekly.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                    this.costagency.local.Weekly.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.Monthly.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.Monthly.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                        this.costagency.local.Monthly.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.OneTime.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.OneTime.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                        this.costagency.local.OneTime.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.Prepaid.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.Prepaid.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                        this.costagency.local.Prepaid.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }
                }

            }else if (type == 'locator') {

                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    if (typevalue == 'LocatorPlatformFee') {
                        this.costagency.locator.Weekly.LocatorPlatformFee = this.LocatorPlatformFee;
                    }else if (typevalue == 'LocatorCostperlead') {
                        this.costagency.locator.Weekly.LocatorCostperlead = this.LocatorCostperlead;
                    }else if (typevalue == 'LocatorMinCostMonth') {
                        this.costagency.locator.Weekly.LocatorMinCostMonth = this.LocatorMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    if (typevalue == 'LocatorPlatformFee') {
                        this.costagency.locator.Monthly.LocatorPlatformFee = this.LocatorPlatformFee;
                    }else if (typevalue == 'LocatorCostperlead') {
                        this.costagency.locator.Monthly.LocatorCostperlead = this.LocatorCostperlead;
                    }else if (typevalue == 'LocatorMinCostMonth') {
                        this.costagency.locator.Monthly.LocatorMinCostMonth = this.LocatorMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    if (typevalue == 'LocatorPlatformFee') {
                        this.costagency.locator.OneTime.LocatorPlatformFee = this.LocatorPlatformFee;
                    }else if (typevalue == 'LocatorCostperlead') {
                        this.costagency.locator.OneTime.LocatorCostperlead = this.LocatorCostperlead;
                    }else if (typevalue == 'LocatorMinCostMonth') {
                        this.costagency.locator.OneTime.LocatorMinCostMonth = this.LocatorMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    if (typevalue == 'LocatorPlatformFee') {
                        this.costagency.locator.Prepaid.LocatorPlatformFee = this.LocatorPlatformFee;
                    }else if (typevalue == 'LocatorCostperlead') {
                        this.costagency.locator.Prepaid.LocatorCostperlead = this.LocatorCostperlead;
                    }else if (typevalue == 'LocatorMinCostMonth') {
                        this.costagency.locator.Prepaid.LocatorMinCostMonth = this.LocatorMinCostMonth;
                    }
                }

            }else if (type == 'locatorlead') {
                if (typevalue == 'FirstName_LastName') {
                    this.costagency.locatorlead.FirstName_LastName = this.lead_FirstName_LastName;
                }else if (typevalue == 'FirstName_LastName_MailingAddress') {
                    this.costagency.locatorlead.FirstName_LastName_MailingAddress = this.lead_FirstName_LastName_MailingAddress;
                }else if (typevalue == 'FirstName_LastName_MailingAddress_Phone') {
                    this.costagency.locatorlead.FirstName_LastName_MailingAddress_Phone = this.lead_FirstName_LastName_MailingAddress_Phone;
                    this.costagency.locator.Weekly.LocatorCostperlead = this.lead_FirstName_LastName_MailingAddress_Phone;
                    this.costagency.locator.Monthly.LocatorCostperlead = this.lead_FirstName_LastName_MailingAddress_Phone;
                    this.costagency.locator.OneTime.LocatorCostperlead = this.lead_FirstName_LastName_MailingAddress_Phone;
                    this.costagency.locator.Prepaid.LocatorCostperlead = this.lead_FirstName_LastName_MailingAddress_Phone;
                }
            }else if(type == 'enhance') {
                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.Weekly.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.Weekly.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                    this.costagency.enhance.Weekly.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.Monthly.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.Monthly.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                        this.costagency.enhance.Monthly.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.OneTime.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.OneTime.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                        this.costagency.enhance.OneTime.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.Prepaid.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.Prepaid.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                        this.costagency.enhance.Prepaid.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }
                }
            }
        
        },
        paymentTermStatus() {
            if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                this.txtLeadService = 'weekly';
                this.txtLeadIncluded = 'in that weekly charge';
                this.txtLeadOver ='from the weekly charge';

                /** SET VALUE */
                this.LeadspeekPlatformFee = this.costagency.local.Weekly.LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.costagency.local.Weekly.LeadspeekCostperlead;
                this.LeadspeekMinCostMonth = this.costagency.local.Weekly.LeadspeekMinCostMonth;

                this.LocatorPlatformFee  = this.costagency.locator.Weekly.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.Weekly.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.Weekly.LocatorMinCostMonth

                this.EnhancePlatformFee  = this.costagency.enhance.Weekly.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.Weekly.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.Weekly.EnhanceMinCostMonth
                /** SET VALUE */

                this.rootSiteIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.local.Weekly) !== 'undefined')?this.rootCostAgency.local.Weekly.LeadspeekCostperlead:0;
                this.rootSearchIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.locator.Weekly) !== 'undefined')?this.rootCostAgency.locator.Weekly.LocatorCostperlead:0;
                this.rootEnhanceIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.enhance.Weekly) !== 'undefined')?this.rootCostAgency.enhance.Weekly.EnhanceCostperlead:0;

            }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                this.txtLeadService = 'monthly';
                this.txtLeadIncluded = 'in that monthly charge';
                this.txtLeadOver ='from the monthly charge';

                /** SET VALUE */
                this.LeadspeekPlatformFee = this.costagency.local.Monthly.LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.costagency.local.Monthly.LeadspeekCostperlead;
                this.LeadspeekMinCostMonth = this.costagency.local.Monthly.LeadspeekMinCostMonth;
                
                this.LocatorPlatformFee  = this.costagency.locator.Monthly.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.Monthly.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.Monthly.LocatorMinCostMonth
                
                this.EnhancePlatformFee  = this.costagency.enhance.Monthly.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.Monthly.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.Monthly.EnhanceMinCostMonth
                /** SET VALUE */

                this.rootSiteIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.local.Monthly) !== 'undefined')?this.rootCostAgency.local.Monthly.LeadspeekCostperlead:0;
                this.rootSearchIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.locator.Monthly) !== 'undefined')?this.rootCostAgency.locator.Monthly.LocatorCostperlead:0;
                this.rootEnhanceIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.enhance.Monthly) !== 'undefined')?this.rootCostAgency.enhance.Monthly.EnhanceCostperlead:0;

            }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                this.txtLeadService = '';
                this.txtLeadIncluded = '';
                this.txtLeadOver ='';

                /** SET VALUE */
                this.LeadspeekPlatformFee = this.costagency.local.OneTime.LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.costagency.local.OneTime.LeadspeekCostperlead;
                this.LeadspeekMinCostMonth = this.costagency.local.OneTime.LeadspeekMinCostMonth;
                
                this.LocatorPlatformFee  = this.costagency.locator.OneTime.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.OneTime.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.OneTime.LocatorMinCostMonth
                
                this.EnhancePlatformFee  = this.costagency.enhance.OneTime.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.OneTime.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.OneTime.EnhanceMinCostMonth
                /** SET VALUE */

                this.rootSiteIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.local.OneTime) !== 'undefined')?this.rootCostAgency.local.OneTime.LeadspeekCostperlead:0;
                this.rootSearchIDCostPerLead = (this.rootCostAgency != "" &&  typeof(this.rootCostAgency.locator.OneTime) !== 'undefined')?this.rootCostAgency.locator.OneTime.LocatorCostperlead:0;
                this.rootEnhanceIDCostPerLead = (this.rootCostAgency != "" &&  typeof(this.rootCostAgency.enhance.OneTime) !== 'undefined')?this.rootCostAgency.enhance.OneTime.EnhanceCostperlead:0;

            }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                this.txtLeadService = '';
                this.txtLeadIncluded = '';
                this.txtLeadOver ='';

                if (typeof(this.costagency.local.Prepaid) == 'undefined') {
                    this.$set(this.costagency.local,'Prepaid',{
                    LeadspeekPlatformFee: '0',
                    LeadspeekCostperlead: '0',
                    LeadspeekMinCostMonth: '0',
                    });
                }

                if (typeof(this.costagency.locator.Prepaid) == 'undefined') {
                    this.$set(this.costagency.locator,'Prepaid',{
                    LocatorPlatformFee: '0',
                    LocatorCostperlead: '0',
                    LocatorMinCostMonth: '0',
                    });
                }

                /** SET VALUE */
                this.LeadspeekPlatformFee = this.costagency.local.Prepaid.LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.costagency.local.Prepaid.LeadspeekCostperlead;
                this.LeadspeekMinCostMonth = this.costagency.local.Prepaid.LeadspeekMinCostMonth;
                
                this.LocatorPlatformFee  = this.costagency.locator.Prepaid.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.Prepaid.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.Prepaid.LocatorMinCostMonth
                
                this.EnhancePlatformFee  = this.costagency.enhance.Prepaid.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.Prepaid.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.Prepaid.EnhanceMinCostMonth
                /** SET VALUE */

                this.rootSiteIDCostPerLead =  (this.rootCostAgency != "" && typeof(this.rootCostAgency.local.Prepaid) !== 'undefined')?this.rootCostAgency.local.Prepaid.LeadspeekCostperlead:0; 
                this.rootSearchIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.locator.Prepaid) !== 'undefined')?this.rootCostAgency.locator.Prepaid.LocatorCostperlead:0;
                this.rootEnhanceIDCostPerLead = (this.rootCostAgency != "" && typeof(this.rootCostAgency.enhance.Prepaid) !== 'undefined')?this.rootCostAgency.enhance.Prepaid.EnhanceCostperlead:0;


            }
        },
        paymentTermChange() {
            this.paymentTermStatus();

            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'paymenttermDefault',
                paymenttermDefault: this.selectsPaymentTerm.PaymentTermSelect,
            }).then(response => {
                if (response.result == "success") {
                    this.userData.paymentterm_default = this.selectsPaymentTerm.PaymentTermSelect;
                    localStorage.setItem('userData', JSON.stringify(this.userData));

                    this.$notify({
                        type: 'success',
                        message: 'Default Payment Term has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  
                }
            },error => {
                        
            });

        },
        save_plan_package() {
            if (this.radios.packageID != '' && this.radios.packageID != this.radios.lastpackageID) {
                swal.fire({
                        title: 'Please Confirm',
                        text: 'Your new plan will begin immediately.',
                        icon: '',
                        showCancelButton: true,
                        customClass: {
                        confirmButton: 'btn btn-fill mr-3',
                        cancelButton: 'btn btn-danger btn-fill'
                        },
                        confirmButtonText: 'Choose Plan',
                        buttonsStyling: false
                }).then(result => {
                        if (result.isDismissed) {
                            this.radios.packageID = this.radios.lastpackageID;
                        }else if (result.isConfirmed) {
                            this.process_save_plan();
                        }
                });
            }
        },
        process_save_plan() {
            if (this.radios.packageID != '' && this.radios.packageID != this.radios.lastpackageID) {
                this.$store.dispatch('savePlanPackage', {
                    CompanyID: this.userData.company_id,
                    packageID: this.radios.packageID,
                }).then(response => {
                    //console.log(response);
                    if (response.result == 'success') {
                        this.radios.lastpackageID = this.radios.packageID;
                        this.enable_disabled_package(this.radios.packageID);
                        if (response.packagewhite == 'F') {
                            this.Whitelabellingstatus = false;
                        }else{
                            this.Whitelabellingstatus = true;
                        }

                        if (response.plannextbill != '') { 
                            this.plannextbill = response.plannextbill;
                        }

                        this.is_whitelabeling = response.is_whitelabeling
                        
                        this.$notify({
                            type: 'success',
                            message: 'Your plan has been updated',
                            icon: 'tim-icons icon-bell-55'
                        });  
                    }else{
                        this.$notify({
                            type: 'warning',
                            message: 'We are unable to save your plan, please try again later or contact the support',
                            icon: 'tim-icons icon-bell-55'
                        });  
                    }
                },error => { 
                    this.radios.packageID = this.radios.lastpackageID;
                    this.$notify({
                        type: 'warning',
                        message: 'We are unable to save your plan, please try again later or contact the support',
                        icon: 'tim-icons icon-bell-55'
                    });  
                });
            }
        },
        insertShortCode(text) {
            var areaId = this.activeElement;
            
            if (areaId == 'emailsubject' || areaId == 'fromName') {
                const shortcode = text;
                const textField = $('#' + areaId)[0]; // Get the raw DOM element

                const startPos = textField.selectionStart || 0;
                const endPos = textField.selectionEnd || 0;
                
                const currentValue = textField.value;
                const newValue = currentValue.substring(0, startPos) + shortcode + currentValue.substring(endPos);
                
                textField.value = newValue;
                textField.setSelectionRange(startPos + shortcode.length, startPos + shortcode.length);
                
                // Trigger input event manually to update Vue or other frameworks
                $(textField).trigger('input');
                if (areaId == 'emailsubject') {
                    this.emailtemplate.subject = $(textField).val();
                }else if (areaId == 'fromName') {
                    this.emailtemplate.fromName = $(textField).val();
                }
                $(textField).focus();
            }else if (areaId == 'emailcontent') {
                var txtarea = document.getElementById(areaId);
                var strPos = 0;
                var br = ((txtarea.selectionStart || txtarea.selectionStart == '0') ?
                            "ff" : (document.selection ? "ie" : false));
                if (br == "ie") {
                    txtarea.focus();
                    var range = document.selection.createRange();
                    range.moveStart('character', -txtarea.value.length);
                    strPos = range.text.length;
                } else if (br == "ff") strPos = txtarea.selectionStart;

                var front = (txtarea.value).substring(0, strPos);
                var back = (txtarea.value).substring(strPos, txtarea.value.length);
                txtarea.value = front + text + back;
                strPos = strPos + text.length;
                if (br == "ie") {
                    txtarea.focus();
                    var range = document.selection.createRange();
                    range.moveStart('character', -txtarea.value.length);
                    range.moveStart('character', strPos);
                    range.moveEnd('character', 0);
                    range.select();
                } else if (br == "ff") {
                    txtarea.selectionStart = strPos;
                    txtarea.selectionEnd = strPos;
                    txtarea.focus();
                }

                this.emailtemplate.content = txtarea.value;
            }
            return false;
        },
        get_email_template(templateName) {
            if (templateName == "forgetpassword") {
                this.emailtemplate.title = "Forget password Template"
                this.emailupdatemodule = 'em_forgetpassword';

                this.$store.dispatch('getGeneralSetting', {
                    companyID: this.userData.company_id,
                    settingname: 'em_forgetpassword',
                }).then(response => {
                    if (response.data != '') {
                        if (typeof(response.data.fromAddress) != 'undefined') {
                            this.emailtemplate.fromAddress = response.data.fromAddress;
                        }
                        if (typeof(response.data.fromName) != 'undefined') {
                            this.emailtemplate.fromName = response.data.fromName;
                        }
                        if (typeof(response.data.fromReplyto) != 'undefined') {
                            this.emailtemplate.fromReplyto = response.data.fromReplyto;
                        }
                        this.emailtemplate.subject = response.data.subject;
                        this.emailtemplate.content = response.data.content;
                    }
                },error => {
                  
                });

                this.modals.emailtemplate = true;
            }else if (templateName == "clientwelcome") {
                this.emailtemplate.title = "Account setup template"
                this.emailupdatemodule = 'em_clientwelcomeemail';

                this.$store.dispatch('getGeneralSetting', {
                    companyID: this.userData.company_id,
                    settingname: 'em_clientwelcomeemail',
                }).then(response => {
                    if (response.data != '') {
                        if (typeof(response.data.fromAddress) != 'undefined') {
                            this.emailtemplate.fromAddress = response.data.fromAddress;
                        }
                        if (typeof(response.data.fromName) != 'undefined') {
                            this.emailtemplate.fromName = response.data.fromName;
                        }
                        if (typeof(response.data.fromReplyto) != 'undefined') {
                            this.emailtemplate.fromReplyto = response.data.fromReplyto;
                        }
                        this.emailtemplate.subject = response.data.subject;
                        this.emailtemplate.content = response.data.content;
                    }
                },error => {
                  
                });

                this.modals.emailtemplate = true;
            }else if (templateName == "agencyclientwelcome") {
                this.emailtemplate.title = "Agency account setup template"
                this.emailupdatemodule = 'em_agencywelcomeemail';

                this.$store.dispatch('getGeneralSetting', {
                    companyID: this.userData.company_id,
                    settingname: 'em_agencywelcomeemail',
                }).then(response => {
                    if (response.data != '') {
                        if (typeof(response.data.fromAddress) != 'undefined') {
                            this.emailtemplate.fromAddress = response.data.fromAddress;
                        }
                        if (typeof(response.data.fromName) != 'undefined') {
                            this.emailtemplate.fromName = response.data.fromName;
                        }
                        if (typeof(response.data.fromReplyto) != 'undefined') {
                            this.emailtemplate.fromReplyto = response.data.fromReplyto;
                        }
                        this.emailtemplate.subject = response.data.subject;
                        this.emailtemplate.content = response.data.content;
                    }
                },error => {
                  
                });

                this.modals.emailtemplate = true;
            }else if (templateName == "campaigncreated") {
                this.emailtemplate.title = "Campaign Create template"
                this.emailupdatemodule = 'em_campaigncreated';

                this.$store.dispatch('getGeneralSetting', {
                    companyID: this.userData.company_id,
                    settingname: 'em_campaigncreated',
                }).then(response => {
                    if (response.data != '') {
                        if (typeof(response.data.fromAddress) != 'undefined') {
                            this.emailtemplate.fromAddress = response.data.fromAddress;
                        }
                        if (typeof(response.data.fromName) != 'undefined') {
                            this.emailtemplate.fromName = response.data.fromName;
                        }
                        if (typeof(response.data.fromReplyto) != 'undefined') {
                            this.emailtemplate.fromReplyto = response.data.fromReplyto;
                        }
                        this.emailtemplate.subject = response.data.subject;
                        this.emailtemplate.content = response.data.content;
                    }
                },error => {
                  
                });

                this.modals.emailtemplate = true;
            }else if (templateName == "billingunsuccessful") {
                this.emailtemplate.title = "Billing Unsuccessful template"
                this.emailupdatemodule = 'em_billingunsuccessful';

                this.$store.dispatch('getGeneralSetting', {
                    companyID: this.userData.company_id,
                    settingname: 'em_billingunsuccessful',
                }).then(response => {
                    if (response.data != '') {
                        if (typeof(response.data.fromAddress) != 'undefined') {
                            this.emailtemplate.fromAddress = response.data.fromAddress;
                        }
                        if (typeof(response.data.fromName) != 'undefined') {
                            this.emailtemplate.fromName = response.data.fromName;
                        }
                        if (typeof(response.data.fromReplyto) != 'undefined') {
                            this.emailtemplate.fromReplyto = response.data.fromReplyto;
                        }
                        this.emailtemplate.subject = response.data.subject;
                        this.emailtemplate.content = response.data.content;
                    }
                },error => {
                  
                });

                this.modals.emailtemplate = true;
            }else if (templateName == "archivecampaign") {
                this.emailtemplate.title = "Campaign Archived template"
                this.emailupdatemodule = 'em_archivecampaign';

                this.$store.dispatch('getGeneralSetting', {
                    companyID: this.userData.company_id,
                    settingname: 'em_archivecampaign',
                }).then(response => {
                    if (response.data != '') {
                        if (typeof(response.data.fromAddress) != 'undefined') {
                            this.emailtemplate.fromAddress = response.data.fromAddress;
                        }
                        if (typeof(response.data.fromName) != 'undefined') {
                            this.emailtemplate.fromName = response.data.fromName;
                        }
                        if (typeof(response.data.fromReplyto) != 'undefined') {
                            this.emailtemplate.fromReplyto = response.data.fromReplyto;
                        }
                        this.emailtemplate.subject = response.data.subject;
                        this.emailtemplate.content = response.data.content;
                    }
                },error => {
                  
                });

                this.modals.emailtemplate = true;
            }
        },
        createConnectedAccountLink() {
            this.$store.dispatch('createConnectedAccountLink', {
                connectid: this.accConID,
                refreshurl: this.refreshURL,
                returnurl: this.returnURL,
                idsys: this.$global.idsys,
            }).then(response => {
                document.location = response.params.url;
            },error => {
                this.txtStatusConnectedAccount = "Connect your stripe account";
                this.DisabledBtnConnectedAccount = false;
                
                this.$notify({
                    type: 'warning',
                    message: 'we are unable to connect your account, please try again later or contact the support',
                    icon: 'tim-icons icon-bell-55'
                });  
            });
        },
        createConnectedAccount() {
            this.txtStatusConnectedAccount = "Connecting...";
            this.DisabledBtnConnectedAccount = true;

            this.$store.dispatch('createConnectedAccount', {
                companyID: this.userData.company_id,
                companyname: this.userData.company_name,
                companyphone: this.userData.company_phone,
                companyaddress: this.userData.company_address,
                companycity: this.userData.company_city,
                companystate: this.userData.company_state,
                companyzip: this.userData.company_zip,
                companycountry: this.userData.company_country_code,
                companyemail: this.userData.company_email,
                //weburl: (this.userData.domain != '')?this.userData.domain:this.userData.subdomain,
                weburl: window.location.hostname,
                idsys: this.$global.idsys,
            }).then(response => {
                this.accConID = response.params.ConnectAccID;
                this.createConnectedAccountLink();
            },error => {
                this.txtStatusConnectedAccount = "Connect your stripe account";
                this.DisabledBtnConnectedAccount = false;
                this.$notify({
                    type: 'warning',
                    message: 'we are unable to connect your account, with following message: ' + error,
                    icon: 'tim-icons icon-bell-55',
                    duration: 8000,
                });  

            });
        },
        processConnectedAccount() {
            if (this.ActionBtnConnectedAccount == 'createAccount') {
                this.createConnectedAccount();
            }else if (this.ActionBtnConnectedAccount == 'createAccountLink') {
                this.createConnectedAccountLink();
            }
        },
        showError(){
            const htmlContent = this.txtErrorRequirements + `<br/><br/>To update your Stripe connected account <a class="text-underline-color" style="text-decoration: underline;" href="https://dashboard.stripe.com/account/status" target="_blank">Click here</a>`;
            swal.fire({
                html: htmlContent,
                confirmButtonText: 'OK',
                customClass: {
                    confirmButton: 'btn-black-color'
                }
            });
        },
        checkConnectedAccount() {
            this.$store.dispatch('checkConnectedAccount', {
                companyID: this.userData.company_id,
                idsys: this.$global.idsys,
            }).then(response => {
                if (this.defaultPaymentMethod == 'stripe') {
                    if (this.$refs.btnglobalreset) {
                        this.$refs.btnglobalreset.style.display = 'block';
                    }
                }
                if (response.result == 'failed') {
                    this.txtStatusConnectedAccount = "Connect your stripe account";
                    this.ActionBtnConnectedAccount = 'createAccount';
                    this.statusColorConnectedAccount = '';
                    if (this.userData.manual_bill == 'T') {
                        this.radios.lastpackageID = 'agencyDirectPayment';
                        this.radios.packageID = 'agencyDirectPayment';
                        this.Whitelabellingstatus = true;
                        this.is_whitelabeling = response.is_whitelabeling
                        this.$global.statusaccountconnected = 'completed';
                    }
                }else if (response.result == 'pending') {
                    this.txtStatusConnectedAccount = 'Your stripe registration is incomplete, click here to continue';
                    this.ActionBtnConnectedAccount = 'createAccountLink';
                    this.accConID = response.params[0].acc_connect_id;
                    this.statusColorConnectedAccount = '';
                }else if (response.result == 'pending-verification' || response.result == 'inverification') {
                    this.txtStatusConnectedAccount = 'Almost there, stripe is verifying your account.';
                    this.ActionBtnConnectedAccount = 'inverification';
                    this.accConID = response.params[0].acc_connect_id;
                    this.statusColorConnectedAccount = '#fb6340';
                }else{
                    this.txtStatusConnectedAccount = "Stripe Account Connected"
                    this.ActionBtnConnectedAccount = 'accountConnected';
                    this.statusColorConnectedAccount = '#2dce89';
                    this.$global.stripeaccountconnected = true;
                    this.txtPayoutsEnabled = response.payouts_enabled; 
                    this.txtpaymentsEnabled = response.charges_enabled; 
                    this.txtErrorRequirements = response.account_requirements.errors.length > 0 ? response.account_requirements.errors[0].reason : '';
                    this.$global.statusaccountconnected = 'completed';
                    $('#popstatusaccountconnect').hide();

                    if (response.params[0]['package_id'] == '') {
                        this.Whitelabellingstatus = false;
                    }else{
                        if (response.paymentgateway != 'stripe') {
                            this.defaultPaymentMethod = response.paymentgateway;  
                        }
                       
                        if (typeof(response.packagename) != 'undefined' && response.packagename != '') {
                            this.packageName = response.packagename;
                        }

                        this.radios.packageID = response.params[0]['package_id'];
                        this.radios.lastpackageID = response.params[0]['package_id'];

                        if (response.openallplan == 'F') {
                            this.enable_disabled_package(response.params[0]['package_id']);
                        }else{
                            this.openallplan();
                        }

                        if (response.plannextbill != '') {
                            this.plannextbill = response.plannextbill;
                        }

                        if (response.packagewhite == 'T') {
                            this.Whitelabellingstatus = true;
                        }

                        this.is_whitelabeling = response.is_whitelabeling
                    }
                    
                }


            },error => {
                if (this.$refs.btnglobalreset) {
                    this.$refs.btnglobalreset.style.display = 'block';
                }
            });
        },
        openallplan() {
            this.radios.nonwhitelabelling.monthly_disabled = false;
            this.radios.nonwhitelabelling.yearly_disabled = false;
            this.radios.whitelabeling.monthly_disabled = false;
            this.radios.whitelabeling.yearly_disabled = false;
        },
        enable_disabled_package(currPlan) {
            /** FOR INITIAL PACKAGE PLAN */
            this.openallplan();
        if (process.env.VUE_APP_DEVMODE == 'true') {
            if (currPlan == this.radios.nonwhitelabelling.yearly) {
                this.radios.nonwhitelabelling.monthly_disabled = true;
                this.radios.whitelabeling.monthly_disabled = true;
            }else if (currPlan == this.radios.whitelabeling.monthly) {
                this.radios.nonwhitelabelling.monthly_disabled = true;
                this.radios.nonwhitelabelling.yearly_disabled = true;
            }else if (currPlan == this.radios.whitelabeling.yearly) {
                this.radios.nonwhitelabelling.monthly_disabled = true;
                this.radios.nonwhitelabelling.yearly_disabled = true;
                this.radios.whitelabeling.monthly_disabled = true;
            }
            
        }else{
            if (currPlan == this.radios.nonwhitelabelling.yearly) {
                this.radios.nonwhitelabelling.monthly_disabled = true;
                this.radios.whitelabeling.monthly_disabled = true;
            }else if (currPlan == this.radios.whitelabeling.monthly) {
                this.radios.nonwhitelabelling.monthly_disabled = true;
                this.radios.nonwhitelabelling.yearly_disabled = true;
            }else if (currPlan == this.radios.whitelabeling.yearly) {
                this.radios.nonwhitelabelling.monthly_disabled = true;
                this.radios.nonwhitelabelling.yearly_disabled = true;
                this.radios.whitelabeling.monthly_disabled = true;
            }
        }
        /** FOR INITIAL PACKAGE PLAN */
        },
        checkGoogleConnect() {
            this.$store.dispatch('checkGoogleConnectSheet', {
                companyID: this.userData.company_id,
            }).then(response => {
                //console.log(response.googleSpreadsheetConnected);
                if (response.googleSpreadsheetConnected) {
                    this.GoogleConnectTrue = true;
                    this.GoogleConnectFalse = false;
                }else{
                    this.GoogleConnectTrue = false;
                    this.GoogleConnectFalse = true;
                }
            },error => {
                
            });
        },
        disconnect_googleSpreadSheet() {
            swal.fire({
                    title: 'Please Confirm',
                    text: 'Are you sure you want to disconnect from Google Sheets? Please ensure that after disconnecting, you reconnect with the same Google account you previously used. If you connect with a different account, your current or old campaigns may not function properly, but your new campaigns will work with the newly connected Google account. Additionally, please make sure you check all permissions required when you connect your Google account. Are you sure you want to proceed?',
                    icon: '',
                    showCancelButton: true,
                    customClass: {
                    confirmButton: 'btn btn-fill mr-3',
                    cancelButton: 'btn btn-danger btn-fill'
                    },
                    confirmButtonText: 'Disconnect Google Sheet',
                    buttonsStyling: false
            }).then(result => {
                    if (result.isConfirmed) {
                       this.process_disconnect_googleSpreadSheet();
                    }
            });

            
        },
        process_disconnect_googleSpreadSheet() {
            this.$store.dispatch('disconectGoogleSheet', {
                companyID: this.userData.company_id,
            }).then(response => {
                //console.log(response.googleSpreadsheetConnected);
                if (response.result == 'success') {
                    this.GoogleConnectTrue = false;
                    this.GoogleConnectFalse = true;
                }else{
                    swal.fire({
                        title: 'Information',
                        text: response.message,
                        timer: 7000,
                        showConfirmButton: false,
                        icon: 'success'
                    });
            }
            },error => {
                
          });
        },
        // scrollToSection(sectionId) {
        //   this.$nextTick(() => {
        //     const element = document.getElementById(sectionId);
        //     if (element) {
        //       element.scrollIntoView({ behavior: 'smooth' });
        //       this.activeSection = sectionId;
        //     }
        //   });
        // },
        connect_googleSpreadSheet() {
            window.removeEventListener('message', this.callbackGoogleConnected);
            window.addEventListener('message', this.callbackGoogleConnected);

            var left = (screen.width/2)-(1024/2);
            var top = (screen.height/2)-(800/2);
            var fbwindow = window.open(process.env.VUE_APP_DATASERVER_URL + '/auth/google-spreadSheet/' + this.userData.company_id,'Google SpreadSheet Auth',"menubar=no,toolbar=no,status=no,width=640,height=800,toolbar=no,location=no,modal=1,left="+left+",top="+top);
        },
        callbackGoogleConnected(e) {
            window.removeEventListener('message', this.callbackGoogleConnected);
            if (e.origin == process.env.VUE_APP_DATASERVER_URL) {
                if (e.data == 'success') {
                    this.GoogleConnectTrue = true;
                    this.GoogleConnectFalse = false;
                }
            }
        },
        keyup_modulename(module,name) {
            if (name == 'local') {
                if(module == 'leadname') {
                    this.$global.globalModulNameLink.local.name = this.leadsLocalName;
                }else if(module == 'leadurl') {
                    this.$global.globalModulNameLink.local.url = this.leadsLocalUrl;
                }
            }else if (name == 'locator') {
                if(module == 'leadname') {
                    this.$global.globalModulNameLink.locator.name = this.leadsLocatorName;
                }else if(module == 'leadurl') {
                    this.$global.globalModulNameLink.locator.url = this.leadsLocatorUrl;
                }
            }else if (name == 'enhance' && this.$global.globalModulNameLink.enhance.name != null && this.$global.globalModulNameLink.enhance.url != null) {
                if(module == 'leadname') {
                    this.$global.globalModulNameLink.enhance.name = this.leadsEnhanceName;
                }else if(module == 'leadurl') {
                    this.$global.globalModulNameLink.enhance.url = this.leadsEnhanceUrl;
                }
            }
        },
        showProgress(index) {
            $('#progressmsgshow' + index + ' .progress').find('.progress-bar').css('width', '0%');
            $('#progressmsgshow' + index + ' .progress').find('.progress-bar').html('0%');
            $('#progressmsgshow' + index + ' .progress').find('.progress-bar').removeClass('bg-success');
            $('#progressmsgshow' + index + ' .progress').show();
            $('#progressmsgshow' + index + '').show();
        },
        updateProgress(index,value) {
            $('#progressmsgshow' + index + ' .progress').find('.progress-bar').css('width', `${value}%`)
            $('#progressmsgshow' + index + ' .progress').find('.progress-bar').html(`${value}%`)
        },
        hideProgress(index) {
            $('#progressmsgshow' + index + ' .progress').hide();
            $('#progressmsgshow' + index + '').hide();
        },

        changefont(fontname,event) {
            $('body').css('font-family',fontname);
            $('.fontoption').each(function(i, el) {
                $(el).removeClass('fontactive')
            });
           
            $(event.target).parent().addClass('fontactive');
            this.fontthemeactive = $(event.target).parent().attr('id');
        },
        reverthistory(revertkey) {
            if (revertkey == 'sidebar') {
                $('#sidebarcolor').val(this.sidebarcolor);
                $('.sidebar').css('background', this.sidebarcolor);
                $('head').append('<style>.sidebar:before{border-bottom-color:' + this.sidebarcolor + ' !important;}</style>');
                document.documentElement.style.setProperty('--bg-bar-color', this.sidebarcolor);
            }else if (revertkey == 'template') {
                $('#backgroundtemplatecolor').val(this.backgroundtemplatecolor);
                $('.main-panel').css('background',this.backgroundtemplatecolor);
            }
            // else if (revertkey == 'box') {
            //     $('#boxcolor').val(this.boxcolor);
            //     $('.card').css('background', this.boxcolor);
            //     $('.card-body').css('background', this.boxcolor);
            // }
            else if (revertkey == 'text') {
                $('#textcolor').val(this.textcolor);
                $('#cssGlobalTextColor').remove();
                $('head').append('<style id="cssGlobalTextColor">.sidebar-wrapper a span small, .sidebar-wrapper #sidebarCompanyName, .sidebar-menu-item p, .company-select-tag, .sidebar-normal {color:' + this.textcolor + ' !important;}</style>');
                document.documentElement.style.setProperty('--text-bar-color', this.textcolor);
            }else if (revertkey == 'link') {
                $('#linkcolor').val(this.linkcolor);
                $('#cssGlobalLinkColor').remove();
                $('head').append('<style id="cssGlobalLinkColor">body a, a span {color:' + this.linkcolor + ' !important;}</style>');
            }
        },
        get_agency_embeddedcode() {
            var _settingname = 'rootAgencyEmbeddedCode';

            this.$store.dispatch('getGeneralSetting', {
                companyID: this.userData.company_id,
                settingname: _settingname,
            }).then(response => {
                if (response.data != '') {
                    this.agencyEmbeddedCode.embeddedcode = response.data.embeddedcode;
                    this.agencyEmbeddedCode.placeEmbedded = response.data.placeEmbedded;
                }
            },error => {
                  
            });
        },
        get_smtp_setting() {
            var _settingname = 'customsmtpmenu';
            if (this.$global.systemUser) {
                _settingname = 'rootsmtp';
            }
            this.$store.dispatch('getGeneralSetting', {
                companyID: this.userData.company_id,
                settingname: _settingname,
            }).then(response => {
                if (response.data != '') {
                    this.customsmtp.default = response.data.default;
                    this.customsmtp.host = response.data.host;
                    this.customsmtp.port = response.data.port;
                    this.customsmtp.username = response.data.username;
                    this.customsmtp.password = response.data.password;
                    
                    if (typeof(response.data.security) == 'undefined') {
                        response.data.security = 'ssl';
                    }else if (response.data.security == null) {
                        response.data.security = "none";
                    }
                    this.customsmtp.security = response.data.security;
                }
            },error => {
                  
            });
        },
        test_email_content() {
            this.btnTestEmail = 'Sending test email...';
            this.isSendingTestEmail = true;
            // Data to be sent for testing the email
            var emailData = {
            fromAddress: (this.customsmtp.host) ? this.customsmtp.host :this.emailtemplate.fromAddress,
            fromName: (this.customsmtp.username) ? this.customsmtp.username :this.emailtemplate.fromName,
            fromReplyto: this.emailtemplate.fromReplyto,
            subject: this.emailtemplate.subject,
            content: this.emailtemplate.content,
            testEmailAddress: this.userData.email,
            companyID: this.userData.company_id,
            companyParentID: this.userData.company_parent,
            userType: this.userData.user_type
            };
            // Send request to backend to test email via Vuex store
            this.$store.dispatch('testEmail', emailData)
            .then(response => {
                this.$notify({
                type: 'success',
                message: 'Test email sent successfully',
                icon: 'far fa-check-circle'
                });
                this.isSendingTestEmail = false;
                this.btnTestEmail = 'Send Test Email';
            })
            .catch(error => {
                this.$notify({
                type: 'danger',
                message: 'Failed to send test email',
                icon: 'far fa-times-circle'
                });
                console.error(error);
                this.isSendingTestEmail = false;
                this.btnTestEmail = 'Send Test Email';
            });
        },

        save_email_content() {
            var templateName = this.emailupdatemodule
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'customsmtpmodule',
                comsetname: templateName,
                comsetval: this.emailtemplate,
            }).then(response => {
                if (response.result == "success") {
                    this.$notify({
                        type: 'success',
                        message: 'Setting has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  

                    this.modals.emailtemplate = false;
                }
            },error => {
                        
            });
        },
        isValidDomain(v) {
            if (!v) return false;
            var re = /^(?!:\/\/)([a-zA-Z0-9-]+\.){0,5}[a-zA-Z0-9-][a-zA-Z0-9-]+\.[a-zA-Z]{2,64}?$/gi;
            return re.test(v);
        },
        check_whitelabelling_fields() {
            var pass = true;
            if(this.chkagreewl == true) {
                if(this.DownlineDomain == "" || !this.isValidDomain(this.DownlineDomain)) {
                    pass = false;
                    //$('#dwdomain').parent().addClass('has-danger');
                    this.$notify({
                        type: 'danger',
                        message: 'Invalid domain name. Please enter a valid domain name.',
                        icon: 'tim-icons icon-bell-55'
                    });     
                }else{
                    $('#dwdomain').parent().removeClass('has-danger');
                }
            }
           
            /*if(this.chkagreewl == false) {
                pass = false;
                this.agreewhitelabelling = true;
            }else{
                this.agreewhitelabelling = false;
            }*/

            return pass;
        },
        save_general_whitelabelling() {
            if (this.check_whitelabelling_fields()) {
            //if (true) {
                this.chkagreewl = true;

                /** CHECK IF A RECORD POINTED */
                swal.fire({
                    title: '',
                    html: 'Have you followed the steps before saving it?<br/><small>* The process may take a few minutes. Please refresh the page to check its status.</small>',
                    icon: '',
                    showCancelButton: true,
                    customClass: {
                    confirmButton: 'btn btn-fill mr-3',
                    cancelButton: 'btn btn-danger btn-fill'
                    },
                    confirmButtonText: `Yes, I have`,
                    cancelButtonText: `No, I haven't`,
                    buttonsStyling: false
                }).then(result => {
                        if (result.isDismissed) {
                            return false;
                        }
                        else if (result.isConfirmed) {
                            /** PROCESS SAVE */
                            this.$store.dispatch('updateCustomDomain', {
                            companyID: this.userData.company_id,
                            DownlineDomain: this.DownlineDomain,
                            whitelabelling: this.chkagreewl,
                            }).then(response => {
                                if (response.result == "success") {
                                    var typecolor = ''
                                    if (response.activated == 'T') {
                                        typecolor = 'success';
                                        this.Whitelabellingstatus = true;
                                        this.chkagreewl = true;
                                        this.userData.whitelabelling = 'T';
                                    }else{
                                        typecolor = 'danger';
                                        this.Whitelabellingstatus = false;
                                        //this.chkagreewl = false;
                                        this.userData.whitelabelling = 'F';
                                    }

                                    this.userData.domain = response.domain;

                                    localStorage.setItem('userData',JSON.stringify(this.userData));

                                    this.$notify({
                                        type: typecolor,
                                        message: response.message,
                                        icon: 'tim-icons icon-bell-55'
                                    });  
                                }else{
                                    this.$notify({
                                        type: 'danger',
                                        message: response.message,
                                        icon: 'tim-icons icon-bell-55'
                                    });  
                                }
                            },error => {
                                        
                            });
                            /** PROCESS SAVE */
                        }
                });
                /** CHECK IF A RECORD POINTED */
            }

            return false;
        },
        save_general_agencyembeddedcode() {
            var _comsetname = 'rootAgencyEmbeddedCode';
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'customsmtpmodule',
                comsetname: _comsetname,
                comsetval: this.agencyEmbeddedCode,
            }).then(response => {
                if (response.result == "success") {
                    this.$notify({
                        type: 'success',
                        message: 'Setting has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  
                }
            },error => {
                        
            });
        },
        save_general_smtpemail() {
            var _comsetname = 'customsmtpmenu';
            if (this.$global.systemUser) {
                _comsetname = 'rootsmtp';
            }
            if(!this.customsmtp.host ||  !this.customsmtp.port || !this.customsmtp.username || !this.customsmtp.password){
                this.customsmtp.default = true
            }
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'customsmtpmodule',
                comsetname: _comsetname,
                comsetval: this.customsmtp,
            }).then(response => {
                if (response.result == "success") {
                    this.$notify({
                        type: 'success',
                        message: 'Setting has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  
                }
            },error => {
                        
            });
        },
        validateProductname(){
            if(!this.leadsLocalName || !this.leadsLocalUrl || !this.leadsLocatorName || !this.leadsLocatorUrl)	{
                this.$notify({
                        type: 'primary',
                        message: 'All fields are mandatory.',
                        icon: 'fas fa-bug'
                    });  
                return false 
            } else if(this.$global.globalModulNameLink.enhance.name != null && this.$global.globalModulNameLink.enhance.url != null) {
                if(!this.leadsEnhanceName || !this.leadsEnhanceUrl) {
                    this.$notify({
                        type: 'primary',
                        message: 'All fields are mandatory.',
                        icon: 'fas fa-bug'
                    });  
                    return false 
                }
                return true
            }
            else { 
                return true
            }
        },
        filterGlobalModulNameLink(obj) {
            // Destructuring untuk mendapatkan semua entri
            const { enhance, ...rest } = obj;

            // Mengembalikan objek tanpa entri enhance jika name dan url kosong
            return enhance.name === null && enhance.url === null ? rest : { ...rest, enhance };
        },
        save_general_custommenumodule() {
            if(!this.validateProductname()) return
            var _comsetname = 'customsidebarleadmenu';
            if (this.$global.systemUser) {
                _comsetname = 'rootcustomsidebarleadmenu';
            }

            let globalModulNameLink = { ...this.$global.globalModulNameLink };

            if(this.$global.globalModulNameLink.enhance.name == null || this.$global.globalModulNameLink.enhance.url == null) {
                globalModulNameLink = this.filterGlobalModulNameLink(globalModulNameLink);
            }
            
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'custommenumodule',
                comsetname: _comsetname,
                comsetval: globalModulNameLink,
            }).then(response => {
                //console.log(response.data.local.name);
                if (response.result == "success") {
                    
                    this.userData.leadlocalname = this.leadsLocalName;
                    this.userData.leadlocalurl = this.leadsLocalUrl;

                    this.userData.leadlocatorname = this.leadsLocatorName;
                    this.userData.leadlocatorurl = this.leadsLocatorUrl;
                    
                    this.userData.leadenhancename = this.leadsEnhanceName;
                    this.userData.leadenhanceurl = this.leadsEnhanceUrl;

                    localStorage.setItem('userData',JSON.stringify(this.userData));

                    this.$notify({
                        type: 'success',
                        message: 'Setting has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  
                    //window.location.reload(true);
                    this.$router.go(0);
                }
            },error => {
                        
            });
        },
        save_general_fontheme() {
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'fonttheme',
                fonttheme: this.fontthemeactive,
            }).then(response => {
                //console.log(response[0]);  
                this.fonttheme =  this.fontthemeactive;
                
                this.userData.font_theme = this.fonttheme;
                localStorage.setItem('userData', JSON.stringify(this.userData));

                this.$notify({
                    type: 'success',
                    message: 'Setting has been saved.',
                    icon: 'tim-icons icon-bell-55'
                });  
            },error => {
                        
            });
        },
        save_general_colortheme() {
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'colortheme',
                sidebarcolor: $('#sidebarcolor').val(),
                // templatecolor: $('#backgroundtemplatecolor').val(),
                // boxcolor: $('#boxcolor').val(),
                textcolor: $('#textcolor').val(),
                // linkcolor: $('#linkcolor').val(),
            }).then(response => {
                //console.log(response[0]);  
                this.sidebarcolor =  $('#sidebarcolor').val();
                // this.backgroundtemplatecolor = $('#backgroundtemplatecolor').val();
                // this.boxcolor = $('#boxcolor').val();
                this.textcolor = $('#textcolor').val();
                // this.linkcolor = $('#linkcolor').val();
                
                this.userData.sidebar_bgcolor = this.sidebarcolor;
                // this.userData.template_bgcolor = this.backgroundtemplatecolor;
                // this.userData.box_bgcolor = this.boxcolor;
                this.userData.text_color = this.textcolor;
                // this.userData.link_color = this.linkcolor;

                localStorage.setItem('userData', JSON.stringify(this.userData));

                this.$notify({
                    type: 'success',
                    message: 'Setting has been saved.',
                    icon: 'tim-icons icon-bell-55'
                });  
            },error => {
                        
            });
        },
        check_whitelabelling() {
            if (this.userData.user_type == 'userdownline' || this.userData.user_type == 'user') {
                this.DownlineDomain = this.userData.domain;
                this.DownlineSubDomain = this.userData.subdomain;
                if (this.userData.whitelabelling == 'F') {
                    //this.Whitelabellingstatus = false;
                    this.chkagreewl = false;
                }else{
                    //this.Whitelabellingstatus = true;
                    this.chkagreewl = true;
                }
                if (this.userData.status_domain == 'action_retry') {
                    this.domainSetupCompleted = false;
                    this.DownlineDomainStatus = 'Please add A record to our IP server.';
                }else if (this.userData.status_domain == 'action_check_manually') {
                    this.domainSetupCompleted = false;
                    this.DownlineDomainStatus = 'Need manually configuration please contact <a href="mailto:support@' + this.$global.companyrootdomain + '">support</a>';
                }else if (this.userData.status_domain == 'ssl_acquired') {
                    this.domainSetupCompleted = true;
                    this.DownlineDomainStatus = 'Domain Setup Completed.';
                }else{
                    this.domainSetupCompleted = false;
                    this.DownlineDomainStatus = 'Domain not setup yet.';
                }
            }

        },
        handleDefaultModule(type, value){
            const module = this.defaultModule && this.defaultModule.find(mod => mod.type === type)
            if(module){
                module.status = value;
            }
        },
        saveDefaultModule(){
            const module = this.defaultModule.map(({ name, icon, ...rest }) => rest);
            this.isLoadingSaveDefaultModule = true
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.userData.company_id,
                actionType: 'agencyDefaultModule',
                comsetval: module,
            }).then(response => {
                if (response.result == "success") {
                    this.$notify({
                        type: 'success',
                        message: 'Setting has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });
                    this.isLoadingSaveDefaultModule = false
                }
            },error => {
                this.$notify({
                        type: 'danger',
                        message: 'Something went wrong, please try again later',
                        icon: 'tim-icons icon-bell-55'
                });
                this.isLoadingSaveDefaultModule = false    
            });
        },
        cssDefaultModuleByLength(){
            const lengthDefaultModule = this.defaultModule.filter(module => module.name !== '' && module.name !== null);
            
            if (lengthDefaultModule.length == 1){
                return 'col-sm-12 col-md-12 col-lg-12'
            } else if (lengthDefaultModule.length == 2){
                return 'col-sm-12 col-md-6 col-lg-6'
            } else if (lengthDefaultModule.length == 3){
                return 'col-sm-6 col-md-4 col-lg-4'
            } else {
                return 'col-sm-6 col-md-4 col-lg-4'
            }
        }
    },
    mounted() {
        this.userData = this.$store.getters.userData;
        this.trialEndDate = new Date(this.userData.trial_end_date);
        this.defaultPaymentMethod = this.userData.paymentgateway;
        this.hasPassedFreeTrial();
        this.plannextbill = 'free';

        if (!this.$global.systemUser) {
            this.embeddedCodeTitle = 'Client';
        }
        this.selectsPaymentTerm.PaymentTerm = this.$global.rootpaymentterm;

        this.sidebarcolor = this.userData.sidebar_bgcolor;
        this.backgroundtemplatecolor = this.userData.template_bgcolor;
        this.boxcolor = this.userData.box_bgcolor;
        this.textcolor = this.userData.text_color;
        this.linkcolor = this.userData.link_color;
        
        this.images.login = (this.userData.login_image != '')?this.userData.login_image:this.images.login;
        this.images.register = (this.userData.client_register_image != '')?this.userData.client_register_image:this.images.register;
        this.images.agency = (this.userData.agency_register_image != '')?this.userData.agency_register_image:this.images.agency;
        this.logo.loginAndRegister = (this.userData.logo_login_register == null || this.userData.logo_login_register == '') ? this.logo.loginAndRegister : this.userData.logo_login_register;

        this.selectsPaymentTerm.PaymentTermSelect = (typeof(this.userData.paymentterm_default) != 'undefined' && this.userData.paymentterm_default != '')?this.userData.paymentterm_default:'Weekly';
        this.paymentTermStatus();

        const moduleAgency = this.$global.agencyDefaultModules
        const agencyFilterModules = this.$global.agencyfilteredmodules

        const defaultModule = this.defaultModule.map(module => {
            const agencyModule = moduleAgency && moduleAgency.find(agency => agency.type == module.type)
            if(agencyModule){
                const status = agencyModule.status !== undefined ? agencyModule.status : true
                return  {...module, status: status}
            } else {
                return module
            }
        })

        if(agencyFilterModules){
            const local = agencyFilterModules.local !== undefined ? agencyFilterModules.local.name : ''
            const locator = agencyFilterModules.locator !== undefined ? agencyFilterModules.locator.name : ''
            const enhance = agencyFilterModules.enhance !== undefined ? agencyFilterModules.enhance.name : ''
            
            defaultModule[0].name = local
            defaultModule[1].name = locator
            defaultModule[2].name = enhance
        }

        this.defaultModule = defaultModule

        $('#sidebarcolor').val(this.userData.sidebar_bgcolor);
        $('#backgroundtemplatecolor').val(this.userData.template_bgcolor);
        $('#boxcolor').val(this.userData.box_bgcolor);
        $('#textcolor').val(this.userData.text_color);
        $('#linkcolor').val(this.userData.link_color);

        if (this.userData.font_theme != '') {
            $('#' + this.userData.font_theme).addClass('fontactive');
        }else{
            $('#' + this.fonttheme).addClass('fontactive');
        }

        // Basic instantiation:
        $('#sidebarcolor').colorpicker({
            format: 'hex',
        });

        $('#backgroundtemplatecolor').colorpicker({
            format: 'hex',
        });

        $('#boxcolor').colorpicker({
            format: 'hex',
        });

        $('#textcolor').colorpicker({
            format: 'hex',
        });

        $('#linkcolor').colorpicker({
            format: 'hex',
        });
        

        $('#sidebarcolor').on('colorpickerChange', function(event) {
            $('.sidebar').css('background', event.color.toString());
            $('head').append('<style>.sidebar:before{border-bottom-color:' +  event.color.toString() + ' !important;}</style>');
            document.documentElement.style.setProperty('--bg-bar-color', event.color.toString());

        });

        $('#backgroundtemplatecolor').on('colorpickerChange', function(event) {
            $('.main-panel').css('background', event.color.toString());
            
        });

         $('#boxcolor').on('colorpickerChange', function(event) {
            $('.card').css('background', event.color.toString());
             $('.card-body').css('background',event.color.toString());
        });

        $('#textcolor').on('colorpickerChange', function(event) {
            $('#cssGlobalTextColor').remove();
            $('head').append('<style id="cssGlobalTextColor">.sidebar-wrapper a span small, .sidebar-wrapper #sidebarCompanyName, .sidebar-menu-item p, .company-select-tag, .sidebar-normal {color:' + event.color.toString() + ' !important;}</style>');
            document.documentElement.style.setProperty('--text-bar-color', event.color.toString());
        });

        $('#linkcolor').on('colorpickerChange', function(event) {
            $('#cssGlobalLinkColor').remove();
            $('head').append('<style id="cssGlobalLinkColor">body a, a span {color:' +  event.color.toString() + ' !important;}</style>');
        });
        
        /** PREPARE FOR UPLOAD RESUMABLE FILE */

        /** LOGO LOGIN AND REGISTER UPDATE */
        this.ruLogoLoginAndRegister = new Resumable({
            target: this.apiurl + '/file/upload',
            query:{
            newfilenameid:'_logologinandregister_',
            pkid:this.userData.company_id,
            uploadFolder:'users/images',
            } ,// CSRF token
            fileType: ['jpeg','jpg','png','gif'],
            headers: {
                'Accept' : 'application/json',
                'Authorization' : 'Bearer ' + localStorage.getItem('access_token'),
            },
            testChunks: false,
            throttleProgressCallbacks: 1,
            maxFileSize:5000000,
            maxFileSizeErrorCallback:function(file, errorCount) {
            filetolarge('',file,errorCount,'5000000');
            },
        });

        this.ruLogoLoginAndRegister.assignBrowse(this.$refs.browseFileLogoLoginAndRegister);
        
        this.ruLogoLoginAndRegister.on('fileAdded', (file, event) => { // trigger when file picked
            $('#progressmsgshow3 #progressmsg label').html('* Please wait while your image uploads. (It might take a couple of minutes.)');
            this.showProgress('3');
            this.ruLogoLoginAndRegister.upload() // to actually start uploading.
            
        });

        this.ruLogoLoginAndRegister.on('fileProgress', (file) => { // trigger when file progress update
            this.updateProgress('3',Math.floor(file.progress() * 100));
        });

        this.ruLogoLoginAndRegister.on('fileSuccess', (file, event) => { // trigger when file upload complete
            const response = JSON.parse(event);
            //console.log(response.path);
            this.logo.loginAndRegister = response.path;
            this.userData.logo_login_register = response.path;
            localStorage.setItem('userData', JSON.stringify(this.userData));
            this.hideProgress('3');
        });

        this.ruLogoLoginAndRegister.on('fileError', (file, event) => { // trigger when there is any error
            console.log('file uploading failed contact admin.');
        });
        

        this.hideProgress('3');
        /** LOGO LOGIN AND REGISTER UPDATE */

        /** LOGO SIDEBAR UPDATE */
        this.ruLogoSidebar = new Resumable({
            target: this.apiurl + '/file/upload',
            query:{
            newfilenameid:'_logophoto_',
            pkid:this.userData.company_id,
            uploadFolder:'users/images',
            } ,// CSRF token
            fileType: ['jpeg','jpg','png','gif'],
            headers: {
                'Accept' : 'application/json',
                'Authorization' : 'Bearer ' + localStorage.getItem('access_token'),
            },
            testChunks: false,
            throttleProgressCallbacks: 1,
            maxFileSize:5000000,
            maxFileSizeErrorCallback:function(file, errorCount) {
            filetolarge('',file,errorCount,'5000000');
            },
        });

        this.ruLogoSidebar.assignBrowse(this.$refs.browseFileLogoSidebar);
        
        this.ruLogoSidebar.on('fileAdded', (file, event) => { // trigger when file picked
            $('#progressmsgshow4 #progressmsg label').html('* Please wait while your image uploads. (It might take a couple of minutes.)');
            this.showProgress('4');
            this.ruLogoSidebar.upload() // to actually start uploading.
            
        });

        this.ruLogoSidebar.on('fileProgress', (file) => { // trigger when file progress update
            this.updateProgress('4',Math.floor(file.progress() * 100));
        });

        this.ruLogoSidebar.on('fileSuccess', (file, event) => { // trigger when file upload complete
            const response = JSON.parse(event);
            //console.log(response.path);
            this.logo.sidebar = response.path;
            this.userData.company_logo = response.path;
            document.getElementById('companylogosidebar').src = response.path;
            localStorage.setItem('userData', JSON.stringify(this.userData));
            this.hideProgress('4');
        });

        this.ruLogoSidebar.on('fileError', (file, event) => { // trigger when there is any error
            console.log('file uploading failed contact admin.');
        });
        

        this.hideProgress('4');
        /** LOGO SIDEBAR UPDATE */

        /** LOGIN IMAGE UPDATE */
        this.ru = new Resumable({
            target: this.apiurl + '/file/upload',
            query:{
              newfilenameid:'_loginphoto_',
              pkid:this.userData.company_id,
              uploadFolder:'users/images',
            } ,// CSRF token
            fileType: ['jpeg','jpg','png','gif'],
            headers: {
                'Accept' : 'application/json',
                'Authorization' : 'Bearer ' + localStorage.getItem('access_token'),
            },
            testChunks: false,
            throttleProgressCallbacks: 1,
            maxFileSize:5000000,
            maxFileSizeErrorCallback:function(file, errorCount) {
              filetolarge('',file,errorCount,'5000000');
            },
        });

        this.ru.assignBrowse(this.$refs.browseFileLogin);
        
        this.ru.on('fileAdded', (file, event) => { // trigger when file picked
            $('#progressmsgshow #progressmsg label').html('* Please wait while your image uploads. (It might take a couple of minutes.)');
            this.showProgress('');
            this.ru.upload() // to actually start uploading.
            
        });

        this.ru.on('fileProgress', (file) => { // trigger when file progress update
            this.updateProgress('',Math.floor(file.progress() * 100));
        });

        this.ru.on('fileSuccess', (file, event) => { // trigger when file upload complete
            const response = JSON.parse(event);
            //console.log(response.path);
            this.images.login = response.path;
            this.userData.login_image = response.path;
            localStorage.setItem('userData', JSON.stringify(this.userData));
            this.hideProgress('');
        });

        this.ru.on('fileError', (file, event) => { // trigger when there is any error
            console.log('file uploading failed contact admin.');
        });
  
        this.hideProgress('');
        /** LOGIN IMAGE UPDATE */

        /** REGISTER IMAGE UPDATE */
        if (!this.$global.systemUser) {
            this.ru1 = new Resumable({
                target: this.apiurl + '/file/upload',
                query:{
                newfilenameid:'_registerphoto_',
                pkid:this.userData.company_id,
                uploadFolder:'users/images',
                } ,// CSRF token
                fileType: ['jpeg','jpg','png','gif'],
                headers: {
                    'Accept' : 'application/json',
                    'Authorization' : 'Bearer ' + localStorage.getItem('access_token'),
                },
                testChunks: false,
                throttleProgressCallbacks: 1,
                maxFileSize:5000000,
                maxFileSizeErrorCallback:function(file, errorCount) {
                filetolarge('',file,errorCount,'5000000');
                },
            });

            this.ru1.assignBrowse(this.$refs.browseFileRegister);
            
            this.ru1.on('fileAdded', (file, event) => { // trigger when file picked
                $('#progressmsgshow1 #progressmsg label').html('* Please wait while your image uploads. (It might take a couple of minutes.)');
                this.showProgress('1');
                this.ru1.upload() // to actually start uploading.
                
            });

            this.ru1.on('fileProgress', (file) => { // trigger when file progress update
                this.updateProgress('1',Math.floor(file.progress() * 100));
            });

            this.ru1.on('fileSuccess', (file, event) => { // trigger when file upload complete
                const response = JSON.parse(event);
                console.log(response.path);
                this.images.register = response.path;
                this.userData.client_register_image = response.path;
                localStorage.setItem('userData', JSON.stringify(this.userData));
                this.hideProgress('1');
            });

            this.ru1.on('fileError', (file, event) => { // trigger when there is any error
                console.log('file uploading failed contact admin.');
            });
    
            this.hideProgress('1');
        }
        /** REGISTER IMAGE UPDATE */

        /** AGENCY IMAGE UPDATE */
        if (this.$global.systemUser) {
            this.ru2 = new Resumable({
                target: this.apiurl + '/file/upload',
                query:{
                newfilenameid:'_agencyphoto_',
                pkid:this.userData.company_id,
                uploadFolder:'users/images',
                } ,// CSRF token
                fileType: ['jpeg','jpg','png','gif'],
                headers: {
                    'Accept' : 'application/json',
                    'Authorization' : 'Bearer ' + localStorage.getItem('access_token'),
                },
                testChunks: false,
                throttleProgressCallbacks: 1,
                maxFileSize:5000000,
                maxFileSizeErrorCallback:function(file, errorCount) {
                filetolarge('',file,errorCount,'5000000');
                },
            });

            this.ru2.assignBrowse(this.$refs.browseFileAgency);
            
            this.ru2.on('fileAdded', (file, event) => { // trigger when file picked
                $('#progressmsgshow2 #progressmsg label').html('* Please wait while your image uploads. (It might take a couple of minutes.)');
                this.showProgress('2');
                this.ru2.upload() // to actually start uploading.
                
            });

            this.ru2.on('fileProgress', (file) => { // trigger when file progress update
                this.updateProgress('2',Math.floor(file.progress() * 100));
            });

            this.ru2.on('fileSuccess', (file, event) => { // trigger when file upload complete
                const response = JSON.parse(event);
                //console.log(response.path);
                this.images.agency = response.path;
                this.userData.agency_register_image = response.path;
                localStorage.setItem('userData', JSON.stringify(this.userData));
                this.hideProgress('2');
            });

            this.ru2.on('fileError', (file, event) => { // trigger when there is any error
                console.log('file uploading failed contact admin.');
            });
            
    
            this.hideProgress('2');
        }
        /** AGENCY IMAGE UPDATE */

        /** PREPARE FOR UPLOAD RESUMABLE FILE */

        /** CHECK GOOGLE CONNECT */
        this.checkGoogleConnect();
        /** CHECK GOOGLE CONNECT */

        /** GET EMAIL CONFIGURATION */
        this.get_smtp_setting();
        /** GET EMAIL CONFIGURATION */

        /** GET AGENCY EMBEDDED CODE */
        this.get_agency_embeddedcode();
        /** GET EMAIL CONFIGURATION */

        /** FOR REFRESH URL CONNECTED STRIPE*/
        if (process.env.VUE_APP_DEVMODE == 'true') {
            // this.refreshURL = 'http://' + window.location.hostname + ':8080' +  this.refreshURL;
            // this.returnURL = 'http://' + window.location.hostname + ':8080' + this.returnURL;
            this.refreshURL = 'https://' + window.location.hostname +  this.refreshURL;
            this.returnURL = 'https://' + window.location.hostname + this.returnURL;
        }else{
            this.refreshURL = 'https://' + window.location.hostname  +  this.refreshURL;
            this.returnURL = 'https://' + window.location.hostname + this.returnURL;
        }
        /** FOR REFRESH URL CONNECTED STRIPE*/

        /** CHECK CONNECTED ACCOUNT */
        if (!this.$global.systemUser) {
            this.checkConnectedAccount();
        }
        /** CHECK CONNECTED ACCOUNT */

        /** FOR INITIAL PACKAGE PLAN */
        if (!this.$global.systemUser) {
            this.getAgencyPlanPrice();
        }
        /** FOR INITIAL PACKAGE PLAN */

        /** FOR WHITE LABELLING */
        if (!this.$global.systemUser) {
            this.check_whitelabelling();
        }
        /** FOR WHITE LABELLING */

        /** INITIALLY DEFAULT PRICE */
        //if (!this.$global.systemUser) {
            this.initial_default_price();
        //}
        /** INITIALLY DEFAULT PRICE */
        
    }
};

function formatSize(size){
      if(size<1024) {
        return size + ' bytes';
      } else if(size<1024*1024) {
        return (size/1024.0).toFixed(0) + ' KB';
      } else if(size<1024*1024*1024) {
        return (size/1024.0/1024.0).toFixed(1) + ' MB';
      } else {
        return (size/1024.0/1024.0/1024.0).toFixed(1) + ' GB';
      }
}

function filetolarge(index,file,errorCount,filesize) {
      $('#progressmsgshow' + index + ' #progressmsg label').html(file.fileName||file.name +' is too large, please upload files less than ' + formatSize(filesize) + '.');
      $('#progressmsgshow' + index).show();
}

</script>
<style>
.radio-inactive {
    opacity: 0.5;
}

.radio-inactive:hover {
    opacity: 0.8;
}

.fontoption.fontactive {
    border: 1px solid  var(--info) !important;
}
.fontoption {
    border: 1px solid  var(--gray-dark) !important;
}

.modal .form-control {
    color: #222a42;
}

.email-template-item {
    padding-bottom: 5px;
    font-weight: 300;
    font-size: 0.9rem;
}
.pricing-setting-item-toggle{
    cursor: pointer;
    flex: 1;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid transparent;
    transition: all 0.2s ease;
}
.pricing-setting-item-toggle h5{
    margin:0;
}
.pricing-setting-item-toggle.--active{
    border: 1px solid var(--input-border-color);
}
.leadspeek-pricing-setting-form-wrapper{
    display:flex;
    align-items:baseline;
    gap:16px;
}
.price-setting-form-item .form-group{
    width: 100% !important;
    text-align:left;
}
.pricing-duration-dropdown-wrapper{
    display: flex;
    text-align: left;
    flex-direction: column;
    margin-bottom: 24px;
}

.product__default__module {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px solid var(--muted);
    border-radius: 8px;
    padding: 16px;
    transition: border 0.3s ease;
}

.default__module__text {
    opacity: 0.5;
    transition: opacity 0.3s ease;
}

.active__default__module__text {
    font-weight: 600;
    opacity: 1;
}

.active__default__module {
    border: 2px solid var(--input-border-color);
}

.general-setting-side-nav .--active{
font-weight: bold;
}
.general-setting-side-nav .nav-link{
    cursor: pointer;
    transition: all 0.3s ease;
}
.general-setting-side-nav .nav-link:hover{
    font-weight: bold;
    color: var(--primary-color);
}

</style>