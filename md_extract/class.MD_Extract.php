<?php


class MD_Extract {
	
	/**
	 * Where the itermediate results are stored. (In intermediate data structure form)
	 */
	private $results = array();
	/**
	 * The clean results.
	 */
	private $clean_results = array();
	/**
	 * Array of references to html nodes with ids.
	 */
	public $node_ids = array();
	/**
	 * Errors encountered.
	 */
	private $errors = array();
	/**
	 * The html tree. (that is refernced by node_ids)
	 */
	private $html_tree = array();
	/**
	 * The Base of the Document to be used when processing Images and URIS.
	 */
	private $base = "";
	/**
	 * The URI if provided. Used when deciding the base.
	 */
	private $URI = "";
	
	
	/**
	 * 
	 * Factor(ish) creation of the object by html.
	 * 
	 * @param unknown_type $html
	 * @param string $URI
	 * @param string $encoding The encoding of $html (in the version that mb_string uses)
	 */
	public static function create_by_html($html, $URI = "", $encoding = "") {
		$mdx = new MD_Extract();
		//Prepare itemscope for tidy
		$html = $mdx->itemscope_for_tidy($html, $encoding);
		//Create tidy node
		$tidy = tidy_parse_string($html, array("new-blocklevel-tags" => "section, header, footer, nav, article, aside, figure, dialog, video, audio, details, datagrid, menu, command, output",
		 "new-inline-tags" => "mark, time, meter, progress, figcaption, ruby, rt, rp, bdi, keygen, mdxmeta",
		 "new-empty-tags" => "video, audio, wbr, mdxmeta"));
		//Set $URI
		$mdx->set_URI($URI);
		//Decide Base
		$mdx->decide_base( $tidy->head() );
		//extract
		$mdx->extract($tidy->body());
		//do clean results
		$mdx->do_clean_results();
		return $mdx;
	}

	/**
	 * 
	 * Extract all items.
	 * @param unknown_type $html_tree
	 */
	public function extract($html_tree) {
		$this->html_tree = $html_tree;
		//get node ids
		$this->traverse_for_ids($this->html_tree);
		//get results
		$this->traverse($this->html_tree, $this->results);
		
	}
	
	/**
	 * 
	 * Function for traversing the html tree to get the ids
	 * 
	 * @param tidy_node $node
	 */
	private function traverse_for_ids(&$node) {
		if(isset($node->attribute['id'])) {
			if( isset($this->node_ids[ $node->attribute['id'] ]) ) {
				//Repetition of ID
				$this->new_error(MDX_REPEATED_ID, $node);
			} else {
				$this->node_ids[ $node->attribute['id'] ]['node'] =& $node;
			}
		}
		if(isset($node->child) && is_array($node->child)) {
			foreach($node->child as &$child) {
				$this->traverse_for_ids($child);
			}
		}
	}
	
	/**
	 * 
	 * Traverse the HTML tree to get items.
	 * @param unknown_type $node
	 * @param unknown_type $results
	 */
	private function traverse(&$node, &$results) {
		if(isset($node->attribute['itemscope'])) {
			//Check if it has a determined itemtype
			if(isset($node->attribute['itemtype'])) {
				$this->crawl($node, $results[$node->attribute['itemtype']][]);
			} else {
				//It doesn't that's not very cool. Maybe I should store a warning in errors. 
				$this->crawl($node, $results['untyped'][]);
			}
		} else {
			if(isset($node->child) && is_array($node->child)) {
				foreach($node->child as &$child) {
					$this->traverse($child, $results);
				}
			}		
		}
	}
	
	/**
	 * 
	 * Crawl an item.
	 * 
	 * @param unknown_type $node
	 * @param unknown_type $results
	 */
	private function crawl(&$node, &$results) {
		if(isset($node->attribute['itemref'])) {
			$itemrefs = explode(" ", $node->attribute['itemref']);
			foreach($itemrefs as $itemref) {
				$itemref = trim($itemref);
				//Check if node exists
				if( isset($this->node_ids[ $itemref] ) ) {
					//Check if it has already passed. If it has do error.
					if( isset($this->node_ids[ $itemref]['reffed_by']) && $this->node_in_array($node, $this->node_ids[ $itemref]['reffed_by']) ) {
						$this->new_error(MDX_ITEMREF_LOOP, $node);
					} else {
						//add to reffed_bys
						$this->node_ids[ $itemref]['reffed_by'][] =& $node;
						//crawl
						$this->crawl_props($this->node_ids[ $itemref]['node'], $results);
					}
				} else {
					$this->new_error(MDX_ITEMREF_NOT_EXISTS, $node);
				}
			}
		}
		//check whether it's top level or not
		if( isset($node->attribute['itemprop']) ) {
			$results['top_level'] = false;
		} else {
			$results['top_level'] = true;
		}
		//Check for itemid
		if( isset($node->attribute['itemid']) ) {
			$results['itemid'] = $node->attribute['itemid'];
		}
		//Add itemType.. even thought it's in the key of the array. If it's a child item it might have both an itemprop and an itemtype
		if( isset($node->attribute['itemtype']) ) {
			$results['itemtype'] = $node->attribute['itemtype'];
		}
		
		
		
		if(isset($node->child) && is_array($node->child)) {
			foreach($node->child as $child) {
				$this->crawl_props($child, $results);
			}
		} else {
			$this->new_error(MDX_ITEMSCOPE_WITHOUT_CHILDS, $node);
			
		}		
	}

	/**
	 * 
	 * Extract all props from an item.
	 * 
	 * @param unknown_type $node
	 * @param unknown_type $results
	 */
	private function crawl_props(&$node, &$results) {
		if(isset($node->attribute['itemprop'])) {
			$this->get_prop_value($node, $results);
		} else {
			if(isset($node->child) && is_array($node->child)) {
				foreach($node->child as $child) {
					$this->crawl_props($child, $results);
				}
			}		
		}
	}
	
	/**
	 * 
	 * Function to get the value of a node.
	 * 
	 * @param unknown_type $node
	 * @param unknown_type $results
	 */
	private function get_prop_value(&$node, &$results) {
		if(isset($node->attribute['itemscope'])) {
			$this->crawl($node, $results['childs'][ $node->attribute['itemprop'] ][]);
		} else {
			//Switch content extraction method base on tag
			switch($node->name) {
				case "mdxmeta":
					if(isset($node->attribute['content'])) {
						$results['childs'][ $node->attribute['itemprop'] ][] = $node->attribute['content'];
					} else {
						$this->new_error(MDX_META_NO_CONTENT, $node);
					}
				break;
				case "audio":
				case "embed":
				case "iframe":
				case "img":
				case "source":
				case "track":
				case "video":
					if( isset($node->attribute['src']) ) {
						$results['childs'][ $node->attribute['itemprop'] ][] = $this->get_with_base($node->attribute['src']);
					} else {
						$this->new_error(MDX_TAG_NO_SRC, $node);
					}
				break;
				case "a":
				case "area":
				case "link":
					if(isset($node->attribute['href'])) {
						$results['childs'][ $node->attribute['itemprop'] ][] = $this->get_with_base($node->attribute['href']);
					} else {
						$this->new_error(MDX_TAG_NO_HREF, $node);
					}
				break;
				case "object":
					if(isset($node->attribute['data'])) {
						$results['childs'][ $node->attribute['itemprop'] ][] = $this->get_with_base($node->attribute['data']);
					} else {
						$this->new_error(MDX_TAG_NO_HREF, $node);
					}
				break;
				case "time":
					//TODO: Validate datetime
					$results['childs'][ $node->attribute['itemprop'] ][] = $node->attribute['datetime'];
				break;
				default:
					$results['childs'][ $node->attribute['itemprop'] ][] = $this->extract_text($node);
				break;					
			}
		}
	}
	

	/**
	 * 
	 * Basix text extraction for testing. Will be redone.
	 * @param unknown_type $node
	 */
	private function extract_text(&$node) {
		$text = "";
		if(isset($node->type) && $node->type == 4 ) { 
			$text .= $node->value;
		} else {
			if(isset($node->child) && is_array($node->child)) {
				foreach($node->child as $child) {
					$text .= $this->extract_text($child);
				}
			}		
		}
		return $text;
	}

	/**
	 * 
	 * test if a tidy node exists (same reference) in an array.
	 * 
	 * @param tidy_node $node
	 * @param array of tidy_nodes $node_array
	 */
	private function node_in_array(&$node, &$node_array) {
		foreach($node_array as &$n) {
			if($node === $n) return true;
		}
		return false;
	}	
	
	/**
	 * 
	 * Function to call the next function. Why? Factorish design pattern.
	 */
	
	private function do_clean_results() {
		$this->clean($this->results, $this->clean_results, true);
	}
	
	/**
	 * Function to recursively create the clean version of results
	 * 
	 * @param intermediate_results_struct $results
	 * @param clean_results_struct $clean_results
	 * @param boolean $top whether we are iterating on first set of nodes or childs of an item
	 */
	private function clean(&$results, &$clean_results, $top = false) {
		foreach($results as $res_key => &$res_val) {
			//Explode itemprops
			$keys = explode(" ", $res_key);
			foreach($keys as $key) {
				//TODO: Check that it has values
				//count the number of ocurrences of the itemtype.
				$count_res = count($res_val);	
				//Check if it's a list of results
				if( $count_res > 1 ) {
					//Check if it has childs
					foreach($res_val as $res) {
						//check if we are at the first leaf and that it's not a top_level. 
						//If it's not, it was probably referenced by an itemref and does not belong in results.
						//I TOOK THIS OUT CAUSE IT'S NOT SPECIFIED AS SUCH IN THE SPEC
//						if(! ($top && !$res['top_level']) ) {
							//check if It's an item
							if( is_array($res) && isset($res['childs']) ) {
								$r =& $clean_results[ $key ][];
								$this->clean($res['childs'], $r, false);
								//Add itemid if it exists
								if(isset($res['itemid'])) $r['itemid'] = $res['itemid']; 
								//Add itemtype if it exists
								if(isset($res['itemtype'])) $r['itemtype'] = $res['itemtype']; 
							} else {
								$clean_results[ $key ][] = $res;
							}
//						}
					}				
				} else {
					//It's a single result
					//check if we are at the first leaf and that it's not a top_level. 
					//If it's not, it was probably referenced by an itemref and does not belong in results.
					//I TOOK THIS OUT CAUSE IT'S NOT SPECIFIED AS SUCH IN THE SPEC
//					if(! ($top && !$res_val[0]['top_level']) ) {
						//check if It's an item
						if(is_array($res_val[0]) && isset($res_val[0]['childs']) ) {
							$r =& $clean_results[ $key ];
							$this->clean($res_val[0]['childs'], $r);
							//Add itemid if it exists
							if(isset($res_val[0]['itemid'])) $r['itemid'] = $res_val[0]['itemid']; 
							//Add itemtype if it exists
							if(isset($res_val[0]['itemtype'])) $r['itemtype'] = $res_val[0]['itemtype']; 
						} else {
							$clean_results[ $key ] = $res_val[0];
						}
					}
//				}
			}
		}
	}
	
	/**
	 * Function to add ="1" to all itemscope appereances within the html string, so that the attribute get's picked up by tidy.
	 * 
	 * <p>There's another version of this function (below, commented) that is more efficient but requires more memory. I'm still trying to decide which is best.</p>
	 * 
	 * 
	 * @param string $html
	 * @param string $encoding
	 */
	
	private function itemscope_for_tidy($html, $encoding = "") {
		$offset = 0;
		if($encoding == "") $encoding = mb_detect_encoding($html);
		$strlen = strlen($html);
		
		while( ($ltpos = mb_strpos($html, "<", $offset, $encoding)) !== false) {
			
			$offset = $ltpos;
			$gtpos = mb_strpos($html, ">", $offset, $encoding);
	
			$inside = mb_substr($html, $ltpos, ($gtpos+1)-$ltpos, $encoding);
	
			if(mb_strpos($inside, " itemscope ", 0 , $encoding) || mb_strpos($inside, " itemscope/", 0 , $encoding) || mb_strpos($inside, " itemscope>", 0 , $encoding ) || mb_strpos($inside, " itemscope" . "\n", 0 , $encoding )) {
				$pre = mb_substr($html, 0, $offset, $encoding);
				$pos = mb_substr($html, $gtpos+1, $strlen , $encoding);
				$inside = str_replace('itemscope', 'itemscope="1"', $inside);
				$html = $pre.$inside.$pos;
				$offset = $gtpos+5;
			} elseif(mb_strpos($inside, "meta ", 0 , $encoding) || mb_strpos($inside, "meta " . "\n", 0 , $encoding)) {
				$pre = mb_substr($html, 0, $offset, $encoding);
				$pos = mb_substr($html, $gtpos+1, $strlen , $encoding);
				$inside = str_replace('meta', 'mdxmeta', $inside);
				$html = $pre.$inside.$pos;
				$offset = $gtpos+4;
			} else {
				$offset = $gtpos+1;
			}
			if($gtpos === false) break;
		}
		return $html;
	}

/*
	private function itemscope_for_tidy($html) {
		$tr = "";
		$offset = 0;
		while( ($ltpos = mb_strpos($html, "<", $offset)) !== false) {
	
			$tr .= mb_substr($html, $offset, ($ltpos)-$offset);
	
			$offset = $ltpos;
			$gtpos = mb_strpos($html, ">", $offset);
	
			$inside = mb_substr($html, $ltpos, ($gtpos+1)-$ltpos);
	
			if($gtpos === false) {
				$tr .= mb_substr($html, $offset);
				break;	
					
			} elseif(mb_strpos($inside, " itemscope ") || mb_strpos($inside, " itemscope/") || mb_strpos($inside, " itemscope>")) {
	
				$inside = str_replace('itemscope', 'itemscope="1"', $inside);
				$tr .= $inside;
	
			} else {
	
				$tr .= mb_substr($html, $offset, ($gtpos+1)-$offset);
			}
			
			$offset = $gtpos+1;
			
		}
		if(mb_strlen($html) > $offset) {
			$tr .= mb_substr($html, $offset);
		}
		return $tr;
	
	}
*/
	
	/**
	 * results getter.
	 * 
	 */
	public function get_results() {
		return $this->results;
	}

	/*
	 * Clean results getter.
	 */
	public function get_clean_results() {
		return $this->clean_results;
	}
	
	
	/**
	 * Errors getter.
	 * 
	 */
	public function get_errors() {
		return $this->errors;
	}
	
	/**
	 * Function to get the line, column and id of an item.
	 * 
	 * @param a-tidy-node $node passed by reference
	 */
	private function get_line_column_id( &$node) {
		$full["line"] =  $node->line;
		$full['column'] = $node->column;
		if( isset($node->attribute['id']) ) {
			$full['id'] = $node->attribute['id'];
		} else {
			$full['id'] = false;
		}
		return $full;
		
	}
	
	/**
	 * Function to add a line to errors
	 * 
	 * @param string the string with the error description
	 * @param a-tidy-node $node passed by reference
	 */
	private function new_error($str, &$node) {
		$error = $this->get_line_column_id($node);
		$error['error'] = $str;
		$this->errors[] = $error;
	}

	/**
	 * URI setter
	 */
	private function set_URI($URI) {
		$this->URI = $URI;
	}

	/**
	 * Function to decide the base to be used on images and URLs.
	 * 
	 * First we take the base from The URI (if provided), then we look for the base element in the head of the html document.<br/>
	 * A base defined at the document level takes precedence over the URI base.<br/>
	 * For full documentation see: http://www.w3.org/TR/REC-html40/struct/links.html#h-12.4
	 * 
	 * @param a-tidy-node $node the tidy Head node of the html document 
	 */
	private function decide_base( $node ) {
		$base = "";
		//Construct base from URL
		if($this->URI != "") {
			$base = $this->construct_base( $this->URI );
		}

		//Now parse Head and search for Base, since document defined base takes precedence over URI.
		$html_base = "";
		if( $node->hasChildren() ) {
			//Base is a first child element, it will be in the first set of nodes.
			//So we don't need to do deep parsing of the tree.
			foreach($node->child as $child) {
				if($child->id == TIDY_TAG_BASE && isset($child->attribute['href']) ) {
					$html_base = $child->attribute['href'];
					$this->construct_base($html_base);
				}
			}
		}
		if($html_base != "") $base = $html_base;
		$this->base = $base;
	}
	
	private function construct_base($str) {
		$parsed_html_base = parse_url($str);
		//Check if it parse_url worked
		if( isset($parsed_html_base['scheme']) ) {
			//Construct Base
			$html_base = $parsed_html_base['scheme'] . '://' . $parsed_html_base['host'];
			if( isset( $parsed_html_base['port'] ) ) $html_base .= ':' . $parsed_html_base['port'];
			//Check for dirs
			if( isset($parsed_html_base['path']) ) {
				$parsed_html_path = pathinfo($parsed_html_base['path']);
				if($parsed_html_path['dirname'] != "/") {
					if($parsed_html_path['basename'] == $parsed_html_path['filename']) {
						$html_base .= $parsed_html_path['dirname'] . "/" . $parsed_html_path['basename'] . "/";
					} else {
						$html_base .= $parsed_html_path['dirname'] . "/";
					}
				} elseif($parsed_html_path['dirname'] == "/" && isset($parsed_html_path['basename'])) {
					if( (!isset($parsed_html_path['filename'])) || $parsed_html_path['filename']=="" ) {	
						if($parsed_html_path['basename'] != "") {
							$html_base .= "/" . $parsed_html_path['basename'] . "/";
						} else {
							$html_base .= "/";
						}
					} else {
						if($parsed_html_path['basename'] == $parsed_html_path['filename']) {
							$html_base .= "/" . $parsed_html_path['basename'] . "/";
						} else {
							$html_base .= "/";
						}
					}
				}
			} else {
				$html_base .= "/";
			}
			return $html_base;
		} else {
				//Invalid URI In Base
				$this->new_error(MDX_INVALID_BASE_URI);
		}
		return "";
	}
	
	/**
	 * 
	 * function to decide whether a src alreay has a base or if it has to be considered to the base of the document.  
	 * @param unknown_type $str
	 */
	private	function get_with_base($str) {
		$parsed_html_base = parse_url($str);
		//Check if it has a base
		if( isset($parsed_html_base['scheme']) ) {
			return $str;
		} else {
			return $this->base . $str;
		}

	}
	

	/**
	 * Get as JSON
	 * 
	 * @param boolean $show_errors whether or not to show errors on the results
	 * @param boolean $no_line_breaks whether or not to show the result with line breaks, since PHP will not parse a JSON string that has line breaks.
	 */
	public function get_microdata_as_JSON($show_errors = false, $no_line_breaks = true) {
		$json = '{ ';
		//Add Uri to the md definition
		if($this->URI) {
			$json .= ' "from":"' . $this->URI . '",';
		}
		$json .= ' "microdata":{ ' ;
//		print_r($this->clean_results);
		//Start with all root elements
		$passed_comma = false;
		foreach($this->clean_results as $md_name => $md) {
			$json .= ($passed_comma ? ', ' : '') . '"' . $md_name . '" : ' . $this->iterate_to_json($md) ;
			$passed_comma = true;
		}
		
		$json .= ' }';
		
		//Show errors if set
		if($show_errors && count($this->errors) > 0) {
			$json .= ', "errors":[ ';
			$passed_errors = false;
			foreach($this->errors as $error) {
				$json .= ($passed_errors ? ', ' : '') . '{ "error":"' . $error['error'] . '"';
				if(isset($error['line'])) 	$json .= ', "line":"' . $error['line'] . '"';
				if(isset($error['column'])) $json .= ', "column":"' . $error['column'] . '"';
				$json .= '}';
				$passed_errors = true;
			}
			$json .= ' ]';
		}
		$json .= '}';
		if($no_line_breaks) $json = str_replace("\n", "", $json);
		return $json;
	}	
	
	/**
	 * 
	 * Function used to generate a JSON string from the clean results 
	 * 
	 * @param unknown_type $md
	 */	
	public function iterate_to_json($md) {
		$tr = "";
		if(!is_array($md)) {
			//It's a text value
			return '"' . $md . '"';
		} else {
			if( is_int( key($md) ) ) {
				//It's an array of multiple values
				$passed_comma = false;
				$tr = "[ ";
				foreach($md as $md_value) {
					$tr .= ($passed_comma ? ", " : "") . $this->iterate_to_json($md_value);
					$passed_comma = true;
				}
				$tr .= " ]";
				return $tr;
			} else {
				$tr = "{ ";
				$passed_comma = false;
				foreach($md as $md_name => $md_value) {
					$tr .= ($passed_comma ? ", " : "") . '"' . $md_name . '" : ' .  $this->iterate_to_json($md_value);
					$passed_comma = true;
				}
				$tr .= " }";
				return $tr;
			}
		}
	}
	
}

?>