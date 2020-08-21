// assets/js/app.js
import Vue from 'vue';
import VueRouter from 'vue-router'
import Notifications from 'vue-notification'
import Multiselect from 'vue-multiselect'

Vue.use(VueRouter)
Vue.use(Notifications)

// register globally
Vue.component('multiselect', Multiselect)

//import routes from './routes'

import Profile from './components/Profile'
import NewAttribute from './components/NewAttribute'
// import ppp from './components/Ppp'

const Messages = { template: '<div>Messages</div>' }

const About = { template: '<p>about page</p>' }

const routes = [
  // { path: '/', component: Main },
  { path: '/profile/:id?', component: Profile, props: true },
  { path: '/messages', component: Messages },
  { path: '/create-new-attribute', component: NewAttribute },
]

const router = new VueRouter({
  mode: 'history',
  routes
})


// Global Component Registration

/*
Vue.component('stub-list', {
  props: ['stub'],
  template: `
      <router-link class="nav-link" :to="{ path: '/profile/'+stub.id}">Stub {{ stub.id }}</router-link>
  `
})
*/


/* Old Method */
/*
new Vue({
  router
  //render: h => h(Example)
}).$mount('#app')
*/

const methods = {
	// loadFeeds: function(event){
	// 	if (this.selected.id=='') this.$router.push({path:'/'})
	// 	else this.$router.push({path:'/edit/' + this.selected.id })
	// }
    updateStubList (value) {
    	this.stubs = value;
    },
    changeStubState(value){
    	this.stubliststatechanged = value;
    }
}

var data = {
	selected:{
		id:'' 
	},
	load:'sdfsdfs',
	model:'false',
	stubs:[],
	stubliststatechanged:true
	// routename:'',
}

var watch = {
    // whenever question changes, this function will run
  //   'selected.id': function () {
  //   	console.log('RUNS')
		// if(this.selected.id) this.$emit('select', this.selected.id);
  //   }

    '$route.params.id'(newId, oldId) {
    	//console.log('oldId'+oldId);
    	//console.log('newId'+newId);
        //this.fetchFeeds(newId);
    }

}


var options = {
	router,
	components:{

	},
	methods,
	data,
	watch, 
	created:function() {
		console.log('app_created');
	    this.selected.id = this.$route.params.id;
	    // axios.get('/api/v1/pages_groups')
	    // .then(function (resp) {
	    //     app.pages = resp.data.pages;
	    //     app.groups = resp.data.groups;

	    // })
	    // .catch(function () {
	    //     alert("Could not load your pages/groups")
	    // });
  	},
  	mounted() {
  		console.log('App Mounted');
  		//this.model = 'true'
	}
}


var app = new Vue(options).$mount('#app')