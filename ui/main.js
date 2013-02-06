/*
 * kkm
 * JavaScript lib used on both the frontend and admin interface.
 */


/**
 * Determines the text color for the given background color of the tag.
 */
function kkm_color_for_bg(bg_color) {
	hex = /([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i.exec(bg_color);
	gs = (parseInt(hex[1],16) + parseInt(hex[2],16) + parseInt(hex[3],16)) / 3;
	if (gs > 128) {
		return '000';
	} else {
		return 'fff';
	}
}
/**
 * Renders a tag as a HTML &lt;span&gt; tag.
 */
function kkm_render_tag(name, bg_color, value, title) {
	color = kkm_color_for_bg(bg_color);
	if ($.type(value) == 'string') {
		value = value.replace(/&/g, '&amp;').replace(/"/g, '&quot;');
	}
	return '<span class="kkm_tag" style="background:#' + bg_color + ';color:#' + color + ';" value="' + value + '" title="' + title + '">' + name + '</span>';
}

/**
 * Adds JS to a tagging interface.
 * 
 * @param object params
 */
function kkm_tagging_box(params) {
	this.params = params;
	this.$ = window.jQuery;
	
	/**
	 * Initializing function called upon object creation.
	 * Registers the init_interface() function as a callback for the taglist loading,
	 * and tries to load the taglist.
	 */
	this.init = function() {
		kkm_tags.callbacks.push(this.init_interface);
		kkm_tags.load();
		
		this.search_list = $('#kkm_tagging_pool_'+this.params.name);
		this.assigned_list = $('#kkm_tagging_assigned_'+this.params.name);
		this.search_field = $('#kkm_tagging_search_'+this.params.name);
		this.value_field = $('#'+this.params.name);
		this.needed_list = $('#kkm_tagging_needed_'+this.params.name);
		this.create_list = $('#kkm_tagging_newcat_'+this.params.name);
	
		this.selected_tags = [];
		$.each(params.assigned, function(k,v){
			selected_tags.push(String(v));
		});
		this.update_field(); //Save already assigned tags into the field
	}
	
	/**
	 * Called after the taglist is loaded, this function builds up the interface.
	 */
	this.init_interface = function() {
		//Add existing suggested tags
		$.each(kkm_tags.taglist, function(tid, tag) {
			tcid = parseInt(tag['tcid']);
			tid = parseInt(tid);
			if (params.suggested.indexOf(tid) > -1) { //Suggested
				not_excluded = (params.excluded.indexOf(tcid) == -1); //Tag category exclusion
				cat = kkm_tags.tagcats[tcid];
				assignable_type = (cat['mandatory_'+params.type] > 0); //Tag does not belong to a category that cannot be assigned to this type (composition/document)
				
				if (not_excluded && assignable_type) {
					title = cat['name'] + ': ' + tag['name'];
					tag_source = kkm_render_tag(tag['name'], cat['color'], tid, title);
					
					if (params.assigned.indexOf(tid) == -1) {
						lit = $('<li>'+tag_source+'</li>').addClass('suggested')
						search_list.append(lit);
					} else {
						assigned_list.append('<li>'+tag_source+'</li>');
					}
				}
			}
		});
		
		//Add not-yet-created tags
		$.each(params.suggested, function(i, sta) {
			if (sta instanceof Array) {
				tcid = sta[0];
				cat = kkm_tags.tagcats[tcid];
				name = sta[1];
				title = cat['name'] + ': ' + name;
				tid = JSON.stringify([tcid, name]);
				tag_source = kkm_render_tag(name, cat['color'], tid, title);
				lit = $('<li>'+tag_source+'</li>').addClass('suggested')
				search_list.append(lit);
			}
		});
		
		//Populate list
		$.each(kkm_tags.taglist, function(tid, tag) {
			tcid = parseInt(tag['tcid']);
			tid = parseInt(tid);
			if (params.suggested.indexOf(tid) == -1) { //Not suggested
				not_excluded = (params.excluded.indexOf(tcid) == -1); //Tag category exclusion
				cat = kkm_tags.tagcats[tcid];
				assignable_type = (cat['mandatory_'+params.type] > 0); //Tag does not belong to a category that cannot be assigned to this type (composition/document)
				
				if (not_excluded && assignable_type) {
					cat = kkm_tags.tagcats[tcid];
					title = cat['name'] + ': ' + tag['name'];
					tag_source = kkm_render_tag(tag['name'], cat['color'], tid, title);
					
					if (params.assigned.indexOf(tid) == -1) {
						search_list.append('<li>'+tag_source+'</li>');
					} else {
						assigned_list.append('<li>'+tag_source+'</li>');
					}
				}
			}
		});
		
		//Add events
		populate_create_tag_list();
		search_field.bind('keyup', filter);
		create_list.bind('change', create_tag);
		update_events();
		update_mandatory();
	}
	
	/**
	 * Search field event: filtering
	 */
	this.last_filter = '';
	this.filter = function() {
		filter = search_field.val();
		if (last_filter != filter) {
			last_filter = filter;
			filter = filter.replace(/([\\\/\|\.\*\+\?\(\)\[\]\{\}])/g, '\\$1');
			words = filter.split(' ');
			r = [];
			$.each(words, function(key, val) {
				r.push(new RegExp(val, 'i'));
			});
			
			search_list.children().each(function(i,li){
				text = li.firstChild.innerHTML;
				ok = true;
				$.each(r, function(rk,rv){
					if (!rv.test(text)) {
						ok = false;
						return false;
					}
				})
				li = $(li);
				vis = li.is(':visible');
				if (ok && !vis) {
					li.slideDown(100);
				} else if (!ok && vis) {
					li.slideUp(100);
				}
			})
		}
	}
	
	/**
	 * Updates the hidden field containing the selected tags
	 * based on the content of selected_tags
	 */
	this.update_field = function() {
		value_field.val(JSON.stringify(selected_tags));
	}
	
	/**
	 * Removes all events from li-tags and reassigns them.
	 */
	this.update_events = function() {
		search_list.children().unbind('dblclick').bind('dblclick', add_tag);
		assigned_list.children().unbind('dblclick').bind('dblclick', remove_tag);
	}
	
	/**
	 * Event for the tags in the pool.
	 * Adds the tag to the assigned list.
	 */
	this.add_tag = function(e) {
		t = e.target;
		if (t.tagName == 'SPAN') {
			t = t.parentNode;
		}
		t = $(t);
		t.remove().appendTo(assigned_list);
		
		tid = t.children().attr('value');
		selected_tags.push(tid);
		
		update_events();
		update_field();
		update_mandatory();
	}
	
	/**
	 * Event for the tags in the assigned list.
	 * Removes the tag from the list
	 */
	this.remove_tag = function(e) {
		t = e.target;
		if (t.tagName == 'SPAN') {
			t = t.parentNode;
		}
		t = $(t);
		t.remove().appendTo(search_list);
		
		tid = t.children().attr('value');
		selected_tags.splice(selected_tags.indexOf(tid), 1);
		
		update_events();
		update_field();
		update_mandatory();
	}
	
	/**
	 * Updates the list of mandatory categories.
	 */
	this.update_mandatory = function() {
		needed_list.children().remove();
		
		t = params.type;
		if (t == 'doc' || t == 'comp') {
			display = false;
			$.each(kkm_tags.tagcats, function(tcid, cat){
				tcid = parseInt(tcid);
				if (params.excluded.indexOf(tcid) == -1) {
					if (cat['mandatory_'+t] == 3) {
						//Category is mandatory, check for assigned tags
						
						has_tag = false;
						$.each(selected_tags, function(i, tag){
							if ($.isNumeric(tag)) {
								//Existing tag, check tcid in the DB
								if (kkm_tags.taglist[tag]['tcid'] == tcid) {
									has_tag = true;
									return false;
								}
							} else {
								//Not yet existing tag
								tag = $.parseJSON(tag);
								if (tag[0] == tcid) {
									has_tag = true;
									return false;
								}
							}
						});
						if (!has_tag) {
							cat_tag = kkm_render_tag(cat['name'], cat['color'], '', cat['name']);
							needed_list.append(cat_tag);
							display = true;
						}
					}
				}
			});
			
			//Show or hide row
			if (display) {
				needed_list.parent().parent().show();
			} else {
				needed_list.parent().parent().hide();
			}
		}
	}
	
	/**
	 * Adds the categories to the list.
	 */
	this.populate_create_tag_list = function() {
		$.each(kkm_tags.tagcats, function(tcid, cat){
			tcid = parseInt(tcid);
			not_excluded = (params.excluded.indexOf(tcid) == -1); //Tag category is not in the exclusion list
			assignable_type = (cat['mandatory_'+params.type] > 0); //Tags from this category can be assigned to this type (composition/document)
			if (not_excluded && assignable_type) {
				color = kkm_color_for_bg(cat['color']);
				opt = '<option value="'+tcid+'" style="background:#'+cat['color']+';color:#'+color+'">'+cat['name']+'</option>';
				create_list.append(opt);
			}
		});
	}
	
	/**
	 * Event for the new tag creation dropdown.
	 */
	this.create_tag = function() {
		tcid = create_list.val();
		tag_name = search_field.val().trim();
		if (tcid > 0) {
			if (tag_name.length > 0) {
				value = "["+tcid+",\""+tag_name+"\"]";
				title = kkm_tags.tagcats[tcid]['name']+": "+search_field.val();
				tag = kkm_render_tag(search_field.val(), kkm_tags.tagcats[tcid]['color'], value, title);
				
				assigned_list.append('<li>'+tag+'</li>');
				selected_tags.push(value);
				update_events();
				update_field();
				update_mandatory();
			}
			//Reset
			create_list.val(0);
		}
	}
	
	//Call init upon creation
	this.init();
}

kkm_tags = new Object();
kkm_tags.taglist_loading = false;
kkm_tags.taglist_loaded = false;
kkm_tags.taglist = {};
kkm_tags.tagcats = {};
kkm_tags.callbacks = [];
kkm_tags.load = function() {
	if (!this.taglist_loading) {
		this.taglist_loading = true;
		jQuery.getJSON(
			kkm_root_url + 'taglist',
			function(data) {
				kkm_tags.taglist = data.tags;
				kkm_tags.tagcats = data.categories;
				kkm_tags.taglist_loading = false;
				kkm_tags.taglist_loaded = true;
				
				//Call the registered callbacks
				jQuery.each(kkm_tags.callbacks, function(k,func){
					func();
				})
			}
		);
	}
}



/*
 * Conversion
 */

function kkm_conversion_validate(form) {
	jform = jQuery(form);
	
	//It's under validation
	if (form.isValidating) {
		return false;
	}
	
	//It has been validated, and there was no error -> send
	if (form.isValidationOk) {
		return true;
	}
	
	jform.find('#kkm_converter_feedback').empty();
	
	//Send data to validator
	form.isValidating = true;
	form.isValidationOk = false;
	data = {};
	jQuery.each(jform.find('input, select'), function(k,field){
		data[field.name] = jQuery(field).val();
	});
	data.action = 'options-validate';
	jQuery.post(
		form.action,
		data,
		function(result){
			form.isValidating = false;
			
			if (result == 'ok') {
				form.isValidationOk = true;
				form.submit();
			} else {
				//Add HTML returned by the validation script into the feedback panel
				jform.find('#kkm_converter_feedback').slideDown(100);
				jform.find('#kkm_converter_feedback').append(result);
			}
		}
	);
	return false;
}