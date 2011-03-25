<?php
/**
 * Simple example of How to Use the Class to get a PHP associative array.
 * 
 * @package mdx
 * @subpackage examples
 * @author Emiliano Martínez Luque ( http://www.metonymie.com)
 */
?><?php 

require_once("../md_extract/class.MD_Extract.php");
require_once("../md_extract/lang.errors.php");

$mdx = MD_Extract::create_by_URL("http://www.metonymie.com/projects/2011/mdx/md-for-mdx.html");


echo("<!DOCTYPE HTML>
<html>
 <head>
  <title>Example</title>
 </head>
 <body>
 <h1>Results:</h1>
 <pre>");
var_dump($mdx->get_clean_results());
echo("</pre><h1>Errors</h1><pre>");
var_dump($mdx->get_errors());
echo("</pre></body></html>");


?>