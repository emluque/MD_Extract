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

 <p itemscope
           itemtype="http://data-vocabulary.org/Address">
    <span itemprop="street-address">100 North Salem Street</span><br>
    <span itemprop="locality">Apex</span>,
    <span itemprop="region">NC</span>
    <span itemprop="postal-code">27502</span><br>
    <span itemprop="country-name">USA</span>
  </p>
  <span itemscope
        itemtype="http://data-vocabulary.org/Geo">
    <meta itemprop="latitude" content="35.730796" />
    <meta itemprop="longitude" content="-78.851426" />
  </span>
  
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
  <h1>Results:</h1>
 <pre>");
var_dump($mdx->get_clean_results());
echo("</pre><h1>Errors</h1><pre>");
var_dump($mdx->get_errors());
echo("</pre></body></html>");


?>