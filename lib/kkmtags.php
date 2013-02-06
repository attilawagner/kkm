<?php
/**
 * kkm
 * Tag handler
 * 
 * @author Attila Wagner
 */

class kkm_tags {
	
	/*
	 * Built in tag categories
	 */
	
	/**
	 * The category ID for the Format tag category.
	 * @var integer
	 */
	const TYPE_FORMAT		= 1;
	
	/**
	 * The category ID for the Composer tag category.
	 * @var integer
	 */
	const TYPE_COMPOSER		= 2;
	
	/**
	 * The category ID for the Performer tag category.
	 * @var integer
	 */
	const TYPE_PERFORMER	= 3;
	
	/*
	 * Constants used with the get_tags_for_ functions.
	 */
	
	/**
	 * Used with the {@link kkm_tags::get_tags_for_composition} function.
	 * Only mandatory tags will be returned.
	 * @var integer
	 */
	const FILTER_MANDATORY	= 3;
	
	/**
	 * Used with the {@link kkm_tags::get_tags_for_composition} function.
	 * Mandatory and visible optional tags will be returned.
	 * @var integer
	 */
	const FILTER_VISIBLE	= 2;
	
	/**
	 * Used with the {@link kkm_tags::get_tags_for_composition} function.
	 * All tags will be returned regardless of their category.
	 * @var integer
	 */
	const FILTER_ALL		= 1;
	
	
	
	/**
	 * Renders a tag.
	 * 
	 * @param string $name Label of the tag.
	 * @param string $bg_color Background color of the tag.
	 * @param string $title Title of the tag.
	 * @param boolean $return If set to true, the HTML fragment of the tag will be returned,
	 * otherwise it will be echoed. Defaults to false.
	 */
	public static function render_tag($name, $bg_color, $title = '', $return = false) {
		//Get BG color from hex string
		preg_match('/([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})/i', $bg_color, $matches);
		$r = hexdec($matches[1]);
		$g = hexdec($matches[2]);
		$b = hexdec($matches[3]);
		
		//Calculate text color
		$gs = ($r + $g + $b) / 3;
		if ($gs > 128) {
			$color = '000';
		} else {
			$color = 'fff';
		}
		
		//Assemble and return
		if (!empty($title)) {
			$title = ' title="'.$title.'"';
		}
		$tag = '<span class="kkm_tag" style="background:#'.$bg_color.';color:#'.$color.';"'.$title.'>'.$name.'</span>';
		if ($return) {
			return $tag;
		} else {
			echo $tag;
		}
	}
	
	/**
	 * Returns the localized name of the mandatory flag.
	 * 
	 * @param integer $flag The mandatory flag for the tag.
	 * @return string Localized name, e.g. 'Mandatory', 'Optional'
	 */
	public static function get_mandatory_name($flag) {
		switch ($flag) {
			case 0:
				return '-';
			case 1:
				return __('Optional (hidden)', 'kkm');
			case 2:
				return __('Optional (visible)', 'kkm');
			case 3:
				return __('Mandatory', 'kkm');
		}
	}
	
	/**
	 * Renders a list of tags from the given category as a HTML &lt;select&gt;.
	 * 
	 * @param integer|string $category Category name or ID.
	 * @param boolean $multiple If true is given, a multiple choice list will be rendered.
	 * @param string $name Name and ID of the &lt;select&gt; tag.
	 * @param string $attributes Optional. This string will be inserted into the opening tag.
	 * May contain CSS class and JavaScript event definitions.
	 * @param array|integer $selected Optional. If an array of integers is given,
	 * the tags with these IDs will be selected by default.
	 * For dropdown lists, a single integer may be used.
	 * @param boolean $show_empty Optional. If set to true, an empty row will be rendered
	 * at the top with it's value set to 0.
	 * @param boolean $return Optional. If it's set to true, the HTML fragment
	 * will be returned instead of echoed.
	 */
	public static function render_tag_list($category, $multiple, $name, $attributes = '', $selected = null, $show_empty = false, $return = false) {
		global $wpdb;
		
		/*
		 * If category is given as string, get the ID.
		 */
		if (!is_numeric($category)) {
			$category = $wpdb->get_var(
				$wpdb->prepare(
					"select `tcid` from `kkm_tag_categories` where `name`=%s limit 1;",
					$category
				)
			);
			//An error occurred: maybe there's no category with this name.
			if ($category == null) {
				return;
			}
		}
		
		/*
		 * Get tags from DB.
		 */
		$tags = $wpdb->get_results(
			$wpdb->prepare(
				"select `tid`, `name`, `alias`, `parent` from `kkm_tags` where `tcid`=%d",
				$category
			),
			ARRAY_A
		);
		
		/*
		 * Sanitize $selected array.
		 * The render_tag_list_items reqires an array.
		 */
		if (!is_array($selected)) {
			if (is_numeric($selected)) {
				$selected = array($selected);
			} else {
				$selected = array();
			}
		}
		/*
		 * Render the list.
		 */
		$list = '<select name="'.$name.'" id="'.$name.'" '.($multiple ? 'multiple="multiple" ' : '').$attributes.'>';
		if ($show_empty) {
			$list .= '<option value="0"></option>';
		}
		$list .= self::render_tag_list_items($tags, 0, $selected);
		$list .= '</select>';
		
		
		if ($return) {
			return $list;
		} else {
			echo $list;
		}
	}
	
	/**
	 * Renders &lt;option&gt; elements for tags that belong to the given parent tag.
	 * If a tag has children, this function will be called again.
	 * 
	 * @param array $tags Tags from the DB.
	 * @param integer $parent Parent tag ID.
	 * @param array $selected If an array of integers are given,
	 * the tags with these IDs will be selected by default.
	 * @param integer $level Indentation level. Defaults to 0.
	 * @return string HTML fragment.
	 */
	private static function render_tag_list_items($tags, $parent, $selected, $level = 0) {
		$ret = '';
		
		foreach ($tags as $tag) {
			if ($tag['parent'] == $parent) {
				$tag_sel = (in_array($tag['tid'], $selected) ? ' selected="selected"' : '');
				$tag_class = ($tag['alias'] ? ' class="alias"' : '');
				
				$ret .= '<option value="'.$tag['tid'].'"'.$tag_sel.$tag_class.'>';
				$ret .= str_repeat(' ', $level).$tag['name'];
				$ret .= '</option>';
				
				$ret .= self::render_tag_list_items($tags, $tag['tid'], $selected, $level+1);
			}
		}
		
		return $ret;
	}
	
	/**
	 * Renders an HTML interface for assigning tags to the document.
	 * The generated code is returned.
	 * 
	 * @param integer $doc_id Document ID in the database.
	 * @param string $field_name Name for the form element that stores the selected tags.
	 * @param array $suggested_tags Optional array of tags that are currently not assigned to the document.
	 * Elements of this array may be integers (tag IDs), or string arrays (tag category - name) pairs
	 * or arrays of (category ID - name) pairs. 
	 * @return string HTML fragment.
	 * @see kkm_tags::render_tagging_box_for_comp
	 * @see kkm_tags::render_tagging_box
	 */
	public static function render_tagging_box_for_doc($doc_id, $field_name, $suggested_tags = null) {
		global $wpdb;
		
		/*
		 * Get assigned tags
		 */
		$tags = $wpdb->get_results(
			$wpdb->prepare(
				'select `tid` from `kkm_doc_tags` where `docid`=%d;',
				$doc_id
			),
			ARRAY_N
		);
		
		$assigned = array();
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				$assigned[] = (int)$tag[0];
			}
		}
		
		/*
		 * Sanitize suggested tags
		 */
		if (is_array($suggested_tags)) {
			$suggested_tags = self::sanitize_suggested_tags($suggested_tags);
		} else {
			$suggested_tags = null;
		}
		
		/*
		 * Render
		 * Format category is excluded because that was choosen at the beginning of the upload process.
		 */
		return self::render_tagging_box($field_name, 'doc', $assigned, $suggested_tags, array(self::TYPE_FORMAT));
	}
	
	/**
	 * Renders an HTML interface for assigning tags to a composition.
	 * The generated code is returned.
	 *
	 * @param integer $comp_id Composition ID in the database.
	 * @param string $field_name Name for the form element that stores the selected tags.
	 * @param array $suggested_tags Optional array of tags that are currently not assigned to the document.
	 * Elements of this array may be integers (tag IDs), or string arrays (tag category - name) pairs
	 * or arrays of (category ID - name) pairs.
	 * @return string HTML fragment.
	 * @see kkm_tags::render_tagging_box_for_doc
	 * @see kkm_tags::render_tagging_box
	 */
	public static function render_tagging_box_for_comp($comp_id, $field_name, $suggested_tags = null) {
		global $wpdb;
	
		/*
		 * Get assigned tags
		*/
		$tags = $wpdb->get_results(
			$wpdb->prepare(
				'select `tid` from `kkm_comp_tags` where `compid`=%d;',
				$comp_id
			),
			ARRAY_N
		);
	
		$assigned = array();
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				$assigned[] = (int)$tag[0];
			}
		}
	
		/*
		 * Sanitize suggested tags
		*/
		if (is_array($suggested_tags)) {
			$suggested_tags = self::sanitize_suggested_tags($suggested_tags);
		} else {
			$suggested_tags = null;
		}
	
		/*
		 * Render
		* Format category is excluded because that was choosen at the beginning of the upload process.
		*/
		return self::render_tagging_box($field_name, 'comp', $assigned, $suggested_tags);
	}
	
	/**
	 * Tries to load existing tag IDs for the given category-name pairs and removes tags
	 * that do not fit any existing category.
	 * 
	 * @param array $suggested_tags Array of tags that are currently not assigned to the document.
	 * Elements of this array may be integers (tag IDs), or string arrays (tag category - name) pairs
	 * or arrays of (category ID - name) pairs.
	 */
	private static function sanitize_suggested_tags($suggested_tags) {
		global $wpdb;
		
		$tag_ids = array(); //Existing tag IDs
		$cat_tag_names = array(); //Category name - tag name pairs
		$catid_tag_names = array(); //tcid - tag name pairs
		$catid_tag_names_clean = array(); //This will contain only tags that do not exist in the DB
		
		/*
		 * Filter tags into different lists
		 */
		foreach ($suggested_tags as $tag) {
			if (is_numeric($tag)) {
				$tag_ids[] = (int)$tag; //Cast to int if it was string containing numeric data
				
			} elseif (is_array($tag)) {
				if (is_numeric($tag[0]) && is_string($tag[1])) {
					//tcid - tag name pair
					$catid_tag_names[] = $tag;
				} elseif (is_string($tag[0]) && is_string($tag[1])) {
					//category name - tag name pair
					$cat_tag_names[] = $tag;
				}
			}
		}
		
		/*
		 * Try to resolve category names
		 */
		//Get category names
		$cat_names = array();
		foreach ($cat_tag_names as $tag) {
			$cat_names[] = $tag[0];
		}
		$cat_names = array_unique($cat_names); //Remove duplicate entries
		$cat_names = $wpdb->escape($cat_names); //Escape slashes
		
		if (!empty($cat_names)) {
			//Query DB
			$categories = $wpdb->get_results(
				'select `name`, `tcid` from `kkm_tag_categories` where `name` in (\''.implode('\',\'', $cat_names).'\')',
				ARRAY_A
			);
			
			//Build associative array
			$cat_cache = array();
			foreach ($categories as $cat) {
				$cat_cache[$cat['name']] = $cat['tcid'];
			}
			
			//Move tags with existing categories into the $catid_tag_names array.
			foreach ($cat_tag_names as $tag) {
				if (array_key_exists($tag[0], $cat_cache)) {
					$catid_tag_names[] = array($cat_cache[$tag[0]], $tag[1]); //Resolve name into tcid and add to the array
				}
			}
		}
		
		
		/*
		 * Check for existing tags
		 */
		//Get tag names
		$tag_names = array();
		foreach ($catid_tag_names as $tag) {
			$tag_names[] = $tag[1];
		}
		$tag_names = array_unique($tag_names); //Remove duplicate entries
		$tag_names = $wpdb->escape($tag_names); //Escape slashes
		
		if (!empty($tag_names)) {
			//Query DB
			$tags = $wpdb->get_results(
				'select `name`, `tcid`, `tid` from `kkm_tags` where `name` in (\''.implode('\',\'', $tag_names).'\')',
				ARRAY_A
			);
			
			//Build multidimensional associative array where first key is tcid and the second is the tag name
			$tag_cache = array();
			foreach ($tags as $tag) {
				if (!array_key_exists($tag['tcid'], $tag_cache)) {
					$tag_cache[$tag['tcid']] = array();
				}
				$tag_cache[$tag['tcid']][$tag['name']] = $tag['tid'];
			}
			
			//Resolve existing tags and move them to the $tag_ids array
			foreach ($catid_tag_names as $tag) {
				list($tcid, $name) = $tag;
				if (array_key_exists($tcid, $tag_cache) && array_key_exists($name, $tag_cache[$tcid])) {
					$tag_ids[] = $tag_cache[$tcid][$name]; //Existing tag is moved as tag ID.
				} else {
					$catid_tag_names_clean[] = $tag; //Not yet existing tag is saved
				}
			}
		}
		
		
		/*
		 * We now have the tag ID for every existing tag, and the tcid-name pairs for
		 * every tag suggestion that has a valid tag category.
		 */
		return array_merge($tag_ids, $catid_tag_names_clean);
	}
	
	/**
	 * Renders a HTML fragment containing the interface for assigning tags to a document or composition.
	 * The generated code is returned.
	 * 
	 * @param string $field_name Name for the form element that stores the selected tags.
	 * @param string $type Enforce mandatory tag categories for documents ('doc') or compositions ('comp').
	 * When left empty, no enforecement will be done.
	 * @param array $assigned_tags Array of tag IDs. Optional.
	 * @param array $suggested_tags Array of integers and [category ID - name] pairs.
	 * @param array $excluded_categories Array of integers (category IDs). Tags from these categories won't be displayed.
	 * @return string HTML fragment.
	 * @see kkm_tags::render_tagging_box_for_doc()
	 */
	public static function render_tagging_box($field_name, $type = '', $assigned_tags = null, $suggested_tags = null, $excluded_categories = null) {
		$ret = '<table class="kkm_tagging" id="kkm_tagging_table_'.$field_name.'">';
		
		//Filter row
		$ret .= '<tr><td colspan="2">';
		$ret .= '<input type="text" name="kkm_tagging_search" id="kkm_tagging_search_'.$field_name.'" />';
		$ret .= '<select name="kkm_tagging_newcat" id="kkm_tagging_newcat_'.$field_name.'"><option value="0">'.__('Create as new tag in...','kkm').'</option></select>';
		$ret .= '</td></tr>';
		
		//Taglist caption row
		$ret .= '<tr><td>';
		$ret .= __('Avaliable tags:','kkm');
		$ret .= '</td><td>';
		$ret .= __('Assigned tags:','kkm');
		$ret .= '</td></tr>';
		
		//Taglist row
		$ret .= '<tr><td>';
		$ret .= '<ul class="kkm_taglist" id="kkm_tagging_pool_'.$field_name.'"></ul>';
		$ret .= '</td><td>';
		$ret .= '<ul class="kkm_taglist" id="kkm_tagging_assigned_'.$field_name.'"></ul>';
		$ret .= '</td></tr>';
		
		//Mandatory row
		$ret .= '<tr><td colspan="2">'.__('Categories you need to choose from:', 'kkm').'<div id="kkm_tagging_needed_'.$field_name.'"></div></td></tr>';
		$ret .= '</table>';
		$ret .= '<input type="hidden" name="'.$field_name.'" id="'.$field_name.'" />';
		$params = array(
			'name'			=> $field_name,
			'type'			=> $type,
			'assigned'		=> is_array($assigned_tags) ? $assigned_tags : array(),
			'suggested'		=> is_array($suggested_tags) ? $suggested_tags : array(),
			'excluded'		=> is_array($excluded_categories) ? $excluded_categories : array()
		);
		$ret .= '<script type="text/javascript">kkm_tagging_box('.json_encode($params).');</script>';
		return $ret;
	}
	
	/**
	 * Saves the tags assigned to the document on the HTML interface by the user.
	 * 
	 * @param integer $doc_id Document ID in the database.
	 * @param string $taglist The value returned by the tagging interface.
	 * @see kkm_tags::save_tags_for_comp
	 */
	public static function save_tags_for_doc($doc_id, $taglist) {
		global $wpdb;
		
		//Remove every tag except the format
		self::remove_tags_from_doc($doc_id, array(self::TYPE_FORMAT));
		
		/*
		 * Get tags from the taglist
		 */
		$tags = json_decode($taglist);
		
		//Filter tags into existing and new
		$new_tags = array();
		$tag_ids = array();
		foreach ($tags as $tag) {
			if (is_numeric($tag)) {
				$tag_ids[] = (int)$tag;
			} else {
				$tag = @json_decode($tag);
				if (is_array($tag) && is_numeric($tag[0]) && is_string($tag[1])) { //Check variable types
					$tag_name = trim($tag[1]); //Trim whitespace
					if ($tag[0] > 0 && strlen($tag_name) > 0) { //Do not allow invalid values
						$new_tags[] = array($tag[0], $tag_name);
					}
				}
			}
		}
		
		//Create new tags
		if (!empty($new_tags)) {
			$new_ids = self::create_tags($new_tags);
			$tag_ids = array_merge($tag_ids, $new_ids);
		}
		
		if (!empty($tag_ids)) {
			//Build SQL query
			$sql_pairs = array(); //(docid, tid) pairs
			foreach ($tag_ids as $tid) {
				$sql_pairs[] = $wpdb->prepare(
					'(%d,%d)',
					$doc_id,
					$tid
				);
			}
			
			$wpdb->query(
				'insert into `kkm_doc_tags` (`docid`, `tid`) values '.implode(',', $sql_pairs).' on duplicate key update `dtagid`=`dtagid`;'
			);
		}
	}
	
	/**
	 * Saves the tags assigned to the composition on the HTML interface by the user.
	 *
	 * @param integer $comp_id Composition ID in the database.
	 * @param string $taglist The value returned by the tagging interface.
	 * @see kkm_tags::save_tags_for_doc
	 */
	public static function save_tags_for_comp($comp_id, $taglist) {
		global $wpdb;
	
		//Remove every tag
		self::remove_tags_from_comp($comp_id);
	
		/*
		 * Get tags from the taglist
		*/
		$tags = json_decode($taglist);
	
		//Filter tags into existing and new
		$new_tags = array();
		$tag_ids = array();
		foreach ($tags as $tag) {
			if (is_numeric($tag)) {
				$tag_ids[] = (int)$tag;
			} else {
				$tag = json_decode($tag);
				if (is_array($tag) && is_numeric($tag[0]) && is_string($tag[1])) { //Check variable types
					$tag_name = trim($tag[1]); //Trim whitespace
					if ($tag[0] > 0 && strlen($tag_name) > 0) { //Do not allow invalid values
						$new_tags[] = array($tag[0], $tag_name);
					}
				}
			}
		}
	
		//Create new tags
		if (!empty($new_tags)) {
			$new_ids = self::create_tags($new_tags);
			$tag_ids = array_merge($tag_ids, $new_ids);
		}
	
		if (!empty($tag_ids)) {
			//Build SQL query
			$sql_pairs = array(); //(docid, tid) pairs
			foreach ($tag_ids as $tid) {
				$sql_pairs[] = $wpdb->prepare(
					'(%d,%d)',
					$comp_id,
					$tid
				);
			}
				
			$wpdb->query(
				'insert into `kkm_comp_tags` (`compid`, `tid`) values '.implode(',', $sql_pairs).' on duplicate key update `ctagid`=`ctagid`;'
			);
		}
	}
	
	/**
	 * Removes every tag from the document that does not belong to the
	 * list of excluded categories.
	 * 
	 * @param integer $doc_id Document ID in the database.
	 * @param array $excluded_categories Optional. Array of tag category IDs.
	 * @see kkm_tags::save_tags_for_doc
	 */
	private static function remove_tags_from_doc($doc_id, $excluded_categories = null) {
		global $wpdb;
		
		$where = '';
		if (is_array($excluded_categories)) {
			//Get excluded IDs from DB
			$excluded = $wpdb->get_results(
				$wpdb->prepare(
					'select `t`.`tid`
					from `kkm_tags` as `t`
					left join `kkm_doc_tags` as `dt`
						on (`dt`.`tid`=`t`.`tid`)
					where `dt`.`docid`=%d and `t`.`tcid` in ('.implode(',', $excluded_categories).');',
					$doc_id
				),
				ARRAY_N
			);
			
			if (!empty($excluded)) {
				$excluded_tids = array();
				foreach ($excluded as $tag) {
					$excluded_tids[] = $tag[0];
				}
				
				$where = ' and `tid` not in ('.implode(',', $excluded_tids).')';
			}
		}
		
		//Delete tag links
		$wpdb->query(
			$wpdb->prepare(
				'delete from `kkm_doc_tags` where `docid`=%d'.$where,
				$doc_id
			)
		);
	}
	
	/**
	 * Removes every tag from the composition that does not belong to the
	 * list of excluded categories.
	 *
	 * @param integer $comp_id Composition ID in the database.
	 * @param array $excluded_categories Optional. Array of tag category IDs.
	 * @see kkm_tags::save_tags_for_doc
	 */
	private static function remove_tags_from_comp($comp_id, $excluded_categories = null) {
		global $wpdb;
	
		if (is_array($excluded_categories)) {
			//Get excluded IDs from DB
			$excluded = $wpdb->get_results(
				$wpdb->prepare(
					'select `t`.`tid`
					from `kkm_tags` as `t`
					left join `kkm_comp_tags` as `ct`
						on (`ct`.`tid`=`t`.`tid`)
					where `ct`.`compid`=%d and `t`.`tcid` in ('.implode(',', $excluded_categories).');',
					$comp_id
				),
				ARRAY_N
			);
				
			$excluded_tids = array();
			foreach ($excluded as $tag) {
				$excluded_tids[] = $tag[0];
			}
				
			$where = ' and `tid` not in ('.implode(',', $excluded_tids).')';
		} else {
			//No exclusions
			$where = '';
		}
	
		//Delete tag links
		$wpdb->query(
			$wpdb->prepare(
				'delete from `kkm_comp_tags` where `compid`=%d'.$where,
				$comp_id
			)
		);
	}
	
	/**
	 * Saves new tags into the database.
	 * 
	 * @param array $tags Array of (tag category ID - tag name) pairs.
	 * @return array Tag IDs of newly created tags.
	 */
	public static function create_tags($tags) {
		global $wpdb;
		if (!empty($tags)) {
			
			$sql_pairs = array(); //(tcid, name) pairs
			//Escape names
			foreach ($tags as $tag) {
				$sql_pairs[] = $wpdb->prepare(
					'(%d, %s)',
					$tag[0],
					$tag[1]
				);
			}
			
			//Insert
			$wpdb->query(
				'insert into `kkm_tags` (`tcid`,`name`) values '.implode(',', $sql_pairs).' on duplicate key update `tid`=`tid`;'
			);
			
			//Get IDs
			$new_ids = $wpdb->get_results(
				'select `tid` from `kkm_tags` where (`tcid`,`name`) in ('.implode(',', $sql_pairs).')',
				ARRAY_N
			);
			$ret = array();
			foreach ($new_ids as $id) {
				$ret[] = $id[0];
			}
			return $ret;
		}
	}
	
	/**
	 * Returns tags for the given composition.
	 * 
	 * The $mandatory_filter parameter restricts the tag categories from which the tags will be returned:
	 * <ul>
	 *   <li>3 - Only mandatory tags</li>
	 *   <li>2 - Mandatory and visible optional tags</li>
	 *   <li>1 - Every tag assigned to the composition</li>
	 * </ul>
	 * 
	 * @param integer $comp_id Composition ID in the database.
	 * @param integer $mandatory_filter One of the FILTER_* constants.
	 * @return array Tags from the database. Category names and colors will be appended to the rows.
	 * If no tags could be found, an empty array will be returned.
	 * @see kkm_tags::get_tags_for_document
	 */
	public static function get_tags_for_composition($comp_id, $mandatory_filter) {
		global $wpdb;
		
		$tags = $wpdb->get_results(
			$wpdb->prepare(
				'select `t`.*, `tc`.`name` as `catname`, `tc`.`color`
				from `kkm_tags` as `t`
				left join `kkm_tag_categories` as `tc`
					on (`tc`.`tcid`=`t`.`tcid`)
				left join `kkm_comp_tags` as `ct`
					on (`ct`.`tid`=`t`.`tid`)
				where `tc`.`mandatory_comp`>=%d and `ct`.`compid`=%d
				order by `tc`.`mandatory_comp` desc, `catname` asc, `t`.`name` asc;',
				$mandatory_filter,
				$comp_id
			),
			ARRAY_A
		);
		
		return $tags;
	}
	
	/**
	 * Returns tags for the given document.
	 *
	 * The $mandatory_filter parameter restricts the tag categories from which the tags will be returned:
	 * <ul>
	 *   <li>3 - Only mandatory tags</li>
	 *   <li>2 - Mandatory and visible optional tags</li>
	 *   <li>1 - Every tag assigned to the composition</li>
	 * </ul>
	 *
	 * @param integer $doc_id Document ID in the database.
	 * @param integer $mandatory_filter One of the FILTER_* constants.
	 * @return array Tags from the database. Category names and colors will be appended to the rows.
	 * If no tags could be found, an empty array will be returned.
	 * @see kkm_tags::get_tags_for_composition
	 */
	public static function get_tags_for_document($doc_id, $mandatory_filter) {
		global $wpdb;
	
		$tags = $wpdb->get_results(
			$wpdb->prepare(
				'select `t`.*, `tc`.`name` as `catname`, `tc`.`color`
				from `kkm_tags` as `t`
				left join `kkm_tag_categories` as `tc`
					on (`tc`.`tcid`=`t`.`tcid`)
				left join `kkm_doc_tags` as `dt`
					on (`dt`.`tid`=`t`.`tid`)
				where `tc`.`mandatory_doc`>=%d and `dt`.`docid`=%d
				order by `tc`.`mandatory_doc` desc, `catname` asc, `t`.`name` asc;',
				$mandatory_filter,
				$doc_id
			),
			ARRAY_A
		);
	
		return $tags;
	}
	
	/**
	 * Loads the format tag of a document.
	 * 
	 * @param integer $doc_id Document ID in the database.
	 * @return array Tag from the database. Category name and color will be appended to the row.
	 * If the tag could not be found, null will be returned.
	 */
	public static function get_format_tag_for_document($doc_id) {
		global $wpdb;
		
		$tag = $wpdb->get_row(
			$wpdb->prepare(
				'select `t`.*, `tc`.`name` as `catname`, `tc`.`color`
				from `kkm_tags` as `t`
				left join `kkm_tag_categories` as `tc`
					on (`tc`.`tcid`=`t`.`tcid`)
				left join `kkm_doc_tags` as `dt`
					on (`dt`.`tid`=`t`.`tid`)
				where `tc`.`tcid`=%s and `dt`.`docid`=%d
				limit 1;',
				self::TYPE_FORMAT,
				$doc_id
			),
			ARRAY_A
		);
		
		return $tag;
	}
	
	/**
	 * Checks whether the given tid belongs to an existing tag in
	 * the Format category. Used to validate value sent by the user.
	 * 
	 * @param integer $tag_id The tid of a tag.
	 * @return boolean True if it's a valid format tag, false otherwise.
	 */
	public static function is_valid_format_tag($tag_id) {
		global $wpdb;
		
		$tag_category = $wpdb->get_var(
			$wpdb->prepare(
				'select `tcid` from `kkm_tags` where `tid`=%d',
				$tag_id
			)
		);
		
		return ($tag_category == self::TYPE_FORMAT);
	}
	
	/**
	 * Renders the tags for the given document as a HTML &lt;table&gt;.
	 * 
	 * @param integer $doc_id Document ID.
	 */
	public static function render_tag_table_for_document($doc_id) {
		$tags = self::get_tags_for_document($doc_id, self::FILTER_ALL);
		self::render_tag_table($tags);
	}
	
	/**
	 * Renders the tags for the given composition as a HTML &lt;table&gt;.
	 *
	 * @param integer $comp_id Composition ID.
	 */
	public static function render_tag_table_for_composition($comp_id) {
		$tags = self::get_tags_for_composition($comp_id, self::FILTER_ALL);
		self::render_tag_table($tags);
	}
	
	/**
	 * Renders the tags in a HTML &lt;table&gt; element.
	 * 
	 * @param array $tags The result of get_tags_for_composition() or get_tags_for_document().
	 */
	private static function render_tag_table($tags) {
		if (empty($tags)) {
			return;
		}
		
		$prev_tcid = 0;
		$first_cat = true;
		$first_tag = true;
		echo('<table class="kkm_tag_table">');
		foreach ($tags as $tag) {
			if ($tag['tcid'] != $prev_tcid) {
				if (!$first_cat) {
					echo('</td></tr>');
					$first_tag = true;
				} else {
					$first_cat = false;
				}
				echo('<tr><th>'.$tag['catname'].':</th><td>');
				$prev_tcid = $tag['tcid'];
			}
			
			if (!$first_tag) {
				echo('<br/>');
			} else {
				$first_tag = false;
			}
			
			/* translators: First string is the category name and the second is the tag name. Used as the title for the tag. */
			$title = sprintf(__('%1$s: %2$s','kkm'), $tag['catname'], $tag['name']);
			kkm_tags::render_tag($tag['name'], $tag['color'], $title);
		}
		echo('</td></tr>');
		echo('</table>');
	}
	
	/**
	 * Updates the tag hierarchy so that the given tag will have $parent as its new parent tag.
	 * 
	 * This function updates both the kkm_tags and kkm_tag_relations tables.
	 * The parent of a tag cannot be itself or one of its descendants. If its called to do
	 * such an update, the function will return with false without updating the tables. 
	 * 
	 * @param integer $tag_id Tag ID.
	 * @param mixed $parent ID of new parent tag or 'null'.
	 * @return boolean Returns false on failure, true on success.
	 */
	public static function update_tag_parent($tag_id, $parent) {
		global $wpdb;
		
		if ($tag_id == $parent) {
			return false;
		}
		
		//Get old ancestors
		$ancestors = array();
		$ancestor_rows = $wpdb->get_results(
			$wpdb->prepare(
				'select `ancestor` from `kkm_tag_relations` where `descendant`=%d',
				$tag_id
			),
			ARRAY_N
		);
		foreach ($ancestor_rows as $row) {
			$ancestors[] = $row[0];
		}
		
		//Get descendants
		$descendant_rows = $wpdb->get_results(
			$wpdb->prepare(
				'select `descendant` from `kkm_tag_relations` where `ancestor`=%d',
				$tag_id
			),
			ARRAY_N
		);
		$descendants = array();
		foreach ($descendant_rows as $row) {
			$descendants[] = $row[0];
		}
		
		/*
		 * Check:
		 * A tag cannot be the descendant of its descendant.
		 */
		if (in_array($parent, $descendants)) {
			return false;
		}
		
		/*
		 * Build pairs to delete
		 * We want to delete every ancestor-descendant pair where the ancestor
		 * is one of the tag's ancestors (including its parent), and the descendant is
		 * the tag itself or the descendant of the tag.
		 * Pairs where the ancestor is the tag can be kept.
		 */
		$delete_pairs = array();
		foreach ($ancestors as $ancestor) {
			$delete_pairs[] = '('.$ancestor.','.$tag_id.')';
			foreach ($descendants as $descendant) {
				$delete_pairs[] = '('.$ancestor.','.$descendant.')';
			}
		}
		
		//Delete old values
		if (!empty($delete_pairs)) {
			$wpdb->query(
				'delete from `kkm_tag_relations` where (`ancestor`,`descendant`) in ('.implode(',', $delete_pairs).');'
			);
		}
		
		if (is_numeric($parent) || !empty($descendants)) {
			//Get new ancestors: ancestors of the parent tag
			$new_ancestors = array();
			if (is_numeric($parent)) {
				$new_ancestors[] = $parent; //Add parent to the ancestors
					
				$new_ancestor_rows = $wpdb->get_results(
					$wpdb->prepare(
						'select `ancestor` from `kkm_tag_relations` where `descendant`=%d',
						$parent
					),
					ARRAY_N
				);
				foreach ($new_ancestor_rows as $row) {
					$new_ancestors[] = $row[0];
				}
			}
		
			if (!empty($new_ancestors)) {
				//Build value pairs
				$new_relations = array();
				foreach ($new_ancestors as $ancestor) {
					$new_relations[] = '('.$ancestor.','.$tag_id.')';
					foreach ($descendants as $descendant) {
						$new_relations[] = '('.$ancestor.','.$descendant.')';
					}
				}
					
				//Insert
				$wpdb->query(
					'insert ignore into `kkm_tag_relations` (`ancestor`,`descendant`) values '.implode(',',$new_relations).';'
				);
			}
		}
		
		
		/*
		 * Update tag parent
		 * At this point, we know that the new parent is not one of its descendants.
		 */
		$wpdb->query(
			$wpdb->prepare(
				'update `kkm_tags` set `parent`='.$parent.' where `tid`=%d limit 1;',
				$tag_id
			)
		);
		
		return true;
	}
	
	/**
	 * Deletes a tag from the database.
	 * 
	 * The tag will be removed from every document and composition,
	 * and children tags will be updated so as their parent will be set
	 * to the parent of this tag. Tag hierarchy is maintained.
	 * 
	 * @param integer $tag_id Tag ID.
	 * @return boolean True on success, false on failure.
	 */
	public static function delete_tag($tag_id) {
		global $wpdb;
		
		$tag = $wpdb->get_row(
			$wpdb->prepare(
				'select * from `kkm_tags` where `tid`=%d limit 1;',
				$tag_id
			),
			ARRAY_A
		);
		if (empty($tag)) {
			return false;
		}
		
		$parent = empty($tag['parent']) ? 'null' : $tag['parent'];
		
		/*
		 * Update kkm_tags table.
		 */
		$wpdb->query(
			$wpdb->prepare(
				'update `kkm_tags` set `parent`='.$parent.' where `parent`=%d;',
				$tag_id
			)
		);
		
		/*
		 * Delete rows from the hierarchy table.
		 */
		$wpdb->query(
			$wpdb->prepare(
				'delete from `kkm_tag_relations` where `ancestor`=%d or `descendant`=%d;',
				$tag_id,
				$tag_id
			)
		);
		
		/*
		 * Remove tag
		 */
		$wpdb->query(
			$wpdb->prepare(
				'delete from `kkm_tags` where `tid`=%d limit 1;',
				$tag_id
			)
		);
		
		return true;
	}
}
?>