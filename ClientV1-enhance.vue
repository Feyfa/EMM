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
                    <div class="d-flex flex-wrap" style="justify-content: space-between;">
                        <div class="d-flex align-items-center flex-wrap" style="column-gap:8px;row-gap:20px">
                        <div class='campaign-filter-item-wrapper'>
                                <span style="opacity:0.3">Showing</span>
                                <span v-for="(filter, index) in optionCampaignStatus" @click="applyFilter(filter)" :class="['--filter-item',{'--active':filterCampaignStatus === filter.value}]" :key='index'>{{filter.label}}</span>
                        </div>
                        <div class="container__input__campaign__management">
                            <base-input class='mb-0'>
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
                        <div v-else class='mt-3 mt-md-5 d-flex flex-column' style='gap: 16px' >
                            <card   class="mb-0" v-for="(campaign,index) in queriedData" :key="campaign.leadspeek_api_id">
                                <div  class='d-flex justify-content-between align-items-center flex-wrap cursor-pointer' style='gap: 8px'>
                                    <div class='campaign-card-title-info-wrapper' @click.stop>
                                        <!-- <span class="card-icon-styling" :style='{backgroundColor:colorOptions[(index * 7) % 4 + 1].background}'>
                                            <i class="fa-solid fa-bullhorn" :style="{color:colorOptions[(index * 7) % 4 + 1].color}"></i>
                                        </span> -->
                                        <div class='align-items-center d-flex flex-wrap' style='gap:16px'>
                                            <div class='campaign-card-title-info'>
                                                <span class='campaign-card-client-name'>{{campaign.company_name}}</span>
                                                <span class="campain-card-name d-flex justify-content-between"><span class='campaign-name-ellips'>{{campaign.campaign_name}}</span> <span class='arrows'><i class="fa-solid fa-arrow-up" :style="{opacity: currOrderBy == 'ascending' ? 0.3 : 0.6}" @click.stop="sortdynamic('campaign_name','ascending')"></i><i :style="{opacity: currOrderBy == 'descending' ? 0.3 : 0.6}" class="fa-solid fa-arrow-down" @click.stop="sortdynamic('campaign_name','descending')"></i></span></span>
                                                <span v-if="campaign.last_lead_added" class='campaign-card-update-date'>Last updated on {{campaign.last_lead_added}}</span>
                                            </div>
                                            <div v-if="campaign.disabled == 'F' && campaign.active_user == 'T'" class='campaign-card-status-badge' style="background:rgb(0, 81, 97);color:white"><span >Running</span></div>
                                            <div v-if="campaign.disabled == 'T' && campaign.active_user == 'T'" class='campaign-card-status-badge' style="background:#fdae61"><span>Paused</span></div>
                                            <div v-if="campaign.active_user == 'F'" class='campaign-card-status-badge' style="background:rgb(147, 2, 30);color:white"><span >Stopped</span></div>
                                        
                                        </div>

                                    </div>
                                    <div class='d-flex justify-content-between align-items-center flex-wrap campaign-card-right-wrapper'>

                                        <div class='campaign-card-stat-wrapper' style='min-width:253px'>
                                            <div class='campaign-card-stat-block'>
                                                <span class='--stat-block-title'>ID</span>
                                                <span class='--stat-block-value'>{{campaign.leadspeek_api_id}} <span v-if="$global.globalviewmode && userTypeOri != 'sales' && $global.rootcomp">
                                                <!-- <span v-if="campaign.clientcampaignsid != ''" class="pl-2"><a :href="'https://app.simpli.fi/organizations/' +  campaign.clientorganizationid + '/campaigns/' + campaign.clientcampaignsid + ''" target="_blank"><i class="fas fa-chart-line" style="color:green"></i></a></span>
                                                <i v-if="campaignclientcampaignsid == ''" class="fas fa-exclamation-triangle pl-2" style="color:yellow"></i> -->
                                                
                                              </span>
                                              <span v-if="$global.globalviewmode && userTypeOri != 'sales' && $global.rootcomp">
                                                <span v-if="campaign.leadspeek_api_id != ''" class="pl-2"><a :href="'/configuration/report-analytics/?campaignid=' +  campaign.leadspeek_api_id + ''" target="_blank"><i class="fas fa-file-chart-line" style="color:gray"></i></a></span>
                                              </span></span>
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
                                        <div class='campaign-card-actions-block flex-column'>
                                            <div class='campaign-card-actions-block'>
                                                <el-tooltip
                                                        content="Edit This Campaign"
                                                        effect="light"
                                                        :open-delay="300"
                                                        placement="top"
                                                    >
                                                    <span @click='handleExpandChange(campaign, queriedData)' class="card-action-icon-wrapper">
                                                        <i class="fa-solid fa-pen"></i>
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

                                            
                                        </div>
                                        <div @click.stop>
                                            <el-dropdown trigger="click">
                                                <span >
                                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                                </span>
                                                <el-dropdown-menu slot="dropdown" >
                                                    <div @click.stop='handleExpandChange(campaign, queriedData)'>
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
               
               
                        <div class="tab-footer pull-right">
                                    <div class="pt-3">
                                        <p class="card-category">
                                            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
                                        </p>
                                    </div>
                                        <base-pagination
                                        class="pagination-no-border pt-4"
                                        v-model="pagination.currentPage"
                                        :per-page="pagination.perPage"
                                        :total="pagination.total"
                                        @input="changePage"
                                        >
                                        </base-pagination>
                                </div>


                 <!-- </card> -->
             </div>    
        </div>

                            <!-- Modal LeadSpeek Embedded Code -->
                            <modal :show.sync="modals.embededcode" headerClasses="justify-content-center" modalContentClasses="">
                              <h4 slot="header" class="title title-up">Client Embed Code</h4>
                              <p class="text-center">
                                This embed code is to be placed in the Header of your client's site.
                                <!--The short code is a universal container that will not need manually updating as we will handle any needed code updates dynamically.-->
                              </p>
                              <div class="text-center" v-if="false">
                                <textarea rows="1" cols="70"  id="universalembededcode"><script async src='https://tag.exactmatchmarketing.com/'></script></textarea>
                                <div>
                                    <a href="javascript:void(0);" id="universalembededcodeLink" class="far fa-clipboard" @click="copytoclipboard('universalembededcode')"> Copy</a>
                                </div>
                              </div>
                              <p class="text-center">
                                  The full code below for custom placements of the LeadsPeek token.
                              </p>
                              <div class="text-center">
                                  <textarea rows="10" cols="70"  id="leadspeekembededcode">
                                      
                                  </textarea>
                                  <div>
                                    <a href="javascript:void(0);" id="leadspeekembededcodeLink" class="far fa-clipboard" @click="copytoclipboard('leadspeekembededcode')"> Copy</a>
                                </div>
                              </div>
                              <p class="text-center">
                                  The below code is your client's Suppression code. This code is usually placed on a page where a lead can already be identified as part of the customer journey, such as a check out page, or an email signup confirmation page.
                              </p>
                              <div class="text-center">
                                  <textarea rows="2" cols="90"  id="leadspeekembededcodesupression">
                                      
                                  </textarea>
                                  <div>
                                    <a href="javascript:void(0);" id="leadspeekembededcodesupressionLink" class="far fa-clipboard" @click="copytoclipboard('leadspeekembededcodesupression')"> Copy</a>
                                </div>
                              </div>
                              <template slot="footer">
                                <div class="container text-center pb-4">
                                  <base-button  @click.native="modals.embededcode = false">Close Window</base-button>
                                </div>
                              </template>
                            </modal>
                            <!-- Modal LeadSpeek Embedded Code -->

                            <!-- Modal General Information -->
                            <modal :show.sync="modals.helpguide" headerClasses="justify-content-center" modalContentClasses="">
                                <h4 slot="header" class="title title-up" v-html="modals.helpguideTitle"></h4>
                                <p class="text-center" v-html="modals.helpguideTxt">
                                </p>
                            
                                <template slot="footer">
                                <div class="container text-center pb-4">
                                    <base-button  @click.native="modals.helpguide = false">Close</base-button>
                                </div>
                                </template>
                            </modal>
                            <!-- Modal General Information -->

                            <!-- QUESTIONNAIRE RESULT MODAL -->
                            <modal id="modalQuestionnaire" :show.sync="modals.questionnaire" headerClasses="justify-content-center" modalContentClasses="">
                                <h4 slot="header" class="title title-up">Questionnaire result for : <span style="color:#d42e66">{{LeadspeekCompany}}</span></h4>

                                <div class="col-sm-12 col-md-12 col-lg-12">
                                    <p class="text-center"><strong>{{leadlocatorname}} Questionnaire</strong></p>
                                </div>
                                <div style="height:10px">&nbsp;</div>

                                <p>- What is your campaign name?</p>
                                <p class="qanswer pl-2">{{questionnaire.campaign_name}}</p>

                                <p>- Do you want to target location(s) by :</p>
                                <p class="qanswer pl-2"  v-if="questionnaire.nationaltargeting === true">National Tergeting</p>
                                <p class="qanswer pl-2"  v-if="questionnaire.locatorstate != ''">State(s) : {{questionnaire.locatorstate}}</p>
                                <p class="qanswer pl-2"  v-if="questionnaire.locatorcity != ''" style="display:none">City(s) : {{questionnaire.locatorcity}}</p>
                                <p class="qanswer pl-2"  v-if="questionnaire.locatorzip != ''">ZipCode(s) : {{questionnaire.locatorzip}}</p>
                                
                                <p>- When campaign will start and end?</p>
                                <p class="qanswer pl-2">Start : {{questionnaire.startcampaign}}</p>
                                <p class="qanswer pl-2">End : {{questionnaire.endcampaign}}</p>

                                <p>- Do you want to cap the amount of leads you receive per day?</p>
                                <p class="qanswer pl-2" v-html="questionnaire.asec5_6">&nbsp;</p>
                               
                                <p>- List several keywords or phrases that your preferred customer would be searching for online.</p>
                                <p class="qanswer pl-2" v-html="questionnaire.locatorkeyword">&nbsp;</p>

                                
                                <div style="height:10px">&nbsp;</div>
                                <div v-if="questionnaire.asec6_3 != '' || questionnaire.asec6_4 != '' || questionnaire.asec6_6 != ''">
                                    <div class="col-sm-12 col-md-12 col-lg-12">
                                        <p class="text-center"><strong>Information for all campaign options</strong></p>
                                        <p class="text-center"><small style="font-size:12px">All questions must be answered below.</small></p>
                                    </div>
                                    <div style="height:40px">&nbsp;</div>

                                    <p>- Besides yourself, please provide any additional email addresses (separated by a comma) of those you wish to also have access to the leads information sheet.</p>
                                    <p class="qanswer pl-2" v-html="questionnaire.asec6_3">&nbsp;</p>
                                    <p>- You can link the Google Sheet(s) to your CRM. If you want this to happen, do you know how to do it or will you need our help to set it up?</p>
                                    <p class="qanswer pl-2" v-html="questionnaire.asec6_4">&nbsp;</p>
                                
                                    <p>- Note: The leads captured will have been filled out by the customer via an opt-in form. We have no control over what they entered or if their personal information has changed from the time they completed the form to now. (For example, they may have moved, gotten married and changed their last name, etc.) By law, you are required to be CAN-SPAM compliant if you reach out via e-mail.</p>
                                    <p class="qanswer pl-2" v-html="questionnaire.asec6_6">&nbsp;</p>
                                </div>

                                <template slot="footer">
                                    <div class="container text-center pb-4">
                                    <base-button  @click.native="modals.questionnaire = false">Close</base-button>
                                    </div>
                                </template>
                            </modal>
                            <!-- QUESTIONNAIRE RESULT MODAL -->

                            <!-- Modal Setting Markup -->
                            <modal id="modalSetPrice" :show.sync="modals.pricesetup" headerClasses="justify-content-center" modalContentClasses="">
                              <h4 slot="header" class="title title-up">Campaign Financials For: <span style="color:#000000">{{LeadspeekCompany}}</span></h4>
                              <div style="display:none">
                                <!--<iframe width="970" height="415" src="https://www.youtube.com/embed/SCSDyqRP7cY" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>-->
                              </div>
                              <div class="row" v-if="userType != 'client'">
                                  <div class="col-sm-12 col-md-12 col-lg-12">
                                      <div class="d-inline-block pr-4" v-if="false">
                                            <label>Select Modules:</label>
                                            <el-select
                                                class="select-primary"
                                                size="large"
                                                placeholder="Select Modules"
                                                v-model="selectsAppModule.AppModuleSelect"
                                                >
                                                <el-option
                                                    v-for="option in selectsAppModule.AppModule"
                                                    class="select-primary"
                                                    :value="option.value"
                                                    :label="option.label"
                                                    :key="option.label"
                                                >
                                                </el-option>
                                            </el-select>
                                      </div>
                                      <div class="d-flex flex-column" style="margin-bottom:16px;">
                                        <span class="client-payment-modal-form-label" style="color:#222a42">Billing Frequency</span>
                                            <el-select
                                                class="select-primary"
                                                size="large"
                                                placeholder="Select Modules"
                                                v-model="selectsPaymentTerm.PaymentTermSelect"
                                                @change="paymentTermChange()"
                                                :disabled="LeadspeekInputReadOnly || OnlyPrepaidDisabled" 
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
                                  </div>
                              </div>

                            <div v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && userType != 'client'" class="mb-4 p-0">
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
                                                    <p style="font-size: 14px; color: #6b7280;"> <span style="font-weight: 600;">Continual top up</span> will automatically top up your pool of pre-purchased leads when it gets low. This will keep your campaign running until stopped.</p>
                                                </div>
                                                <div class="col-6">
                                                    <el-switch
                                                        v-model="contiDurationSelection"
                                                        active-text="Monthly"
                                                        inactive-text="Weekly"
                                                        @change="updateWeeklyMonthlyToggle"
                                                        >
                                                    </el-switch>
                                                    <p v-if="prepaidType == 'continual'">Number of leads to buy: <b>{{ totalLeads.continualTopUp }}</b> lead(s)</p>     
                                                </div>
                                                <div class="col-6" v-if="prepaidType == 'continual'">
                                                    <span class="client-payment-modal-form-label" style="color:#222a42">Automatic top up will occur when your pre-purchased lead amount drops below {{ LeadspeekLimitLead }}</span>
                                                </div>
                                                <div class="col-12 mt-2" v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && prepaidType == 'continual' && userType != 'client'">
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
                                            <div class='w-100' v-if="userType != 'client'">
                                                <div class="client-payment-modal-form-label">
                                                    Setup Fee
                                                </div>
                                                <div>
                                                    <base-input
                                                        label=""
                                                        type="text"
                                                        placeholder="0"
                                                        addon-left-icon="fas fa-dollar-sign"
                                                        class="campaign-cost-input"  
                                                        v-model="LeadspeekPlatformFee"       
                                                        :readonly="LeadspeekInputReadOnly"  
                                                        @keyup="profitcalculation();"
                                                        @keydown="restrictInput"    
                                                    >
                                                    </base-input>
                                                </div>
                                                <div class="client-payment-modal-form-helper-text"><span>Your Setup Fee cost is $<span>{{m_LeadspeekPlatformFee}}</span></span></div>
                                            </div>

                                            <div class='w-100'  v-if="userType != 'client'">
                                                    <div class="client-payment-modal-form-label">
                                                        Campaign Fee <span v-html="txtLeadService">per month</span>
                                                    </div>
                                                <div>
                                                    <base-input
                                                        label=""
                                                        type="text"
                                                        placeholder="0"
                                                        addon-left-icon="fas fa-dollar-sign"
                                                        class="campaign-cost-input"  
                                                        v-model="LeadspeekMinCostMonth"    
                                                        :readonly="LeadspeekInputReadOnly"     
                                                        @keyup="profitcalculation();"      
                                                        @keydown="restrictInput"
                                                    >
                                                    </base-input>
                                                </div>
                                                <div class="client-payment-modal-form-helper-text"><span>Your Platform Fee cost is $<span>{{m_LeadspeekMinCostMonth}}</span></span></div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap flex-md-nowrap" style="gap:16px;">
                                        <div class='w-100' v-if="selectsPaymentTerm.PaymentTermSelect == 'One Time' && userType != 'client'">
                                            <div class="client-payment-modal-form-label">
                                            How many leads are included <span v-html="txtLeadIncluded">in that weekly charge</span>?
                                            </div>
                                            <div class="d-inline" style="float:left;">
                                                <base-input
                                                    label=""
                                                    type="text"
                                                    placeholder="0"
                                                    class="campaign-cost-input"
                                                    v-model="LeadspeekMaxLead"    
                                                    :readonly="LeadspeekInputReadOnly"
                                                    @keyup="profitcalculation();"   
                                                    @keydown="restrictInput"     
                                                >
                                                </base-input>
                                            </div>
                                            <div class="client-payment-modal-form-helper-text"><span>Your cost per lead is $<span>{{m_LeadspeekCostperlead}}</span></span></div>
                                        </div>

                                        

                                        <div class='w-100' v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time' && userType != 'client'">
                                            <div class="client-payment-modal-form-label d-flex align-items-center">
                                                Cost per lead<span v-html="txtLeadOver" v-if="false">from the
                                                monthly charge</span>?
                                                <div v-if="clientTypeLead.type == 'clientcaplead' && errMaxCostPerLead" style="color:#942434; font-size: .75rem;" class="ml-2">
                                                    <span>* Client price capped at ${{ clientTypeLead.value }} per lead</span>
                                                </div>
                                                <div v-if="clientTypeLead.type != 'clientcaplead' && errMinCostAgencyPerLead" style="color:#942434; font-size: .75rem;" class="ml-2">
                                                    <span>*Minimum cost per lead is ${{ minimumCostPerLead }},but it can also be $0</span>
                                                </div>
                                            </div>
                                            <div>
                                                <base-input
                                                    label=""
                                                    type="text"
                                                    placeholder="0"
                                                    addon-left-icon="fas fa-dollar-sign"
                                                    class="campaign-cost-input"  
                                                    v-model="LeadspeekCostperlead"        
                                                    :readonly="LeadspeekInputReadOnly"     
                                                    @keyup="profitcalculation();"     
                                                    @keydown="restrictInput"
                                                    @blur="validateMinimumCostPerLead"
                                                >
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
                                                <base-input
                                                    label=""
                                                    type="text"
                                                    placeholder="Input should be numeric and greater than zero"
                                                    style="text-align: left;"
                                                    :style="{ border: errMinLeadDay ? '1px solid #942434' : '' }"
                                                    class="campaign-cost-input"
                                                    v-model="LeadspeekLimitLead"    
                                                    @input="onInput"
                                                    @keyup="profitcalculation();"     
                                                    @keydown="event => restrictInput(event,'integer')"
                                                    @blur="validateMinLead"      
                                                >
                                                </base-input>
                                            </div>
                                            <span v-if="!errMinLeadDay" class="client-payment-modal-form-helper-text">*Input should be numeric, greater than zero and can not be empty</span>
                                            <span v-if="errMinLeadDay" style="color:#942434; font-size:12px;font-weight: 400;line-height: 12px;margin-top: 4px;display: block;">*Leads Per Day Minimum {{ clientMinLeadDayEnhance }}</span>
                                            <!-- <div class="d-inline" style="float:left;padding-left:10px">
                                                 <label class="pt-2">Leads per&nbsp;day <small>*Zero means unlimited</small></label>
                                                <el-select
                                                    v-if="false"
                                                    class="select-primary"
                                                    size="large"
                                                    placeholder="Select Modules"
                                                    v-model="selectsAppModule.LeadsLimitSelect"
                                                    style="padding-left:10px"
                                                    @change="checkLeadsType"
                                                    >
                                                    <el-option
                                                        v-for="option in selectsAppModule.LeadsLimit"
                                                        class="select-primary"
                                                        :value="option.value"
                                                        :label="option.label"
                                                        :key="option.label"
                                                    >
                                                    </el-option>
                                                </el-select>
                                            </div> -->

                                            <div v-if="LeadspeekMaxDateVisible" class="d-inline" style="float:left;padding-left:10px">
                                                <label class="client-payment-modal-form-label">Start From : </label>
                                                <el-date-picker
                                                    type="date"
                                                    placeholder="Date Start"
                                                    v-model="LeadspeekMaxDateStart"
                                                    class="frmSetCost leadlimitdate"
                                                    id="leaddatestart"
                                                >
                                                </el-date-picker>
                                            </div>
                                            
                                        </div>
                                    </div>
                                        <div class="col-sm-12 col-md-12 col-lg-12" v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time' && false">
                                            <div class="d-inline pr-3" style="float:left;line-height:40px">
                                            What date should this campaign end?
                                            </div>
                                            <div class="d-inline" style="float:left;">
                                                <el-date-picker
                                                    type="date"
                                                    placeholder="Date End"
                                                    v-model="LeadspeekDateEnd"
                                                    class="frmSetCost leadlimitdate"
                                                    id="leaddateend"
                                                    :readonly="LeadspeekInputReadOnly"  
                                                >
                                                </el-date-picker>
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

                                                            <div v-else> 
                                                                <span>
                                                                    4. Estimate cost per {{ t_freqshow }}:
                                                                </span>
                                                            <br/>({{ LeadspeekLimitLead }} per day x 7 days) x {{ t_freq }} weeks :
                                                            $<span><strong>{{formatPrice( ((LeadspeekCostperlead * LeadspeekLimitLead)*7 ) *t_freq )}}</strong></span>
                                                            </div>
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
                                                <div  v-if="selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && LeadspeekLimitLead > 0">
                                                    <small v-if="prepaidType == 'continual'">Your estimate cost per {{ contiDurationSelection ? 'Month' : 'Week' }} is $<strong>{{contiDurationSelection ?  formatPrice( (((LeadspeekCostperlead * LeadspeekLimitLead)* 7) * t_freq) + parseFloat(LeadspeekMinCostMonth)) : formatPrice( (((LeadspeekCostperlead * LeadspeekLimitLead)* 7)) + parseFloat(LeadspeekMinCostMonth))}}</strong></small>
                                                    <small v-else>Your cost is $<strong>{{ formatPrice(LeadspeekCostperlead * totalLeads.oneTime) }}</strong></small>
                                                </div>  
                                                <div style="margin-top: auto" v-else>
                                                    <small v-if="LeadspeekLimitLead > 0">Your estimate cost per {{ t_freqshow }} is $<strong>{{ formatPrice( (((LeadspeekCostperlead * LeadspeekLimitLead)* 7) * t_freq) + parseFloat(LeadspeekMinCostMonth)) }}</strong></small>
                                                    <!-- <small v-if="false">Your estimate cost {{ t_freqshow }} is <strong>unlimited</strong></small> -->
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

                              <div class="row">
                              </div>
                              <template slot="footer">
                                <div class="container text-center pb-4" >
                                  <base-button v-if="LeadspeekLimitLead > 0" @click.native="setModuleCost()">Ok, Set It Up!</base-button>
                                </div>
                              </template>
                            </modal>
                           <!-- Modal Setting Markup -->

                           <!-- WhiteList DB upload -->
                            <modal :show.sync="modals.whitelist" id="locatorWhitelist" headerClasses="justify-content-center" modalContentClasses="">
                              <h4 slot="header" class="title title-up">Exclusion List for this campaign</h4>
                              <div>
                                <!--UPLOAD-->
                                    <form enctype="multipart/form-data">
                                        <!--<h5>Drag & Drop your suppression List (file type should be .csv). Download <a href="#">example file</a></h5>-->
                                        <div class="dropbox">
                                        <input type="file" :name="uploadFieldName" :disabled="isSaving" @change="filesChange($event.target.name, $event.target.files); fileCount = $event.target.files.length"
                                            accept=".csv" class="input-file">
                                            <p v-if="isInitial">
                                            Drag your file here to upload<br/>or click to browse<br/>
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
                                  <base-button  @click.native="modals.whitelist = false">Cancel</base-button>
                                </div>
                              </template>
                            </modal>
                           <!-- WhiteList DB upload -->

     <div id="popProcessing" class="popProcessing" style="display:none" v-html="popProcessingTxt">Please wait, updating campaign ....</div> 
     <!-- Integrations modal -->
     <modal :show.sync="modals.integrations" id="addIntegrations" modalContentClasses=""
            footerClasses="border-top">
            <h3 slot="header" class="title title-up">Add integrations to campaign #{{selectedRowData.leadspeek_api_id}}</h3>
            <div>
                <div class="integratios-list-wrapper d-flex align-items-center gap-4 flex-wrap">
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
                            <base-input label="Client Emails Approved to View the Google Sheet:">
                                <textarea class="w-100" v-model="selectedRowData.report_sent_to" @keydown="handleKeydown" @paste="handlePaste"
                                    placeholder="Enter email, separate by new line" rows="4">
                                </textarea>
                                <span>Seperate the emails by space or comma</span>
                            </base-input>
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
                            <el-checkbox v-model="zapierEnable">Enable Zapier/Webhooks</el-checkbox>
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
                                <p v-if="defaultWebhook" class="mt-2">you're using <b>default</b> client webhook, replace URL if you want to use custom webhook for this campaign  </p>
                                <p v-if="!defaultWebhook" class="mt-2">you're using <b>custom</b> webhook, just delete this URL and save. if you want to use default webhook for this campaign </p>
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
                                <p>* check and save to test</p>
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
            <!-- create campaign modal end -->
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
                        <div class="col-sm-12 col-md-12 col-lg-12" v-if="GoogleConnectTrue">
                            <base-input label="Client Emails Approved to View the Google Sheet:" >
                                <textarea class="form-control input-border" v-model="ClientReportSentTo"
                                    placeholder="Put client emails here, separate by new line" rows="50" @keydown="handleGsheetKeydowncreate" @paste="handleGSheetPasteCreate">
                                </textarea>
                            </base-input>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 has-label form-group" v-if="userType != 'client'">
                            <label>Admins Approved for Google Sheet Administration</label>
                            <el-select multiple class="select-info select-fullwidth" size="large"
                                v-model="selectsAdministrator.administratorSelected"
                                placeholder="You can select multiple Admin">
                                <el-option v-for="option in selectsAdministrator.administratorList" class="select-info"
                                    :value="option.id" :label="option.name" :key="option.id">
                                </el-option>
                            </el-select>
                        </div>
                      <!--ADD QUESTIONNAIRE LOCATOR QUESTION-->
                       <ValidationObserver ref="form5" class="col-sm-12 col-md-12 col-lg-12">
                        <div v-if="false" class="form-group">
                            <label>Campaign End Date:</label>
                            
                          
                                <base-input class="mb-0">
                                    <el-date-picker
                                        type="date"
                                        placeholder="Input end date"
                                        v-model="EndDateCampaign"
                                        :picker-options="pickerOptions"
                                    >
                                    </el-date-picker>
                                </base-input> 
                                <small><em>* Campaign will not run automatically until you click play button</em></small>  
                                <div class="col-sm-12 col-md-12 col-lg-12" id="err_startendcamp" style="display:none">
                                    <label style="color:#942434" v-html="ErrStartEndCampaign">* Please fill the date when campaign start and end</label>
                                </div>    
                            
                        </div>
                        <div class="form-group">
                            <label>Do you want to target locations by :</label>
                            <div class="row">
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <base-checkbox name="nationaltargeting" v-model="newcampaign.asec5_4_0_0"  @change="nationaltargetselected('asec5_4_0_0', $event)"  inline>National Targeting</base-checkbox>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <base-checkbox name="state" v-model="newcampaign.asec5_4_0_1" @change="nationaltargetselected('asec5_4_0_1', $event)" inline>State</base-checkbox>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <base-checkbox name="zipcode" v-model="newcampaign.asec5_4_0_3" @change="nationaltargetselected('asec5_4_0_3', $event)" inline>Zip Code</base-checkbox>
                                </div>
                            </div>
                            <div class="border-box" id="boxState" v-if="newcampaign.asec5_4_0_1" style="margin-top: 8px">
                              <label >Select By States</label>
                              <div class="row">
                                  <div class="col-sm-12 col-md-12 col-lg-12" id="state-selector-wrapper">
                                          <base-input style='border:none !important;'>
                                              <el-select
                                                  id="statelist"
                                                  v-model="selects.state"
                                                  class="select-primary "
                                                  name="statelist"
                                                  inline
                                                  size="large"
                                                  placeholder="Select State(s)"
                                                  filterable
                                                  default-first-option
                                                  multiple
                                                  @change="onStateChange"
                                                  >
                                                  <el-option
                                                  v-for="option in selects.statelist"
                                                  class="select-primary"
                                                  :label="option.state_name"
                                                  :value="option.state_code"
                                                  :key="option.state_code"
                                                  ></el-option>
                                              </el-select>
                                          </base-input>
                                  </div>
                              </div>
                            </div>
                            <div class="border-box" id="boxState" v-if="newcampaign.asec5_4_0_1">
                              <label >Select By Cities</label>
                              <div class="row">
                                  <div class="col-sm-12 col-md-12 col-lg-12" id="state-selector-wrapper">
                                    <el-select
                                        v-model="selectedCity"
                                        :class="[selects.state.length > 1 || selects.state.length == 0 ? 'disabled' : 'select-primary']"
                                        inline
                                        size="large"
                                        placeholder="Select By Cities"
                                        filterable
                                        default-first-option
                                        multiple
                                        remote
                                        @visible-change="onDropdownVisible"
                                        @change="onCityChange"
                                        no-match-text="Loading...."
                                        ref="select"
                                        :remote-method="onSearchCity"
                                        :disabled="selects.state.length > 1 || selects.state.length == 0"
                                        :style="[selects.state.length > 1 || selects.state.length == 0 ? {background: '#f5f7fa', color: '#c0c4cc !important', cursor: 'not-allowed !important'} : {}]"
                                        >
                                        <el-option
                                        v-for="(option, index) in optionsCity"
                                        class="select-primary"
                                        :label="option.city"
                                        :value="option.city"
                                        :key="option.city"
                                        ></el-option>
                                        <el-option v-if="isLoadingOptionsCity" value="loading" label="Loading...." disabled>
                                        </el-option>
                                    </el-select>
                                    <small>* You can select cities only if exactly one state is selected.</small>
                                  </div>
                              </div>
                            </div>
                            <div class="border-box mt-4" v-if="newcampaign.asec5_4_0_3">
                                <label class="label-border-box">List the zip codes that you want the campaign to target, <strong>One postal code per line</strong>:</label>
                                <div class="row">
                                    <div class="col-sm-12 col-md-12 col-lg-12 pt-2">
                                            <div class="form-group has-label">
                                                    <textarea
                                                        id="zipcodelist"
                                                        class="form-control input-border"
                                                        v-model="newcampaign.asec5_3"
                                                        placeholder="" 
                                                        rows="5"
                                                        style="min-height:11rem"
                                                        @keydown="handleZipKeydowncreate" @paste="handleZipPasteCreate"
                                                    >
                                                    </textarea>
                                                    <small style='color:red !important;' id="err_locator_zip">* A minimum of 1 valid ZIP code and a maximum of 50 valid ZIP codes must be provided.</small>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <div class="form-group">
                                <ValidationProvider
                                        name="How many leads"
                                        rules="required|numeric|min_value:1"
                                        v-slot="{ passed, failed, errors }"
                                    >
                                    <base-input
                                        v-model="newcampaign.asec5_6"
                                        label="Do you want to cap the amount of leads you receive per day? (Input should be numeric and greater than zero) *"
                                        placeholder="Input should be numeric and greater than zero"
                                        :error="errors[0]"
                                        :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                    </base-input>
                                    </ValidationProvider>
                            </div>
  
                    
                        <!-- <div class="col-sm-12 col-md-12 col-lg-12 pt-4"> -->
                            <div class="pt-2">
                                <base-checkbox name="enabledphonenumber" v-model="checkboxes.phoneenabled" inline>Enable
                                    Phone Number</base-checkbox>
                            </div>
                            <div class="">
                                <base-checkbox name="enabledhomeaddress" v-model="checkboxes.homeaddressenabled"
                                    inline>Enable Home Address</base-checkbox>
                            </div>
                            <div class="">
                                <base-checkbox name="requireemailaddress" v-model="checkboxes.requireemailaddress"
                                    inline>Require Email Address</base-checkbox>
                            </div>
                         
                        <!-- </div> -->

                        <div class="pt-2">
                            <label>Lead Re-identification Time Limit:</label>
                            <div class="leads-re-identification-selector-wrapper">
                                <div v-for='item in ReidentificationTimeOptions' :key='item.value' :class="['leads-re-identification-selector',{'--active': Reidentificationtime == item.value}]" @click='Reidentificationtime = item.value'>
                                    {{item.label}}
                                </div>
                            </div>
                            <base-checkbox class="pt-2" name="ApplyReidentificationToAll" v-model="checkboxes.ApplyReidentificationToAll" inline>Do not identify leads already identified on other campaigns.</base-checkbox>
                            <br /><small>* Your lead could be re-identified after the time limit you select.</small>
                        </div>
                    </ValidationObserver>
                    <el-tooltip content="<span>In the boxes below, specify the keywords that your customer<br> is searching for and reading about (contextual keywords).<br> Keywords must be at least three letters long and no more than three words long.<br> For best results, specify as many keywords as possible.<br> You may enter them one at a time, or paste them in bulk separated by a comma.</span>"  effect="light"  :open-delay="300"  placement="top">
                            <template #content><span>In the boxes below, specify the keywords that your customer<br> is searching for and reading about (contextual keywords).<br> Keywords must be at least three letters long and no more than three words long.<br> For best results, specify as many keywords as possible.<br> You may enter them one at a time, or paste them in bulk separated by a comma.</span></template>
                        <div class="col-12 mt-4 text-center"><span style="font-weight:700;cursor: pointer;">Keyword Control Panel</span>
                          <i class="fa fa-question-circle" style="cursor: pointer; margin-left: 12px;"></i>
                        </div></el-tooltip>
                        <div class="col-12 my-4" style="padding: 0px 15px;box-sizing:border-box;" v-if="newcampaign.asec5_9_1">
                            <div class="row" style="width:100%;margin: auto;">
                                
                                <div class="col-sm-6 col-md-6 col-lg-6 border" style="padding:15px">
                                    <ValidationProvider
                                            name="Keywords"
                                            rules="required"
                                            v-slot="{ passed, failed, errors }"
                                        >
                                        <label class="pb-2">Enter Search keywords one at a time and then press enter.*</label>
                                        <base-input>
                                            <tags-input 
                                            v-model="tags.keywordlist"
                                            @change="updatekeywordbulk()"
                                            placeholder="Enter the keyword/phrases then press enter"
                                            :error="errors[0]"
                                            :class="[{ 'has-success': passed }, { 'has-danger': failed }]"
                                            ></tags-input>
                                        
                                        </base-input>                                              
                                        </ValidationProvider>
                                </div>
                                <div class="col-sm-6 col-md-6 col-lg-6 border" style="padding:15px; display: flex; flex-direction: column;">
                                    <label style="flex-shrink: 0;">Use this box to paste Search keywords separated by a comma.</label>
                                    <textarea
                                        class="form-control"
                                        v-model="tags.keywordlistbulk"
                                        @keyup="updatekeyword($event,$event.target.value)"
                                        @keydown="handleKeydownComma($event,'keywordlistbulk')"
                                        @blur="sanitizedkeyword('keyword')"
                                        placeholder="Seperate each term with a comma"
                                        style="flex-grow: 1; resize: none;max-height:none">
                                    </textarea>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-12" style="padding-block: 8px; padding-left: 0px; padding-right: 0px;">
                                <p v-if="isErrorStopWords" style="color: red; font-size: 12px;">Some words in your input were not allowed and have been removed.</p>
                                <p style="font-size: 12px;">
                                    <i class="el-icon-info"></i>
                                    The following keywords are restricted and will be automatically removed if entered: "<span style="font-weight: 600;">{{ stopWords.join(', ') }}</span>".<br>
                                    The total length of all keywords must not exceed 500 characters.<br>
                                    Keyword should not have more than 3 words.
                                </p>
                            </div>
                            </div>
                        
                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2" id="err_asec5_10" style="display:none">
                            <label style="color:#942434 !important;font-weight:bold">* Target Location and Keywords Field is required</label>
                        </div>
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
                        <div class="col-sm-12 col-md-12 col-lg-12" v-if="GoogleConnectTrue && spreadsheet_id != ''">
                            <base-input label="Client Emails Approved to View the Google Sheet:" >
                                <textarea class="form-control input-border" v-model="selectedRowData.report_sent_to"
                                    placeholder="Put client emails here, separate by new line" rows="50" @keydown="handleGsheetKeydowncreate" @paste="handleGSheetPasteCreate">
                                </textarea>
                            </base-input>
                        </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 has-label form-group" v-if="userType != 'client'">
                            <label>Admins Approved for Google Sheet Administration</label>
                            <el-select multiple class="select-info select-fullwidth" size="large"
                                v-model="selectedRowData.admin_notify_to"
                                placeholder="You can select multiple Admin">
                                <el-option v-for="option in selectsAdministrator.administratorList" class="select-info"
                                    :value="option.id" :label="option.name" :key="option.id">
                                </el-option>
                            </el-select>
                        </div>
                      <!--ADD QUESTIONNAIRE LOCATOR QUESTION-->
                       <ValidationObserver ref="form5" class="col-sm-12 col-md-12 col-lg-12">
                        <div class="form-group" v-if="false">
                            <label>Campaign End Date:</label>
                            
                          
                                <base-input class="mb-0">
                                    <el-date-picker
                                        type="date"
                                        placeholder="Input end date"
                                        v-model="selectedRowData.ori_campaign_enddate"
                                        :picker-options="pickerOptions"
                                    >
                                    </el-date-picker>
                                </base-input> 
                                <small><em>* Campaign will not run automatically until you click play button</em></small>  
                                <div class="col-sm-12 col-md-12 col-lg-12"  :id="'err_startendcamp' + selectedRowData.id"  style="display:none">
                                    <label style="color:#942434" :id="'ErrStartEndCampaign' + selectedRowData.id" ></label>
                                </div>    
                            
                        </div>
                        <div class="form-group">
                            <label>Do you want to target locations by :</label>
                            <div class="row">
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <base-checkbox name="nationaltargeting" v-model="selectedRowData.target_nt"  @change="nationaltargetselectedEdit('target_nt',selectedRowData, $event)"  inline>National Targeting</base-checkbox>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <base-checkbox name="state" v-model="selectedRowData.target_state" @change="nationaltargetselectedEdit('target_state',selectedRowData, $event)" inline>State</base-checkbox>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <base-checkbox name="zipcode" v-model="selectedRowData.target_zip" @change="nationaltargetselectedEdit('target_zip',selectedRowData, $event)" inline>Zip Code</base-checkbox>
                                </div>
                            </div>
                            <div class="border-box" id="boxState" v-if="selectedRowData.target_state" style="margin-top: 8px">
                              <label >Select By States</label>
                              <div class="row">
                                  <div class="col-sm-12 col-md-12 col-lg-12" id="state-selector-wrapper">
                                          <base-input style='border:none !important;'>
                                              <el-select
                                                  id="statelist"
                                                  v-model="selectedRowData.leadspeek_locator_state"
                                                  class="select-primary "
                                                  name="statelist"
                                                  inline
                                                  size="large"
                                                  placeholder="Select State(s)"
                                                  filterable
                                                  default-first-option
                                                  multiple
                                                  @change="onStateChange($event, selectedRowData)"
                                                  >
                                                  <el-option
                                                  v-for="option in selects.statelist"
                                                  class="select-primary"
                                                  :label="option.state_name"
                                                  :value="option.state_code"
                                                  :key="option.state_code"
                                                  ></el-option>
                                              </el-select>
                                          </base-input>
                                  </div>
                              </div>
                            </div>
                            <div class="border-box" id="boxState" v-if="selectedRowData.target_state">
                              <label >Select By Cities</label>
                              <div class="row">
                                  <div class="col-sm-12 col-md-12 col-lg-12" id="state-selector-wrapper">
                                    <el-select
                                        v-model="selectedRowData.leadspeek_locator_city"
                                        :class="[selectedRowData.leadspeek_locator_state.length > 1 || selectedRowData.leadspeek_locator_state.length == 0 ? 'disabled' : 'select-primary']"
                                        inline
                                        size="large"
                                        placeholder="Select By Cities"
                                        filterable
                                        default-first-option
                                        multiple
                                        remote
                                        @visible-change="onDropdownVisible"
                                        @change="onCityChange"
                                        no-match-text="Loading...."
                                        ref="select"
                                        :remote-method="onSearchCity"
                                        :disabled="selectedRowData.leadspeek_locator_state.length > 1 || selectedRowData.leadspeek_locator_state.length == 0"
                                        :style="[selectedRowData.leadspeek_locator_state.length > 1 || selectedRowData.leadspeek_locator_state.length == 0 ? {background: '#f5f7fa', color: '#c0c4cc !important', cursor: 'not-allowed !important'} : {}]"
                                        >
                                        <el-option
                                        v-for="(option, index) in optionsCity"
                                        class="select-primary"
                                        :label="option.city"
                                        :value="option.city"
                                        :key="option.city"
                                        ></el-option>
                                        <el-option v-if="isLoadingOptionsCity" value="loading" label="Loading...." disabled>
                                        </el-option>
                                    </el-select>
                                    <small>* You can select cities only if exactly one state is selected.</small>
                                  </div>
                              </div>
                            </div>
                            <div class="border-box mt-4" id="boxCityState" v-if="selectedRowData.target_zip">
                                <label class="label-border-box">List the zip codes that you want the campaign to target, <strong>One postal code per line</strong>:</label>
                                <div class="row">
                                    <div class="col-sm-12 col-md-12 col-lg-12 pt-2">
                                            <div class="form-group has-label">
                                                    <textarea
                                                        id="zipcodelist"
                                                        class="form-control input-border"
                                                        v-model="selectedRowData.leadspeek_locator_zip"
                                                        placeholder="" 
                                                        rows="5"
                                                        style="min-height:11rem"
                                                        @keydown="handleZipKeydowncreate" @paste="onPasteEditZipCodes($event, selectedRowData.leadspeek_locator_zip)"
                                                    >
                                                    </textarea>
                                                    <small style='color:red !important;' :id="'err_locator_zip' + selectedRowData.id">* A minimum of 1 valid ZIP code and a maximum of 50 valid ZIP codes must be provided.</small>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                            <div class="form-group" v-if="false">
                                <ValidationProvider
                                        name="How many leads"
                                        rules="required|numeric|min_value:1"
                                        v-slot="{ passed, failed, errors }"
                                    >
                                    <base-input
                                        v-model="newcampaign.asec5_6"
                                        label="Do you want to cap the amount of leads you receive per day? (Input should be numeric and greater than zero) *"
                                        placeholder="Input should be numeric and greater than zero"
                                        :error="errors[0]"
                                        :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                    </base-input>
                                    </ValidationProvider>
                            </div>
  
                    
                        <!-- <div class="col-sm-12 col-md-12 col-lg-12 pt-4"> -->
                            <div class="pt-2">
                                <base-checkbox name="enabledphonenumber" v-model="selectedRowData.phoneenabled" inline>Enable
                                    Phone Number</base-checkbox>
                            </div>
                            <div class="">
                                <base-checkbox name="enabledhomeaddress" v-model="selectedRowData.homeaddressenabled"
                                    inline>Enable Home Address</base-checkbox>
                            </div>
                            <div class="">
                                <base-checkbox name="requireemailaddress" v-model="selectedRowData.require_email"
                                    inline>Require Email Address</base-checkbox>
                            </div>
                         
                        <!-- </div> -->

                        <div class="pt-2">
                            <label>Lead Re-identification Time Limit:</label>
                            <div class="leads-re-identification-selector-wrapper">
                                <div v-for='item in ReidentificationTimeOptions' :key='item.value' :class="['leads-re-identification-selector',{'--active': selectedRowData.reidentification_type == item.value}]" @click='selectedRowData.reidentification_type = item.value'>
                                    {{item.label}}
                                </div>
                            </div>
                            <base-checkbox class="pt-2" name="ApplyReidentificationToAll" v-model="selectedRowData.applyreidentificationall" inline>Do not identify leads already identified on other campaigns.</base-checkbox>
                            <br /><small>* Your lead could be re-identified after the time limit you select.</small>
                        </div>
                    </ValidationObserver>
                    <el-tooltip content="<span>In the boxes below, specify the keywords that your customer<br> is searching for and reading about (contextual keywords).<br> Keywords must be at least three letters long and no more than three words long.<br> For best results, specify as many keywords as possible.<br> You may enter them one at a time, or paste them in bulk separated by a comma.</span>"  effect="light"  :open-delay="300"  placement="top">
                            <template #content><span>In the boxes below, specify the keywords that your customer<br> is searching for and reading about (contextual keywords).<br> Keywords must be at least three letters long and no more than three words long.<br> For best results, specify as many keywords as possible.<br> You may enter them one at a time, or paste them in bulk separated by a comma.</span></template>
                        <div class="col-12 mt-4 text-center"><span style="font-weight:700;cursor: pointer;">Keyword Control Panel</span>
                          <i class="fa fa-question-circle" style="cursor: pointer; margin-left: 12px;"></i>
                        </div></el-tooltip>
                        <div class="col-12 my-4" style="padding: 0px 15px;box-sizing:border-box;" v-if="newcampaign.asec5_9_1">
                            <div class="row" style="width:100%;margin: auto;">
                                
                                <div class="col-sm-6 col-md-6 col-lg-6 border" style="padding:15px">
                                    <ValidationProvider
                                            name="Keywords"
                                            rules="required"
                                            v-slot="{ passed, failed, errors }"
                                        >
                                        <label class="pb-2">Enter Search keywords one at a time and then press enter.*</label>
                                        <base-input>
                                            <tags-input 
                                            v-model="selectedRowData.leadspeek_locator_keyword"
                                            @change="updatekeywordbulkEdit(selectedRowData)"
                                            placeholder="Enter the keyword/phrases then press enter"
                                            :error="errors[0]"
                                            :class="[{ 'has-success': passed }, { 'has-danger': failed }]"
                                            ></tags-input>
                                        
                                        </base-input>                                              
                                        </ValidationProvider>
                                </div>
                                <div class="col-sm-6 col-md-6 col-lg-6 border" style="padding:15px; display: flex; flex-direction: column;">
                                    <label style="flex-shrink: 0;">Use this box to paste Search keywords separated by a comma.</label>
                                    <textarea
                                        class="form-control"
                                        v-model="selectedRowData.leadspeek_locator_keyword_bulk"
                                        @keyup="updatekeywordEdit($event.target.value,selectedRowData)"
                                        @keydown="handleKeydownCommaEdit($event,selectedRowData,'leadspeek_locator_keyword_bulk')"
                                        @blur="sanitizedkeywordEdit('keyword',selectedRowData)"
                                        placeholder="Seperate each term with a comma" rows="50" 
                                        style="flex-grow: 1; resize: none;max-height:none;height:calc(2.33571rem + 2px);">
                                    </textarea>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12" style="padding-block: 8px; padding-left: 0px; padding-right: 0px;">
                                                                    <p v-if="isErrorStopWordsEdit" style="color: red; font-size: 12px;">Some words in your input were not allowed and have been removed.</p>
                                                                    <p style="font-size: 12px;">
                                                                        <i class="el-icon-info"></i>
                                                                        The following keywords are restricted and will be automatically removed if entered: "<span style="font-weight: 600;">{{ stopWords.join(', ') }}</span>".<br>
                                                                        The total length of all keywords must not exceed 500 characters.<br>
                                                                        Keyword should not have more than 3 words.
                                                                    </p>
                            </div>
                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2" id="err_asec5_10" style="display:none">
                            <label style="color:#942434 !important;font-weight:bold">* Target Location and Keywords Field is required</label>
                        </div>
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
    </div>
</template>
<script>
import { DatePicker,Table, TableColumn, Select, Option,Checkbox,Tabs, TabPane, Badge, Switch, Dropdown, DropdownMenu, DropdownItem, CollapseItem, Collapse, RadioGroup, Radio } from 'element-ui';
import { BasePagination,BaseCheckbox, Modal, BaseRadio } from 'src/components';
import { TagsInput } from '/src/components/index';
import Fuse from 'fuse.js';
import swal from 'sweetalert2';
import { html, json } from 'd3';
import axios from 'axios';
import { mapActions } from "vuex";
import SelectClientDropdown from '@/components/SelectClientDropdown.vue'
import { extend } from "vee-validate";
import { required, numeric, regex, confirmed, min_value } from "vee-validate/dist/rules";
var CHECK_GROUPCOMPANY;
const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;
import Vue from 'vue';
import { Loading } from 'element-ui';

Vue.use(Loading.directive);
extend("required", required);
extend("numeric", numeric);
extend("regex", regex);
extend("confirmed", confirmed);
extend("min_value",min_value);

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
        BaseCheckbox,
        TagsInput,
        SelectClientDropdown
    },
    data() {
        return {
            clientdefaultprice:'',
            isLoadingCompanyList: false,
            isLoadingFilterCampaign: false,
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
            rootcostagency: '',
            defaultFilters: ['Campaign Status'],
            clientTypeLead: '',
            errMaxCostPerLead: false,
            errMinCostAgencyPerLead: false,
            minimumCostPerLead: '',
            clientMinLeadDayEnhance: '',
            errMinLeadDay: false, 
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
             tableDataOri:[],
             tableData: [],
             fuseSearch: null,
             searchedData: [],
             searchQuery: '',
             formpass: true,
             isKartraInfoFetching: false,
             isGoHighInfoFetching: false,
             isSendgridInfoFetching: false,
             isGoogleInfoFetching: false,
             isZapierFetching: false,
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
                helpguideTitle:'',
                helpguideTxt: '',

                embededcode: false,
                pricesetup: false,
                campaign: false,
                campaignEdit: false,
                pricesetup: false,
                questionnaire: false,
                integrations: false,
                whitelist: false,
            },
            selects: {
                state: [],
                statelist: [],
                Citystate: [],
                Citystatelist: [],
                city: [],
                citylist: [],
                country: ['8180'],
                countrylist: [
                    {
                        'country' : 'United States',
                        'country_code' : '8180',
                    }
                ],
            },
            tags: {
                keywordlist: [],
                keywordlistbulk:[],
                keywordlistContextual:[],
                keywordlistContextualBulk:[],
            },
            newcampaign: {
                'asec5_4_0_0': false,
                'asec5_4_0_1': false,
                'asec5_4_0_2': false,
                'asec5_4_0_3': false,
                'asec5_4': [],
                'asec5_4_1': [],
                'asec5_4_2': [],
                'asec5_3': '',
                'asec5_5': '',
                'asec5_6': '10',
    
                'asec5_9_1': true,
                'asec5_10': [],
                'asec5_10_1': [],
                'asec6_5': 'FirstName,LastName,MailingAddress,Phone',

                'startdatecampaign': '',
                'enddatecampaign': '',
            },

            questionnaire: {
                'asec5_1': '',
                'asec5_2': '',
                'asec5_3': '',
                'asec5_4_0_1': false,
                'asec5_4_0_2': false,
                'asec5_4_0_3': false,
                'asec5_4_2': '',
                'asec5_4': '',
                'asec5_5': '',
                'asec5_6': '',
                'asec5_7': '',
                'asec5_8': '',

                'asec5_9_1': false,
                'asec5_9_2': false,
                'asec5_9_3': false,
                'asec5_9_4': false,
                'asec5_10': '',
                'asec5_11': '',
                'asec5_12': '',
                'asec5_13': '',
                'asec5_14': '',

                'asec6_1': '',
                'asec6_2': '',
                'asec6_3': '',
                'asec6_4': '',
                'asec6_5': '',
                'asec6_6': false,
                'asec6_7': '',

                'campaign_name': '',
                'nationaltargeting': false,
                'locatorstate': '',
                'locatorcity': '',
                'locatorzip': '',
                'locatorkeyword': '',
                'startcampaign':'',
                'endcampaign': '',
                'enablehomeaddress': false,
                'enablephonenumber': false,
            },
            companyID:'',
            activeClientCompanyID: '',

            ClientCompanyName: '',
            ClientCampaignName: '',
            ClientFullName: '',
            ClientEmail: '',
            ClientPhone: '',
            ClientPerLead: '500',
            ClientUserID: '',
            ClientReportSentTo:'',
            ClientUrlCode: '',
            ClientUrlCodeThankyou: '',
            
            ClientEmbededCode: '',
            ClientOrganizationID: '',
            ClientCampaignID: '',

            popProcessingTxt: 'Please wait, adding new campaign ....',
        
            GoogleConnectFalse: false,
            GoogleConnectTrue: false,
            CompanyNamedisabled: false,

            tmpdefaultadmin : [],
            tmpClientReportSentTo: '',

            checkboxes: {
                hide_phone:true,
                phoneenabled:false,
                homeaddressenabled:false,
                requireemailaddress:true,
                ApplyReidentificationToAll: false,
            },

            radios: {
                reportType: 'GoogleSheet',
                locationTarget: 'Focus',
            },
            selectsAdministrator: {
                administratorSelected: [],
                administratorList: [],
            },
            selectsCompany: {
                companySelected: '',
                companyList: [],
                campaignList: [],
                campaignSelected: [],
            },
            selectsGroupCompany: {
                companyGroupID: '',
                newCompanyGroupName: '',
                companyGroupAddEdit:false,
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

            t_estleadspermonth: '0',

            LeadspeekInputReadOnly: false,
            OnlyPrepaidDisabled: false,
            ActiveLeadspeekID: '',

            StartDateCampaign: '',
            EndDateCampaign: '',
            ErrStartEndCampaign: '',

            leadlocatorname: '',
            leadlocalname: '',

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
                        { value: 'Day', label: 'Day'},
                        //{ value: 'Max', label: 'Max'},
                    ],
            },

            costagency : {
                enhance : {
                    'Weekly' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '0',
                    },
                    'Monthly' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '0',
                    },
                    'OneTime' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '0',
                    },
                    'Prepaid' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '10',
                    }
                },
            },

            // locatorlead: {
            //     FirstName_LastName: '0',
            //     FirstName_LastName_MailingAddress: '0',
            //     FirstName_LastName_MailingAddress_Phone: '0',
            // },

            defaultCostCampaign : {
                paymentterm: 'Weekly',
                EnhancePlatformFee: '0',
                EnhanceCostperlead: '0',
                EnhanceMinCostMonth: '0',
                EnhanceLeadsPerday: '0',

                enhance : {
                    'Weekly' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '0',
                    },
                    'Monthly' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '0',
                    },
                    'OneTime' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '0',
                    },
                    'Prepaid' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '10',
                    }
                },
            },

            Reidentificationtime: 'never',
            userTypeOri: '',
            userType:'',
            err_companyselect:false,
            fetchingCampaignData:false,
            whiteListLeadspeekID: '',
            whiteListCompanyId: '',
            whiteListLeadspeekApiId: '',
            disabledaddcampaign: true,
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
            selectedKartraList: [],
            selectedKartraTags: [],
            kartraTagsOptions: [],
            kartraListOptions: [],
            sendGridcampaignId: '',
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
            selectedRowData: {},
            currSortBy: '',
            currOrderBy: '',
            spreadsheet_id: '',

            supressionProgress: [],
            supressionInterval: '',
            supressionTimeout: '',
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
            stopWords: [''],
            isErrorStopWords: false,
            isErrorStopWordsEdit: false,
            stateCode: [],
            selectedCity: '',
            optionsCity: [],
            isLoadingOptionsCity: false,
            limitOptionsCity: 50,
            totalCity: 0,
            searchCity: '',
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
        ...mapActions(["getUserSendgridList", 'getUserIntegrationList', "updateCompanyIntegrationConfiguration","geUsertKartraList","geUsertKartraDetails",'getSelectedKartraListAndTags']),
        addWebhookUrl() {
            this.zapierWebhook.push('');
        },
        removeWebhookUrl(index) {
            if (this.zapierWebhook.length > 1) {
                this.zapierWebhook.splice(index, 1);
            }
        },
        validateCostPerLeadUnderCostAgency(type) {
            if(type == 'Weekly') {
                const rootCostPerLeadMin = (this.costagency.enhance.Weekly.EnhanceCostperlead > this.rootcostagency.enhance.Weekly.EnhanceCostperlead) ? this.costagency.enhance.Weekly.EnhanceCostperlead : this.rootcostagency.enhance.Weekly.EnhanceCostperlead;
                if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                    this.LeadspeekCostperlead = rootCostPerLeadMin;
                    this.errMinCostAgencyPerLead = false;
                }
            } else if(type == 'Monthly') {
                const rootCostPerLeadMin = (this.costagency.enhance.Monthly.EnhanceCostperlead > this.rootcostagency.enhance.Monthly.EnhanceCostperlead) ? this.costagency.enhance.Monthly.EnhanceCostperlead : this.rootcostagency.enhance.Monthly.EnhanceCostperlead;
                if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                    this.LeadspeekCostperlead = rootCostPerLeadMin;
                    this.errMinCostAgencyPerLead = false;
                }
            } else if(type == 'One Time') {
                const rootCostPerLeadMin = (this.costagency.enhance.OneTime.EnhanceCostperlead > this.rootcostagency.enhance.OneTime.EnhanceCostperlead) ? this.costagency.enhance.OneTime.EnhanceCostperlead : this.rootcostagency.enhance.OneTime.EnhanceCostperlead;
                if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                    this.LeadspeekCostperlead = rootCostPerLeadMin;
                    this.errMinCostAgencyPerLead = false;
                }
            } else if(type == 'Prepaid') {
                const rootCostPerLeadMin = (this.costagency.enhance.Prepaid.EnhanceCostperlead > this.rootcostagency.enhance.Prepaid.EnhanceCostperlead) ? this.costagency.enhance.Prepaid.EnhanceCostperlead : this.rootcostagency.enhance.Prepaid.EnhanceCostperlead;
                if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                    this.LeadspeekCostperlead = rootCostPerLeadMin;
                    this.errMinCostAgencyPerLead = false;
                }
            }
        },
        validateMinimumCostPerLead() {
            if(this.clientTypeLead.type !== 'clientcaplead') {
                if(this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    this.validateCostPerLeadUnderCostAgency('Weekly');
                    this.profitcalculation();
                } else if(this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    this.validateCostPerLeadUnderCostAgency('Monthly');
                    this.profitcalculation();
                } else if(this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    this.validateCostPerLeadUnderCostAgency('One Time');
                    this.profitcalculation();
                } else if(this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    this.validateCostPerLeadUnderCostAgency('Prepaid');
                    this.profitcalculation();
                }
            }
        },
        validateMinLead() {
            if((this.clientMinLeadDayEnhance !== '') && (Number(this.LeadspeekLimitLead) < Number(this.clientMinLeadDayEnhance))) {
                this.errMinLeadDay = false;
                this.LeadspeekLimitLead = this.clientMinLeadDayEnhance 
                this.profitcalculation();
            }
        },
        updateWeeklyMonthlyToggle(){
         this.totalLeads.continualTopUp = this.contiDurationSelection ? (this.LeadspeekLimitLead * 7 ) * 4 : (this.LeadspeekLimitLead * 7)
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
        ...mapActions(["getUserSendgridList", 'getUserIntegrationList', "updateCompanyIntegrationConfiguration","geUsertKartraList","geUsertKartraDetails",'getSelectedKartraListAndTags', "getAgencyZapierDetails", "getCampaignZapierDetails", "getCampaignZapierTags", "getAgencyZapierTags"]),
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
                    this.GetClientList(this.currSortBy,this.currOrderBy);   
                    // console.log(response);
                }
            })
            .catch(error => {
                console.error(error);
            })
        },
        validationLeadToBuy(input) {
            if (Number(input) < 50) {
                this.err_totalleads = true;
            } else {
                this.err_totalleads = false;
            }
        },
        ExportLeadsData(index, row) {
            var leadsExportStart = this.format_date(this.$moment(row.created_at).format('YYYY-MM-DD') + " 00:00:00",true,false);
            var leadsExportEnd = this.format_date(this.$moment().format('YYYY-MM-DD') + " 23:59:59", true, false);
            if(row.created_at != '' && row.id != '') {
                //window.open(process.env.VUE_APP_DATASERVER_URL + '/leadspeek/report/lead/export/' + this.companyID + '/' + this.ClientActiveID + '/' + this.LeaddatePickerStart + '/' + this.LeaddatePickerEnd, "_blank");
                document.location = process.env.VUE_APP_DATASERVER_URL + '/leadspeek/report/lead/export/' + row.company_id + '/' + row.id + '/' + leadsExportStart + '/' + leadsExportEnd;
            }
        },
        changePage(event){
            this.GetClientList(this.currSortBy,this.currOrderBy);
        },
        handleKeydown(event) {
            if (event.key === ' ' || event.key === ',') {
                event.preventDefault(); // Prevent the default action (space or comma)
                this.selectedRowData.report_sent_to += '\n'; // Add a newline
            }
        },
        handlePaste(event) {
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
        handleZipKeydown(event) {
            if (event.key === ' ' || event.key === ',') {
                event.preventDefault(); // Prevent the default action (space or comma)
                // Check if the current line has content before adding a newline
                this.selectedRowData.leadspeek_locator_zip += '\n'; // Add a newline
                }
                this.selectedRowData.leadspeek_locator_zip =  this.selectedRowData.leadspeek_locator_zip.replace(/\n{2,}/g, '\n');
        },
        handleZipPasteCreate(event) {
            event.preventDefault(); // Prevent the default paste action
            // Get the pasted data and replace commas with newline characters
            const pastedText = (event.clipboardData || window.clipboardData).getData('text');
            let modifiedText = pastedText.replace(/,/g, '\n');
            modifiedText = modifiedText.replace(/\s+/g, '\n');
            // Insert the modified text at the current cursor position
            const cursorPos = event.target.selectionStart;
            const textBeforeCursor = this.newcampaign.asec5_3.substring(0, cursorPos);
            const textAfterCursor = this.newcampaign.asec5_3.substring(cursorPos);
            this.newcampaign.asec5_3 = textBeforeCursor + modifiedText + textAfterCursor;
            this.newcampaign.asec5_3 = this.newcampaign.asec5_3.replace(/\n{2,}/g, '\n');
        },  
         handleZipKeydowncreate(event) {
            if (event.key === ' ' || event.key === ',') {
                event.preventDefault(); // Prevent the default action (space or comma)
                // Check if the current line has content before adding a newline
                 this.newcampaign.asec5_3 += '\n'; // Add a newline
                }
                this.newcampaign.asec5_3 = this.newcampaign.asec5_3.replace(/\n{2,}/g, '\n');
        },
        handleZipPaste(event) {
            event.preventDefault(); // Prevent the default paste action
            // Get the pasted data and replace commas with newline characters
            const pastedText = (event.clipboardData || window.clipboardData).getData('text');
            let modifiedText = pastedText.replace(/,/g, '\n');
            modifiedText = modifiedText.replace(/\s+/g, '\n');
            // Insert the modified text at the current cursor position
            const cursorPos = event.target.selectionStart;
            const textBeforeCursor = this.selectedRowData.leadspeek_locator_zip.substring(0, cursorPos);
            const textAfterCursor = this.selectedRowData.leadspeek_locator_zip.substring(cursorPos);
            this.selectedRowData.leadspeek_locator_zip = textBeforeCursor + modifiedText + textAfterCursor;
            this.selectedRowData.leadspeek_locator_zip =   this.selectedRowData.leadspeek_locator_zip.replace(/\n{2,}/g, '\n');
        },
        async handleIntegrationClick(index, row) {
            this.selectedRowData = row
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
            this.selectedIntegration = 'googlesheet'
            this.sendGridListOptions =  this.getUserSendgridList({ companyID: this.selectedRowData.company_id })
            this.modals.integrations = true;
        },
        async saveIntegrationConfiguration() {
            if(this.validateIntegrationFields()){
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
                    data.kartra_is_active =  this.selectedRowData.ghl_is_active ? 1 : 0
                }else if (this.selectedIntegration === 'zapier') {
                    data.zapier_webhook = this.zapierWebhook
                    data.zapier_tags = this.zapierTags
                    data.zapier_is_active = this.zapierEnable ? 1 : 0
                    data.zapier_test_enable = this.zapierTestEnable ? 1 : 0
                    data.leadspeek_api_id = this.selectedRowData.leadspeek_api_id
                    data.campaign_type = 'search_id'
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
            if (this.selectedRowData.sendgrid_action && this.selectedRowData.sendgrid_action.length < 1) {
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
                if ((campaign_webhook.zap_webhook != '' && campaign_webhook.zap_webhook != null) && campaign_webhook.zap_webhook != agency_webhook.api_key) {
                    this.defaultWebhook = false;
                    this.zapierEnable = campaign_webhook.zap_is_active === 1
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
                    this.zapierEnable = campaign_webhook.zap_is_active === 1
                    this.zapierWebhook = []
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
                // this.prefillSendgridIntegrationData()
        },
        async redirectToAddIntegration() {
            if (this.$store.getters.userData.user_type === 'client') {
                this.$router.push({ name: 'Integration List' })
            } else {
                this.$router.push({ name: 'Client List' })
            }
        },
        handleExpandChange(row, expandedRows) {
            const rowIndex = this.tableData.findIndex((item) => item.id === row.id);
            
            if (expandedRows.includes(row)) {
                // Row is expanded
               this.rowClickedEdit(rowIndex,row);
               this.selectedRowData = row
               this.selectedRowData.leadspeek_locator_state = row.leadspeek_locator_state != '' ? row.leadspeek_locator_state.split(',') : ''
               this.selectedRowData.leadspeek_locator_city = row.leadspeek_locator_city != '' ? row.leadspeek_locator_city.split(',') : ''

               if(this.selectedRowData.leadspeek_locator_state.length == 1){
                    this.stateCode = this.selectedRowData.leadspeek_locator_state
                    this.getLocationBigDbm()
               }
            //    this.updatekeywordcontextualBulkEdit(row)


               //console.log('Expanded:', row.name, 'Index:', rowIndex);
            } else {
                // Row is collapsed
                this.ClearClientFormEdit(row);
                //console.log('Collapsed:', row.name, 'Index:', rowIndex);
            }
        },
        show_helpguide(helptype) {
            if (helptype == 'suppression') {
                this.modals.helpguideTitle = "Whitelist Your Current Database"
                this.modals.helpguideTxt = "We do not want to charge you for anyone who is currently in your database. You can Whitelist them by providing an encrypted list of email addresses, or by uploading a list of email addresses and we will encrypt them for you. Do not include any other information in the file aside from the email address. <a href='/samplefile/suppressionlist.csv' target='_blank'>Click here</a> to download a Sample File"
            }

            this.modals.helpguide = true;
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
                    'Access-Control-Allow-Origin' : '*',
                }
           };

            /* UPLOAD FILE */
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
            
            formData.append("leadspeekID",this.whiteListLeadspeekID);
            
            // save it
            this.save(formData);
        },
        showWhitelist(index,row) {
            this.whiteListLeadspeekID = row.id;
            this.whiteListCompanyId = row.company_id;
            this.whiteListLeadspeekApiId = row.leadspeek_api_id;
            this.modals.whitelist = true;

            this.checkStatusFileUpload();
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
        nationaltargetselectedEdit(currentChangeItem,row, event) {  
            const isChecked = event.target.checked

            this.selectedRowData.target_nt = currentChangeItem == 'target_nt' && isChecked ? true : false;
            this.selectedRowData.target_state = currentChangeItem == 'target_state' && isChecked ? true : false;
            this.selectedRowData.target_zip = currentChangeItem == 'target_zip' && isChecked ? true : false;
            // if (status) {
            //     if (row.target_nt == true) {
            //         row.target_state = false;
            //         row.target_city = false;
            //         row.target_zip = false;
            //         //this.radios.locationTarget = "Focus";
            //     }
            // }else{
            //     if (row.target_state || row.target_city || row.target_zip) {
            //         row.target_nt = false;
            //     }
            // }
        },
        updatekeywordbulk() {
            const filterInput = this.tags.keywordlist.map(sentence => {
                const words = sentence.split(' ');

                const filteredWords = words.filter(word => {
                    return !this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase());
                });

                const containsStopWords = words.some(word => 
                    this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase())
                );

                this.isErrorStopWords = containsStopWords

                return filteredWords.join(' ');
            }).filter(sentence => sentence.trim().length > 0);

            this.tags.keywordlistbulk = filterInput;
            this.tags.keywordlist = filterInput;

            this.sanitizedkeyword('keyword');
        },
        updatekeywordbulkEdit(row) {
           
            const filterInput = row.leadspeek_locator_keyword.map(sentence => {
                const words = sentence.split(' ');

                const filteredWords = words.filter(word => {
                    return !this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase());
                });

                const containsStopWords = words.some(word => 
                    this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase())
                );

                this.isErrorStopWordsEdit = containsStopWords

                return filteredWords.join(' ');
            }).filter(sentence => sentence.trim().length > 0);
            row.leadspeek_locator_keyword = filterInput
            row.leadspeek_locator_keyword_bulk = filterInput;
          
            this.sanitizedkeywordEdit('keyword',row);
        },
        updatekeywordcontextualBulkEdit(row) {
            row.leadspeek_locator_keyword_contextual_bulk = row.leadspeek_locator_keyword_contextual;
            this.sanitizedkeywordEdit('contextual',row);
        },
        updatekeywordcontextual(keyword) {
            this.tags.keywordlistContextual = keyword.split(",");
            // this.sanitizedkeyword('contextual');
        }, 
        updatekeywordcontextualBulk(keyword) {
            this.tags.keywordlistContextualBulk = this.tags.keywordlistContextual;
            this.sanitizedkeyword('contextual');
        },
        handleKeydownComma(event,type){
            if (this.tags[type] && event.key === 'Enter') {
                event.preventDefault();
                const keywords = this.tags.keywordlist.join(',') + ',';
                this.tags[type] = keywords

                if(keywords == ','){
                    this.tags[type] = ''
                }

                 // add comma with sanitized
                this.sanitizedkeyword('keyword')
                const newKeywords = this.tags.keywordlist.join(',') + ',';
                this.tags[type] = newKeywords
            }
        }, 
        handleKeydownCommaEdit(event,row,type){
            if ( event.key === 'Enter') {
                event.preventDefault();
                const keywords = row.leadspeek_locator_keyword.join(',') + ','
                row[type] = keywords
                if(row[type] == ','){
                    row[type] = ''
                }

                // add comma with sanitized
                this.sanitizedkeywordEdit(event, row)
                const newKeywords = row.leadspeek_locator_keyword.join(',') + ','
                row[type] = newKeywords
            }
        },
        updatekeyword(event,keyword) {
           const inputKeyword = keyword.split(',')

           const filterInput = inputKeyword.map(sentence => {
                const words = sentence.split(' ');

                const filteredWords = words.filter(word => {
                    return !this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase());
                });

                const containsStopWords = words.some(word => 
                    this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase())
                );

                this.isErrorStopWords = containsStopWords

                return filteredWords.join(' ');
            }).filter(sentence => sentence.trim().length > 0);
           
            if(this.customTrim(keyword) == "") {
                this.tags.keywordlist = [];
            }else{
                this.tags.keywordlist = filterInput
            }
        },
         updatekeywordEdit(keyword,row) {
            const inputKeyword = keyword.split(',')
            const filterInput = inputKeyword.map(sentence => {
                const words = sentence.split(' ');

                const filteredWords = words.filter(word => {
                    return !this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase());
                });

                const containsStopWords = words.some(word => 
                    this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase())
                );

                this.isErrorStopWordsEdit = containsStopWords

                return filteredWords.join(' ');
            }).filter(sentence => sentence.trim().length > 0);

            if(this.customTrim(keyword) == "") {
                row.leadspeek_locator_keyword = [];
            }else{
                row.leadspeek_locator_keyword = filterInput;
            }
        },
       
        updatekeywordcontextualEdit(keyword,row) {
            row.leadspeek_locator_keyword_contextual = keyword.split(",");
        },
        customTrim(str) {
            return str.replace(/^\s+|\s+$/g, '');
        },
        sanitizedkeyword(section) { 
            const minLengthRegex = /^.{3,}$/;
            const maxWordsRegex = /^(?:\S+\s*){1,3}$/;
            if (section == 'keyword') {
                var keylenght = this.tags.keywordlist.length;    
                var tmp = this.tags.keywordlist;
                this.tags.keywordlist = [];
                var k = 0;
                for(var i=0;i<keylenght;i++) {
                    // Check both conditions using the regular expressions.
                    var _words = this.customTrim(tmp[i]);
                    if (minLengthRegex.test(_words) && maxWordsRegex.test(_words)) {
                        this.tags.keywordlist[k] = _words;
                        k++;
                    }
                }
                // let trimmedKeywords = [];
                // let totalLength = 0;
                
                let keywords = Array.isArray(this.tags.keywordlistbulk) 
                    ? this.tags.keywordlistbulk.join(',').split(',')
                    : this.tags.keywordlistbulk.split(',');
                let trimmedKeywords = [];
                let totalLength = 0;

                for (let keyword of keywords) {
                    keyword = keyword.trim();
                    if (totalLength + keyword.length + (trimmedKeywords.length > 0 ? 1 : 0) <= 500) {
                        trimmedKeywords.push(keyword);
                        totalLength += keyword.length + (trimmedKeywords.length > 1 ? 1 : 0); // Add 1 for comma
                    } else {
                        this.$notify({
                            type: 'warning',
                            message: `Keyword "${keyword}" was removed to stay within the 500 character limit.`
                        });
                        break;
                    }
                }
                trimmedKeywords = trimmedKeywords.filter(keyword => {
                    if ((keyword.match(/\s+/g) || []).length <= 2) {
                        return true;
                    } else {
                        this.$notify({
                            type: 'warning',
                            message: `Keyword "${keyword}" was removed because it contains more than 3 words.`
                        });
                        return false;
                    }
                });
                
                const filterInput = trimmedKeywords.map(sentence => {
                    const words = sentence.split(' ');

                    const filteredWords = words.filter(word => {
                        return !this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase());
                    });

                    const containsStopWords = words.some(word => 
                        this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase())
                    );

                    this.isErrorStopWords = containsStopWords

                    return filteredWords.join(' ');
                }).filter(sentence => sentence.trim().length > 0);
                            

                // this.tags.keywordlistbulk = trimmedKeywords.join(',');
                // this.tags.keywordlist = trimmedKeywords;
           

                // if (trimmedKeywords.length < this.tags.keywordlist.length) {
                //     this.$notify({
                //         type: 'warning',
                //         message: 'Some keywords were removed to stay within the 500 character limit.'
                //     });
                // }

                this.tags.keywordlist = filterInput;
                this.tags.keywordlistbulk = filterInput;
            }else{
                var keylenght = this.tags.keywordlistContextual.length;
                var tmp = this.tags.keywordlistContextual;
                this.tags.keywordlistContextual = [];
                var k = 0;
                for(var i=0;i<keylenght;i++) {
                    // Check both conditions using the regular expressions.
                    var _words = this.customTrim(tmp[i]);
                    if (minLengthRegex.test(_words) && maxWordsRegex.test(_words)) {
                        this.tags.keywordlistContextual[k] = _words;
                        k++;
                    }
                }
                this.tags.keywordlistContextualBulk = this.tags.keywordlistContextual;
            }
        },
        sanitizedkeywordEdit(section,row) { 
           
            const minLengthRegex = /^.{3,}$/;
            const maxWordsRegex = /^(?:\S+\s*){1,3}$/;
            // if (section == 'keyword') {
                var keylenght = row.leadspeek_locator_keyword.length;    
                var tmp = row.leadspeek_locator_keyword;
                row.leadspeek_locator_keyword = [];
                var k = 0;
                for(var i=0;i<keylenght;i++) {
                    // Check both conditions using the regular expressions.
                    var _words = this.customTrim(tmp[i]);
                    if (minLengthRegex.test(_words) && maxWordsRegex.test(_words)) {
                        row.leadspeek_locator_keyword[k] = _words;
                        k++;
                    }
                }
               
                // let tmp = row.leadspeek_locator_keyword;
                // row.leadspeek_locator_keyword = row.leadspeek_locator_keyword.filter(keyword => keyword.length <= 500);
                // if (row.leadspeek_locator_keyword.length < tmp.length) {
                //     this.$notify({
                //         type: 'warning',
                //         message: 'Keywords exceeding 500 characters have been removed.'
                //     });
                // }
                // let trimmedKeywords = [];
                // let totalLength = 0;
                
                // for (let keyword of row.leadspeek_locator_keyword) {
                //     if (totalLength + keyword.length <= 500) {
                //         trimmedKeywords.push(keyword);
                //         totalLength += keyword.length;
                //     } else if (totalLength < 500) {
                //         let remainingSpace = 500 - totalLength;
                //         // trimmedKeywords.push(keyword.substring(0, remainingSpace));
                //         this.$notify({
                //             type: 'warning',
                //             message: `Keyword "${keyword}" has been removed to stay within the 500 character limit.`
                //         });
                //         break;
                //     } else {
                //         break;
                //     }
                // }
                // console.log(row.leadspeek_locator_keyword_bulk,'excess')
                let keywords = Array.isArray(row.leadspeek_locator_keyword_bulk) 
                    ? row.leadspeek_locator_keyword_bulk.join(',').split(',')
                    : row.leadspeek_locator_keyword_bulk.split(',');
                // let keywords = row.leadspeek_locator_keyword_bulk.split(',');
                let trimmedKeywords = [];
                let totalLength = 0;

                for (let keyword of keywords) {
                    keyword = keyword.trim();
                    if (totalLength + keyword.length + (trimmedKeywords.length > 0 ? 1 : 0) <= 500) {
                        trimmedKeywords.push(keyword);
                        totalLength += keyword.length + (trimmedKeywords.length > 1 ? 1 : 0); // Add 1 for comma
                    } else {
                        this.$notify({
                            type: 'warning',
                            message: `Keyword "${keyword}" was removed to stay within the 500 character limit.`
                        });
                        break;
                    }
                }

                trimmedKeywords = trimmedKeywords.filter(keyword => {
                    if ((keyword.match(/\s+/g) || []).length <= 2) {
                        return true;
                    } else {
                        this.$notify({
                            type: 'warning',
                            message: `Keyword "${keyword}" was removed because it contains more than 3 words.`
                        });
                        return false;
                    }
                });
                
                const filterInput = trimmedKeywords.map(sentence => {
                    const words = sentence.split(' ');

                    const filteredWords = words.filter(word => {
                        return !this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase());
                    });

                    const containsStopWords = words.some(word => 
                        this.stopWords.some(stopWord => word.toLowerCase() === stopWord.toLowerCase())
                    );

                    this.isErrorStopWords = containsStopWords

                    return filteredWords.join(' ');
                }).filter(sentence => sentence.trim().length > 0);

                // row.leadspeek_locator_keyword_bulk = trimmedKeywords.join(',');
                // row.leadspeek_locator_keyword  = trimmedKeywords;
                // let trimmedKeywords = row.leadspeek_locator_keyword.map(keyword => {
                //     if (keyword.length > 500) {
                //         this.$notify({
                //             type: 'warning',
                //             message: `Keyword "${keyword.substring(0, 20)}..." has been trimmed to 500 characters.`
                //         });
                //         return keyword.substring(0, 500);
                //     }
                //     return keyword;
                // });
                row.leadspeek_locator_keyword = filterInput;
                row.leadspeek_locator_keyword_bulk = filterInput;
               
            // }else{
            //     var keylenght = row.leadspeek_locator_keyword_contextual.length;
            //     var tmp = row.leadspeek_locator_keyword_contextual;
            //     row.leadspeek_locator_keyword_contextual = [];
            //     var k = 0;
            //     for(var i=0;i<keylenght;i++) {
            //         // Check both conditions using the regular expressions.
            //          var _words = this.customTrim(tmp[i]);
            //         if (minLengthRegex.test(_words) && maxWordsRegex.test(_words)) {
            //             row.leadspeek_locator_keyword_contextual[k] = _words;
            //             k++;
            //         }
            //     }
            //     row.leadspeek_locator_keyword_contextual_bulk = row.leadspeek_locator_keyword_contextual;
            // }
        },
        archiveCampaign(index,row) {
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
                            if(response.result == 'success') {
                                this.deleteRow(row);
                                
                                this.$notify({
                                    type: 'success',
                                    message: 'Campaign has been archive.',
                                    icon: 'far fa-save'
                                });  
                            }
                        },error => {
                            this.$notify({
                                type: 'primary',
                                message: 'Sorry there is something wrong, pleast try again later',
                                icon: 'fas fa-bug'
                            }); 

                        });
                    }

                });

            }else{
                    this.$notify({
                    type: 'primary',
                    message: 'Please stop the campaign before archive it.',
                    icon: 'fas fa-bug'
                }); 
            }
        },
        nationaltargetselected(currentChangeItem, event) {
            const isChecked = event.target.checked

            this.newcampaign.asec5_4_0_0 = currentChangeItem == 'asec5_4_0_0' && isChecked ? true : false;;
            this.newcampaign.asec5_4_0_1 = currentChangeItem == 'asec5_4_0_1' && isChecked ? true : false;;
            this.newcampaign.asec5_4_0_3 = currentChangeItem == 'asec5_4_0_3' && isChecked ? true : false;;

            if(this.newcampaign.asec5_4_0_0){
                this.selectedCity = []
                this.selects.state = [];
                this.selects.city = [];
                this.selects.Citystate = [];
            }

            if(this.newcampaign.asec5_4_0_1){
                this.newcampaign.asec5_4_0_0 = false;
                this.newcampaign.asec5_3 = '';
            }

            if(this.newcampaign.asec5_4_0_3){
                this.selectedCity = []
                this.selects.state = [];
                this.selects.city = [];
                this.selects.Citystate = [];
            }
            
            // if (status) {
            //     //console.log(this.questionnaire.asec5_4_0_0);
            //     if (this.newcampaign.asec5_4_0_0 == true) {
            //         // this.newcampaign.asec5_4_0_1 = false;
            //         // this.newcampaign.asec5_4_0_2 = false;
            //         // this.newcampaign.asec5_4_0_3 = false;
            //         this.radios.locationTarget = "Focus";

            //         this.selects.state = [];
            //         this.selects.city = [];
            //         this.selects.Citystate = [];
            //         this.newcampaign.asec5_3 = '';
            //         //this.newcampaign.asec5_4_1 = this.selects.country;
            //         //this.newcampaign.asec5_10 = this.tags.keywordlist;
            //         //this.newcampaign.asec5_10_1 = this.tags.keywordlistContextual;
            //         //this.newcampaign.asec5_4_2 = this.selects.city;
            //     }
            // }else{
            //     if (this.newcampaign.asec5_4_0_1 || this.newcampaign.asec5_4_0_2 || this.newcampaign.asec5_4_0_3) {
            //         // this.newcampaign.asec5_4_0_0 = false;
            //     }
            //     if(!this.newcampaign.asec5_4_0_1) {
            //         this.selects.state = [];
            //     }

            //     if(!this.newcampaign.asec5_4_0_2) {
            //         this.selects.city = [];
            //         this.selects.Citystate = [];
            //     }

            //     if(!this.newcampaign.asec5_4_0_3) {
            //         this.newcampaign.asec5_3 = '';
            //     }
            // }
        },
         redirectaddclient() {
            this.$router.push({ name: 'Client List' })
         },
         onCityStateChange() {
            if (this.selects.Citystate != '') {
                $('#boxCityState').addClass('disabled-area');
                this.$store.dispatch('getCityStateList',{
                citystate:this.selects.Citystate,
                }).then(response => {
                    //console.log(response.params.geo_targets);
                    this.selects.citylist = response.params.geo_targets;
                    $('#boxCityState').removeClass('disabled-area');
                },error => {
                    $('#boxCityState').removeClass('disabled-area');
                });
            }else{
                $('#boxCityState').removeClass('disabled-area');
            }
            return false;
         },
         onCityStateChangeEdit(row) {
            if (row.selects_citystate != '') {
                $('#boxCityState').addClass('disabled-area');
                this.$store.dispatch('getCityStateList',{
                citystate:row.selects_citystate,
                }).then(response => {
                    //console.log(response.params.geo_targets);
                    row.selects_citylist = response.params.geo_targets;
                    $('#boxCityState').removeClass('disabled-area');
                },error => {
                    $('#boxCityState').removeClass('disabled-area');
                });
            }else{
                $('#boxCityState').removeClass('disabled-area');
            }
            return false;
         },
         getStateList() {
            this.$store.dispatch('getLocationBigDbm', {
                type: 'state',
                param: '',
                limit: '',
                search: '',
            }).then(response => {
                this.selects.statelist = response.data
            }, error => {
                console.error(error);
            })
         },
         tooltip_campaign(index,row,stat) {
            if (stat == 'play') {
                if (row.disabled == 'T' && row.active_user == 'F' && row.customer_card_id != '') {
                    return "Start Campaign";
                }else if (row.disabled == 'F' && row.active_user == 'T' && row.customer_card_id != '') {
                    return "Campaign is Running";
                }else if (row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                    return "Campaign can not start before your complete payment information"
                }else{
                    return "Start Campaign";
                }
            }else if (stat == 'pause') {
                if (row.disabled == 'F' && row.active_user == 'F' && row.customer_card_id != '') {
                    return "Pause Campaign";
                }else if (row.disabled == 'T' && row.active_user == 'T' && row.customer_card_id != '') {
                    return "Campaign is Paused";
                }else if (row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                    return "Campaign can not start before your complete payment information"
                }else{
                    return "Pause Campaign";
                }
                
            }else if (stat == 'stop') {
                if (row.active_user == 'T' && row.customer_card_id != '') {
                    return "Stop Campaign";
                }else if (row.active_user == 'F' && row.customer_card_id != '') {
                    return "Campaign is Stopped";
                }else if (row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                    return "Campaign can not start before your complete payment information"
                }else{
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
                        LimitLeadStart: this.$moment(this.LeadspeekMaxDateStart).format('YYYY-MM-DD'),
                        LimitLeadEnd: this.format_date(this.$moment(this.LeadspeekDateEnd).format('YYYY-MM-DD') + " 23:59:59",true,false),
                        PaymentTerm: this.selectsPaymentTerm.PaymentTermSelect,
                        contiDurationSelection: this.contiDurationSelection,
                        topupoptions: this.prepaidType,
                        leadsbuy: this.prepaidType == 'continual' ? this.totalLeads.continualTopUp : this.totalLeads.oneTime,
                        PlatformFee: this.LeadspeekPlatformFee,
                        LeadspeekApiId: this.ActiveLeadspeekID,
                        idUser: this.$store.getters.userData.id,
                        ipAddress: this.$store.getters.userData.ip_login,
                        idSys: this.$global.idsys
                    }).then(response => {
                        //console.log(response[0]);
                        /** UPDATE ROW */
                        //this.tableData[this.LeadspeekClientActiveIndex].lp_min_cost_month = this.LeadspeekMinCostMonth
                        
                        this.tableData.filter((item) => {
                            if (item.id == this.ClientActiveID){
                                item.lp_min_cost_month = this.LeadspeekMinCostMonth
                                item.lp_max_lead_month = this.LeadspeekMaxLead
                                item.cost_perlead = this.LeadspeekCostperlead
                                item.lp_limit_leads = this.LeadspeekLimitLead
                                item.lp_limit_freq = this.selectsAppModule.LeadsLimitSelect
                                item.lp_limit_startdate = this.LeadspeekMaxDateStart
                                item.lp_enddate = this.LeadspeekDateEnd
                                item.paymentterm = this.selectsPaymentTerm.PaymentTermSelect
                                item.platformfee = this.LeadspeekPlatformFee
                                item.topupoptions = this.prepaidType
                                item.leadsbuy = this.prepaidType == 'continual' ? this.totalLeads.continualTopUp : this.totalLeads.oneTime
                                item.stopcontinual =  (this.isContainulTopUpStop)? 'T':'F'
                                item.continual_buy_options = this.contiDurationSelection;
                            }
                        })

                        /** UPDAE ROW */
                        this.modals.pricesetup = false;
                        this.$notify({
                            type: 'success',
                            message: 'Data has been updated successfully',
                            icon: 'far fa-save'
                        });  
                    },error => {
                        
                });
                /** SET MODULE COST */
            }
        },
        paymentTermChange() {
            if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                this.txtLeadService = 'per week';
                this.txtLeadIncluded = 'in that weekly charge';
                this.txtLeadOver ='from the weekly charge';

                if (typeof(this.costagency.enhance.Weekly.EnhanceLeadsPerday) == 'undefined') {
                    this.costagency.enhance.Weekly.EnhanceLeadsPerday = (this.clientMinLeadDayEnhance !== '') ? this.clientMinLeadDayEnhance : "10"
                }

                this.m_LeadspeekPlatformFee = this.costagency.enhance.Weekly.EnhancePlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.enhance.Weekly.EnhanceCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.enhance.Weekly.EnhanceMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.enhance.Weekly.EnhanceLeadsPerday;

                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead;

                /*if (this.selectsPaymentTerm.PaymentTermSelect != this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.locator.Weekly.LocatorPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.locator.Weekly.LocatorCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.locator.Weekly.LocatorMinCostMonth;
                }else{*/
                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.EnhancePlatformFee
                    this.LeadspeekCostperlead = this.defaultCostCampaign.EnhanceCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.EnhanceMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.EnhanceLeadsPerday;
                }else{
                    if (this.clientdefaultprice != "") {
                        this.LeadspeekPlatformFee = this.clientdefaultprice.enhance.Weekly.EnhancePlatformFee;
                        this.LeadspeekCostperlead = this.clientdefaultprice.enhance.Weekly.EnhanceCostperlead;
                        this.LeadspeekMinCostMonth = this.clientdefaultprice.enhance.Weekly.EnhanceMinCostMonth;
                    }else{
                        this.LeadspeekPlatformFee = this.defaultCostCampaign.enhance.Weekly.EnhancePlatformFee;
                        this.LeadspeekCostperlead = this.defaultCostCampaign.enhance.Weekly.EnhanceCostperlead;
                        this.LeadspeekMinCostMonth = this.defaultCostCampaign.enhance.Weekly.EnhanceMinCostMonth;
                        //this.LeadspeekLimitLead = this.defaultCostCampaign.enhance.Weekly.EnhanceLeadsPerday;
                    }
                }

                if((this.clientMinLeadDayEnhance !== '') && (Number(this.LeadspeekLimitLead) < Number(this.clientMinLeadDayEnhance))) {
                    this.LeadspeekLimitLead = this.clientMinLeadDayEnhance;
                }

                if(this.clientTypeLead.type !== 'clientcaplead') {
                    this.validateCostPerLeadUnderCostAgency('Weekly');
                }
            }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                this.txtLeadService = 'per month';
                this.txtLeadIncluded = 'in that monthly charge';
                this.txtLeadOver ='from the monthly charge';

                if (typeof(this.costagency.enhance.Monthly.EnhanceLeadsPerday) == 'undefined') {
                    // this.costagency.enhance.Monthly.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                    this.costagency.enhance.Monthly.EnhanceLeadsPerday = (this.clientMinLeadDayEnhance !== '') ? this.clientMinLeadDayEnhance : "10";
                }

                this.m_LeadspeekPlatformFee = this.costagency.enhance.Monthly.EnhancePlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.enhance.Monthly.EnhanceCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.enhance.Monthly.EnhanceMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.enhance.Monthly.EnhanceLeadsPerday;

                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead;

                /*if (this.selectsPaymentTerm.PaymentTermSelect != this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.locator.Monthly.LocatorPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.locator.Monthly.LocatorCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.locator.Monthly.LocatorMinCostMonth;
                }else{*/
                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.EnhancePlatformFee
                    this.LeadspeekCostperlead = this.defaultCostCampaign.EnhanceCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.EnhanceMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.EnhanceLeadsPerday;
                }else{
                    if (this.clientdefaultprice != "") {
                        this.LeadspeekPlatformFee = this.clientdefaultprice.enhance.Monthly.EnhancePlatformFee;
                        this.LeadspeekCostperlead = this.clientdefaultprice.enhance.Monthly.EnhanceCostperlead;
                        this.LeadspeekMinCostMonth = this.clientdefaultprice.enhance.Monthly.EnhanceMinCostMonth;
                    }else{
                        this.LeadspeekPlatformFee = this.defaultCostCampaign.enhance.Monthly.EnhancePlatformFee;
                        this.LeadspeekCostperlead = this.defaultCostCampaign.enhance.Monthly.EnhanceCostperlead;
                        this.LeadspeekMinCostMonth = this.defaultCostCampaign.enhance.Monthly.EnhanceMinCostMonth;
                        //this.LeadspeekLimitLead = this.defaultCostCampaign.enhance.Monthly.EnhanceLeadsPerday;
                    }
                }

                if((this.clientMinLeadDayEnhance !== '') && (Number(this.LeadspeekLimitLead) < Number(this.clientMinLeadDayEnhance))) {
                    this.LeadspeekLimitLead = this.clientMinLeadDayEnhance;
                }

                if(this.clientTypeLead.type !== 'clientcaplead') {
                    this.validateCostPerLeadUnderCostAgency('Monthly');

                }
            } else if(this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                this.txtLeadService = 'per month';
                this.txtLeadIncluded = 'in that week charge';
                this.txtLeadOver = 'from the week charge';

                if (typeof(this.costagency.enhance.Prepaid) == 'undefined') {
                    this.$set(this.costagency.enhance,'Prepaid',{
                        LocatorPlatformFee: '0',
                        LocatorCostperlead: '0',
                        LocatorMinCostMonth: '0',
                        LocatorLeadsPerday: '10',
                    });
                }

                if (typeof(this.costagency.enhance.Prepaid.EnhanceLeadsPerday) == 'undefined') {
                    this.$set(this.costagency.enhance.Prepaid, 'EnhanceLeadsPerday', '10');
                    this.costagency.enhance.Prepaid.EnhanceLeadsPerday = '10';
                    
                }
                
                this.m_LeadspeekPlatformFee = this.costagency.enhance.Prepaid.EnhancePlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.enhance.Prepaid.EnhanceCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.enhance.Prepaid.EnhanceMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.enhance.Prepaid.EnhanceLeadsPerday;

                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead;

                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.EnhancePlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.EnhanceCostperlead
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.EnhanceMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.EnhanceLeadsPerday;
                }else{
                    if (this.clientdefaultprice != "") {
                        this.LeadspeekPlatformFee = this.clientdefaultprice.enhance.Prepaid.EnhancePlatformFee;
                        this.LeadspeekCostperlead = this.clientdefaultprice.enhance.Prepaid.EnhanceCostperlead
                        this.LeadspeekMinCostMonth = this.clientdefaultprice.enhance.Prepaid.EnhanceMinCostMonth;
                    }else{
                        this.LeadspeekPlatformFee = this.defaultCostCampaign.enhance.Prepaid.EnhancePlatformFee;
                        this.LeadspeekCostperlead = this.defaultCostCampaign.enhance.Prepaid.EnhanceCostperlead
                        this.LeadspeekMinCostMonth = this.defaultCostCampaign.enhance.Prepaid.EnhanceMinCostMonth;
                        //this.LeadspeekLimitLead = this.defaultCostCampaign.enhance.Prepaid.EnhanceLeadsPerday;
                    }
                }

                if(this.clientTypeLead.type !== 'clientcaplead') {
                    this.validateCostPerLeadUnderCostAgency('Prepaid');
                }

                if((this.clientMinLeadDayEnhance !== '') && (Number(this.LeadspeekLimitLead) < Number(this.clientMinLeadDayEnhance))) {
                    this.LeadspeekLimitLead = this.clientMinLeadDayEnhance;
                }
            } else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                this.txtLeadService = '';
                this.txtLeadIncluded = '';
                this.txtLeadOver ='';

                this.m_LeadspeekPlatformFee = this.costagency.enhance.OneTime.EnhancePlatformFee;
                this.m_LeadspeekCostperlead = this.costagency.enhance.OneTime.EnhanceCostperlead;
                this.m_LeadspeekMinCostMonth = this.costagency.enhance.OneTime.EnhanceMinCostMonth;
                this.m_LeadspeekLimitLead = this.costagency.enhance.OneTime.EnhanceLeadsPerday;

                this.LeadspeekPlatformFee = this.m_LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.m_LeadspeekCostperlead; 
                this.LeadspeekMinCostMonth = this.m_LeadspeekMinCostMonth;
                this.LeadspeekLimitLead = this.m_LeadspeekLimitLead;

                /*if (this.selectsPaymentTerm.PaymentTermSelect != this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.locator.OneTime.LocatorPlatformFee;
                    this.LeadspeekCostperlead = this.defaultCostCampaign.locator.OneTime.LocatorCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.locator.OneTime.LocatorMinCostMonth;
                }else{*/
                if (this.selectsPaymentTerm.PaymentTermSelect == this.defaultCostCampaign.paymentterm) {
                    this.LeadspeekPlatformFee = this.defaultCostCampaign.EnhancePlatformFee
                    this.LeadspeekCostperlead = this.defaultCostCampaign.EnhanceCostperlead;
                    this.LeadspeekMinCostMonth = this.defaultCostCampaign.EnhanceMinCostMonth;
                    this.LeadspeekLimitLead = this.defaultCostCampaign.EnhanceLeadsPerday;
                }

                if((this.clientMinLeadDayEnhance !== '') && (Number(this.LeadspeekLimitLead) < Number(this.clientMinLeadDayEnhance))) {
                    this.LeadspeekLimitLead = this.clientMinLeadDayEnhance;
                }

                if(this.clientTypeLead.type !== 'clientcaplead') {
                    this.validateCostPerLeadUnderCostAgency('One Time');
                }
            }

            /** CALCULATION PROFIT PER TERM */
                this.profitcalculation();
            /** CALCULATION PROFIT PER TERM */
        },
        checkLeadsType() {
            if (this.selectsAppModule.LeadsLimitSelect == 'Max') {
                this.LeadspeekMaxDateVisible = true;
            }else{
                this.LeadspeekMaxDateVisible = false;
            }
        },
        formatPrice(value) {
            //let val = (value/1).toFixed(2).replace(',', '.')
            let val = (value/1).toFixed(2)
            return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
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

        profitcalculation_() {
            this.t_LeadspeekPlatformFee = this.LeadspeekPlatformFee - this.m_LeadspeekPlatformFee;
            this.t_LeadspeekMinCostMonth = this.LeadspeekMinCostMonth - this.m_LeadspeekMinCostMonth;
            this.t_LeadspeekCostperlead = this.LeadspeekCostperlead - this.m_LeadspeekCostperlead;
            
            if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                this.t_profit = this.formatPrice(this.t_LeadspeekPlatformFee + this.t_LeadspeekMinCostMonth - (this.m_LeadspeekCostperlead * this.LeadspeekMaxLead));
                this.t_LeadspeekCostperlead = this.formatPrice(this.m_LeadspeekCostperlead * this.LeadspeekMaxLead);
                this.t_freqshow = '';
                this.t_LeadspeekPlatformFee = this.formatPrice(this.t_LeadspeekPlatformFee);
                this.t_LeadspeekMinCostMonth = this.formatPrice(this.t_LeadspeekMinCostMonth);
            }else{
                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    this.t_freqshow = 'week';
                    this.t_freq = '1';
                }else {
                    this.t_freqshow = 'month';
                    this.t_freq = '4';
                }
                var _estleadspermonth = ((this.LeadspeekLimitLead * 7) * this.t_freq) * this.t_LeadspeekCostperlead;
                this.t_estleadspermonth = this.formatPrice(_estleadspermonth);
                this.t_profit =  this.formatPrice(this.t_LeadspeekMinCostMonth + _estleadspermonth);
                this.t_LeadspeekCostperlead = this.formatPrice(this.t_LeadspeekCostperlead);
                this.t_LeadspeekPlatformFee = this.formatPrice(this.t_LeadspeekPlatformFee);
                this.t_LeadspeekMinCostMonth = this.formatPrice(this.t_LeadspeekMinCostMonth);
            }
        },

        calculationMinimumCostPerLead() {
            if(this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    const m_LeadspeekCostperlead = (this.LeadspeekCostperlead * this.clientTypeLead.value) / 100;
                    const rootCostPerLeadMin = (this.costagency.enhance.Weekly.EnhanceCostperlead > this.rootcostagency.enhance.Weekly.EnhanceCostperlead) ? this.costagency.enhance.Weekly.EnhanceCostperlead : this.rootcostagency.enhance.Weekly.EnhanceCostperlead;
                    const rootCostPerLeadMax = (rootCostPerLeadMin) / (this.clientTypeLead.value / 100);

                    if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                        this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                        this.minimumCostPerLead = rootCostPerLeadMin;
                        this.errMinCostAgencyPerLead = true;
                    } else {
                        this.errMinCostAgencyPerLead = false;
                    }

                    if(this.clientTypeLead.type == 'clientcapleadpercentage') {
                        if(Number(this.LeadspeekCostperlead) > Number(rootCostPerLeadMax)) {
                            this.m_LeadspeekCostperlead = m_LeadspeekCostperlead;
                            this.errMinCostAgencyPerLead = false;
                        } else if(Number(this.LeadspeekCostperlead) <= Number(rootCostPerLeadMax) && Number(this.LeadspeekCostperlead) >= Number(rootCostPerLeadMin)) {
                            this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                            this.errMinCostAgencyPerLead = false;
                        } 
                    }
                } else if(this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    const m_LeadspeekCostperlead = (this.LeadspeekCostperlead * this.clientTypeLead.value) / 100;
                    const rootCostPerLeadMin = (this.costagency.enhance.Monthly.EnhanceCostperlead > this.rootcostagency.enhance.Monthly.EnhanceCostperlead) ? this.costagency.enhance.Monthly.EnhanceCostperlead : this.rootcostagency.enhance.Monthly.EnhanceCostperlead;
                    const rootCostPerLeadMax = (rootCostPerLeadMin) / (this.clientTypeLead.value / 100);
                    
                    if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                        this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                        this.minimumCostPerLead = rootCostPerLeadMin;
                        this.errMinCostAgencyPerLead = true;
                    } else {
                        this.errMinCostAgencyPerLead = false;
                    }

                    if(this.clientTypeLead.type == 'clientcapleadpercentage') {
                        if(Number(this.LeadspeekCostperlead) > Number(rootCostPerLeadMax)) {
                            this.m_LeadspeekCostperlead = m_LeadspeekCostperlead;
                            this.errMinCostAgencyPerLead = false;
                        } else if(Number(this.LeadspeekCostperlead) <= Number(rootCostPerLeadMax) && Number(this.LeadspeekCostperlead) >= Number(rootCostPerLeadMin)) {
                            this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                            this.errMinCostAgencyPerLead = false;
                        } 
                    }
                } else if(this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    const m_LeadspeekCostperlead = (this.LeadspeekCostperlead * this.clientTypeLead.value) / 100;
                    const rootCostPerLeadMin = (this.costagency.enhance.OneTime.EnhanceCostperlead > this.rootcostagency.enhance.OneTime.EnhanceCostperlead) ? this.costagency.enhance.OneTime.EnhanceCostperlead : this.rootcostagency.enhance.OneTime.EnhanceCostperlead;
                    const rootCostPerLeadMax = (rootCostPerLeadMin) / (this.clientTypeLead.value / 100);

                    if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                        this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                        this.minimumCostPerLead = rootCostPerLeadMin;
                        this.errMinCostAgencyPerLead = true;
                    } else {
                        this.errMinCostAgencyPerLead = false;
                    }

                    if(this.clientTypeLead.type == 'clientcapleadpercentage') {
                        if(Number(this.LeadspeekCostperlead) > Number(rootCostPerLeadMax)) {
                            this.m_LeadspeekCostperlead = m_LeadspeekCostperlead;
                            this.errMinCostAgencyPerLead = false;
                        } else if(Number(this.LeadspeekCostperlead) <= Number(rootCostPerLeadMax) && Number(this.LeadspeekCostperlead) >= Number(rootCostPerLeadMin)) {
                            this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                            this.errMinCostAgencyPerLead = false;
                        } 
                    }
                } else if(this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    const m_LeadspeekCostperlead = (this.LeadspeekCostperlead * this.clientTypeLead.value) / 100;
                    const rootCostPerLeadMin = (this.costagency.enhance.Prepaid.EnhanceCostperlead > this.rootcostagency.enhance.Prepaid.EnhanceCostperlead) ? this.costagency.enhance.Prepaid.EnhanceCostperlead : this.rootcostagency.enhance.Prepaid.EnhanceCostperlead;
                    const rootCostPerLeadMax = (rootCostPerLeadMin) / (this.clientTypeLead.value / 100);

                    if((Number(this.LeadspeekCostperlead) < Number(rootCostPerLeadMin) && this.LeadspeekCostperlead != 0) || this.LeadspeekCostperlead === '') {
                        this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                        this.minimumCostPerLead = rootCostPerLeadMin;
                        this.errMinCostAgencyPerLead = true;
                    } else {
                        this.errMinCostAgencyPerLead = false;
                    }

                    if(this.clientTypeLead.type == 'clientcapleadpercentage') {
                        if(Number(this.LeadspeekCostperlead) > Number(rootCostPerLeadMax)) {
                            this.m_LeadspeekCostperlead = m_LeadspeekCostperlead;
                            this.errMinCostAgencyPerLead = false;
                        } else if(Number(this.LeadspeekCostperlead) <= Number(rootCostPerLeadMax) && Number(this.LeadspeekCostperlead) >= Number(rootCostPerLeadMin)) {
                            this.m_LeadspeekCostperlead = rootCostPerLeadMin;
                            this.errMinCostAgencyPerLead = false;
                        } 
                    }
                }
        },

        // new function profitcalculation, add estimate client cost - AGIES
        profitcalculation() {
            if(this.clientTypeLead.type === 'clientcaplead') {
                if(this.LeadspeekCostperlead > this.clientTypeLead.value) {
                    this.errMaxCostPerLead = true;
                    this.LeadspeekCostperlead = this.clientTypeLead.value;
                } else {
                    this.errMaxCostPerLead = false;
                }
            } else if(this.clientTypeLead.type != 'clientcaplead') {
                this.calculationMinimumCostPerLead();
            }

            if((this.clientMinLeadDayEnhance !== '') && (Number(this.LeadspeekLimitLead) < Number(this.clientMinLeadDayEnhance))) {
                this.errMinLeadDay = true;
            } else {
                this.errMinLeadDay = false;
            }

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
            } else {
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

        showQuestionnaire(index,row) {

            this.questionnaire.campaign_name = row.campaign_name;
            this.questionnaire.nationaltargeting = (row.national_targeting == 'T')?true:false;
            this.questionnaire.locatorstate = row.leadspeek_locator_state;
            this.questionnaire.locatorcity = row.leadspeek_locator_city;
            this.questionnaire.locatorzip = row.leadspeek_locator_zip;

            this.questionnaire.startcampaign = row.campaign_startdate;
            this.questionnaire.endcampaign = row.campaign_enddate;
            this.questionnaire.enablehomeaddress = (row.homeaddressenabled == 'T')?true:false; 
            this.questionnaire.enablephonenumber = (row.phoneenabled == 'T')?true:false;
            this.questionnaire.locatorkeyword = row.leadspeek_locator_keyword;

            var aq = JSON.parse(row.questionnaire_answers);
            this.questionnaire.asec5_1 = aq.asec5_1;
            this.questionnaire.asec5_2 = aq.asec5_2;
            this.questionnaire.asec5_3 = aq.asec5_3;
            this.questionnaire.asec5_4_0_1 = aq.asec5_4_0_1;
            this.questionnaire.asec5_4_0_2 = aq.asec5_4_0_2,
            this.questionnaire.asec5_4_0_3 = aq.asec5_4_0_3,
            this.questionnaire.asec5_4_2 = aq.asec5_4_2;
            this.questionnaire.asec5_4 = aq.asec5_4;
            this.questionnaire.asec5_5 = aq.asec5_5;
            this.questionnaire.asec5_6 = aq.asec5_6;
            this.questionnaire.asec5_7 = aq.asec5_7;

            var tmpArray = row.file_url.split("|");
            var usrfile = "";
            for(let k=0;k<tmpArray.length;k++) {
                usrfile = usrfile + "- <a style='font-weight:bold' href='" + process.env.VUE_APP_CDNQUESTIONNAIRE + tmpArray[k] + "' target='_blank'>" + tmpArray[k] + "</a><br/>";
            }

            this.questionnaire.asec5_8 = usrfile;

            this.questionnaire.asec5_9_1 = aq.asec5_9_1;
            this.questionnaire.asec5_9_2 = aq.asec5_9_2;
            this.questionnaire.asec5_9_3 = aq.asec5_9_3;
            this.questionnaire.asec5_9_4 = aq.asec5_9_4;

            this.questionnaire.asec5_10 = aq.asec5_10;
            this.questionnaire.asec5_11 = aq.asec5_11;
            this.questionnaire.asec5_12 = aq.asec5_12;
            this.questionnaire.asec5_13 = aq.asec5_13;
            this.questionnaire.asec5_14 = aq.asec5_14;
            
            this.questionnaire.asec6_1 = (aq.asec6_1 == "GoogleSheet")?"Google Sheet":"Google Sheet";
            if (typeof(aq.asec6_2) != 'undefined'){
                this.questionnaire.asec6_2 = aq.asec6_2.replace('_',' ');
            }
            this.questionnaire.asec6_3 = (typeof(aq.asec6_3) == 'undefined')?'':aq.asec6_3;
            this.questionnaire.asec6_4 = (typeof(aq.asec6_4) == 'undefined')?'':aq.asec6_4;

            if (aq.asec6_5 == "FirstName,LastName,MailingAddress") {
                this.questionnaire.asec6_5 = "Emails, names and mailing addresses: $2.00 (Best for sending mailers.)";
            }else if (aq.asec6_5 == "FirstName,LastName") {
                this.questionnaire.asec6_5 = "Emails and Names: $1.50 (Best for email campaigns and Facebook retargeting.)";
            }else if (aq.asec6_5 == "FirstName,LastName,MailingAddress,Phone") {
                this.questionnaire.asec6_5 = "Emails, names, mailing addresses and phone number: $3.00";
            }
            
            this.questionnaire.asec6_6 = (aq.asec6_6)?"I understand and agree to follow the law and will notify anyone using these leads of the law.":"";
            this.questionnaire.asec6_7 = aq.asec6_7;
            
            this.LeadspeekCompany = row.campaign_name + ' #' + row.leadspeek_api_id + ' (' + row.company_name + ')';
            this.modals.questionnaire = true;
        },
        resetAgencyCost() {
            this.m_LeadspeekPlatformFee = '0';
            this.m_LeadspeekCostperlead = '0';
            this.m_LeadspeekMinCostMonth = '0';

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
                    this.rootcostagency = response.rootcostagency;
                    this.clientTypeLead = response.clientTypeLead;
                    this.clientMinLeadDayEnhance = response.clientMinLeadDayEnhance;
                    this.clientdefaultprice = response.clientDefaultPrice;

                    if((this.clientMinLeadDayEnhance !== '') && (Number(this.LeadspeekLimitLead) < Number(this.clientMinLeadDayEnhance))) {
                        this.LeadspeekLimitLead = this.clientMinLeadDayEnhance;
                    }

                    if (typeof(this.costagency.enhance.Prepaid) == 'undefined') {
                        this.$set(this.costagency.enhance,'Prepaid',{
                            EnhancePlatformFee: '0',
                            EnhanceCostperlead: '0',
                            EnhanceMinCostMonth: '0',
                            EnhanceLeadsPerday: '10',
                        });
                    }

                    if (typeof(this.costagency.enhance.Prepaid.EnhanceLeadsPerday) === 'undefined') {
                        this.$set(this.costagency.enhance.Prepaid, 'EnhanceLeadsPerday', '10');
                        this.costagency.enhance.Prepaid.EnhanceLeadsPerday = '10';
                    }

                    if (this.selectsPaymentTerm.PaymentTermSelect =='Weekly') {
                        if(this.clientTypeLead.type !== 'clientcaplead') {
                            this.validateCostPerLeadUnderCostAgency('Weekly');
                        }
                        this.m_LeadspeekPlatformFee = this.costagency.enhance.Weekly.EnhancePlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.enhance.Weekly.EnhanceCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.enhance.Weekly.EnhanceMinCostMonth;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect =='Monthly') {
                        if(this.clientTypeLead.type !== 'clientcaplead') {
                            this.validateCostPerLeadUnderCostAgency('Monthly');

                        }
                        this.m_LeadspeekPlatformFee = this.costagency.enhance.Monthly.EnhancePlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.enhance.Monthly.EnhanceCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.enhance.Monthly.EnhanceMinCostMonth;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect =='One Time') {
                        if(this.clientTypeLead.type !== 'clientcaplead') {
                            this.validateCostPerLeadUnderCostAgency('One Time');
                        }
                        this.m_LeadspeekPlatformFee = this.costagency.enhance.OneTime.EnhancePlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.enhance.OneTime.EnhanceCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.enhance.OneTime.EnhanceMinCostMonth;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                        if(this.clientTypeLead.type !== 'clientcaplead') {
                            this.validateCostPerLeadUnderCostAgency('Prepaid');
                        }
                        this.m_LeadspeekPlatformFee = this.costagency.enhance.Prepaid.EnhancePlatformFee;
                        this.m_LeadspeekCostperlead = this.costagency.enhance.Prepaid.EnhanceCostperlead;
                        this.m_LeadspeekMinCostMonth = this.costagency.enhance.Prepaid.EnhanceMinCostMonth;
                        this.m_LeadspeekLimitLead = this.costagency.enhance.Prepaid.EnhanceLeadsPerday;
                    }

                        this.paymentTermChange();

                     /** CALCULATION PROFIT PER TERM */
                        this.profitcalculation();
                    /** CALCULATION PROFIT PER TERM */
                }else{
                    this.m_LeadspeekPlatformFee = '0';
                    this.m_LeadspeekCostperlead = '0';
                    this.m_LeadspeekMinCostMonth = '0';
                }
                
                /** HACK TEMPORARY FOR TRYSERA CAMPAIGN STILL */
                let campaignIDexception = ["2530","2558","2559","2581","2560","2555","2546","2441","2563","2562"];
                let leadpriceException = ["0.15","0.15","0.15","0.17","0.17","0.17","0.17","0.17","0.17","0.17"];
                const searchexecption = campaignIDexception.indexOf(this.ActiveLeadspeekID);
                
                if (searchexecption >= 0) {
                    this.m_LeadspeekCostperlead = leadpriceException[searchexecption];
                }
                /** HACK TEMPORARY FOR TRYSERA CAMPAIGN STILL */

                /** CALCULATION PROFIT PER TERM */
                    this.profitcalculation();
                /** CALCULATION PROFIT PER TERM */

                this.modals.pricesetup = true;
                
            },error => {
                    
            });
        },
        handlePriceSet(index, row) {
            //console.log(row);
            this.activeClientCompanyID = row.company_id;
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
            
            this.defaultCostCampaign.paymentterm = row.paymentterm;
            this.defaultCostCampaign.EnhancePlatformFee = row.platformfee;
            this.defaultCostCampaign.EnhanceMinCostMonth = row.lp_min_cost_month;
            this.defaultCostCampaign.EnhanceCostperlead = row.cost_perlead;
            this.defaultCostCampaign.EnhanceLeadsPerday = row.lp_limit_leads;

            if (row.lp_limit_startdate == '' || row.lp_limit_startdate ==  null) {
                this.LeadspeekMaxDateStart = this.$moment().format('YYYY-MM-DD HH:mm:ss');
            }else{
                this.LeadspeekMaxDateStart = this.$moment(row.lp_limit_startdate).format('YYYY-MM-DD HH:mm:ss');
            }
            if (row.lp_enddate == '' || row.lp_enddate ==  null) {
                this.LeadspeekDateEnd = '';
            }else{
                this.LeadspeekDateEnd = this.$moment(row.lp_enddate).format('YYYY-MM-DD HH:mm:ss');
            }
            if (row.lp_limit_freq == 'max') {
                this.LeadspeekMaxDateVisible = true;
            }else{
                this.LeadspeekMaxDateVisible = false;
            }
            if (((row.active_user == 'T'  && row.active == 'T') || (row.active == 'F' && row.disabled == 'F')) && row.paymentterm != 'Prepaid') {
                this.LeadspeekInputReadOnly = true;
            }else{
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
        chkHideShowCol(row) {
            if (row.hide_phone == 'T') {
                row.hide_phone = 'F';
            }else{
                row.hide_phone = 'T';
            }
        },
        companyGroupChange(_companyGroupSelected,id) {
            if (id == '') {
                for(let i=0;i<this.selectsGroupCompany.companyGroupList.length;i++) {
                    if (this.selectsGroupCompany.companyGroupList[i].id == _companyGroupSelected) {
                        this.selectsGroupCompany.companyGroupID = this.selectsGroupCompany.companyGroupList[i].id;
                        this.selectsGroupCompany.newCompanyGroupName = this.selectsGroupCompany.companyGroupList[i].group_name;
                    }
                }
            }else{
                for(let i=0;i<this.selectsGroupCompany.companyGroupList.length;i++) {
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
                    if(response.result == 'success') {
                        if(response.params.action == 'Add') {
                            this.selectsGroupCompany.companyGroupList.push({'id':response.params.id,'group_name':response.params.group_name});
                            this.$global.selectsGroupCompany.companyGroupList.push({'id':response.params.id,'group_name':response.params.group_name});
                            this.selectsGroupCompany.companyGroupSelected = response.params.id;
                            this.selectsGroupCompany.companyGroupID = response.params.id;
                            this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;
                            
                        }else if(response.params.action == 'Edit') {
                            /** UPDATE */
                            for(let i=0;i<this.selectsGroupCompany.companyGroupList.length;i++) {
                                if (this.selectsGroupCompany.companyGroupList[i].id == response.params.id) {
                                    this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;
                                    this.selectsGroupCompany.companyGroupList[i].group_name = response.params.group_name;
                                }
                            }

                            for(let i=0;i<this.$global.selectsGroupCompany.companyGroupList.length;i++) {
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

                },error => {
                
                    this.$notify({
                        type: 'primary',
                        message: 'Sorry there is something wrong, pleast try again later',
                        icon: 'fas fa-bug'
                    }); 
                });

            }else{
                 
                 this.$store.dispatch('AddEditGroupCompany', {
                    companyGroupID: id.group_company_id,
                    companyGroupName: id.group_name,
                    companyID: this.companyID,
                    moduleID: '3',
                }).then(response => {
                    //console.log(response[0]);
                    if(response.result == 'success') {
                        if(response.params.action == 'Add') {
                            this.selectsGroupCompany.companyGroupList.push({'id':response.params.id,'group_name':response.params.group_name});
                            this.$global.selectsGroupCompany.companyGroupList.push({'id':response.params.id,'group_name':response.params.group_name});
                            id.group_company_id = response.params.id;
                            id.group_name = response.params.group_name;
                            //this.selectsGroupCompany.companyGroupID = response.params.id;
                            //this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;
                            
                        }else if(response.params.action == 'Edit') {
                            /** UPDATE */
                            for(let i=0;i<this.selectsGroupCompany.companyGroupList.length;i++) {
                                if (this.selectsGroupCompany.companyGroupList[i].id == response.params.id) {
                                    id.group_name = response.params.group_name;
                                    //this.selectsGroupCompany.newCompanyGroupName = response.params.group_name;
                                    this.selectsGroupCompany.companyGroupList[i].group_name = response.params.group_name;
                                }
                            }

                            for(let i=0;i<this.$global.selectsGroupCompany.companyGroupList.length;i++) {
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

                },error => {
                
                    this.$notify({
                        type: 'primary',
                        message: 'Sorry there is something wrong, pleast try again later',
                        icon: 'fas fa-bug'
                    }); 
                });
            }
        },
        cancelAddeditCompanyGroup(id) {
            if(id == '') {
                this.selectsGroupCompany.companyGroupSelected = '';
                this.selectsGroupCompany.companyGroupID = '';
                this.selectsGroupCompany.newCompanyGroupName = ''
            }else{
                id = id.id;
            }
            $('#editGroupName' + id).hide();
            $('#listGroupName' + id).show();
        },
        removeCompanyGroup(act,id) {
            var _companyGroupID;
        
            if (id == ''){
                _companyGroupID = this.selectsGroupCompany.companyGroupID;
            }else{
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
                                for(let i=0;i<this.selectsGroupCompany.companyGroupList.length;i++) {
                                    if (this.selectsGroupCompany.companyGroupList[i].id == _companyGroupID) {
                                        this.selectsGroupCompany.companyGroupList.splice(i,1);
                                    }
                                }

                                for(let i=0;i<this.$global.selectsGroupCompany.companyGroupList.length;i++) {
                                    if (this.$global.selectsGroupCompany.companyGroupList[i].id == _companyGroupID) {
                                        this.$global.selectsGroupCompany.companyGroupList.splice(i,1);
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
                            },error => {
                                
                            });
                            
                        /** REMOVE COMPANY GROUP */    
                    }
            });
            
        },
        addeditCompanyGroup(act,id) {
            if (id == '') {
                if (act == 'Add') {
                    this.selectsGroupCompany.companyGroupID = '';
                    this.selectsGroupCompany.newCompanyGroupName = '';
                }else if (act == 'Edit') {
                    if (this.selectsGroupCompany.companyGroupID == ''){
                        return;
                    }
                }
            }else{ 
                if (act == 'Add') {
                    id.group_company_id = '';
                    id.group_name = '';
                }else if (act == 'Edit') {
                    if (id.group_company_id == ''){
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
        sortcolumn: function(a,b) {
            return a.value - b.value;
        },
        sortdate: function(a,b) {
            return new Date(a.last_lead_added) - new Date(b.last_lead_added);
        },
         sortnumber: function(a,b) {
             return a - b;
        },
        process_activepauseclient(index,row) {
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
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                idSys: this.$global.idsys,
            }).then(response => {
                if(typeof (response.result) !== 'undefined' && response.result === 'failedPayment') {
                    $('#processingArea').removeClass('disabled-area');
                    $('#popProcessing').hide();

                    $('#activePause' + index).removeClass('fas fa-pause gray').addClass('fas fa-pause orange nocursor');
                    $('#activePlay' + index).removeClass('fas fa-play green nocursor').addClass('fas fa-play gray');
                    this.GetClientList(this.currSortBy,this.currOrderBy,this.searchQuery);
                    this.$notify({
                            type: 'danger',
                            message: response.msg,
                            icon: 'fas fa-bug'
                        }); 
                        return false;
                }else{
                     $('#processingArea').removeClass('disabled-area');
                     $('#popProcessing').hide();

                     if (row.disabled == 'F') {
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
                        var msg = 'Campaign successfully activated.';
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
            },error => {
                $('#processingArea').removeClass('disabled-area');
                $('#popProcessing').hide();

                this.$notify({
                            type: 'primary',
                            message: 'sorry this campaign cannot be started, please contact your administrator with campaign ID : #' + row.leadspeek_api_id + ' (1)',
                            icon: 'fas fa-bug'
                        }); 
                        return false;
            });
        },
        async activepauseclient(index,row,action) {
            if(row.paymentterm.toLowerCase() === 'prepaid' && (row.leadsbuy == 0 || row.leadsbuy == null)) {
                swal.fire({
                    icon: "error",
                    text: "You cannot start the campaign until you have set up the campaign financials. Please click the dollar icon to set them up.",
                })
                return false;
            }
            
            if ((action == 'pause' && row.disabled == 'T')) {
                return false;
            }else if ((action == 'play' && row.disabled == 'F')) {
                return false;
            }else if (action == 'play' && row.disabled == 'T' && row.active_user == 'F' && (row.customer_card_id == '' && this.$store.getters.userData.manual_bill == 'F')) {
                return false;
            }

            /* GET COSTAGENCY IF COST_PERLEAD ZERO */
            if(this.$store.getters.userData.user_type == 'userdownline') {
                this.contiDurationSelection = row.continual_buy_options;
                this.selectsPaymentTerm.PaymentTermSelect = row.paymentterm;
                this.LeadspeekCostperlead = row.cost_perlead;

                try {
                    const response = await this.$store.dispatch('getGeneralSetting', {
                        companyID: this.companyID,
                        settingname: 'costagency',
                        idSys: this.$global.idsys,
                        pk: row.company_id,
                    });

                    this.clientTypeLead = response.clientTypeLead;                    
                    this.m_LeadspeekCostperlead = this.costagency.ehance.Prepaid.EnhanceCostPerLead;
                    this.m_LeadspeekPlatformFee = this.costagency.enhance.Prepaid.EnhancePlatformFee;
                    
                    if(this.clientTypeLead.type == 'clientcapleadpercentage') {
                        this.costagency = response.data;
                        this.rootcostagency = response.rootcostagency;
                        this.calculationMinimumCostPerLead();
                    }

                } catch (error) {
                    console.error(error);
                    return false;
                }
            }
            /* GET COSTAGENCY IF COST_PERLEAD ZERO */
            
            if (action == 'play' && row.disabled == 'T' && row.active_user == 'F') {
                /** CHECK IF THE CAMPAIGN ID EXIST ON SIMPLI.FI */
                // if (row.clientcampaignsid == '') {
                //     this.$notify({
                //             type: 'primary',
                //             message: 'sorry this campaign cannot be started, please contact administrator with campaign ID : #' + row.leadspeek_api_id + ' (2)',
                //             icon: 'fas fa-bug'
                //         }); 
                //         return false;
                // }else if (row.campaign_startdate == '0000-00-00') {
                //     this.$notify({
                //             type: 'primary',
                //             message: 'sorry this campaign cannot be started, please check the campaign start and end date.',
                //             icon: 'fas fa-bug'
                //         }); 
                //         return false;
                // }
               /** CHECK IF THE CAMPAIGN ID EXIST ON SIMPLI.FI */
                // var defaultText = 'Activating this will charge a One Time startup fee of $' + row.platformfee + ' and the ' + row.paymentterm + ' fee of $' + row.lp_min_cost_month + '. This campaign is set up for ' + row.paymentterm.toLowerCase() + ' billing and your client will be billed on the same day each ' + row.paymentterm.toLowerCase() + '.';
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

                // const userData = this.$store.getters.userData;
                // if (userData.user_type == 'client') {
                //     defaultText = 'Activating this will charge a One Time startup fee of $' + row.platformfee + ' and the ' + row.paymentterm + ' fee of $' + row.lp_min_cost_month + '. This campaign is set up for ' + row.paymentterm.toLowerCase() + ' billing and you will be billed on the same day each ' + row.paymentterm.toLowerCase() + '.';
                // }

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
                        this.process_activepauseclient(index,row);
                    }
                });
            }else{
                this.process_activepauseclient(index,row);
            }
            
        },
        
        setIconReportType(row) {
            if(row.report_type == "CSV") {
                return  '<i class="fas fa-file-csv" style="color:white;padding-right:10px"></i> CSV File';
            }else if (row.report_type == "Excel") {
                return  '<i class="far fa-file-excel" style="color:white;padding-right:10px"></i> Microsoft Excel File';
            }else if (row.report_type == "GoogleSheet") {
                return  '<i class="fab fa-google-drive" style="color:white;padding-right:10px"></i> <a style="color:white" href="https://docs.google.com/spreadsheets/d/' + row.spreadsheet_id + '/edit#gid=0" target="_blank">Google Spreadsheet</a>';
            }
        },
        disconnect_googleSpreadSheet() {
            this.$store.dispatch('disconectGoogleSheet', {
                companyID: this.companyID,
            }).then(response => {
                //console.log(response.googleSpreadsheetConnected);
                this.GoogleConnectTrue = false;
                this.GoogleConnectFalse = true;
            },error => {
                
            });
        },
        connect_googleSpreadSheet() {
            window.removeEventListener('message', this.callbackGoogleConnected);
            window.addEventListener('message', this.callbackGoogleConnected);

            var left = (screen.width/2)-(1024/2);
            var top = (screen.height/2)-(800/2);
            var fbwindow = window.open(process.env.VUE_APP_DATASERVER_URL + '/auth/google-spreadSheet/' + this.companyID,'Google SpreadSheet Auth',"menubar=no,toolbar=no,status=no,width=640,height=800,toolbar=no,location=no,modal=1,left="+left+",top="+top);
        },
        onCompanyChange(_companySelected) {
            for(let i=0;i<this.selectsCompany.companyList.length;i++) {
                if (this.selectsCompany.companyList[i].id == _companySelected) {
                    this.ClientFullName = this.selectsCompany.companyList[i].name;
                    this.ClientEmail = this.selectsCompany.companyList[i].email;
                    this.ClientPhone = this.selectsCompany.companyList[i].phonenum;
                    this.ClientUserID = this.selectsCompany.companyList[i].id;
                    this.ClientCompanyName = this.selectsCompany.companyList[i].company_name;
                    this.selectsCompany.companySelected = this.selectsCompany.companyList[i].company_name;
                    this.ClientReportSentTo = this.selectsCompany.companyList[i].email;
                    this.tmpClientReportSentTo = this.selectsCompany.companyList[i].email;
                }
            }

            for(let i=0;i<this.$global.selectsGroupCompany.companyGroupList.length;i++) {
                if (this.$global.selectsGroupCompany.companyGroupList[i].id == _companySelected) {
                    this.newcampaign.asec5_6 = this.$global.selectsGroupCompany.companyGroupList[i].LocatorLeadsPerday;
                }
            }
        },
        replaceCommaLine(data) {
            //convert string to array and remove whitespace
            let dataToArray = data.split(',').map(item => item.trim());
            //convert array to string replacing comma with new line
            return dataToArray.join("\n");
        },
        rowClicked(index,row) {
            this.modals.campaignEdit = !this.modals.campaignEdit
        },
        rowClickedEdit(index,row) {
            this.modals.campaignEdit = !this.modals.campaignEdit
            // if ($('.ClientRow' + index + '.expanded').length > 0) {
            //     this.CancelAddEditClient(row);
            //     return false;
            // }
            this.spreadsheet_id = row.spreadsheet_id

            row.target_nt = false;
            row.target_state = false;
            row.target_city = false;
            row.target_zip = false;
             
            if (row.national_targeting == 'T') {
                row.target_nt = true;
            }else{
                if (row.leadspeek_locator_zip != "" && row.leadspeek_locator_zip != null) {
                    row.target_zip = true;
                    row.leadspeek_locator_zip = this.replaceCommaLine(row.leadspeek_locator_zip);
                }

                if (row.leadspeek_locator_state != "" && row.leadspeek_locator_state != null) {
                    row.target_state = true;
                    var _ClientLocatorState =  row.leadspeek_locator_state.split(",");
                    var _ClientLocatorStateSifi = row.leadspeek_locator_state_simplifi.split(",");

                    for(var i=0;i<_ClientLocatorState.length;i++) {
                        if (_ClientLocatorStateSifi[i] != '' &&  _ClientLocatorState[i] != '') {
                            let newValue = _ClientLocatorStateSifi[i] + '|' + _ClientLocatorState[i];
                            let existingIndex = row.selects_state.findIndex(item => item === newValue);

                            if (existingIndex !== -1) {
                                row.selects_state[existingIndex] = newValue;
                            } else {
                                row.selects_state.push(newValue);
                            }

                        }
                    }

                }

                if (row.leadspeek_locator_city != "" && row.leadspeek_locator_city != null) {
                    row.target_city = true;
                    var _ClientLocatorCity =  row.leadspeek_locator_city.split(",");
                    var _ClientLocatorCitySifi = row.leadspeek_locator_city_simplifi.split(",");

                    for(i=0;i<_ClientLocatorCity.length;i++) {
                        if (_ClientLocatorCitySifi[i] != '' && _ClientLocatorCity[i] != '') {
                            /** INITIAL LIST FOR CITY BEFOR DEFAULT SELECTED */
                            var _tmp = {
                                'active':true,
                                'id':_ClientLocatorCitySifi[i],
                                'metro_code_id': '',
                                'name': _ClientLocatorCity[i],
                                'parent_id' : '',
                                'resource':'',
                                'resources': [{}],
                                'update_date':'',
                            }
                            row.selects_citylist.push(_tmp);
                            /** INITIAL LIST FOR CITY BEFOR DEFAULT SELECTED */
                            row.selects_city.push(_ClientLocatorCitySifi[i] + '|' + _ClientLocatorCity[i]);
                        }
                    }

                }

            }

            
            if (row.leadspeek_locator_keyword != "" && row.leadspeek_locator_keyword != null) {
                if(Array.isArray(row.leadspeek_locator_keyword)){
                    row.leadspeek_locator_keyword = row.leadspeek_locator_keyword;
                } else {
                    row.leadspeek_locator_keyword = row.leadspeek_locator_keyword.split(',');
                }
            }

            if (row.leadspeek_locator_keyword_contextual != null && row.leadspeek_locator_keyword_contextual != "") {
                row.leadspeek_locator_keyword_contextual = row.leadspeek_locator_keyword_contextual.split(",");
            }

            row.campaign_startdate = (row.campaign_startdate != '0000-00-00')?row.campaign_startdate:'';
            row.campaign_enddate = (row.campaign_enddate != '0000-00-00')?row.campaign_enddate:'';
            
            
            if (row.homeaddressenabled == 'T') {
                row.homeaddressenabled = true;
            }else{
                row.homeaddressenabled = false;
            }

            if (row.phoneenabled == 'T') {
                row.phoneenabled = true;
            }else{
                row.phoneenabled = false;
            }

            if (row.require_email == 'T') {
                row.require_email = true;
            }else{
                row.require_email = false;
            }

            if (row.applyreidentificationall == 'T') {
                row.applyreidentificationall = true;
            } else {
                row.applyreidentificationall = false;
            }
            
            row.statuscampaignplay = true;
            
            if (row.disabled == 'F' && row.active_user == 'T') {
                row.statuscampaignplay = true;
            }

            //this.$refs.tableData.toggleRowExpansion(row);
        },
        tableRowClassName({row, rowIndex}) {
                row.index = rowIndex;
                return 'clickable-rows ClientRow' + rowIndex;
        },
        ClearClientForm() { 
            this.ClientPerLead = '100';
            this.radios.reportType = 'GoogleSheet';
            this.selectsAdministrator.administratorSelected = [];
            this.ClientCampaignName = '';
            this.ClientUrlCode = '';
            this.ClientUrlCodeThankyou = '';
            
            this.newcampaign.asec5_4_0_0 = false;
            this.newcampaign.asec5_4_0_1 = false;
            this.newcampaign.asec5_4_0_2 = false;
            this.newcampaign.asec5_4_0_3 = false;
            this.checkboxes.ApplyReidentificationToAll = false;

            this.selects.state = [];
            this.selects.city = [];
            this.selects.Citystate = [];
            this.tags.keywordlist = [];
            this.tags.keywordlistbulk = [];
            this.tags.keywordlistContextual = [];
            this.tags.keywordlistContextualBulk = [];
            this.newcampaign.asec5_3 = '';
            this.newcampaign.asec5_6 = '0';
            $('#err_locator_zip').removeClass("errwarning");

            let _tmpdefaultadmin = Array();
                $.each(this.tmpdefaultadmin,function(key, value) {
                     if(value['defaultadmin'] == 'T') {
                        _tmpdefaultadmin.push(value['id']);
                     }
                });
            
            this.selectsAdministrator.administratorSelected = _tmpdefaultadmin;
            if ((localStorage.getItem('companyGroupSelected') == null || localStorage.getItem('companyGroupSelected') == '')){
                this.ClientFullName = '';
                this.ClientEmail = '';
                this.ClientPhone = '';
                this.ClientUserID = '';
                this.ClientCompanyName = '';
                this.selectsCompany.companySelected = '';
                 this.ClientReportSentTo = this.tmpClientReportSentTo;
            }
            
        },
        ClearClientFormEdit(id) {
                if (id.leadspeek_locator_keyword != null && id.leadspeek_locator_keyword != '') {
                    id.leadspeek_locator_keyword = id.leadspeek_locator_keyword.join(',');
                }
                if (id.leadspeek_locator_keyword_contextual != null && id.leadspeek_locator_keyword_contextual != '') {
                    id.leadspeek_locator_keyword_contextual = id.leadspeek_locator_keyword_contextual.join(',');
                }
                id.selects_state = [];
                id.selects_city = [];
                id.selects_citylist = [];

                if (id.homeaddressenabled) {
                    id.homeaddressenabled = 'T';
                }else{
                    id.homeaddressenabled = 'F';
                }

                if (id.phoneenabled) {
                    id.phoneenabled = 'T';
                }else{
                    id.phoneenabled = 'F';
                }
                
                if (id.applyreidentificationall) {
                    id.applyreidentificationall = 'T';
                } else {
                    id.applyreidentificationall = 'F';
                }

                if (id.require_email) {
                    id.require_email = 'T';
                }else{
                    id.require_email = 'F';
                }

                $('#err_locator_zip' + id.id).removeClass("errwarning");
        },
        AddEditClient(id) {
            this.modals.campaign = !this.modals.campaign 
        },
        CancelAddEditClient(id) {
            this.isErrorStopWords = false;
            this.isErrorStopWordsEdit = false;
            if(id == '') {
              this.ClearClientForm();
              this.modals.campaign = false
            }else{
                //this.ClearClientFormEdit(id);
                this.modals.campaignEdit = false
                this.GetClientList(this.currSortBy,this.currOrderBy)
            }
            
        },
        ResendLink(id) {
            if(id.id != '') {
                $('#btnResend' + id.id).attr('disabled',true);
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
                    $('#btnResend' + id.id).attr('disabled',false);
                    $('#btnResend' + id.id).html('Resend Google Sheet Link'); 

                    this.$notify({
                        type: 'success',
                        message: 'Invitation has been sent!',
                        icon: 'far fa-save'
                    });  

                },error => {
                    $('#btnResend' + id.id).attr('disabled',false);
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
            if(id == '') {
                /** PROCESS ADD / EDIT ORGANIZATION */

                if(id == '') {
                    $('#err_startendcamp').hide();

                    /** VALIDATE QUESTIONNAIRE FORM */
                        this.formpass = true;

                        /** CHECK START AND END DATE CAMPAIGN */
                        let todaydate = this.$moment().format('YYYYMMDD');
                       
                        let startdatecampaign = this.$moment(this.StartDateCampaign).format('YYYYMMDD');
                        let enddatecampaign =  this.$moment(this.EndDateCampaign).format('YYYYMMDD');

                        // if (enddatecampaign <= startdatecampaign) {
                        //     //this.ErrStartEndCampaign = '* Campaign end date must be on or after start date';
                        //     this.ErrStartEndCampaign = '* Campaign end date must be after start / today date';
                        //     $('#err_startendcamp').show();
                        //     this.formpass = false;
                        // }else if (enddatecampaign <= todaydate) {
                        //     //this.ErrStartEndCampaign = '* Campaign end date must be on or after today date';
                        //     this.ErrStartEndCampaign = '* Campaign end date must be after today date';
                        //     $('#err_startendcamp').show();
                        //     this.formpass = false;
                        // // }else if (startdatecampaign < todaydate) {
                        // //     //this.ErrStartEndCampaign = '* Campaign start date must be on or after today date';
                        // //     this.ErrStartEndCampaign = '* Campaign start date must be after today date';
                        // //     $('#err_startendcamp').show();
                        // //     this.formpass = false;
                        // }else{


                        //     if (this.StartDateCampaign == "" || this.StartDateCampaign == null || this.EndDateCampaign == "" || this.EndDateCampaign == null) {
                        //         this.ErrStartEndCampaign = '* Please fill the date when campaign start and end';
                        //         this.formpass = false;
                        //         $('#err_startendcamp').show();
                        //     }else{
                        //         $('#err_startendcamp').hide();
                        //     }
                        // }

                     /** CHECK START AND END DATE CAMPAIGN */

                    if (this.ClientCampaignName == "") {
                        this.formpass = false;
                        $('#err_campaignname').show();
                    }else{
                        $('#err_campaignname').hide();
                    }

                    if (this.selectsCompany.companySelected == '') {
                        this.formpass = false;
                        this.err_companyselect = true;
                    }else{
                        this.err_companyselect = false;
                    }

                    if (this.newcampaign.asec5_9_1) {
                        if (this.tags.keywordlist.length == 0) {
                            this.formpass = false;
                            $('#err_asec5_10').show();
                        }else{
                            $('#err_asec5_10').hide();
                        }
                    }

                    if ((this.newcampaign.asec5_4_0_0 === false && this.newcampaign.asec5_4_0_1 === false && this.newcampaign.asec5_4_0_2 === false && this.newcampaign.asec5_4_0_3 === false) || this.tags.keywordlist.length == 0) {
                        this.formpass = false;
                        $('#err_asec5_10').show();
                    }else if (this.newcampaign.asec5_4_0_1 && this.selects.state.length == 0 || this.tags.keywordlist.length == 0) {
                        this.formpass = false;
                        $('#err_asec5_10').show();
                    }else{
                        $('#err_asec5_10').hide();
                    }

                    /** VALIDATE ZIP CODE IF SELECTED SHOULD BE HAVE MINIMUM 1 zip code and MAX 50 zipcode*/
                    if (this.newcampaign.asec5_4_0_3) {
                        //err_locator_zip
                        if (this.newcampaign.asec5_3 != '') {
                            if (this.newcampaign.asec5_3.split('\n').length > 50) {
                                this.formpass = false;
                                $('#err_locator_zip').addClass("errwarning");
                            }else{
                                $('#err_locator_zip').removeClass("errwarning");
                            }
                        }else{
                            this.formpass = false;
                            $('#err_locator_zip').addClass("errwarning");
                        }
                    }
                    /** VALIDATE ZIP CODE IF SELECTED SHOULD BE HAVE MINIMUM 1 zip code and MAX 50 zipcode*/

                    this.$refs.form5.validate().then(res => {
                        if (!res || !this.formpass) {
                            return false;
                        }
                    });
                    
                    if (this.formpass == false) {
                        this.$notify({
                            type: 'primary',
                            message: 'Please check the required fields.',
                            icon: 'fas fa-bug'
                        }); 
                        return false;
                    }

                    this.newcampaign.asec5_4 = this.selects.state;
                    this.newcampaign.asec5_4_1 = '';
                    this.newcampaign.asec5_10 = this.tags.keywordlist;
                    this.newcampaign.asec5_10_1 = this.tags.keywordlistContextual;
                    this.newcampaign.asec5_4_2 = this.selectedCity;

                    if(this.newcampaign.asec5_4_2.length > 0){
                        this.newcampaign.asec5_4_0_2 = true
                    }
                    
                    let oriStartDateCampaign = this.$moment(this.StartDateCampaign).format('YYYY-MM-DD') + " 00:00:00";
                    let oriEndDateCampaign = this.$moment(this.EndDateCampaign).format('YYYY-MM-DD') + " 23:59:59";

                    this.StartDateCampaign =  this.format_date(this.$moment(this.StartDateCampaign).format('YYYY-MM-DD') + " 00:00:00",true,false);
                    this.EndDateCampaign =  this.format_date(this.$moment(this.EndDateCampaign).format('YYYY-MM-DD') + " 23:59:59",true,false);

                    this.newcampaign.startdatecampaign = this.StartDateCampaign;
                    this.newcampaign.enddatecampaign = this.EndDateCampaign;
                    
                    /** VALIDATE QUESTIONNAIRE FORM */

                    if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '')){
                        this.selectsGroupCompany.companyGroupID = localStorage.getItem('companyGroupSelected');
                    }else{
                        this.selectsGroupCompany.companyGroupID = this.ClientUserID;
                    }
                    //HIDE PHONE WILL BE DEFAULT WHEN ADD CLIENT
                    var hidePhone = 'T';
                    if (this.checkboxes.hide_phone) {
                        hidePhone = 'T';
                    }

                    this.popProcessingTxt = "Please wait, adding new campaign ....";
                    $('#processingArea').addClass('disabled-area');
                    $('#popProcessing').show();
                   
                    $('#btnSave' + id).attr('disabled',true);
                    $('#btnSave' + id).html('Processing...');
                    /** CREATE CLIENT */
                    this.$store.dispatch('CreateLeadsPeekClient', {
                        companyID: this.companyID,
                        userID:this.ClientUserID,
                        companyName: this.ClientCompanyName,
                        reportType: this.radios.reportType,
                        reportSentTo: this.ClientReportSentTo,
                        adminNotifyTo: this.selectsAdministrator.administratorSelected,
                        leadsAmountNotification: this.ClientPerLead,
                        leadspeekType: 'enhance',
                        companyGroupID: this.selectsGroupCompany.companyGroupID,
                        clientOrganizationID: this.ClientOrganizationID,
                        clientCampaignID: this.ClientCampaignID,
                        clientHidePhone: hidePhone,
                        campaignName: this.ClientCampaignName,
                        urlCode: this.ClientUrlCode,
                        urlCodeThankyou: this.ClientUrlCodeThankyou,
                        answers: this.newcampaign,
                        startdatecampaign: this.StartDateCampaign,
                        enddatecampaign: this.EndDateCampaign,
                        oristartdatecampaign: oriStartDateCampaign,
                        orienddatecampaign: oriEndDateCampaign,
                        phoneenabled: this.checkboxes.phoneenabled,
                        homeaddressenabled: this.checkboxes.homeaddressenabled,
                        requireemailaddress: this.checkboxes.requireemailaddress,
                        reidentificationtype: this.Reidentificationtime,
                        locationtarget: this.radios.locationTarget,
                        timezone: this.$global.clientTimezone,
                        applyreidentificationall: this.checkboxes.ApplyReidentificationToAll,
                        idSys: this.$global.idsys
                    }).then(response => {
                        //console.log(response);
                        //console.log(response[0]);
                        this.modals.campaign = false
                        if (typeof(response.result) != 'undefined' && response.result == 'failed') {
                            $('#processingArea').removeClass('disabled-area');
                            $('#popProcessing').hide();
                    
                            $('#btnSave' + id).attr('disabled',false);
                            $('#btnSave' + id).html('Save'); 

                            this.$notify({
                                type: 'primary',
                                message: response.message,
                                icon: 'fas fa-bug'
                            }); 
                            return;
                        }else {
                        
                            response = this.AttachedAdminNotify(response);
                            this.tableData.push(response[(response.length-1)]);
                            this.initialSearchFuse();
                            this.ClearClientForm();
                            this.resetDefaultCampaigndate();
                            $('#showAddEditClient' + id).collapse('hide');

                            $('#processingArea').removeClass('disabled-area');
                            $('#popProcessing').hide();

                            $('#btnSave' + id).attr('disabled',false);
                            $('#btnSave' + id).html('Save'); 

                            this.$notify({
                                type: 'success',
                                message: 'Data has been added successfully',
                                icon: 'far fa-save'
                            });  

                            this.GetClientList(this.currSortBy,this.currOrderBy)
                        }
                    },error => {
                        this.modals.campaign = false
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();

                        $('#btnSave' + id).attr('disabled',false);
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
            }else{
            
                if ((id.report_type != '' && id.email != '') && (typeof id.report_type != 'undefined' && typeof id.email != 'undefined')) {
                    
                    var _formpass = true;
                    $('#err_startendcamp' + id.id).hide();

                    if (id.campaign_name == "") {
                        _formpass = false;
                        $('#err_campaignname' + id.id).show();
                    }else{
                        $('#err_campaignname' + id.id).hide();
                    }
                
                    /** CHECK START AND END DATE CAMPAIGN */
                        let todaydate = this.$moment().format('YYYYMMDD');

                        let startdatecampaign = this.$moment(id.campaign_startdate).format('YYYYMMDD');
                        let enddatecampaign = this.$moment(id.ori_campaign_enddate).format('YYYYMMDD');
                        // never execute
                        if (false) {
                            if (enddatecampaign <= startdatecampaign) {
                                //$('#ErrStartEndCampaign' + id.id).html('* Campaign end date must be on or after start date');
                                $('#ErrStartEndCampaign' + id.id).html('* Campaign end date must be after start / today date');
                                $('#err_startendcamp' + id.id).show();
                                _formpass = false;
                            }else if (enddatecampaign <= todaydate) {
                                //$('#ErrStartEndCampaign' + id.id).html('* Campaign end date must be on or after today date');
                                $('#ErrStartEndCampaign' + id.id).html('* Campaign end date must be after today date');
                                $('#err_startendcamp' + id.id).show();
                                _formpass = false;
                            }else{
                                if (id.campaign_startdate == "" ||id.campaign_startdate == null || id.campaign_enddate == "" || id.campaign_enddate == null) {
                                    $('#ErrStartEndCampaign' + id.id).html('* Please fill the date when campaign start and end');
                                    _formpass = false;
                                    $('#err_startendcamp' + id.id).show();
                                }else{
                                    $('#err_startendcamp' + id.id).hide();
                                }
                            }
                        }

                    /** CHECK START AND END DATE CAMPAIGN */

                    if (id.leadspeek_locator_keyword.length == 0) {
                        _formpass = false;
                        $('#err_asec5_10' + id.id).show();
                    }else{
                        $('#err_asec5_10' + id.id).hide();
                    }

                    // Check target locations
                    let selectedCount = 0;
                    if (this.selectedRowData.target_nt) selectedCount++;
                    if (this.selectedRowData.target_state) selectedCount++;
                    if (this.selectedRowData.target_zip) selectedCount++;

                    if (selectedCount === 0 ) {
                        this.$notify({
                            type: 'danger',
                            message: 'You must select at least one target locations.',
                            icon: 'fas fa-exclamation-circle'
                        })
                        return;
                    } else if (selectedCount > 1){
                        this.$notify({
                            type: 'danger',
                            message: 'You can only select one target locations.',
                            icon: 'fas fa-exclamation-circle'
                        });
                        return;
                    }
                    // Check target locations

                    // VALIDATION STATES > 5
                    if(id.leadspeek_locator_state.length > 5){
                        this.$notify({
                            type: 'danger',
                            message: 'A maximum of 5 states may be selected. Please reduce the number of choices.',
                            icon: 'fas fa-exclamation-circle'
                        })

                        return;
                    }
                    // VALIDATION STATES > 5

                    // VALIDATION CITY > 10
                    if(id.leadspeek_locator_city.length > 10){
                        this.$notify({
                            type: 'danger',
                            message: 'A maximum of 10 cities may be selected. Please reduce the number of choices.',
                            icon: 'fas fa-exclamation-circle'
                        })

                        return;
                    }
                    // VALIDATION CITY > 10

                    if ((id.target_nt === false && id.target_state === false && id.target_city === false && id.target_zip === false) || id.leadspeek_locator_keyword.length == 0) {
                        _formpass = false;
                        $('#err_asec5_10' + id.id).show();
                    }else if (id.target_state && id.leadspeek_locator_state.length == 0 || id.leadspeek_locator_keyword.length == 0) {
                       _formpass = false;
                        $('#err_asec5_10' + id.id).show();
                    }else{
                        $('#err_asec5_10' + id.id).hide();
                    }

                    /** VALIDATE ZIP CODE IF SELECTED SHOULD BE HAVE MINIMUM 1 zip code and MAX 50 zipcode*/
                    if (id.target_zip) {
                        //err_locator_zip
                        if (id.leadspeek_locator_zip != '') {
                            if (id.leadspeek_locator_zip.split('\n').length > 50) {
                                _formpass = false;
                                $('#err_locator_zip' + id.id).addClass("errwarning");
                            }else{
                                $('#err_locator_zip' + id.id).removeClass("errwarning");
                            }
                        }else{
                            _formpass = false;
                            $('#err_locator_zip' + id.id).addClass("errwarning");
                        }
                    }
                    /** VALIDATE ZIP CODE IF SELECTED SHOULD BE HAVE MINIMUM 1 zip code and MAX 50 zipcode*/
                    
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

                    $('#btnSave' + id.id).attr('disabled',true);
                    $('#btnSave' + id.id).html('Processing...'); 

                    var _locatorrequire = 'FirstName,LastName,MailingAddress,Phone';
           
                    _locatorrequire = _locatorrequire.replace(/,\s*$/, "");

                    let oriStartDate_Campaign = this.$moment(id.campaign_startdate).format('YYYY-MM-DD') + " 00:00:00";
                    let oriEndDate_Campaign = this.$moment(id.ori_campaign_enddate).format('YYYY-MM-DD') + " 23:59:59";
                    let StartDate_Campaign =  this.format_date(this.$moment(id.campaign_startdate).format('YYYY-MM-DD') + " 00:00:00",true,false);
                    let EndDate_Campaign = this.format_date(this.$moment(id.ori_campaign_enddate).format('YYYY-MM-DD') + " 23:59:59",true,false);

                    this.updateDataClient(id);
                    
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
                        clientOrganizationID: id.clientorganizationid,
                        clientCampaignID: id.clientcampaignsid,
                        clientHidePhone: id.hide_phone,
                        campaignName: id.campaign_name,
                        urlCode: id.url_code,
                        urlCodeThankyou: id.url_code_thankyou,
    
                        locatorzip: id.leadspeek_locator_zip,
                        locatordesc: id.leadspeek_locator_desc,   
                        locatorkeyword: id.leadspeek_locator_keyword,
                        locatorkeywordcontextual: id.leadspeek_locator_keyword_contextual,
                        locatorstate: id.leadspeek_locator_state,
                        locatorrequire: _locatorrequire,
                        locatorcity: id.leadspeek_locator_city,
                        sificampaign: id.clientcampaignsid,
                        sifiorganizationid: id.clientorganizationid,
                        startdatecampaign: StartDate_Campaign,
                        enddatecampaign: EndDate_Campaign,
                        oristartdatecampaign: oriStartDate_Campaign,
                        orienddatecampaign: oriEndDate_Campaign,

                        nationaltargeting: id.target_nt,
                        locationtarget: this.radios.locationTarget,
                        phoneenabled: id.phoneenabled,
                        homeaddressenabled: id.homeaddressenabled,
                        requireemailaddress: id.require_email,
                        reidentificationtype: id.reidentification_type,
                        timezone: this.$global.clientTimezone,
                        applyreidentificationall: id.applyreidentificationall,
                    }).then(response => {
                        //console.log(response[0]);
                        this.adjustmentDataClient(id,response[0]);
                       
                        // this.$refs.tableData.toggleRowExpansion(id);
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();

                        $('#btnSave' + id.id).attr('disabled',false);
                        $('#btnSave' + id.id).html('Save');

                        this.$notify({
                            type: 'success',
                            message: 'Data has been updated successfully',
                            icon: 'far fa-save'
                        });  
                        this.modals.campaignEdit = false
                        //this.ClearClientFormEdit(id);

                        if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '') && localStorage.getItem('companyGroupSelected') != id.group_company_id) {
                             this.deleteRow(id);
                        }

                        if (id.leadspeek_type != 'locator') {
                             this.deleteRow(id);
                        }

                        this.GetClientList(this.currSortBy,this.currOrderBy)
                    },error => {
                        $('#processingArea').removeClass('disabled-area');
                        $('#popProcessing').hide();
                        
                        $('#btnSave' + id.id).attr('disabled',false);
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
        adjustmentDataClient(id,response) {
            id.leadspeek_locator_state = response['leadspeek_locator_state'];
            id.leadspeek_locator_state_simplifi = response['leadspeek_locator_state_simplifi'];
            id.leadspeek_locator_city = response['leadspeek_locator_city'];
            id.leadspeek_locator_city_simplifi = response['leadspeek_locator_city_simplifi'];
            id.national_targeting = response['national_targeting'];
        },
        updateDataClient(id) {
            if (id.target_nt == true) {
                id.target_state = false;
                id.target_city = false;
                id.target_zip = false;
                //this.radios.locationTarget = "Focus";

                id.leadspeek_locator_state = "";
                id.leadspeek_locator_state_simplifi = "";
                id.selects_state = [];
                id.leadspeek_locator_city = "";
                id.leadspeek_locator_city_simplifi = "";
                id.selects_city = [];
                id.leadspeek_locator_zip = "";
            }
        
            if (id.target_state || id.target_city || id.target_zip) {
                id.target_nt = false;
            }

            if(id.target_state){
                id.leadspeek_locator_zip = "";
            }

            if(id.target_zip){
                id.leadspeek_locator_state = "";
                id.leadspeek_locator_state_simplifi = "";
                id.leadspeek_locator_city = "";
                id.leadspeek_locator_city_simplifi = "";
                id.selects_state = [];
                id.selects_city = [];
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
                }else{
                    this.GoogleConnectTrue = false;
                    this.GoogleConnectFalse = true;
                }
            },error => {
                
            });
        },
        AttachedAdminNotify(response) {
            /** SET ADMIN NOTIFY */
                for(let i=0;i<response.length;i++) {
                    var tmp = "";
                    var tmpArray = Array();
                    tmp = response[i]['admin_notify_to'];
                    tmpArray = tmp.split(",");
                    for(let k=0;k<tmpArray.length;k++) {
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
                userType:'user',
            }).then(response => {
                let _tmpdefaultadmin = Array();
                $.each(response,function(key, value) {
                     if(value['defaultadmin'] == 'T') {
                        _tmpdefaultadmin.push(value['id']);
                     }
                });
                this.tmpdefaultadmin = response;
                this.selectsAdministrator.administratorSelected = _tmpdefaultadmin;
                this.selectsAdministrator.administratorList = response
            },error => {
                
            });
        },
        GetCompanyList() {
            this.fetchingCampaignData = true;
            // $('.el-select-dropdown__empty').html('Loading data...');
            var _groupCompanyID = '';

            if (this.userType == 'client') {
                const userData = this.$store.getters.userData;
                _groupCompanyID = userData.id;
            }
           
            /** GET CLIENT LIST */
            this.$store.dispatch('GetClientList', {
                companyID: this.companyID,
                idsys: this.$global.idsys,
                userType:'client',
                userModule: 'LeadsPeek',
                groupCompanyID: _groupCompanyID,
            }).then(response => {
                //console.log(response);
                if(response.length == 0) {
                    $('.el-select-dropdown__empty').html('No new client');
                }
                this.selectsCompany.companyList = response
                if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '')){
                    this.onCompanyChange(localStorage.getItem('companyGroupSelected'));
                    this.CompanyNamedisabled = true;
                }
            },error => {
                this.fetchingCampaignData = false;
            });
            /** GET CLIENT LIST */            
        },
        sortdynamic(column,order,prop) { 
            this.currSortBy = prop;
            this.currOrderBy = order;
            this.GetClientList(column,column);
        },
        GetClientList(sortby,order,searchkey,isFilter) {
            this.fetchingCampaignData = true;
            var _sortby = '';
            var _order = '';
            var _searchkey = '';

            if (typeof(sortby) != 'undefined') {
                _sortby = sortby;
            }
            if (typeof(order) != 'undefined') {
                _order = order;
            }
            if (this.searchQuery != '') {
                _searchkey = this.searchQuery;
               
            }
           
            //console.log(_sortby + ' | ' + _order);
            var _groupCompanyID = '';
            if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '')){
                _groupCompanyID = localStorage.getItem('companyGroupSelected');
            }
            /** GET CLIENT LIST */
            this.tableData = [];
            $('.el-table__empty-text').html('<i class="fas fa-spinner fa-pulse fa-2x d-block"></i>Loading data...');
            this.$store.dispatch('GetLeadsPeekClientList', {
                companyID: this.companyID,
                leadspeekType: 'enhance',
                groupCompanyID: _groupCompanyID,
                sortby: _sortby,
                order: _order,
                searchkey: _searchkey,
                page:this.pagination.currentPage,
                view: 'campaign',
                campaignStatus: this.filterCampaignStatus,
            }).then(response => {
                //console.log(response.length);
                this.pagination.currentPage = response.current_page? response.current_page : 1
               this.pagination.total = response.total ?response.total : 0
               this.pagination.lastPage = response.last_page ? response.last_page : 2
               this.pagination.from = response.from ? response.from : 0
               this.pagination.to = response.to ? response.to : 0
                if(response.data.length == 0) {
                    $('.el-table__empty-text').html('No Data');
                }
                //console.log(response);
                this.tableData = this.AttachedAdminNotify(response.data);
                //this.searchedData = this.tableData;
                //this.searchQuery = "";
                this.initialSearchFuse();
                this.fetchingCampaignData = false;
            },error => {
                this.fetchingCampaignData = false;
            });
            /** GET CLIENT LIST */
        },
        GetClientListAll() {
            //console.log(_sortby + ' | ' + _order);
            var _groupCompanyID = '';
            if ((localStorage.getItem('companyGroupSelected') != null && localStorage.getItem('companyGroupSelected') != '')){
                _groupCompanyID = localStorage.getItem('companyGroupSelected');
            }
            this.selectsCompany.campaignList = [];
           
            this.$store.dispatch('GetLeadsPeekClientList', {
                companyID: this.companyID,
                leadspeekType: 'enhance',
                groupCompanyID: _groupCompanyID,
             
                view: 'campaign',
             
            }).then(response => {
                //console.log(response.length);
                
                this.selectsCompany.campaignList = response;
                // this.selectsCompany.campaignSelected = response.data[0].id;
                // this.ClientActiveID = response.data[0].id;
            },error => {
                
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
                    this.selectsGroupCompany.companyGroupList.unshift({'id':0,'group_name':'none'});
                }
            },error => {
                
            });
            /** GET COMPANY GROUP */
        },

        initialSearchFuse() {
            // Fuse search initialization.
            // this.fuseSearch = new Fuse(this.tableData, {
            //     keys: ['company_name','campaign_name','leadspeek_api_id','last_lead_added'],
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
            this.modals.embededcode = true;
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
                                this.GetClientList(this.currSortBy,this.currOrderBy); 
                                this.$notify({
                                    type: 'success',
                                    message: 'This campaign will activated',
                                    icon: 'tim-icons icon-bell-55'
                                });  
                            }else{
                                row.active_user = 'F';
                                row.disabled = 'T';
                                $('#userstartstop' + index).removeClass('fas fa-stop green').addClass('fas fa-stop gray');
                                this.GetClientList(this.currSortBy,this.currOrderBy); 
                                this.$notify({
                                    type: 'success',
                                    message: 'Campaign successfully stopped.',
                                    icon: 'tim-icons icon-bell-55'
                                });  
                            }
                        },error => {
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
                    // this.locatorlead.lead_FirstName_LastName = (response.data.locatorlead.FirstName_LastName != '' && response.data.locatorlead.FirstName_LastName != '0')?this.formatPrice(response.data.locatorlead.FirstName_LastName):'1.50';
                    // this.locatorlead.lead_FirstName_LastName_MailingAddress  = (response.data.locatorlead.FirstName_LastName_MailingAddress != '' && response.data.locatorlead.FirstName_LastName_MailingAddress != '0')?this.formatPrice(response.data.locatorlead.FirstName_LastName_MailingAddress):'2.00';
                    // this.locatorlead.lead_FirstName_LastName_MailingAddress_Phone = (response.data.locatorlead.FirstName_LastName_MailingAddress_Phone != '' && response.data.locatorlead.FirstName_LastName_MailingAddress_Phone != '0')?this.formatPrice(response.data.locatorlead.FirstName_LastName_MailingAddress_Phone):'3.00';

                    this.defaultCostCampaign.enhance.Monthly.EnhancePlatformFee = (response.data.enhance.Monthly.EnhancePlatformFee != '')?response.data.enhance.Monthly.EnhancePlatformFee:'0';
                    this.defaultCostCampaign.enhance.Monthly.EnhanceCostperlead = (response.data.enhance.Monthly.EnhanceCostperlead != '')?response.data.enhance.Monthly.EnhanceCostperlead:'0';
                    this.defaultCostCampaign.enhance.Monthly.EnhanceMinCostMonth = (response.data.enhance.Monthly.EnhanceMinCostMonth != '')?response.data.enhance.Monthly.EnhanceMinCostMonth:'0';

                    this.defaultCostCampaign.enhance.Weekly.EnhancePlatformFee = (response.data.enhance.Weekly.EnhancePlatformFee != '')?response.data.enhance.Weekly.EnhancePlatformFee:'0';
                    this.defaultCostCampaign.enhance.Weekly.EnhanceCostperlead = (response.data.enhance.Weekly.EnhanceCostperlead != '')?response.data.enhance.Weekly.EnhanceCostperlead:'0';
                    this.defaultCostCampaign.enhance.Weekly.EnhanceMinCostMonth = (response.data.enhance.Weekly.EnhanceMinCostMonth != '')?response.data.enhance.Weekly.EnhanceMinCostMonth:'0';

                    this.defaultCostCampaign.enhance.OneTime.EnhancePlatformFee = (response.data.enhance.OneTime.EnhancePlatformFee != '')?response.data.enhance.OneTime.EnhancePlatformFee:'0';
                    this.defaultCostCampaign.enhance.OneTime.EnhanceCostperlead = (response.data.enhance.OneTime.EnhanceCostperlead != '')?response.data.enhance.OneTime.EnhanceCostperlead:'0';
                    this.defaultCostCampaign.enhance.OneTime.EnhanceMinCostMonth = (response.data.enhance.OneTime.EnhanceMinCostMonth != '')?response.data.enhance.OneTime.EnhanceMinCostMonth:'0';

                    this.defaultCostCampaign.enhance.Prepaid.EnhancePlatformFee = (response.data.enhance.Prepaid.EnhancePlatformFee != '')?response.data.enhance.Prepaid.EnhancePlatformFee:'0';
                    this.defaultCostCampaign.enhance.Prepaid.EnhanceCostperlead = (response.data.enhance.Prepaid.EnhanceCostperlead != '')?response.data.enhance.Prepaid.EnhanceCostperlead:'0';
                    this.defaultCostCampaign.enhance.Prepaid.EnhanceMinCostMonth = (response.data.enhance.Prepaid.EnhanceMinCostMonth != '')?response.data.enhance.Prepaid.EnhanceMinCostMonth:'0';

                }else{
                    // this.locatorlead.lead_FirstName_LastName = '1.50';
                    // this.locatorlead.lead_FirstName_LastName_MailingAddress = '2.00';
                    // this.locatorlead.lead_FirstName_LastName_MailingAddress_Phone = '3.00';
                }
                
            },error => {
                    
            });
        },
        resetDefaultCampaigndate() {
            const ServerlocalTime = this.$moment();
            const chicagoTime = ServerlocalTime.tz('America/New_York');

            var todayDate = this.$moment();
            this.StartDateCampaign = chicagoTime.format('YYYY-MM-DD');
            this.EndDateCampaign = todayDate.add(6,'months').format('YYYY-MM-DD');
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
        getStopWords(){
            const userData = this.$store.getters.userData;

            this.$store.dispatch('getStopWords', {
                company_root_id: userData.company_root_id,
            }).then(response => {
                this.stopWords = response.data.stopWords
            }, error => {   
                console.error(error);
            })
        },
        onImportCampaignSelectedChange(campaign){
            this.newcampaign.asec5_3 = ''
            this.selects.state = []
            this.newcampaign.asec5_4_0_3 = false;
            this.newcampaign.asec5_4_0_0 = false;
            this.newcampaign.asec5_4_0_1 = false;
            let selected = this.selectsCompany.campaignList.filter(item => item.id === campaign)[0]
            this.ClientCampaignName = selected.campaign_name
            
            if (selected.national_targeting == 'T') {
                this.newcampaign.asec5_4_0_0 = true;
            }else{
               
                if (selected.leadspeek_locator_zip != "" && selected.leadspeek_locator_zip != null) {
                    this.newcampaign.asec5_4_0_3 = true;
                    this.newcampaign.asec5_3 = this.replaceCommaLine(selected.leadspeek_locator_zip);
                }

                if (selected.leadspeek_locator_state != "" && selected.leadspeek_locator_state != null) {
                    this.newcampaign.asec5_4_0_1 = true;
                    
                    let _ClientLocatorState =  selected.leadspeek_locator_state.split(",");
                    let _ClientLocatorStateSifi = selected.leadspeek_locator_state_simplifi.split(",");

                    for(var i=0;i<_ClientLocatorState.length;i++) {
                        if (_ClientLocatorStateSifi[i] != '' &&  _ClientLocatorState[i] != '') {
                            this.selects.state.push(_ClientLocatorStateSifi[i] + '|' + _ClientLocatorState[i]);
                        }
                    }

                }else{
                  
                }
               
          

            }

            
            if (selected.leadspeek_locator_keyword != "" && selected.leadspeek_locator_keyword != null) {
                this.tags.keywordlistbulk = selected.leadspeek_locator_keyword.split(",");
                this.tags.keywordlist = selected.leadspeek_locator_keyword.split(",");

            }
            // console.log(selected.admin_notify_to,'admins notify to ')
            // if(selected.admin_notify_to != '' && selected.admin_notify_to != null){
            //     this.selectsAdministrator.administratorSelected = selected.admin_notify_to
            // }
            // if (row.leadspeek_locator_keyword_contextual != null && row.leadspeek_locator_keyword_contextual != "") {
            //     row.leadspeek_locator_keyword_contextual = row.leadspeek_locator_keyword_contextual.split(",");
            // }
           

            if(selected.reidentification_type !== '' && selected.reidentification_type == null){
                this.Reidentificationtime = selected.reidentification_type
            }
            this.StartDateCampaign = (selected.campaign_startdate != '0000-00-00')?selected.campaign_startdate:'';
            this.EndDateCampaign = (selected.campaign_enddate != '0000-00-00')?selected.campaign_enddate:'';
            
            
            if (selected.homeaddressenabled == 'T') {
                this.checkboxes.homeaddressenabled = true;
            }else{
                this.checkboxes.homeaddressenabled= false;
            }

            if (selected.phoneenabled == 'T') {
                this.checkboxes.phoneenabled = true;
            }else{
                this.checkboxes.phoneenabled = false;
            }

            if (selected.require_email == 'T') {
                this.checkboxes.requireemailaddress = true;
            }else{
                this.checkboxes.requireemailaddress = false;
            }

            if (selected.applyreidentificationall == 'T') {
                this.checkboxes.ApplyReidentificationToAll = true;
            } else {
                this.checkboxes.ApplyReidentificationToAll = false;
            }
            
            
        },
        getLocationBigDbm(){
            this.isLoadingOptionsCity = true

            return this.$store.dispatch('getLocationBigDbm', {
                type: 'city',
                param: this.stateCode.join(''),
                limit: this.limitOptionsCity,
                search: this.searchCity,
            }).then(response => {
                this.optionsCity = response.data
                this.totalCity = response.total
                this.isLoadingOptionsCity = false
            }, error => {
                console.error(error);
                this.isLoadingOptionsCity = false
            })
        },
        onStateChange(selectedState, row){
            const isFetchCity = selectedState.length == 1

            if(selectedState.length > 5){
                selectedState.splice(5);

                this.$notify({
                    type: 'danger',
                    message: 'A maximum of 5 states may be selected.',
                    icon: 'fas fa-exclamation-circle'
                })
            }

            if(isFetchCity){
                this.stateCode = selectedState;
                this.limitOptionsCity = 50;
                this.getLocationBigDbm();
            } else {
                this.selectedCity = '';

                if(row){
                    row.leadspeek_locator_city = ''
                }
            }
        },
        onCityChange(selectedCity){
            if(selectedCity.length > 10){
                selectedCity.splice(10);

                this.$notify({
                    type: 'danger',
                    message: 'A maximum of 10 cities may be selected.',
                    icon: 'fas fa-exclamation-circle'
                })
            }
        },
        onSearchCity(query){
            this.searchCity = query
            this.limitOptionsCity = 50
            this.getLocationBigDbm()
            this.scrollToTop()
        },
        onDropdownVisible(visible) {
            if (visible) {
                this.$nextTick(() => {
                    const dropdown = this.$refs.select.$refs.popper.$el.querySelector('.el-select-dropdown__wrap');
                    if (dropdown) {
                        dropdown.addEventListener('scroll', this.handleScroll);
                    } else {
                        console.error('Dropdown not found');
                    }
                });
            } else {
                this.searchCity = ''
                this.getLocationBigDbm()
            }
        },
        handleScroll(event) {
            const dropdown = event.target;
            if (dropdown.scrollTop + dropdown.clientHeight >= dropdown.scrollHeight - 1) {
                
                if(this.limitOptionsCity > this.totalCity){
                    return;
                } else {
                    this.limitOptionsCity += 50
                    this.getLocationBigDbm()
                }

            }
        },
        scrollToTop() {
            this.$nextTick(() => {
                const dropdown = this.$refs.select.$refs.popper.$el.querySelector('.el-select-dropdown__wrap');
                if (dropdown) {
                    dropdown.scrollTop = 0;
                } else {
                    console.error('Dropdown not found');
                }
            });
        },
        onPasteEditZipCodes(event, row){
            event.preventDefault();
            let zipCodeRow = row

            const pastedText = (event.clipboardData || window.clipboardData).getData('text');

            let modifiedText = pastedText.replace(/,/g, '\n').replace(/\s+/g, '\n').trim();

            const input = event.target;
            const cursorPos = input.selectionStart;
            const selectionEnd = input.selectionEnd;

            // Check if any text is selected
            if (cursorPos !== selectionEnd) {
                // If there is text selected, replace the selected text with the pasted one
                const textBeforeSelection = input.value.substring(0, cursorPos);
                const textAfterSelection = input.value.substring(selectionEnd);
                zipCodeRow = textBeforeSelection + modifiedText + textAfterSelection;
            } else {
                // If no text is selected, add text at the cursor position
                const textBeforeCursor = input.value.substring(0, cursorPos);
                const textAfterCursor = input.value.substring(cursorPos);
                zipCodeRow = textBeforeCursor + modifiedText + textAfterCursor;
            }

            // Removes consecutive blank lines if any
            zipCodeRow = zipCodeRow.replace(/\n{2,}/g, '\n').trim();

            this.selectedRowData.leadspeek_locator_zip = zipCodeRow
        }
    },

    mounted() {
        const userData = this.$store.getters.userData;
        if (userData.user_type == 'client') {
            this.companyID = userData.company_parent;
        }else{
            this.companyID = userData.company_id;
        }
        this.selectsPaymentTerm.PaymentTerm = this.$global.rootpaymentterm;
        this.leadlocatorname = userData.leadlocatorname;
        this.leadlocalname = userData.leadlocalname;
        if (this.$global.globalviewmode) {
            this.userTypeOri = userData.user_type_ori;
        }
        const protectAccessMenu = () => {
            if(userData.user_type == 'client'){
                const sidebar = this.$global.clientsidebar

                if (!sidebar['enhance']){
                    this.$router.push({ name: 'Profile Setup' });
                }
                
            } else if (userData.user_type == 'userdownline'){
                const sidebarAgency = this.$global.agency_side_menu && this.$global.agency_side_menu.find(menu => menu.type == 'enhance')

                if(sidebarAgency && !sidebarAgency.status){
                    this.$router.push({ name: 'Profile Setup' });
                }
            }
        }

        protectAccessMenu()
        this.userType = userData.user_type;
        this.getStopWords();
        this.checkGoogleConnect();
        this.GetCompanyList();
        this.GetAdministratorList();
        //this.GetClientList();
        CHECK_GROUPCOMPANY = setInterval(() => {
            if ((localStorage.getItem('companyGroupSelected') != null) && this.selectsAdministrator.administratorList.length != 0){
                clearInterval(CHECK_GROUPCOMPANY);
                this.GetClientList();
                this.GetClientListAll()
            }
        }, 1000);
        this.getStateList();  
        this.initial_default_price();
        this.resetDefaultCampaigndate();
        this.reset();
        //this.GetCompanyGroup();
    },

    watch: {
        'modals.whitelist': function(newValue) {
            if(!newValue) {
                this.supressionProgress = [];
                clearTimeout(this.supressionTimeout);
                clearInterval(this.supressionInterval);
            }
        },

        prepaidType(newValue, oldvalue) {
            if(this.totalLeads.oneTime < 50) {
                this.totalLeads.oneTime = 50;
                this.err_totalleads = '';
            }
            if(newValue === 'continual' && oldvalue === 'onetime') {
                this.profitcalculation();
            }
        },

        'modals.campaignEdit': function(newValue){
            if(!newValue){
                this.GetClientList(this.currSortBy,this.currOrderBy)
            }
        }
        /**
         * Searches through the table data by a given query.
         * NOTE: If you have a lot of data, it's recommended to do the search on the Server Side and only display the results here.
         * @param value of the query
         */
        // searchQuery(value) {
        //     if (value.length > 3) {
        //          this.GetClientList(this.currSortBy,this.currOrderBy,value);
        //     }
            
        // }
    },
    beforeDestory(){
        const dropdown = this.$refs.select.$refs.popper.$el.querySelector('.el-select-dropdown__wrap');
        if (dropdown) {
            dropdown.removeEventListener('scroll', this.handleScroll);
        }
    }
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
/* .el-table__expanded-cell {
    background-color: red;
} */
/* .clickable-rows .el-table, .el-table__expanded-cell {
    background-color:#1e1e2f;
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

.table .form-check label .form-check-sign::before, .el-table table .form-check label .form-check-sign::before, .table .form-check label .form-check-sign::after, .el-table table .form-check label .form-check-sign::after {
    top:3px;
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

.el-table__fixed-right::before, .el-table__fixed::before {
    background-color: transparent;
}

tr.hover-row > td {
    background-color: transparent !important;
}

.el-table__fixed-body-wrapper .el-table__row > td:not(.is-hidden)  {
    border-top: transparent !important;
}

.frmSetCost .input-group input[type=text], .frmSetCost input[type=text],.frmSetCost .input-group .input-group-prepend .input-group-text {
    color: #525f7f;
    border-color: #525f7f;
}
.el-date-editor .el-input__suffix-inner .el-input__icon{
  display: none;
}

.errwarning {
    color: #942434 !important;
    font-weight: bold !important;
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

@media (max-width: 991.98px) {
    .input__campaign__management {
        width: 100%;
    }
    .container__input__campaign__management {
        width: 100%;
    }
}

@media (max-width: 575.98px) { 
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
</style>