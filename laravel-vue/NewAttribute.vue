<template>
	<div class="container mt-5 mb-5">

		<div class="form-group row">
			<label class="col-4 col-form-label">Add New Attribute</label> 
			<div class="col-8">
				<input v-model="attribute_name" type="text" class="form-control">
			</div>
		</div>

		<div class="form-group row" v-if="attribute_name">
			<label class="col-4 col-form-label">Does this has any parent</label> 
			<div class="col-8">
				<label class="custom-toggle">
				  <input type="checkbox" v-model="has_parent">
				  <span class="custom-toggle-slider rounded-circle"></span>
				</label>
			</div>	
		</div>

		<div class="form-group row" v-if="has_parent && attribute_name">
			<label class="col-4 col-form-label">Select any parent attribute</label> 
			<div class="col-8">
				<multiselect v-model="parent_attribute_selected" :options="attribute_list"  placeholder="Select one" label="name" track-by="name" allowEmpty=""></multiselect>
			</div>
		</div>

		<div class="row" v-if="Object.keys(category_available).length && attribute_name">			
			<div v-for="(category,index) in category_available" class="col-6">
				<div class="card" style="margin-bottom:10px;">
					<div class="card-body">
						<h5 class="card-title">
							<div class="custom-control custom-radio mb-3">
							  <input :value="index" v-model="category_selected" class="custom-control-input" :id="index" type="radio">
							  <label class="custom-control-label" :for="index">{{ categoryRelations[index].name }}</label>
							</div>
						</h5>
						<span v-for="attribute in category">{{ attribute.name }}, </span>
					</div>
				</div>
			</div>
			<br>
		</div>

		<div class="row" v-if="has_parent && parent_attribute_selected.attributeId && attribute_name">
			<div class="col-12">
				<div class="card" style="margin-bottom:10px;">
					<div class="card-body">
						<h5 class="card-title">
							<div class="custom-control custom-radio mb-3">
							  <input value="0" v-model="category_selected" class="custom-control-input" id="custom-radio-0" type="radio">
							  <label class="custom-control-label" for="custom-radio-0">New Category</label>
							</div>
						</h5>
						<div class="form-group row" v-if="category_selected==='0'">
							<label class="col-4 col-form-label">Relationship Name</label> 
							<div class="col-8">
								<input v-model="relationship_name" type="text" class="form-control" disabled>
								<p class="text-muted">Only Moderator can change this</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>


		<div class="card" v-if="has_parent && parent_attribute_selected.attributeId && false">
			<div class="card-body">
				<h5 class="card-title">{{ parent_attribute_selected.name }}</h5>
				<div class="form-group row" v-if="has_parent">
					<label class="col-4 col-form-label">{{ relationship_name }} :</label> 
					<div class="col-8">
						<h4>{{ attribute_name }}</h4>
					</div>
				</div>
				
			</div>
		</div>
			

		<button v-if="has_parent && parent_attribute_selected.attributeId && category_selected && attribute_name" type="button" class="btn btn-primary" @click="submit">Submit</button>
		<button v-else-if="!has_parent && attribute_name" type="button" class="btn btn-primary" @click="submit">Submit</button>
	
	</div>
</template>

<script>

	import _ from 'lodash'

	export default {
		name: "newAttribute",
		//props:['author'],
		mounted() {
	    	this.init();
		},
		data: function () {
			return {
				attribute_list: [],
				parent_attribute_selected:{},
				category_selected:'',
				attribute_name:null,
				relationship_name:null,
				has_parent:null,
				categoryRelations:{},
				relations:[],
				category_available:{}
			}
		},
       	methods: {
	   		init(){
	   			this.loadData(3);
	        },     		
       		loadData(stubCategoryId){
       			var app = this;
                axios.post('/api/v1/attribute/create_attribute',{'stubCategoryId':stubCategoryId})
                .then(function (resp) {
                	app.attribute_list = resp.data.attribute_list;
                	app.categoryRelations = resp.data.categoryRelations;
                	app.relations = resp.data.relations;
                })
                .catch(function (resp) {
                });
       		},
       		submit(){

       			var app = this;

       			if(_.isEmpty(app.parent_attribute_selected)) var parentAttributeId = null;
       			else var parentAttributeId = app.parent_attribute_selected.attributeId;

       			var post = {
       				'name':app.attribute_name,
       				'has_parent':app.has_parent,
       				'parentAttributeId':parentAttributeId,
       				'relationship_name':app.relationship_name,
       				'category_selected':app.category_selected,
       			}
                axios.post('/api/v1/attribute/new',post)
                .then(function (resp) {
                	//app.attribute_list = resp.data.data;
                })
                .catch(function (resp) {
                });
       		},
	    },
		watch: {
			parent_attribute_selected: function (newObj, oldObj) {
				var categories = this.relations[newObj.attributeId];
				//var attributeRelationCategoryIds = _.map(categories, 'attributeRelationCategoryId');
				var categories = _.groupBy(categories, function(b) { return b.attributeRelationCategoryId}) 
				this.category_available = categories;
				this.relationship_name = 'Select '+newObj.name;
			}
		},
		created() {

		}
	};
</script>