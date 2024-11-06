<template>
    <div>

        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <h2 style="font-size: 40px; font-weight: 700; line-height: 48px; " class="mb-0 mt-5">Client Management
                </h2>
            </div>
            <div class="col-sm-12 col-md-12 col-lg-12 pt-4">
                <p>On this page, you can add, edit, or remove your clients.<br />When a new client is added, the system
                    will email them directions for completing their profile, reporting preferences, and payment
                    settings.
                    <br /> Make sure to also add the client's campaigns under the campaign tab for each desired service.
                </p>
            </div>
            <!-- <div class="col-sm-12 col-md-12 col-lg-12 pt-4">
               <div style="display:inline-block">
                <a href="#" @click="modals.helpguide = true" style="display:inline-flex"><i class="far fa-play-circle" style="font-size:21px;padding-right:5px"></i> <span>here for more information.</span></a>
               </div>
            </div> -->
        </div>
        <div class="pt-3 pb-3">&nbsp;</div>

        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <card>
                    <div class="row">
                        <div class="col-12">
                            <div class="row" style="align-items: baseline;">
                            <div class="col-lg-5 col-md-5 col-sm-12">
                                <div>
                                    <base-input>
                                        <el-input type="search" class="mb-3 search-input input__client__management" clearable
                                            prefix-icon="el-icon-search" placeholder="Search Client" v-model="searchQuery"
                                            aria-controls="datatables" @keyup.native.enter="searchKeyWord"
                                            @clear="searchKeyWord">
                                        </el-input>
                                    </base-input>
                                </div>
                            </div>  
                            <div class="col-lg-7 col-md-7 col-sm-12" style="display: flex; justify-content: flex-end; align-items: center;">
                                <div style="padding-inline: 16px;">
                                    <el-dropdown ref="dropdown" @visible-change="handleVisibleChange">
                                        <span>
                                        <base-button class="m-0 ml-2" size="sm" style="height:40px">
                                            <i class="fa-solid fa-filter"></i> Filters
                                        </base-button>
                                        </span>

                                        <el-dropdown-menu slot="dropdown" style="width: 240px;"  :class="{ 'dropdown-hidden': !dropdownVisible }" @click.native.stop>
                                        <div class='ml-4 mr-4'>
                                            <el-collapse value="Card Status" style="border: none;">
                                            <el-collapse-item title="Card Status" name="Card Status">
                                                <div style="padding-left:8px;">
                                                <el-checkbox v-model="filters.cardStatus.active">Active</el-checkbox>
                                                <el-checkbox v-model="filters.cardStatus.inactive">Not Setup</el-checkbox>
                                                <el-checkbox v-model="filters.cardStatus.failed">Failed</el-checkbox>
                                                </div>
                                            </el-collapse-item>
                                            </el-collapse>
                                            <el-collapse value="Campaign Status" style="border: none;">
                                            <el-collapse-item title="Campaign Status" name="Campaign Status">
                                                <div style="padding-left:8px;">
                                                <el-checkbox v-model="filters.campaignStatus.active">Active</el-checkbox>
                                                <el-checkbox v-model="filters.campaignStatus.inactive">Inactive</el-checkbox>
                                                </div>
                                            </el-collapse-item>
                                            </el-collapse>
                                        </div>
                                        <div class='d-flex justify-content-end mr-4 mt-4'>
                                            <base-button @click="applyFilters" :simple="true" size="sm">
                                            Save
                                            </base-button>
                                        </div>
                                        </el-dropdown-menu>
                                    </el-dropdown>
                                </div>
                                <div>
                                    <base-button size="sm" style="height:40px" v-if="this.$global.settingMenuShow_create"
                                        @click="AddEditClient('')">
                                        <i class="fas fa-plus-circle"></i> Add Client
                                    </base-button>
                                </div>
                        </div>
                    </div>
                        </div>
                    </div>

                    <ValidationObserver v-slot="{ handleSubmit }">
                        <form autocomplete="off">
                            <input autocomplete="false" name="hidden" type="text" style="display:none;">
                            <div id="showAddEditClient" class="row collapse">
                                <div class="col-sm-12 col-md-12 col-lg-12 pt-2 pb-2">&nbsp;</div>

                                <div class="col-sm-4 col-md-4 col-lg-4 form-group has-label">
                                    <ValidationProvider name="Client Name" rules="required"
                                        v-slot="{ passed, failed, errors }">
                                        <base-input label="Client Name" type="text" placeholder="Input Client Name"
                                            addon-left-icon="fas fa-building" v-model="ClientCompanyName"
                                            :error="errors[0]"
                                            :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                        </base-input>
                                    </ValidationProvider>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <ValidationProvider name="Contact Name" rules="required"
                                        v-slot="{ passed, failed, errors }">
                                        <base-input label="Contact Name" type="text" placeholder="Input Contact Name"
                                            addon-left-icon="far fa-id-badge" v-model="ClientFullName"
                                            :error="errors[0]"
                                            :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                        </base-input>
                                    </ValidationProvider>
                                </div>
                                <div class="col-sm-4 col-md-4 col-lg-4">
                                    <ValidationProvider name="email" rules="required|email"
                                        v-slot="{ passed, failed, errors }">
                                        <base-input label="Email" type="email" placeholder="Input Client Email"
                                            addon-left-icon="fas fa-envelope" v-model="ClientEmail" :lowercase="true"
                                            :error="errors[0]"
                                            :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                        </base-input>
                                    </ValidationProvider>
                                </div>

                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <!-- <base-input
                                label="Phone Number"
                                type="text"
                                placeholder="Input Client Phone"
                                addon-left-icon="fas fa-phone-alt"
                                v-model="ClientPhone"
                                class="phonenum"
                                >
                            </base-input> -->
                                    <label>Phone Number</label>

                                    <VuePhoneNumberInput :defaultCountryCode="clientPhoneNumber.countryCode"
                                        v-model="clientPhoneNumber.number" @update="onPhoneUpdate" />
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-12 ">
                                    <base-checkbox value="T" v-model="disabledreceiveemail" class="pull-left">Disable
                                        all system emails to this client</base-checkbox>
                                </div>
                                <div class="col-sm-12 col-md-12 col-lg-12 ">
                                    <base-checkbox value="T" v-model="disabledaddcampaign" class="pull-left">Disable
                                        clients ability to add campaigns</base-checkbox>
                                </div>
                                <div class="col-sm-6 col-md-6 col-lg-6 form-group has-label" v-if="false">
                                    <base-input label="Domain Name:" type="text" placeholder="yourdomain.com"
                                        addon-left-icon="fas fa-globe-americas" v-model="ClientDomain">
                                    </base-input>
                                </div>

                                <div class="mt-2">
                                    <div class="col-sm-12 col-md-12 col-lg-12">
                                        <span class="" style="font-size: 0.80143rem;">Product(s) :</span>

                                        <base-checkbox
                                            v-for="(sidebar, key) in customsidebarleadmenu"
                                            :key="sidebar.url"
                                            class="mr-3"
                                            v-model="selectedModulesCreate[key]"
                                        >
                                        {{ sidebar.name }}
                                        </base-checkbox>
                                    </div>
                                </div>

                                <div class="col-sm-12 col-md-12 col-lg-12">
                                    <base-button size="sm" id="btnSave" name="btnSave"
                                        class="pull-right" style="height:40px" @click="ProcessAddEditClient('')">
                                        Save
                                    </base-button>
                                    <base-button size="sm" class="pull-right mr-4" style="height:40px"
                                        @click="CancelAddEditClient('')">
                                        Cancel
                                    </base-button>
                                </div>

                                <div class="col-sm-12 col-md-12 col-lg-12 pt-4 pb-4">&nbsp;</div>
                            </div>
                        </form>
                    </ValidationObserver>

                    <div class="row">
                        <div
                            class="col-12 d-flex justify-content-center justify-content-sm-between flex-wrap tab-table">
                            <el-table @expand-change="onExpandClick" :data="queriedData" @sort-change="sortdynamic" :row-class-name="tableRowClassName"
                                ref="tableData" :default-sort="{prop: 'company_name', order: 'ascending'}">
                                <template #empty>
                                    <i class="fas fa-spinner fa-pulse fa-2x d-block"></i>Loading data...
                                </template>
                                <el-table-column min-width="180" prop="company_name" sortable="custom"
                                    label="Client Name">
                                    <div slot-scope="props">
                                        {{props.row.company_name}}
                                    </div>
                                </el-table-column>
                                <el-table-column min-width="180" prop="full_name" sortable="custom"
                                    label="Contact Name">
                                    <div slot-scope="props">
                                        {{props.row.name}}
                                    </div>
                                </el-table-column>
                                <el-table-column min-width="180" prop="email" sortable="custom" label="E-mail">
                                    <div slot-scope="props">
                                        {{props.row.email}}
                                    </div>
                                </el-table-column>
                                <el-table-column min-width="120" prop="phone" sortable="custom" label="Phone">
                                    <div slot-scope="props">
                                        {{props.row.phonenum}}
                                    </div>
                                </el-table-column>
                                <el-table-column min-width="120" prop="created_at" sortable="custom" align="center"
                                    label="Created">
                                    <div slot-scope="props">
                                        {{props.row.created_at}}
                                    </div>
                                </el-table-column>
                                <el-table-column min-width="140" align="center" label="Metrics">
                                    <div slot-scope="props">
                                        <el-tooltip content="Card settings" effect="light" :open-delay="300"
                                            placement="top" v-if="props.row.manual_bill == 'F'">
                                            <base-button @click.native="handleCardSet(props.$index, props.row)"
                                                class="edit btn-link" type="warning" size="sm" icon>
                                                <i class="far fa-credit-card" :style="getCardStatus(props.row)"></i>
                                            </base-button>
                                        </el-tooltip>
                                        <div class="icons-container">
                                            <el-tooltip content="Active campaigns" effect="light" :open-delay="300"
                                                placement="top">
                                                <i class="iconcampaign cmpActive">{{props.row.campaign_active}}</i>
                                            </el-tooltip>
                                            <el-tooltip content="Paused campaigns" effect="light" :open-delay="300"
                                                placement="top">
                                                <i
                                                    class="iconcampaign cmpPauseStop">{{props.row.campaign_not_active}}</i>
                                            </el-tooltip>
                                        </div>
                                    </div>
                                </el-table-column>
                                <el-table-column min-width="140" align="center" label="Actions">
                                    <div slot-scope="props">

                                        <el-tooltip v-if="props.row.company_id !== null" effect="light"
                                            :open-delay="300" placement="top"
                                            content="Configure Client Integration Settings">
                                            <base-button class="edit btn-link" type="warning" size="sm" icon>
                                            </base-button>
                                            <i class="fas fa-plug"
                                                @click="handleIntegrationClick(props.$index, props.row)"></i>

                                        </el-tooltip>
                                        <el-tooltip content="Edit Client" effect="light" :open-delay="300"
                                            placement="top">

                                            <base-button @click.native="rowClicked(props.row)" class="edit btn-link"
                                                type="neutral" size="sm" icon>
                                                <i class="fa-solid fa-pen-to-square" style="color:gray"></i>
                                            </base-button>
                                        </el-tooltip>
                                        <el-tooltip content="Client Level Exclusion List" effect="light" :open-delay="300"
                                            placement="top" v-if="props.row.company_id !== null">
                                            <base-button @click.native="showWhitelist(props.$index, props.row)"
                                                class="edit btn-link" type="warning" size="sm" icon>
                                                <i class="fas fa-align-slash" style="color:gray"></i>
                                            </base-button>
                                        </el-tooltip>
                                        <el-tooltip content="Default Client Financials" effect="light" :open-delay="300"
                                            placement="top">
                                            <base-button @click.native="handlePriceSet(props.$index, props.row)"
                                                class="edit btn-link" type="warning" size="sm" icon>
                                                <i class="fa-solid fa-dollar-sign" style="color:green"></i>
                                            </base-button>
                                        </el-tooltip>
                                        <el-tooltip content="Remove Client" effect="light" :open-delay="300"
                                            placement="top">
                                            <!-- <base-button
                                                       
                                                        class="remove btn-link"
                                                        type="danger"
                                                        size="sm"
                                                        icon
                                                        >
                                                    </base-button> -->
                                            <i v-if="$global.settingMenuShow_delete"
                                                @click="handleDelete(props.$index, props.row)"
                                                class="fa-solid fa-circle-x"></i>
                                        </el-tooltip>
                                    </div>
                                </el-table-column>

                                <el-table-column min-width="100%" type="expand">
                                    <!-- START EDIT AREA -->
                                    <template slot-scope="scope">
                                        <ValidationObserver :ref="'frmuser' + scope.row.id" v-slot="{ handleSubmit }">
                                            <form :id="'frmuser' + scope.row"
                                                autocomplete="off">
                                                <input autocomplete="false" name="hidden2" type="text"
                                                    style="display:none;">
                                                <div class="row">
                                                    <div class="col-sm-4 col-md-4 col-lg-4 form-group has-label">
                                                        <ValidationProvider name="Client Name" rules="required"
                                                            v-slot="{ passed, failed, errors }">
                                                            <base-input label="Client Name" type="text"
                                                                placeholder="Input Client Name"
                                                                addon-left-icon="fas fa-building"
                                                                v-model="scope.row.company_name" :error="errors[0]"
                                                                :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                                            </base-input>
                                                        </ValidationProvider>
                                                    </div>
                                                    <div class="col-sm-4 col-md-4 col-lg-4">
                                                        <ValidationProvider name="Contact Name" rules="required"
                                                            v-slot="{ passed, failed, errors }">
                                                            <base-input label="Contact Name" type="text"
                                                                placeholder="Input Contact Name"
                                                                addon-left-icon="far fa-id-badge"
                                                                v-model="scope.row.name" :error="errors[0]"
                                                                :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                                            </base-input>
                                                        </ValidationProvider>
                                                    </div>
                                                    <div class="col-sm-4 col-md-4 col-lg-4">
                                                        <ValidationProvider name="email" rules="required|email"
                                                            v-slot="{ passed, failed, errors }">
                                                            <base-input label="Email" type="email"
                                                                placeholder="Input Client Email"
                                                                addon-left-icon="fas fa-envelope"
                                                                v-model="scope.row.email" :lowercase="true"
                                                                :error="errors[0]"
                                                                :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                                            </base-input>
                                                        </ValidationProvider>
                                                    </div>

                                                    <div class="col-sm-6 col-md-6 col-lg-6">
                                                        <!-- <base-input
                                                                    label="Phone Number"
                                                                    type="text"
                                                                    placeholder="Input Client Phone"
                                                                    addon-left-icon="fas fa-phone-alt"
                                                                    v-model="scope.row.phonenum"
                                                                    class="phonenum"
                                                                    aria-autocomplete="none"
                                                                    >
                                                                </base-input> -->
                                                        <label>Phone Number</label>

                                                        <VuePhoneNumberInput
                                                            :defaultCountryCode="clientPhoneNumber.countryCode"
                                                            v-model="clientPhoneNumber.number"
                                                            @update="onPhoneUpdate" />
                                                    </div>

                                                    <div class="col-sm-6 col-md-6 col-lg-6 form-group has-label"
                                                        v-if="false">
                                                        <base-input label="Domain Name:" type="text"
                                                            placeholder="yourdomain.com"
                                                            addon-left-icon="fas fa-globe-americas"
                                                            v-model="scope.row.domain">
                                                        </base-input>
                                                    </div>
                                                    <div class="col-sm-6 col-md-6 col-lg-6 form-group has-label">
                                                        <base-input label="Update Password:" type="password"
                                                            placeholder="Type your new password"
                                                            addon-left-icon="fas fa-key"
                                                            v-model="scope.row.newpassword">
                                                        </base-input>
                                                    </div>
                                                    <div class="mt-2">
                                                        <div class="col-sm-12 col-md-12 col-lg-12">
                                                            <base-checkbox @change="clientreceiveemail(scope.row)"
                                                                :checked="scope.row.disabled_receive_email == 'T' ? true : false"
                                                                inline>Disable all system emails to this
                                                                client</base-checkbox>
                                                        </div>
                                                        <div class="col-sm-12 col-md-12 col-lg-12 pt-2">
                                                            <base-checkbox @change="clientaddcampaign(scope.row)"
                                                                :checked="scope.row.disable_client_add_campaign == 'T' ? true : false"
                                                                inline>Disable clients ability to add
                                                                campaigns</base-checkbox>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2">
                                                        <div class="col-sm-12 col-md-12 col-lg-12">
                                                            <span class="" style="font-size: 0.80143rem;">Product(s) :</span>

                                                            <base-checkbox
                                                                v-for="(sidebar, key) in customsidebarleadmenu"
                                                                :key="sidebar.url"
                                                                class="mr-3"
                                                                v-model="selectedModules[key]"
                                                            >
                                                            {{ sidebar.name }}
                                                            </base-checkbox>
                                                        </div>
                                                    </div>

                                                    <div class="col-sm-12 col-md-12 col-lg-12">
                                                        <base-button v-if="$global.settingMenuShow_update"
                                                            :id="'btnSave' + scope.row.id" size="sm" class="pull-right"
                                                            style="height:40px" @click="ProcessAddEditClient(scope.row)">
                                                            Save
                                                        </base-button>
                                                        <base-button size="sm" class="pull-right mr-4"
                                                            style="height:40px" @click="CancelAddEditClient(scope.row)">
                                                            Cancel
                                                        </base-button>
                                                        <base-button size="sm" class="pull-right mr-4"
                                                            :id="'btnResend' + scope.row.id" style="height:40px"
                                                            @click="ResendInvitation(scope.row)">
                                                            Resend Invitation
                                                        </base-button>
                                                    </div>
                                                </div>
                                            </form>
                                        </ValidationObserver>
                                    </template>

                                    <!-- START EDIT AREA -->
                                </el-table-column>

                            </el-table>
                        </div>
                    </div>

                    <template slot="footer">
                        <div class="tab-footer pull-right">
                            <div class="pt-3">
                                <p class="card-category">
                                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }}
                                    entries
                                </p>
                            </div>
                            <base-pagination class="pagination-no-border pt-4" v-model="pagination.currentPage"
                                :per-page="pagination.perPage" :total="pagination.total" @input="changePage">
                            </base-pagination>
                        </div>
                    </template>

                </card>
            </div>
        </div>
        <!-- Modal Video Guide -->
        <modal :show.sync="modals.helpguide" headerClasses="justify-content-center" modalContentClasses="customhead">
            <h4 slot="header" class="title title-up">What is on Dashboard Menu?</h4>
            <p class="text-center">
                Watch the video below if you still have question please <a href="#">Contact Us</a>
            </p>
            <div>
                <!--<iframe width="970" height="415" src="https://www.youtube.com/embed/SCSDyqRP7cY" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>-->
            </div>
            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button @click.native="modals.helpguide = false">Ok I already understand</base-button>
                </div>
            </template>
        </modal>
        <!-- Modal Video Guide -->

        <!-- Modal Card Setup -->
        <modal id="modalSetCard" :show.sync="modals.cardsetup" headerClasses="justify-content-center">
            <h4 slot="header" class="title title-up">Credit Card Setup for <span
                    style="color:#d42e66">{{LeadspeekCompany}}</span></h4>
            <!-- IF HAVE FAILED PAYMENT -->
            <div class="col-sm-12 col-md-12 col-lg-12 text-center" v-if="clientPaymentFailed">
                <p>Your account has outstanding invoices for these campaigns:</p>
                <ul style="width:40%;text-align:left;margin:0 auto">
                    <li style="color:#000" v-for="(item, index) in failedCampaignNumber" :key="index">
                        Campaign #{{ item }} total amount : {{ displayMoney(failedInvoiceAmount[index]) }}
                    </li>
                    <li class="pt-2 pb-2" style="list-style:none;margin-left:-95px;color:#000">The total outstanding
                        amount is <strong>{{ failedInvoiceTotal() }}</strong></li>
                </ul>
                <p>Please update your credit card information or retry the charge with your existing card. Thank you.
                </p>
            </div>
            <!-- IF HAVE FAILED PAYMENT -->

            <div class="row justify-content-center client__management__card__credit">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <ValidationObserver ref="formCC">
                        <form>

                            <!--START ADD / UPDATE FOR CC -->
                            <div id="card-element" class="col-sm-12 col-md-12 col-lg-12 card-border">&nbsp;</div>
                            <div class="pt-2 pl-2" v-if="cardfailetoload" style="font-size:0.80143rem"><i
                                    class="fas fa-sync-alt pr-2" @click="refreshCCfield();"
                                    style="cursor:pointer"></i>Failed to load the Credit Card field. Please <a
                                    href="javascript:void(0);" @click="refreshCCfield();" style="font-weight:bold">click
                                    here</a> to refresh and try again.</div>
                            <div id="carderror" class="col-sm-12 col-md-12 col-lg-12 pt-2 hide" style="color:red">
                                <small>&nbsp;</small>
                            </div>

                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <ValidationProvider name="Card Holder Name" rules="required"
                                    v-slot="{ passed, failed, errors }">
                                    <base-input v-model="cardholdername" label="Card Holder Name" :error="errors[0]"
                                        :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                    </base-input>
                                </ValidationProvider>
                            </div>

                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <ValidationProvider name="Billing Address" rules="required"
                                    v-slot="{ passed, failed, errors }">
                                    <base-input v-model="billingaddress" label="Billing Address" :error="errors[0]"
                                        :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                    </base-input>
                                </ValidationProvider>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12 mt-2">
                                <div class="country-region-select-wrapper">
                                    <span style="color: #525f7f;font-size: 14px;" class="country-label">Country *</span>
                                    <country-select style="color: #525f7f;border-color: #cad1d7;"
                                        class="country-region-select" v-model="selects.country"
                                        :country="selects.country" topCountry="US" />
                                    <span v-if="showErrorMessage && !selects.country">Please select country</span>
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-12 col-lg-12 mt-2">
                                <div v-if="selects.country" class="country-region-select-wrapper">
                                    <span style="color: #525f7f;font-size: 14px;"
                                        class="country-label">{{selects.country === "CA" ?"Province *": "State*"}}</span>
                                    <region-select style="color: #525f7f;border-color: #cad1d7;"
                                        class="country-region-select" v-model="selects.state" :country="selects.country"
                                        :region="selects.state" />
                                    <span v-if="showErrorMessage && !selects.state">Please select state</span>
                                </div>
                            </div>
                            <div class="row pt-2 mr-0 ml-0">
                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <ValidationProvider name="City" rules="required"
                                        v-slot="{ passed, failed, errors }">
                                        <base-input v-model="city" label="City" :error="errors[0]"
                                            :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                        </base-input>
                                    </ValidationProvider>
                                </div>
                                <!-- <div class="col-sm-4 col-md-4 col-lg-4">
                                                    <label>State</label>
                                                    <base-input>
                                                        <el-select
                                                        v-model="selects.state"
                                                        class="select-primary "
                                                        name="state"
                                                        inline
                                                        size="large"
                                                        filterable
                                                        default-first-option
                                                        >
                                                        <el-option
                                                            v-for="option in selects.statelist"
                                                            class="select-primary"
                                                            :label="option.state"
                                                            :value="option.state_code"
                                                            :key="option.state_code"
                                                        ></el-option>
                                                        </el-select>
                                                    </base-input>
                                                </div> -->
                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <ValidationProvider name="Zip" rules="required" v-slot="{ passed, failed, errors }">
                                        <base-input v-model="zipcode" label="Zip" :error="errors[0]"
                                            :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
                                        </base-input>
                                    </ValidationProvider>
                                </div>
                            </div>
                            <!--START ADD / UDPATE FOR CC -->
                            <div class="pt-3 pb-3">&nbsp;</div>

                            <div class="col-sm-12 col-md-12 col-lg-12 ">
                                <card>
                                    <div slot="header">
                                        <h4 class="card-title">Your Current Card Information</h4>
                                    </div>
                                </card>
                            </div>


                            <div class="col-sm-12 col-md-12 col-lg-12 mr-0 ml-0">
                                <p v-if="currCardHolder != ''">{{currCardHolder}}</p>
                                <p v-if="currCardlastdigit != ''">Card Number :
                                    &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;
                                    {{currCardlastdigit}}</p>
                                <p v-if="currCardType != ''">Card Type : {{currCardType}}</p>
                            </div>

                            <div class="pt-3 pb-3">&nbsp;</div>

                            <div class="col-sm-12 col-md-12 col-lg-12 ">
                                <base-checkbox v-model="agreeTerm" class="pull-left"
                                    :class="{'has-danger': agreeTermStat}">I attest that the client and credit card owner has agreed to the <a
                                        :href="'/service-billing-agreement/?src=client&host=' + termhost"
                                        target="_blank" style="color:#919aa3"
                                        :style="[agreeTermStat?{'color':'#ec250d'}:'']">Billing
                                        Terms and Service Agreement</a></base-checkbox>
                            </div>

                            <div class="col-sm-12 col-md-12 col-lg-12" v-if="!clientPaymentFailed">
                                <base-button @click="validateCC('');" id="btnupdcc" size="sm" class="pull-right"
                                    style="height:40px">
                                    Update
                                </base-button>
                            </div>
                            <div class="row pt-2" style="width:100%" v-if="clientPaymentFailed">

                                <div class="col-sm-6 col-md-6 col-lg-6 pl-4">
                                    <base-button @click="validateCC('existcard');" id="btnRetryExistCard" size="sm"
                                        class="pull-right" style="height:50px">
                                        Retry charge with existing card
                                    </base-button>
                                </div>
                                <div class="col-sm-6 col-md-6 col-lg-6 pr-0">
                                    <base-button @click="validateCC('updatecharge');" id="btnUpdateAndCharge" size="sm"
                                        class="pull-right" style="height:50px">
                                        save and charge amount due
                                    </base-button>
                                </div>
                            </div>

                        </form>
                    </ValidationObserver>
                </div>
            </div>
        </modal>
        <!-- Modal Card Setup -->

        <!-- Card Update Modal -->
        <modal :show.sync="modals.cardupdate" headerClasses="justify-content-center">
            <h4 slot="header" class="title title-up">Card Information</h4>
            <p>
                Thank you! Your Credit Card Information Has been updated.
            </p>
            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button @click.native="closebtnupdatecard()">Ok</base-button>
                </div>
            </template>
        </modal>
        <!-- Card Update Modal -->

        <!-- Card retry charge Modal -->
        <modal :show.sync="modals.cardretrycharge" headerClasses="justify-content-center">
            <h4 slot="header" class="title title-up">{{cardretrychargeTitle}}</h4>
            <p class="text-center" v-html="cardretrychargeTxt">

            </p>
            <template slot="footer">
                <div class="container text-center pb-4">
                    <base-button @click.native="closebtnupdatecard()">Ok</base-button>
                </div>
            </template>
        </modal>

        <!-- Modal Setting Markup -->
        <modal id="modalSetPrice" :show.sync="modals.pricesetup" headerClasses="justify-content-center">
            <h4 slot="header" class="title title-up">Set Campaign pricing for <span
                    style="color:#d42e66">{{LeadspeekCompany}}</span></h4>
            <div class="row">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <div class="row" style="padding-inline: 16px; margin-bottom: 24px;">
                        <div v-if="moduleAgency.local != undefined" @click="selectsAppModule.AppModuleSelect = 'LeadsPeek'" 
                        style="display: flex; justify-content: center;"
                        class="menu__prices"
                        :class="[selectsAppModule.AppModuleSelect === 'LeadsPeek' ? 'active__menu__prices' : '', cssDefaultModuleByLength()]">
                        {{ this.$global.globalModulNameLink.local.name }}
                        </div>
                        <div v-if="moduleAgency.locator != undefined" @click="selectsAppModule.AppModuleSelect = 'locator'" 
                        style="display: flex; justify-content: center;"
                        class="menu__prices"
                        :class="[selectsAppModule.AppModuleSelect === 'locator' ? 'active__menu__prices' : '', cssDefaultModuleByLength()]">
                            {{ this.$global.globalModulNameLink.locator.name }}
                        </div>
                        <div v-if="this.$global.globalModulNameLink.enhance.name != null && this.$global.globalModulNameLink.enhance.url != null && moduleAgency.enhance != undefined" 
                        @click="selectsAppModule.AppModuleSelect = 'enhance'" 
                        class="menu__prices"  
                        :class="[selectsAppModule.AppModuleSelect === 'enhance' ? 'active__menu__prices' : '', cssDefaultModuleByLength()]" 
                        style="display: flex; justify-content: center;">
                            {{ this.$global.globalModulNameLink.enhance.name }}
                        </div>
                        <div style="width: 100%; border: 1px solid gray; height: 1px; margin-top: 16px;"></div>
                    </div>
                    <div class="d-flex flex-column" style="margin-bottom:16px;">
                        <span class="client-payment-modal-form-label" style="color:#222a42">Billing Frequency</span>
                        <el-select class="select-primary" size="small" placeholder="Select Modules"
                            v-model="selectsPaymentTerm.PaymentTermSelect" @change="paymentTermChange()">
                            <el-option v-for="option in selectsPaymentTerm.PaymentTerm" class="select-primary"
                                :value="option.value" :label="option.label" :key="option.label">
                            </el-option>
                        </el-select>
                    </div>
                </div>
            </div>

            <div v-if="selectsAppModule.AppModuleSelect == 'LeadsPeek' && moduleAgency.local != undefined">
                <div class="client-payment-setup-form-wrapper">

                    <div class="row">
                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                Setup Fee
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="LeadspeekPlatformFee"
                                    @keyup="set_fee('local','LeadspeekPlatformFee');"
                                    @blur="handleFormatCurrency('local','LeadspeekPlatformFee')"
                                    @keydown="restrictInput"  
                                    >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                Campaign Fee <span v-html="txtLeadService">per month</span>
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="LeadspeekMinCostMonth"
                                    @keyup="set_fee('local','LeadspeekMinCostMonth');"
                                    @blur="handleFormatCurrency('local','LeadspeekMinCostMonth')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-md-6 col-lg-6" v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time'">
                            <div class="client-payment-modal-form-label">
                                Cost per lead<span v-html="txtLeadOver" v-if="false">from the
                                    monthly charge</span>?
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="LeadspeekCostperlead"
                                    @keyup="set_fee('local','LeadspeekCostperlead');"
                                    @blur="handleFormatCurrency('local','LeadspeekCostperlead')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                How many leads per day does the client want to receive?
                            </div>
                            <div>
                                <base-input style="text-align: left;" label="" type="text" placeholder="0"
                                    class="campaign-cost-input" v-model="LeadspeekLeadsPerday"
                                    @keyup="set_fee('local','LeadspeekLeadsPerday');"
                                    @blur="handleFormatCurrency('local','LeadspeekLeadsPerday')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                            <span class="client-payment-modal-form-helper-text">Zero means unlimited</span>


                        </div>
                    </div>

                </div>

            </div>

            <div v-if="selectsAppModule.AppModuleSelect == 'locator' && moduleAgency.locator != undefined">
                <div class="client-payment-setup-form-wrapper">

                    <div class="row">
                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                Setup Fee
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="LocatorPlatformFee"
                                    @keyup="set_fee('locator','LocatorPlatformFee');"
                                    @blur="handleFormatCurrency('locator','LocatorPlatformFee')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                Campaign Fee <span v-html="txtLeadService">per month</span>
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="LocatorMinCostMonth"
                                    @keyup="set_fee('locator','LocatorMinCostMonth');"
                                    @blur="handleFormatCurrency('locator','LocatorMinCostMonth')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-md-6 col-lg-6" v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time'">
                            <div class="client-payment-modal-form-label">
                                Cost per lead<span v-html="txtLeadOver" v-if="false">from the
                                    monthly charge</span>?
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="LocatorCostperlead"
                                    @keyup="set_fee('locator','LocatorCostperlead');"
                                    @blur="handleFormatCurrency('locator','LocatorCostperlead')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                How many leads per day does the client want to receive?
                            </div>
                            <div>
                                <base-input style="text-align: left;" label="" type="text" placeholder="0"
                                    class="campaign-cost-input" v-model="LocatorLeadsPerday"
                                    @keyup="set_fee('locator','LocatorLeadsPerday');"
                                    @blur="handleFormatCurrency('locator','LocatorLeadsPerday')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                            <span class="client-payment-modal-form-helper-text">Zero means unlimited</span>


                        </div>
                    </div>

                </div>

            </div>

            <div v-if="selectsAppModule.AppModuleSelect == 'enhance' && moduleAgency.enhance != undefined">
                <div class="client-payment-setup-form-wrapper">

                    <div class="row">
                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                Setup Fee
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input"  v-model="EnhancePlatformFee"    
                                    @keyup="set_fee('enhance','EnhancePlatformFee');"
                                    @blur="handleFormatCurrency('enhance','EnhancePlatformFee')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-6"  >
                            <div class="client-payment-modal-form-label">
                                Campaign Fee <span v-html="txtLeadService">per month</span>
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="EnhanceMinCostMonth"
                                    @keyup="set_fee('enhance','EnhanceMinCostMonth');"
                                    @blur="handleFormatCurrency('enhance','EnhanceMinCostMonth')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-md-6 col-lg-6" 
                            v-if="selectsPaymentTerm.PaymentTermSelect != 'One Time'">
                            <div class="client-payment-modal-form-label">
                                Cost per lead<span v-html="txtLeadOver" v-if="false">from the
                                    monthly charge</span>?
                            </div>
                            <div>
                                <base-input label="" type="text" placeholder="0" addon-left-icon="fas fa-dollar-sign"
                                    class="campaign-cost-input" v-model="EnhanceCostperlead"
                                    @keyup="set_fee('enhance','EnhanceCostperlead');"
                                    @blur="handleFormatCurrency('enhance','EnhanceCostperlead')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-6 col-lg-6">
                            <div class="client-payment-modal-form-label">
                                How many leads per day does the client want to receive?
                            </div>
                            <div>
                                <base-input style="text-align: left;" label="" type="text" placeholder="0"
                                    class="campaign-cost-input" v-model="EnhanceLeadsPerday"
                                    @keyup="set_fee('enhance','EnhanceLeadsPerday');"
                                    @blur="handleFormatCurrency('enhance','EnhanceLeadsPerday')"
                                    @keydown="restrictInput"
                                    >
                                </base-input>
                            </div>
                            <span v-if="!errMinLeadDay" class="client-payment-modal-form-helper-text">Zero means unlimited</span>
                            <span v-if="errMinLeadDay" style="color:#942434; font-size:12px;font-weight: 400;line-height: 12px;margin-top: 4px;display: block;">*Leads Per Day Minimum {{ clientMinLeadDayEnhance }}</span>

                        </div>
                    </div>

                </div>

            </div>

            <template slot="footer">
                <div class="d-flex justify-content-center w-100 pb-4">
                    <base-button @click.native="save_default_price()">Ok, Set It Up!</base-button>
                </div>
            </template>
        </modal>
        <!-- Modal Setting Markup -->
        <!-- Modal integration settings -->
        <modal :show.sync="modals.integrations" id="addIntegrations"
            footerClasses="border-top">
            <h3 slot="header" class="title title-up">Integration Settings for {{ selectedRowData.company_name }}</h3>
            <div>
                <div class="integratios-list-wrapper d-flex align-items-center gap-4 flex-wrap">
                    <div v-for="item in integrations" :key="item.slug"
                        class="integrations__modal-item-wrapper d-flex align-items-center justify-content-center shadow-sm border"
                        :class="{ '--active bg-blue text-secondary': item.slug === selectedIntegration }"
                        @click="selectedIntegration = item.slug">
                        <div class="integrations__modal-item">
                            <i :class="item.img" style="font-size: 36px;"></i>
                            <span class="integrarion-brand-name">{{ item.name }}</span>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <div v-if="selectedIntegration === 'gohighlevel'" class="''">
                        <el-checkbox v-model="enabledconntector.gohighlevel">Enable GoHighLevel</el-checkbox>
                        <div class="has-label">
                            <label class="mb-0">Enter your API Key</label>
                            <el-input type="text" clearable label="Enter your API Key" placeholder="AssAW02A02ksjsklah"
                                class="flex-grow-1 mb-0" v-model="userApiKey.gohighlevel" aria-controls="datatables">
                            </el-input>

                        </div>


                    </div>

                    <div v-if="selectedIntegration === 'sendgrid'" class="''">
                        <el-checkbox v-model="enabledconntector.sendgrid">Enable Sendgrid</el-checkbox>
                        <div class="has-label">
                            <label class="mb-0">Enter your API Key</label>
                            <el-input type="text" clearable label="Enter your API Key" placeholder="GJYEzzse345673htj"
                                class="flex-grow-1 mb-0" v-model="userApiKey.sendgrid" aria-controls="datatables">
                            </el-input>

                        </div>


                    </div>
                    <div v-if="selectedIntegration === 'kartra'" class="''">
                        <el-checkbox v-model="enabledconntector.kartra">Enable Kartra</el-checkbox>
                        <div class="has-label">
                            <label class="mb-0">Enter your API Key</label>
                            <el-input type="text" clearable label="Enter your API Key" placeholder="GJYEzzse345673htj"
                                class="flex-grow-1 mb-0" v-model="userApiKey.kartra" aria-controls="datatables">
                            </el-input>

                        </div>
                        <div class="has-label">
                            <label class="mb-0">Enter your API Password</label>
                            <el-input type="text" clearable label="Enter your API Password"
                                placeholder="GJYEzzse345673htj" class="flex-grow-1 mb-0" v-model="kartraApiPassword"
                                aria-controls="datatables">
                            </el-input>

                        </div>
                        <!-- <div class="has-label">
                            <label class="mb-0">Enter your App ID</label>
                            <el-input type="text" clearable label="Enter your App ID"
                                 placeholder="GJYEzzse345673htj" class="flex-grow-1 mb-0" v-model="kartraAppId" aria-controls="datatables">
                            </el-input>

                        </div> -->


                        <p class="mt-4 d-block"><strong>Note:</strong> When you click Save, this app will create the
                            below custom fields in your Kartra account. These fields are Required for this connection to
                            work properly.
                            As per Kartra's policy, once a custom field is deleted, it cannot be recreated. Therefore,
                            <span class="text-underline">Do NOT</span> delete or rename the below custom fields in your
                            Kartra account. <strong style="cursor: pointer;" v-if="!showKartraCustomFields"
                                @click="showKartraCustomFields = !showKartraCustomFields">show more</strong>
                        </p>

                        <ul v-show="showKartraCustomFields" class="text-dark">
                            <li class="text-dark">secondphone</li>
                            <li class="text-dark">secondemail</li>
                            <li class="text-dark">keyword</li>
                            <li class="text-dark">secondaddress</li>
                        </ul>
                    </div>
                    <div v-if="selectedIntegration === 'zapier'" class="''">
                        <el-checkbox v-model="enabledconntector.zapier">Enable Zapier</el-checkbox>
                        <div class="has-label">
                            <label class="mb-0">Enter your Webhook URL</label>
                            <!-- <el-input type="text" clearable label="Enter your Webhook URL"
                                placeholder="https://hooks.zapier.com/hooks/catch/....." class="flex-grow-1 mb-0"
                                v-model="userApiKey.zapier" aria-controls="datatables">
                            </el-input> -->
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <div v-for="(url, index) in userApiKey.zapier" :key="index" class="webhook-url-wrapper">
                                        <el-input type="text" clearable label="Enter your Webhook URL"
                                            placeholder="https://hooks.app.com/hooks/catch/....." class="flex-grow-1 mb-0"  v-model="userApiKey.zapier[index]"  aria-controls="datatables" @keyup.native="addWebhookUrlKeyupEnter($event)" :ref="'webhookInput-' + index">
                                        </el-input>
                                    <span 
                                        v-if="index == 0" 
                                        class="add-webhook-url" 
                                        @click="addWebhookUrl">
                                        <i class="fa-solid fa-plus"></i>
                                    </span>
                                    <span 
                                         v-if="index != 0 && userApiKey.zapier.length > 1" 
                                        class="add-webhook-url" 
                                         @click="removeWebhookUrl(index)">
                                        <i class="fa-solid fa-minus"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <el-checkbox v-model="enabledconntector.sendTestZapier" class="mt-2">Send Test
                            Data</el-checkbox>
                        <p class="m-0">* check and click save to send dummy data to your webhook</p>
                    </div>
                  
                </div>
            </div>
            <template slot="footer">
                <div class="integrations-modal-footer-wrapper">
                    <div class="d-flex align-items-center justify-content-end">
                        <base-button @click.native="modals.integrations = false">Cancel</base-button>
                        <base-button id="btnSaveIntegration"
                            @click.native="saveIntegrationSettingslocal">Save</base-button>
                    </div>
                </div>
            </template>
        </modal>

        <!-- Modal integration settings -->
        <!-- WhiteList DB upload -->
        <modal :show.sync="modals.whitelist" id="clientWhitelist" headerClasses="justify-content-center">
            <h4 slot="header" class="title title-up">Client Wide Exclusion List</h4>
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
            <ul v-if="supressionProgress.length > 0" class="mt-2 mb-0 mx-0 p-0"
                style="list-style: none; max-height: 90px; overflow: auto;">
                <li v-for="(progress, index) in supressionProgress" :key="index" class="text-dark m-0 p-0">
                    <i class="mr-2"
                        :class="{'el-icon-loading': progress.status === 'progress', 'el-icon-circle-check': progress.status === 'done', 'el-icon-eleme': progress.status === 'queue'}"></i>
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
                <div class="text-center" v-if="optoutfileexist">Your current exclusion list: <a
                        :href="optoutpath + '/tools%2Foptout%2Fclientoptoutlist_' + whiteListClientID + '.csv'"
                        target="_blank">download here</a></div>
            </div>
            <a class="mt-2 d-inline-block" @click="purgeSuppressionList('client')" style="cursor: pointer;"><i
                    class="fas fa-trash"></i> Purge Existing Records</a>
            <template slot="footer">
                <div class="pb-4 d-flex justify-content-center w-100">
                    <base-button @click.native="modals.whitelist = false">Cancel</base-button>
                </div>
            </template>
        </modal>
        <!-- WhiteList DB upload -->
    </div>
</template>
<script>
import { extend } from "vee-validate";
import { required, email} from "vee-validate/dist/rules";
import { DatePicker, Table, TableColumn, Select, Option,Checkbox, Dropdown, DropdownMenu, DropdownItem,CollapseItem,Collapse } from 'element-ui';
import { BasePagination,Modal } from 'src/components';
import Fuse from 'fuse.js';
import swal from 'sweetalert2';
import moment from 'moment';
import { mapActions } from "vuex";
import VuePhoneNumberInput from '@/components/VuePhoneNumberInput';
import axios from 'axios';
import { formatCurrencyUSD } from '@/util/formatCurrencyUSD'


const STATUS_INITIAL = 0, STATUS_SAVING = 1, STATUS_SUCCESS = 2, STATUS_FAILED = 3;

extend("email", email);
extend("required", required);

var _elements;
var _cardElement;
var _stripe;
var _CardValidate;
var _tokenid = '';
var _sppubkey = '';
var _this;

export default {
    components: {
        Modal,
        [DatePicker.name]: DatePicker,
        [Table.name]: Table,
        [TableColumn.name]: TableColumn,
        [Option.name]: Option,
        [Select.name]: Select,
        [Checkbox.name]: Checkbox,
        BasePagination,
        VuePhoneNumberInput,
        [Dropdown.name]: Dropdown,
        [DropdownMenu.name]: DropdownMenu,
        [DropdownItem.name]: DropdownItem,
        [Collapse.name]: Collapse,
        [CollapseItem.name]: CollapseItem,
       
    },
    data() {
        return {
            dropdownVisible: false,
            clientMinLeadDayEnhance: '',
            errMinLeadDay: false, 
            filters:{
                cardStatus:{
                    show:false,
                    active:false,
                    failed:false,
                    inactive:false
                },
                campaignStatus:{
                    show:false,
                    active:false,
                    inactive:false,
                }
            },
            termhost:'',
            showKartraCustomFields:'',
            integrations: [
                {
                    name: 'Twilio SendGrid',
                    description: 'Deliver Exceptional Email Experiences with SendGrid',
                    img: 'fa-solid fa-cloud-arrow-up',
                    active: '3',
                    slug: 'sendgrid'
                },
                {
                    name: 'GoHighLevel',
                    description: 'Get Better Clicks & Engagement - Enhance Your Email Campaigns.',
                    img: 'fas fa-angle-double-up',
                    active: '4',
                    slug: 'gohighlevel'
                },
                {
                    name: 'Kartra',
                    description: 'This is Kartra intergration .',
                    img: 'fa-solid fa-k',
                    active: '5',
                    slug: 'kartra'
                },
                {
                    name: 'Zapier/Webhooks',
                    description: 'This is Zapier intergration .',
                    img: 'fa-solid fa-link',
                    active: '6',
                    slug: 'zapier'
                },
                // {
                //     name: 'Webhook',
                //     description: 'Webhook Integration.',
                //     img: 'fa-solid fa-link',
                //     active: '7',
                //     slug: 'webhook'
                // },
            ],
            /** FOR SUPRESSION UPLOAD FILE */
            uploadedFiles: [],
            uploadError: null,
            currentStatus: null,
            uploadFieldName: 'clientoptoutfile',
            /** FOR SUPRESSION UPLOAD FILE */
            selectedIntegration: 'sendgrid',
            selectedModule: 'LeadsPeek',
             tableDataOri:[],
             tableData: [],
             fuseSearch: null,
             searchedData: [],
             searchQuery: '',
             pagination: {
                perPage: 10,
                currentPage: 1,
                //perPageOptions: [5, 10, 25, 50],
                total: 0,
                from: 0,
                to: 0,
            },

            currCardHolder: '',
            currCardlastdigit: '',
            currCardType: '',

            cardholdername: '',
            billingaddress: '',
            city: '',
            zipcode: '',
            selects: {
                state: '',
                country: '',
                statelist: [],
            },
            agreeTerm:false,
            showErrorMessage:false,
            cardfailetoload:false,
            agreeTermStat:false,
            btncardupdate: false,

            modals: {
                helpguide: false,
                pricesetup: false,
                cardsetup: false,
                cardupdate: false,
                integrations: false,
                whitelist: false,
                cardretrycharge: false,
            },
            cardretrychargeTitle: '',
            cardretrychargeTxt: '',

            companyID:'',
            selectedRowData: {},
            ClientCompanyName: '',
            ClientFullName: '',
            ClientEmail: '',
            clientPhoneNumber:{
                number:'',
                countryCode:'US',
                countryCallingCode:'+1'
            },
            ClientPhone: '',
            ClientActiveID: '',
            ClientDomain:'',
            LeadspeekCompany: '',

            LeadspeekPlatformFee: '0',
            LeadspeekCostperlead: '0',
            LeadspeekMinCostMonth: '0',
            LeadspeekLeadsPerday: '10',

            LocatorPlatformFee: '0',
            LocatorCostperlead: '0',
            LocatorMinCostMonth: '0',
            LocatorLeadsPerday: '10',

            EnhancePlatformFee: '0',
            EnhanceCostperlead: '0',
            EnhanceMinCostMonth: '0',
            EnhanceLeadsPerday: '10',

            LeadspeekInputReadOnly: false,

            lead_FirstName_LastName : '0',
            lead_FirstName_LastName_MailingAddress: '0',
            lead_FirstName_LastName_MailingAddress_Phone: '0',

            costagency : {
                local : {
                    'Weekly' : {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'Monthly' : {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'OneTime' : {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '10',
                    },
                    'Prepaid' : {
                        LeadspeekPlatformFee: '0',
                        LeadspeekCostperlead: '0',
                        LeadspeekMinCostMonth: '0',
                        LeadspeekLeadsPerday: '50',
                    }
                },

                locator : {
                    'Weekly' : {
                        LocatorPlatformFee: '0',
                        LocatorCostperlead: '0',
                        LocatorMinCostMonth: '0',
                        LocatorLeadsPerday: '10',
                    },
                    'Monthly' : {
                        LocatorPlatformFee: '0',
                        LocatorCostperlead: '0',
                        LocatorMinCostMonth: '0',
                        LocatorLeadsPerday: '10',
                    },
                    'OneTime' : {
                        LocatorPlatformFee: '0',
                        LocatorCostperlead: '0',
                        LocatorMinCostMonth: '0',
                        LocatorLeadsPerday: '10',
                    },
                    'Prepaid' : {
                        LocatorPlatformFee: '0',
                        LocatorCostperlead: '0',
                        LocatorMinCostMonth: '0',
                        LocatorLeadsPerday: '10',
                    }
                },

                enhance : {
                    'Weekly' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '10',
                    },
                    'Monthly' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '10',
                    },
                    'OneTime' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '10',
                    },
                    'Prepaid' : {
                        EnhancePlatformFee: '0',
                        EnhanceCostperlead: '0',
                        EnhanceMinCostMonth: '0',
                        EnhanceLeadsPerday: '10',
                    }
                },

                locatorlead: {
                    FirstName_LastName: '0',
                    FirstName_LastName_MailingAddress: '0',
                    FirstName_LastName_MailingAddress_Phone: '0',
                }
            },

            activeClientCompanyID: '',
            activeClientCompanyIndex: '',

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
                    { value: 'LeadsPeek', label: 'LeadsPeek' },
                ],
                LeadsLimitSelect: 'Day',
                LeadsLimit: [
                    { value: 'Day', label: 'Day'},
                ],
            },

            selectsPTAds: {
                    PTAdsSelect: 'By Fix Amount',
                    PTAds: [
                        { value: 'By Fix Amount', label: 'By Fix Amount' },
                        { value: 'By Percentage', label: 'By Percentage' },
                        
                    ],
            },

            disabledreceiveemail: false,
            openFiletrs: false,
            disabledaddcampaign:false,
            integrationDetails:{},
            userApiKey: {
                sendgrid: '',
                gohighlevel: '',
                kartra: '',
                zapier:[''],
            },

            kartraApiPassword:'',
            kartraAppId:'',
            enabledconntector: {
                sendgrid: false,
                gohighlevel: false,
                kartra: false,
                zapier:false,
                sendTestZapier: false,
            },
            currentRowIndex:0,
            whiteListClientID: '',
            optoutfileexist: false,
            optoutpath: process.env.VUE_APP_CDN,
            clientPaymentFailed: false,
            currSortBy: '',
            currOrderBy: '',
            failedCampaignNumber: [],
            failedInvoiceAmount: [],
            failedTotalOutstanding:0,

            supressionProgress: [],
            supressionInterval: '',

            customsidebarleadmenu:[],
            selectedModules:{},
            selectedModulesCreate:{},
            expandedRow: null,
            isProcessingExpandRow: false,
            moduleAgency: {},
            prevSelectedModules: [],
            localActiveCampaignId: [],
            locatorActiveCampaignId: [],
            enhanceActiveCampaignId: [],
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
            return result.slice().sort((a, b) => {
                // Move items with payment_status == 'failed' to the top
                if (a.payment_status === 'failed' && b.payment_status !== 'failed') {
                  return -1;
                } else if (a.payment_status !== 'failed' && b.payment_status === 'failed') {
                  return 1;
                }
            
                // Move items with customer_payment_id == '' or customer_card_id == '' next
                if ((a.customer_payment_id === '' || a.customer_card_id === '') && (b.customer_payment_id !== '' && b.customer_card_id !== '')) {
                  return -1;
                } else if ((a.customer_payment_id !== '' && a.customer_card_id !== '') && (b.customer_payment_id === '' || b.customer_card_id === '')) {
                  return 1;
                }
            
                // Keep the rest of the items in their original order
                return 0;
            });
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
        },      
        initializeSelectedModules() {
            const initialselectedModulesCreate = {};
            const DefaultModules = this.$global.agencyDefaultModules ? this.$global.agencyDefaultModules: '';
            if (DefaultModules != '') {     
                DefaultModules.forEach(module => { 
                    if (module.type) {
                        initialselectedModulesCreate[module.type] = this.$global.agencyDefaultModules ? module.status : true;
                    }
                });
            }
            return initialselectedModulesCreate;
        }  
    },

    methods: {
        ...mapActions(["saveIntegrationSettings", "getUserIntegrationDetails"]),
        validateMinLead() {
            if(this.clientMinLeadDayEnhance != '') {
                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly' && (Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance))) {
                    this.errMinLeadDay = false;
                    this.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                    this.costagency.enhance.Weekly.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly' && (Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance))) {
                    this.errMinLeadDay = false;
                    this.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                    this.costagency.enhance.Monthly.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time' && (Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance))) {
                    this.errMinLeadDay = false;
                    this.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                    this.costagency.enhance.OneTime.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid' && (Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance))) {
                    this.errMinLeadDay = false;
                    this.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                    this.costagency.enhance.Prepaid.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                }
            }
        },
        handleVisibleChange(visible) {            
            this.dropdownVisible = visible;
        },
        addWebhookUrl() {
            this.userApiKey.zapier.push('');
        },
        addWebhookUrlKeyupEnter(event){
            if (event.key === "Enter") {
                this.addWebhookUrl();
            }
        },
        removeWebhookUrl(index) {
            
            if (this.userApiKey.zapier.length > 1) {
                this.userApiKey.zapier.splice(index, 1);
            }
        },
        applyFilters(event){
            event.stopPropagation(); // Prevent click propagation
            this.GetClientList(this.currSortBy, this.currOrderBy)
            this.dropdownVisible = false;
        },
        checkStatusFileUpload() {

            clearInterval(this.supressionInterval);

            /** START CHECK IF THERE IS ANYTHING NOT DONE */
            this.supressionInterval = setInterval(() => {

                this.$store.dispatch('jobProgress', {
                    companyId: this.whiteListClientID,
                    campaignType: 'client',
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
        changePage(event){
            //console.log(this.pagination,event)
            this.GetClientList(this.currSortBy,this.currOrderBy)
        },
        refreshCCfield() {
            if (this.$global.sppubkey != "") {
                _sppubkey = this.$global.sppubkey;
                this.initstripelib();
                //this.cardfailetoload = false;
            }else{
                this.$store.dispatch('getGeneralSetting', {
                    companyID: this.user.company_root_id,
                    settingname: 'rootstripe',
                }).then(response => {
                    _sppubkey = response.data;
                    this.initstripelib();
                    //this.cardfailetoload = false;
                },error => {
                    this.$notify({
                    type: 'primary',
                    message: 'Please try to refresh the page',
                    icon: 'fas fa-bug'
                    })
                    this.cardfailetoload = true;    

                });
            }
        },
        onPhoneUpdate(payload){
           this.clientPhoneNumber.countryCode = payload.countryCode
           this.clientPhoneNumber.countryCallingCode = payload.countryCallingCode
        },
        async handleIntegrationClick(index, row) {
            
            this.selectedRowData = row
    
            try {
                this.integrationDetails = await this.getUserIntegrationDetails({ companyID: this.selectedRowData.company_id, slug: 'all' })
                var _integrationDetails = this.integrationDetails;
                
                this.userApiKey.gohighlevel = "";
                this.userApiKey.sendgrid = "";
                this.userApiKey.kartra = "";
                this.userApiKey.zapier = [''];
                this.kartraAppId = "";
                this.kartraApiPassword = "";
                this.enabledconntector.sendgrid = false;
                this.enabledconntector.gohighlevel = false;
                this.enabledconntector.kartra = false;
                this.enabledconntector.zapier = false;
                this.enabledconntector.sendTestZapier = false;
                
                if (_integrationDetails && Array.isArray(_integrationDetails)) {
                    _integrationDetails.forEach(item => {
                        
                        var _slug = item.company_integration_details.slug;
                        this.enabledconntector[_slug] = item.enable_sendgrid === 1;
                        this.userApiKey[_slug] = item.api_key ? item.api_key : '';
                        if(item.integration_slug === 'kartra'){
                            this.kartraApiPassword = item.password
                            // this.kartraAppId = item.app_id
                        }
                    });
                }else {
                console.error("Integration details not found or not in the expected format.");
                }
             }catch (error) {
                console.error("Error fetching integration details:", error);
            }
            
            $('#btnSaveIntegration').attr('disabled',false);
            $('#btnSaveIntegration').html('Save');   

            this.modals.integrations = true;
        },
        async saveIntegrationSettingslocal() {
            var _slug = this.selectedIntegration;
            
            $('#btnSaveIntegration').attr('disabled',true);
            $('#btnSaveIntegration').html('Saving...');   

            let data = {
                integration_slug: this.selectedIntegration,
                company_id: this.selectedRowData.company_id,
                api_key: this.userApiKey[_slug],
                enable_sendgrid: this.enabledconntector[_slug] ? 1 : 0,
            }
            if(_slug === 'kartra'){
                data.password = this.kartraApiPassword
                // data.app_id = this.kartraAppId
            }
            if(_slug === 'zapier'){
                data.send_test_zapier = this.enabledconntector.sendTestZapier
                const validWebhookUrls = this.userApiKey.zapier.filter(item => item !== null && item != "");
                data.api_key = validWebhookUrls
            }
            await this.saveIntegrationSettings({ data })
            $('#btnSaveIntegration').attr('disabled',false);
            $('#btnSaveIntegration').html('Save');  
            this.modals.integrations = false;
        },
        displayMoney(val) {
          return this.$global.formatMoney(parseFloat(val));
        },
        failedInvoiceTotal() {
            // Calculate the total of the array items
            this.failedTotalOutstanding = this.failedInvoiceAmount.reduce((acc, val) => acc + parseFloat(val), 0);
            return this.$global.formatMoney(this.failedTotalOutstanding);
        },
        /** FOR UPLOAD FILE */
        reset() {
            // reset form to initial state
            this.currentStatus = STATUS_INITIAL;
            this.uploadedFiles = [];
            this.uploadError = null;
            this.uploadFieldName = 'clientoptoutfile';
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

            axios.post(process.env.VUE_APP_APISERVER_URL + '/api/tools/optout-client/upload', formData, config)
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
            //     const response = await axios.post(process.env.VUE_APP_APISERVER_URL + '/api/tools/optout-client/upload', formData, config)
                
            //     if (response.data.result == 'success') {
            //         this.currentStatus = STATUS_SUCCESS;
            //         this.checkStatusFileUpload();
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
                        paramID: this.whiteListClientID,
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
            
            formData.append("ClientCompanyID",this.whiteListClientID);
            formData.append("campaigntype","client");
            // save it
            this.save(formData);
        },
        showWhitelist(index,row) {
            //console.log(row);
            if (row.optoutfile !== null) {
                this.optoutfileexist = false;
            }
            this.whiteListClientID = row.company_id;
            this.modals.whitelist = true;

            this.checkStatusFileUpload();
        },
        getCardStatus(row) {
            const styleObject = {};
            if (row.customer_payment_id == '' || row.customer_card_id == '') {
                styleObject.color = 'gray';
            }else{
                styleObject.color = 'green';
                if (row.payment_status == "failed") {
                    styleObject.color = 'red';
                }
            }
            return styleObject;
        },
        closebtnupdatecard() {
            window.location.reload();
        },
        clientreceiveemail(row) {
            if (row.disabled_receive_email == 'T') {
                row.disabled_receive_email = 'F';
            }else{
                row.disabled_receive_email = 'T';
            }
        },
        clientaddcampaign(row) {
            if (row.disable_client_add_campaign == 'T') {
                row.disable_client_add_campaign = 'F';
            }else{
                row.disable_client_add_campaign = 'T';
            }
        },
        set_fee(type,typevalue) {

            if (type == 'local') {

                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.Weekly.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.Weekly.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                        this.costagency.local.Weekly.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }else if (typevalue == 'LeadspeekLeadsPerday') {
                        this.costagency.local.Weekly.LeadspeekLeadsPerday = this.LeadspeekLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.Monthly.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.Monthly.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                        this.costagency.local.Monthly.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }else if (typevalue == 'LeadspeekLeadsPerday') {
                        this.costagency.local.Monthly.LeadspeekLeadsPerday = this.LeadspeekLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.OneTime.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.OneTime.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                        this.costagency.local.OneTime.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }else if (typevalue == 'LeadspeekLeadsPerday') {
                        this.costagency.local.OneTime.LeadspeekLeadsPerday = this.LeadspeekLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    if (typevalue == 'LeadspeekPlatformFee') {
                        this.costagency.local.Prepaid.LeadspeekPlatformFee = this.LeadspeekPlatformFee;
                    }else if (typevalue == 'LeadspeekCostperlead') {
                        this.costagency.local.Prepaid.LeadspeekCostperlead = this.LeadspeekCostperlead;
                    }else if (typevalue == 'LeadspeekMinCostMonth') {
                        this.costagency.local.Prepaid.LeadspeekMinCostMonth = this.LeadspeekMinCostMonth;
                    }else if (typevalue == 'LeadspeekLeadsPerday') {
                        this.costagency.local.Prepaid.LeadspeekLeadsPerday = this.LeadspeekLeadsPerday;
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
                    }else if (typevalue == 'LocatorLeadsPerday') {
                        this.costagency.locator.Weekly.LocatorLeadsPerday = this.LocatorLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    if (typevalue == 'LocatorPlatformFee') {
                        this.costagency.locator.Monthly.LocatorPlatformFee = this.LocatorPlatformFee;
                    }else if (typevalue == 'LocatorCostperlead') {
                        this.costagency.locator.Monthly.LocatorCostperlead = this.LocatorCostperlead;
                    }else if (typevalue == 'LocatorMinCostMonth') {
                        this.costagency.locator.Monthly.LocatorMinCostMonth = this.LocatorMinCostMonth;
                    }else if (typevalue == 'LocatorLeadsPerday') {
                        this.costagency.locator.Monthly.LocatorLeadsPerday = this.LocatorLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    if (typevalue == 'LocatorPlatformFee') {
                        this.costagency.locator.OneTime.LocatorPlatformFee = this.LocatorPlatformFee;
                    }else if (typevalue == 'LocatorCostperlead') {
                        this.costagency.locator.OneTime.LocatorCostperlead = this.LocatorCostperlead;
                    }else if (typevalue == 'LocatorMinCostMonth') {
                        this.costagency.locator.OneTime.LocatorMinCostMonth = this.LocatorMinCostMonth;
                    }else if (typevalue == 'LocatorLeadsPerday') {
                        this.costagency.locator.OneTime.LocatorLeadsPerday = this.LocatorLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    if (typevalue == 'LocatorPlatformFee') {
                        this.costagency.locator.Prepaid.LocatorPlatformFee = this.LocatorPlatformFee;
                    }else if (typevalue == 'LocatorCostperlead') {
                        this.costagency.locator.Prepaid.LocatorCostperlead = this.LocatorCostperlead;
                    }else if (typevalue == 'LocatorMinCostMonth') {
                        this.costagency.locator.Prepaid.LocatorMinCostMonth = this.LocatorMinCostMonth;
                    }else if (typevalue == 'LocatorLeadsPerday') {
                        this.costagency.locator.Prepaid.LocatorLeadsPerday = this.LocatorLeadsPerday;
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
            }else if (type == 'enhance') {

                if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.Weekly.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.Weekly.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                        this.costagency.enhance.Weekly.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }else if (typevalue == 'EnhanceLeadsPerday') {
                        if(Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.errMinLeadDay = true;
                        } else {
                            this.errMinLeadDay = false;
                        }
                        this.costagency.enhance.Weekly.EnhanceLeadsPerday = this.EnhanceLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.Monthly.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.Monthly.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                        this.costagency.enhance.Monthly.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }else if (typevalue == 'EnhanceLeadsPerday') {
                        if(Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.errMinLeadDay = true;
                        } else {
                            this.errMinLeadDay = false;
                        }
                        this.costagency.enhance.Monthly.EnhanceLeadsPerday = this.EnhanceLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.OneTime.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.OneTime.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                        this.costagency.enhance.OneTime.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }else if (typevalue == 'EnhanceLeadsPerday') {
                        if(Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.errMinLeadDay = true;
                        } else {
                            this.errMinLeadDay = false;
                        }
                        this.costagency.enhance.OneTime.EnhanceLeadsPerday = this.EnhanceLeadsPerday;
                    }
                }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                    if (typevalue == 'EnhancePlatformFee') {
                        this.costagency.enhance.Prepaid.EnhancePlatformFee = this.EnhancePlatformFee;
                    }else if (typevalue == 'EnhanceCostperlead') {
                        this.costagency.enhance.Prepaid.EnhanceCostperlead = this.EnhanceCostperlead;
                    }else if (typevalue == 'EnhanceMinCostMonth') {
                        this.costagency.enhance.Prepaid.EnhanceMinCostMonth = this.EnhanceMinCostMonth;
                    }else if (typevalue == 'EnhanceLeadsPerday') {
                        if(Number(this.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.errMinLeadDay = true;
                        } else {
                            this.errMinLeadDay = false;
                        }
                        this.costagency.enhance.Prepaid.EnhanceLeadsPerday = this.EnhanceLeadsPerday;
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
                this.LeadspeekLeadsPerday = this.costagency.local.Weekly.LeadspeekLeadsPerday;

                this.LocatorPlatformFee  = this.costagency.locator.Weekly.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.Weekly.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.Weekly.LocatorMinCostMonth;
                this.LocatorLeadsPerday = this.costagency.locator.Weekly.LocatorLeadsPerday;

                this.EnhancePlatformFee  = this.costagency.enhance.Weekly.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.Weekly.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.Weekly.EnhanceMinCostMonth;
                this.EnhanceLeadsPerday = this.costagency.enhance.Weekly.EnhanceLeadsPerday;
                /** SET VALUE */
            }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                this.txtLeadService = 'monthly';
                this.txtLeadIncluded = 'in that monthly charge';
                this.txtLeadOver ='from the monthly charge';

                /** SET VALUE */
                this.LeadspeekPlatformFee = this.costagency.local.Monthly.LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.costagency.local.Monthly.LeadspeekCostperlead;
                this.LeadspeekMinCostMonth = this.costagency.local.Monthly.LeadspeekMinCostMonth;
                this.LeadspeekLeadsPerday = this.costagency.local.Monthly.LeadspeekLeadsPerday;
                
                this.LocatorPlatformFee  = this.costagency.locator.Monthly.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.Monthly.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.Monthly.LocatorMinCostMonth;
                this.LocatorLeadsPerday = this.costagency.locator.Monthly.LocatorLeadsPerday;
                
                this.EnhancePlatformFee  = this.costagency.enhance.Monthly.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.Monthly.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.Monthly.EnhanceMinCostMonth;
                this.EnhanceLeadsPerday = this.costagency.enhance.Monthly.EnhanceLeadsPerday;
                /** SET VALUE */
            }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                this.txtLeadService = '';
                this.txtLeadIncluded = '';
                this.txtLeadOver ='';

                /** SET VALUE */
                this.LeadspeekPlatformFee = this.costagency.local.OneTime.LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.costagency.local.OneTime.LeadspeekCostperlead;
                this.LeadspeekMinCostMonth = this.costagency.local.OneTime.LeadspeekMinCostMonth;
                this.LeadspeekLeadsPerday = this.costagency.local.OneTime.LeadspeekLeadsPerday;
                
                this.LocatorPlatformFee  = this.costagency.locator.OneTime.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.OneTime.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.OneTime.LocatorMinCostMonth
                this.LocatorLeadsPerday = this.costagency.locator.OneTime.LocatorLeadsPerday;
                
                this.EnhancePlatformFee  = this.costagency.enhance.OneTime.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.OneTime.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.OneTime.EnhanceMinCostMonth
                this.EnhanceLeadsPerday = this.costagency.enhance.OneTime.EnhanceLeadsPerday;
                /** SET VALUE */

            }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {
                this.txtLeadService = 'monthly';
                this.txtLeadIncluded = 'in that monthly charge';
                this.txtLeadOver ='from the monthly charge';

                if (typeof(this.costagency.local.Prepaid) == 'undefined') {
                    this.$set(this.costagency.local,'Prepaid',{
                    LeadspeekPlatformFee: '0',
                    LeadspeekCostperlead: '0',
                    LeadspeekMinCostMonth: '0',
                    LeadspeekLeadsPerday: '50',
                    });
                }

                if (typeof(this.costagency.locator.Prepaid) == 'undefined') {
                    this.$set(this.costagency.locator,'Prepaid',{
                    LocatorPlatformFee: '0',
                    LocatorCostperlead: '0',
                    LocatorMinCostMonth: '0',
                    LocatorLeadsPerday: '10',
                    });
                }

                if (typeof(this.costagency.enhance.Prepaid) == 'undefined') {
                    this.$set(this.costagency.enhance,'Prepaid',{
                    EnhancePlatformFee: '0',
                    EnhanceCostperlead: '0',
                    EnhanceMinCostMonth: '0',
                    EnhanceLeadsPerday: '10',
                    });
                }
                
                /** SET VALUE */
                this.LeadspeekPlatformFee = this.costagency.local.Prepaid.LeadspeekPlatformFee;
                this.LeadspeekCostperlead = this.costagency.local.Prepaid.LeadspeekCostperlead;
                this.LeadspeekMinCostMonth = this.costagency.local.Prepaid.LeadspeekMinCostMonth;
                this.LeadspeekLeadsPerday = this.costagency.local.Prepaid.LeadspeekLeadsPerday;
                
                this.LocatorPlatformFee  = this.costagency.locator.Prepaid.LocatorPlatformFee;
                this.LocatorCostperlead = this.costagency.locator.Prepaid.LocatorCostperlead;
                this.LocatorMinCostMonth = this.costagency.locator.Prepaid.LocatorMinCostMonth
                this.LocatorLeadsPerday = this.costagency.locator.Prepaid.LocatorLeadsPerday;
                
                this.EnhancePlatformFee  = this.costagency.enhance.Prepaid.EnhancePlatformFee;
                this.EnhanceCostperlead = this.costagency.enhance.Prepaid.EnhanceCostperlead;
                this.EnhanceMinCostMonth = this.costagency.enhance.Prepaid.EnhanceMinCostMonth
                this.EnhanceLeadsPerday = this.costagency.enhance.Prepaid.EnhanceLeadsPerday;
                /** SET VALUE */

            }
        },
        paymentTermChange() {
            this.paymentTermStatus();

            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.activeClientCompanyID,
                actionType: 'paymenttermDefault',
                paymenttermDefault: this.selectsPaymentTerm.PaymentTermSelect,
            }).then(response => {
                if (response.result == "success") {
                    this.tableData[this.activeClientCompanyIndex].paymentterm_default = this.selectsPaymentTerm.PaymentTermSelect;
                    
                    this.$notify({
                        type: 'success',
                        message: 'Default Payment Term has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  
                }
            },error => {
                        
            });

        },
        
        sortcolumn: function(a,b) {
            return a.value - b.value;
        },
        sortdate: function(a,b) {
            return new Date(b.created_at) - new Date(a.created_at);
        },
         sortnumber: function(a,b) {
             return a - b;
        },
        checkLeadsType() {
            if (this.selectsAppModule.LeadsLimitSelect == 'Max') {
                this.LeadspeekMaxDateVisible = true;
            }else{
                this.LeadspeekMaxDateVisible = false;
            }
        },
        save_default_price() {
            //console.log(this.costagency);
            this.$store.dispatch('updateGeneralSetting', {
                companyID: this.activeClientCompanyID,
                actionType: 'customsmtpmodule',
                comsetname: 'clientdefaultprice',
                comsetval: this.costagency,
            }).then(response => {
                if (response.result == "success") {
                    this.modals.pricesetup = false;
                    this.selectsAppModule.AppModuleSelect = 'LeadsPeek';
                    this.$notify({
                        type: 'success',
                        message: 'Default Prices has been saved.',
                        icon: 'tim-icons icon-bell-55'
                    });  
                }
            },error => {
                        
            });
        },
        
        rowClicked(row) {
            if (this.isProcessingExpandRow) {
                return;
            }

            this.isProcessingExpandRow = true;

            if (this.expandedRow === row) {
                // Closing expand

                this.$refs.tableData.toggleRowExpansion(row, false);
                this.expandedRow = null;
            } else {
                // Closing all row expand 
                this.tableData.forEach(item => {
                    this.$refs.tableData.toggleRowExpansion(item, false);
                });

                // Exapand new row
                this.$refs.tableData.toggleRowExpansion(row, true);
                this.expandedRow = row;
            }

            this.isProcessingExpandRow = false;
            
           this.clientPhoneNumber.number = row.phonenum
           this.clientPhoneNumber.countryCode = row.phone_country_code
           this.clientPhoneNumber.countryCallingCode = row.phone_country_calling_code
           this.prevSelectedModules = row.selected_side_bar

            let responseSideBar = row.selected_side_bar

            let result = {}
            if(responseSideBar != undefined || responseSideBar != null){
                responseSideBar.forEach(item => {
                result[item.type] = item.status;
            });
            }
            this.selectedModules = result


        },
        tableRowClassName({row, rowIndex}) {
                row.index = rowIndex;
                return 'clickable-rows ClientRow' + rowIndex;
        },
        ClearClientForm() {
            this.ClientCompanyName = '';
            this.ClientFullName = '';
            this.ClientEmail = '';
            this.ClientPhone = '';
        },

        AddEditClient(id) {
            this.clientPhoneNumber={
                number:'',
                countryCode:'US',
                countryCallingCode:'+1'
            },
            $('#showAddEditClient' + id).collapse('show');
        },
        CancelAddEditClient(id) {
            if(id == '') {
              this.ClearClientForm();
              $('#showAddEditClient' + id).collapse('hide');
            }else{
                this.$refs.tableData.toggleRowExpansion(id);
                this.GetClientList(this.currSortBy,this.currOrderBy);
            }
            
        },
        ResendInvitation(id) {
            if(id.id != '') {
                $('#btnResend' + id.id).attr('disabled',true);
                $('#btnResend' + id.id).html('Sending...');   

                /** RESEND THE INVITATION */
                this.$store.dispatch('ResendInvitation', {
                    ClientID: id.id,
                }).then(response => {
                    //console.log(response[0]);
                    this.$refs.tableData.toggleRowExpansion(id);
                    $('#btnResend' + id.id).attr('disabled',false);
                    $('#btnResend' + id.id).html('Resend Invitation'); 

                    this.$notify({
                        type: 'success',
                        message: 'Invitation has been sent!',
                        icon: 'far fa-save'
                    });  

                },error => {
                    $('#btnResend' + id.id).attr('disabled',false);
                    $('#btnResend' + id.id).html('Resend Invitation'); 

                    this.$notify({
                        type: 'primary',
                        message: 'Sorry there is something wrong, pleast try again later',
                        icon: 'fas fa-bug'
                    }); 
                });
                /** RESEND THE INVITATION */
            }
        },
        async ProcessAddEditClient(id) {
            
            if(this.ClientFullName != '' && this.ClientEmail != '' && id == '') {
                /** PROCESS ADD / EDIT ORGANIZATION */
                if(id == '') {
                    $('#btnSave' + id).attr('disabled',true);
                    $('#btnSave' + id).html('Processing...');   
                    
                    var _disabledreceivedemail = 'F';
                    var _disabledaddcampaign = 'F';

                    if (this.disabledreceiveemail) {
                        _disabledreceivedemail = 'T';
                    }

                    if (this.disabledaddcampaign) {
                        _disabledaddcampaign = 'T';
                    }

                    Object.entries(this.customsidebarleadmenu).forEach(([key, sidebar]) => {
                        if (!(key in this.selectedModulesCreate)) {
                            this.$set(this.selectedModulesCreate, key, false);
                        }
                    });
                    let selectedModulesCreateArray = [];
                    for (let key in this.selectedModulesCreate) {
                        selectedModulesCreateArray.push({
                            type: key,
                            status: this.selectedModulesCreate[key]
                        });
                    }

                    /** CREATE CLIENT */
                    this.$store.dispatch('CreateClient', {
                        companyID: this.companyID,
                        idsys: this.$global.idsys,
                        userType:'client',
                        ClientCompanyName: this.ClientCompanyName,
                        ClientFullName: this.ClientFullName,
                        ClientEmail: this.ClientEmail,
                        ClientPhone: this.clientPhoneNumber.number,
                        ClientPhoneCountryCode: this.clientPhoneNumber.countryCode,
                        ClientPhoneCountryCallingCode: this.clientPhoneNumber.countryCallingCode,
                        ClientDomain: this.ClientDomain,
                        disabledreceivedemail: _disabledreceivedemail,
                        disabledaddcampaign: _disabledaddcampaign,
                        selectedmodules: selectedModulesCreateArray,
                    }).then(response => {
                        //console.log(response);
                        if (response.result == 'success') {
                            this.tableData.push(response.data[0]);
                            this.initialSearchFuse();
                            this.ClearClientForm();
                            $('#showAddEditClient' + id).collapse('hide');
                            $('#btnSave' + id).attr('disabled',false);
                            $('#btnSave' + id).html('Save');
                            
                            this.GetClientList(this.currSortBy, this.currOrderBy);
                            this.$notify({
                                type: 'success',
                                message: 'Data has been added successfully',
                                icon: 'far fa-save'
                            }); 
                        }else{
                            $('#btnSave' + id).attr('disabled',false);
                            $('#btnSave' + id).html('Save'); 

                            this.$notify({
                                type: 'primary',
                                message: response.message,
                                icon: 'fas fa-bug'
                            }); 
                        }
                    },error => {
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
                var frmName = 'frmuser' + id.id;
                const isValid = await this.$refs[frmName].validate();

                if (!isValid) {
                    return false;
                }

                if ((id.name != '' && id.email != '') && (typeof id.name != 'undefined' && typeof id.email != 'undefined')) {
                    $('#btnSave' + id.id).attr('disabled',true);
                    $('#btnSave' + id.id).html('Processing...'); 

                    Object.entries(this.customsidebarleadmenu).forEach(([key, sidebar]) => {
                        if (!(key in this.selectedModules)) {
                            this.$set(this.selectedModules, key, false);
                        }
                    });
                    let selectedModulesArray = [];
                    for (let key in this.selectedModules) {
                        selectedModulesArray.push({
                            type: key,
                            status: this.selectedModules[key]
                        });
                    }

                    const prevSelectedModules = this.prevSelectedModules
                    const selectModules = selectedModulesArray
                    const checkModuleChanges = this.checkModuleChanges(prevSelectedModules, selectModules)
                    this.localActiveCampaignId = []
                    this.locatorActiveCampaignId = []
                    this.enhanceActiveCampaignId = []

                    if(checkModuleChanges.length > 0){
                        const promises = checkModuleChanges.map(item => {
                            return this.$store.dispatch('checkCampaignActive', {
                                company_id: id.company_parent,
                                leadspeek_type: item.type,
                                user_type: 'client',
                                user_id: id.id
                            }).then((response) => {
                                if(item.type == 'local'){
                                    this.localActiveCampaignId = response.active_campaign_id
                                }

                                if(item.type == 'locator'){
                                    this.locatorActiveCampaignId = response.active_campaign_id
                                }

                                if(item.type == 'enhance'){
                                    this.enhanceActiveCampaignId = response.active_campaign_id
                                }

                            }, error => {
                                this.$notify({
                                    type: 'primary',
                                    message: 'Server might be busy please try again later',
                                    icon: 'fas fa-bug'
                                });
                            }).catch(error => {
                                this.$notify({
                                    type: 'primary',
                                    message: 'Server might be busy please try again later',
                                    icon: 'fas fa-bug'
                                }); 
                            })
                        })

                        await Promise.all(promises);

                        const htmlLocal = `<div style="font-weight: 600">${this.customsidebarleadmenu.local && this.customsidebarleadmenu.local.name} with campaign id : ${this.localActiveCampaignId.join(', ')}</div>`
                        const htmlLocator = `<div style="font-weight: 600">${this.customsidebarleadmenu.locator && this.customsidebarleadmenu.locator.name} with campaign id : ${this.locatorActiveCampaignId.join(', ')}</div>`
                        const htmlEnhance = `<div style="font-weight: 600">${this.customsidebarleadmenu.enhance && this.customsidebarleadmenu.enhance.name} with campaign id : ${this.enhanceActiveCampaignId.join(', ')}</div>`
                        
                        if(this.localActiveCampaignId.length > 0 || this.locatorActiveCampaignId.length > 0 || this.enhanceActiveCampaignId.length > 0){
                            swal.fire({
                                html: `<div style="display: flex; flex-direction: column;">
                                        <div style="font-size: 100px; color: #f8bb86;">
                                        <i class="el-icon-warning"></i> 
                                        </div>
                                        <div style="margin-bottom: 16px;">
                                        Unable to disable the product due to active campaigns. Please manually stop the campaigns before disabling the product.
                                        </div>
                                        ${this.localActiveCampaignId.length > 0 ? htmlLocal : ""}
                                        ${this.locatorActiveCampaignId.length > 0 ? htmlLocator : ""}
                                        ${this.enhanceActiveCampaignId.length > 0 ? htmlEnhance : ""}
                                    </div>`,
                                confirmButtonClass: 'btn btn-fill',
                                buttonsStyling: false
                            });
                            $('#btnSave' + id.id).attr('disabled',false);
                            $('#btnSave' + id.id).html('Save');
                            return;
                        }
                    }

                    /** UPDATE CLIENT */
                    this.$store.dispatch('UpdateClient', {
                        companyID: id.company_id,
                        idsys: this.$global.idsys,
                        ClientID: id.id,
                        ClientCompanyName: id.company_name,
                        ClientFullName: id.name,
                        ClientEmail: id.email,
                        ClientPhone: id.phonenum,
                        ClientPhone: this.clientPhoneNumber.number,
                        ClientPhoneCountryCode: this.clientPhoneNumber.countryCode,
                        ClientPhoneCountryCallingCode: this.clientPhoneNumber.countryCallingCode,
                        ClientDomain: id.domain,
                        ClientPass: id.newpassword,
                        disabledreceivedemail: id.disabled_receive_email,
                        disabledaddcampaign: id.disable_client_add_campaign,
                        selectedmodules: selectedModulesArray,
                        action: 'client',
                    }).then(response => {
                        //console.log(response[0]);
                        this.$refs.tableData.toggleRowExpansion(id);
                        $('#btnSave' + id.id).attr('disabled',false);
                        $('#btnSave' + id.id).html('Save'); 
                        id.newpassword = '';
                        this.GetClientList(this.currSortBy, this.currOrderBy);
                        this.$notify({
                            type: 'success',
                            message: 'Data has been updated successfully',
                            icon: 'far fa-save'
                        });  
                    },error => {
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
        sortdynamic(column,prop,order) { 
            this.currSortBy = column.prop;
            this.currOrderBy = column.order;
            this.GetClientList(column.prop,column.order);
        },
        searchKeyWord() {
            this.pagination.currentPage = 1;
            this.GetClientList(this.currSortBy,this.currOrderBy);
        },
        GetClientList(sortby,order) {
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
           
            /** GET CLIENT LIST */
            this.tableData = [];
            $('.el-table__empty-text').html('<i class="fas fa-spinner fa-pulse fa-2x d-block"></i>Loading data...');
            this.$store.dispatch('GetClientList', {
                companyID: this.companyID,
                idsys: this.$global.idsys,
                userType:'client',
                sortby: _sortby,
                order: _order,
                searchkey: _searchkey,
                page:this.pagination.currentPage,
                cardStatus: this.filters.cardStatus,
                campaignStatus: this.filters.campaignStatus,
            }).then(response => {
                this.pagination.currentPage = response.current_page? response.current_page : 1
                this.pagination.total = response.total ?response.total : 0
                this.pagination.lastPage = response.last_page ? response.last_page : 0
                this.pagination.from = response.from ? response.from : 0
                this.pagination.to = response.to ? response.to : 0
                
                if (response.data.length == 0) {
                    $('.el-table__empty-text').html('No Data');
                }
                
                for(let i=0;i<response.data.length;i++) {
                    if (response.data[i].phonenum == '') {
                        response.data[i].phonenum = '000-000-0000';
                    }
                }
                this.tableData = response.data
                this.initialSearchFuse()
                if (response.data.length == 0) {
                    $('.el-table__empty-text').html('No Data');
                }
            },error => {
                
            });
            /** GET CLIENT LIST */
        },

        initialSearchFuse() {
            // Fuse search initialization.
            // this.fuseSearch = new Fuse(this.tableData, {
            //     keys: ['company_name','name','email','phonenum','created_at'],
            //     threshold: 0.1
            // });
        },

        handleLike(index, row) {
            swal.fire({
                title: `You liked ${row.name}`,
                buttonsStyling: false,
                icon: 'success',
                customClass: {
                confirmButton: 'btn btn-success btn-fill'
                }
            });
        },
        handleCardSet(index,row) {
            this.cleanCCform();
            this.ClientActiveID = row.id;
            this.LeadspeekCompany = row.company_name;
            this.clientPaymentFailed = false;
            if (row.payment_status == 'failed' && row.failed_campaignid != '' && row.failed_campaignid !== null) {
                this.clientPaymentFailed = true;
                this.failedCampaignNumber = row.failed_campaignid.split('|');
                this.failedInvoiceAmount = row.failed_total_amount.split('|');
            }
            this.termhost = row.company_id;
            this.getCardInfo(row.id);
        },
        resetClientCost() {
            this.selectsAppModule.AppModuleSelect = 'LeadsPeek';
            this.LeadspeekPlatformFee = '0';
            this.LeadspeekCostperlead = '0';
            this.LeadspeekMinCostMonth = '0';
            this.LocatorPlatformFee = '0';
            this.LocatorCostperlead = '0';
            this.LocatorMinCostMonth = '0';
            this.EnhancePlatformFee = '0';
            this.EnhanceCostperlead = '0';
            this.EnhanceMinCostMonth = '0';
            this.lead_FirstName_LastName = '0';
            this.lead_FirstName_LastName_MailingAddress = '0';
            this.lead_FirstName_LastName_MailingAddress_Phone = '0';

            this.costagency.local.Weekly.LeadspeekPlatformFee = '0';
            this.costagency.local.Weekly.LeadspeekCostperlead = '0';
            this.costagency.local.Weekly.LeadspeekMinCostMonth = '0';
            this.costagency.local.Weekly.LeadspeekLeadsPerday = '0';

            this.costagency.local.Monthly.LeadspeekPlatformFee = '0';
            this.costagency.local.Monthly.LeadspeekCostperlead = '0';
            this.costagency.local.Monthly.LeadspeekMinCostMonth = '0';
            this.costagency.local.Monthly.LeadspeekLeadsPerday = '0';

            this.costagency.local.OneTime.LeadspeekPlatformFee = '0';
            this.costagency.local.OneTime.LeadspeekCostperlead = '0';
            this.costagency.local.OneTime.LeadspeekMinCostMonth = '0';
            this.costagency.local.OneTime.LeadspeekLeadsPerday = '0';

            this.costagency.locator.Weekly.LocatorPlatformFee = '0';
            this.costagency.locator.Weekly.LocatorCostperlead = '0';
            this.costagency.locator.Weekly.LocatorMinCostMonth = '0';
            this.costagency.locator.Weekly.LeadspeekLeadsPerday = '0';

            this.costagency.locator.Monthly.LocatorPlatformFee = '0';
            this.costagency.locator.Monthly.LocatorCostperlead = '0';
            this.costagency.locator.Monthly.LocatorMinCostMonth = '0';
            this.costagency.locator.Monthly.LeadspeekLeadsPerday = '0';

            this.costagency.locator.OneTime.LocatorPlatformFee = '0';
            this.costagency.locator.OneTime.LocatorCostperlead = '0';
            this.costagency.locator.OneTime.LocatorMinCostMonth = '0';
            this.costagency.locator.OneTime.LeadspeekLeadsPerday = '0';

            this.costagency.enhance.Weekly.enhancePlatformFee = '0';
            this.costagency.enhance.Weekly.enhanceCostperlead = '0';
            this.costagency.enhance.Weekly.enhanceMinCostMonth = '0';
            this.costagency.enhance.Weekly.LeadspeekLeadsPerday = '0';

            this.costagency.enhance.Monthly.enhancePlatformFee = '0';
            this.costagency.enhance.Monthly.enhanceCostperlead = '0';
            this.costagency.enhance.Monthly.enhanceMinCostMonth = '0';
            this.costagency.enhance.Monthly.LeadspeekLeadsPerday = '0';

            this.costagency.enhance.OneTime.enhancePlatformFee = '0';
            this.costagency.enhance.OneTime.enhanceCostperlead = '0';
            this.costagency.enhance.OneTime.enhanceMinCostMonth = '0';
            this.costagency.enhance.OneTime.LeadspeekLeadsPerday = '0';

            this.costagency.locatorlead.FirstName_LastName = '0';
            this.costagency.locatorlead.FirstName_LastName_MailingAddress = '0';
            this.costagency.locatorlead.FirstName_LastName_MailingAddress_Phone = '0';
        },
        handlePriceSet(index, row) {
            //console.log(row);
            this.activeClientCompanyID = row.company_id; 
            this.activeClientCompanyIndex = index;
            this.LeadspeekCompany = row.company_name;
            this.resetClientCost();
            this.$store.dispatch('getGeneralSetting', {
                companyID: row.company_id,
                settingname: 'clientdefaultprice',
                idSys: this.$global.idsys
            }).then(response => {
                //console.log(response);
                if (response.data != '') {
                    this.costagency = response.data;
                    this.clientMinLeadDayEnhance = response.clientMinLeadDayEnhance;
                    this.selectsPaymentTerm.PaymentTermSelect = row.paymentterm_default;
                    this.paymentTermStatus();

                    if(this.clientMinLeadDayEnhance != '') {
                        if(Number(this.costagency.enhance.Weekly.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.costagency.enhance.Weekly.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                        }
                        if(Number(this.costagency.enhance.Monthly.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.costagency.enhance.Monthly.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                        }
                        if(Number(this.costagency.enhance.OneTime.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.costagency.enhance.OneTime.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                        }
                        if(Number(this.costagency.enhance.Prepaid.EnhanceLeadsPerday) < Number(this.clientMinLeadDayEnhance)) {
                            this.costagency.enhance.Prepaid.EnhanceLeadsPerday = this.clientMinLeadDayEnhance;
                        }
                    }


                    //this.selectsPaymentTerm.PaymentTermSelect = 'Weekly';
                    if (this.selectsPaymentTerm.PaymentTermSelect == 'Weekly') {
                        this.LeadspeekPlatformFee = this.costagency.local.Weekly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.costagency.local.Weekly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.costagency.local.Weekly.LeadspeekMinCostMonth;
                        this.LeadspeekLeadsPerday = this.costagency.local.Weekly.LeadspeekLeadsPerday;

                        this.LocatorPlatformFee  = this.costagency.locator.Weekly.LocatorPlatformFee;
                        this.LocatorCostperlead = this.costagency.locator.Weekly.LocatorCostperlead;
                        this.LocatorMinCostMonth = this.costagency.locator.Weekly.LocatorMinCostMonth;
                        this.LocatorLeadsPerday = this.costagency.locator.Weekly.LocatorLeadsPerday;
                        
                        this.EnhancePlatformFee  = this.costagency.enhance.Weekly.EnhancePlatformFee;
                        this.EnhanceCostperlead = this.costagency.enhance.Weekly.EnhanceCostperlead;
                        this.EnhanceMinCostMonth = this.costagency.enhance.Weekly.EnhanceMinCostMonth;
                        this.EnhanceLeadsPerday = this.costagency.enhance.Weekly.EnhanceLeadsPerday;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Monthly') {
                        this.LeadspeekPlatformFee = this.costagency.local.Monthly.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.costagency.local.Monthly.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.costagency.local.Monthly.LeadspeekMinCostMonth;
                        this.LeadspeekLeadsPerday = this.costagency.local.Monthly.LeadspeekLeadsPerday;

                        this.LocatorPlatformFee  = this.costagency.locator.Monthly.LocatorPlatformFee;
                        this.LocatorCostperlead = this.costagency.locator.Monthly.LocatorCostperlead;
                        this.LocatorMinCostMonth = this.costagency.locator.Monthly.LocatorMinCostMonth;
                        this.LocatorLeadsPerday = this.costagency.locator.Monthly.LocatorLeadsPerday;

                        this.EnhancePlatformFee  = this.costagency.enhance.Monthly.EnhancePlatformFee;
                        this.EnhanceCostperlead = this.costagency.enhance.Monthly.EnhanceCostperlead;
                        this.EnhanceMinCostMonth = this.costagency.enhance.Monthly.EnhanceMinCostMonth;
                        this.EnhanceLeadsPerday = this.costagency.enhance.Monthly.EnhanceLeadsPerday;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'One Time') {
                        this.LeadspeekPlatformFee = this.costagency.local.OneTime.LeadspeekPlatformFee;
                        this.LeadspeekCostperlead = this.costagency.local.OneTime.LeadspeekCostperlead;
                        this.LeadspeekMinCostMonth = this.costagency.local.OneTime.LeadspeekMinCostMonth;
                        this.LeadspeekLeadsPerday = this.costagency.local.OneTime.LeadspeekLeadsPerday;

                        this.LocatorPlatformFee  = this.costagency.locator.OneTime.LocatorPlatformFee;
                        this.LocatorCostperlead = this.costagency.locator.OneTime.LocatorCostperlead;
                        this.LocatorMinCostMonth = this.costagency.locator.OneTime.LocatorMinCostMonth;
                        this.LocatorLeadsPerday = this.costagency.locator.OneTime.LocatorLeadsPerday;
                        
                        this.EnhancePlatformFee  = this.costagency.enhance.OneTime.EnhancePlatformFee;
                        this.EnhanceCostperlead = this.costagency.enhance.OneTime.EnhanceCostperlead;
                        this.EnhanceMinCostMonth = this.costagency.enhance.OneTime.EnhanceMinCostMonth;
                        this.EnhanceLeadsPerday = this.costagency.enhance.OneTime.EnhanceLeadsPerday;
                    }else if (this.selectsPaymentTerm.PaymentTermSelect == 'Prepaid') {

                        this.LeadspeekPlatformFee = (typeof(this.costagency.local.Prepaid !== 'undefined'))?this.costagency.local.Prepaid.LeadspeekPlatformFee:0;
                        this.LeadspeekCostperlead = (typeof(this.costagency.local.Prepaid !== 'undefined'))?this.costagency.local.Prepaid.LeadspeekCostperlead:0;
                        this.LeadspeekMinCostMonth = (typeof(this.costagency.local.Prepaid !== 'undefined'))?this.costagency.local.Prepaid.LeadspeekMinCostMonth:0;
                        this.LeadspeekLeadsPerday = (typeof(this.costagency.local.Prepaid !== 'undefined'))?this.costagency.local.Prepaid.LeadspeekLeadsPerday:50;

                        this.LocatorPlatformFee  = (typeof(this.costagency.locator.Prepaid !== 'undefined'))?this.costagency.locator.Prepaid.LocatorPlatformFee:0;
                        this.LocatorCostperlead = (typeof(this.costagency.locator.Prepaid !== 'undefined'))?this.costagency.locator.Prepaid.LocatorCostperlead:0;
                        this.LocatorMinCostMonth = (typeof(this.costagency.locator.Prepaid !== 'undefined'))?this.costagency.locator.Prepaid.LocatorMinCostMonth:0;
                        this.LocatorLeadsPerday = (typeof(this.costagency.locator.Prepaid !== 'undefined'))?this.costagency.locator.Prepaid.LocatorLeadsPerday:50;

                        this.EnhancePlatformFee  = (typeof(this.costagency.enhance.Prepaid !== 'undefined'))?this.costagency.enhance.Prepaid.EnhancePlatformFee:0;
                        this.EnhanceCostperlead = (typeof(this.costagency.enhance.Prepaid !== 'undefined'))?this.costagency.enhance.Prepaid.EnhanceCostperlead:0;
                        this.EnhanceMinCostMonth = (typeof(this.costagency.enhance.Prepaid !== 'undefined'))?this.costagency.enhance.Prepaid.EnhanceMinCostMonth:0;
                        this.EnhanceLeadsPerday = (typeof(this.costagency.enhance.Prepaid !== 'undefined'))?this.costagency.enhance.Prepaid.EnhanceLeadsPerday:50;

                    }

                    this.lead_FirstName_LastName = this.costagency.locatorlead.FirstName_LastName;
                    this.lead_FirstName_LastName_MailingAddress  = this.costagency.locatorlead.FirstName_LastName_MailingAddress;
                    this.lead_FirstName_LastName_MailingAddress_Phone = this.costagency.locatorlead.FirstName_LastName_MailingAddress_Phone;
                }else{
                    this.selectsPaymentTerm.PaymentTermSelect = response.dpay;
                    this.paymentTermStatus();
                }
                this.modals.pricesetup = true;
            },error => {
                    
            });
            
        },
        handleDelete(index, row) {
            //console.log('Row: ' + index);
            swal.fire({
                title: 'Are you sure want to delete this?',
                text: `You won't be able to revert this!`,
                icon: '',
                showCancelButton: true,
                customClass: {
                confirmButton: 'btn btn-fill mr-3',
                cancelButton: 'btn btn-danger btn-fill'
                },
                confirmButtonText: 'Yes, delete it!',
                buttonsStyling: false
        }).then(result => {
                if (result.value) {
                    /** REMOVE ORGANIZATION */
                    
                        this.$store.dispatch('RemoveClient', {
                            companyID: row.company_id,
                            userID: row.id,
                        }).then(response => {
                            //console.log(response)
                            if(response.data.result == "success") {
                                this.deleteRow(row);
                                swal.fire({
                                    title: 'Deleted!',
                                    text: `You deleted ${row.name}`,
                                    icon: 'success',
                                    confirmButtonClass: 'btn btn-default btn-fill',
                                    buttonsStyling: false
                                });
                            }else{
                                this.$notify({
                                    type: 'primary',
                                    message: response.data.message,
                                    icon: 'fas fa-bug'
                                }); 
                            }
                        },error => {
                            
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
        processRechargeExisting(_act) {
            this.$store.dispatch('processUpdateCard', {
                tokenid: '',
                cardholder: '',
                address: '',
                city: '',
                state: '',
                country: '',
                zip: '',
                usrID: this.ClientActiveID,
                companyParentID: this.companyID,
                action: _act,
            }).then(response => {
                this.$refs.formCC.reset(); 

                if(response.result == 'success' && response.msg != '') {
                    $('#btnRetryExistCard').html('Retry charge with existing card');
                    $('#btnRetryExistCard').attr('disabled',false);
                    $('#btnUpdateAndCharge').html('save and charge amount due');
                    $('#btnUpdateAndCharge').attr('disabled', false);
                    this.GetClientList();

                    swal.fire({
                        icon: "success",
                        title: response.title,
                        text: response.msg
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Refresh the page when the user clicks "OK"
                            // location.reload();
                        }
                    });
                    
                }else{
                    $('#btnRetryExistCard').html('Retry charge with existing card');
                    $('#btnRetryExistCard').attr('disabled',false);
                    $('#btnUpdateAndCharge').html('save and charge amount due');
                    $('#btnUpdateAndCharge').attr('disabled', false);

                    _this.$refs.formCC.reset();  
                    _this.cleanCCform();
                    
                    swal.fire({
                        icon: "error",
                        title: response.title,
                        text: response.msg
                    });
                     
                    // this.modals.cardsetup = false;
                    // this.modals.cardupdate = false;
                    // this.cardretrychargeTitle = response.title;
                    // this.cardretrychargeTxt = response.msg;
                    // this.modals.cardretrycharge = false;
                    
                }
        
            },error => {
                
            });
        },
        validateCC(action) {
            if (action == 'existcard') {
                $('#btnRetryExistCard').html('Processing...')
                $('#btnRetryExistCard').attr('disabled', true);
                $('#btnUpdateAndCharge').attr('disabled', true);

                /** UPDATE THE CARD INFORMATION */
                this.processRechargeExisting(action);
                /** UPDATE THE CARD INFORMATION */
                return false;
            }

            if(this.selects.country === '' || this.selects.state === ''){
                this.showErrorMessage = true
            }
            return this.$refs.formCC.validate().then(res => {
                if(res) {
                    this.agreeTermStat = false;
                    if (this.agreeTerm == false) {
                        this.agreeTermStat = true;
                        return false;
                    }

                    if (this.selects.state != '' && this.selects.country != '') {
                        $('#btnupdcc').attr('disabled',true);
                        $('#btnupdcc').html('Processing...'); 
                        $('#btnUpdateAndCharge').html('Processing...');
                        $('#btnUpdateAndCharge').attr('disabled', true);
                        $('#btnRetryExistCard').attr('disabled', true);
                        /** UPDATE THE CARD INFORMATION */
                        if (this.btncardupdate === true) {
                            this.processUpdateCard(action);
                            // console.log('CC updated!');
                        }else{
                            this.processAddCard(action);
                            // console.log('CC Added!');
                        }
                        /** UPDATE THE CARD INFORMATION */
                    }else{
                        this.showErrorMessage = true
                    }

                }
            });
            
        },
        processAddCard(action) {
            var _store = this.$store;
            var _this = this;
            var _act = action;

            return new Promise((resolve, reject) => {
                _stripe.createToken(_cardElement).then(function(result) {
                    if(result.error) {
                        //console.log(result.error.message);
                        $('#carderror').html('<small style="color:red"><sup>*</sup>' + result.error.message + '</small>');
                        $('#carderror').show();
                    }else{
                        $('#carderror').hide();
                        _tokenid = result.token.id;
                        _store.dispatch('paymentcustomersetup', {
                            tokenid: _tokenid,
                            cardholder: _this.cardholdername,
                            address: _this.billingaddress,
                            city: _this.city,
                            state: _this.selects.state,
                            country: _this.selects.country,
                            zip: _this.zipcode,
                            usrID: _this.ClientActiveID,
                            action: _act,
                        }).then(response => {
                            //console.log(response.result)
                            if(response.result == 'success' && response.msg != '') {
                                _this.$refs.formCC.reset();  
                                _this.modals.cardsetup = false;
                                _this.GetClientList();
                                $('#btnupdcc').attr('disabled',false);
                                $('#btnupdcc').html('Update'); 
                                $('#btnRetryExistCard').html('Retry charge with existing card');
                                $('#btnRetryExistCard').attr('disabled',false);
                                $('#btnUpdateAndCharge').html('save and charge amount due');
                                $('#btnUpdateAndCharge').attr('disabled', false);
                                swal.fire({
                                    icon: "success",
                                    title: response.title,
                                    text: response.msg
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Refresh the page when the user clicks "OK"
                                        // location.reload();
                                    }
                                });
                                // _this.modals.cardupdate = true;
                                // _this.modals.cardretrycharge = false;
                                // $('#btnupdcc').attr('disabled',false);
                                // $('#btnupdcc').html('Update'); 
                            }else{
                                _this.$refs.formCC.reset();  
                                //_this.modals.cardsetup = false;
                                // _this.modals.cardupdate = false;
                                // _this.cardretrychargeTitle = response.title;
                                // _this.cardretrychargeTxt = response.msg;
                                // _this.modals.cardretrycharge = true;
                                _this.cleanCCform();
                                $('#btnupdcc').attr('disabled',false);
                                $('#btnupdcc').html('Update'); 
                                swal.fire({
                                    icon: "error",
                                    title: response.title,
                                    text: response.msg
                                });
                            }
                        },error => {
                            
                        });
                    }
                });
            });
        },
        processUpdateCard(action) {
            var _store = this.$store;
            var _this = this;
            var _act = action;

            return new Promise((resolve, reject) => {
              _stripe.createToken(_cardElement).then(function(result) {
                  if(result.error) {
                      //console.log(result.error.message);
                      $('#btnupdcc').html('Update');
                      $('#btnupdcc').attr('disabled',false);
                      $('#btnUpdateAndCharge').html('save and charge amount due');
                      $('#btnUpdateAndCharge').attr('disabled',false);
                      $('#btnRetryExistCard').html('Retry charge with existing card');
                      $('#btnRetryExistCard').attr('disabled', false);
                      $('#carderror').html('<small style="color:red"><sup>*</sup>' + result.error.message + '</small>');
                      $('#carderror').show();
                  }else{
                      $('#carderror').hide();
                      _tokenid = result.token.id;
                      _store.dispatch('processUpdateCard', {
                          tokenid: _tokenid,
                          cardholder: _this.cardholdername,
                          address: _this.billingaddress,
                          city: _this.city,
                          state: _this.selects.state,
                          country: _this.selects.country,
                          zip: _this.zipcode,
                          usrID: _this.ClientActiveID,
                          companyParentID: _this.companyID,
                          action: _act,
                      }).then(response => {
                          //console.log(response.result)
                          if(response.result == 'success' && response.msg != '') {
                            _this.$refs.formCC.reset();  
                            _this.modals.cardsetup = false;
                            _this.GetClientList();
                            $('#btnupdcc').attr('disabled',false);
                            $('#btnupdcc').html('Update'); 
                            $('#btnRetryExistCard').html('Retry charge with existing card');
                            $('#btnRetryExistCard').attr('disabled',false);
                            $('#btnUpdateAndCharge').html('save and charge amount due');
                            $('#btnUpdateAndCharge').attr('disabled', false);
                            swal.fire({
                                icon: "success",
                                title: response.title,
                                text: response.msg
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Refresh the page when the user clicks "OK"
                                    // location.reload();
                                }
                            });
                            // _this.modals.cardupdate = true;
                            // _this.modals.cardretrycharge = false;
                            // $('#btnupdcc').attr('disabled',false);
                            // $('#btnupdcc').html('Update'); 
                          }else {
                            _this.$refs.formCC.reset();  
                            //_this.modals.cardsetup = false;
                            // _this.modals.cardupdate = false;
                            // _this.cardretrychargeTitle = response.title;
                            // _this.cardretrychargeTxt = response.msg;
                            // _this.modals.cardretrycharge = true;
                            _this.cleanCCform();
                            $('#btnupdcc').attr('disabled',false);
                            $('#btnupdcc').html('Update'); 
                            $('#btnRetryExistCard').html('Retry charge with existing card');
                            $('#btnRetryExistCard').attr('disabled',false);
                            $('#btnUpdateAndCharge').html('save and charge amount due');
                            $('#btnUpdateAndCharge').attr('disabled', false);
                            swal.fire({
                              icon: "error",
                              title: response.title,
                              text: response.msg
                            });
                          }
                      },error => {
                          
                      });
                  }
              });
            });
        },
        initstripelib() {
            var chkload = this.$global.stripescriptload;
            if (chkload === false) {
                this.$global.initStripeScriptLoad(true);
                $.getScript( "https://js.stripe.com/v3/" )
                .done(function( script, textStatus ) {
                    initcreditcard();
                })
                .fail(function( jqxhr, settings, exception ) {
                    //$( "div.log" ).text( "Triggered ajaxError handler." );
                });
            }else{
                initcreditcard();
            }
        
        },
        cleanCCform() {
          this.cardholdername = '';
          this.billingaddress = '';
          this.city = '';
          this.selects.state = '';
          this.zipcode = '';
          this.agreeTerm = false;
          this.$refs.formCC.reset();  
        //   this.currCardHolder = '';
        //   this.currCardlastdigit = '';
        //   this.currCardType = '';
          if (typeof(_cardElement) == "undefined") {
            this.cardfailetoload = true;
          }else{
            _cardElement.clear();
          }
        },
        getStateList() {
            this.$store.dispatch('getStateList').then(response => {
                this.selects.statelist = response.params
            },error => {
                
            });
        },

        getCardInfo(_usrID) {
            this.$store.dispatch('getCardInfo', {
                    usrID: _usrID,
            }).then(response => {
               //console.log(response.params);
               this.btncardupdate = false;
               if (response.params != '') {
                    this.currCardHolder = (response.params.name != null)?response.params.name:''
                    this.currCardlastdigit = (response.params.last4 != null)?response.params.last4:''
                    this.currCardType = (response.params.brand != null)?response.params.brand:''
                    this.btncardupdate = true;
               } else {
                    this.currCardHolder = ''
                    this.currCardlastdigit =''
                    this.currCardType = ''
               }
               this.modals.cardsetup = true;
            },error => {
                
            });
        },
        onExpandClick(row){
            this.rowClicked(row);
        },
        checkModuleChanges(prevModules, currentModules){
            const changedModules = currentModules.reduce((acc, current) => {
                const prev = prevModules.find(prev => prev.type === current.type);

                if (prev && prev.status === true && current.status === false) {
                acc.push({ type: current.type });
                }

                return acc;
            }, []);

            return changedModules;
        },
        handleFormatCurrency(type, field){
            const validInput = /^[0-9]*(\.[0-9]*)?$/;

            if(!validInput.test(this[field])){
                this[field] = 0
            }

            if(field == 'EnhanceLeadsPerday'){
                this.validateMinLead()
            }

            const formatNumber = formatCurrencyUSD(this[field])
            this[field] = formatNumber
            this.set_fee(type, field)
        },
        restrictInput(event) {
            const char = event.key;

            if (['Backspace', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(char)) {
                return; 
            }

            if (!char.match(/[0-9]/) && char !== '.') {
                event.preventDefault();
            }
        },
        cssDefaultModuleByLength(){
            const lengthDefaultModule = Object.values(this.moduleAgency)
            
            if (lengthDefaultModule.length == 1){
                return 'col-sm-12 col-md-12 col-lg-12'
            } else if (lengthDefaultModule.length == 2){
                return 'col-sm-12 col-md-6 col-lg-6'
            } else if (lengthDefaultModule.length == 3){
                return 'col-sm-6 col-md-4 col-lg-4'
            } else {
                return 'col-sm-6 col-md-4 col-lg-4'
            }
        },
    },

    mounted() {
        const userData = this.$store.getters.userData;
        this.companyID = userData.company_id;
         _sppubkey = this.$global.sppubkey;
         _this = this;
        this.selectsPaymentTerm.PaymentTerm = this.$global.rootpaymentterm;
        this.GetClientList();
        this.cardfailetoload = false;
        this.moduleAgency = this.$global.agencyfilteredmodules

        this.customsidebarleadmenu = this.$global.agencyfilteredmodules;
        

        this.selectedModulesCreate = this.initializeSelectedModules




        if (typeof(_sppubkey) == "undefined" || _sppubkey == "") {
            this.cardfailetoload = true;
        }else{
            this.initstripelib();
        }
        this.getStateList();
        this.reset();
        
        $('.phonenum input').usPhoneFormat({
            format: 'xxx-xxx-xxxx',
        });
    },

    watch: {
        'modals.whitelist': function(newValue) {
            if(!newValue) {
                this.supressionProgress = [];
                clearTimeout(this.supressionTimeout);
                clearInterval(this.supressionInterval);
            }
        },
        'modals.pricesetup': function(newValue) {
            if(newValue){
                this.$nextTick(() => {
                    if (this.moduleAgency.local != undefined) {
                        this.selectsAppModule.AppModuleSelect = 'LeadsPeek';
                    } else if (this.moduleAgency.locator != undefined) {
                        this.selectsAppModule.AppModuleSelect = 'locator';
                    } else if (this.moduleAgency.enhance != undefined) {
                        this.selectsAppModule.AppModuleSelect = 'enhance';
                    }
                });
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
        //         for(let i=0;i<result.length;i++) {
        //             temp.push(result[i].item);
        //             //console.log(result[i].item);
        //         }

        //         if (result.length == 0) {
        //             if (this.tableData.length > 0) {
        //                 this.tableDataOri = [];
        //                 this.tableDataOri = this.tableData;
        //             }
        //             this.tableData = [];
        //         }
                
        //     }else{
        //         this.tableData = this.tableDataOri;
        //     }
        //     this.searchedData = temp;
        // }
    },
    
}

function initcreditcard() {
  if (!$('#card-element').length) {
    return
  }
	_stripe = Stripe(_sppubkey);
	/** ATTACHED CARD ELEMENTS */
   _elements = _stripe.elements();
    _cardElement = _elements.create('card', {
    hidePostalCode: true,    
    style: {
        base: {
                color: 'rgba(82, 95, 127, 0.8)',
                fontSize: '16px',
                fontFamily: '"Open Sans", sans-serif',
                fontSmoothing: 'antialiased',
                '::placeholder': {
                  color: 'rgba(82, 95, 127, 0.3)',
                },
            },
            invalid: {
                color: '#e5424d',
                ':focus': {
                  color: 'rgba(82, 95, 127, 0.3)',
                },
            },
        },
    });

    _this.cardfailetoload = false;
    
    if (typeof(_cardElement) == "undefined") {
      _this.cardfailetoload = true;
    }
    //var cardElement = elements.create('card');
    _cardElement.mount('#card-element');
    /** ATTACHED CARD ELEMENTS */

    /** CARD ELEMENT VALIDATION */
    _cardElement.on('change', function(event) {
        if (event.error) {
          console.log('Card Error :' + event.error.message);
          $('#carderror').html('<small style="color:red"><sup>*</sup>' + event.error.message + '</small>');
          $('#carderror').show();
        } else {
          $('#carderror').hide();
        }
    });

	
}

</script>
<style>
#modalSetCard .select-primary.el-select .el-input input {
     color: rgba(82, 95, 127, 0.8);
}

#modalSetCard .form-control {
    color: rgba(82, 95, 127, 0.8);
    border: 1px solid #525f7f;
}

#modalSetCard {
    /*top:-10%;*/
}

#modalSetCard .card-border {
  width: 95% !important;
  left: 15px;
  line-height: 1.5;
  -webkit-appearance: none;
  -moz-appearance: none;
  border: 1px solid #525f7f;
  border-radius:4px;
  padding: 8px 12px 6px 12px;
  margin:8px 0px 4px -1px;
  height:36px;
  box-sizing: border-box;
  transform: translateZ(0);
  -webkit-user-select:text;
}

#modalSetPrice input:read-only {
    background-color: white;
}

#modalSetPrice .el-input__prefix, #modalSetPrice .el-input__suffix {
    color: #525f7f;
}

#modalSetPrice .leadlimitdate {
    width: auto !important;
}

#modalSetPrice .el-input__inner {
    background-color: transparent;
    border-width: 1px;
    border-color: #2b3553;
    color: #942434;
}

.frmSetCost .input-group .input-group-prepend .input-group-text i {
    color: #525f7f;
}

.frmSetCost .input-group input[type=text], .frmSetCost input[type=text],.frmSetCost .input-group .input-group-prepend .input-group-text {
    color: #525f7f;
    border-color: #525f7f;
}
.black-center input[type=text] {
    color: #525f7f;
    text-align: center;
}
.clickable-rows td {
    cursor: pointer;
}
/* .clickable-rows .el-table, .el-table__expanded-cell {
    background-color:#1e1e2f;
} */
.clickable-rows tr .el-table__expanded-cell {
    cursor: default;
}

.iconcampaign {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: inline-flex;
    justify-content: center;
    align-items: center;
    color: #fff; /* You can change the text color as needed */
    font-size: 11px;
    font-weight: bold;
    font-style: normal;
    margin-right: 5px;
}

.iconcampaign + .iconcampaign {
  margin-left: 5px;
}

.cmpActive {
    background-color: green; /* You can change the background color as needed */
}

.cmpPauseStop {
    background-color: #646769; /* You can change the background color as needed */
}

.icons-container {
    display: inline-block;
}
.integratios-list-wrapper{
    gap:8px;
}
.integrations__modal-item-wrapper{
    border-radius: 8px;
    cursor: pointer;
    box-sizing: border-box;
    padding:16px;
}
.integrations__modal-item{
    width:96px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
    justify-content: center;
}
/* .integrations__modal-item.--active{
    
} */
.integrations__modal-item-wrapper:hover{
    background-color: #5e72e4 !important;
    color: #f4f5f7 !important;
}
.integrarion-brand-name{
    font-size:12px;
    line-height:16px;
    font-weight:400px;
}
.integrations-modal-footer-wrapper{
    padding:12px 24px;
    width: 100%;
}
.integrations-modal-footer-wrapper .d-flex{
    gap:16px;
}
.financial-modules-wrapper .financial-modules-item{
    font-size: 1.2rem;
    cursor: pointer;
    border-radius: 8px;
    transition: all 0.2s ease;
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
    gap: 0px;
}

.campaign-cost-input.form-group .form-control:not(.has-danger){
    border: none !important;
}
.campaign-cost-input.el-date-editor .el-input__inner{
    border-color: black;
}
.campaign-cost-input.el-date-editor .el-input__inner:hover{
    border-color:var(--red);
}
.campaign-cost-input.form-group{
       border:1px solid black;
        color: #525f7f;
        border-radius: 4px;
  
}
.campaign-cost-input.form-group:hover{
      border-color:var(--red);
  
}
.campaign-cost-input .input-group .input-group-text{
    border: none;
        color: #525f7f;

}
.campaign-cost-input{
    border: none;
}

.dropdown-hidden {
  display: none !important;
}

.menu__prices {
  padding: 8px 16px;
  border-radius: 4px;
  color: gray;
  cursor: pointer;
  font-weight: 600;
  font-size: 18px;
  border: 1px solid transparent;
  transition: border 300ms ease;
}

.active__menu__prices {
  color: black;
  border: 1px solid #222a42;
}

.country-region-select-wrapper {
    margin-bottom: 10px;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-align: start;
    -ms-flex-align: start;
    align-items: flex-start;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
}

.country-region-select-wrapper .country-label {
    font-size: .80143rem;
    margin-bottom: 5px;
    display: block;
}

.country-region-select-wrapper .country-region-select {
    background: transparent;
    width: 100%;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    border: 1px solid var(--input-border-color);
    outline: none !important;
    color: var(--primary-input-text-color);
}

.country-region-select-wrapper .country-region-select option{
    padding: 10px 18px;
    color: black !important;
}

.input__client__management .el-input__inner {
    padding-left: 30px;
    padding-right: 30px;
}

.client__management__card__credit {
    width: 50%;
    margin: 0 auto;
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

@media (max-width: 992px) {
    .client__management__card__credit {
        width: 75%;
    }
}

@media (max-width: 576px) {
    .client__management__card__credit {
        width: 100%;
    }
}

@media (max-width: 469px) {
    .integratios-list-wrapper {
        justify-content: center;
    }
}
</style>