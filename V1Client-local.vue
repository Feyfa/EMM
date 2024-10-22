<template>
    <div style="margin-top:-25px">
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12 text-left">
                
                <h2 style="font-size: 40px; font-weight: 700; line-height: 48px; " class="mb-0 mt-2">Campaign Management</h2>
            </div>
         
        </div>
        <div class="pt-3 pb-3">&nbsp;</div>

        <div class="row" id="processingArea">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <!-- <card> -->

                    <div class="d-flex flex-wrap" style="justify-content: space-between; gap:8px;">
                        <div class="d-flex align-items-center flex-wrap small-width-full" style="column-gap:8px;row-gap:20px">

                            <div class='campaign-filter-item-wrapper'>
                                        <span style="opacity:0.3">Showing</span>
                                        <span v-for="(filter, index) in optionCampaignStatus" @click="applyFilter(filter)" :class="['--filter-item',{'--active':filterCampaignStatus === filter.value}]" :key='index'>{{filter.label}}</span>
                                        <!-- <span class="--filter-item">Running</span>
                                        <span class="--filter-item">Paused</span>
                                        <span class="--filter-item">Stopped</span> -->
                                        <!-- <span class="--filter-item">Archived</span> -->
                            </div>
                            <div class="container__input__campaign__management">
                                <base-input class="mb-0">
                                    <el-input type="search" class="search-input input__campaign__management" clearable prefix-icon="el-icon-search"
                                        placeholder="Search" v-model="searchQuery" aria-controls="datatables" @keyup.native.enter="searchKeyWord" @clear="searchKeyWord">
                                    </el-input>
                                </base-input>
                            </div>
                            <SelectClientDropdown></SelectClientDropdown>
                        
                          
                        </div>
                        <div class="container__filter__campaign__management">
                            <div>
                               
                                <base-button size="sm" style="height:40px" class="button__filter__campaign__management" @click="AddEditClient('')"
                                    v-if="this.$global.menuLeadsPeek_create || (userType == 'client' && disabledaddcampaign && (this.$store.getters.userData.manual_bill == 'F' || this.$store.getters.userData.company_parent == '1251'))">
                                    <i class="fas fa-plus-circle"></i> Add Campaign
                                </base-button>
                            </div>
                        </div>
                    </div>

                    <transition name="fade">
                        <div v-if="fetchingCampaignData" >
                            <div style="min-height: 40vh; display: flex; justify-content: center; align-items: center;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 40px;"></i>
                            </div>
                        </div>
                        <div v-else class='mt-3 mt-md-5 d-flex flex-column campaigns__item-wrapper' style='gap: 16px' >
                            <card   class="mb-0" v-for="(campaign,index) in queriedData" :key="campaign.leadspeek_api_id">
                                <div class='d-flex justify-content-between align-items-center flex-wrap cursor-pointer '>
                                    <div class='campaign-card-title-info-wrapper' @click.stop>
                                        <!-- <span class="card-icon-styling" :style='{backgroundColor:colorOptions[(index * 7) % 4 + 1].background}'>
                                            <i class="fa-solid fa-bullhorn" :style="{color:colorOptions[(index * 7) % 4 + 1].color}"></i>
                                        </span> -->
                                        <div class='align-items-center d-flex flex-wrap' style='gap:16px'>
                                            <div class='campaign-card-title-info'>
                                                <span class='campaign-card-client-name'>{{campaign.company_name}}</span>
                                                <span class="campain-card-name d-flex justify-content-between"><span class='campaign-name-ellips'>{{campaign.campaign_name}} </span> <span class='arrows'><i class="fa-solid fa-arrow-up" :style="{opacity: currOrderBy == 'ascending' ? 0.3 : 0.6}" @click.stop="sortdynamic('campaign_name','ascending')"></i><i :style="{opacity: currOrderBy == 'descending' ? 0.3 : 0.6}" class="fa-solid fa-arrow-down" @click.stop="sortdynamic('campaign_name','descending')"></i></span></span>
                                                <span v-if="campaign.last_lead_added" class='campaign-card-update-date'>Last updated on {{campaign.last_lead_added}}</span>
                                            </div>
                                            <div v-if="campaign.disabled == 'F' && campaign.active_user == 'T'" class='campaign-card-status-badge' style="background:rgb(0, 81, 97);color:white"><span >Running</span></div>
                                            <div v-if="campaign.disabled == 'T' && campaign.active_user == 'T'" class='campaign-card-status-badge' style="background:#fdae61"><span>Paused</span></div>
                                            <div v-if="campaign.active_user == 'F'" class='campaign-card-status-badge' style="background:rgb(147, 2, 30);color:white"><span >Stopped</span></div>
                                        
                                        </div>

                                    </div>
                                    <div class='d-flex justify-content-between align-items-center flex-wrap campaign-card-right-wrapper'>

                                        <div class='campaign-card-stat-wrapper'>
                                            <div class='campaign-card-stat-block'>
                                                <span class='--stat-block-title'>ID</span>
                                                <span class='--stat-block-value'>{{campaign.leadspeek_api_id}}</span>
                                                <span v-if="$global.globalviewmode && userTypeOri != 'sales' && $global.rootcomp">
                                                <span v-if="campaign.leadspeek_api_id != ''" class="pl-2"><a
                                                            :href="'/configuration/report-analytics/?campaignid=' + campaign.leadspeek_api_id + ''"
                                                            target="_blank"><i class="fas fa-file-chart-line"
                                                                style="color:gray"></i></a></span>
                                                </span>
                                            </div>
                                            <div class='campaign-card-stat-block'>
                                                <span class='--stat-block-title'>Total</span>
                                                <span class='--stat-block-value'>{{campaign.total_leads}}</span>
                                            </div>
                                            <div class='campaign-card-stat-block'>
                                                <span class='--stat-block-title'>Yesterday's</span>
                                                <span class='--stat-block-value'> <span :style="{ color: campaign.yerterday_leads > 1.1 * campaign.yerterday_previous_leads ? 'green' : campaign.yerterday_leads < 0.9 * campaign.yerterday_previous_leads ? 'red' : '' }">{{campaign.yerterday_leads}}</span></span>
                                            </div>
                                        </div>
                                        <div class='campaign-card-actions-block --site-id flex-column'>
                                            <div class='campaign-card-actions-block --site-id'>
                                                <el-tooltip
                                                        content="Edit This Campaign"
                                                        effect="light"
                                                        :open-delay="300"
                                                        placement="top"
                                                    >
                                                    <span @click='rowClicked(index, campaign)' class="card-action-icon-wrapper">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip content="View Embed Code" effect="light" :open-delay="300" placement="top">
                                                    <span @click.stop="handleEmbededCode(index, campaign)" class="card-action-icon-wrapper">
                                                        <i class="fas fa-code"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip content="Campaign Financials" effect="light" :open-delay="300" placement="top">
                                                    <span @click.stop="handlePriceSet(index, campaign)" class="card-action-icon-wrapper">
                                                        <i class="fa-solid fa-dollar-sign"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip effect="light" :open-delay="300" placement="top" content="Configure integrations">
                                                    <span @click.stop="handleIntegrationClick(index, campaign)" class="card-action-icon-wrapper">
                                                        <i  class="fas fa-plug"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip
                                                            v-if="!$global.globalviewmode"
                                                            content="Go to Google Sheet"
                                                            effect="light"
                                                            :open-delay="300"
                                                            placement="top"
                                                        >
                                                        <span class="card-action-icon-wrapper">
                                                            <a :href="'https://docs.google.com/spreadsheets/d/' + campaign.spreadsheet_id + '/edit#gid=0'" target="_blank"><i class="fab fa-google-drive"></i></a>
                                                        </span>    
                                                </el-tooltip>
                                                <el-tooltip
                                                    v-if="$global.globalviewmode"
                                                    :content="(campaign.spreadsheet_id === '') ? 'Download Excel':'Go to Google Sheet'"
                                                    effect="light"
                                                    :open-delay="300"
                                                    placement="top"
                                                >
                                                    <span class="card-action-icon-wrapper">
                                                        <a v-if="campaign.spreadsheet_id != ''" :href="'https://docs.google.com/spreadsheets/d/' + campaign.spreadsheet_id + '/edit#gid=0'" target="_blank"><i class="fab fa-google-drive"></i></a>
                                                        <i v-else class="fas fa-file-excel pl-2 pr-2" @click.stop="ExportLeadsData(index, campaign)"></i>
                                                        
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip v-show="campaign.payment_status != 'failed' &&  ( campaign.active_user == 'F' || (campaign.disabled == 'T' && campaign.active_user == 'T'))" :content="tooltip_campaign(index, campaign, 'play')" effect="light" :open-delay="300" placement="top">
                                                    <span @click.stop="activepauseclient(index, campaign, 'play')" class="card-action-icon-wrapper">
                                                        <i class="fas fa-play"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip v-show="(campaign.disabled == 'F' && campaign.active_user == 'T')" :content="tooltip_campaign(index, campaign, 'pause')" effect="light" :open-delay="300" placement="top">
                                                    <span :class="{'cursornodrop': campaign.active_user == 'F' && (campaign.customer_card_id == '' && $store.getters.userData.manual_bill == 'F')}"  @click.stop="activepauseclient(index, campaign, 'pause')" class="card-action-icon-wrapper">
                                                        <i class="fas fa-pause"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip v-show="(campaign.disabled == 'F' && campaign.active_user == 'T') || (campaign.disabled == 'T' && campaign.active_user == 'T')" :content="tooltip_campaign(index, campaign, 'stop')" effect="light" :open-delay="300" placement="top">
                                                    <span @click.stop="handleDelete(index, campaign)" class="card-action-icon-wrapper">
                                                        <i class="fas fa-stop"></i>
                                                    </span>
                                                </el-tooltip>
                                            </div>
                                            <!-- <div class='campaign-card-actions-block'>
                                                <el-tooltip content="View Embed Code" effect="light" :open-delay="300" placement="top">
                                                    <span @click="handleEmbededCode(index, campaign)" class="card-action-icon-wrapper">
                                                        <i class="fas fa-pencil"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip content="View Embed Code" effect="light" :open-delay="300" placement="top">
                                                    <span @click="handleEmbededCode(index, campaign)" class="card-action-icon-wrapper">
                                                        <i class="fas fa-pause"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip content="View Embed Code" effect="light" :open-delay="300" placement="top">
                                                    <span @click="handleEmbededCode(index, campaign)" class="card-action-icon-wrapper">
                                                        <i class="fas fa-play"></i>
                                                    </span>
                                                </el-tooltip>
                                                <el-tooltip content="View Embed Code" effect="light" :open-delay="300" placement="top">
                                                    <span @click="handleEmbededCode(index, campaign)" class="card-action-icon-wrapper">
                                                        <i class="fas fa-stop"></i>
                                                    </span>
                                                </el-tooltip>
                                            </div> -->
                                            
                                        </div>
                                        <div @click.stop>
                                            <el-dropdown trigger="click">
                                                <span >
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </span>
                                                <el-dropdown-menu slot="dropdown" >
                                                    <div @click.stop='rowClicked(index, campaign)'>
                                                        <el-dropdown-item >Edit</el-dropdown-item>
                                                    </div>
                                                    <!-- <div v-if="campaign.disabled == 'T' && campaign.active_user == 'T'" @click.stop="activepauseclient(index, campaign, 'play')">
                                                        <el-dropdown-item>Play</el-dropdown-item>
                                                    </div>
                                                    <div v-if="campaign.disabled == 'F' && campaign.active_user == 'T'" @click.stop="activepauseclient(index, campaign, 'pause')">
                                                        <el-dropdown-item >Pause</el-dropdown-item>
                                                    </div>
                                                    <div v-if="campaign.active_user != 'F'" @click.stop="handleDelete(index, campaign)">
                                                        <el-dropdown-item>Stop</el-dropdown-item>
                                                    </div> -->
                                                    <div @click.stop="archiveCampaign(index, campaign)">
                                                        <el-dropdown-item>Archive</el-dropdown-item>
                                                    </div>
                                                    <div @click.stop="showWhitelist(index, campaign)">
                                                        <el-dropdown-item>Exclusion</el-dropdown-item>
                                                    </div>
                                                </el-dropdown-menu>
                                            </el-dropdown>
                                        </div>
                                    </div>
                                </div>
                            </card> 
                            <div class="align-items-center d-flex justify-content-center text-center" style="height: 200px;"v-if='queriedData.length <= 0'>
                                No campaigns found 
                            </div>  
                        </div>
                    </transition>

                 

                    <template >
                        <div class="tab-footer pull-right">
                            <div class="pt-3">
                                <p class="card-category">
                                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                                </p>
                            </div>
                            <base-pagination class="pagination-no-border pt-4"
                            v-model="pagination.currentPage"
                                        :per-page="pagination.perPage"
                                        :total="pagination.total"
                                        @input="changePage"
                                 >
                            </base-pagination>
                        </div>
                    </template>

                <!-- </card> -->
            </div>
        </div>

        <!-- Modal LeadSpeek Embedded Code -->
        <modal :show.sync="modals.embededcode" headerClasses="justify-content-center" >
            <h4 slot="header" class="title title-up">Client Embed Code</h4>
            <p class="text-center">
                This embed code is to be placed in the Header of your client's site.
                <!--The short code is a universal container that will not need manually updating as we will handle any needed code updates dynamically.-->
            </p>
            <div class="text-center" v-if="false">
                <textarea rows="1" cols="70"
                    id="universalembededcode"><script async src='https://tag.exactmatchmarketing.com/'></script></textarea>
                <div>
                    <a href="javascript:void(0);" id="universalembededcodeLink" class="far fa-clipboard"
                        @click="copytoclipboard('universalembededcode')"> Copy</a>
                </div>
            </div>
            <p class="text-center">
                The full code below for custom placements of the {{ leadlocalname }}.
            </p>
            <div class="text-center">
                <textarea rows="10" cols="70" id="leadspeekembededcode" class='w-100 w-md-auto'>

                                  </textarea>
                <div>
                    <a href="javascript:void(0);" id="leadspeekembededcodeLink" class="far fa-clipboard"
                        @click="copytoclipboard('leadspeekembededcode')"> Copy</a>
                </div>
            </div>
            <div id="suppressioncode">
                <p class="text-center">
                    The below code is your client's Suppression code. This code is usually placed on a page where a lead can
                    already be identified as part of the customer journey, such as a check out page, or an email signup
                    confirmation page.
                </p>
                <div class="text-center">
                    <textarea rows="2" cols="90" id="leadspeekembededcodesupression">

                                        </textarea>
                    <div>
                        <a href="javascript:void(0);" id="leadspeekembededcodesupressionLink" class="far fa-clipboard"
                            @click="copytoclipboard('leadspeekembededcodesupression')"> Copy</a>
                    </div>
                </div>
            </div>
            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button @click.native="modals.embededcode = false">Close Window</base-button>
                </div>
            </template>
        </modal>
        <!-- Modal LeadSpeek Embedded Code -->

        <!-- Modal General Information -->
        <modal :show.sync="modals.helpguide" headerClasses="justify-content-center" >
            <h4 slot="header" class="title title-up" v-html="modals.helpguideTitle"></h4>
            <p class="text-center" v-html="modals.helpguideTxt">
            </p>

            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button @click.native="modals.helpguide = false">Close</base-button>
                </div>
            </template>
        </modal>
        <!-- Modal General Information -->

        <!-- QUESTIONNAIRE RESULT MODAL -->
        <modal id="modalQuestionnaire" :show.sync="modals.questionnaire" headerClasses="justify-content-center"
            >
            <h4 slot="header" class="title title-up">Questionnaire result for : <span style="color:#d42e66">{{
                LeadspeekCompany }}</span></h4>

            <div class="col-sm-12 col-md-12 col-lg-12">
                <p class="text-center"><strong>{{ leadlocalname }} Questionnaire</strong></p>
            </div>
            <div style="height:10px">&nbsp;</div>
            <p>- What is your campaign name?</p>
            <p class="qanswer pl-2">{{ questionnaire.campaign_name }}</p>

            <p>- What is your website address where you want to identify visitors and generate leads?</p>
            <p class="qanswer pl-2" v-html="questionnaire.url_code">&nbsp;</p>

            <p>- The Privacy Policy on your website needs to have the proper disclosure verbiage in it to make it legal. We
                recommend you check your policy and speak with your legal council to insure something like the following:
                &quot;We collect information about our users via cookies and use the information primarily to provide you
                with a personalized Internet experience that delivers the information, resources, and services that are most
                relevant and helpful to you. To opt out, click here.&quot; Include a link to opt-out or an email address for
                them to do so.</p>
            <p class="qanswer pl-2" v-html="questionnaire.asec3_2">&nbsp;</p>
            <p v-if="false">- I agree that it is up to me to understand the law regarding collecting third party information
                via my website and will not hold Exact Match Marketing/Uncommon Reach accountable for any issues that may
                arise.</p>
            <p v-if="false" class="qanswer pl-2" v-html="questionnaire.asec3_3">&nbsp;</p>


            <div style="height:10px">&nbsp;</div>
            <div v-if="questionnaire.asec6_3 != '' || questionnaire.asec6_4 != '' || questionnaire.asec6_6 != ''">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <p class="text-center"><strong>Information for all campaign options</strong></p>
                    <p class="text-center"><small style="font-size:12px">All questions must be answered below.</small></p>
                </div>
                <div style="height:40px">&nbsp;</div>

                <p>- Besides yourself, please provide any additional email addresses (separated by a comma) of those you
                    wish to also have access to the leads information sheet.</p>
                <p class="qanswer pl-2" v-html="questionnaire.asec6_3">&nbsp;</p>
                <p>- You can link the Google Sheet(s) to your CRM. If you want this to happen, do you know how to do it or
                    will you need our help to set it up?</p>
                <p class="qanswer pl-2" v-html="questionnaire.asec6_4">&nbsp;</p>

                <p>- Note: The leads captured will have been filled out by the customer via an opt-in form. We have no
                    control over what they entered or if their personal information has changed from the time they completed
                    the form to now. (For example, they may have moved, gotten married and changed their last name, etc.) By
                    law, you are required to be CAN-SPAM compliant if you reach out via e-mail.</p>
                <p class="qanswer pl-2" v-html="questionnaire.asec6_6">&nbsp;</p>
            </div>


            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button @click.native="modals.questionnaire = false">Close</base-button>
                </div>
            </template>
        </modal>
        <!-- QUESTIONNAIRE RESULT MODAL -->

        <!-- Modal Setting Markup -->
        <modal id="modalSetPrice" :show.sync="modals.pricesetup" headerClasses="justify-content-center" bodyClasses='create-campaign-modal-body'
    >
            <h4 slot="header" class="title title-up">Campaign Financials For: <span style="color:#000000">{{
                LeadspeekCompany }}</span></h4>
            <div style="display:none">
                <!--<iframe width="970" height="415" src="https://www.youtube.com/embed/SCSDyqRP7cY" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>-->
            </div>
            <div class="row" v-if="userType != 'client'">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <div class="d-inline-block pr-4" v-if="false">
                        <label>Select Modules:</label>
                        <el-select class="select-primary" size="large" placeholder="Select Modules"
                            v-model="selectsAppModule.AppModuleSelect" style="padding-left:10px">
                            <el-option v-for="option in selectsAppModule.AppModule" class="select-primary"
                                :value="option.value" :label="option.label" :key="option.label">
                            </el-option>
                        </el-select>
                    </div>
                    <div class="d-flex flex-column" style="margin-bottom:16px;">
                        <span class="client-payment-modal-form-label" style="color:#222a42">Billing Frequency</span>
                        <el-select class="select-primary" size="large" placeholder="Select Modules"
                            v-model="selectsPaymentTerm.PaymentTermSelect"
                            @change="paymentTermChange()" :disabled="LeadspeekInputReadOnly || OnlyPrepaidDisabled">
                            <el-option v-for="option in selectsPaymentTerm.PaymentTerm" class="select-primary"
                                :value="option.value" :label="option.label" :key="option.label">
                            </el-option>
                        </el-select>
                    </div>
                </div>
            </div>
     
            <div v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid'" class="mb-4 p-0">
                <div class="col-12 p-0">
                    <p>Your remaining balance is: <b>{{ remainingBalanceLeads }}</b> leads</p>
                </div>
                <div class="col-12 p-0 mt-3 d-flex justify-content-center">
                    <p class="text-center client-payment-modal-form-label" style="color:#222a42; font-size: 22px; font-weight: 700">Top Up Options</p>
                </div>
                <div class="col-12 p-0 mt-3">
                    <el-tabs type="border-card" v-model="prepaidType">
                        <el-tab-pane name="continual" :disabled="prepaidType !== 'continual' && OnlyPrepaidDisabled" :style="prepaidType !== 'continual' && OnlyPrepaidDisabled ?  'cursor: not-allowed' : ''">
                            <span slot="label">
                                        Continual Top Up
                                        <el-badge v-if="prepaidType == 'continual'" value="Active" type="success" class="item ml-3">
                                            <span></span>
                                        </el-badge>
                            </span>
                            <div class="row">
                                <div class="col-12 mb-4">
                                    <p style="font-size: 14px; color: #6b7280;"> <span style="font-weight: 600;">Continual top up</span> will automatically top up your pool of pre-purchased leads when it gets low. This will keep your campaign running until stopped. </p>
                                    
                                </div>
                                <div class="col-12 col-md-6">
                                    <el-switch
                                        v-model="contiDurationSelection"
                                        active-text="Monthly"
                                        inactive-text="Weekly"
                                        @change='updateWeeklyMonthlyToggle'
                                        >
                                    </el-switch>
                                    <!-- <p>{{contiDurationSelection ? 'Monthly' : 'Weekly'}} leads count will be: {{ contiDurationSelection ? (LeadspeekLimitLead * 7 ) * 4 : (LeadspeekLimitLead * 7)  }}</p> -->
                                    <p v-if="prepaidType == 'continual'">Number of leads to buy: <b>{{totalLeads.continualTopUp  }}</b> lead(S)</p>     
                                </div>
                                <div class="col-12 col-md-6" v-if="prepaidType == 'continual'">
                                    <span class="client-payment-modal-form-label" style="color:#222a42">Automatic top up will occur when your pre-purchased lead amount drops below {{ LeadspeekLimitLead }}</span>
                                   
                                </div>
                                <div class="col-12 mt-2" v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && prepaidType == 'continual'">
                                    <base-button 
                                        class="bg-danger text-white" 
                                        @click="stopContinualTopUp" 
                                        :disabled="isContainulTopUpStop" 
                                        :class="{'bg-light': isContainulTopUpStop}">
                                        Stop Continual Top Up
                                    </base-button>
                                </div>
                            </div>
                        </el-tab-pane>
                        <el-tab-pane name="onetime" :disabled="prepaidType !== 'onetime' && OnlyPrepaidDisabled" :style="prepaidType !== 'onetime' && OnlyPrepaidDisabled ? 'cursor: not-allowed' : ''">
                            <span slot="label">
                                One Time
                                        <el-badge v-if="prepaidType == 'onetime'" value="Active" type="success" class="item ml-3">
                                            <span></span>
                                        </el-badge>
                            </span>
                            <div class="row">
                                <div class="col-12 mb-4">
                                    <p style="font-size: 14px; color: #6b7280;"> <span style="font-weight: 600;">One time</span> campaigns will run until the number of purchased leads have been used. You may manually add additional leads to these campaigns as desired.</p>
                                </div>
                                <div class="col-12" v-if="prepaidType == 'onetime'">     
                                    <div class="d-flex justify-content-start align-items-center">
                                        <p>Number of leads to buy:</p>
                                        <base-input 
                                            type="text" 
                                            placeholder="Minimum 50 leads" 
                                            class="campaign-cost-input mx-1"
                                            v-model="totalLeads.oneTime"
                                            @input="validationLeadToBuy"
                                            @keydown="event => restrictInput(event,'integer')">
                                        </base-input>
                                        <p>lead(s)</p>
                                    </div>
                                    <span v-if="err_totalleads" style="font-size: 13px; color: #942434;">* Minimum 50 lead</span>
                                </div>
                            </div>
                        </el-tab-pane>
                    </el-tabs>
                </div>
            </div>
            <div v-if="selectsAppModule.AppModuleSelect == 'LeadsPeek'">
                <div class="client-payment-setup-form-wrapper">

          <div class="d-flex flex-wrap flex-md-nowrap" style="gap:16px;">
              <div class='w-100'  v-if="userType != 'client'">
                  <div class="client-payment-modal-form-label">
                      Setup Fee
                  </div>
                  <div>
                      <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                          class="campaign-cost-input" v-model="LeadspeekPlatformFee"
                          :readonly="LeadspeekInputReadOnly" @keyup="profitcalculation();" @keydown="restrictInput">
                      </base-input>
                  </div>
                  <div class="client-payment-modal-form-helper-text"><span>Your base price for One Time
                          Creative/Set Up Fee is ${{ m_LeadspeekPlatformFee }}</span></div>
              </div>

              <div class='w-100'  v-if="userType != 'client'">
                  <div class="client-payment-modal-form-label">
                      Campaign Fee <span v-html="txtLeadService">per month</span>
                  </div>
                  <div>
                      <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                          class="campaign-cost-input" v-model="LeadspeekMinCostMonth"
                          :readonly="LeadspeekInputReadOnly" @keyup="profitcalculation();" @keydown="restrictInput">
                      </base-input>
                  </div>
                  <div class="client-payment-modal-form-helper-text"><span>Your base price for charging
                          your client for Platform Fee is $<span>{{ m_LeadspeekMinCostMonth }}</span></span></div>
              </div>
          </div>
             <div class="d-flex flex-wrap flex-md-nowrap" style="gap:16px;">
                <div class='w-100'  
                    v-if="selectsPaymentTerm.PaymentTermSelect == 'One Time' && userType != 'client'">
                    <div class="client-payment-modal-form-label">
                        How many leads are included <span v-html="txtLeadIncluded">in that weekly charge</span>?
                    </div>
                    <div>
                        <base-input label="" type="text" placeholder="0" class="frmSetCost black-center"
                            v-model="LeadspeekMaxLead" style="width:100px" :readonly="LeadspeekInputReadOnly"
                            @keyup="profitcalculation();" @keydown="restrictInput">
                        </base-input>
                    </div>
                    <div class="client-payment-modal-form-helper-text"><span>Your base price for cost per
                            lead is $<span>{{ m_LeadspeekCostperlead }}</span></span></div>
                </div>
                <div class='w-100'  
                    v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time' && userType != 'client'">
                    <div class="client-payment-modal-form-label">
                        Cost per lead<span v-html="txtLeadOver" v-if="false">from the
                            monthly charge</span>?
                    </div>
                    <div>
                        <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                            class="campaign-cost-input" v-model="LeadspeekCostperlead"
                            :readonly="LeadspeekInputReadOnly" @keyup="profitcalculation();" @keydown="restrictInput">
                        </base-input>
                    </div>
                    <div class="client-payment-modal-form-helper-text"><span>Your base price for cost per
                            lead is $<span>{{ m_LeadspeekCostperlead }}</span></span></div>
                </div>

                <div class='w-100'>
                    <div class="client-payment-modal-form-label">
                        How many leads per day does the client want to receive?
                    </div>
                    <div>
                        <base-input style="text-align: left;" label="" type="text" placeholder="Input should be numeric and greater than zero" class="campaign-cost-input"
                            v-model="LeadspeekLimitLead" @input="onInput" @keyup="profitcalculation();" @keydown="event => restrictInput(event,'integer')">
                        </base-input>
                    </div>
                    <span class="client-payment-modal-form-helper-text">*Input should be numeric, greater than zero and can not be empty</span>
                    <!-- <div class="d-inline" style="float:left;padding-left:10px">
                        <label class="pt-2">Leads per&nbsp;day <small>*Zero means unlimited</small></label>
                        <el-select v-if="false" class="select-primary" size="large" placeholder="Select Modules"
                            v-model="selectsAppModule.LeadsLimitSelect" style="padding-left:10px" @change="checkLeadsType">
                            <el-option v-for="option in selectsAppModule.LeadsLimit" class="select-primary"
                                :value="option.value" :label="option.label" :key="option.label">
                            </el-option>
                        </el-select>
                    </div> -->

                    <div v-if="LeadspeekMaxDateVisible" class="campaign-cost-input">
                        <label class="client-payment-modal-form-label">Start From : </label>
                        <el-date-picker type="date" placeholder="Date Start" v-model="LeadspeekMaxDateStart"
                            class="frmSetCost leadlimitdate" id="leaddatestart">
                        </el-date-picker>
                    </div>

                </div>
            </div>
                <div 
                    v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time' && selectsPaymentTerm.PaymentTermSelect != 'Prepaid' && userType != 'client'">
                    <div class="client-payment-modal-form-label">
                        Do you want to set an end date?
                    </div>
                    <div>
                        <el-date-picker type="date" placeholder="Date End" v-model="LeadspeekDateEnd" :picker-options="pickerOptions"
                            class="campaign-cost-input" style="width:100% !important;" id="leaddateend" :readonly="LeadspeekInputReadOnly">
                        </el-date-picker>
                    </div>
                    <div class="client-payment-modal-form-helper-text" v-if="userType != 'client'">
                        <span>Leave blank to manually stop in the future</span>
                    </div>
                </div>
            </div>
                <div style="border:solid 1px;opacity:0.5;margin-top:24px; width:100%">&nbsp;</div>
                <div class="row">
                    <div class="col-sm-6 col-md-6 col-lg-6 d-flex flex-column justify-content-between">
                        <div>
                            <small> Client Calculations</small>
                            <div>
                                <small>1. Your Set Up fee is $<span><strong>{{ LeadspeekPlatformFee }}</strong></span></small>
                            </div>
                            <!-- <div>
                                <small>2. Your profit Platform fee profit
                                    $<span><strong>{{ t_LeadspeekMinCostMonth }}</strong></span></small>
                            </div> -->
                            <div>
                                <small>2. Your {{ txtLeadService }} campaign fee is
                                    $<span><strong>{{ LeadspeekMinCostMonth }}</strong></span></small>
                            </div>
                            <div v-if="selectsPaymentTerm.PaymentTermSelect != 'onetime' && t_estleadspermonth != '0.000' && LeadspeekLimitLead > 0" >
                                <small v-if="(prepaidType != 'onetime')">3. Your Cost per lead is $<span>
                                    <strong>{{ LeadspeekCostperlead }}</strong></span>
                                    <br/>
                                    <div class='d-inline' v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && prepaidType == 'continual'">

                                        <span>
                                            4. Estimate cost per {{ contiDurationSelection ? 'Month' : 'Week' }}:
                                        </span>
                                        <br/>({{ LeadspeekLimitLead }} per day x 7 days) <span v-if='contiDurationSelection'>x {{ t_freq }} weeks</span> :
                                        $<span><strong>{{contiDurationSelection ? formatPrice( ((LeadspeekCostperlead * LeadspeekLimitLead)*7 ) *t_freq ) : formatPrice( ((LeadspeekCostperlead * LeadspeekLimitLead)*7 ) )}}</strong></span>
                                    </div>
                                   
                                    <div class='d-inline' v-else><span>
                                            4. Estimate cost per {{ t_freqshow }}:
                                        </span>
                                        <br/>({{ LeadspeekLimitLead }} per day x 7 days) x {{ t_freq }} weeks :
                                        $<span><strong>{{formatPrice( ((LeadspeekCostperlead * LeadspeekLimitLead)*7 ) *t_freq )}}</strong></span></div>
                                </small>
                                <small v-if="prepaidType == 'onetime'">3. Your Cost per lead is $<span>
                                    <strong>{{ LeadspeekCostperlead }}</strong></span>
                                    <br/>({{ LeadspeekCostperlead }} per lead x {{ totalLeads.oneTime }} lead) :
                                    $<span><strong>{{formatPrice(LeadspeekCostperlead * totalLeads.oneTime)}}</strong></span>
                                </small>
                            </div>
                            <div v-if="selectsPaymentTerm.PaymentTermSelect == 'onetime'">
                                <small>3. Your total cost from leads included is
                                    $<span><strong>-{{ t_LeadspeekCostperlead }}</strong></span></small>
                            </div>
                        </div>
                        <div v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && LeadspeekLimitLead > 0">
                            <small v-if="prepaidType == 'continual'">Your estimate cost per {{ contiDurationSelection ? 'Month' : 'Week' }} is $<strong>{{contiDurationSelection ?  formatPrice( (((LeadspeekCostperlead * LeadspeekLimitLead)* 7) * t_freq) + parseFloat(LeadspeekMinCostMonth)) : formatPrice( (((LeadspeekCostperlead * LeadspeekLimitLead)* 7)) + parseFloat(LeadspeekMinCostMonth))}}</strong></small>
                            <small v-else>Your cost is $<strong>{{ formatPrice(LeadspeekCostperlead * totalLeads.oneTime) }}</strong></small>
                        </div>
                        <div v-else>
                            <small v-if="LeadspeekLimitLead > 0">Your estimate cost per {{ t_freqshow }} is $<strong>{{ formatPrice( (((LeadspeekCostperlead * LeadspeekLimitLead)* 7) * t_freq) + parseFloat(LeadspeekMinCostMonth)) }}</strong></small>
                            <!-- <div  v-if="prepaidType != 'onetime'">
                                <small v-if="false">Your estimate cost {{ t_freqshow }} is <strong>unlimited</strong></small>
                            </div>   -->
                            <!-- <div style="margin-top: auto" v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && prepaidType == 'onetime'">
                                <small v-if="LeadspeekLimitLead > 0">Your cost is $<strong>{{ formatPrice(LeadspeekCostperlead * totalLeads.oneTime) }}</strong></small>
                                <small v-if="false">Your estimate cost {{ t_freqshow }} is <strong>unlimited</strong></small>
                            </div> -->
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-6 d-flex flex-column justify-content-between" v-if="userType != 'client'">
                        <div>
                            <small>Agency Calculations </small>
                            <div>

                                <small>1. Your Set Up fee profit $<span><strong>{{ t_LeadspeekPlatformFee }}</strong></span></small>
                            </div>
                            <div>
                                <small>2. Your profit Platform fee profit
                                    $<span><strong>{{ t_LeadspeekMinCostMonth }}</strong></span></small>
                            </div>
                            <div v-if="selectsPaymentTerm.PaymentTermSelect != 'onetime' && t_estleadspermonth != '0.000' && LeadspeekLimitLead > 0">
                                <small v-if="(prepaidType != 'onetime')">3. Your profit per lead is $<span><strong>{{ t_LeadspeekCostperlead }}</strong></span>
                                    <br/>
                                    
                                     <span v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && prepaidType == 'continual'">
                                         <span>
                                            4. Estimate profit per {{ contiDurationSelection ? 'Month' : 'Week' }}:
                                        </span>
                                        <br/>({{ LeadspeekLimitLead }} per day x 7 days) <span v-if='contiDurationSelection'>x {{ t_freq }} weeks</span> :
                                        $<span><strong>{{contiDurationSelection ? t_estleadspermonth : (parseFloat(t_estleadspermonth.replace(/,/g, ''))) /4}}</strong></span>
                                    </span>
                                    <div v-else>
                                        <span>
                                            4. Estimate profit per {{ t_freqshow }}:
                                        </span>
                                        <br/>({{ LeadspeekLimitLead }} per day x 7 days) x {{ t_freq }} weeks :
                                        $<span><strong>{{ t_estleadspermonth }}</strong></span>
                                    </div>
                                </small>
                                <small v-if="prepaidType == 'onetime'">3. Your profit per lead is $<span>
                                    <strong>{{ t_LeadspeekCostperlead }}</strong></span>
                                    <br/>({{ t_LeadspeekCostperlead }} per lead x {{ totalLeads.oneTime }} lead) :
                                    $<span><strong>{{formatPrice(t_LeadspeekCostperlead * totalLeads.oneTime)}}</strong></span>
                                </small>
                            </div>
                            <div v-if="selectsPaymentTerm.PaymentTermSelect == 'onetime'">
                                <small>3. Your total cost from leads included is
                                    $<span><strong>-{{ t_LeadspeekCostperlead }}</strong></span></small>
                            </div>
                        </div>
                        <div style="margin-top: auto" v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && LeadspeekLimitLead > 0">
                            <small v-if="prepaidType == 'continual'">Your estimate profit per {{ contiDurationSelection ? 'Month' : 'Week' }} is $<strong>{{contiDurationSelection ?  t_profit : (parseFloat(t_profit.replace(/,/g, ''))) / 4}}</strong></small>
                            <small v-else>Your profit is $<strong>{{ formatPrice(t_LeadspeekCostperlead * totalLeads.oneTime) }}</strong></small>
                        </div>
                        <div v-else>
                            <small v-if="LeadspeekLimitLead > 0">Your estimate profit per {{ t_freqshow }} is $<strong>{{ t_profit }}</strong></small>
                            <!-- <div style="margin-top: auto" v-if="(prepaidType != 'onetime')">
                                <small v-if="false">Your estimate profit {{ t_freqshow }} is <strong>unlimited</strong></small>
                            </div>
                            <div style="margin-top: auto" v-if="prepaidType == 'onetime'">
                                <small v-if="LeadspeekLimitLead > 0">Your profit is $<strong>{{ formatPrice(t_LeadspeekCostperlead * totalLeads.oneTime) }}</strong></small>
                                <small v-if="false">Your estimate profit {{ t_freqshow }} is <strong>unlimited</strong></small>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button v-if="LeadspeekLimitLead > 0" @click.native="setModuleCost()">Ok, Set It Up!</base-button>
                </div>
            </template>
        </modal>
        <!-- Modal Setting Markup -->

        <!-- WhiteList DB upload -->
        <modal :show.sync="modals.whitelist" id="locatorWhitelist" headerClasses="justify-content-center"
         >
            <h4 slot="header" class="title title-up">Exclusion List for this campaign</h4>
            <div>
                <!--UPLOAD-->
                <form enctype="multipart/form-data">
                    <!--<h5>Drag & Drop your suppression List (file type should be .csv). Download <a href="#">example file</a></h5>-->
                    <div class="dropbox">
                        <input type="file" :name="uploadFieldName" :disabled="isSaving"
                            @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length"
                            accept=".csv" class="input-file">
                        <p v-if="isInitial">
                            Drag your file here to upload<br />or click to browse<br />
                            <!--<base-button type="info" round icon  @click="show_helpguide('suppression')">
                                                <i class="fas fa-question"></i>
                                            </base-button>-->
                        </p>
                        <p v-if="isSaving">
                            Please Wait, your file being upload ...
                        </p>
                        <p v-if="isSuccess">
                            Your Suppression file has been Submitted, Thank you!
                        </p>
                        <p v-if="isFailed">
                            Whoops that did not work, please check your file for errors and try again
                        </p>
                    </div>
                </form>
            </div>
            <ul v-if="supressionProgress.length > 0" class="mt-2 mb-0 mx-0 p-0" style="list-style: none; max-height: 90px; overflow: auto;">
                <li v-for="(progress, index) in supressionProgress" :key="index" class="text-dark m-0 p-0">
                    <i class="mr-2" :class="{'el-icon-loading': progress.status === 'progress', 'el-icon-circle-check': progress.status === 'done', 'el-icon-eleme': progress.status === 'queue'}"></i> 
                    <span class="mr-2">{{ progress.filename }}</span> 
                    <span v-if="progress.status === 'done'">{{ progress.status }}</span>
                    <span v-else>{{ progress.percentage }}%</span>
                </li>
            </ul>
            <div class="pt-2 mt-3">
                You have the ability to restrict your current database from being identified as a new lead. 
                You can Exclude them by uploading a list of MD5 encrypted email addresses, 
                or by uploading a list of email addresses and we will encrypt them for you. 
                Do not include any other information in the file aside from the email address. 
                You may upload lists of up to 10,000 records at a time. <a
                    href='https://app.exactmatchmarketing.com/samplefile/suppressionlist.csv' target='_blank'>Click here</a> to download a Sample File
            </div>
            <a class="mt-2 d-inline-block" @click="purgeSuppressionList('campaign')" style="cursor: pointer;"><i class="fas fa-trash" ></i> Purge Existing Records</a>
            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button @click.native="modals.whitelist = false">Cancel</base-button>
                </div>
            </template>
        </modal>
        <!-- WhiteList DB upload -->
        <!-- Integrations modal -->
        <modal :show.sync="modals.integrations" id="addIntegrations"
            footerClasses="border-top">
            <h3 slot="header" class="title title-up">Add integrations to campaign #{{selectedRowData.leadspeek_api_id}}</h3>
            <div>
                <div class="integratios-list-wrapper d-flex align-items-center gap-4">
                    <div v-for="item in integrations" :key="item.slug"
                        class="integrations__modal-item-wrapper d-flex align-items-center justify-content-center shadow-sm border"
                        :class="{ '--active bg-blue text-secondary': item.slug === selectedIntegration }"
                        @click="integrationItemClick(item)">
                        <div class="integrations__modal-item">
                            <i :class="item.logo" style="font-size: 36px;"></i>
                            <span class="integrarion-brand-name">{{ item.name }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-center shadow-sm add-new-integration-wrapper"
                        @click="redirectToAddIntegration">
                        <i class="fa-solid fa-plus" style="font-size: 36px;"></i>
                        <span class="integrarion-brand-name">Add integration</span>
                    </div>
                </div>
              
                <div class="mt-4">
                    <div v-if="selectedIntegration === 'googlesheet'" class="">
                        <div v-if="isGoogleInfoFetching" class="row" v-loading="true" id="loading"></div>
                        <div v-else>
                        <div>
                            <base-input label="Client Emails Approved to View the Google Sheet:">
                                <textarea class="w-100" v-model="selectedRowData.report_sent_to" @keydown="handleGsheetKeydown" @paste="handleGSheetPaste"
                                    placeholder="Enter email, separate by new line" rows="4">
                                    </textarea>
                                    <span>Seperate the emails by space or comma</span>
                                </base-input>
                        </div>
                        <div class="has-label">
                            <label>Admins Approved for Google Sheet Administration</label>
                            <el-select multiple class="select-info select-fullwidth" size="large"
                                v-model="selectedRowData.admin_notify_to" placeholder="You can select multiple Admin">
                                <el-option v-for="option in selectsAdministrator.administratorList" class="select-info"
                                    :value="option.id" :label="option.name" :key="option.id">
                                </el-option>
                            </el-select>
                        </div>
                        <span v-if="selectedRowData.spreadsheet_id" class="d-block mt-4">Open google sheet <el-tooltip content="Go to Google Sheet" effect="light" :open-delay="300" placement="top">
                            <a class="pl-2 pr-2" style="color:white" :href="'https://docs.google.com/spreadsheets/d/' + selectedRowData.spreadsheet_id + '/edit#gid=0'" target="_blank"><i class="fab fa-google-drive" style="color:#6699CC"></i></a>
                        </el-tooltip></span>
                    </div>
                    </div>

                    <div v-if="selectedIntegration === 'sendgrid'" class="''">
                      <div v-if="isSendgridInfoFetching" class="row" v-loading="true" id="loading"></div>
                        <div v-else>
                        <el-checkbox v-model="selectedRowData.sendgrid_is_active">Connect to sendgrid</el-checkbox>
                        <div class="d-flex" style="gap: 4px;">

                            <div class="has-label flex-grow-1">
                                <label class="mb-0">Choose Action</label>
                                <el-select multiple class="select-info select-fullwidth" size="large"
                                    v-model="selectedRowData.sendgrid_action" placeholder="You can select multiple actions">
                                    <el-option v-for="option in sendGridActionsOptions" class="select-info" :value="option.id"
                                        :label="option.name" :key="option.id">
                                    </el-option>
                                </el-select>
                            </div>
                             
                            <div v-if="selectedRowData.sendgrid_action && selectedRowData.sendgrid_action.includes('add-to-list')" class="flex-grow-1">
                                <div class="warning-banner" v-if="sendGridListOptions.length < 1"><p>You don't have any active sendgrid list please create one</p></div>
    
                                <div v-else class="has-label">
                                    <label class="mb-0">Choose sendgrid list</label>
                                    <el-select multiple class="select-info select-fullwidth" size="large"
                                        v-model="selectedRowData.sendgrid_list" placeholder="You can select multiple actions">
                                        <el-option v-for="option in sendGridListOptions" class="select-info" :value="option.id"
                                            :label="option.name" :key="option.id">
                                        </el-option>
                                    </el-select>
                                </div>
                            </div>
                        </div>

                        <!-- <div class="has-label mt-2" v-if="userType != 'client' && selectedSendgridAction.includes('campaign')">
                            <label class="mb-0">Campaign Id</label>
                            <el-input label="Campaign Id" class="select-info select-fullwidth" size="large"
                                v-model="sendGridcampaignId" placeholder="Enter Campaign Id">

                            </el-input>
                        </div> -->

                        <base-input v-if="userType != 'client' && selectedRowData.sendgrid_list && selectedRowData.sendgrid_list.includes('send-mail')"
                            label="Email format">
                            <textarea class="w-100" v-model="ClientReportSentTo" placeholder="Enter email format" rows="4">
                            </textarea>
                        </base-input>
                        </div>
                    </div>

                     <div v-if="selectedIntegration === 'gohighlevel'" class="">
                        <div v-if="isGoHighInfoFetching" class="row" v-loading="true" id="loading"></div>
                        <div v-else>
                        <el-checkbox v-model="selectedRowData.ghl_is_active">Connect to GoHighLevel</el-checkbox>
                    

                    <div class="has-label" v-if="selectedIntegration === 'gohighlevel'">
                        <label class="mb-0">Choose Tags</label>
                        <el-select 
                            multiple 
                            allow-create
                            filterable
                            default-first-option
                            :reserve-keyword="false"
                            class="select-info select-fullwidth" 
                            size="large"
                            v-model="ghl_tags" 
                            placeholder="You can select multiple tags">
                            <el-option v-for="option in goHighLevelTagsOptions" class="select-info" :value="option.id"
                                :label="option.name" :key="option.id">
                            </el-option>
                        </el-select>
                        <p v-if="ghl_tags_remove.length > 0">Attention: The following tags have been removed from GoHighLevel:</p>
                            <p v-for="(item, index) in ghl_tags_remove" :key="index">
                               - {{item}}
                            </p>
                        </div>
                    </div>
                    </div>
                    <div v-if="selectedIntegration === 'kartra'" class="''">
                      <div v-if="isKartraInfoFetching" class="row" v-loading="true" id="loading"></div>
                      <div v-else>
                          <el-checkbox v-model="selectedRowData.ghl_is_active">Connect to Kartra</el-checkbox>
                          <div class="d-flex mt-2" style="gap: 4px;">

                              <div class="flex-grow-1">
                                  <div class="warning-banner" v-if="kartraListOptions.length < 1"><p>You don't have any active kartra list please create one</p></div>
        
                                  <div v-else class="has-label">
                                      <label class="mb-0">Choose kartra list</label>
                                      <el-select multiple class="select-info select-fullwidth" size="large"
                                          v-model="selectedKartraList" placeholder="You can select multiple lists">
                                          <el-option v-for="option in kartraListOptions" class="select-info" :value="option"
                                              :label="option" :key="option">
                                          </el-option>
                                      </el-select>
                                  </div>
                              </div>    
                              <div class="flex-grow-1">
                                  <div class="warning-banner" v-if="kartraTagsOptions.length < 1"><p>You don't have any active kartra Tags please create one</p></div>
        
                                  <div v-else class="has-label">
                                      <label class="mb-0">Choose kartra Tags</label>
                                      <el-select multiple class="select-info select-fullwidth" size="large"
                                          v-model="selectedKartraTags" placeholder="You can select multiple tags">
                                          <el-option v-for="option in kartraTagsOptions" class="select-info" :value="option"
                                              :label="option" :key="option">
                                          </el-option>
                                      </el-select>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
                  <div v-if="selectedIntegration === 'zapier'" class="''">
                        <div v-if="isZapierFetching" class="row" v-loading="true" id="loading"></div>
                        <div v-else>
                            <el-checkbox v-model="zapierEnable">Enable Zapier / Webhook</el-checkbox>
                            <div class="has-label">
                                <label class="mb-0">Enter your Webhook URL</label>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <div v-for="(url, index) in zapierWebhook" :key="index" class="webhook-url-wrapper">
                                        <el-input type="text" clearable label="Enter your Webhook URL"
                                            placeholder="https://hooks.app.com/hooks/catch/....." class="flex-grow-1 mb-0"  v-model="zapierWebhook[index]"  aria-controls="datatables">
                                            
                                        </el-input>
                                        <span 
                                            v-if="index == 0" 
                                            class="add-webhook-url" 
                                            @click="addWebhookUrl">
                                            <i class="fa-solid fa-plus"></i>
                                        </span>
                                        <span 
                                            v-if="index != 0 && zapierWebhook.length > 1" 
                                            class="add-webhook-url" 
                                            @click="removeWebhookUrl(index)">
                                            <i class="fa-solid fa-minus"></i>
                                        </span>
                                    </div>
                                </div>

                                <p v-if="defaultWebhook" class="mt-2">you are using <b>default</b> client webhook, replace URL if you want to use custom webhook for this campaign  </p>
                                <p v-if="!defaultWebhook" class="mt-2">you are using <b>custom</b> webhook, just delete this URL and save. if you want to use default webhook for this campaign </p>
                                <label class="mb-0">Enter Tags</label>
                                <el-select 
                                    multiple 
                                    allow-create
                                    filterable
                                    default-first-option
                                    :reserve-keyword="false"
                                    class="select-info select-fullwidth" 
                                    size="large"
                                    v-model="zapierTags" 
                                    placeholder="You can select multiple tags">
                                    <el-option v-for="option in zapierTagsOptions" class="select-info" :value="option.name"
                                        :label="option.name" :key="option.name">
                                    </el-option>
                                </el-select>
                                <label class="mt-2">Test Your Webhook ?</label>
                                <el-checkbox v-model="zapierTestEnable" class="d-block">Send Test Data</el-checkbox>
                                <p>* Please check the 'Send Test Data' checkbox and click 'Save' to send the test data to your Webhook.</p>
                            </div>
                        </div>
                    </div>   
                 
                </div>
            </div>

            <template slot="footer">
                <div class="integrations-modal-footer-wrapper">
                    <div class="d-flex align-items-center justify-content-end">
                        <base-button @click.native="modals.integrations = false">Cancel</base-button>
                        <base-button @click.native="saveIntegrationConfiguration">Save</base-button>
                    </div>
                </div>
            </template>
        </modal>
        <!-- Integrations modal -->
         <!-- Create campaign modal -->
         <modal :show.sync="modals.campaign" id="addCampaign"  bodyClasses='create-campaign-modal-body'
            footerClasses="border-top p-2">
            <h3 slot="header" class="title title-up">Create Campaign</h3>
            <div class="row">
                        
                       
                            <div v-if="this.$global.menuUserType != 'client'" class="col-sm-12 col-md-12 col-lg-12 form-group has-label pull-left">
                            <label>{{CompanyNamedisabled ? 'Client Name' : 'Select Client Name'}}</label>
                            <el-select class="select-primary" size="large" placeholder="Select Client Name"
                                v-model="selectsCompany.companySelected"
                                @change="onCompanyChange(selectsCompany.companySelected);" :disabled="CompanyNamedisabled">

                                <el-option v-for="option in selectsCompany.companyList" class="select-primary"
                                    :value="option.id" :label="option.company_name" :key="option.id">
                                </el-option>
                            </el-select>
                            <small style="color:#942434 !important;" v-if="err_companyselect">* Client Company is required</small>
                        </div>
                        <div v-if="this.$global.menuUserType != 'client'" class="col-sm-12 col-md-12 col-lg-12">
                            <base-input label="Contact Name" type="text" placeholder="Input Client Contact Name"
                                v-model="ClientFullName" disabled>
                            </base-input>
                        </div>
                        <div v-if="this.$global.menuUserType != 'client'" class="col-sm-6 col-md-6 col-lg-6">
                            <base-input label="Email" type="email" placeholder="Input Client Email"
                                 v-model="ClientEmail" disabled>
                            </base-input>
                        </div>
                        <div v-if="this.$global.menuUserType != 'client'" class="col-sm-6 col-md-6 col-lg-6">
                            <base-input label="Phone Number" type="text" placeholder="Input Client Phone"
                               v-model="ClientPhone" disabled>
                            </base-input>
                        </div>
                        <span v-if="this.$global.menuUserType != 'client'" class="selerator-campaign-create"></span>
                        <div class="col-sm-12 col-md-12 col-lg-12 form-group has-label pull-left">
                            <base-input label="Campaign Name" type="text" placeholder="Input Campaign Name"
                                v-model="ClientCampaignName" :maxlength="255" class='mb-0 pb-0'>
                            </base-input>
                            <small id="err_campaignname" style="display:none;color:#942434 !important;">
                                * Campaign Name is required
                            </small>
                        </div>
                        <div class="col-sm-4 col-md-12 col-lg-12">
                            <base-input label="The URL where you will place the code" type="text"
                                placeholder="https://yourdomain.com/url-code-place" 
                                v-model="ClientUrlCode" :maxlength="255"
                                @input="limitUrlCodeLength"
                                >
                                <template #helperText>
                                    <small>URL must not exceed 255 characters.</small>
                                </template>
                            </base-input>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12" v-if="GoogleConnectTrue">
                            <base-input label="Client Emails Approved to View the Google Sheet:" >
                                <textarea class="form-control input-border" v-model="ClientReportSentTo"
                                    placeholder="Put client emails here, separate by new line" rows="50" @keydown="handleGsheetKeydowncreate" @paste="handleGSheetPasteCreate">
                                </textarea>
                            </base-input>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 has-label" v-if="userType != 'client'">
                            <label>Admins Approved for Google Sheet Administration</label>
                            <el-select multiple class="select-info select-fullwidth" size="large"
                                v-model="selectsAdministrator.administratorSelected"
                                placeholder="You can select multiple Admin">
                                <el-option v-for="option in selectsAdministrator.administratorList" class="select-info"
                                    :value="option.id" :label="option.name" :key="option.id">
                                </el-option>
                            </el-select>
                        </div>
                      
                        <!-- <div class="col-sm-12 col-md-12 col-lg-12 pt-4"> -->
                            <div class="col-sm-12 col-md-12 col-lg-12 pt-2">
                                <base-checkbox name="enabledphonenumber" v-model="checkboxes.phoneenabled" inline>Enable
                                    Phone Number</base-checkbox>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <base-checkbox name="enabledhomeaddress" v-model="checkboxes.homeaddressenabled"
                                    inline>Enable Home Address</base-checkbox>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <base-checkbox name="requireemailaddress" v-model="checkboxes.requireemailaddress"
                                    inline>Require Email Address</base-checkbox>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12">
                            <base-checkbox v-model="checkboxes.gtm" inline>
                                This code is installed in a Tag Manager
                            </base-checkbox>
                        </div>
                        <!-- </div> -->

                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2">
                            <label>Lead Re-identification Time Limit:</label>
                            <div class="leads-re-identification-selector-wrapper">
                                <div v-for='item in ReidentificationTimeOptions' :key='item.value' :class="['leads-re-identification-selector',{'--active': Reidentificationtime == item.value}]" @click='Reidentificationtime = item.value'>
                                    {{item.label}}
                                </div>
                            </div>
                            <base-checkbox class="pt-2" name="ApplyReidentificationToAll" v-model="checkboxes.ApplyReidentificationToAll" inline>Do not identify leads already identified on other campaigns.</base-checkbox>
                            <br /><small>* Your lead could be re-identified after the time limit you select.</small>
                        </div>

                  
                    </div>
                    <template slot="footer">
                        <div class="col-sm-12 col-md-12 col-lg-12">
                            <base-button size="sm" class="pull-right" id="btnSave" style="height:40px"
                                @click="ProcessAddEditClient('')">
                                Save
                            </base-button>
                            <base-button size="sm" class="pull-right mr-4" style="height:40px"
                                @click="CancelAddEditClient('')">
                                Cancel
                            </base-button>
                        </div>
                    </template>
        </modal>
          <!-- create campaign modal end -->
          <!-- edit campaign modal start -->
            <modal :show.sync="modals.campaignEdit" id="editCampaign"  bodyClasses='create-campaign-modal-body'
            footerClasses="border-top p-2">
                <h3 slot="header" class="title title-up">Edit Campaign #{{selectedRowData.leadspeek_api_id}}</h3>
                <div class="row">
                  
                    <div v-if="this.$global.menuUserType != 'client'" class="col-sm-12 col-md-12 col-lg-12">
                            <base-input label="Client Name" type="text" placeholder="Input Client Name"
                                v-model="selectedRowData.company_name" disabled>
                            </base-input>
                    </div>
                    <div v-if="this.$global.menuUserType != 'client'" class="col-sm-12 col-md-12 col-lg-12">
                            <base-input label="Contact Name" type="text" placeholder="Input Client Contact Name"
                                v-model="selectedRowData.name" disabled>
                            </base-input>
                    </div>
                    <div v-if="this.$global.menuUserType != 'client'" class="col-sm-6 col-md-6 col-lg-6">
                            <base-input label="Email" type="email" placeholder="Input Client Email"
                                 v-model="selectedRowData.email" disabled>
                            </base-input>
                        </div>
                        <div v-if="this.$global.menuUserType != 'client'" class="col-sm-6 col-md-6 col-lg-6">
                            <base-input label="Phone Number" type="text" placeholder="Input Client Phone"
                               v-model="selectedRowData.phonenum" disabled>
                            </base-input>
                        </div>
                        <span v-if="this.$global.menuUserType != 'client'" class="selerator-campaign-create"></span>
                        <div class="col-sm-12 col-md-12 col-lg-12 form-group has-label pull-left">
                            <base-input label="Campaign Name" type="text" placeholder="Input Campaign Name"
                                v-model="selectedRowData.campaign_name" :maxlength="255" class='mb-0 pb-0'>
                            </base-input>
                            <small id="err_campaignname" style="display:none;color:#942434 !important;">
                                * Campaign Name is required
                            </small>
                        </div>
                        <div class="col-sm-4 col-md-12 col-lg-12">
                            <base-input label="The URL where you will place the code" type="text"
                                placeholder="https://yourdomain.com/url-code-place" 
                                v-model="selectedRowData.url_code" :maxlength="255"   @input="limitUrlCodeLengthEdit(scope.row)">
                                <template #helperText>
                                    <small>URL must not exceed 255 characters.</small>
                                </template>
                            </base-input>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2">
                                <base-checkbox name="enabledphonenumber" v-model="selectedRowData.phoneenabled" inline>Enable
                                    Phone Number</base-checkbox>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <base-checkbox name="enabledhomeaddress" v-model="selectedRowData.homeaddressenabled"
                                    inline>Enable Home Address</base-checkbox>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <base-checkbox name="requireemailaddress" v-model="selectedRowData.require_email"
                                    inline>Require Email Address</base-checkbox>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12">
                            <base-checkbox @change="chkHideShowGTM(selectedRowData)" value="T"
                            :checked="selectedRowData.gtminstalled == 'T' ? true : false" inline>
                                This code is installed in a Tag Manager
                            </base-checkbox>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2">
                            <label>Lead Re-identification Time Limit:</label>
                            <div class="leads-re-identification-selector-wrapper">
                                <div v-for='item in ReidentificationTimeOptions' :key='item.value' :class="['leads-re-identification-selector',{'--active': selectedRowData.reidentification_type == item.value}]" @click='selectedRowData.reidentification_type = item.value'>
                                    {{item.label}}
                                </div>
                            </div>
                            <base-checkbox class="pt-2" name="ApplyReidentificationToAll" v-model="selectedRowData.applyreidentificationall" inline>Do not identify leads already identified on other campaigns.</base-checkbox>
                            <br /><small>* Your lead could be re-identified after the time limit you select.</small>
                        </div>
                 </div>
                 <template slot="footer">
                        <div class="align-items-center col-lg-12 col-md-12 col-sm-12 d-flex flex-row-reverse flex-wrap" style="gap:8px">
                            <base-button v-if="$global.menuLeadsPeek_update" size="sm"
                                                   :id="'btnSave' + selectedRowData.id" style="height:40px"
                                                    @click="ProcessAddEditClient(selectedRowData)">
                                                    Save
                                                </base-button>
                                                <base-button size="sm"  style="height:40px"
                                                    @click="CancelAddEditClient(selectedRowData)">
                                                    Cancel
                                                </base-button>
                                                <base-button v-if="selectedRowData.report_type == 'GoogleSheet'" size="sm"
                                                    :id="'btnResend' + selectedRowData.id"
                                                    style="height:40px" @click="ResendLink(selectedRowData)">
                                                    Resend Google Sheet Link
                                                </base-button>
                        </div>
                </template>
            </modal>
          <!-- edit campaign modal end -->
        <div id="popProcessing" class="popProcessing" style="display:none" v-html="popProcessingTxt">Please wait, updating
            campaign ....</div>
    </div>
</template>
<script>
import { DatePicker, Table, TableColumn, Select, Option, Checkbox, Tabs, TabPane, Badge, Switch, Dropdown, DropdownMenu, DropdownItem, CollapseItem, Collapse, RadioGroup, Radio } from 'element-ui';
import { BasePagination, Modal, BaseRadio } from 'src/components';
import SelectClientDropdown from '@/components/SelectClientDropdown.vue'
import Fuse from 'fuse.js';
import swal from 'sweetalert2';
import { dispatch, json } from 'd3';
import axios from 'axios';
import moment from 'moment';
import BaseInput from '../../../../components/Inputs/BaseInput.vue';
const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;
var CHECK_GROUPCOMPANY;
import { mapActions } from "vuex";
export default {
    components: {
        Modal,
        //Collapse,
        [DatePicker.name]: DatePicker,
        [Table.name]: Table,
        [TableColumn.name]: TableColumn,
        [Option.name]: Option,
        [Select.name]: Select,
        [Checkbox.name]: Checkbox,
        [Tabs.name]: Tabs,
        [TabPane.name]: TabPane,
        [Badge.name]: Badge,
        [Switch.name]: Switch,
        [Dropdown.name]: Dropdown,
        [DropdownMenu.name]: DropdownMenu,
        [DropdownItem.name]: DropdownItem,
        [Collapse.name]: Collapse,
        [CollapseItem.name]: CollapseItem,
        [RadioGroup.name]: RadioGroup,
        [Radio.name]: Radio,
        BasePagination,
        BaseRadio,
        BaseInput,
        SelectClientDropdown
    },
    data() {
        return {
            clientdefaultprice:'',
            isWebhookFetching: false,
            prepaidType: '',    
            contiDurationSelection: false,    
            totalLeads: {
                continualTopUp: '',
                oneTime: '',
            },
            lp_user_id: '',
            remainingBalanceLeads: '',
            err_totalleads: false,
            isContainulTopUpStop: false,
            
            // date picker settings 
            pickerOptions: {
                disabledDate(time) {
                    return time.getTime() < Date.now();
                },
            },
            /** FOR SUPRESSION UPLOAD FILE */
            uploadedFiles: [],
            uploadError: null,
            currentStatus: null,
            uploadFieldName: 'suppressionfile',
            /** FOR SUPRESSION UPLOAD FILE */
            tableDataOri: [],
            tableData: [],
            fuseSearch: null,
            searchedData: [],
            selectedRowData: {},
            searchQuery: '',
            pagination: {
                perPage: 10,
                currentPage: 1,
                // perPageOptions: [],
                total: 0,
                from: 0,
                to: 0,
            },
            modals: {
                helpguide: false,
                helpguideTitle: '',
                helpguideTxt: '',

                embededcode: false,
                campaign: false,
                campaignEdit: false,
                pricesetup: false,
                integrations: false,
                questionnaire: false,
                whitelist: false,
            },

            questionnaire: {
                'asec3_1': '',
                'asec3_2': '',
                'asec3_3': false,
                'asec3_4': '',
                'asec3_5': '',
                'asec3_6': '',

                'asec6_1': '',
                'asec6_2': '',
                'asec6_3': '',
                'asec6_4': '',
                'asec6_5': '',
                'asec6_6': false,
                'asec6_7': '',

                'url_code': '',
                'campaign_name': '',
            },

            leadlocatorname: '',
            leadlocalname: '',

            companyID: '',
            activeClientCompanyID: '',
            activeClientCompanyParent: '',

            ClientCompanyName: '',
            ClientCampaignName: '',
            ClientFullName: '',
            ClientEmail: '',
            ClientPhone: '',
            ClientPerLead: '500',
            ClientUserID: '',
            ClientReportSentTo: '',
            ClientUrlCode: '',
            ClientUrlCodeThankyou: '',

            ClientEmbededCode: '',
            popProcessingTxt: 'Please wait, adding new campaign ....',

            GoogleConnectFalse: false,
            GoogleConnectTrue: false,
            CompanyNamedisabled: false,
            isKartraInfoFetching: false,
            isZapierFetching: false,
            isGoHighInfoFetching: false,
            isSendgridInfoFetching: false,
            isGoogleInfoFetching: false,

            tmpdefaultadmin: [],

            Reidentificationtime: 'never',
            ReidentificationTimeOptions: [
                {
                    value: 'never',
                    label: 'Never'
                },
                {
                    value: '1 week',
                    label: '1 Week'
                },
                {
                    value: '1 month',
                    label: '1 Month'
                },
                {
                    value: '3 months',
                    label: '3 Months'
                },
                {
                    value: '6 months',
                    label: '6 Months'
                },
                {
                    value: '1 year',
                    label: '1 Year'
                },
            ],

            checkboxes: {
                hide_phone: true,
                gtm: false,
                phoneenabled: false,
                homeaddressenabled: false,
                requireemailaddress: true,
                ApplyReidentificationToAll: false,
            },

            radios: {
                reportType: 'GoogleSheet',
            },
            selectsAdministrator: {
                administratorSelected: [],
                administratorList: [],
            },
            selectsCompany: {
                companySelected: '',
                companyList: [],
            },
            selectsGroupCompany: {
                companyGroupID: '',
                newCompanyGroupName: '',
                companyGroupAddEdit: false,
                companyGroupSelected: '',
                companyGroupList: [],
            },

            ClientActiveID: '',

            LeadspeekPlatformFee: '0',
            m_LeadspeekPlatformFee: '0',
            t_LeadspeekPlatformFee: '0',
            LeadspeekCompany: '',
            LeadspeekclientEmail: '',
            LeadspeekCostperlead: '0',
            m_LeadspeekCostperlead: '0',
            t_LeadspeekCostperlead: '0',
            LeadspeekMaxLead: '0',
            LeadspeekMinCostMonth: '0',
            m_LeadspeekMinCostMonth: '0',
            m_LeadspeekLimitLead: '10',
            t_LeadspeekMinCostMonth: '0',
            LeadspeekLimitLead: '10',
            LeadspeekClientActiveIndex: '',
            LeadspeekMaxDateStart: '',
            LeadspeekDateEnd: '',
            LeadspeekMaxDateVisible: false,
            LeadspeekType: '',

            t_estleadspermonth: '0',

            LeadspeekInputReadOnly: false,
            OnlyPrepaidDisabled: false,
            ActiveLeadspeekID: '',

            t_profit: '0',
            t_freqshow: 'week',
            t_freq: '4',
            t_cost: '0',
            
            txtLeadService: 'per week',
            txtLeadIncluded: 'in that weekly charge',
            txtLeadOver: 'from the weekly charge',

            selectsPaymentTerm: {
                PaymentTermSelect: 'Weekly',
                PaymentTerm: [
                    // { value: 'One Time', label: 'One Time'},
                    // { value: 'Weekly', label: 'Weekly'},
                    // { value: 'Monthly', label: 'Monthly'},
                ],
            },
            selectsAppModule: {
                AppModuleSelect: 'LeadsPeek',
                AppModule: [
                    // { value: 'Ads Design', label: 'Ads Design' },
                    // { value: 'Campaign', label: 'Campaign' },
                    { value: 'LeadsPeek', label: 'LeadsPeek' },
                ],
                LeadsLimitSelect: 'Day',
                LeadsLimit: [
                    //{ value: 'Month', label: 'Month'},
                    { value: 'Day', label: 'Day' },
                    //{ value: 'Max', label: 'Max'},
                ],
            },

            costagency: {
                local: {
                    'Weekly': {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'Monthly': {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'OneTime': {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'Prepaid' : {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    }
                },
            },

            defaultCostCampaign: {
                paymentterm: 'Weekly',
                LeadspeekPlatformFee: '0',
                LeadspeekCostperlead: '0',
                LeadspeekMinCostMonth: '0',
                LeadspeekLeadsPerday: '0',

                local: {
                    'Weekly': {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'Monthly': {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'OneTime': {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'Prepaid': {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                },
            },

            userTypeOri: '',
            err_companyselect: false,
            fetchingCampaignData: false,
            whiteListLeadspeekID: '',
            whiteListCompanyId: '',
            whiteListLeadspeekApiId: '',
            disabledaddcampaign: true,
            userType: '',
            sendGridActionsOptions: [
                {
                    id: 'add-to-list',
                    name: 'Add to list'
                },
                {
                    id: 'create-contact',
                    name: 'Create a contact'
                },

            ],

            goHighLevelTagsOptions: [],
            ghl_tags : [],
            ghl_tags_remove: [],

            sendGridListOptions: [
            ],
            selectedSendgridAction: [],
            selectedSendgridList: [],
            sendGridcampaignId: '',
            selectedKartraList: [],
            selectedKartraTags: [],
            kartraTagsOptions: [],
            kartraListOptions: [],
            // dummy data for integraions 
            integrations: [

            ],
            zapierEnable: false,
            zapierTestEnable: false,
            zapierTags : [],
            zapierTagsOptions: [],
            zapierWebhook: [''],
            defaultWebhook: true,
            selectedIntegration: 'googlesheet',
            currentRowIndex: 0,
            currSortBy: '',
            currOrderBy: '',

            supressionProgress: [],
            supressionInterval: '',
            filterCampaignStatus: 'all',
            dropdownVisible: '',
            optionCampaignStatus: [
                {
                    value: 'all',
                    label: 'All'
                },
                {
                    value: 'play',
                    label: 'Running'
                },
                {
                    value: 'paused',
                    label: 'Paused'
                },
                {
                    value: 'stop',
                    label: 'Stopped'
                },
            ],
            colorOptions: [
                 {
                   background: 'rgb(224, 226, 246)',
                   color: 'rgb(80, 70, 177)',
                 },
                 {
                   background: 'rgb(246, 224, 227)',
                   color: 'rgb(177, 70, 143)',
                 },
                 {
                   background: 'rgb(214, 235, 228)',
                   color: 'rgb(90, 194, 159)',
                 },
                 {
                   background: 'rgb(246, 235, 224)',
                   color: 'rgb(194, 132, 69)',
                 },
                 {
                   background: 'rgb(211, 233, 255)',
                   color: 'rgb(102, 153, 204)',
                 },
            ],
        }
    },

    computed: {
        isInitial() {
            return this.currentStatus === STATUS_INITIAL;
        },
        isSaving() {
            return this.currentStatus === STATUS_SAVING;
        },
        isSuccess() {
            return this.currentStatus === STATUS_SUCCESS;
        },
        isFailed() {
            return this.currentStatus === STATUS_FAILED;
        },
        /***
         * Returns a page from the searched data or the whole data. Search is performed in the watch section below
         */
        queriedData() {
            let result = this.tableData;
            if (this.searchedData.length > 0) {
                result = this.searchedData;
            }
            return result;
        },
        to() {
            let highBound = this.from + this.pagination.perPage;
            if (this.total < highBound) {
                highBound = this.total;
            }
            return highBound;
        },
        from() {
            return this.pagination.perPage * (this.pagination.currentPage - 1);
        },
        total() {
            return this.searchedData.length > 0
                ? this.searchedData.length
                : this.tableData.length;
        }
    },

    methods: {
        ...mapActions(["getUserSendgridList", 'getUserIntegrationList', "updateCompanyIntegrationConfiguration","geUsertKartraList","geUsertKartraDetails",'getSelectedKartraListAndTags', "getAgencyZapierDetails", "getCampaignZapierDetails", "getCampaignZapierTags", "getAgencyZapierTags"]),
        limitUrlCodeLength(){
            if(this.ClientUrlCode){
                if(this.ClientUrlCode.includes(' ')){
                    this.$notify({
                        type: 'warning',
                        message: 'Spaces are not allowed in the URL.',
                        icon: 'fa fa-exclamation-triangle'
                    });
                    this.ClientUrlCode = this.ClientUrlCode.replace(/\s/g, '');
                }
                if(this.ClientUrlCode.length > 254){
                    this.ClientUrlCode = this.ClientUrlCode.substring(0, 255);
                    this.$notify({
                        type: 'warning',
                        message: 'The URL has been shortened to 255 characters.',
                        icon: 'fa fa-exclamation-triangle'
                    });
                }
            }
        },
        addWebhookUrl() {
            this.zapierWebhook.push('');
        },
        removeWebhookUrl(index) {
            if (this.zapierWebhook.length > 1) {
                this.zapierWebhook.splice(index, 1);
            }
        },
        limitUrlCodeLengthEdit(row){
            if(row.url_code){
                if(row.url_code.includes(' ')){
                    this.$notify({
                        type: 'warning',
                        message: 'Spaces are not allowed in the URL.',
                        icon: 'fa fa-exclamation-triangle'
                    });
                }
                row.url_code = row.url_code.replace(/\s/g, '');
                if(row.url_code.length > 254){
                    row.url_code = row.url_code.substring(0, 255);
                    this.$notify({
                        type: 'warning',
                        message: 'The URL has been shortened to 255 characters.',
                        icon: 'fa fa-exclamation-triangle'
                    });
                }
            }
        },
        updateWeeklyMonthlyToggle(){
         this.totalLeads.continualTopUp =    this.contiDurationSelection ? (this.LeadspeekLimitLead * 7 ) * 4 : (this.LeadspeekLimitLead * 7)
        },
        checkStatusFileUpload() {

            clearInterval(this.supressionInterval);

            /** START CHECK IF THERE IS ANYTHING NOT DONE */
            this.supressionInterval = setInterval(() => {

                this.$store.dispatch('jobProgress', {
                    leadspeekID: this.whiteListLeadspeekID,
                    companyId: this.whiteListCompanyId,
                    leadspeekApiId: this.whiteListLeadspeekApiId,
                    campaignType: 'campaign',
                })
                .then(response => {

                    this.supressionProgress = response.data.jobProgress;
                    
                    if(response.data.jobProgress[0]['status'] == 'done') {
                        clearInterval(this.supressionInterval);
                    }

                })
                .catch(error => {
                    console.error(error);
                })

            }, 2000);
            /** START CHECK IF THERE IS ANYTHING NOT DONE */
        },
        stopContinualTopUp() {
            this.$store.dispatch('stopContinualTopUp', {
                leadspeek_api_id: this.ActiveLeadspeekID,
            })
            .then(response => {
                if(!response.data.status) {
                    this.$notify({
                        type: 'danger',
                        message: response.data.message,
                        icon: 'fa fa-save'
                    })
                } else {
                    this.isContainulTopUpStop = response.data.stop_continue === 'T' ? true : false;
                    this.GetClientList();   
                    // console.log(response);
                }
            })
            .catch(error => {
                console.error(error);
            })
        },
        ExportLeadsData(index, row) {
            var leadsExportStart = this.format_date(this.$moment(row.created_at).format('YYYY-MM-DD') + " 00:00:00",true,false);
            var leadsExportEnd = this.format_date(this.$moment().format('YYYY-MM-DD') + " 23:59:59", true, false);
            if(row.created_at != '' && row.id != '') {
                //window.open(process.env.VUE_APP_DATASERVER_URL + '/leadspeek/report/lead/export/' + this.companyID + '/' + this.ClientActiveID + '/' + this.LeaddatePickerStart + '/' + this.LeaddatePickerEnd, "_blank");
                document.location = process.env.VUE_APP_DATASERVER_URL + '/leadspeek/report/lead/export/' + row.company_id + '/' + row.id + '/' + leadsExportStart + '/' + leadsExportEnd;
            }
        },
        show_helpguide(helptype) {
            if (helptype == 'suppression') {
                this.modals.helpguideTitle = "Whitelist Your Current Database"
                this.modals.helpguideTxt = "We do not want to charge you for anyone who is currently in your database. You can Whitelist them by providing an encrypted list of email addresses, or by uploading a list of email addresses and we will encrypt them for you. Do not include any other information in the file aside from the email address. <a href='/samplefile/suppressionlist.csv' target='_blank'>Click here</a> to download a Sample File"
            }

            this.modals.helpguide = true;
        },
        validationLeadToBuy(input) {
            if (Number(input) < 50) {
                this.err_totalleads = true;
            } else {
                this.err_totalleads = false;
            }
        },
        changePage(event){
            //console.log(this.pagination,event)
            this.GetClientList(this.currSortBy,this.currOrderBy)
        },
        handleGsheetKeydown(event) {
            if (event.key === ' ' || event.key === ',') {
                event.preventDefault(); // Prevent the default action (space or comma)
                this.selectedRowData.report_sent_to += '\n'; // Add a newline
            }
        },
        handleGSheetPaste(event) {
            event.preventDefault(); // Prevent the default paste action
            // Get the pasted data and replace commas with newline characters
            const pastedText = (event.clipboardData || window.clipboardData).getData('text');
            const modifiedText = pastedText.replace(/,/g, '\n');
            // Insert the modified text at the current cursor position
            const cursorPos = event.target.selectionStart;
            const textBeforeCursor = this.selectedRowData.report_sent_to.substring(0, cursorPos);
            const textAfterCursor = this.selectedRowData.report_sent_to.substring(cursorPos);
            this.selectedRowData.report_sent_to = textBeforeCursor + modifiedText + textAfterCursor;
        }, 
        handleGsheetKeydowncreate(event) {
            if (event.key === ' ' || event.key === ',') {
                event.preventDefault(); // Prevent the default action (space or comma)
                this.ClientReportSentTo += '\n'; // Add a newline
            }
        },
        handleGSheetPasteCreate(event) {
            event.preventDefault(); // Prevent the default paste action
            // Get the pasted data and replace commas with newline characters
            const pastedText = (event.clipboardData || window.clipboardData).getData('text');
            const modifiedText = pastedText.replace(/,/g, '\n');
            // Insert the modified text at the current cursor position
            const cursorPos = event.target.selectionStart;
            const textBeforeCursor = this.ClientReportSentTo.substring(0, cursorPos);
            const textAfterCursor = this.ClientReportSentTo.substring(cursorPos);
            this.ClientReportSentTo = textBeforeCursor + modifiedText + textAfterCursor;
        },
        onInput(input) {
            if (input == "0") {
                this.LeadspeekLimitLead = 1;
            }
        },
        restrictInput(event,inputType) {
            const input = event.target.value;
            // Allow backspace, arrow keys, and other non-character keys
            const char = event.key;
            if (['Backspace', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(char)) {
                return; 
            }

            if (inputType == 'integer') {
                if (!/^\d$/.test(char)) {
                    event.preventDefault();
                }
            }

            // Check if the character is not a number
            if (!char.match(/[0-9]/) && char !== '.') {
                event.preventDefault();
            }

            // Allow only one period
            if (char === '.' && input.includes('.')) {
                event.preventDefault();
            }
        },
        async redirectToAddIntegration() {

            if(this.$store.getters.userData.user_type === 'client'){
                this.$router.push({ name: 'Integration List' })
            }else{
                this.$router.push({ name: 'Client List' })
            }
        },
        searchKeyWord() {
          
                 this.pagination.currentPage = 1;
                  this.GetClientList(this.currSortBy,this.currOrderBy,this.searchQuery);
         
        },
        reset() {
            // reset form to initial state
            this.currentStatus = STATUS_INITIAL;
            this.uploadedFiles = [];
            this.uploadError = null;
            this.uploadFieldName = 'suppressionfile';
            $('input[type="file"]').val(null);
        },
        save(formData) {
            // upload data to the server
            this.currentStatus = STATUS_SAVING;

            //console.log(formData);

            const config = {
                headers: {
                    'content-type': 'multipart/form-data',
                    'Access-Control-Allow-Origin': '*',
                }
            };
            
            axios.post(process.env.VUE_APP_APISERVER_URL + '/api/leadspeek/suppressionlist/upload', formData, config)
            .then(response => {
                //console.log(response.data);
                if(response.data.result == 'success') {
                    this.currentStatus = STATUS_SUCCESS;
                    this.checkStatusFileUpload(); 
                    setTimeout(() => {
                        this.reset();
                    }, 2000);
                }else{
                    this.currentStatus = STATUS_FAILED;
                    setTimeout(() => {
                        this.reset();
                    }, 2000);
                }
            })
            .catch(error => {
                //console.log(error);
                this.currentStatus = STATUS_FAILED;
                setTimeout(() => {
                    this.reset();
                }, 2000);
                //reject(error)
            })
            
            /* UPLOAD FILE */
            // try 
            // {
            //     const response = await axios.post(process.env.VUE_APP_APISERVER_URL + '/api/leadspeek/suppressionlist/upload', formData, config)

            //     console.log(response);
                
            //     if (response.data.result == 'success') {
            //         this.currentStatus = STATUS_SUCCESS;
            //         setTimeout(() => {
            //             this.reset();
            //         }, 2000);
            //     } else {
            //         this.currentStatus = STATUS_FAILED;
            //         setTimeout(() => {
            //             this.reset();
            //         }, 2000);
            //     }
            // } 
            // catch(error) 
            // {
            //     console.error(error);
            //     this.currentStatus = STATUS_FAILED;
            //     setTimeout(() => {
            //         this.reset();
            //     }, 2000);
            // }
            /* UPLOAD FILE */
        },
        purgeSuppressionList(action){
            swal.fire({
                title: 'Record Purge Confirmation',
                text: "Are you sure you want to purge existing records?",
                showCancelButton: true,
                confirmButtonColor: 'blue',
                cancelButtonColor: 'red',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Perform the deletion
                    this.$store.dispatch('purgeSuppressionList', {
                        paramID: this.whiteListLeadspeekID,
                        campaignType: action,
                    }).then(response => {
                        if (response.result === 'success') {
                            swal.fire({
                                icon: 'success',
                                title: response.title,
                                text: response.msg,
                                confirmButtonText: 'OK'
                            });
                        } else {
                            swal.fire({
                                icon: 'error',
                                title: response.title,
                                text: response.msg,
                                confirmButtonText: 'OK'
                            });
                        }
                    }).catch(error => {
                        swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'There was an error processing your request.',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        },
        filesChange(fieldName, fileList) {
            // handle file changes
            const formData = new FormData();

            if (!fileList.length) return;

            // append the files to FormData
            Array
                .from(Array(fileList.length).keys())
                .map(x => {
                    formData.append(fieldName, fileList[x], fileList[x].name);
                });

            formData.append("leadspeekID", this.whiteListLeadspeekID);

            // save it
            this.save(formData);
        },
        showWhitelist(index, row) {
            this.whiteListLeadspeekID = row.id;
            this.whiteListCompanyId = row.company_id;
            this.whiteListLeadspeekApiId = row.leadspeek_api_id;
            this.modals.whitelist = true;

            this.checkStatusFileUpload();
        },
        archiveCampaign(index, row) {
            if (row.disabled == 'T' && row.active_user == 'F') {
                swal.fire({
                    title: "Are you sure you want to archive this campaign?",
                    text: '',
                    icon: '',
                    showCancelButton: true,
                    customClass: {
                        confirmButton: 'btn btn-default btn-fill',
                        cancelButton: 'btn btn-danger btn-fill'
                    },
                    confirmButtonText: 'Archive this campaign',
                    buttonsStyling: false
                }).then(result => {
                    if (result.value) {
                        this.$store.dispatch('archiveCampaign', {
                            lpuserid: row.id,
                            status: 'T',
                        }).then(response => {
                            if (response.result == 'success') {
                                this.deleteRow(row);

                                this.$notify({
                                    type: 'success',
                                    message: 'Campaign has been archive.',
                                    icon: 'far fa-save'
                                });
                            }
                        }, error => {
                            this.$notify({
                                type: 'primary',
                                message: 'Sorry there is something wrong, pleast try again later',
                                icon: 'fas fa-bug'
                            });

                        });
                    }

                });

            } else {
                this.$notify({
                    type: 'primary',
                    message: 'Please stop the campaign before archive it.',
                    icon: 'fas fa-bug'
                });
            }
        },
        redirectaddclient() {
            this.$router.push({ name: 'Client List' })
        },
        tooltip_campaign(index, row, stat) {
            if (stat == 'play') {
                if (row.disabled == 'T' && row.active_user == 'F' && row.customer_card_id != '') {
                    return "Start Campaign";
                } else if (row.disabled == 'F' && row.active_user == 'T' && row.customer_card_id != '') {
                    return "Campaign is Running";
                } else if (row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                    return "Campaign can not start before your complete payment information"
                } else {
                    return "Start Campaign";
                }
            } else if (stat == 'pause') {
                if (row.disabled == 'F' && row.active_user == 'F' && row.customer_card_id != '') {
                    return "Pause Campaign";
                } else if (row.disabled == 'T' && row.active_user == 'T' && row.customer_card_id != '') {
                    return "Campaign is Paused";
                } else if (row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                    return "Campaign can not start before your complete payment information"
                } else {
                    return "Pause Campaign";
                }

            } else if (stat == 'stop') {
                if (row.active_user == 'T' && row.customer_card_id != '') {
                    return "Stop Campaign";
                } else if (row.active_user == 'F' && row.customer_card_id != '') {
                    return "Campaign is Stopped";
                } else if (row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                    return "Campaign can not start before your complete payment information"
                } else {
                    return "Stop Campaign";
                }
            }

            return ""
        },
        setModuleCost() {
            if((this.totalLeads.oneTime < 50) && (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid')) {
                this.$notify({
                    type: 'danger',
                    message: 'Minimum 50 lead',
                    icon: 'far fa-save'
                });
            } else {
                    //if (this.selectsAppModule.AppModuleSelect == 'Leads Peek') {
                /** SET MODULE COST */
                this.$store.dispatch('ClientModuleCost', {
                    companyID: this.companyID,
                    ClientID: this.ClientActiveID,
                    ModuleName: this.selectsAppModule.AppModuleSelect,
                    CostSet: this.LeadspeekCostperlead,
                    CostMonth: this.LeadspeekMinCostMonth,
                    CostMaxLead: this.LeadspeekMaxLead,
                    LimitLead: this.LeadspeekLimitLead,
                    LimitLeadFreq: this.selectsAppModule.LeadsLimitSelect,
                    LimitLeadStart: moment(this.LeadspeekMaxDateStart, "YYYY-MM-DD").format('YYYY-MM-DD'),
                    //LimitLeadEnd: moment(this.LeadspeekDateEnd, "YYYY-MM-DD").format('YYYY-MM-DD'),
                    LimitLeadEnd: this.format_date(this.$moment(this.LeadspeekDateEnd).format('YYYY-MM-DD') + " 23:59:59",true,false),
                    PaymentTerm: this.selectsPaymentTerm.PaymentTermSelect,
                    topupoptions: this.prepaidType,
                    leadsbuy: this.prepaidType == 'continual' ? this.totalLeads.continualTopUp : this.totalLeads.oneTime,
                    PlatformFee: this.LeadspeekPlatformFee,
                    LeadspeekApiId: this.ActiveLeadspeekID,
                    idUser: this.$store.getters.userData.id,
                    ipAddress: this.$store.getters.userData.ip_login,
                    // continualBuyOption: this.contiDurationSelection ? 'Monthly' :  'Weekly',
                    contiDurationSelection: this.contiDurationSelection,
                    idSys: this.$global.idsys
                }).then(response => {
                  
                    /** UPDATE ROW */

                    this.tableData[this.LeadspeekClientActiveIndex].lp_min_cost_month = this.LeadspeekMinCostMonth
                    this.tableData[this.LeadspeekClientActiveIndex].lp_max_lead_month = this.LeadspeekMaxLead
                    this.tableData[this.LeadspeekClientActiveIndex].cost_perlead = this.LeadspeekCostperlead
                    this.tableData[this.LeadspeekClientActiveIndex].lp_limit_leads = this.LeadspeekLimitLead
                    this.tableData[this.LeadspeekClientActiveIndex].lp_limit_freq = this.selectsAppModule.LeadsLimitSelect
                    this.tableData[this.LeadspeekClientActiveIndex].lp_limit_startdate = this.LeadspeekMaxDateStart
                    this.tableData[this.LeadspeekClientActiveIndex].lp_enddate = this.LeadspeekDateEnd
                    this.tableData[this.LeadspeekClientActiveIndex].paymentterm = this.selectsPaymentTerm.PaymentTermSelect
                    this.tableData[this.LeadspeekClientActiveIndex].platformfee = this.LeadspeekPlatformFee
                    this.tableData[this.LeadspeekClientActiveIndex].topupoptions = this.prepaidType
                    this.tableData[this.LeadspeekClientActiveIndex].leadsbuy = this.prepaidType == 'continual' ? this.totalLeads.continualTopUp : this.totalLeads.oneTime
                    this.tableData[this.LeadspeekClientActiveIndex].stopcontinual =  (this.isContainulTopUpStop)? 'T':'F'
                    this.tableData[this.LeadspeekClientActiveIndex].continual_buy_options = this.contiDurationSelection;

                    /** UPDAE ROW */
                    this.modals.pricesetup = false;
                    this.$notify({
                        type: 'success',
                        message: 'Data has been updated successfully',
                        icon: 'far fa-save'
                    });
                }, error => {

                });
                /** SET MODULE COST */
                //}
            }
            
        },
        paymentTermChange() {
            if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                this.txtLeadService = 'per week';
                this.txtLeadIncluded = 'in that weekly charge';
                this.txtLeadOver = 'from the weekly charge';
                this.prepaidType= '';
                this.m_LeadspeekPlatformFee = this.costagency.local.Weekly.LeadspeekPlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.local.Weekly.LeadspeekCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.local.Weekly.LeadspeekMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.local.Weekly.LeadspeekLeadsPerday;
                
                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead;

                /*if (this.selectsPaymentTerm.PaymentTermSelect != this.defaultCostCampaign.paymentterm) {
                    console.log('1');
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.local.Weekly.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.local.Weekly.LeadspeekCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.local.Weekly.LeadspeekMinCostMonth;
                } else {*/
                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.LeadspeekCostperlead
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.LeadspeekMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.LeadspeekLeadsPerday;
                }else{
                    if (this.clientdefaultprice != "") {
                        this.LeadspeekPlatformFee = this.clientdefaultprice.local.Weekly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.clientdefaultprice.local.Weekly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.clientdefaultprice.local.Weekly.LeadspeekMinCostMonth;
                    }else{
                        this.LeadspeekPlatformFee = this.defaultCostCampaign.local.Weekly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.defaultCostCampaign.local.Weekly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.defaultCostCampaign.local.Weekly.LeadspeekMinCostMonth;
                        //this.LeadspeekLimitLead = this.defaultCostCampaign.local.Weekly.LeadspeekLimitLead;
                    }
                }

            } else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                this.txtLeadService = 'per month';
                this.txtLeadIncluded = 'in that monthly charge';
                this.txtLeadOver = 'from the monthly charge';
                this.prepaidType= '';
                this.m_LeadspeekPlatformFee = this.costagency.local.Monthly.LeadspeekPlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.local.Monthly.LeadspeekCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.local.Monthly.LeadspeekMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.local.Monthly.LeadspeekLeadsPerday;

                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead
                /*if (this.selectsPaymentTerm.PaymentTermSelect != this.defaultCostCampaign.paymentterm) {
                     console.log('3');
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.local.Monthly.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.local.Monthly.LeadspeekCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.local.Monthly.LeadspeekMinCostMonth;
                } else {*/
                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.LeadspeekCostperlead
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.LeadspeekMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.LeadspeekLeadsPerday;
                }else{
                    if (this.clientdefaultprice != "") {
                        this.LeadspeekPlatformFee = this.clientdefaultprice.local.Monthly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.clientdefaultprice.local.Monthly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.clientdefaultprice.local.Monthly.LeadspeekMinCostMonth;
                    }else{
                        this.LeadspeekPlatformFee = this.defaultCostCampaign.local.Monthly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.defaultCostCampaign.local.Monthly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.defaultCostCampaign.local.Monthly.LeadspeekMinCostMonth;
                        //this.LeadspeekLimitLead = this.defaultCostCampaign.local.Monthly.LeadspeekLimitLead;
                    }
                }

            } else if(this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                // this.prepaidType = 'continual';
                // console.log('prepaid');
                // console.log(this.prepaidType);;
                // console.log('prepaid');
                this.txtLeadService = 'per month';
                this.txtLeadIncluded = 'in that week charge';
                this.txtLeadOver = 'from the week charge';
                this.prepaidType = this.selectedRowData.topupoptions ? this.selectedRowData.topupoptions : 'continual';
                if (typeof(this.costagency.local.Prepaid) == 'undefined') {
                    this.$set(this.costagency.local,'Prepaid',{
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    });
                }

                if (typeof(this.costagency.local.Prepaid.LocatorLeadsPerday) == 'undefined') {
                    this.$set(this.costagency.local.Prepaid, 'LeadspeekLeadsPerday', '10');
                    this.costagency.local.Prepaid.LeadspeekLeadsPerday = '10';
                }

                this.m_LeadspeekPlatformFee = this.costagency.local.Prepaid.LeadspeekPlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.local.Prepaid.LeadspeekCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.local.Prepaid.LeadspeekMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.local.Prepaid.LeadspeekLeadsPerday;

                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead;
                
                /*if (this.selectsPaymentTerm.PaymentTermSelect != this.defaultCostCampaign.paymentterm) {
                     console.log('5');
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.local.OneTime.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.local.OneTime.LeadspeekCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.local.OneTime.LeadspeekMinCostMonth;
                } else {*/
                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.LeadspeekCostperlead
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.LeadspeekMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.LeadspeekLeadsPerday;
                }else{
                    if (this.clientdefaultprice != "") {
                        this.LeadspeekPlatformFee = this.clientdefaultprice.local.Prepaid.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.clientdefaultprice.local.Prepaid.LeadspeekCostperlead
                        this.LeadspeekMinCostMonth = this.clientdefaultprice.local.Prepaid.LeadspeekMinCostMonth;
                    }else{
                        this.LeadspeekPlatformFee = this.defaultCostCampaign.local.Prepaid.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.defaultCostCampaign.local.Prepaid.LeadspeekCostperlead
                        this.LeadspeekMinCostMonth = this.defaultCostCampaign.local.Prepaid.LeadspeekMinCostMonth;
                        //this.LeadspeekLimitLead = this.defaultCostCampaign.local.Prepaid.EnhanceLeadsPerday;
                    }
                }

            } else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                this.txtLeadService = '';
                this.txtLeadIncluded = '';
                this.txtLeadOver = '';

                this.m_LeadspeekPlatformFee = this.costagency.local.OneTime.LeadspeekPlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.local.OneTime.LeadspeekCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.local.OneTime.LeadspeekMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.local.OneTime.LeadspeekLeadsPerday;

                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead
                /*if (this.selectsPaymentTerm.PaymentTermSelect != this.defaultCostCampaign.paymentterm) {
                     console.log('5');
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.local.OneTime.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.local.OneTime.LeadspeekCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.local.OneTime.LeadspeekMinCostMonth;
                } else {*/
                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.LeadspeekPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.LeadspeekCostperlead
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.LeadspeekMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.LeadspeekLeadsPerday;
                }

            }

            /** CALCULATION PROFIT PER TERM */
            this.profitcalculation();
            /** CALCULATION PROFIT PER TERM */
        },
        checkLeadsType() {
            if (this.selectsAppModule.LeadsLimitSelect == 'Max') {
                this.LeadspeekMaxDateVisible = true;
            } else {
                this.LeadspeekMaxDateVisible = false;
            }
        },
        formatPrice(value) {
            //let val = (value/1).toFixed(2).replace(',', '.')
            let val = (value/1).toFixed(2)
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
        },
        profitcalculation_() {
            this.t_LeadspeekPlatformFee = this.LeadspeekPlatformFee - this.m_LeadspeekPlatformFee;
            this.t_LeadspeekMinCostMonth = this.LeadspeekMinCostMonth - this.m_LeadspeekMinCostMonth;
            this.t_LeadspeekCostperlead = this.LeadspeekCostperlead - this.m_LeadspeekCostperlead;

            if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                //console.log(this.t_LeadspeekPlatformFee + ' | ' +  this.t_LeadspeekMinCostMonth + ' | ' + this.m_LeadspeekCostperlead + ' | ' + this.LeadspeekMaxLead);
                this.t_profit = this.formatPrice(this.t_LeadspeekPlatformFee + this.t_LeadspeekMinCostMonth - (this.m_LeadspeekCostperlead * this.LeadspeekMaxLead));
                this.t_LeadspeekCostperlead = this.formatPrice(this.m_LeadspeekCostperlead * this.LeadspeekMaxLead);
                this.t_freqshow = '';
            } else {
                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    this.t_freqshow = 'week';
                    this.t_freq = '1';
                } else {
                    this.t_freqshow = 'month';
                    this.t_freq = '4';
                }
                var _estleadspermonth = ((this.LeadspeekLimitLead * 7) * this.t_freq) * this.t_LeadspeekCostperlead;
                this.t_estleadspermonth = this.formatPrice(_estleadspermonth);
                this.t_profit = this.formatPrice(this.t_LeadspeekMinCostMonth + _estleadspermonth);
                this.t_LeadspeekCostperlead = this.formatPrice(this.t_LeadspeekCostperlead);
            }
        },
        // new function profitcalculation, add estimate client cost - AGIES
        profitcalculation() {
            if (this.LeadspeekLimitLead == "") {
                return false;
            }
            this.t_LeadspeekPlatformFee = this.LeadspeekPlatformFee - this.m_LeadspeekPlatformFee;
            this.t_LeadspeekMinCostMonth = this.LeadspeekMinCostMonth - this.m_LeadspeekMinCostMonth;
            this.t_LeadspeekCostperlead = this.LeadspeekCostperlead - this.m_LeadspeekCostperlead;

            if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                //console.log(this.t_LeadspeekPlatformFee + ' | ' +  this.t_LeadspeekMinCostMonth + ' | ' + this.m_LeadspeekCostperlead + ' | ' + this.LeadspeekMaxLead);
                this.t_profit = this.formatPrice(this.t_LeadspeekPlatformFee + this.t_LeadspeekMinCostMonth - (this.m_LeadspeekCostperlead * this.LeadspeekMaxLead));
                this.t_LeadspeekCostperlead = this.formatPrice(this.m_LeadspeekCostperlead * this.LeadspeekMaxLead);
                this.t_freqshow = '';
            } 
            // else if(this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && this.prepaidType == 'continual') {
            //     this.totalLeads.continualTopUp = this.LeadspeekLimitLead * 7 * 4;
            // } 
            else {
                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    this.t_freqshow = 'week';
                    this.t_freq = '1';
                } else {
                    this.t_freqshow = 'month';
                    this.t_freq = '4';
                }
                var _estleadspermonth = ((this.LeadspeekLimitLead * 7) * this.t_freq) * this.t_LeadspeekCostperlead;
                this.t_cost = this.formatPrice( this.t_LeadspeekMinCostMonth + (((this.LeadspeekCostperlead * this.LeadspeekLimitLead) * 7) * parseInt(this.t_freq)));
                this.t_estleadspermonth = this.formatPrice(_estleadspermonth);
                this.t_profit = this.formatPrice(this.t_LeadspeekMinCostMonth + _estleadspermonth);
                this.t_LeadspeekCostperlead = this.formatPrice(this.t_LeadspeekCostperlead);
                
                if(this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && this.prepaidType == 'continual') {
                    this.totalLeads.continualTopUp = this.contiDurationSelection ? (this.LeadspeekLimitLead * 7) * 4: (this.LeadspeekLimitLead * 7);
                }

            }
        },
        showQuestionnaire(index, row) {
            this.questionnaire.campaign_name = row.campaign_name;
            this.questionnaire.url_code = row.url_code;

            var aq = JSON.parse(row.questionnaire_answers);
            this.questionnaire.asec3_1 = aq.asec3_1;
            this.questionnaire.asec3_2 = aq.asec3_2;
            this.questionnaire.asec3_3 = (aq.asec3_3) ? 'I agree' : '';
            this.questionnaire.asec3_4 = aq.asec3_4;
            this.questionnaire.asec3_5 = aq.asec3_5;
            this.questionnaire.asec3_6 = aq.asec3_6;

            this.questionnaire.asec6_1 = (aq.asec6_1 == "GoogleSheet") ? "Google Sheet" : "Google Sheet";
            if (typeof (aq.asec6_2) != 'undefined') {
                this.questionnaire.asec6_2 = aq.asec6_2.replace('_', ' ');
            }
            this.questionnaire.asec6_3 = (typeof (aq.asec6_3) == 'undefined') ? '' : aq.asec6_3;
            this.questionnaire.asec6_4 = (typeof (aq.asec6_4) == 'undefined') ? '' : aq.asec6_4;

            if (aq.asec6_5 == "FirstName,LastName,MailingAddress") {
                this.questionnaire.asec6_5 = "Must Have Contact Name and Mailing Address";
            } else if (aq.asec6_5 == "FirstName,LastName") {
                this.questionnaire.asec6_5 = "Must Have Contact Name";
            } else if (aq.asec6_5 == "MailingAddress") {
                this.questionnaire.asec6_5 = "Must Have Mailing Address";
            }

            this.questionnaire.asec6_6 = (aq.asec6_6) ? "I understand and agree to follow the law and will notify anyone using these leads of the law." : "";
            this.questionnaire.asec6_7 = aq.asec6_7;

            this.LeadspeekCompany = row.campaign_name + ' #' + row.leadspeek_api_id + ' (' + row.company_name + ')';
            this.modals.questionnaire = true;
        },
        resetAgencyCost() {
            this.m_LeadspeekPlatformFee = '0';
            this.m_LeadspeekCostperlead = '0';
            this.m_LeadspeekMinCostMonth = '0';

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
        },
        getMasterAgencyCost() {
            this.resetAgencyCost();
            this.$store.dispatch('getGeneralSetting', {
                companyID: this.companyID,
                settingname: 'costagency',
                idSys: this.$global.idsys,
                pk: this.activeClientCompanyID,
            }).then(response => {
                //console.log(response.data);
                if (response.data != '') {
                    this.costagency = response.data;
                    this.clientdefaultprice = response.clientDefaultPrice;
                    if (typeof(this.costagency.local.Weekly.LeadspeekLeadsPerday) === 'undefined') {
                        this.costagency.local.Weekly.LeadspeekLeadsPerday = '10';
                    }
                    if (typeof(this.costagency.local.Monthly.LeadspeekLeadsPerday) === 'undefined') {
                        this.costagency.local.Monthly.LeadspeekLeadsPerday = '10';
                    }
                    if (typeof(this.costagency.local.OneTime.LeadspeekLeadsPerday) === 'undefined') {
                        this.costagency.local.OneTime.LeadspeekLeadsPerday = '10';
                    }

                    if (typeof(this.costagency.local.Prepaid) == 'undefined') {
                        this.$set(this.costagency.local,'Prepaid',{
                            LeadspeekPlatformFee: '0',
                            LeadspeekCostperlead: '0',
                            LeadspeekMinCostMonth: '0',
                            LeadspeekLeadsPerday: '10',
                        });
                    }

                    if (typeof(this.costagency.local.Prepaid.LeadspeekLeadsPerday) === 'undefined') {
                        this.$set(this.costagency.local.Prepaid, 'LeadspeekLeadsPerday', '10');
                        this.costagency.local.Prepaid.LeadspeekLeadsPerday = '10';
                    }
                    if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                        this.m_LeadspeekPlatformFee = this.costagency.local.Weekly.LeadspeekPlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.local.Weekly.LeadspeekCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.local.Weekly.LeadspeekMinCostMonth;
                        this.m_LeadspeekLimitLead = this.costagency.local.Weekly.LeadspeekLeadsPerday;
                    } else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                        this.m_LeadspeekPlatformFee = this.costagency.local.Monthly.LeadspeekPlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.local.Monthly.LeadspeekCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.local.Monthly.LeadspeekMinCostMonth;
                        this.m_LeadspeekLimitLead = this.costagency.local.Monthly.LeadspeekLeadsPerday;
                    } else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                        this.m_LeadspeekPlatformFee = this.costagency.local.OneTime.LeadspeekPlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.local.OneTime.LeadspeekCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.local.OneTime.LeadspeekMinCostMonth;
                        this.m_LeadspeekLimitLead = this.costagency.local.OneTime.LeadspeekLeadsPerday;
                    } else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                        this.m_LeadspeekPlatformFee = this.costagency.local.Prepaid.LeadspeekPlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.local.Prepaid.LeadspeekCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.local.Prepaid.LeadspeekMinCostMonth;
                        this.m_LeadspeekLimitLead = this.costagency.local.Prepaid.LeadspeekLeadsPerday;
                    }

                } else {
                    this.m_LeadspeekPlatformFee = '0';
                    this.m_LeadspeekCostperlead = '0';
                    this.m_LeadspeekMinCostMonth = '0';
                    this.m_LeadspeekLimitLead = '10';
                }

                /** HACK TEMPORARY FOR TRYSERA CAMPAIGN STILL */
                let campaignIDexception = ["2530", "2558", "2559", "2581", "2560", "2555", "2546", "2441", "2563", "2562"];
                let leadpriceException = ["0.15", "0.15", "0.15", "0.17", "0.17", "0.17", "0.17", "0.17", "0.17", "0.17"];
                const searchexecption = campaignIDexception.indexOf(this.ActiveLeadspeekID);

                if (searchexecption >= 0) {
                    this.m_LeadspeekCostperlead = leadpriceException[searchexecption];
                }
                /** HACK TEMPORARY FOR TRYSERA CAMPAIGN STILL */

                /** CALCULATION PROFIT PER TERM */
                this.profitcalculation();
                /** CALCULATION PROFIT PER TERM */

                this.modals.pricesetup = true;

            }, error => {

            });
        },
        handlePriceSet(index, row) {
            this.selectedRowData = row
            // console.log(row);
            this.activeClientCompanyID = row.company_id;
            this.activeClientCompanyParent = row.company_parent;
            this.ClientActiveID = row.id;
            this.selectsPaymentTerm.PaymentTermSelect = row.paymentterm;
            this.LeadspeekCostperlead = row.cost_perlead;
            this.LeadspeekMaxLead = row.lp_max_lead_month;
            this.LeadspeekMinCostMonth = row.lp_min_cost_month;
            this.LeadspeekLimitLead = row.lp_limit_leads;
            this.selectsAppModule.LeadsLimitSelect = row.lp_limit_freq;
            this.LeadspeekCompany = row.campaign_name + ' #' + row.leadspeek_api_id + ' (' + row.company_name + ')';
            this.LeadspeekclientEmail = row.email;
            this.LeadspeekClientActiveIndex = index;
            this.LeadspeekPlatformFee = row.platformfee;
            this.ActiveLeadspeekID = row.leadspeek_api_id;
            this.LeadspeekType = row.leadspeek_type;
            this.prepaidType = row.topupoptions ? row.topupoptions : 'continual';
            this.isContainulTopUpStop = (row.stopcontinual === 'T')?true:false;
            this.contiDurationSelection = row.continual_buy_options;
            // this.prepaidType = row.prepaid_type;
            // this.totalLeads.oneTime = row.total_leads_onetime;
            // console.log('handle price set');
            // console.log(this.LeadspeekType);
            // console.log('handle price set');

            this.defaultCostCampaign.paymentterm = row.paymentterm;
            this.defaultCostCampaign.LeadspeekCostperlead = row.cost_perlead;
            this.defaultCostCampaign.LeadspeekMinCostMonth = row.lp_min_cost_month;
            this.defaultCostCampaign.LeadspeekPlatformFee = row.platformfee;
            this.defaultCostCampaign.LeadspeekLeadsPerday = row.lp_limit_leads;
            this.paymentTermChange();

            //this.m_LeadspeekPlatformFee = (row.m_platformfee == null)?'0':row.m_platformfee;


            if (row.lp_limit_startdate == '' || row.lp_limit_startdate == null) {
                this.LeadspeekMaxDateStart = moment().format('YYYY-MM-DD HH:mm:ss');
            } else {
                this.LeadspeekMaxDateStart = moment(row.lp_limit_startdate).format('YYYY-MM-DD HH:mm:ss');
            }
            if (row.lp_enddate == '' || row.lp_enddate == null) {
                this.LeadspeekDateEnd = '';
            } else {
                this.LeadspeekDateEnd = row.lp_enddate !== '0000-00-00 00:00:00' ? moment(row.lp_enddate).format('YYYY-MM-DD HH:mm:ss') : '';
            }
            if (row.lp_limit_freq == 'max') {
                this.LeadspeekMaxDateVisible = true;
            } else {
                this.LeadspeekMaxDateVisible = false;
            }

            if (((row.active_user == 'T'  && row.active == 'T') || (row.active == 'F' && row.disabled == 'F')) && row.paymentterm != 'Prepaid') {
                this.LeadspeekInputReadOnly = true;
            } else {
                this.LeadspeekInputReadOnly = false;

                if (row.paymentterm == 'Prepaid' && row.active_user == 'F' && row.active == 'F') {
                    this.OnlyPrepaidDisabled = false;
                }else if (row.paymentterm != 'Prepaid' && row.active_user == 'F' && row.active == 'F') {
                    this.OnlyPrepaidDisabled = false;
                }else{
                    this.OnlyPrepaidDisabled = true;
                }
            }

            /** GET MASTER AGENCY COST */
            this.getMasterAgencyCost();
            /** GET MASTER AGENCY COST */

            /* GET remaining balance leads */
            this.getRemainingBalanceLeads();
            /* GET remaining balance leads */
        },
        getRemainingBalanceLeads() {
            this.$store.dispatch(`getRemainingBalanceLeads`, {
                leadspeek_api_id: this.ActiveLeadspeekID
            })
            .then(response => {
                this.remainingBalanceLeads = response.data.remainingBalanceLeads ? response.data.remainingBalanceLeads : 0;
            })
            .catch(error => {
                console.error(error);
            })
        },
        chkHideShowGTM(row) {
            if (this.selectedRowData.gtminstalled == 'T') {
                this.selectedRowData.gtminstalled = 'F';
            } else {
                this.selectedRowData.gtminstalled = 'T';
            }
        },
        chkHideShowCol(row) {
            if (row.hide_phone == 'T') {
                row.hide_phone = 'F';
            } else {
                row.hide_phone = 'T';
            }
        },
        companyGroupChange(_companyGroupSelected, id) {
            if (id == '') {
                for (let i = 0; i < this.selectsGroupCompany.companyGroupList.length; i++) {
                    if (this.selectsGroupCompany.companyGroupList[i].id == _companyGroupSelected) {
                        this.selectsGroupCompany.companyGroupID = this.selectsGroupCompany.companyGroupList[i].id;
                        this.selectsGroupCompany.newCompanyGroupName = this.selectsGroupCompany.companyGroupList[i].group_name;
                    }
                }
            } else {
                for (let i = 0; i < this.selectsGroupCompany.companyGroupList.length; i++) {
                    if (this.selectsGroupCompany.companyGroupList[i].id == _companyGroupSelected) {
                        id.group_company_id = this.selectsGroupCompany.companyGroupList[i].id;
                        id.group_name = this.selectsGroupCompany.companyGroupList[i].group_name;
                    }
                }
            }
        },
        processAddEditCompanyGroup(id) {

            if (id == '') {
                this.$store.dispatch('AddEditGroupCompany', {
                    companyGroupID: this.selectsGroupCompany.companyGroupID,
                    companyGroupName: this.selectsGroupCompany.newCompanyGroupName,
                    companyID: this.companyID,
                    moduleID: '3',
                }).then(response => {
                    //console.log(response[0]);
                    if (response.result == 'success') {
                        if (response.params.action == 'Add') {
                            this.selectsGroupCompany.companyGroupList.push({ 'id': response.params.id, 'group_name': response.params.group_name });
                            this.$global.selectsGroupCompany.companyGroupList.push({ 'id': response.params.id, 'group_name': response.params.group_name });
                            this.selectsGroupCompany.companyGroupSelected = response.params.id;
                            this.selectsGroupCompany.companyGroupID = response.params.id;
                            this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;

                        } else if (response.params.action == 'Edit') {
                            /** UPDATE */
                            for (let i = 0; i < this.selectsGroupCompany.companyGroupList.length; i++) {
                                if (this.selectsGroupCompany.companyGroupList[i].id == response.params.id) {
                                    this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;
                                    this.selectsGroupCompany.companyGroupList[i].group_name = response.params.group_name;
                                }
                            }

                            for (let i = 0; i < this.$global.selectsGroupCompany.companyGroupList.length; i++) {
                                if (this.$global.selectsGroupCompany.companyGroupList[i].id == response.params.id) {
                                    this.$global.selectsGroupCompany.companyGroupList[i].group_name = response.params.group_name;
                                }
                            }


                            /** UPDATE */
                        }

                        $('#editGroupName' + id).hide();
                        $('#listGroupName' + id).show();

                    }

                    this.$notify({
                        type: 'success',
                        message: 'Invitation has been sent!',
                        icon: 'far fa-save'
                    });

                }, error => {

                    this.$notify({
                        type: 'primary',
                        message: 'Sorry there is something wrong, pleast try again later',
                        icon: 'fas fa-bug'
                    });
                });

            } else {

                this.$store.dispatch('AddEditGroupCompany', {
                    companyGroupID: id.group_company_id,
                    companyGroupName: id.group_name,
                    companyID: this.companyID,
                    moduleID: '3',
                }).then(response => {
                    //console.log(response[0]);
                    if (response.result == 'success') {
                        if (response.params.action == 'Add') {
                            this.selectsGroupCompany.companyGroupList.push({ 'id': response.params.id, 'group_name': response.params.group_name });
                            this.$global.selectsGroupCompany.companyGroupList.push({ 'id': response.params.id, 'group_name': response.params.group_name });
                            id.group_company_id = response.params.id;
                            id.group_name = response.params.group_name;
                            //this.selectsGroupCompany.companyGroupID = response.params.id;
                            //this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;

                        } else if (response.params.action == 'Edit') {
                            /** UPDATE */
                            for (let i = 0; i < this.selectsGroupCompany.companyGroupList.length; i++) {
                                if (this.selectsGroupCompany.companyGroupList[i].id == response.params.id) {
                                    id.group_name = response.params.group_name;
                                    //this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;
                                    this.selectsGroupCompany.companyGroupList[i].group_name = response.params.group_name;
                                }
                            }

                            for (let i = 0; i < this.$global.selectsGroupCompany.companyGroupList.length; i++) {
                                if (this.$global.selectsGroupCompany.companyGroupList[i].id == response.params.id) {
                                    this.$global.selectsGroupCompany.companyGroupList[i].group_name = response.params.group_name;
                                }
                            }


                            /** UPDATE */
                        }

                        id = id.id;
                        $('#editGroupName' + id).hide();
                        $('#listGroupName' + id).show();

                    }

                    this.$notify({
                        type: 'success',
                        message: 'Invitation has been sent!',
                        icon: 'far fa-save'
                    });

                }, error => {

                    this.$notify({
                        type: 'primary',
                        message: 'Sorry there is something wrong, pleast try again later',
                        icon: 'fas fa-bug'
                    });
                });
            }
        },
        cancelAddeditCompanyGroup(id) {
            if (id == '') {
                this.selectsGroupCompany.companyGroupSelected = '';
                this.selectsGroupCompany.companyGroupID = '';
                this.selectsGroupCompany.newCompanyGroupName = ''
            } else {
                id = id.id;
                //console.log('edit : ' + id);
            }
            $('#editGroupName' + id).hide();
            $('#listGroupName' + id).show();
        },
        removeCompanyGroup(act, id) {
            var _companyGroupID;

            if (id == '') {
                _companyGroupID = this.selectsGroupCompany.companyGroupID;
            } else {
                _companyGroupID = id.group_company_id;
            }

            swal.fire({
                title: 'Are you sure want to delete this?',
                text: `You won't be able to revert this!`,
                icon: '',
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-default btn-fill',
                    cancelButton: 'btn btn-danger btn-fill'
                },
                confirmButtonText: 'Ok',
                buttonsStyling: false
            }).then(result => {
                if (result.value) {
                    /** REMOVE COMPANY GROUP */

                    this.$store.dispatch('RemoveCompanyGroup', {
                        companyID: this.companyID,
                        companyGroupID: _companyGroupID,
                    }).then(response => {

                        this.selectsGroupCompany.companyGroupSelected = '';
                        this.selectsGroupCompany.companyGroupID = '';
                        this.selectsGroupCompany.newCompanyGroupName = '';

                        id.group_company_id = '';
                        id.group_name = '';

                        /** REMOVE THE COMPANY GROUP FROM ARRAY */
                        for (let i = 0; i < this.selectsGroupCompany.companyGroupList.length; i++) {
                            if (this.selectsGroupCompany.companyGroupList[i].id == _companyGroupID) {
                                this.selectsGroupCompany.companyGroupList.splice(i, 1);
                            }
                        }

                        for (let i = 0; i < this.$global.selectsGroupCompany.companyGroupList.length; i++) {
                            if (this.$global.selectsGroupCompany.companyGroupList[i].id == _companyGroupID) {
                                this.$global.selectsGroupCompany.companyGroupList.splice(i, 1);
                            }
                        }
                        /** REMOVE THE COMPANY GROUP FROM ARRAY */
                        swal.fire({
                            title: 'Deleted!',
                            text: `You deleted Company Group`,
                            icon: 'success',
                            confirmButtonClass: 'btn btn-default btn-fill',
                            buttonsStyling: false
                        });
                    }, error => {

                    });

                    /** REMOVE COMPANY GROUP */
                }
            });

        },
        addeditCompanyGroup(act, id) {
            if (id == '') {
                if (act == 'Add') {
                    this.selectsGroupCompany.companyGroupID = '';
                    this.selectsGroupCompany.newCompanyGroupName = '';
                } else if (act == 'Edit') {
                    if (this.selectsGroupCompany.companyGroupID == '') {
                        return;
                    }
                }
            } else {
                if (act == 'Add') {
                    id.group_company_id = '';
                    id.group_name = '';
                } else if (act == 'Edit') {
                    if (id.group_company_id == '') {
                        return;
                    }
                    //id.group_company_id = this.selectsGroupCompany.companyGroupID;
                    //id.group_name = this.selectsGroupCompany.newCompanyGroupName;
                }

                id = id.id;
            }

            $('#listGroupName' + id).hide();
            $('#editGroupName' + id).show();

        },
        sortcolumn: function (a, b) {
            return a.value - b.value;
        },
        sortdate: function (a, b) {
            return new Date(a.last_lead_added) - new Date(b.last_lead_added);
        },
        sortnumber: function (a, b) {
            return a - b;
        },
        process_activepauseclient(index, row) {
            var _status = 'F';

            if (row.disabled == 'F') {
                _status = 'T';
            }

            if (row.disabled == 'F') {
                this.popProcessingTxt = "Please wait, pausing campaign ....";
            }else{
                this.popProcessingTxt = "Please wait, activating campaign ....";
            }

            $('#processingArea').addClass('disabled-area');
            $('#popProcessing').show();

            this.$store.dispatch('activepauseLeadsPeek', {
                companyID: this.companyID,
                leadspeekID: row.id,
                status: _status,
                activeuser: row.active_user,
                userID: row.user_id,
                ip_user: this.$store.getters.userData.ip_login,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
            }).then(result => {
                if (typeof (result.result) !== 'undefined' && result.result === 'failedPayment') {
                    $('#processingArea').removeClass('disabled-area');
                    $('#popProcessing').hide();

                    $('#activePause' + index).removeClass('fas fa-pause gray').addClass('fas fa-pause orange nocursor');
                    $('#activePlay' + index).removeClass('fas fa-play green nocursor').addClass('fas fa-play gray');
                    this.GetClientList();
                    this.$notify({
                        type: 'danger',
                        message: result.msg,
                        icon: 'fas fa-bug'
                    });
                    return false;
                } else {
                       
                    $('#processingArea').removeClass('disabled-area');
                    $('#popProcessing').hide();
                    if (row.disabled == 'F') {
                        _status = 'T';
                        row.disabled = 'T';
                        $('#activePause' + index).removeClass('fas fa-pause gray').addClass('fas fa-pause orange nocursor');
                        $('#activePlay' + index).removeClass('fas fa-play green nocursor').addClass('fas fa-play gray');
                        this.$notify({
                            type: 'success',
                            message: 'Campaign successfully paused.',
                            icon: 'tim-icons icon-bell-55'
                        });
                    }else{
                        row.disabled = 'F';
                        row.active_user = 'T';
                        $('#activePause' + index).removeClass('fas fa-pause orange').addClass('fas fa-pause gray');
                        $('#activePlay' + index).removeClass('fas fa-play gray').addClass('fas fa-play green nocursor');
                        let msg = 'Campaign successfully activated.';
                        if (row.active_user == 'F') {
                            msg = "Initial Campaign Start...";
                        }
                        this.$notify({
                            type: 'success',
                            message: msg,
                            icon: 'tim-icons icon-bell-55'
                        });
                    }
                }
            }).catch(error => {
                $('#processingArea').removeClass('disabled-area');
                $('#popProcessing').hide();

                this.$notify({
                    type: 'primary',
                    message: 'sorry this campaign cannot be started, please contact your administrator with campaign ID : #' + row.leadspeek_api_id + ' (1)',
                    icon: 'fas fa-bug'
                });
            });
           
        },
        async activepauseclient(index, row, action) {
            if(row.paymentterm.toLowerCase() === 'prepaid' && (row.leadsbuy == 0 || row.leadsbuy == null)) {
                swal.fire({
                    icon: "error",
                    text: "You cannot start the campaign until you have set up the campaign financials. Please click the dollar icon to set them up.",
                })
                return false;
            }

            if ((action == 'pause' && row.disabled == 'T')) {
                return false;
            } else if ((action == 'play' && row.disabled == 'F')) {
                return false;
            } else if (action == 'play' && row.disabled == 'T' && row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                return false;
            }

            /* GET COSTAGENCY IF COST_PERLEAD ZERO */
            if(row.paymentterm.toLowerCase() === 'prepaid' && parseFloat(row.cost_perlead) == 0 && this.$store.getters.userData.user_type == 'userdownline') {
                this.contiDurationSelection = row.continual_buy_options;
                try {
                    const response = await this.$store.dispatch('getGeneralSetting', {
                        companyID: this.companyID,
                        settingname: 'costagency',
                        idSys: this.$global.idsys,
                        pk: row.company_id,
                    });
                    const costagency = response.data;
                    this.m_LeadspeekPlatformFee = costagency.local.Prepaid.LeadspeekPlatformFee;
                    this.m_LeadspeekCostperlead = costagency.local.Prepaid.LeadspeekCostperlead;
                } catch (error) {
                    console.error(error);
                    return false;
                }
            }
            /* GET COSTAGENCY IF COST_PERLEAD ZERO */

            if (action == 'play' && row.disabled == 'T' && row.active_user == 'F') {
                // var defaultText = 'Activating this will charge a One Time startup fee of $' + row.platformfee + ' and the ' + row.paymentterm + ' fee of $' + row.lp_min_cost_month + '. This campaign is set up for ' + row.paymentterm.toLowerCase() + ' billing and your client will be billed on the same day each ' + row.paymentterm.toLowerCase() + '.';
                // const userData = this.$store.getters.userData;
                // if (userData.user_type == 'client') {
                //     defaultText = 'Activating this will charge a One Time startup fee of $' + row.platformfee + ' and the ' + row.paymentterm + ' fee of $' + row.lp_min_cost_month + '. This campaign is set up for ' + row.paymentterm.toLowerCase() + ' billing and you will be billed on the same day each ' + row.paymentterm.toLowerCase() + '.';
                // }
                var btnOkName = 'Ok';
                var defaultText = '<p style="text-align: left; color: initial;">Activating this will authorize the following charges:<br>';
                    defaultText += '<ul style="text-align: left;">';
                    defaultText += '<li class="alert-li">A start up fee of $' + this.formatPrice(row.platformfee) + '</li>';
                    
                    // jika prepaid
                    if(row.paymentterm.toLowerCase() === 'prepaid') {
                        defaultText += '<li class="alert-li">  A monthly campaign fee of $' + this.formatPrice(row.lp_min_cost_month) + ' starting one month after today</li>';
                    } else {
                        defaultText += '<li class="alert-li">  A  ' + row.paymentterm.toLowerCase() + ' campaign fee of $' + this.formatPrice(row.lp_min_cost_month) + '</li>';
                    }

                    defaultText += '<li class="alert-li">Up to ';
                        
                    if (row.lp_limit_leads == 0){
                        defaultText += 'Unlimited' + ' leads per day at a cost of $' + this.formatPrice(row.cost_perlead) + ' each lead </li>';
                    }else{
                        defaultText += row.lp_limit_leads + ' leads per day at a cost of $' + this.formatPrice(row.cost_perlead) + ' each lead </li>';
                    }
                    defaultText += '</ul>';

                    // jika prepaid
                    if(row.paymentterm.toLowerCase() === 'prepaid') {
                        defaultText += 'This campaign is set up for ' + row.paymentterm.toLowerCase() + ' billing and will charge ';

                        if(parseFloat(row.cost_perlead) == 0 && this.$store.getters.userData.user_type == 'userdownline') {
                            defaultText += "the Cients Card ";
                        }
                    } else {
                        defaultText += 'This campaign is set up for ' + row.paymentterm.toLowerCase() + ' billing and will be billed on the same day each ' + row.paymentterm.toLowerCase().replace(/ly\b/g,'') + '. ';
                        defaultText += 'If this campaign reaches its ' + row.paymentterm.toLowerCase() + ' lead limit goal, your total cost for leads will be ';    
                    }
                    
                    if (row.lp_limit_leads == 0) {
                        defaultText += 'unlimited.<br/>';
                    }else{ 
                        if(row.paymentterm.toLowerCase() === 'prepaid' && row.topupoptions === 'continual') {
                            var platformfee = parseFloat(row.platformfee);
                            var cost_perlead = parseFloat(row.cost_perlead);
                            var lp_limit_leads = parseInt(row.lp_limit_leads);
                            var totalCost = this.contiDurationSelection ? this.formatPrice(platformfee + (((cost_perlead * lp_limit_leads) * 7) * 4)) : this.formatPrice(platformfee + ((cost_perlead * lp_limit_leads) * 7));
                            //var totalCost = this.formatPrice(((cost_perlead * lp_limit_leads) * 7) * 4);
                            defaultText += '$' +  totalCost;

                            if(parseFloat(row.cost_perlead) == 0 && this.$store.getters.userData.user_type == 'userdownline') {
                                var agency_platformfee = parseFloat(this.m_LeadspeekPlatformFee);
                                var agency_cost_perlead = parseFloat(this.m_LeadspeekCostperlead);
                                var agency_total_cost = this.contiDurationSelection ? this.formatPrice(agency_platformfee + (((agency_cost_perlead * lp_limit_leads) * 7) * 4)) : this.formatPrice(agency_platformfee + ((agency_cost_perlead * lp_limit_leads) * 7));
                                defaultText += ` and charge your Agency Card on record $${agency_total_cost}`;
                            }
                        }
                        else if(row.paymentterm.toLowerCase() === 'prepaid' && row.topupoptions === 'onetime') {
                            var platformfee = parseFloat(row.platformfee);
                            var cost_perlead = parseFloat(row.cost_perlead);
                            var leadsbuy = parseInt(row.leadsbuy);
                            var totalCost = this.formatPrice(platformfee + (cost_perlead * leadsbuy));
                            //var totalCost = this.formatPrice(cost_perlead * leadsbuy);
                            defaultText += '$' +  totalCost;

                            if(parseFloat(row.cost_perlead) == 0 && this.$store.getters.userData.user_type == 'userdownline') {
                                var agency_platformfee = parseFloat(this.m_LeadspeekPlatformFee);
                                var agency_cost_perlead = parseFloat(this.m_LeadspeekCostperlead);
                                var agency_total_cost = this.formatPrice(agency_platformfee + (agency_cost_perlead * leadsbuy));
                                defaultText += ` and charge your Agency Card on record $${agency_total_cost}`;
                            }
                        }
                        else {
                            var lp_min_cost_month = parseFloat(row.lp_min_cost_month);
                            var cost_perlead = parseFloat(row.cost_perlead);
                            var lp_limit_leads = parseInt(row.lp_limit_leads);
                            var totalCost = this.formatPrice(lp_min_cost_month + (((cost_perlead * lp_limit_leads) * 7)));
                            defaultText += '$' +  totalCost +  '.<br>';
                        }
                    }

                    // jika prepaid
                    if(row.paymentterm.toLowerCase() === 'prepaid') {
                        if (row.topupoptions == 'continual') {
                            defaultText += ' immediately, and again when the campaign falls to ' + row.lp_limit_leads + ' leads available'
                        }else if (row.topupoptions == 'onetime') {
                            defaultText += ' immediately. ';
                        }
                    }
                    /** IF PREPAID PUT THIS AGREEMENT */
                    if(row.paymentterm.toLowerCase() === 'prepaid') {
                        defaultText += '<br><br>';
                        defaultText += 'I understand that the purchase of prepaid leads is non-refundable.';
                        btnOkName = 'I agree';
                    }
                    /** IF PREPAID PUT THIS AGREEMENT */
                    defaultText += '<br>';
                    if (row.lp_limit_leads == 0 ) {
                        defaultText += '<span><strong style="color:red">WARNING: </strong>by setting a campaign to Unlimited leads, the cost for this campaign could be thousands of dollars per day.</span>';
                    }
                    defaultText += '</p>';

                swal.fire({
                    title: "Are you sure you want to activate this campaign?",
                    html: defaultText,
                    icon: '',
                    showCancelButton: true,
                    customClass: {
                        confirmButton: 'btn btn-default btn-fill',
                        cancelButton: 'btn btn-danger btn-fill'
                    },
                    confirmButtonText: btnOkName,
                    buttonsStyling: false
                }).then(result => {
                    if (result.value) {
                        this.process_activepauseclient(index, row);
                    }
                });

            } else {
                this.process_activepauseclient(index, row);
            }

        },
        setIconReportType(row) {
            if (row.report_type == "CSV") {
                return '<i class="fas fa-file-csv" style="color:white;padding-right:10px"></i> CSV File';
            } else if (row.report_type == "Excel") {
                return '<i class="far fa-file-excel" style="color:white;padding-right:10px"></i> Microsoft Excel File';
            } else if (row.report_type == "GoogleSheet") {
                return '<i class="fab fa-google-drive" style="color:white;padding-right:10px"></i> <a style="color:white" href="https://docs.google.com/spreadsheets/d/' + row.spreadsheet_id + '/edit#gid=0" target="_blank">Google Spreadsheet</a>';
            }
        },
        disconnect_googleSpreadSheet() {
            this.$store.dispatch('disconectGoogleSheet', {
                companyID: this.companyID,
            }).then(response => {
                //console.log(response.googleSpreadsheetConnected);
                this.GoogleConnectTrue = false;
                this.GoogleConnectFalse = true;
            }, error => {

            });
        },
        connect_googleSpreadSheet() {
            window.removeEventListener('message', this.callbackGoogleConnected);
            window.addEventListener('message', this.callbackGoogleConnected);

            var left = (screen.width / 2) - (1024 / 2);
            var top = (screen.height / 2) - (800 / 2);
            var fbwindow = window.open(process.env.VUE_APP_DATASERVER_URL + '/auth/google-spreadSheet/' + this.companyID, 'Google SpreadSheet Auth', "menubar=no,toolbar=no,status=no,width=640,height=800,toolbar=no,location=no,modal=1,left=" + left + ",top=" + top);
        },
        onCompanyChange(_companySelected) {
            for (let i = 0; i < this.selectsCompany.companyList.length; i++) {
                if (this.selectsCompany.companyList[i].id == _companySelected) {
                    this.ClientFullName = this.selectsCompany.companyList[i].name;
                    this.ClientEmail = this.selectsCompany.companyList[i].email;
                    this.ClientPhone = this.selectsCompany.companyList[i].phonenum;
                    this.ClientUserID = this.selectsCompany.companyList[i].id;
                    this.ClientCompanyName = this.selectsCompany.companyList[i].company_name;
                    this.selectsCompany.companySelected = this.selectsCompany.companyList[i].company_name;
                    this.ClientReportSentTo = this.selectsCompany.companyList[i].email;
                }
            }
        },
        rowClicked(index, row) {
           
            this.modals.campaignEdit = !this.modals.campaignEdit
            this.selectedRowData = row
            if (row.homeaddressenabled == 'T') {
                row.homeaddressenabled = true;
            } else {
                row.homeaddressenabled = false;
            }

            if (row.phoneenabled == 'T') {
                row.phoneenabled = true;
            } else {
                row.phoneenabled = false;
            }

            if (row.require_email == 'T') {
                row.require_email = true;
            } else {
                row.require_email = false;
            }

            if (row.applyreidentificationall == 'T') {
                row.applyreidentificationall = true;
            } else {
                row.applyreidentificationall = false;
            }

            
        },
        tableRowClassName({ row, rowIndex }) {
            row.index = rowIndex;
            return 'clickable-rows ClientRow' + rowIndex;
        },
        ClearClientForm() {
            this.ClientCompanyName = '';
            // this.ClientFullName = '';
            // this.ClientEmail = '';
            // this.ClientPhone = '';
            // this.ClientUserID = '';
            // this.ClientReportSentTo = '';
            this.ClientPerLead = '100';
            this.radios.reportType = 'GoogleSheet';
            this.selectsAdministrator.administratorSelected = [];
            this.ClientCampaignName = '';
            this.ClientUrlCode = '';
            this.ClientUrlCodeThankyou = '';
            let _tmpdefaultadmin = Array();
            $.each(this.tmpdefaultadmin, function (key, value) {
                if (value['defaultadmin'] == 'T') {
                    _tmpdefaultadmin.push(value['id']);
                }
            });

            this.selectsAdministrator.administratorSelected = _tmpdefaultadmin;
            // if ((localStorage.getItem('companyGroupSelected') == null || localStorage.getItem('companyGroupSelected') == '')) {
            //     this.selectsCompany.companySelected = '';
            // }
            this.checkboxes.phoneenabled = false;
            this.checkboxes.homeaddressenabled = false;
            this.checkboxes.requireemailaddress = true;
            this.checkboxes.ApplyReidentificationToAll = false;

        },
        ClearClientFormEdit(id) {
            if (id.homeaddressenabled) {
                id.homeaddressenabled = 'T';
            } else {
                id.homeaddressenabled = 'F';
            }

            if (id.phoneenabled) {
                id.phoneenabled = 'T';
            } else {
                id.phoneenabled = 'F';
            }

            if (id.applyreidentificationall) {
                id.applyreidentificationall = 'T';
            } else {
                id.applyreidentificationall = 'F';
            }

            if (id.require_email) {
                id.require_email = 'T';
            } else {
                id.require_email = 'F';
            }
        },
        AddEditClient(id) {
            this.modals.campaign = !this.modals.campaign 
            // $('#showAddEditClient' + id).collapse('show');
        },
        CancelAddEditClient(id) {
            if (id == '') {
                this.ClearClientForm();
                this.modals.campaign = false
            } else {
                this.ClearClientFormEdit(id);
              
                this.modals.campaignEdit = false
            }

        },
        ResendLink(id) {
            if (id.id != '') {
                $('#btnResend' + id.id).attr('disabled', true);
                $('#btnResend' + id.id).html('Sending...');

                /** RESEND THE INVITATION */
                this.$store.dispatch('ResendGoogleLink', {
                    companyID: this.companyID,
                    userID: id.company_id,
                    leadspeekID: id.id,
                    reportSentTo: id.report_sent_to,
                    adminNotifyTo: id.admin_notify_to,
                }).then(response => {
                    //console.log(response[0]);
                    this.modals.campaignEdit = false
                    // this.$refs.tableData.toggleRowExpansion(id);
                    $('#btnResend' + id.id).attr('disabled', false);
                    $('#btnResend' + id.id).html('Resend Google Sheet Link');

                    this.$notify({
                        type: 'success',
                        message: 'Invitation has been sent!',
                        icon: 'far fa-save'
                    });

                }, error => {
                    $('#btnResend' + id.id).attr('disabled', false);
                    $('#btnResend' + id.id).html('Resend Google Sheet Link');

                    this.$notify({
                        type: 'primary',
                        message: 'Sorry there is something wrong, pleast try again later',
                        icon: 'fas fa-bug'
                    });
                });
                /** RESEND THE INVITATION */
            }
        },
        ProcessAddEditClient(id) {
            if (id == '') {
                /** PROCESS ADD / EDIT ORGANIZATION */
                if (id == '') {
                    var _formpass = true;

                    if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '')) {
                        this.selectsGroupCompany.companyGroupID = localStorage.getItem('companyGroupSelected');
                    } else {
                        this.selectsGroupCompany.companyGroupID = this.ClientUserID;
                    }
                    //HIDE PHONE WILL BE DEFAULT WHEN ADD CLIENT
                    var hidePhone = 'T';
                    var gtminstalled = 'F';
                    if (this.checkboxes.hide_phone) {
                        hidePhone = 'T';
                    }

                    if (this.checkboxes.gtm) {
                        gtminstalled = 'T';
                    }

                    if (this.ClientCampaignName == "") {
                        _formpass = false;
                        $('#err_campaignname').show();
                    } else {
                        $('#err_campaignname').hide();
                    }

                    if (this.selectsCompany.companySelected == '') {
                        _formpass = false;
                        this.err_companyselect = true;
                    } else {
                        this.err_companyselect = false;
                    }

                    if (_formpass == false) {
                        this.$notify({
                            type: 'primary',
                            message: 'Please check the required fields.',
                            icon: 'fas fa-bug'
                        });
                        return false;
                    }

                    this.popProcessingTxt = "Please wait, adding new campaign ....";
                    $('#processingArea').addClass('disabled-area');
                    $('#popProcessing').show();

                    $('#btnSave' + id).attr('disabled', true);
                    $('#btnSave' + id).html('Processing...');
                    /** CREATE CLIENT */
                    this.$store.dispatch('CreateLeadsPeekClient', {
                        companyID: this.companyID,
                        userID: this.ClientUserID,
                        companyName: this.ClientCompanyName,
                        reportType: this.radios.reportType,
                        reportSentTo: this.ClientReportSentTo,
                        adminNotifyTo: this.selectsAdministrator.administratorSelected,
                        leadsAmountNotification: this.ClientPerLead,
                        leadspeekType: 'local',
                        companyGroupID: this.selectsGroupCompany.companyGroupID,
                        clientHidePhone: hidePhone,
                        campaignName: this.ClientCampaignName,
                        urlCode: this.ClientUrlCode,
                        urlCodeThankyou: this.ClientUrlCodeThankyou,
                        gtminstalled: gtminstalled,
                        phoneenabled: this.checkboxes.phoneenabled,
                        homeaddressenabled: this.checkboxes.homeaddressenabled,
                        requireemailaddress: this.checkboxes.requireemailaddress,
                        reidentificationtype: this.Reidentificationtime,
                        applyreidentificationall: this.checkboxes.ApplyReidentificationToAll,
                        locationtarget: 'Focus',
                    }).then(response => {
                        //console.log(response);
                        //console.log(response[0]);
                        if (typeof (response.result) != 'undefined' && response.result == 'failed') {
                            $('#processingArea').removeClass('disabled-area');
                            $('#popProcessing').hide();
                            this.modals.campaign = false
                            $('#btnSave' + id).attr('disabled', false);
                            $('#btnSave' + id).html('Save');

                            this.$notify({
                                type: 'primary',
                                message: response.message,
                                icon: 'fas fa-bug'
                            });
                            return;
                        } else {
                            response = this.AttachedAdminNotify(response);
                            this.tableData.push(response[(response.length - 1)]);
                            this.modals.campaign = false
                            this.initialSearchFuse();
                            this.ClearClientForm();
                            $('#showAddEditClient' + id).collapse('hide');
                            $('#processingArea').removeClass('disabled-area');
                            $('#popProcessing').hide();
                            
                            $('#btnSave' + id).attr('disabled', false);
                            $('#btnSave' + id).html('Save');
                            
                            this.$notify({
                                type: 'success',
                                message: 'Data has been added successfully',
                                icon: 'far fa-save'
                            });
                            
                            this.GetClientList(this.currSortBy,this.currOrderBy)
                        }
                    }, error => {
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();

                        $('#btnSave' + id).attr('disabled', false);
                        $('#btnSave' + id).html('Save');

                        this.$notify({
                            type: 'primary',
                            message: 'Server might be busy please try again later',
                            icon: 'fas fa-bug'
                        });
                    });
                    /** CREATE CLIENT */
                }
                /** PROCESS ADD / EDIT ORGANIZATION */
            } else {

                if ((id.report_type != '' && id.email != '') && (typeof id.report_type != 'undefined' && typeof id.email != 'undefined')) {
                    var _formpass = true;

                    if (id.campaign_name == "") {
                        _formpass = false;
                        $('#err_campaignname' + id.id).show();
                    } else {
                        $('#err_campaignname' + id.id).hide();
                    }

                    if (_formpass == false) {
                        this.$notify({
                            type: 'primary',
                            message: 'Please check the required fields.',
                            icon: 'fas fa-bug'
                        });
                        return false;
                    }

                    this.popProcessingTxt = "Please wait, updating campaign ....";
                    $('#processingArea').addClass('disabled-area');
                    $('#popProcessing').show();

                    $('#btnSave' + id.id).attr('disabled', true);
                    $('#btnSave' + id.id).html('Processing...');
                    /** UPDATE CLIENT */
                    this.$store.dispatch('UpdateLeadsPeekClient', {
                        companyID: this.companyID,
                        userID: id.company_id,
                        leadspeekID: id.id,
                        reportType: id.report_type,
                        reportSentTo: id.report_sent_to,
                        adminNotifyTo: id.admin_notify_to,
                        leadsAmountNotification: id.leads_amount_notification,
                        leadspeekType: id.leadspeek_type,
                        companyGroupID: id.group_company_id,
                        clientHidePhone: id.hide_phone,
                        campaignName: id.campaign_name,
                        urlCode: id.url_code,
                        urlCodeThankyou: id.url_code_thankyou,
                        gtminstalled: id.gtminstalled,

                        phoneenabled: id.phoneenabled,
                        homeaddressenabled: id.homeaddressenabled,
                        requireemailaddress: id.require_email,
                        reidentificationtype: id.reidentification_type,
                        applyreidentificationall: id.applyreidentificationall,
                    }).then(response => {
                        //console.log(response[0]);
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();
                        this.modals.campaignEdit = false

                        $('#btnSave' + id.id).attr('disabled', false);
                        $('#btnSave' + id.id).html('Save');

                        this.$notify({
                            type: 'success',
                            message: 'Data has been updated successfully',
                            icon: 'far fa-save'
                        });

                        this.ClearClientFormEdit(id);

                        if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '') && localStorage.getItem('companyGroupSelected') != id.group_company_id) {
                            this.deleteRow(id);
                        }

                        if (id.leadspeek_type != 'local') {
                            this.deleteRow(id);
                        }

                        this.GetClientList(this.currSortBy,this.currOrderBy)
                    }, error => {
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();

                        $('#btnSave' + id.id).attr('disabled', false);
                        $('#btnSave' + id.id).html('Save');

                        this.$notify({
                            type: 'primary',
                            message: 'Server might be busy please try again later',
                            icon: 'fas fa-bug'
                        });
                    });
                    /** UPDATE CLIENT */
                }
            }
        },
        checkGoogleConnect() {
            this.$store.dispatch('checkGoogleConnectSheet', {
                companyID: this.companyID,
            }).then(response => {
                //console.log(response.googleSpreadsheetConnected);
                if (response.googleSpreadsheetConnected) {
                    this.GoogleConnectTrue = true;
                    this.GoogleConnectFalse = false;
                } else {
                    this.GoogleConnectTrue = false;
                    this.GoogleConnectFalse = true;
                }
            }, error => {

            });
        },
        AttachedAdminNotify(response) {
            /** SET ADMIN NOTIFY */
            for (let i = 0; i < response.length; i++) {
                var tmp = "";
                var tmpArray = Array();
                tmp = response[i]['admin_notify_to'];
                tmpArray = tmp.split(",");
                for (let k = 0; k < tmpArray.length; k++) {
                    tmpArray[k] = parseInt(tmpArray[k]);
                }
                response[i]['admin_notify_to'] = tmpArray;

                //response[i]['admin_notify_to'].push(split);
            }
            return response;
            /** SET ADMIN NOTIFY */
        },
        GetAdministratorList() {
            this.$store.dispatch('GetClientList', {
                companyID: this.companyID,
                idsys: this.$global.idsys,
                userType: 'user',
            }).then(response => {
                let _tmpdefaultadmin = Array();
                $.each(response, function (key, value) {
                    if (value['defaultadmin'] == 'T') {
                        _tmpdefaultadmin.push(value['id']);
                    }
                });
                this.tmpdefaultadmin = response;
                this.selectsAdministrator.administratorSelected = _tmpdefaultadmin;
                this.selectsAdministrator.administratorList = response;
            }, error => {

            });
        },
        GetCompanyList() {
            this.fetchingCampaignData = true;
            $('.el-select-dropdown__empty').html('Loading data...');
            var _groupCompanyID = '';

            if (this.userType == 'client') {
                const userData = this.$store.getters.userData;
                _groupCompanyID = userData.id;
            }
           
            /** GET CLIENT LIST */
            this.$store.dispatch('GetClientList', {
                companyID: this.companyID,
                idsys: this.$global.idsys,
                userType: 'client',
                userModule: 'LeadsPeek',
                groupCompanyID: _groupCompanyID,
            }).then(response => {
                //console.log(response);
                if (response.length == 0) {
                    $('.el-select-dropdown__empty').html('No new client');
                }
                this.selectsCompany.companyList = response
                if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '')) {
                    this.onCompanyChange(localStorage.getItem('companyGroupSelected'));
                    this.CompanyNamedisabled = true;
                }
                // this.fetchingCampaignData = false
            }, error => {
                this.fetchingCampaignData = false
            });
            /** GET CLIENT LIST */
        },
        sortdynamic(column, order, prop) {
        
            this.currSortBy = column;
            this.currOrderBy = order;
            this.GetClientList(column, order);
        },
        GetClientList(sortby, order,searchkey) {
            this.fetchingCampaignData = true;
            var _sortby = '';
            var _order = '';
            var _searchkey = '';

            if (typeof (sortby) != 'undefined') {
                _sortby = sortby;
            }
            if (typeof (order) != 'undefined') {
                _order = order;
            }

            if (this.searchQuery != '') {
                _searchkey = this.searchQuery;
              
            }


            var _groupCompanyID = '';
            if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '')) {
                _groupCompanyID = localStorage.getItem('companyGroupSelected');
            }

            /** GET CLIENT LIST */
            // this.tableData = [];
            $('.el-table__empty-text').html('<i class="fas fa-spinner fa-pulse fa-2x d-block"></i>Loading data...');
            this.$store.dispatch('GetLeadsPeekClientList', {
                companyID: this.companyID,
                leadspeekType: 'local',
                groupCompanyID: _groupCompanyID,
                sortby: _sortby,
                order: _order,
                searchkey: _searchkey,
                page:this.pagination.currentPage,
                view: 'campaign',
                campaignStatus: this.filterCampaignStatus,
            }).then(response => {
                //console.log(response)
                //console.log(response.length);
                this.pagination.currentPage = response.current_page? response.current_page : 1
                this.pagination.total = response.total ?response.total : 0
                this.pagination.lastPage = response.last_page ? response.last_page : 2
                this.pagination.from = response.from ? response.from : 0
                this.pagination.to = response.to ? response.to : 0

                if (response.data.length == 0) {
                    $('.el-table__empty-text').html('No Data');
                }
                
                this.tableData = this.AttachedAdminNotify(response.data);
                // this.searchedData = this.tableData;
                // this.searchQuery = "";
                this.initialSearchFuse();
                this.fetchingCampaignData = false;
            }, error => {
                this.fetchingCampaignData = false;
            });
            /** GET CLIENT LIST */
        },

        GetCompanyGroup() {
            const userData = this.$store.getters.userData;
            /** GET COMPANY GROUP */
            this.$store.dispatch('GetCompanyGroup', {
                companyID: userData.company_id,
                userModule: 'LeadsPeek',
            }).then(response => {
                //console.log(response.length);
                if (response.result == 'success') {
                    //this.$global.selectsGroupCompany.companyGroupList = [];
                    //this.$global.selectsGroupCompany.companyGroupList = response.params;
                    this.selectsGroupCompany.companyGroupList = response.params;
                    this.selectsGroupCompany.companyGroupList.unshift({ 'id': 0, 'group_name': 'none' });
                }
            }, error => {

            });
            /** GET COMPANY GROUP */
        },

        initialSearchFuse() {
            // Fuse search initialization.
            // this.fuseSearch = new Fuse(this.tableData, {
            //     keys: ['company_name', 'campaign_name', 'leadspeek_api_id', 'last_lead_added'],
            //     threshold: 0.1
            // });
        },

        handleLike(index, row) {
            swal.fire({
                title: `You liked ${row.name}`,
                buttonsStyling: false,
                icon: 'success',
                customClass: {
                    confirmButton: 'btn btn-default btn-fill'
                }
            });
        },
        copytoclipboard(idembeded) {
            $('#' + idembeded).select();
            document.execCommand('copy');
            $('#' + idembeded + 'Link').html(' Copied!');

        },
        handleEmbededCode(index, row) {
            if (row.trysera == 'T') {
                var codeembeded = '<script>';
                codeembeded += 'var ts = {';
                codeembeded += 'c: "14798651632618831906",';
                codeembeded += 'd: "oi.0o0o.io",';
                codeembeded += 's: ' + row.leadspeek_api_id + ',';
                codeembeded += '};';
                codeembeded += 'if("undefined"!=typeof ts){var url="//";ts.hasOwnProperty("d")?url+=ts.d:url+="oi.0o0o.io",url+="/ts.min.js",function(e,t,n,a,r){var o,s,d;e.ts=e.ts||[],o=function(){e.ts=ts},(s=t.createElement(n)).src=a,s.async=1,s.onload=s.onreadystatechange=function(){var e=this.readyState;e&&"loaded"!==e&&"complete"!==e||(o(),s.onload=s.onreadystatechange=null)},(d=t.getElementsByTagName(n)[0]).parentNode.insertBefore(s,d)}(window,document,"script",url)}';
                codeembeded += '<\/script>';
                codeembeded += '<noscript>';
                codeembeded += '<img src="https://oi.0o0o.io/i/14798651632618831906/s/' + row.leadspeek_api_id + '/tsimg.png" width="1" height="1" style="display:none;overflow:hidden">';
                codeembeded += '</noscript>';
                //console.log(codeembeded);
                $('#leadspeekembededcode').val(codeembeded);

                codeembeded = '<img src="https://oi.0o0o.io/c/14798651632618831906" style="width:1px;height:1px;border:0" />';
                $('#leadspeekembededcodesupression').val(codeembeded);
                $('#suppressioncode').show();
            } else {
                var jspx = "px.min.js";
                if (process.env.VUE_APP_DEVMODE == 'true') {
                    jspx = "px-sandbox.min.js";
                }
                var codeembeded = '<script>';
                codeembeded += '(function(doc, tag, id){';
                codeembeded += 'var js = doc.getElementsByTagName(tag)[0];';
                codeembeded += 'if (doc.getElementById(id)) {return;}';
                codeembeded += 'js = doc.createElement(tag); js.id = id;';
                codeembeded += 'js.src = "https://' + window.location.hostname + '/' + jspx + '";';
                codeembeded += 'js.type = "text/javascript";';
                codeembeded += 'doc.head.appendChild(js);';
                codeembeded += 'js.onload = function() {pxfired();};';
                codeembeded += "}(document, 'script', 'px-grabber'));";
                //codeembeded += 'window.addEventListener("load", function () {';
                codeembeded += 'function pxfired() {';
                codeembeded += 'PxGrabber.setOptions({';
                codeembeded += 'Label: "' + row.leadspeek_api_id + '|" + window.location.href,';
                codeembeded += '});';
                codeembeded += 'PxGrabber.render();';
                //codeembeded += '});';
                codeembeded += '};';
                codeembeded += '<\/script>';
                $('#leadspeekembededcode').val(codeembeded);

                $('#suppressioncode').hide();
            }
            this.modals.embededcode = true;
        },
        async handleIntegrationClick(index, row) {
            this.selectedRowData = row
            this.selectedRowData.kartra_is_active = false
            this.currentRowIndex = index
            let data = await this.getUserIntegrationList({ companyID: this.selectedRowData.company_id })
            let conditionSpreadsheet = []

            if(row.spreadsheet_id != ''){
                conditionSpreadsheet.push({
                    name: 'Google Sheet',
                    description: 'Deliver Exceptional Email Experiences with SendGrid',
                    logo: 'fa-brands fa-google-drive',
                    active: '3',
                    slug: 'googlesheet'
                })
            }
            
            this.integrations = conditionSpreadsheet

            if (data.length > 0) {
                data.forEach(item => {
                    if(item.company_integration_details.slug === 'sendgrid'){
                        this.selectedRowData.sendgrid_is_active =  this.selectedRowData.sendgrid_is_active === 1
                    }
                    this.integrations.push(item.company_integration_details)
                });
            }
            this.ghl_tags = [];
            this.ghl_tags_remove = [];
            this.selectedRowData.ghl_is_active = 0;
            this.selectedRowData.kartra_is_active = 0
            this.selectedIntegration = 'googlesheet'
            this.sendGridListOptions =  this.getUserSendgridList({ companyID: this.selectedRowData.company_id })
            this.modals.integrations = true;
        },
        async saveIntegrationConfiguration() {
            if (this.validateIntegrationFields()) {
                let data = {
                    id: this.selectedRowData.id,
                    company_id: this.selectedRowData.company_parent,
                    integration_slug: this.selectedIntegration,
                }
                if (this.selectedIntegration === 'googlesheet') {
                    data.reportSentTo = this.selectedRowData.report_sent_to
                    data.adminNotifyTo = this.selectedRowData.admin_notify_to

                } else if (this.selectedIntegration === 'sendgrid') {
                    data.sendgrid_action = this.selectedRowData.sendgrid_action
                    data.sendgrid_list = this.selectedRowData.sendgrid_list ? this.selectedRowData.sendgrid_list : []
                    data.sendgrid_is_active = this.selectedRowData.sendgrid_is_active ? 1 : 0
                }else if (this.selectedIntegration === 'gohighlevel') {
                    data.ghl_tags = this.ghl_tags
                    data.ghl_is_active =  this.selectedRowData.ghl_is_active ? 1 : 0
                    data.company_parent_id = this.selectedRowData.company_id;
                    data.leadspeek_api_id = this.selectedRowData.leadspeek_api_id;
                }else if (this.selectedIntegration === 'kartra') {
                
                    data.tag = this.selectedKartraTags
                    data.list = this.selectedKartraList
                    // data.campaign_id =  this.selectedRowData.id,
                    data.kartra_is_active =  this.selectedRowData.ghl_is_active ? 1 : 0
                 
                }else if (this.selectedIntegration === 'zapier') {
                    data.zapier_webhook = this.zapierWebhook
                    data.zapier_tags = this.zapierTags
                    data.zapier_is_active = this.zapierEnable ? 1 : 0
                    data.zapier_test_enable = this.zapierTestEnable ? 1 : 0
                    data.leadspeek_api_id = this.selectedRowData.leadspeek_api_id
                    data.campaign_type = 'site_id'
                    data.company_id_zap = this.selectedRowData.company_id
                }

                await this.updateCompanyIntegrationConfiguration({ data })
                this.modals.integrations = false
                this.tableData[this.currentRowIndex] = this.selectedRowData
                this.tableData[this.currentRowIndex].sendgrid_is_active = this.tableData[this.currentRowIndex].sendgrid_is_active ? 1 : 0
            }
        },
        validateIntegrationFields() {
            return true
            if (this.selectedRowData.sendgrid_action &&  this.selectedRowData.sendgrid_action.length < 1) {
                this.$notify({
                    type: 'primary',
                    message: 'Please select at least one action',
                    icon: 'fas fa-bug'
                });
                return false
            }
            if (this.selectedRowData.sendgrid_action && this.selectedRowData.sendgrid_action.includes('add-to-list') && this.selectedRowData.sendgrid_list.length < 1) {
                this.$notify({
                    type: 'primary',
                    message: 'Please select at least one sendgrid list',
                    icon: 'fas fa-bug'
                });
                return false
            }
            return true
        },
        prefillSendgridIntegrationData() {
            this.selectedSendgridAction =this.selectedRowData.sendgrid_action ? this.selectedRowData.sendgrid_action : '' ;
            this.selectedSendgridList = this.selectedRowData.sendgrid_list ? this.selectedRowData.sendgrid_list : ''
       
        },
        async integrationItemClick(item) {

            this.selectedIntegration = item.slug
            if( this.selectedIntegration === 'sendgrid'){
                this.isSendgridInfoFetching = true;
                this.selectedSendgridAction = []
                this.selectedSendgridList = []
                this.sendGridListOptions = await this.getUserSendgridList({ companyID: this.selectedRowData.company_id })
                this.prefillSendgridIntegrationData()
                this.isSendgridInfoFetching = false;
            }else if( this.selectedIntegration === 'gohighlevel'){
                this.isGoHighInfoFetching = true;
                this.ghl_tags = [];
                this.ghl_tags_remove = [];
                this.selectedRowData.ghl_is_active = 0;

                this.$store.dispatch('getGoHighLevelTags', {
                    companyID: this.selectedRowData.company_id,
                }).then(response => {
                    this.goHighLevelTagsOptions = response.param.tags.map((item) => {
                        return { id: item.id + '|' + item.name, name: item.name }
                    });
                    
                    //this.selectedRowData.ghl_is_active =  this.selectedRowData.ghl_is_active === 1

                    this.$store.dispatch('getGhlUserTags', {
                            campaignID: this.selectedRowData.leadspeek_api_id,
                            companyID: this.selectedRowData.company_id,
                        }).then(response => {
                            this.ghl_tags = [];
                            response.param.map((item) => {
                                  this.ghl_tags.push(item.id + '|' + item.name);
                            });
                            this.selectedRowData.ghl_is_active = response.ghlactive === 1;
                            this.ghl_tags_remove = response.tagremove;
                            this.isGoHighInfoFetching = false;
                        },error => {
                            
                        });                       
                },error => {
                    
                });
            }else if( this.selectedIntegration === 'kartra'){
                this.onKartraIntegrationSelect()
            }else if( this.selectedIntegration === 'googlesheet'){     
                this.isGoogleInfoFetching = true;
                this.sendGridListOptions = await this.getUserSendgridList({ companyID: this.selectedRowData.company_id })
                this.isGoogleInfoFetching = false;
            }else if(this.selectedIntegration === 'zapier') {
                this.isZapierFetching = true;
                const campaign_webhook = await this.getCampaignZapierDetails({campaign_id: this.selectedRowData.id})
                const campaign_tags = await this.getCampaignZapierTags({campaign_id: this.selectedRowData.id})
                const agency_webhook = await this.getAgencyZapierDetails({ company_id: this.selectedRowData.company_id})
                const agency_tags = await this.getAgencyZapierTags({ company_id: this.selectedRowData.company_id})
                this.zapierTagsOptions = []
                if(agency_tags){
                    this.zapierTagsOptions = agency_tags.map((item) => {
                            return {name: item.name }
                        });
                }
                if ((campaign_webhook.zap_webhook != '' && campaign_webhook.zap_webhook != null) && campaign_webhook.zap_webhook != agency_webhook[0].api_key) {
                    this.defaultWebhook = false;
                    this.zapierEnable = campaign_webhook.zap_is_active === 1
                    this.zapierTestEnable = 0
                    this.zapierWebhook = campaign_webhook.zap_webhook
                    this.leadspeek_api_id = this.selectedRowData.leadspeek_api_id
                    this.zapierTags = [];
                    if (campaign_tags.zap_tags) {
                        campaign_tags.zap_tags.map((item) => {
                                this.zapierTags.push(item.name);
                        });
                    }
                }else{
                    this.defaultWebhook = true;
                    this.zapierEnable = campaign_webhook.zap_is_active === 1;
                    this.zapierTestEnable = 0
                    this.zapierWebhook = [];
                    if (agency_webhook[0].api_key) {
                        agency_webhook[0].api_key.forEach(item => {
                            this.zapierWebhook.push(item)
                        });
                    }
                    this.leadspeek_api_id = this.selectedRowData.leadspeek_api_id
                    this.zapierTags = [];
                    if (campaign_tags.zap_tags) {
                        campaign_tags.zap_tags.map((item) => {
                                this.zapierTags.push(item.name);
                        });
                    }
                }
                this.isZapierFetching = false;
            }
            // this.selectedRowData.sendgrid_action = null
            // this.selectedRowData.sendgrid_list = null
           
        },
        async onKartraIntegrationSelect(){
                this.isKartraInfoFetching = true
                this.selectedKartraList = []
               
                this.selectedKartraTags = []
                const data = {
                    campaign_id: this.selectedRowData.id,
                    company_id: this.selectedRowData.company_parent,
                    integration_slug: this.selectedIntegration,
                }
                let res = await this.geUsertKartraDetails({ companyID: this.selectedRowData.company_id })
                let resListandTag = await this.getSelectedKartraListAndTags({data})
                this.selectedRowData.ghl_is_active = resListandTag.data.kartra_is_active  === 1 
                this.selectedKartraTags = resListandTag.data.tag
                this.selectedKartraList = resListandTag.data.list
                this.kartraTagsOptions = res.tags ?  res.tags : []
                this.kartraListOptions = res.lists ?  res.lists : []
                this.isKartraInfoFetching = false
                
        },
        handleDelete(index, row) {
            var _status = 'F';
            if (row.active_user == 'F') {
                _status = 'T';
            }
            if (_status == 'T') {
                return false;
            }
            //console.log('Row: ' + index);
            swal.fire({
                title: 'Are you sure you want to stop this campaign?',
                html: 'Restarting a Stopped campaign will charge any setup fees and reoccurring weekly/monthly fees. Restarting a Paused campaign will not charge any setup fees, however a paused campaign will still charge any reoccurring weekly/monthly fees.',
                icon: '',
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-default btn-fill',
                    cancelButton: 'btn btn-danger btn-fill'
                },
                confirmButtonText: 'Ok',
                buttonsStyling: false
            }).then(result => {
                if (result.value) {
                    this.popProcessingTxt = "Please wait, stopping campaign ....";

                    $('#processingArea').addClass('disabled-area');
                    $('#popProcessing').show();

                    /** REMOVE ORGANIZATION */
                    this.$store.dispatch('RemoveLeadsPeekClient', {
                        companyID: this.companyID,
                        leadspeekID: row.id,
                        status: _status,
                        userID: row.user_id,
                    }).then(response => {
                        //console.log(response)
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();

                        if (_status == 'T') {
                            row.active_user = 'T';
                            row.disabled = 'F';
                            $('#userstartstop' + index).removeClass('fas fa-stop gray').addClass('fas fa-stop green');
                            this.GetClientList();
                            this.$notify({
                                type: 'success',
                                message: 'This campaign will activated',
                                icon: 'tim-icons icon-bell-55'
                            });
                        } else {
                            row.active_user = 'F';
                            row.disabled = 'T';
                            $('#userstartstop' + index).removeClass('fas fa-stop green').addClass('fas fa-stop gray');
                            this.GetClientList();
                            this.$notify({
                                type: 'success',
                                message: 'Campaign successfully stopped.',
                                icon: 'tim-icons icon-bell-55'
                            });
                        }
                    }, error => {
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();
                    });

                    /** REMOVE ORGANIZATION */
                }
            });
        },
        deleteRow(row) {
            let indexToDelete = this.tableData.findIndex(
                tableRow => tableRow.id === row.id
            );
            if (indexToDelete >= 0) {
                this.tableData.splice(indexToDelete, 1);
            }
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
        initial_default_price() {
            this.resetAgencyCost();
            this.$store.dispatch('getGeneralSetting', {
                companyID: this.companyID,
                settingname: 'agencydefaultprice',
                idSys: this.$global.idsys
            }).then(response => {
                if (response.data != '') {
                    //console.log(response.data);
                    this.defaultCostCampaign.local.Monthly.LeadspeekPlatformFee = (response.data.local.Monthly.LeadspeekPlatformFee != '') ? response.data.local.Monthly.LeadspeekPlatformFee : '0';
                    this.defaultCostCampaign.local.Monthly.LeadspeekCostperlead = (response.data.local.Monthly.LeadspeekCostperlead != '') ? response.data.local.Monthly.LeadspeekCostperlead : '0';
                    this.defaultCostCampaign.local.Monthly.LeadspeekMinCostMonth = (response.data.local.Monthly.LeadspeekMinCostMonth != '') ? response.data.local.Monthly.LeadspeekMinCostMonth : '0';

                    this.defaultCostCampaign.local.Weekly.LeadspeekPlatformFee = (response.data.local.Weekly.LeadspeekPlatformFee != '') ? response.data.local.Weekly.LeadspeekPlatformFee : '0';
                    this.defaultCostCampaign.local.Weekly.LeadspeekCostperlead = (response.data.local.Weekly.LeadspeekCostperlead != '') ? response.data.local.Weekly.LeadspeekCostperlead : '0';
                    this.defaultCostCampaign.local.Weekly.LeadspeekMinCostMonth = (response.data.local.Weekly.LeadspeekMinCostMonth != '') ? response.data.local.Weekly.LeadspeekMinCostMonth : '0';
                    
                    this.defaultCostCampaign.local.Prepaid.LeadspeekPlatformFee = (response.data.local.Prepaid.LeadspeekPlatformFee != '') ? response.data.local.Prepaid.LeadspeekPlatformFee : '0';
                    this.defaultCostCampaign.local.Prepaid.LeadspeekCostperlead = (response.data.local.Prepaid.LeadspeekCostperlead != '') ? response.data.local.Prepaid.LeadspeekCostperlead : '0';
                    this.defaultCostCampaign.local.Prepaid.LeadspeekMinCostMonth = (response.data.local.Prepaid.LeadspeekMinCostMonth != '') ? response.data.local.Prepaid.LeadspeekMinCostMonth : '0';
                    
                    this.defaultCostCampaign.local.OneTime.LeadspeekPlatformFee = (response.data.local.OneTime.LeadspeekPlatformFee != '') ? response.data.local.OneTime.LeadspeekPlatformFee : '0';
                    this.defaultCostCampaign.local.OneTime.LeadspeekCostperlead = (response.data.local.OneTime.LeadspeekCostperlead != '') ? response.data.local.OneTime.LeadspeekCostperlead : '0';
                    this.defaultCostCampaign.local.OneTime.LeadspeekMinCostMonth = (response.data.local.OneTime.LeadspeekMinCostMonth != '') ? response.data.local.OneTime.LeadspeekMinCostMonth : '0';

                    // this.defaultCostCampaign.local.Prepaid.LeadspeekPlatformFee = (typeof(this.costagency.local.Prepaid !== 'undefined') && response.data.local.Prepaid.LeadspeekPlatformFee != '') ? response.data.local.Prepaid.LeadspeekPlatformFee : '0';
                    // this.defaultCostCampaign.local.Prepaid.LeadspeekCostperlead = (typeof(this.costagency.local.Prepaid !== 'undefined') && response.data.local.Prepaid.LeadspeekCostperlead != '') ? response.data.local.Prepaid.LeadspeekCostperlead : '0';
                    // this.defaultCostCampaign.local.Prepaid.LeadspeekMinCostMonth = (typeof(this.costagency.local.Prepaid !== 'undefined') && response.data.local.Prepaid.LeadspeekMinCostMonth != '') ? response.data.local.Prepaid.LeadspeekMinCostMonth : '0';

                    // this.defaultCostCampaign.local.Prepaid.LeadspeekPlatformFee = (typeof(this.costagency.local.Prepaid !== 'undefined') && response.data.local.Weekly.LeadspeekPlatformFee != '') ? response.data.local.Weekly.LeadspeekPlatformFee : '0';
                    // this.defaultCostCampaign.local.Prepaid.LeadspeekCostperlead = (typeof(this.costagency.local.Prepaid !== 'undefined') && response.data.local.Weekly.LeadspeekCostperlead != '') ? response.data.local.Weekly.LeadspeekCostperlead : '0';
                    // this.defaultCostCampaign.local.Prepaid.LeadspeekMinCostMonth = (typeof(this.costagency.local.Prepaid !== 'undefined') && response.data.local.Weekly.LeadspeekMinCostMonth != '') ? response.data.local.Weekly.LeadspeekMinCostMonth : '0';
                    
                }

            }, error => {

            });
        },
        format_date(valdate,convert = false,toClientTime = false){
            if (valdate) {

                if (convert) {
                    // Set the source and target timezones
                    const sourceTimezone = this.$global.clientTimezone; 
                    const targetTimezone = this.$global.systemTimezone;
                    if (toClientTime) {
                        const sourceTimezone = this.$global.systemTimezone; 
                        const targetTimezone = this.$global.clientTimezone; 
                    }

                    // Parse the input time in the source timezone
                    const sourceMoment = this.$moment.tz(valdate, sourceTimezone);

                    // Convert the time to the target timezone
                    const targetMoment = sourceMoment.clone().tz(targetTimezone);

                    return targetMoment.format('YYYY-MM-DD HH:mm:ss');
                }else{

                    return this.$moment(valdate).format('YYYY-MM-DD HH:mm:ss');
                }
            }
        },
        handleVisibleChange(visible) {            
            this.dropdownVisible = visible;
        },
        applyFilters(event){
            event.stopPropagation();
            this.GetClientList(this.currSortBy, this.currOrderBy)
            this.dropdownVisible = false;
        },
        applyFilter(filter){
            this.filterCampaignStatus = filter.value
            this.GetClientList(this.currSortBy, this.currOrderBy)
            this.dropdownVisible = false;
        },
    },

    mounted() {
      
        
        const userData = this.$store.getters.userData;
        if (userData.user_type == 'client') {
            this.companyID = userData.company_parent;
        } else {
            this.companyID = userData.company_id;
        }

const protectAccessMenu = () => {
    if(userData.user_type == 'client'){
        const sidebar = this.$global.clientsidebar

        if (!sidebar['local']){
            this.$router.push({ name: 'Profile Setup' });
        }
        
    } else if (userData.user_type == 'userdownline'){
        const sidebarAgency = this.$global.agency_side_menu && this.$global.agency_side_menu.find(menu => menu.type == 'local')

        if(sidebarAgency && !sidebarAgency.status){
            this.$router.push({ name: 'Profile Setup' });
        }
    }
}

protectAccessMenu()

this.selectsPaymentTerm.PaymentTerm = this.$global.rootpaymentterm;

this.leadlocatorname = userData.leadlocatorname;
this.leadlocalname = userData.leadlocalname;
if (this.$global.globalviewmode) {
    this.userTypeOri = userData.user_type_ori;
}

this.userType = userData.user_type;
this.checkGoogleConnect();
this.GetCompanyList();
this.GetAdministratorList();
//this.GetClientList();
this.initial_default_price();
this.reset();

CHECK_GROUPCOMPANY = setInterval(() => {
    if ((localStorage.getItem('companyGroupSelected') != null) && this.selectsAdministrator.administratorList.length != 0) {
        clearInterval(CHECK_GROUPCOMPANY);
        this.GetClientList();
    }
}, 1000);
},

    watch: {
        'modals.whitelist': function(newValue) {
            if(!newValue) {
                this.supressionProgress = [];
                clearTimeout(this.supressionTimeout);
                clearInterval(this.supressionInterval);
            }
        },

        prepaidType(newValue) {
            if(this.totalLeads.oneTime < 50) {
                this.totalLeads.oneTime = 50;
                this.err_totalleads = '';
            }
            if(newValue === 'continual') {
                this.profitcalculation();
            }
        },
        /**
         * Searches through the table data by a given query.
         * NOTE: If you have a lot of data, it's recommended to do the search on the Server Side and only display the results here.
         * @param value of the query
         */
        // searchQuery(value) {
        //     let result = this.tableData;
        //     var temp = Array();
        //     if (value !== '') {
        //         result = this.fuseSearch.search(this.searchQuery);
        //         for (let i = 0; i < result.length; i++) {
        //             temp.push(result[i].item);
        //             //console.log(result[i].item);
        //         }
        //         if (result.length != 0) {
        //             this.searchedData = temp;
        //         } else {

        //             if (this.tableData.length > 0) {
        //                 this.tableDataOri = [];
        //                 this.tableDataOri = this.tableData;
        //             }
        //             this.tableData = [];
        //             this.searchedData = "";

        //         }
        //         this.initialSearchFuse();
        //     } else {
        //         if (this.tableData.length == 0) {
        //             this.tableData = this.tableDataOri;
        //         }
        //         this.searchedData = result;
        //         this.initialSearchFuse();
        //     }

        // }
    },

}
</script>
<style>
.popProcessing {
    font-weight: bold;
    font-size: 14px;
    position: absolute;
    top: 50%;
    left: 50%;
    position: fixed;
    color: gray;
    background-color: white;
    height: 60px;
    width: 320px;
    padding-left: 30px;
    padding-top: 18px;
    border: 1px solid green;
}

.qanswer {
    font-weight: bold;
    font-style: italic;
    padding-bottom: 10px;
}



.clickable-rows td {
    cursor: pointer;
}

/* .clickable-rows .el-table,
.el-table__expanded-cell {
    background-color: #1e1e2f;
} */

.clickable-rows tr .el-table__expanded-cell {
    cursor: default;
}

.select-fullwidth {
    width: 100%;
}

.perleads {
    width: 60px;
    display: inline-block !important;
}

.table .form-check label .form-check-sign::before,
.el-table table .form-check label .form-check-sign::before,
.table .form-check label .form-check-sign::after,
.el-table table .form-check label .form-check-sign::after {
    top: 3px;
    left: 0px;
}

.green {
    color: green;
}

.red {
    color: red;
}

.gray {
    color: #6c757d;
}

.orange {
    color: #fb6340;
}

.cursordefault {
    cursor: default;
}

.cursornodrop {
    cursor: no-drop;
}

.el-table__fixed-right::before,
.el-table__fixed::before {
    background-color: transparent;
}

tr.hover-row>td {
    background-color: transparent !important;
}

.el-table__fixed-body-wrapper .el-table__row>td:not(.is-hidden) {
    border-top: transparent !important;
}

.integratios-list-wrapper {
    gap: 8px;
}

.integrations__modal-item-wrapper {
    border-radius: 8px;
    cursor: pointer;
    box-sizing: border-box;
    padding: 16px;
}

.integrations__modal-item {
    width: 96px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
    justify-content: center;
}

/* .integrations__modal-item.--active{
    
} */
.integrations__modal-item-wrapper:hover {
    background-color: #5e72e4 !important;
    color: #f4f5f7 !important;
}

.integrarion-brand-name {
    font-size: 12px;
    line-height: 16px;
    font-weight: 400px;
}

.integrations-modal-footer-wrapper {
    padding: 12px 24px;
    width: 100%;
}

.integrations-modal-footer-wrapper .d-flex {
    gap: 16px;
}

.warning-banner {
    padding: 8px 16px;
    /* background-color: #ecf8ff; */
    background-color: #ffecec;
    border-radius: 4px;
    /* border-left: 5px solid #50bfff; */
    border-left: 5px solid #ff5050;
    margin: 20px 0;
    font-size: 14px;
    color: #5e6d82;
    line-height: 1.5em;
}

.warning-banner p {
    margin: 0;
}

.add-new-integration-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
    border: 1px dotted rgb(155, 155, 155);
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    cursor: pointer;
    box-sizing: border-box;
    padding: 16px;
    transition: all 0.5s ease-in-out;
}

.add-new-integration-wrapper i {
    transition: all 0.5s ease-in-out;
    user-select: none;
}

.add-new-integration-wrapper:hover i {
    transform: rotate(180deg);
}

.add-new-integration-wrapper:hover {
    border-color: rgb(120, 120, 120);
}
.client-payment-modal-form-label{
    color:#222a42 !important;
    font-size:16px;
    font-weight: 600;
    line-height: 24px;
    margin-bottom: 8px;
    display: block;
}
.client-payment-modal-form-helper-text{
    color:#6b7280 !important;
    font-size:12px;
    font-weight: 400;
    line-height: 12px;
    margin-top: 4px;
    display: block;
}
.client-payment-setup-form-wrapper{
    display: flex;
    flex-direction: column;
    gap: 24px;

}

.alert-li{
color:initial !important;
}
.el-tabs__nav {
    width: 100%;
}
.el-tabs__item{
    width: 50%;
}
.el-badge__content{
    height: 20px;
}
.el-tabs--border-card>.el-tabs__header .el-tabs__item.is-active{
    color: #222a42 !important;
    font-weight: 600 !important;
}
.el-tabs--border-card>.el-tabs__header .el-tabs__item:not(.is-disabled):hover{
    color: #222a42 !important;
}
.el-badge__content--success{
    background-color: green !important;
}

.container__input__campaign__management {
    /* padding-inline: 16px; */
}

.input__campaign__management {
    width: 22vw;
}

.container__filter__campaign__management {
    /* padding-inline: 16px; */
    margin-top: -2px;
}

.button_add__client__campaign__management {
    margin-inline: 16px;
}

.dropdown-hidden {
  display: none !important;
}


.input__campaign__management .el-input__inner {
    padding-left: 30px;
    padding-right: 30px;
}

@media (max-width: 991.98px) {
    
    .input__campaign__management {
        width: 100%;
    }
    .container__input__campaign__management {
        width: 100%;
    }
}

@media (max-width: 991px) { 
    .small-width-full{
        width: 100%;
    }
    .container__filter__campaign__management {
        width: 100%;
    }
    .dropdown__filter__campaign__management {
        width: 100%;
    }
    .button__filter__campaign__management {
        width: 100%;
    }
    .button_add__client__campaign__management {
        margin-inline: 0px;
    }
    .input__campaign__management{
        margin-bottom: 4px;
    }
}
.add-webhook-url{
    height: 40px;
    width: 40px;
    background: gray;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    cursor: pointer;
    color: white;
}
.webhook-url-wrapper{
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 4px;
}


</style>