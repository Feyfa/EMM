<template>
  <div>
    <div v-click-outside="closeSidebar" class="sidebar bg-bar-color" :data="backgroundColor" :style="{background:this.$global.globalSidebarBgColor}" style="z-index: 99;">
      <div class="sidebar-wrapper" ref="sidebarScrollArea">
        <div class="logo">
          <div style="width:100%">
            <a href="#" class="simple-text logo-normal text-center">
          
              <img id="companylogosidebar" style="max-width:125px" :src="this.$global.globalCompanyPhoto" alt="app-logo" />
            </a>
            <div id="sidebarCompanyName" class="simple-text logo-normal text-center text-bar-color" :style="{color:this.$global.globalTextColor}" style="word-wrap: break-word;white-space: break-spaces;">
             {{ this.$global.globalCompanyName }}
            </div>
            <!-- <small class="company-select-tag text-bar-color" v-if="this.$global.settingMenuShow && !this.$global.systemUser && this.$global.creditcardsetup && this.$global.stripeaccountconnected" :style="{color:this.$global.globalTextColor}" style="display:inline-block;width:100%;text-align:center;font-size:100%;padding-top:5px;font-size:12px">View By Client Name :</small> -->
            <!-- <div v-if="this.$global.settingMenuShow && !this.$global.systemUser && this.$global.creditcardsetup && this.$global.stripeaccountconnected" style="text-align:center" class="pt-2">
              <select style="font-size:12px;width:80%" v-model="selectedGroupCompany" v-on:change="onGroupChange($event)">
                <option  v-for="(item, index) in this.$global.selectsGroupCompany.companyGroupList" :key="index" :value="item.id">{{ item.group_name }}</option>
              </select>
            
            </div> -->
          </div>
        </div>
        <slot></slot>
        <ul class="nav sidebar-item-wrapper text-bar-color" :style="{color:this.$global.globalTextColor}">
          <slot name="links">
            <sidebar-item
              v-for="(link, index) in sidebarLinks"
              :key="link.name + index"
              :link="link"
            >
              <sidebar-item
                v-for="(subLink, index) in link.children"
                :key="subLink.name + index"
                :link="subLink"
              >
              </sidebar-item>
            </sidebar-item>
          </slot>
        </ul>
        <!-- <div style="margin-inline: 30px; display: flex; align-items: center;" class="sidebar__logout__button" @click="logout">
          <i class="fa-solid fa-arrow-right-from-bracket" style="font-size: 20px; width: 34px; color: rgba(255, 255, 255, 0.8);"></i>
          <p class="text-bar-color" style="margin: 0; line-height: 30px; text-transform: uppercase; font-size: 12px;">Log out</p>
        </div> -->
        <div class="sidebar-profile-section-setup"><el-dropdown>
              <span class="el-dropdown-link">
                <div class="user-image"><img id="topnavphoto" :src="this.$global.globalProfilePhoto" /></div><i class="el-icon-caret-bottom el-icon--right"></i>
              </span>
              <el-dropdown-menu slot="dropdown" style="width: 250px;">
                <router-link to="/user/profile-setup-v1" v-if="this.$global.menuUserType != 'sales'">
                  <el-dropdown-item :class="this.$route.path == '/user/profile-setup-v1' && 'dropdown-active'">
                    Profile
                  </el-dropdown-item>
                </router-link>
                <router-link to="/user/card-setting" v-if="(this.$global.creditcardsetup && this.$store.getters.userData.manual_bill == 'F') || (this.$global.creditcardsetup && this.$store.getters.userData.manual_bill == 'T' && this.$store.getters.userData.user_type == 'userdownline')">
                  <el-dropdown-item :class="this.$route.path == '/user/card-setting' && 'dropdown-active'">
                    Card Setting
                  </el-dropdown-item>
                </router-link>
            
                <router-link to="/integrations" v-if="this.$store.getters.userData.user_type === 'client'">
                  <el-dropdown-item :class="this.$route.path == '/integrations' && 'dropdown-active'">
                    Integrations
                  </el-dropdown-item>
                </router-link>
                <a href="#" @click="popWhiteList">
                  <el-dropdown-item>
                    Exclusion List
                  </el-dropdown-item>
                </a>
                <a href="#" @click="popResetPassword">
                  <el-dropdown-item>
                    Change Password
                  </el-dropdown-item>
                </a>
                <a href="#" @click="onOpenModalTwoFactorAuth">
                  <el-dropdown-item>
                    {{ isLoadingGetTwoFactorAuth ? 'Loading...' : 'Two Factor Authentication' }}
                  </el-dropdown-item>
                </a>
                <a href="#" v-on:click.stop.prevent="logout">
                <el-dropdown-item divided>
                  Log out
                </el-dropdown-item>
              </a>
              </el-dropdown-menu>
            </el-dropdown>
            <!-- <span class="logout-btn" :style="{color:this.$global.globalTextColor}" @click="logout">Log out</span> -->
          </div>
      </div>
  
    </div>
         <!-- Modal Update Password -->
         <modal id="modalUpdatePassword" :show.sync="modals.updatepassword" headerClasses="justify-content-center"
        modalContentClasses="modal-updatepassword">
        <h4 slot="header" class="title title-up">Change Password</h4>
        <div class="text-center">
          <img src="/img/reset-password.png" />
        </div>
        <ValidationObserver v-slot="{ handleSubmit }">
          <form ref="frmresetlogin" @submit.prevent="handleSubmit(ProcessResetPassword)">
            <ValidationProvider name="current password" rules="required" v-slot="{ passed, failed, errors }">
              <base-input id="currpassword" v-model="currpassword" type="password" label="Your current password"
                autocomplete="chrome-off" :error="errors[0]" :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
              </base-input>
            </ValidationProvider>

            <ValidationProvider name="new password" rules="required|confirmed:confirmation"
              v-slot="{ passed, failed, errors }">
              <base-input id="newpwd" v-model="password" type="password" label="New Password" autocomplete="chrome-off"
                :error="errors[0]" :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
              </base-input>
            </ValidationProvider>
  
            <ValidationProvider name="Confirm New Password" vid="confirmation" rules="required"
              v-slot="{ passed, failed, errors }">
              <base-input id="confpass" v-model="confirmation" type="password" label="Confirm New Password"
                autocomplete="chrome-off" :error="errors[0]" :class="[{ 'has-success': passed }, { 'has-danger': failed }]">
              </base-input>
            </ValidationProvider>
      
         
  
            <div class="container text-center pt-4">
              <button :disabled="isSubmittingResetPassword" type="submit" class="btn">{{ btnResetPasswordText }}</button>
            </div>
            <small v-if="errorupdatepassword"><span style="color:#ec250d">* Sorry, your current password
                invalid</span></small>
          </form>
        </ValidationObserver>
        <template slot="footer">
  
        </template>
      </modal>
      <!-- Modal Update Password -->
  
      <!-- Modal Two Factor Auth -->
      <modal headerClasses="justify-content-center" :show.sync="modals.twofactorauth" class="modal__2fa">
        <h4 slot="header" class="title title-up">Two Factor Authentication</h4>
        <div class="text-center" style="padding-bottom: 16px;">
          <img src="/img/2fa.jpg" alt="auth" style="max-width: 250px;" />
        </div>
        <div style="display: flex; justify-content: center; padding-bottom: 16px;">
          <p class="text-center" style="font-weight: bold;">We'll now ask for a login code anytime you log in on a device we don't recognize</p>
        </div>
        <div style="display: flex; flex-direction: column; gap: 16px; align-items: center;">
          <el-card style="width: 80%; cursor: pointer; position: relative;" :class="two_factor_auth_type == 'email' ? 'two__fa__active' : ''" @click.native="onHandleSelectTwoFactorAuth('email')" :shadow="two_factor_auth_type == 'email' ? 'always' : 'hover'">
              <div class="row" style="align-items: center;">
                <div style="padding-inline: 16px;">
                  <i class="fa-solid fa-at" style="font-size: xx-large;" :class="two_factor_auth_type == 'email' ? 'two__fa__icon__active' : 'text-gray'"></i>
                </div>
                <div>
                  <p :style="{color: two_factor_auth_type== 'email' ? '#409eff !important' : 'gray !important'}">Email Verification</p>
                </div>
                <span v-if="two_factor_auth_type == 'email'" style="top: 0; right: 0; position: absolute; background-color: green; color: white; padding: 0px 8px; font-size: 12px;">
                  Enabled
                </span>
                <span v-else style="top: 0; right: 0; position: absolute; background-color: gray; color: white; padding: 0px 8px; font-size: 12px;">
                  Disabled
                </span>
            </div>
          </el-card>
          <el-card style="width: 80%; cursor: pointer; position: relative;" :class="two_factor_auth_type == 'google' ? 'two__fa__active' : ''" @click.native="onHandleSelectTwoFactorAuth('google')" :shadow="two_factor_auth_type == 'google' ? 'always' : 'hover'">
              <div class="row" style="align-items: center;">
                <div style="padding-inline: 16px;">
                  <i class="fa-brands fa-google" style="font-size: xx-large;" :class="two_factor_auth_type == 'google' ? 'two__fa__icon__active' : 'text-gray'"></i>
                </div>
                <div>
                  <p :style="{color: two_factor_auth_type== 'google' ? '#409eff !important' : 'gray !important'}">Google Authentication</p>
                </div>
                <span v-if="two_factor_auth_type == 'google'" style="top: 0; right: 0; position: absolute; background-color: green; color: white; padding: 0px 8px; font-size: 12px;">
                  Enabled
                </span>
                <span v-else style="top: 0; right: 0; position: absolute; background-color: gray; color: white; padding: 0px 8px; font-size: 12px;">
                  Disabled
                </span>
            </div>
          </el-card>
          <!-- <el-card style="width: 80%; cursor: pointer;" :class="two_factor_auth_type == 'text_messaging' ? 'two__fa__active' : ''" @click.native="onHandleSelectTwoFactorAuth('text_messaging')" :shadow="two_factor_auth_type == 'text_messaging' ? 'always' : 'hover'">
              <div class="row" style="align-items: center;">
                <div style="padding-inline: 16px;">
                  <i class="el-icon-message" style="font-size: xx-large;" :class="two_factor_auth_type == 'text_messaging' ? 'two__fa__icon__active' : 'text-gray'"></i>
                </div>
                <div>
                  <p :style="{color: two_factor_auth_type== 'text_messaging' ? '#409eff !important' : 'gray !important'}">Text Messaging</p>
                </div>
            </div>
          </el-card> -->
          </div>
          <div style="display:  flex; justify-content: center; margin-top: 31px; margin-bottom: 16px;">
            <button :disabled="isLoadingSaveTwoFactorAuth" type="submit" class="btn" @click="onClickTwoFactorAuth">{{ isLoadingSaveTwoFactorAuth ? 'Loading....' : 'Save' }}</button>
          </div>
        </modal>
        <!-- Modal Two Factor Auth -->
        
        <!-- Modal Children Google Auth -->
        <modal headerClasses="justify-content-center" :show.sync="modals.googleAuth" class="modal__2fa">
          <h4 slot="header" class="title title-up">Google Authenticator</h4>
          <div class="row" v-if="isLoadingGetGoogleTwoFactorAuth">
            <div class="col-12" style="display: flex; justify-content: center;">
              <i class="fas fa-spinner fa-spin" style="font-size: 24px; padding-block: 100px;"></i>
            </div>
          </div>
          <div class="row" v-else>
            <div class="col-12" style="margin-bottom: 24px;">
              <div>
                <p class="text-center" style="font-weight: 600;">Download the app.</p>
                <p class="text-center">For Android: <a style="text-decoration: underline;cursor: pointer;" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en&pli=1" target="_blank">Download Google Authenticator for Android</a></p>
                <p class="text-center">For iPhone:  <a style="text-decoration: underline;cursor: pointer;" href="https://apps.apple.com/us/app/google-authenticator/id388497605" target="_blank">Download Google Authenticator for iPhone</a></p>
              </div>
              <div style="margin-top: 16px; margin-bottom: 16px;">
                <p class="text-center" style="font-weight: 600;">Scan this code with the app</p>
              </div>
              <div style="display: flex; justify-content: center;">
                <img :src="qrCodeUrl" alt="two factor google" v-if="qrCodeUrl" />
              </div>
              <div style="margin-top: 16px;">
                <p class="text-center">Or enter the following code manually:</p>
                <p class="text-center" style="font-weight: 600;">{{ secretKey }}</p>
              </div>
            </div>
            <div class="col-6">
              <base-button class="btn-danger" :disabled="isLoadingGetTwoFactorAuth" @click="onHandleCancelGoogleTwoFactorAuth" style="width: 100%;">{{ isLoadingGetTwoFactorAuth ? 'Loading...' : 'Cancel' }}</base-button>
            </div>
            <div class="col-6">
              <base-button :disabled="isLoadingSaveGoogleTwoFactorAuth" style="width: 100%;" @click="onClickSaveGoogle">{{ isLoadingSaveGoogleTwoFactorAuth ? 'Loading...' : 'Save' }}</base-button>
            </div>
          </div>
      </modal>
      <!-- Modal Children Google Auth -->

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
          <div class="text-center" v-if="optoutfileexist">
            Your current exclusion list: 
            <a :href="optoutpath + '/tools%2Foptout%2Fclientoptoutlist_' + whiteListClientID + '.csv'"
              target="_blank">
              download here
            </a>
          </div>
        </div>
        <a class="mt-2 d-inline-block" @click="purgeSuppressionList('client')" style="cursor: pointer;">
          <i class="fas fa-trash"></i> Purge Existing Records
        </a>
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
import {Dropdown, DropdownMenu, DropdownItem, Switch, Card} from 'element-ui'
import { BaseNav, Modal } from '@/components';
// import SidebarToggleButton from './SidebarToggleButton';
import ThemeButton from '@/components/ThemeButton';
import { extend } from "vee-validate";
import { required, confirmed, min } from "vee-validate/dist/rules";
import swal from 'sweetalert2';

extend("required", required);
extend("confirmed", confirmed);
extend("min", min);
export default {
  components: {
    // SidebarToggleButton,
    //CollapseTransition,
    BaseNav,
    ThemeButton,
    Modal,
    [Dropdown.name]: Dropdown,
    [DropdownMenu.name]: DropdownMenu,
    [DropdownItem.name]: DropdownItem,
    [Switch.name]: Switch,
    [Card.name]: Card,
  },
  name: 'sidebar',
  props: {
    title: {
      type: String,
      default: 'Uncommon Reach',
      description: 'Sidebar title'
    },
    shortTitle: {
      type: String,
      default: 'UR',
      description: 'Sidebar short title'
    },
    logo: {
      type: String,
      default: '/img/icon-ur.png',
      description: 'Sidebar app logo'
    },
    backgroundColor: {
      type: String,
      default: 'red',
      validator: value => {
        let acceptedValues = [
          '',
          'vue',
          'blue',
          'green',
          'orange',
          'red',
          'primary'
        ];
        return acceptedValues.indexOf(value) !== -1;
      },
      description:
        'Sidebar background color (vue|blue|green|orange|red|primary)'
    },
    sidebarLinks: {
      type: Array,
      default: () => [],
      description:
        "List of sidebar links as an array if you don't want to use components for these."
    },
    autoClose: {
      type: Boolean,
      default: true,
      description:
        'Whether sidebar should autoclose on mobile when clicking an item'
    }
  },
  provide() {
    return {
      autoClose: this.autoClose
    };
  },
  data() {
    return {
      selectedGroupCompany: '',
      modals: {
        updatepassword: false,
        twofactorauth: false,
        googleAuth: false,
        whitelist: false,
      },
      password: "",
      currpassword: "",
      confirmation: "",
      btnResetPasswordText: "Change Password",
      isSubmittingResetPassword: false,
      errorupdatepassword: false,
      two_factor_auth: false,
      two_factor_auth_type: null,
      isLoadingGetTwoFactorAuth: false,
      isLoadingSaveTwoFactorAuth: false,
      isLoadingGetGoogleTwoFactorAuth: false,
      isLoadingSaveGoogleTwoFactorAuth: false,
      qrCodeUrl: '',
      secretKey: '',

      optoutpath: process.env.VUE_APP_CDN,
      uploadFieldName: 'clientoptoutfile',
    };
  },
  computed:{
    isMobile() {
      return window.innerWidth <= 768;
    },
    isSaving() {
      return this.currentStatus === STATUS_SAVING;
    },
  },
  methods: {
    popWhiteList() {
      this.modals.whitelist = true;
    },
    onGroupChange: function onGroupChange(event) {
      //alert(this.$global.selectsGroupCompany.companyGroupSelected);
      localStorage.setItem('companyGroupSelected',event.target.value);
      this.$router.go();
    },
    minimizeSidebar() {
      if (this.$sidebar) {
        this.$sidebar.toggleMinimize();
      }
    },
    closeSidebar() {
      if (this.$sidebar && this.isMobile) {
        this.$sidebar.closeSidebar();
      }
    },
    popupdatepasswordsuccess() {
      swal.fire({
        title: 'Change Password',
        text: 'your password has been updated!',
        timer: 2000,
        showConfirmButton: false,
        icon: 'success'
      });
    },
    ProcessResetPassword() {
      this.btnResetPasswordText = 'Change new password...';
      this.isSubmittingResetPassword = true;

      var userdata = this.$store.getters.userData

      this.$store.dispatch('updatePass', {
        usrID: userdata.id,
        newpassword: this.password,
        currpassword: this.currpassword,
      })
        .then(response => {
          if (response.result == 'success') {
            this.modals.updatepassword = false;
            this.popupdatepasswordsuccess();
          } else {
            this.btnResetPasswordText = "Reset Password";
            this.isSubmittingResetPassword = false;
            this.errorupdatepassword = true;
          }

        }, error => {
          this.btnResetPasswordText = "Reset Password";
          this.isSubmittingResetPassword = false;
          this.errorupdatepassword = true;

        })

    },
    popResetPassword() {

      this.password = "";
      this.confirmation = "";
      this.btnResetPasswordText = "Change Password";
      this.isSubmittingResetPassword = false;
      this.errorupdatepassword = false;
      this.modals.updatepassword = true;
    },
    logout() {
      localStorage.removeItem('companyGroupSelected');
      localStorage.removeItem('subdomainAgency');
      localStorage.removeItem('rootcomp');
      this.$global.selectsGroupCompany.companyGroupList = null;
      this.$store.dispatch('destroyToken')
      .then(response => {
        //this.$router.push({ name: 'Login' })
        window.document.location = '/login';
      })
    },
    async onOpenModalTwoFactorAuth(){
      const userData = this.$store.getters.userData
      this.isLoadingGetTwoFactorAuth = true
      await this.$store.dispatch('getSettingTwoFactorAuth', {
        userId: userData.id,
      }).then(response => {
        this.two_factor_auth = response.two_factor_auth
        this.two_factor_auth_type = response.two_factor_auth_type
        this.modals.twofactorauth = true;
      }, error => {
        this.$notify({
          type: 'primary',
          message: error.message,
          icon: 'fas fa-bug'
        })
      })
      this.isLoadingGetTwoFactorAuth = false
    },
    onCloseModalTwoFactorAuth(){
      this.two_factor_auth = false
      this.two_factor_auth_type = null
      this.modals.twofactorauth = false;
    },
    async onClickTwoFactorAuth(){
      if(this.two_factor_auth_type == 'google'){
          this.onCloseModalTwoFactorAuth();
          this.getGoogleTfa();
          this.modals.googleAuth = true
      } else {
        this.isLoadingSaveTwoFactorAuth = true
        const userData = this.$store.getters.userData
  
        const payload = {
          userId: userData.id,
          two_factor_auth: this.two_factor_auth,
          two_factor_auth_type: this.two_factor_auth_type,
        }
  
        await this.$store.dispatch('settingTwoFactorAuth', payload)
        .then(response => {
          this.$notify({
              type: 'success',
              message: response.message,
              icon: 'far fa-save'
          });
        }, error => {
          this.$notify({
            type: 'danger',
            message: error.message,
            icon: 'fa fa-save'
          })
        })
        this.isLoadingSaveTwoFactorAuth = false
        this.onCloseModalTwoFactorAuth()
      }
    },
    onHandleSelectTwoFactorAuth(value){
      if(this.two_factor_auth_type == value){
        this.two_factor_auth = false,
        this.two_factor_auth_type = null
      } else {
        this.two_factor_auth = true
        this.two_factor_auth_type = value
      }
    },
    async onHandleCancelGoogleTwoFactorAuth(){
      await this.onOpenModalTwoFactorAuth();
      this.modals.googleAuth = false;
    },
    async getGoogleTfa(){
      const userData = this.$store.getters.userData
      this.isLoadingGetGoogleTwoFactorAuth = true
      await this.$store.dispatch('getGoogleTfa', {
        userId: userData.id,
        companyId: userData.company_id,
      }).then(response => {
        let qrCodeXml = response.qrCodeUrl;
              const stringToRemoveFront = '<?xml version="1.0" encoding="UTF-8"?>\n';
              const stringToRemoveBack = '\n';

              if (typeof qrCodeXml === 'string') {
                  if (qrCodeXml.startsWith(stringToRemoveFront)) {
                      qrCodeXml = qrCodeXml.slice(stringToRemoveFront.length);
                  }
                  
                  if (qrCodeXml.endsWith(stringToRemoveBack)) {
                      qrCodeXml = qrCodeXml.slice(0, -stringToRemoveBack.length);
                  }
              }

        this.qrCodeUrl = 'data:image/svg+xml;base64,' + btoa(qrCodeXml);
        this.secretKey = response.secretKey
      }, error => {
        this.$notify({
            type: 'danger',
            message: error.message,
            icon: 'fa fa-save'
          })
      })
      this.isLoadingGetGoogleTwoFactorAuth = false
    },
    async onClickSaveGoogle(){
      const userData = this.$store.getters.userData
      this.isLoadingSaveGoogleTwoFactorAuth = true
  
      const payload = {
        userId: userData.id,
        two_factor_auth: true,
        two_factor_auth_type: 'google',
        secretKey: this.secretKey,
      }

      await this.$store.dispatch('settingTwoFactorAuth', payload)
        .then(response => {
          this.$notify({
              type: 'success',
              message: response.message,
              icon: 'far fa-save'
          });
        }, error => {
          this.$notify({
            type: 'danger',
            message: error.message,
            icon: 'fa fa-save'
          })
        })
        this.isLoadingSaveGoogleTwoFactorAuth = false
        this.modals.googleAuth = false
        this.onCloseModalTwoFactorAuth()
    }
  },
  mounted() {
    if(this.$store.state.userData.company_logo != null && this.$store.state.userData.company_logo != '') {
        document.getElementById('companylogosidebar').src = this.$store.state.userData.company_logo
    }else{
        if (this.$store.state.userData.user_type == 'client' && this.$store.state.userData.companyparentlogo != null && this.$store.state.userData.companyparentlogo != '') {
           document.getElementById('companylogosidebar').src = this.$store.state.userData.companyparentlogo;
        }else{
          document.getElementById('companylogosidebar').src = '/img/logoplaceholder.png'
        }
    }
    
    this.selectedGroupCompany = '';
    if (localStorage.getItem('companyGroupSelected') != null) {
       this.selectedGroupCompany = localStorage.getItem('companyGroupSelected');
    }
    //console.log(this.$global.selectsGroupCompany.companyGroupList);
    if (this.$global.selectsGroupCompany.companyGroupList == null || this.$global.selectsGroupCompany.companyGroupList.length == 0) {
      const userData = this.$store.getters.userData;
      /** GET COMPANY GROUP */
      this.$store.dispatch('GetCompanyGroup', {
        companyID: userData.company_id,
        userModule: 'LeadsPeek',
      }).then(response => {
          //console.log(response.length);
          if (response.result == 'success') {
            this.$global.selectsGroupCompany.companyGroupList = response.params;
            this.$global.selectsGroupCompany.companyGroupList.unshift({'id':'','group_name':'View All'});
            
            if (localStorage.getItem('companyGroupSelected') == null && response.params.length > 1) {
              localStorage.setItem('companyGroupSelected',response.params[1].id);
              this.selectedGroupCompany = response.params[1].id;
            }
          }
      },error => {
          
      });
      /** GET COMPANY GROUP */
    }
  },
  beforeDestroy() {
    if (this.$sidebar.showSidebar) {
      this.$sidebar.showSidebar = false;
    }
  }
};
</script>
<style>
@media (min-width: 992px) {
  .navbar-search-form-mobile,
  .nav-mobile-menu {
    display: none;
  }
}
.sidebar-profile-section-setup{
  position: absolute;
  bottom: 24px;
  left: 23px;
  right: 23px;
  display: flex;
  align-items: center;
  justify-content: space-between;

}
.sidebar-profile-section-setup .user-image{
  display: inline-block;
    height: 30px;
    width: 30px;
    border-radius: 50%;
    vertical-align: middle;
    overflow: hidden;
}
.logout-btn{
cursor: pointer;
}

.sidebar__logout__button {
  font-weight: 300;
  transition: all 0.3s;
  cursor: pointer;
}

.sidebar__logout__button:hover {
  font-weight: 600;
}
</style>
