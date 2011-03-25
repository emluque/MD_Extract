<?php
/**
 * Simple example of How to Use the Class to get a JSON definition
 * 
 * @package mdx
 * @subpackage examples
 * @author Emiliano Martínez Luque ( http://www.metonymie.com)
 *
 */

 */
?><?php 

require_once("../md_extract/class.MD_Extract.php");
require_once("../md_extract/lang.errors.php");

$html = <<< HTML
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



$mdx = MD_Extract::create_by_html($html, "http://www.example.com");

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