<?php
/**
 * kkm
 * Search library
 * 
 * Provides functions that could be used when the user runs a search,
 * or when related entries should be retrieved.
 * 
 * @author Attila Wagner
 */

class kkm_search {
	
	/**
	 * Array of strings containing common words that
	 * will be excluded from search queries.
	 * @var array
	 */
	private static $excluded_terms = array(
		'a', 'an', 'the', 'of'
	);
	
	
	/**
	 * Runs a search in the database and returns matching documents and compositions.
	 * 
	 * In the returned array there're two elements with string keys:
	 * an array of document rows and an array of compositions rows from the DB
	 * (each is an array in itself returned by $wpdb->get_result).
	 * 
	 * @param string $search_string Search query string entered by the user.
	 * @return array Array of arrays. If no results found, both sub-arrays will be empty.
	 * If there's no valid search term present, no results will be provided.
	 */
	public static function get_search_results($search_string) {
		$matched_documents = array();
		$matched_compositions = array();
		
		$terms = self::get_search_terms($search_string);
		
		if (!empty($terms)) {
			
			//Get tags
			$matching_tags = self::get_matching_tags($terms);
			$tag_ids = array(); //List of IDs
			$tags = array(); //Associative array
			if (!empty($matching_tags)) {
				foreach ($matching_tags as $tag) {
					$tag_ids[] = $tag['tid'];
					$tags[$tag['tid']] = $tag;
				}
			}
			
			//Get compositions
			$matching_compositions = self::get_matching_compositions($terms, $tag_ids);
			$composition_ids = array(); //List of IDs
			$compositions = array(); //Associative array
			if (!empty($matching_compositions)) {
				foreach ($matching_compositions as $comp) {
					$composition_ids[] = $comp['compid'];
					$compositions[$comp['compid']] = $comp;
				}
			}
			
			//Get documents
			$matching_documents = self::get_matching_documents($terms, $tag_ids, $composition_ids);
			
			/*
			 * Filter compositions
			 * If every term was mathced by a tag or a word in the title, it
			 * will be included in the results.
			 */
			$composition_ids_in_result = array();
			foreach ($compositions as $comp_id => $comp) {
				//Start building content index for the composition with it's title
				$content = $comp['title'];
				
				//Add tag names to the array (taglist is a list of tag IDs separated by commas)
				$comp_tags = preg_split('/,/', $comp['taglist'], 0, PREG_SPLIT_NO_EMPTY);
				foreach ($comp_tags as $tag) {
					$content .= ' '.@$tags[$tag]['name'];//Add tag name from the global array
				}
				
				$comp_words = self::get_search_terms($content);
				
				if (self::is_every_term_fulfilled($terms, $comp_words)) {
					$matched_compositions[] = $comp;
					$composition_ids_in_result[] = $comp_id;
				}
			}
			
			
			/*
			 * Filter documents
			 * Just like compositions, only here we use the filename instead of the title,
			 * but append the composition title to the word list.
			 */
			foreach ($matching_documents as $doc) {
				//If the composition itself matched the search query, do not list its documents
				if (in_array($doc['compid'], $composition_ids_in_result)) {
					continue;
				}
				
				//Start building content index for the composition with it's title
				$content = $doc['filename'];
			
				//Add tag names to the array (taglist is a list of tag IDs separated by commas)
				$doc_tags = preg_split('/,/', $doc['taglist'], 0, PREG_SPLIT_NO_EMPTY);
				foreach ($doc_tags as $tag) {
					$content .= ' '.@$tags[$tag]['name'];//Add tag name from the global array
				}
				
				//Add composition title
				$content .= ' '.@$compositions[$doc['compid']]['title'];
			
				$doc_words = self::get_search_terms($content);
			
				if (self::is_every_term_fulfilled($terms, $doc_words)) {
					$matched_documents[] = $doc;
				}
			}
			
			
		}
		
		return array(
			'compositions' => $matched_compositions,
			'documents' => $matched_documents
		);
	}
	
	/**
	 * Returns the normalized search terms from the user entered
	 * search query string.
	 * 
	 * During normalization every word gets converted into lowercase,
	 * and common words get excluded. The resulting array will contain
	 * every word only once (repetitions are excluded), and will be in
	 * alphabetic order.
	 * 
	 * @param string $search_string User entered string.
	 * @return array Array of strings.
	 */
	private static function get_search_terms($search_string) {
		$terms = array();
		
		//Remove special characters and replace them with spaces
		$search_string = preg_replace('/[\p{M}\p{Z}\p{P}\p{S}\p{C}]/u', ' ', $search_string);
		
		$words = preg_split('/ +/', $search_string, 0, PREG_SPLIT_NO_EMPTY);//Separate words along space characters
		foreach ($words as $word) {
			$word = strtolower($word);
			if (!in_array($word, self::$excluded_terms)) {
				$terms[] = $word;
			}
		}
		
		$terms = array_unique($terms);
		sort($terms);
		return $terms;
	}
	
	/**
	 * Returns the tags from the database that match at least one of the search terms.
	 * 
	 * @param array $terms Array of strings. These terms are included into the SQL
	 * query without any check, so escape terms properly before passing it to this function.
	 * @return array Rows from the database.
	 */
	private static function get_matching_tags($terms) {
		global $wpdb;
		
		$tags = $wpdb->get_results(
			'select * from `kkm_tags` where `name` regexp \''.implode('|', $terms).'\'',
			ARRAY_A
		);
		return $tags;
	}
	
	/**
	 * Loads compositions from the database that have a title matching the
	 * search terms, or has one of the matching tags.
	 * 
	 * @param array $terms Array of strings. These terms are included into the SQL
	 * query without any check, so escape terms properly before passing it to this function.
	 * If null is passed, the title of the composition will not be matched.
	 * @param array $tags Array of integers, containing tag IDs.
	 * @return array Composition rows from the database.
	 */
	private static function get_matching_compositions($terms, $tags = null) {
		global $wpdb;
		
		$conditions = array();
		if (!empty($terms)) {
			$conditions[] = '`c`.`title` regexp (\''.implode('|', $terms).'\')';
		}
		if (!empty($tags)) {
			$conditions[] = '`ct`.`tid` in ('.implode(',', $tags).')';
		}
		if (empty($conditions)) {
			return array();
		}
		
		$compositions = $wpdb->get_results(
			'select `c`.*, group_concat(`ct`.`tid`) as `taglist`
			from `kkm_compositions` as `c`
			left join `kkm_comp_tags` as `ct`
				on (`ct`.`compid`=`c`.`compid`)
			where ' . implode(' or ', $conditions) . ' 
			group by `ct`.`compid`',
			ARRAY_A
		);
		
		return $compositions;
	}
	
	/**
	 * Loads documents from the database that have a filename matching the
	 * search terms, belongs to a matched composition, or has one of the matching tags.
	 *
	 * @param array $terms Array of strings. These terms are included into the SQL
	 * query without any check, so escape terms properly before passing it to this function.
	 * If null is passed, filename won't be checked.
	 * @param array $tags Array of integers, containing tag IDs.
	 * @param array $compositions Array of integers, containing composition IDs.
	 * @return array Document rows from the database.
	 */
	private static function get_matching_documents($terms, $tags = null, $compositions = null) {
		global $wpdb;
		
		$conditions = array();
		if (!empty($terms)) {
			$conditions[] = ' `d`.`filename` regexp (\''.implode('|', $terms).'\')';
		}
		if (!empty($tags)) {
			$conditions[] = '`dt`.`tid` in ('.implode(',', $tags).')';
		}
		if (!empty($compositions)) {
			$conditions[] = '`d`.`compid` in ('.implode(',', $compositions).')';
		}
		if (empty($conditions)) {
			return array();
		}
		
		$documents = $wpdb->get_results(
			'select `d`.*, group_concat(`dt`.`tid`) as `taglist`
			from `kkm_documents` as `d`
			left join `kkm_doc_tags` as `dt`
				on (`dt`.`docid`=`d`.`docid`)
			where `d`.`deleted`=0 and `d`.`complete`=1 and (' . implode(' or ', $conditions) . ')
			group by `dt`.`docid`',
			ARRAY_A
		);
		
		return $documents;
	}
	
	/**
	 * Checks whether the given list of words fulfill every search term.
	 * 
	 * If strong check is enabled, every search term must be present
	 * in the list of provided words as-is. If it's disabled, a regular
	 * expression will be used that may match a term as a part of a word.
	 * 
	 * @param array $terms Array of strings. Normalized search terms.
	 * @param array $words Array of strings. Normalized index of the document/composition.
	 * @param boolean $strong_check Determines the comparison method.
	 * Defaults to false (soft checking with regular exression).
	 * @return boolean True if every term has its match in $words.
	 */
	private static function is_every_term_fulfilled($terms, $words, $strong_check = false) {
		if ($strong_check) {
			//Returns true only if every search term is present in the words array as-is
			$not_matched_terms = array_diff($terms, $words);
			return empty($not_matched_terms);
		} else {
			
			/*
			 * Soft checking
			 * Both search terms and index words are normalized,
			 * meaning they're in alphabetic order. This way a simple
			 * regular expression can be used to test whether every term is contained
			 * in the word list.
			 */
			//Build regex
			$regex = '/' . implode('.*', $terms) . '/';
			$wordlist = implode(' ', $words);
			return preg_match($regex, $wordlist);
		}
	}
	
	/**
	 * Returns the documents and compositions that have the given tag.
	 * 
	 * In the returned array there're two elements with string keys:
	 * an array of document rows and an array of compositions rows from the DB
	 * (each is an array in itself returned by $wpdb->get_result).
	 * 
	 * @param integer $tag_id Tag ID.
	 * @return array Array of arrays. If no results found, both sub-arrays will be empty.
	 * If there's no valid search term present, no results will be provided.
	 */
	public static function get_search_results_by_tag($tag_id) {
		$tags = array((int)$tag_id);
		return array(
			'compositions' => self::get_matching_compositions(null, $tags),
			'documents' => self::get_matching_documents(null, $tags)
		);
	}
}
?>