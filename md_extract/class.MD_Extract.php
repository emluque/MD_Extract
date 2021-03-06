<?php

/**
 * 
 * @author Emiliano Martínez Luque ( http://www.metonymie.com)
 *
 */


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
	 * Tidy configuration Options
	 */
	public $tidy_conf = array('wrap' => 0);
	
	
	
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
		//Detect encoding
		$encoding = mb_detect_encoding($html);
		//Prepare itemscope for tidy
		$html = $mdx->itemscope_for_tidy($html, $encoding);
		//Create tidy node
		$tidy = tidy_parse_string($html, array("new-blocklevel-tags" => "section, header, footer, nav, article, aside, figure, dialog, video, audio, details, datagrid, menu, command, output, canvas, datalist, embed",
		 "new-inline-tags" => "mark, time, meter, progress, figcaption, ruby, rt, rp, bdi, keygen, mdxmeta, source",
		 "new-empty-tags" => "video, audio, wbr, mdxmeta", "char-encoding" => $mdx->encoding_for_tidy($encoding)));
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
	 * Factor(ish) construction of the object by URL
	 * 
	 * @param URL $URL
	 */
	static function create_by_URL($URL) {
		$mdx = new MD_Extract();
		//Set $URI
		$mdx->set_URI($URL);
		
		//Fetch URI with CURL
		$options = array(
			CURLOPT_RETURNTRANSFER => true,	 // return web page
			CURLOPT_HEADER		   => true,	// return headers
			CURLOPT_FOLLOWLOCATION => true,	 // follow redirects
			CURLOPT_ENCODING	   => "",	   // handle all encodings
			CURLOPT_USERAGENT	   => "MD_extract", // The User Agent
			CURLOPT_AUTOREFERER	   => true,	 // set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120,	  // timeout on connect
			CURLOPT_TIMEOUT		   => 120,	  // timeout on response
			CURLOPT_MAXREDIRS	   => 10,	   // stop after 10 redirects
		);

		$ch = curl_init( $URL );
		curl_setopt_array( $ch, $options );
		$html = curl_exec( $ch );
		$err = curl_errno( $ch );
		$errmsg  = curl_error( $ch );
		$header  = curl_getinfo( $ch );
		//Encoding options for tidy		
		if(isset($header['content_type'])) {
			$exp_header = explode('charset=', $header['content_type']);
			if(isset($exp_header[1])) {
				$encoding = str_replace('-', '', $exp_header[1]);
				$mdx->tidy_conf['char-encoding'] = $encoding;
				$mdx->tidy_conf['input-encoding'] = $encoding;
			   	$mdx->tidy_conf['output-encoding'] = $encoding;
			}
		}
		curl_close( $ch );
		//If there where errors just add to $mf
		if($err) {
			$error['error'] = 'CURL Error: ' .  $err . '\n' . $errmsg;
			$mdx->errors[] = $error;
			return $mdx;
		} else {
			//No CURL Errors.
			//Detect encoding
			$encoding = mb_detect_encoding($html);
			//Prepare itemscope for tidy
			$html = $mdx->itemscope_for_tidy($html, $encoding);	
			//Create tidy node
			$tidy = tidy_parse_string($html, array("new-blocklevel-tags" => "section, header, footer, nav, article, aside, figure, dialog, video, audio, details, datagrid, menu, command, output, canvas, datalist, embed",
			 "new-inline-tags" => "mark, time, meter, progress, figcaption, ruby, rt, rp, bdi, keygen, mdxmeta, source",
			 "new-empty-tags" => "video, audio, wbr, mdxmeta", "char-encoding" => $mdx->encoding_for_tidy($encoding)));
			//Set $URI
			$mdx->set_URI($URL);
			//Decide Base
			$mdx->decide_base( $tidy->head() );
			//extract
			$mdx->extract($tidy->body());
			//do clean results
			$mdx->do_clean_results();
			return $mdx;
		}
	}
	
	
	
	public function encoding_for_tidy($encoding) {
		switch ($encoding) {
			case "UTF-8":
				return "utf8";
		}
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
					$has_picked_value = array();
					$results['childs'][ $node->attribute['itemprop'] ][] = $this->extract_text($node, 0, $has_picked_value);
				break;					
			}
		}
	}
	

	/**
	 * 
	 * Basic text extraction for testing. 
	 * @param unknown_type $node
	 */
	private function extract_text(&$node, $depth, &$has_picked_value ) {
		$text = "";
		if( $node->name == "br") {
			//Line breaks
			$text .= '
';
			return $text;
		} elseif($node->type == TIDY_NODETYPE_COMMENT) {
			//Comment
			return;
		} elseif($node->name == "script") {
			//script
			return;
		} elseif(isset($node->type) && $node->type == 4 ) { 
			//Text value
			$text .= $node->value;
		} else {
			//Only add extra line breaks if we have already picked up a text node
			if( isset($has_picked_value[$depth-1])  ) {
				//\n after p
				if($node->id == TIDY_TAG_DIV || $node->id == TIDY_TAG_DL || $node->id == TIDY_TAG_LI 
				|| $node->id == TIDY_TAG_DD || $node->id == TIDY_TAG_P || $node->id == TIDY_TAG_H1 
				|| $node->id == TIDY_TAG_H2 || $node->id == TIDY_TAG_H3 || $node->id == TIDY_TAG_H4 
				|| $node->id == TIDY_TAG_H5 || $node->id == TIDY_TAG_H6 || $node->id == TIDY_TAG_TABLE 
				|| $node->id == TIDY_TAG_TBODY || $node->id == TIDY_TAG_THEAD || $node->id == TIDY_TAG_TFOOT 
				|| $node->id == TIDY_TAG_TR || $node->id == TIDY_TAG_CAPTION || $node->id == TIDY_TAG_DT
				|| $node->name == "section" || $node->name == "article" || $node->name == "aside" 
				|| $node->name == "header"  || $node->name == "footer" || $node->name == "nav"
				|| $node->name == "dialog"  || $node->name == "figure" || $node->name == "address"
				) {
					$text .= "\n";
				}
			}
			//Opening <q>
			if($node->id == TIDY_TAG_Q) $text .= '"';
			//Childs
			if(isset($node->child) && is_array($node->child)) {
				foreach($node->child as $child) {
					$text .= $this->extract_text($child, $depth+1, $has_picked_value);
					if($text != "") $has_picked_value[ $depth ] = true;
				}
			}
			//Closing <q>
			if($node->id == TIDY_TAG_Q) $text .= '"';
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
		
		$delimiters[] = " ";
		$delimiters[] = "\n";
		$delimiters[] = "\t";
		$delimiters[] = "\r";
		
		$offset = 0;
		if($encoding == "") $encoding = mb_detect_encoding($html);
		
		$strlen = strlen($html);
		
		while( ($ltpos = mb_strpos($html, "<", $offset, $encoding)) !== false) {

			$offset = $ltpos;
			
			$start = true;
			$inside_d_quotes = false;
			$inside_s_quotes = false;
			$after_equal = false;
			$gt_found = false;
			$expression_start = $offset;
			$expression_ended = false;
			$in_expression = false;
			
			while( $char = mb_substr($html, $offset, 1, $encoding) ) {
/*				echo("CHAR: " . $char . " - start: " . $start . 
				" - inside_d_quotes: " . $inside_d_quotes . 
				" - inside_s_quotes: " . $inside_s_quotes . 
				" - after_equal: " . $after_equal .
				" - gt_found: " . $gt_found .
				" - expression_start: " . $expression_start .
				" - expression_ended: " . $expression_ended .
				" - in_expression: " . $in_expression . "  <br/>\n"  );
*/				
				
				if($start && $char == "<") { 
					$ltpos++;
					$offset++;
					$expression_start++;
					continue;
				} elseif( in_array($char, $delimiters)) {
					if( (!$after_equal && $in_expression) || ($after_equal && (!$inside_s_quotes) && (!$inside_d_quotes))) {
							//End of expression
							$expression_ended = true;
					}
					if(!$in_expression) { 
						$expression_start++;
					}
					
				} else {
					if($char=='=' && (!$inside_s_quotes) && (!$inside_d_quotes) ) {
						$after_equal = true;					
					} elseif( $after_equal && $char == '"' && !$inside_s_quotes) {
						if(!$inside_d_quotes) {
							$inside_d_quotes = true;
						} else {
							$inside_d_quotes = false;
							$after_equal = false;
						}
					} elseif( $after_equal && $char == "'" && !$inside_d_quotes) {
						if(!$inside_s_quotes) {
							$inside_s_quotes = true;
						} else {
							$inside_s_quotes = false;
							$after_equal = false;
						}
					} elseif( (!$inside_d_quotes) && (!$inside_s_quotes) && $char == ">") {
						$gt_found = true;
						$expression_ended = true;
					}
					$expresion_started = true;
					$in_expression = true;
				}	

				$start = false;
				if($expression_ended) {
					$expression = mb_substr($html, $expression_start, $offset-$expression_start, $encoding);
//					echo("<h1>" . $expression . "</h1>");
					$expression_length = mb_strlen($expression, $encoding);
					if( $expression_length >= 9  ) {
						//This is done with lot's of carelessness, since tidy will pick it up anyhow.
						//And the value of the attribute itemscope is irrelevant
						if(mb_substr($expression, 0, 9) == "itemscope") {
							$pre = mb_substr($html, 0, $expression_start, $encoding);
							$pos = mb_substr($html, $expression_start+$expression_length, $strlen , $encoding);
							$inside = 'itemscope="1"';
							if($expression_length > 9) {
//								$inside .= mb_substr($html, $expression_start+9, $expression_length-9, $encoding);
							}
							
							$html = $pre .$inside . $pos;
//							$offset = $expression_start+$expression_length+4;
							$offset = $expression_start+13;
						}						
					} else {
						if($expression == "meta") {
							$pre = mb_substr($html, 0, $expression_start, $encoding);
							$pos = mb_substr($html, $expression_start+$expression_length, $strlen , $encoding);
							$inside = 'mdxmeta';
							$html = $pre .$inside . $pos;
							$offset = $expression_start+$expression_length+3;
						}
					}
					$expression_start = $offset+1;
					$expression_ended = false;
					$after_equal = false;
					$in_expression = false;
				}
				$offset++;
				if($gt_found) break;
			}
			
			
		}
		return $html;
	}
		
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