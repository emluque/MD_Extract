<?php


	function itemscope_for_tidy($html, $encoding = "") {
		
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
								$inside .= mb_substr($html, $expression_start+9, $expression_length-9, $encoding);
							}
							
							$html = $pre .$inside . $pos;
							$offset = $expression_start+$expression_length+4;
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

/*
$str = '<div class=" itemscope "    itemscope>blabla</div>';
$str = "<div class=aaaa>jhkhah</div>";
$str = "<div class=' >jhkhah</div>";
$str = "<div itemscope='klasj'     itemscope
itemscope  class='sdasdf'>";

//THIS DOES NOT PICK THE EXPRESSIONS CORRECTLY. HOWEVER IT DOESN'T REALLY MATTERS FOR WHAT WE ARE DOING.
$str = "<div class = 'aaaa' '  >jhkhah</div>";
$str = "<div class = 'aaaa' ' aaaa  >jhkhah</div>";


	
$str = <<< HTML
<!DOCTYPE HTML>
<html>
 <head>
  <title>Photo gallery</title>
 </head>
 <body>

 
<section id="jack" itemscope itemtype="http://microformats.org/profile/hcard">
 <h1 itemprop="fn">
  <span itemprop="n" itemscope>
   <span itemprop="given-name">Jack</span>
   <span itemprop="family-name">Bauer</span>
  </span>
 </h1>
 <img itemprop="photo" alt="" src="jack-bauer.jpg">
 <p itemprop="org" itemscope>
  <span itemprop="organization-name">Counter-Terrorist Unit</span>
  (<span itemprop="organization-unit">Los Angeles Division</span>)
 </p>
 <p>
  <span itemprop="adr" itemscope>
   <span itemprop="street-address">10201 W. Pico Blvd.</span><br>
   <span itemprop="locality">Los Angeles</span>,
   <span itemprop="region">CA</span>
   <span itemprop="postal-code">90064</span><br>
   <span itemprop="country-name">United States</span><br>
  </span>
  <span itemprop="geo">34.052339;-118.410623</span>
 </p>
 <h2>Assorted Contact Methods</h2>
 <ul>
  <li itemprop="tel" itemscope>
   <span itemprop="value">+1 (310) 597 3781</span> <span itemprop="type">work</span>
   <meta itemprop="type" content="pref">
  </li>
  <li><a itemprop="url" href="http://en.wikipedia.org/wiki/Jack_Bauer">I'm on Wikipedia</a>
  so you can leave a message on my user talk page.</li>
  <li><a itemprop="url" href="http://www.jackbauerfacts.com/">Jack Bauer Facts</a></li>
  <li itemprop="email"><a href="mailto:j.bauer@la.ctu.gov.invalid">j.bauer@la.ctu.gov.invalid</a></li>
  <li itemprop="tel" itemscope>
   <span itemprop="value">+1 (310) 555 3781</span> <span>
   <meta itemprop="type" content="cell">mobile phone</span>
  </li>
 </ul>
 <p itemprop="note">If I'm out in the field, you may be better off contacting <span
 itemprop="agent" itemscope itemtype="http://microformats.org/profile/hcard"><a
 itemprop="email" href="mailto:c.obrian@la.ctu.gov.invalid"><span
 itemprop="fn"><span itemprop="n" itemscope><span
 itemprop="given-name">Chloe</span> <span
 itemprop="family-name">O'Brian</span></span></span></a></span>
 if it's about work, or ask <span itemprop="agent">Tony Almeida</span>
 if you're interested in the CTU five-a-side football team we're trying to get going.</p>
 <ins datetime="2008-07-20T21:00:00+01:00">
  <span itemprop="rev" itemscope>
   <meta itemprop="type" content="date-time">
   <meta itemprop="value" content="2008-07-20T21:00:00+01:00">
  </span>
  <p itemprop="tel" itemscope><strong>Update!</strong>
  My new <span itemprop="type">home</span> phone number is
  <span itemprop="value">01632 960 123</span>.</p>
 </ins>
</section>
 
 
  </body>
</html>
HTML;


$res = itemscope_for_tidy($str);
echo($res);

*/



?>