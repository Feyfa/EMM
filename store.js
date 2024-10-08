import Vue from "vue";
import Vuex from "vuex";
import axios from "axios";
import Global from "../plugins/global";

Vue.use(Vuex);
Vue.use(Global);
//axios.defaults.baseURL = 'http://data.exactmatchmarketing.local/api'
//axios.defaults.baseURL = 'https://databeta.exactmatchmarketing.com/api'
axios.defaults.baseURL = process.env.VUE_APP_DATASERVER_URL + "/api";

var global = Vue.prototype.$global;
var _clientIP = localStorage.getItem("clientIP");

function fetchClientIP() {
  fetch("https://www.cloudflare.com/cdn-cgi/trace")
    .then(response => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.text();
    })
    .then(data => {
      const lines = data.split("\n");
      const details = {};
      lines.forEach(line => {
        const [key, value] = line.split("=");
        details[key] = value;
      });

      // Accessing the client's IP address
      const clientIPAddress = details["ip"];
      localStorage.setItem("clientIP", clientIPAddress);
      axios.interceptors.request.use(
        function(config) {
          // Add "clientip" header to the request
          config.headers["clientip"] =
            clientIPAddress +
            "|" +
            Intl.DateTimeFormat().resolvedOptions().timeZone; // Replace with the actual client IP
          config.headers[
            "cltmz"
          ] = Intl.DateTimeFormat().resolvedOptions().timeZone;
          return config;
        },
        function(error) {
          return Promise.reject(error);
        }
      );
    })
    .catch(error => {
      console.error("Error:", error);
    });
}

if (_clientIP === null || _clientIP == "") {
  fetchClientIP();
} else {
  axios.interceptors.request.use(
    function(config) {
      // Add "clientip" header to the request
      config.headers["clientip"] =
        _clientIP + "|" + Intl.DateTimeFormat().resolvedOptions().timeZone; // Replace with the actual client IP
      config.headers[
        "cltmz"
      ] = Intl.DateTimeFormat().resolvedOptions().timeZone;
      return config;
    },
    function(error) {
      return Promise.reject(error);
    }
  );
}

export const store = new Vuex.Store({
  state: {
    token: localStorage.getItem("access_token") || null,
    userData: JSON.parse(localStorage.getItem("userData")) || {},
    resultData: {}
  },
  getters: {
    loggedIn(state) {
      return state.token !== null;
    },
    userCompletedSetup(state) {
      return state.userData.profile_setup_completed !== null;
    },
    userData(state) {
      return state.userData;
    },
    resultData(state) {
      return state.resultData;
    },
    getUserType(state) {
      return state.userData.user_type;
    }
  },
  mutations: {
    retrieveToken(state, token) {
      state.token = token;
    },
    destroyToken(state) {
      state.token = null;
      state.userData = null;
    },
    retrieveUser(state, data) {
      state.userData = data;
    },
    putResultData(state, data) {
      state.resultData = data;
    }
  },
  actions: {
    fetchUserFromLocalStorage() {
      this.state.userData = JSON.parse(localStorage.getItem('userData'));
    },
    getClientMinLeadDayEnhance(context, data) {
      console.log(data.idSys);
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios.get('/configuration/setting/minleaddayenhance', {
          params: {
            idSys: data.idSys
          }
        })
        .then(response => {
          resolve(response);
        })
        .catch(error => {
          reject(error);
        })
      });
    },
    purgeOptoutList(context, data) {
      return new Promise((resolve, reject) => {
      	axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios.delete(`/tools/optout/purge/${data.companyRootId}`)
             .then(response => {
              resolve(response);
             })
             .catch(error => {
              reject(error);
             })
      })
    },
    jobProgress(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios.get(`/leadspeek/suppressionlist/progress?leadspeekID=${data.leadspeekID}&companyId=${data.companyId}&leadspeekApiId=${data.leadspeekApiId}&campaignType=${data.campaignType}`)
             .then(response => {
              resolve(response);
             })
             .catch(error => {
              reject(error);
             })
      });
    },
    stopContinualTopUp(context, data) {
      return new  Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios.put('/leadspeek/client/local/stopcontinualtopup', {
          leadspeek_api_id: data.leadspeek_api_id
        })
        .then(response => {
          resolve(response);
        })
        .catch(error => {
          reject(error);
        });
      });
    },
    getRemainingBalanceLeads(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios.get(`/leadspeek/client/local/remainingbalanceleads/${data.leadspeek_api_id}`)
            .then(response => {
              resolve(response);
            })
            .catch(error => {
              reject(error);
            })
      });
    },
    /** GENERATE REF LINK */
    CheckRefLink(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/general/referralink/check/" + data.refcode)
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    CreateRefLink(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/general/referralink/create", {
            userID: data.userID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** GENERATE REF LINK */

    /** CANCEL SUBSCRIPTION */
    cancelsubscription(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/cancel-subscription", {
            companyID: data.companyID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** CANCEL SUBSCRIPTION */

    /** MANUAL AGENCY BILLING */
    agencyManualBill(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/payment/set-billing-method", {
            companyID: data.companyID,
            manualBill: data.manualBill
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** MANUAL AGENCY BILLING */

    /** GOHIGHLEVEL */
    getGhlUserTags(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/gohighlevel/usertags/" + data.campaignID + "/" + data.companyID
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getGoHighLevelTags(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/gohighlevel/tags/" + data.companyID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** GOHIGHLEVEL */

    /** ARCHIVE CAMPAIGN */
    archiveCampaign(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/leadspeek/client/archive", {
            lpuserid: data.lpuserid,
            status: data.status
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** ARCHIVE CAMPAIGN */
    /** COMPANY GROUP */
    RemoveCompanyGroup(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .delete("/company/group/remove/" + data.companyGroupID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    AddEditGroupCompany(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/company/group", {
            companyGroupID: data.companyGroupID,
            companyGroupName: data.companyGroupName,
            companyID: data.companyID,
            moduleID: data.moduleID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetCompanyGroup(context, data) {
      return new Promise((resolve, reject) => {
        var _companyID = "";
        var _userModule = "";

        if (data.companyID != "") {
          _companyID = data.companyID;
        }
        if (
          data.companyID != "" &&
          data.userModule != "" &&
          typeof data.userModule != "undefined"
        ) {
          _userModule = "/" + data.userModule;
        }

        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/company/group/" + _companyID + _userModule)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** COMPANY GROUP */

    /** PAYMENT CUSTOMER */
    processUpdateCard(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/payment/card", {
            tokenid: data.tokenid,
            cardholder: data.cardholder,
            address: data.address,
            city: data.city,
            state: data.state,
            country: data.country,
            zip: data.zip,
            usrID: data.usrID,
            companyParentID: data.companyParentID,
            action: data.action
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getCardInfo(context, data) {
      return new Promise((resolve, reject) => {
        var _userID = "";

        if (
          typeof data != "undefined" &&
          data.usrID != "" &&
          typeof data.usrID != "undefined"
        ) {
          _userID = "/" + data.usrID;
        }

        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/payment/card" + _userID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    paymentcustomersetup(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/payment/customer", {
            tokenid: data.tokenid,
            cardholder: data.cardholder,
            address: data.address,
            city: data.city,
            state: data.state,
            country: data.country,
            zip: data.zip,
            usrID: data.usrID,
            action: data.action
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    paymentdirectcustomersetup(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/payment/customer-direct", {
            usrID: data.usrID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** PAYMENT CUSTOMER */

    /** LEADSPEEK */
    /** REPORT DASHBOARD */

    GetLeadsPeekInvReport(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/leadspeek/report/invoice/" +
              data.companyID +
              "/" +
              data.clientID +
              "/" +
              data.startDate +
              "/" +
              data.endDate
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetLeadsPeekLeadReport(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/leadspeek/report/lead/" +
              data.companyID +
              "/" +
              data.clientID +
              "/" +
              data.startDate +
              "/" +
              data.endDate,
              {
                params: {
                  Page: data.page,
                  PerPage:data.perPage,
                  sortby: data.sortby,
                  order: data.order
                }
              }
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetLeadsPeekInitDate(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/leadspeek/report/initdate/" + data.companyID + "/" + data.clientID
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetLeadsPeekChartReport(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/leadspeek/report/chart/" +
              data.companyID +
              "/" +
              data.clientID +
              "/" +
              data.startDate +
              "/" +
              data.endDate
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** REPORT DASHBOARD */

    activepauseLeadsPeek(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/leadspeek/client/activepause", {
            companyID: data.companyID,
            leadspeekID: data.leadspeekID,
            status: data.status,
            activeuser: data.activeuser,
            userID: data.userID,
            ip_user: data.ip_user,
            timezone: data.timezone,
            idSys: data.idSys,
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    RemoveLeadsPeekClient(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/leadspeek/client/remove", {
            companyID: data.companyID,
            leadspeekID: data.leadspeekID,
            status: data.status,
            userID: data.userID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    UpdateLeadsPeekClientLocal(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/leadspeek/client/local/update", {
            companyID: data.companyID,
            leadspeekID: data.leadspeekID,
            //locatorzip: data.locatorzip,
            //locatordesc: data.locatordesc,
            //locatorkeyword: data.locatorkeyword,
            //locatorstate: data.locatorstate,
            locatorrequire: data.locatorrequire,
            //locatorcity: data.locatorcity,
            //sificampaign: data.sificampaign,
            //sifiorganizationid: data.sifiorganizationid,
            phoneenabled: data.phoneenabled,
            homeaddressenabled: data.homeaddressenabled,
            requireemailaddress: data.requireemailaddress,
            reidentificationtype: data.reidentificationtype
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    UpdateLeadsPeekClientLocator(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/leadspeek/client/locator/update", {
            companyID: data.companyID,
            leadspeekID: data.leadspeekID,
            locatorzip: data.locatorzip,
            locatordesc: data.locatordesc,
            locatorkeyword: data.locatorkeyword,
            locatorkeywordcontextual: data.locatorkeywordcontextual,
            locatorstate: data.locatorstate,
            locatorrequire: data.locatorrequire,
            locatorcity: data.locatorcity,
            sificampaign: data.sificampaign,
            sifiorganizationid: data.sifiorganizationid,
            startdatecampaign: data.startdatecampaign,
            enddatecampaign: data.enddatecampaign,
            nationaltargeting: data.nationaltargeting,
            locationtarget: data.locationtarget,
            phoneenabled: data.phoneenabled,
            homeaddressenabled: data.homeaddressenabled,
            requireemailaddress: data.requireemailaddress,
            reidentificationtype: data.reidentificationtype
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    UpdateLeadsPeekClient(context, data) {
      return new Promise((resolve, reject) => {
        var _fieldupdate = "";
        var _valuefield = "";
        var _leadspeekType = "";

        if (
          data.leadspeekType != "" &&
          typeof data.leadspeekType != "undefined"
        ) {
          _leadspeekType = data.leadspeekType;
        }

        if (data.fieldupdate != "" && typeof data.fieldupdate != "undefined") {
          _fieldupdate = data.fieldupdate;
          _valuefield = data.valuefield;
        }
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/leadspeek/client/update", {
            companyID: data.companyID,
            userID: data.userID,
            leadspeekID: data.leadspeekID,
            reportType: data.reportType,
            reportSentTo: data.reportSentTo,
            adminNotifyTo: data.adminNotifyTo,
            leadsAmountNotification: data.leadsAmountNotification,
            fieldupdate: _fieldupdate,
            valuefield: _valuefield,
            leadspeekType: _leadspeekType,
            companyGroupID: data.companyGroupID,
            clientOrganizationID: data.clientOrganizationID,
            clientCampaignID: data.clientCampaignID,
            clientHidePhone: data.clientHidePhone,
            campaignName: data.campaignName,
            urlCode: data.urlCode,
            urlCodeThankyou: data.urlCodeThankyou,
            gtminstalled: data.gtminstalled,

            locatorzip: data.locatorzip,
            locatordesc: data.locatordesc,
            locatorkeyword: data.locatorkeyword,
            locatorkeywordcontextual: data.locatorkeywordcontextual,
            locatorstate: data.locatorstate,
            locatorrequire: data.locatorrequire,
            locatorcity: data.locatorcity,
            sificampaign: data.sificampaign,
            sifiorganizationid: data.sifiorganizationid,
            startdatecampaign: data.startdatecampaign,
            enddatecampaign: data.enddatecampaign,
            oristartdatecampaign: data.oristartdatecampaign,
            orienddatecampaign: data.orienddatecampaign,
            nationaltargeting: data.nationaltargeting,
            locationtarget: data.locationtarget,
            phoneenabled: data.phoneenabled,
            homeaddressenabled: data.homeaddressenabled,
            requireemailaddress: data.requireemailaddress,
            reidentificationtype: data.reidentificationtype,
            applyreidentificationall: data.applyreidentificationall,
            timezone: data.timezone
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    purgeSuppressionList(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
        .delete(
            "/leadspeek/suppressionlist/purge/" +
              data.paramID +
              "/" +
              data.campaignType
          )
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    ResendGoogleLink(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/leadspeek/client/resendgooglelink", {
            companyID: data.companyID,
            userID: data.userID,
            leadspeekID: data.leadspeekID,
            reportSentTo: data.reportSentTo,
            adminNotifyTo: data.adminNotifyTo
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    CreateLeadsPeekClient(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/leadspeek/client/create", {
            companyID: data.companyID,
            userID: data.userID,
            companyName: data.companyName,
            reportType: data.reportType,
            reportSentTo: data.reportSentTo,
            adminNotifyTo: data.adminNotifyTo,
            leadsAmountNotification: data.leadsAmountNotification,
            leadspeekType: data.leadspeekType,
            companyGroupID: data.companyGroupID,
            clientOrganizationID: data.clientOrganizationID,
            clientCampaignID: data.clientCampaignID,
            clientHidePhone: data.clientHidePhone,
            campaignName: data.campaignName,
            urlCode: data.urlCode,
            urlCodeThankyou: data.urlCodeThankyou,
            answers: data.answers,
            startdatecampaign: data.startdatecampaign,
            enddatecampaign: data.enddatecampaign,
            oristartdatecampaign: data.oristartdatecampaign,
            orienddatecampaign: data.orienddatecampaign,
            gtminstalled: data.gtminstalled,
            phoneenabled: data.phoneenabled,
            homeaddressenabled: data.homeaddressenabled,
            requireemailaddress: data.requireemailaddress,
            reidentificationtype: data.reidentificationtype,
            applyreidentificationall: data.applyreidentificationall,
            locationtarget: data.locationtarget,
            timezone: data.timezone,
            idSys: data.idSys
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetLeadsPeekClientList(context, data) {
      return new Promise((resolve, reject) => {
        var _clientID = "";
        var _leadspeekType = "";
        var _groupCompanyID = "all/";
        var _sortby = "";
        var _order = "";
        var _searchkey = "";

        if (
          data.groupCompanyID != "" &&
          typeof data.groupCompanyID != "undefined"
        ) {
          _groupCompanyID = data.groupCompanyID + "/";
        }

        if (data.clientID != "" && typeof data.clientID != "undefined") {
          _clientID = "/" + data.clientID;
        }

        if (
          data.leadspeekType != "" &&
          typeof data.leadspeekType != "undefined"
        ) {
          _leadspeekType = _groupCompanyID + data.leadspeekType + "/";
        }

        if (typeof data.sortby != "undefined" && data.sortby != "") {
          if (_clientID == "") {
            _sortby = "/0/" + data.sortby;
          } else {
            _sortby = "/" + data.sortby;
          }
        }

        if (typeof data.order != "undefined" && data.order != "") {
          if (_sortby != "") {
            _order = "/" + data.order;
          }
        }

        if (typeof data.searchkey != "undefined" && data.searchkey != "") {
          if (_sortby == "" && _clientID == "") {
            _searchkey = "/0/0/0/" + data.searchkey;
          } else {
            _searchkey = "/" + data.searchkey;
          }
        }

        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/leadspeek/client/" +
              _leadspeekType +
              data.companyID +
              _clientID +
              _sortby +
              _order +
              _searchkey,
            {
              params: {
                Page: data.page,
                PerPage: data.perPage,
                view: data.view,
                CampaignStatus: data.campaignStatus
              }
            }
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    disconectGoogleSheet(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/leadspeek/googlespreadsheet/revoke/" + data.companyID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    savePlanPackage(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/setting/package", {
            CompanyID: data.CompanyID,
            packageID: data.packageID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getReportAnalytics(context, data) {
      return new Promise((resolve, reject) => {
        var _companyid = "";
        var _campaignid = "";

        if (data.companyid != "" && typeof data.companyid != "undefined") {
          _companyid = "/" + data.companyid;
        }

        if (
          data.campaignid != "" &&
          typeof data.campaignid != "undefined" &&
          _companyid != ""
        ) {
          _campaignid = "/" + data.campaignid;
        }

        if (_companyid == "" && _campaignid == "" && data.companyrootid != "") {
          _companyid = "/0/0/" + data.companyrootid;
        }

        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/configuration/report-analytics/" +
              data.startDate +
              "/" +
              data.endDate +
              _companyid +
              _campaignid
          )
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    setAgencyFreePlan(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/setting/freepackage", {
            CompanyID: data.CompanyID,
            packageID: data.packageID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },

    createConnectedAccountLink(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/general/get-connected-account-link", {
            connectid: data.connectid,
            refreshurl: data.refreshurl,
            returnurl: data.returnurl,
            idsys: data.idsys
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },

    createConnectedAccount(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/general/create-connected-account", {
            companyID: data.companyID,
            companyname: data.companyname,
            companyphone: data.companyphone,
            companyaddress: data.companyaddress,
            companycity: data.companycity,
            companystate: data.companystate,
            companyzip: data.companyzip,
            companycountry: data.companycountry,
            companyemail: data.companyemail,
            weburl: data.weburl,
            idsys: data.idsys
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            //console.log(error);
            reject(error.response.data.message);
          });
      });
    },
    createSalesConnectedAccount(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/general/create-sales-connected-account", {
            userID: data.usrID,
            idsys: data.idsys
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            //console.log(error);
            reject(error.response.data.message);
          });
      });
    },
    checkConnectedAccount(context, data) {
      let param = data.companyID && data.idsys ?  data.companyID + "/" + data.idsys : data.companyID;
      // console.log(param); 
      // exit;
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/general/check-connected-account/" + param
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    checkSalesConnectedAccount(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/general/check-sales-connected-account/" +
              data.usrID +
              "/" +
              data.idsys
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    checkGoogleConnectSheet(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/leadspeek/googlespreadsheet/check-connect/" + data.companyID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    resetpaymentconnection(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .delete(
            "/general/reset-payment-connection/" +
              data.companyID +
              "/" +
              data.typeConnection
          )
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** LEADSPEEK */

    /** CONFIGURATION APP */
    updateCustomDomain(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/update-custom-domain", {
            companyID: data.companyID,
            DownlineDomain: data.DownlineDomain,
            whitelabelling: data.whitelabelling
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getCampaignResource(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/leadspeek/getcampaignresource/" +
              data.organizationID +
              "/" +
              data.campaignID
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getDomainorSubInfo(context, data) {
      return new Promise((resolve, reject) => {
        var hostclientID = "";
        if (typeof data.hostID != "undefined" && data.hostID != "") {
          hostclientID = "/" + data.hostID;
        }
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/domainsubdomain/" + data.domainorsub + hostclientID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getGeneralSetting(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/configuration/setting/" + data.companyID + "/" + data.settingname
          , {
            params: {
              idSys: data.idSys
            }
          })
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getCityStateList(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/general/state/" + data.citystate + "/cities")
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getStateList(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/general/state")
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getSalesList(context, data) {
      return new Promise((resolve, reject) => {
        var _companyID = "";

        if (data.companyID != "") {
          _companyID = "/" + data.companyID;
        }

        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/configuration/sales/list" + _companyID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    getRootList(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get("/configuration/root/list")
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    setSalesPerson(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/sales", {
            companyID: data.companyID,
            salesRep: data.salesRep,
            salesAE: data.salesAE,
            salesRef: data.salesRef
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    ClientModuleCost(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/user/costmodule", {
            companyID: data.companyID,
            ClientID: data.ClientID,
            ModuleName: data.ModuleName,
            CostSet: data.CostSet,
            CostMonth: data.CostMonth,
            CostMaxLead: data.CostMaxLead,
            LimitLead: data.LimitLead,
            LimitLeadFreq: data.LimitLeadFreq,
            LimitLeadStart: data.LimitLeadStart,
            LimitLeadEnd: data.LimitLeadEnd,
            PaymentTerm: data.PaymentTerm,
            contiDurationSelection: data.contiDurationSelection,
            topupoptions: data.topupoptions,
            leadsbuy: data.leadsbuy,
            PlatformFee: data.PlatformFee,
            LeadspeekApiId: data.LeadspeekApiId,
            idUser: data.idUser,
            ipAddress: data.ipAddress,
            idSys: data.idSys
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    RemoveRoleModule(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .delete(
            "/configuration/role-module/remove/" +
              data.companyID +
              "/" +
              data.roleID
          )
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    addUpdateRole(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/role-module/addupdate", {
            companyID: data.companyID,
            roleID: data.roleID,
            roleName: data.roleName,
            roleIcon: data.roleIcon,
            roledata: data.roledata
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetRoleList(context, data) {
      return new Promise((resolve, reject) => {
        var _companyID = "";
        var _getType = "";
        var _roleID = "";
        var _usrID = "";

        if (data.getType != "") {
          _getType = data.getType;
        }
        if (data.companyID != "" && data.getType != "") {
          _companyID = "/" + data.companyID;
        }
        if (data.companyID != "" && data.getType != "" && data.roleID != "") {
          _roleID = "/" + data.roleID;
        }

        if (
          data.companyID != "" &&
          data.getType != "" &&
          data.roleID != "" &&
          data.usrID != ""
        ) {
          _usrID = "/" + data.usrID;
        }

        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/configuration/role-module/" +
              _getType +
              _companyID +
              _roleID +
              _usrID
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    RemoveClient(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .delete(
            "/configuration/user/remove/" + data.companyID + "/" + data.userID
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    UpdateClient(context, data) {
      var _ClientPass = "";
      if (data.ClientPass != "") {
        _ClientPass = data.ClientPass;
      }
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/user/update", {
            companyID: data.companyID,
            ClientID: data.ClientID,
            ClientCompanyName: data.ClientCompanyName,
            ClientFullName: data.ClientFullName,
            ClientEmail: data.ClientEmail,
            ClientPhone: data.ClientPhone,
            ClientPhoneCountryCode: data.ClientPhoneCountryCode,
            ClientPhoneCountryCallingCode: data.ClientPhoneCountryCallingCode,
            ClientPass: _ClientPass,
            ClientRole: data.ClientRole,
            ClientDomain: data.ClientDomain,
            ClientWhiteLabeling: data.ClientWhiteLabeling,
            DownlineDomain: data.DownlineDomain,
            DownlineSubDomain: data.DownlineSubDomain,
            DownlineOrganizationID: data.DownlineOrganizationID,
            defaultAdmin: data.defaultAdmin,
            customercare: data.customercare,
            adminGetNotification: data.adminGetNotification,
            action: data.action,
            disabledreceivedemail: data.disabledreceivedemail,
            disabledaddcampaign: data.disabledaddcampaign,
            selectedterms: data.selectedterms,
            selectedsidebar: data.selectedSidebar,
            idsys: data.idsys
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    testsmtp(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/configuration/user/testsmtp", {
            companyID: data.companyID,
            emailsent: data.emailsent
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    ResendInvitation(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/configuration/user/resendinvitation", {
            ClientID: data.ClientID
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    CreateClient(context, data) {
      var _ClientPass = "";
      if (data.ClientPass != "") {
        _ClientPass = data.ClientPass;
      }
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/configuration/user/create", {
            companyID: data.companyID,
            userType: data.userType,
            ClientCompanyName: data.ClientCompanyName,
            ClientFullName: data.ClientFullName,
            ClientEmail: data.ClientEmail,
            ClientPhone: data.ClientPhone,
            ClientPhoneCountryCode: data.ClientPhoneCountryCode,
            ClientPhoneCountryCallingCode: data.ClientPhoneCountryCallingCode,
            ClientPass: _ClientPass,
            ClientRole: data.ClientRole,
            ClientDomain: data.ClientDomain,
            ClientWhiteLabeling: data.ClientWhiteLabeling,
            DownlineDomain: data.DownlineDomain,
            DownlineSubDomain: data.DownlineSubDomain,
            DownlineOrganizationID: data.DownlineOrganizationID,
            defaultAdmin: data.defaultAdmin,
            customercare: data.customercare,
            adminGetNotification: data.adminGetNotification,
            disabledreceivedemail: data.disabledreceivedemail,
            disabledaddcampaign: data.disabledaddcampaign,
            idsys: data.idsys,
            salesRep: data.salesRep,
            salesAE: data.salesAE,
            salesRef: data.salesRef,
            selectedterms: data.selectedterms,
            selectedsidebar: data.selectedSidebarCreate,
            twoFactorAuth: data.twoFactorAuth,
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetSalesDownlineList(context, data) {
      var _searchkey = "";

      if (typeof data.searchkey != "undefined" && data.searchkey != "") {
        _searchkey = "/" + data.searchkey;
      }

      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/configuration/sales-downline/" +
              data.companyID +
              "/" +
              data.usrID +
              "/" +
              data.idsys +
              _searchkey,
            {
              params: {
                Page: data.page,
                PerPage: data.perPage
              }
            }
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetClientList(context, data) {
      return new Promise((resolve, reject) => {
        var _companyID = "";
        var _idSys = "";
        var _userType = "";
        var _groupCompanyID = "/all";
        var _sortby = "";
        var _order = "";

        var _searchkey = "";

        if (
          data.groupCompanyID != "" &&
          typeof data.groupCompanyID != "undefined"
        ) {
          _groupCompanyID = "/" + data.groupCompanyID;
        }

        if (data.companyID != "") {
          _companyID = data.companyID;
        }
        if (data.idsys != "") {
          _idSys = "/" + data.idsys;
        }

        if (data.companyID != "" && data.userType != "") {
          _userType = "/" + data.userType;
        }

        if (
          data.userType != "" &&
          data.userModule != "" &&
          typeof data.userModule != "undefined"
        ) {
          _userType = _userType + "/" + data.userModule;
        }

        if (typeof data.sortby != "undefined" && data.sortby != "") {
          _sortby = "/" + data.sortby;
        }

        if (typeof data.order != "undefined" && data.order != "") {
          if (_sortby != "") {
            _order = "/" + data.order;
          }
        }

        if (typeof data.searchkey != "undefined" && data.searchkey != "") {
          if (_sortby == "") {
            _searchkey = "/0/0/" + data.searchkey;
          } else {
            _searchkey = "/" + data.searchkey;
          }
        }

        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .get(
            "/configuration/user/" +
              _companyID +
              _groupCompanyID +
              _idSys +
              _userType +
              _sortby +
              _order +
              _searchkey,
            {
              params: {
                Page: data.page,
                PerPage: data.perPage,
                CardStatus: data.cardStatus,
                CampaignStatus: data.campaignStatus,
              }
            }
          )
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },

    testEmail(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/configuration/setting/test-email", {
            fromAddress: data.fromAddress,
            fromName: data.fromName,
            fromReplyto: data.fromReplyto,
            subject: data.subject,
            content: data.content,
            testEmailAddress: data.testEmailAddress,
            companyID: data.companyID,
            companyParentID: data.companyParentID,
            userType: data.userType
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },

    updateGeneralSetting(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/setting", {
            companyID: data.companyID,
            actionType: data.actionType,
            sidebarcolor: data.sidebarcolor,
            templatecolor: data.templatecolor,
            boxcolor: data.boxcolor,
            textcolor: data.textcolor,
            linkcolor: data.linkcolor,
            fonttheme: data.fonttheme,
            paymenttermDefault: data.paymenttermDefault,
            comsetname: data.comsetname,
            comsetval: data.comsetval
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },

    updateDefaultSubdomain(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .put("/configuration/subdomain", {
            companyID: data.companyID,
            subdomain: data.subdomain,
            idsys: data.idsys
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** CONFIGURATION APP */

    /** MARKETING SIMPLI.FI */
    DeleteOrganization(context, data) {
      return new Promise((resolve, reject) => {
        axios
          .delete("/marketing/deleteorganization/" + data.OrganizationID)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    GetOrganizationList(context, data) {
      return new Promise((resolve, reject) => {
        var _organizationID = "";
        var _requestType = "";

        if (data.OrganizationID != "") {
          _organizationID = data.OrganizationID;
        }
        if (data.OrganizationID != "" && data.RequestType != "") {
          _requestType = "/" + data.RequestType;
        }

        axios
          .get("/marketing/getorganization/" + _organizationID + _requestType)
          .then(response => {
            //context.commit('putResultData',response.data)
            //resolve('success')
            resolve(response.data.organizations);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    CreateOrganization(context, data) {
      return new Promise((resolve, reject) => {
        axios
          .post("/marketing/createorganization", {
            OrganizationName: data.OrganizationName,
            ParentID: data.ParentID,
            CustomID: data.CustomID
          })
          .then(response => {
            resolve(response.data.organizations);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    /** MARKETING SIMPLI.FI */
    GetBannerList(context, data) {
      return new Promise((resolve, reject) => {
        axios
          .post("/banner/show", {
            bannertype: data.bannertype,
            //bannersethash: data.bannersethash,
            username: this.state.userData.email,
            type: data.type
          })
          .then(response => {
            resolve(response);
          })
          .catch(error => {
            reject(error.response.data.errors);
            //resolve('failed');
          });
      });
    },
    StartBannerCreate(context, data) {
      return new Promise((resolve, reject) => {
        axios
          .post("/banner/startbannercreate", {
            bannertype: data.bannertype,
            banneranimated: data.banneranimated,
            username: this.state.userData.email,
            name: this.state.userData.name
          })
          .then(response => {
            resolve(response);
          })
          .catch(error => {
            reject(error.response.data.errors);
            //resolve('failed');
          });
      });
    },
    forgetPass(context, data) {
      return new Promise((resolve, reject) => {
        axios
          .post("/forgetpass", {
            username: data.username,
            gtoken: data.gtoken,
            ownedcompanyid: data.ownedcompanyid,
            companyrootid: data.companyrootid
          })
          .then(response => {
            resolve("success");
          })
          .catch(error => {
            reject(error.response.data.errors);
            //resolve('failed');
          });
      });
    },
    register(context, data) {
      return new Promise((resolve, reject) => {
        var _companyname = "";
        var _phonenum = "";
        var _userType = "client";
        var _refcode = "";

        if (data.companyname != "") {
          _companyname = data.companyname;
        }

        if (data.phonenum != "") {
          _phonenum = data.phonenum;
        }

        if (data.userType != "") {
          _userType = data.userType;
        }

        if (data.refcode != "") {
          _refcode = data.refcode;
        }

        axios
          .post("/register", {
            companyname: _companyname,
            name: data.name,
            email: data.email,
            phonenum: _phonenum,
            password: data.password,
            gtoken: data.gtoken,
            userType: data.userType,
            domainName: data.domainName,
            ownedcompanyid: data.ownedcompanyid,
            idsys: data.idsys,
            refcode: _refcode,
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            /*console.log(error.response.data.errors.email);
            console.log('errorType', typeof error);
            console.log('error', Object.assign({}, error));
            console.log('getOwnPropertyNames', Object.getOwnPropertyNames(error));
            console.log('stackProperty', Object.getOwnPropertyDescriptor(error, 'stack'));
            console.log('messageProperty', Object.getOwnPropertyDescriptor(error, 'message'));
            console.log('stackEnumerable', error.propertyIsEnumerable('stack'));
            console.log('messageEnumerable', error.propertyIsEnumerable('message'));
            */
            //console.log(error.response.data.errors.email[0]);
            //console.log(error.response.data.errors.captcha[0]);

            /*Object.keys(error.response.data.errors).forEach(key => {
            console.log(error.response.data.errors[key][0]);
           })*/
            //console.log(error.response.data.message);
            reject(error.response.data.errors);
            //resolve('failed');
          });
      });
    },
    destroyToken(context) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;

      if (context.getters.loggedIn) {
        return new Promise((resolve, reject) => {
          axios
            .post("/logout")
            .then(response => {
              localStorage.removeItem("access_token");
              localStorage.removeItem("userData");
              localStorage.removeItem("userDataOri");
              localStorage.removeItem("userRole");
              localStorage.removeItem("clientIP");
              context.commit("destroyToken");
              resolve(response);
              // console.log(response);
              // context.commit('addTodo', response.data)
            })
            .catch(error => {
              localStorage.removeItem("access_token");
              localStorage.removeItem("userData");
              localStorage.removeItem("userDataOri");
              localStorage.removeItem("userRole");
              localStorage.removeItem("clientIP");
              context.commit("destroyToken");
              reject(error);
            });
        });
      }
    },

    putTokenSocial(context, credentials) {
      return new Promise((resolve, reject) => {
        const token = credentials.acctoken;

        localStorage.setItem("access_token", token);
        context.commit("retrieveToken", token);
        resolve("success");
      });
    },

    retrieveToken(context, credentials) {
      return new Promise((resolve, reject) => {
        axios
          .post("/login", {
            username: credentials.username,
            password: credentials.password,
            gtoken: credentials.gtoken,
            domainName: credentials.domainName,
            ownedcompanyid: credentials.ownedcompanyid,
            companyrootid: credentials.companyrootid
          })
          .then(response => {
            const token = response.data.access_token;

            localStorage.setItem("access_token", token);
            context.commit("retrieveToken", token);
            resolve(response);
            //console.log(response);
            // context.commit('addTodo', response.data)
          })
          .catch(error => {
            //console.log(error.response.data)
            reject(error.response.data);
            //resolve('loginfailed')
          });
      });
    },

    retrieveAutomComplete(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .get("/" + data.category + "/autocomplete/" + data.searchtext)
          .then(response => {
            context.commit("putResultData", response.data);
            resolve("success");
          })
          .catch(error => {
            resolve("failed");
          });
      });
    },

    checkUserSetupComplete(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .get("/user/checksetupcomplete/" + data.usrID)
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },

    userSetupComplete(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .put("/leadspeek/user/setupcomplete", {
            usrID: data.usrID,
            statuscomplete: data.statuscomplete,
            answers: data.answers,
            companyGroupID: data.companyGroupID,
            startdatecampaign: data.startdatecampaign,
            enddatecampaign: data.enddatecampaign,
            oristartdatecampaign: data.oristartdatecampaign,
            orienddatecampaign: data.orienddatecampaign,
            phoneenabled: data.phoneenabled,
            homeaddressenabled: data.homeaddressenabled,
            requireemailaddress: data.requireemailaddress,
            phoneenabledsiteid: data.phoneenabledsiteid,
            homeaddressenabledsiteid: data.homeaddressenabledsiteid,
            requireemailaddresssiteid: data.requireemailaddresssiteid,
            timezone: data.timezone
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    switchUserData(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .get("/user/" + data.usrID)
          .then(response => {
            const switchDataUser = response.data;
            const masterUser = context.getters.userData;

            //console.log(switchDataUser);
            //console.log(masterUser);
            if (switchDataUser["user_type"] == "client") {
              localStorage.removeItem("companyGroupSelected");
              localStorage.setItem(
                "companyGroupSelected",
                switchDataUser["id"]
              );
            } else {
              localStorage.removeItem("companyGroupSelected");
              localStorage.setItem("companyGroupSelected", "");
            }
            switchDataUser["user_type_ori"] = masterUser["user_type"];
            const envryptedObjectOri = CryptoJS.AES.encrypt(
              JSON.stringify(masterUser),
              "3ZY@l9qxdwL6"
            );
            //var decrypt =JSON.parse(CryptoJS.AES.decrypt(envryptedObjectOri,'UNEMM').toString(CryptoJS.enc.Utf8));

            localStorage.removeItem("userData");
            localStorage.removeItem("userDataOri");
            localStorage.setItem("userData", JSON.stringify(switchDataUser));
            localStorage.setItem("userDataOri", envryptedObjectOri);
            context.commit("retrieveUser", switchDataUser);

            resolve("success");
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    retrieveUserData(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        var userID = "";
        if (typeof data.usrID != "undefined" && data.usrID != "") {
          userID = "/" + data.usrID;
        }
        axios
          .get("/user" + userID)
          .then(response => {
            const dataUser = response.data;
            localStorage.setItem("userData", JSON.stringify(dataUser));
            context.commit("retrieveUser", dataUser);
            //resolve(response)
            //console.log(response.data);
            // context.commit('addTodo', response.data)
            resolve("success");
          })
          .catch(error => {
            //localStorage.removeItem('access_token')
            //context.commit('destroyToken')
            //reject(error)
            resolve("failed");
          });
      });
    },
    setUserData(context, data) {
      localStorage.setItem("userData", JSON.stringify(data.user));
      context.commit("retrieveUser", data.user);
    },
    updatePass(context, data) {
      return new Promise((resolve, reject) => {
        axios.defaults.headers.common["Authorization"] =
          "Bearer " + context.state.token;
        axios
          .post("/user/resetpassword", {
            usrID: data.usrID,
            newpassword: data.newpassword,
            currpassword: data.currpassword
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    docDownloadAuth(context, data) {
      return new Promise((resolve, reject) => {
        axios
          .post("/docauth", {
            pwd: data.password,
            urldata: data.dataurl
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            console.log(error);
            reject(error);
          });
      });
    },
    saveProfileStep1(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        let formData = new FormData();
        formData.append("name", data.name);
        formData.append("email", data.email);
        formData.append("phone", data.phone);
        formData.append("phoneCountryCode", data.phoneCountryCode);
        formData.append("phoneCountryDialCode", data.phoneCountryDialCode);
        formData.append("address", data.address);
        formData.append("city", data.city);
        formData.append("country", data.country);
        formData.append("zip", data.zip);
        formData.append("state", data.state);
        formData.append("pict", data.pict);
        formData.append("currpict", data.currpict);
        formData.append("profilestep", data.profilestep);
        formData.append("newpass", data.newpass);
        formData.append("renewpass", data.renewpass);
        formData.append("id", data.id);
        formData.append("idsys", data.idsys);

        axios
          .post("/user/update", formData)
          .then(response => {
            if (response.data.image_url != "") {
              this.state.userData.profile_pict = response.data.image_url;
            } else if (response.data.picexist == "") {
              this.state.userData.profile_pict = "";
            }
            this.state.userData.name = data.name;
            this.state.userData.email = data.email;
            this.state.userData.phonenum = data.phone;
            this.state.userData.phone_country_code = data.phoneCountryCode;
            this.state.userData.phone_country_calling_code =
              data.phoneCountryDialCode;
            this.state.userData.address = data.address;
            this.state.userData.city = data.city;
            this.state.userData.zip = data.zip;
            this.state.userData.country_code = data.country;
            this.state.userData.state_code = data.state;

            localStorage.setItem(
              "userData",
              JSON.stringify(this.state.userData)
            );
            //console.log(response.data.image_url);
            //resolve(response)
            //console.log(response.data);
            // context.commit('addTodo', response.data)
            resolve("success");
          })
          .catch(error => {
            //localStorage.removeItem('access_token')
            //context.commit('destroyToken')
            //reject(error)
            resolve("failed");
          });
      });
    },

    saveProfileStep2(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        let formData = new FormData();

        if (data.id != "" && data.id != "" && typeof data.id != "undefined") {
          formData.append("id", data.id);
        }

        formData.append("industryID", data.industryID);
        formData.append("industryName", data.industryName);
        formData.append("companyID", data.companyID);
        formData.append("companyName", data.companyName);
        formData.append("companyphone", data.companyphone);
        formData.append(
          "companyphoneCountryCode",
          data.companyphoneCountryCode
        );
        formData.append(
          "companyPhoneCountryCallingCode",
          data.companyPhoneCountryCallingCode
        );
        formData.append("companyemail", data.companyemail);

        formData.append("companyaddress", data.companyaddress);
        formData.append("companycity", data.companycity);
        formData.append("companyzip", data.companyzip);
        formData.append("companystate", data.companystate);
        formData.append("companycountry", data.companycountry);
        formData.append("franchize", data.franchize);
        formData.append("franchizedirector", data.franchizedirector);

        formData.append("directorfn", data.directorfn);
        formData.append("directorln", data.directorln);
        formData.append("directoremail", data.directoremail);
        formData.append("directorphone", data.directorphone);

        formData.append("officemanager", data.officemanager);
        formData.append("managerfn", data.managerfn);
        formData.append("managerln", data.managerln);
        formData.append("manageremail", data.manageremail);
        formData.append("managerphone", data.managerphone);

        formData.append("team", data.team);
        formData.append("teammanager", data.teammanager);
        formData.append("teamfn", data.teamfn);
        formData.append("teamln", data.teamln);
        formData.append("teamphone", data.teamphone);
        formData.append("teamemail", data.teamemail);

        formData.append("pict", data.pict);
        formData.append("currpict", data.currpict);
        formData.append("profilestep", data.profilestep);

        formData.append("DownlineSubDomain", data.DownlineSubDomain);
        formData.append("idsys", data.idsys);

        axios
          .post("/user/update", formData)
          .then(response => {
            if (response.data.image_url != "") {
              this.state.userData.company_logo = response.data.image_url;
            } else if (response.data.picexist == "") {
              this.state.userData.company_logo = "";
            }
            this.state.userData.industry_id = response.data.industryID;
            this.state.userData.company_id = response.data.companyID;

            this.state.userData.industry_name = data.industryName;
            this.state.userData.company_name = data.companyName;
            this.state.userData.company_phone = data.companyphone;
            this.state.userData.company_phone_country_code =
              data.companyphoneCountryCode;
            this.state.userData.company_phone_country_calling_code =
              data.companyPhoneCountryCallingCode;
            this.state.userData.company_email = data.companyemail;
            this.state.userData.company_address = data.companyaddress;

            this.state.userData.company_city = data.companycity;
            this.state.userData.company_zip = data.companyzip;
            this.state.userData.company_state_code = data.companystate;
            this.state.userData.company_country_code = data.companycountry;

            this.state.userData.franchize = data.franchize;
            this.state.userData.franchizedirector = data.franchizedirector;
            this.state.userData.directorfn = data.directorfn;
            this.state.userData.directorln = data.directorln;
            this.state.userData.directoremail = data.directoremail;
            this.state.userData.directorphone = data.directorphone;

            this.state.userData.officemanager = data.officemanager;
            this.state.userData.managerfn = data.managerfn;
            this.state.userData.managerln = data.managerln;
            this.state.userData.manageremail = data.manageremail;
            this.state.userData.managerphone = data.managerphone;

            this.state.userData.team = data.team;
            this.state.userData.teammanager = data.teammanager;

            this.state.userData.teamfn = data.teamfn;
            this.state.userData.teamln = data.teamln;
            this.state.userData.teamemail = data.teamemail;
            this.state.userData.teamphone = data.teamphone;

            localStorage.setItem(
              "userData",
              JSON.stringify(this.state.userData)
            );

            resolve(response.data.result);
          })
          .catch(error => {
            //localStorage.removeItem('access_token')
            //context.commit('destroyToken')
            //reject(error)
            resolve("failed");
          });
      });
    },

    getUserData(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        var userID = "";
        if (typeof data.usrID != "undefined" && data.usrID != "") {
          userID = "/" + data.usrID;
        }
        axios
          .get("/user" + userID)
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            reject(error);
          });
      });
    },

    // Integrations api
    async saveIntegrationSettings(
      { state },
      { url = "/saveintegration", data }
    ) {
      try {
        const response = await axios({
          method: "post",
          url,
          headers: {
            Authorization: "Bearer " + state.token
          },
          data
        });
        if(response.data.result === 'error'){
          Vue.prototype.$notify({
            type: 'primary',
            message: response.data.message,
            icon: 'far fa-bug'
        });
        }else{
          Vue.prototype.$notify({
            type: 'success',
            message: response.data.message,
            icon: 'far fa-save'
        });
        }
        return response.data;
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        // throw error;
      }
    },
    async updateCompanyIntegrationConfiguration(
      { state },
      { url = "/savecampaignintegrationdetails", data }
    ) {
      try {
        const response = await axios({
          method: "post",
          url,
          headers: {
            Authorization: "Bearer " + state.token
          },
          data
        });
        Vue.prototype.$notify({
          type: "success",
          message: "Changes saved successfully",
          icon: "far fa-save"
        });
        return response.data;
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        // throw error;
      }
    }, 
    async getSelectedKartraListAndTags({ state }, { url = '/integration/details', data }) {
      try {
        const response = await axios({
          method: 'post',
          url,
          headers: {
            'Authorization': 'Bearer ' + state.token
          },
          data
        });    
        return response.data;
      } catch (error) {
        Vue.prototype.$notify({
          type: 'primary',
          message: error.response.data.message,
          icon: 'fas fa-bug'
      }); 
        // throw error;
      }
    }, 
    async getUserSendgridList({ state }, { url = '/sendgridlist/', companyID }) {
      try {
        const response = await axios({
          method: "get", // Changed from 'post' to 'get'
          url: url + companyID,
          headers: {
            Authorization: "Bearer " + state.token
          }
          // No data property for GET request
        });
        if (response.data) {
          return response.data.param.result;
        } else {
          return [];
        }
      } catch (error) {
        return [];
        // throw error;
      }
    },  
    async geUsertKartraList({ state }, { url = '/kartra/list/', companyID }) {
      try {
        const response = await axios({
          method: 'get', // Changed from 'post' to 'get'
          url: url + companyID,
          headers: {
            'Authorization': 'Bearer ' + state.token
          },
          // No data property for GET request
        });
        if(response.data){
          return response.data.param.lists
        }else{
          return []
        }
      } catch (error) {
        return [];
        // throw error;
      }
    },  
    async geUsertKartraDetails({ state }, { url = '/GetKartraListAndTag/', companyID }) {
      try {
        const response = await axios({
          method: 'get', // Changed from 'post' to 'get'
          url: url + companyID,
          headers: {
            'Authorization': 'Bearer ' + state.token
          },
          // No data property for GET request
        });
        if(response.data){
          return response.data.param
        }else{
          return {}
        }
      } catch (error) {
        return [];
        // throw error;
      }
    },    
    async getUserIntegrationDetails({ state }, { url = '/getcompanyintegrationdetail/', companyID,slug }) {
      try {
        const response = await axios({
          method: "get",
          url: url + companyID + "/" + slug,
          headers: {
            Authorization: "Bearer " + state.token
          }
        });
        if (response.data.data) {
          return response.data.data;
        } else {
          return {};
        }
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        // throw error;
      }
    },
    async getUserIntegrationList(
      { state },
      { url = "/getcompanyintegrationlist/", companyID }
    ) {
      try {
        const response = await axios({
          method: "get",
          url: url + companyID,
          headers: {
            Authorization: "Bearer " + state.token
          }
        });
        if (response.data.data) {
          return response.data.data;
        } else {
          return [];
        }
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        return [];
        // throw error;
      }
    },
    async getCampaignZapierDetails({ state }, { url = '/getcampaignwebhook/', campaign_id }) {
      try {
        const response = await axios({
          method: "get",
          url: url + campaign_id,
          headers: {
            Authorization: "Bearer " + state.token
          }
        });
        if (response.data.data) {
          return response.data.data;
        } else {
          return {};
        }
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        // throw error;
      }
    },
    async getCampaignZapierTags({ state }, { url = '/getcampaigntags/', campaign_id }) {
      try {
        const response = await axios({
          method: "get",
          url: url + campaign_id,
          headers: {
            Authorization: "Bearer " + state.token
          }
        });
        if (response.data.data) {
          return response.data.data;
        } else {
          return {};
        }
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        // throw error;
      }
    },
    async getAgencyZapierDetails({ state }, { url = '/getagencywebhook/', company_id }) {
      try {
        const response = await axios({
          method: "get",
          url: url + company_id,
          headers: {
            Authorization: "Bearer " + state.token
          }
        });
        if (response.data.data) {
          return response.data.data;
        } else {
          return {};
        }
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        // throw error;
      }
    },
    async getAgencyZapierTags({ state }, { url = '/getagencytags/', company_id }) {
      try {
        const response = await axios({
          method: "get",
          url: url + company_id,
          headers: {
            Authorization: "Bearer " + state.token
          }
        });
        if (response.data.data) {
          return response.data.data;
        } else {
          return {};
        }
      } catch (error) {
        Vue.prototype.$notify({
          type: "primary",
          message: error.response.data.message,
          icon: "fas fa-bug"
        });
        // throw error;
      }
    },
    getSettingTwoFactorAuth(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .get("/setting/twofactorauth/" + data.userId)
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            reject(error.response.data);
          });
      });
    },
    settingTwoFactorAuth(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .put("/setting/twofactorauth/" + data.userId, {
            userId: data.userId,
            two_factor_auth: data.two_factor_auth,
            two_factor_auth_type: data.two_factor_auth_type,
            secretKey: data.secretKey,
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            reject(error.response.data);
          });
      });
    },
    verifyLogin(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .post("/verifylogin", {
            email: data.email,
            code: data.code,
            currenttimestamp: data.currenttimestamp,
          })
          .then(response => {
            if(response.data.result == 'success'){
              const token = response.data.rows.access_token;
              localStorage.setItem("access_token", token);
              context.commit("retrieveToken", token);
            }

            resolve(response.data);
          })
          .catch(error => {
            reject(error.response.data);
          });
      });
    },
    resendCode(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .post("/resendcode", {
            email: data.email,
            companyrootid: data.companyrootid
          })
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            reject(error.response.data);
          });
      });
    },
    getStopWords(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .get("/bigdbm/services/configdata/" + data.company_root_id)
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            reject(error.response.data);
          });
      });
    },
    getGoogleTfa(context, data) {
      axios.defaults.headers.common["Authorization"] =
        "Bearer " + context.state.token;
      return new Promise((resolve, reject) => {
        axios
          .get(`/setting/get-google-tfa/${data.userId}/${data.companyId}`)
          .then(response => {
            resolve(response.data);
          })
          .catch(error => {
            reject(error.response.data);
          });
      });
    },
  }
});
