<template>
  <div class="row">
    <div class="col-sm-12 col-md-12 col-lg-12 text-center">
        <h2><i class="fas fa-analytics"></i>&nbsp;&nbsp;Report Analytics 123</h2>
    </div>
    <div class="pt-5 pb-5">&nbsp;</div>
    <div class="col-sm-12 col-md-12 col-lg-12 text-left pb-4" v-if="!$global.globalviewmode">
        <i class="fas fa-building pr-2"></i>Select Company :
          <el-select
              class="select-primary"
              size="large"
              placeholder="Select Company"
              filterable
              default-first-option
              v-model="selects.rootSelected"
              style="width:250px"
              @change="viewRootCompany"
          >
          
              <el-option
                  v-for="option in selects.rootList"
                  class="select-primary"
                  :value="option.id"
                  :label="option.name"
                  :key="option.id"
              >
              </el-option>
          </el-select>
    </div>
   
    <div class="col-sm-12 col-md-12 col-lg-12">
            <div class="d-inline-block pr-2">Date Filter:</div>
            <div class="d-inline-block">
                <base-input>
                  <el-date-picker
                    type="date"
                    placeholder="Date Start"
                    v-model="reportStartDate"
                    value-format="yyyy-MM-dd"
                    @change="getReportAnalytics()"
                  >
                  </el-date-picker>
                </base-input>        
            </div>
            <div class="d-inline-block pl-2 pr-2">-</div>
            <div class="d-inline-block">
                <base-input>
                  <el-date-picker
                    type="date"
                    placeholder="Date End"
                    v-model="reportEndDate"
                    value-format="yyyy-MM-dd"
                    @change="getReportAnalytics()"
                  >
                  </el-date-picker>
                </base-input>        
            </div>
            <div class="d-inline-block pl-4">
            <base-button size="sm" style="height:40px" @click="ExportAnalyticsData()">
              <i class="fas fa-cloud-download-alt pr-2"></i> Download Data
            </base-button>
          </div>
    </div>
    <div class="pt-3 pb-3">&nbsp;</div>

    <div class="col-sm-12 col-md-12 col-lg-12 pt-3 pb-3">
        <div class="row">
          <!-- Stats Cards -->
            <div class="col-lg-3 col-md-6" v-for="card in statsCards.filter(info => info.showCard === true)" :key="card.id">
              <stats-card
                :title="card.title"
                :sub-title="card.subTitle"
                :type="card.type"
                :icon="card.icon"
              >
                <div slot="footer" v-html="card.footer"></div>
              </stats-card>
            </div>
        </div>
     </div>

  </div>
</template>

<script>
import StatsCard from 'src/components/Cards/StatsCard';
import { DatePicker, Select, Option } from 'element-ui';

export default {
    components: {
        [DatePicker.name]: DatePicker,
        StatsCard,
        [Option.name]: Option,
        [Select.name]: Select,
    },
    data() {
        return {
            reportStartDate: '',
            reportEndDate: '',
            activeCompanyID: '',
            activeCampaignID: '',
            selects: {
              rootList: [],
              rootSelected: "",
            },
            statsCards: [
                {
                    id: '0',
                    title: '0 Fire(s)',
                    subTitle: 'Total Fires',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Tower Data Pixel Fire',
                    showCard:true,
                },
                {
                    id: '1',
                    title: '0 Call(s)',
                    subTitle: 'Total Calls',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Successful Tower Data Postal API',
                    showCard:true,
                },
                {
                    id: '2',
                    title: '0 Call(s)',
                    subTitle: 'Total Calls',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Successful BIG BDM MD5',
                    showCard:true,
                },
                    
                {
                    id: '3',
                    title: '0 Call(s)',
                    subTitle: 'Total Calls',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Successful BIG BDM PII',
                    showCard:true,
                },
                {
                    id: '4',
                    title: '0 Serve(s)',
                    subTitle: 'Total Serves',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Successful Site ID serve data',
                    showCard:true,
                },
                {
                    id: '5',
                    title: '$0',
                    subTitle: 'Total Revenue',
                    type: 'primary',
                    icon: 'fas fa-dollar-sign',
                    footer: 'Site ID serve revenue',
                    showCard:true,
                },
                {
                    id: '6',
                    title: '0 Serve(s)',
                    subTitle: 'Total Serves',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Successful Search ID serve data',
                    showCard:true,
                },
                {
                    id: '7',
                    title: '$0',
                    subTitle: 'Total Revenue',
                    type: 'primary',
                    icon: 'fas fa-dollar-sign',
                    footer: 'Search ID serve revenue',
                    showCard:true,
                },
                {
                    id: '8',
                    title: '0 Blocked',
                    subTitle: 'Total Blocked',
                    type: 'primary',
                    icon: 'fas fas fa-do-not-enter',
                    footer: 'Blocked by Zero Bounce',
                    showCard:true,
                },
                {
                    id: '9',
                    title: '0 Blocked',
                    subTitle: 'Total Blocked',
                    type: 'primary',
                    icon: 'fas fas fa-do-not-enter',
                    footer: 'Blocked by Location Lock',
                    showCard:true,
                },
                {
                    id: '10',
                    title: '0 Active',
                    subTitle: 'Total Active',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Site ID active campaign',
                    showCard:true,
                },
                {
                    id: '11',
                    title: '0 Active',
                    subTitle: 'Total Active',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Search ID active campaign',
                    showCard:true,
                },
                {
                    id: '12',
                    title: '0 Serve(s)',
                    subTitle: 'Total Serves',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Successful Enhance ID serve data',
                    showCard:true,
                },
                {
                    id: '13',
                    title: '$0',
                    subTitle: 'Total Revenue',
                    type: 'primary',
                    icon: 'fas fa-dollar-sign',
                    footer: 'Enhance ID serve revenue',
                    showCard:true,
                },
                {
                    id: '14',
                    title: '0 Active',
                    subTitle: 'Total Active',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'Enhance ID active campaign',
                    showCard:true,
                },
                {
                    id: '15',
                    title: '0 Hem(s)',
                    subTitle: 'Total Hem(s)',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'BIG BDM HEMS',
                    showCard:true,
                },
                {
                    id: '16',
                    title: '0 Lead(s)',
                    subTitle: 'Total Lead(s)',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'BIG BDM TOTAL LEADS',
                    showCard:true,
                },
                {
                    id: '17',
                    title: '0 Lead(s)',
                    subTitle: 'Total Remaining',
                    type: 'primary',
                    icon: 'fas fa-sort-amount-up',
                    footer: 'BIG BDM REMAINING LEADS',
                    showCard:true,
                },
                {
                    id: '18',
                    title: '0 Time(s)',
                    subTitle: 'Total Failed',
                    type: 'primary',
                    icon: 'fas fas fa-do-not-enter',
                    footer: 'Failed Get Data All',
                    showCard:true,
                },
                {
                    id: '19',
                    title: '0 Time(s)',
                    subTitle: 'Total Failed',
                    type: 'primary',
                    icon: 'fas fas fa-do-not-enter',
                    footer: 'Failed Get Data BIG BDM MD5',
                    showCard:true,
                },
                {
                    id: '20',
                    title: '0 Time(s)',
                    subTitle: 'Total Failed',
                    type: 'primary',
                    icon: 'fas fas fa-do-not-enter',
                    footer: 'Failed Get Data TOWER DATA',
                    showCard:true,
                },
                {
                    id: '21',
                    title: '0 Time(s)',
                    subTitle: 'Total Failed',
                    type: 'primary',
                    icon: 'fas fas fa-do-not-enter',
                    footer: 'Failed Get Data BIG DBM PII',
                    showCard:true,
                },
            ],
        };
    },
    methods: {
        ExportAnalyticsData() {
          var reportStart = this.reportStartDate;
          var reportEnd = this.reportEndDate;
          var _companyid = '';
          var _campaignid = '';

          if (this.activeCompanyID != '') {
              _companyid = '/' + this.activeCompanyID;
          }

          if (this.activeCampaignID != '' && _companyid != '') {
              _campaignid = '/' + this.activeCampaignID;
          }

          document.location = process.env.VUE_APP_DATASERVER_URL + '/configuration/report-analytics/download/' + reportStart + '/' + reportEnd + _companyid + _campaignid;
        },
        viewRootCompany() {
          this.getReportAnalytics();
        },
        getRootList() {
          this.$store.dispatch('getRootList').then(response => {
            if (this.selects.rootList.length == 0) {
              this.selects.rootList = response.params
              this.selects.rootList.unshift({'id':'','name':'View All'});
              this.selects.rootSelected = this.$global.idsys;
              this.getReportAnalytics();
            }
          },error => {
              
          });
        },
        resetCardValue() {
          this.statsCards[0].title = '0 Fire(s)';
          this.statsCards[1].title = '0 Call(s)';
          //this.statsCards[2].title = total_endato + ' Call(s)';
          //this.statsCards[3].title = total_md5 + ' Call(s)';
          this.statsCards[2].title = '0 Call(s)';
          this.statsCards[3].title = '0 Call(s)';
          this.statsCards[4].title = '0 Serve(s)';
          this.statsCards[5].title = '$0';
          this.statsCards[6].title = '0 Serve(s)';
          this.statsCards[7].title = '$0';
          this.statsCards[8].title = '0 Blocked';
          this.statsCards[9].title = '0 Blocked';
          this.statsCards[10].title = '0 Active';
          this.statsCards[11].title = '0 Serve(s)';
          this.statsCards[12].title = '$0';
          this.statsCards[13].title = '0 Active';
          this.statsCards[14].title = '0 Hem(s)';
          this.statsCards[15].title = '0 Lead(s)';
          this.statsCards[16].title = '0 Lead(s)';
          this.statsCards[17].title = '0 Time(s)';
          this.statsCards[18].title = '0 Time(s)';
          this.statsCards[19].title = '0 Time(s)';
          this.statsCards[20].title = '0 Time(s)';
        },
        getReportAnalytics() {
            this.resetCardValue();
            this.$store.dispatch('getReportAnalytics', {
                startDate: this.reportStartDate,
                endDate: this.reportEndDate,
                companyid: this.activeCompanyID,
                campaignid: this.activeCampaignID,
                companyrootid: this.selects.rootSelected,
            }).then(response => {
              console.log(response);
              if (response.data.length > 0) {
                var total_pixelfire = 0;
                var total_postal = 0;
                var total_endato = 0;
                var total_md5 = 0;
                var total_bigbdm_md5 = 0;
                var total_bigbdm_pii = 0;
                var total_zerobouncefailed = 0;
                var total_locationlockfailed = 0;
                var total_bigbdm_hems = 0;
                var total_bigbdm_total_leads = 0;
                var total_bigbdm_remaining_leads = 0;
                var total_failed_get_data_all = 0;
                var total_failed_get_data_bigbdmmd5 = 0;
                var total_failed_get_data_gettowerdata = 0;
                var total_failed_get_data_bigbdmpii = 0;

                var total_siteid_serve = 0;
                var total_searchid_serve = 0;
                var total_enhanceid_serve = 0;
                var total_platformfee_siteid_serve = 0;
                var total_platformfee_searchid_serve = 0;
                var total_platformfee_enhanceid_serve = 0;
                
                var total_siteid_active = 0;
                var total_searchid_active = 0;
                var total_enhanceid_active = 0;

                for(var i=0;i<response.data.length;i++) {
                  total_pixelfire = parseFloat(total_pixelfire) + parseFloat(response.data[i].pixelfire);
                  total_postal = parseFloat(total_postal) + parseFloat(response.data[i].towerpostal);
                  total_endato = parseFloat(total_endato) + parseFloat(response.data[i].endatoenrichment);
                  total_md5 = parseFloat(total_md5) +  parseFloat(response.data[i].toweremail);
                  total_bigbdm_md5 = parseFloat(total_bigbdm_md5) + parseFloat(response.data[i].bigbdmemail);
                  total_bigbdm_pii = parseFloat(total_bigbdm_pii) + parseFloat(response.data[i].bigbdmpii);
                  total_zerobouncefailed = parseFloat(total_zerobouncefailed) + parseFloat(response.data[i].zerobouncefailed);
                  total_locationlockfailed = parseFloat(total_locationlockfailed) + parseFloat(response.data[i].locationlockfailed);

                  if (response.data[i].leadspeek_type == 'local') {
                     total_siteid_serve = response.data[i].serveclient;
                     total_platformfee_siteid_serve = response.data[i].platformfee;
                     total_siteid_active = response.data[i].activecampaign;
                  }else if (response.data[i].leadspeek_type == 'locator') {
                     total_searchid_serve = response.data[i].serveclient;
                     total_platformfee_searchid_serve = response.data[i].platformfee;
                     total_searchid_active = response.data[i].activecampaign;
                  }
                }

                if (this.activeCampaignID != '') {
                    if (response.data[0].leadspeek_type == 'local') {
                        this.statsCards[4].showCard = true;
                        this.statsCards[5].showCard = true;
                        this.statsCards[6].showCard = false;
                        this.statsCards[7].showCard = false;
                        this.statsCards[9].showCard = false;
                    }else if (response.data[0].leadspeek_type == 'locator') {
                        this.statsCards[4].showCard = false;
                        this.statsCards[5].showCard = false;
                        this.statsCards[6].showCard = true;
                        this.statsCards[7].showCard = true;
                        this.statsCards[9].showCard = true;
                    }
                }

                this.statsCards[0].title = total_pixelfire + ' Fire(s)';
                this.statsCards[1].title = total_postal + ' Call(s)';
                //this.statsCards[2].title = total_endato + ' Call(s)';
                //this.statsCards[3].title = total_md5 + ' Call(s)';
                this.statsCards[2].title = total_bigbdm_md5 + ' Call(s)';
                this.statsCards[3].title = total_bigbdm_pii + ' Call(s)';
                this.statsCards[4].title = total_siteid_serve + ' Serve(s)';
                this.statsCards[5].title = '$' + total_platformfee_siteid_serve;
                this.statsCards[6].title = total_searchid_serve + ' Serve(s)';
                this.statsCards[7].title = '$' + total_platformfee_searchid_serve;
                this.statsCards[8].title = total_zerobouncefailed + ' Blocked';
                this.statsCards[9].title = total_locationlockfailed + ' Blocked';
                this.statsCards[10].title = total_siteid_active + ' Active';
                // this.statsCards[11].title = total_searchid_active + ' Active';
                // this.statsCards[12].title = total_searchid_active + ' Active';
                // this.statsCards[13].title = total_searchid_active + ' Active';
                // this.statsCards[14].title = total_searchid_active + ' Active';
                // this.statsCards[15].title = total_searchid_active + ' Active';
                // this.statsCards[16].title = total_searchid_active + ' Active';
                // this.statsCards[17].title = total_searchid_active + ' Active';
                // this.statsCards[18].title = total_searchid_active + ' Active';
                // this.statsCards[19].title = total_searchid_active + ' Active';
                // this.statsCards[20].title = total_searchid_active + ' Active';
              }
            },error => {
                
            });
        },
        currentDate() {
          const current = new Date();
          var _month = current.getMonth()+1;
          var _year = current.getFullYear();
          var _date = current.getDate();

          _month = ('0' + _month).slice(-2);
          _date = ('0' + _date).slice(-2);

          const date = `${current.getFullYear()}-${_month}-${_date}`;
          return date;
        },
    },
    mounted() {
        const userData = this.$store.getters.userData;
        if (this.$global.globalviewmode && typeof(this.$route.query.campaignid) != 'undefined') {
          this.activeCompanyID = userData.company_id;
          this.activeCampaignID = this.$route.query.campaignid;
          this.statsCards[4].showCard = false;
          this.statsCards[5].showCard = false;
          this.statsCards[6].showCard = false;
          this.statsCards[7].showCard = false;
          
          this.statsCards[8].showCard = false;
          this.statsCards[9].showCard = false;
          this.statsCards[10].showCard = false;
          this.statsCards[11].showCard = false;
          
        }else if (this.$global.globalviewmode && typeof(this.$route.query.campaignid) == 'undefined') {
          this.activeCompanyID = userData.company_id;
          this.activeCampaignID = "";
        }else{
          this.activeCompanyID = "";
          this.activeCampaignID = "";
        }
        this.reportStartDate = this.currentDate();
        this.reportEndDate = this.currentDate();
        this.getRootList();
        //this.getReportAnalytics();
    },
};

</script>