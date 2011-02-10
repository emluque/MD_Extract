<?php
/**
 * Simple example of How to Use the Class to get a JSON definition
 * 
 * @package mdx
 * @subpackage examples
 */
?><?php 

require_once("/home/includes/mylibs/md_extract/class.MD_Extract.php");
require_once("/home/includes/mylibs/md_extract/lang.errors.php");

$html = <<< HTML
<!DOCTYPE HTML>
<html>
 <head>
  <title>Photo gallery</title>
 </head>
 <body>

 <div itemscope itemtype="http://microformats.org/profile/hcalendar#vevent">
 <p>I'm going to
 <strong itemprop="summary">Bluesday Tuesday: Money Road</strong>,
 <time itemprop="dtstart" datetime="2009-05-05T19:00:00Z">May 5th at 7pm</time>
 to <time itemprop="dtend" datetime="2009-05-05T21:00:00Z">9pm</time>,
 at <span itemprop="location">The RoadHouse</span>!</p>
 <p><a href="http://livebrum.co.uk/2009/05/05/bluesday-tuesday-money-road"
       itemprop="url">See this event on livebrum.co.uk</a>.</p>
 <meta itemprop="description" content="via livebrum.co.uk">
</div>
 
 </body>
</html>
HTML;



$mdx = MD_Extract::create_by_html($html, "");

echo("<!DOCTYPE HTML>
<html>
 <head>
  <title>Example</title>
 </head>
 <body>
  <h1>Original HTML:</h1>
<code><pre>
" . htmlentities($html) . "
</pre></code>
 <h1>JSON:</h1>
 <pre>");
$json = $mdx->get_microdata_as_JSON();
var_dump($json);
echo('</pre>');
$json_arr = (Array) json_decode($json);;
echo('<h2>Processed JSON: </h2>');
echo('<pre>');
print_r($json_arr);
echo("</pre></body></html>");

?>