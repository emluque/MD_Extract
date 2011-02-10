<?php

require_once("simpletest/unit_tester.php");
require_once("simpletest/reporter.php");

require_once("../md_extract/lang.errors.php");
require_once("../md_extract/class.MD_Extract.php");


/*
 * Class for testing the basic cases of data extraction. 
 * 
 * 
 */

class Test_MD_Basics extends UnitTestCase {

	function __Construct() {
	}

	/**
	 * Test for simple itemprop picking
	 */
	function test_picking_itemprop() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a"><span itemprop="a10">a<em>1</em>0</span><span itemprop="b">b</span>
</div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array("a10" => "a10", "b" => "b", "itemtype" => "a")); 
		$this->assertEqual($arr, $test_arr, "The Test for simpe extraction of an itemprop failed.");		
	}
	
	/**
	 * Test for multiple values of an itemtype
	 */
	function test_multiple_itemtype() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a"><span itemprop="a10">a<em>1</em>0</span><span itemprop="b">b</span>
</div>
<div itemscope itemtype="a"><span itemprop="a10">a<em>1</em>0</span></div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array(0 => array("a10" => "a10", "b" => "b", "itemtype" => "a"), 1 => array("a10" => "a10", "itemtype" => "a"))); 
		$this->assertEqual($arr, $test_arr, "The Test for extracting multiple values of an itemtype failed.");		
	}

	/**
	 * Test for multiple values of an itemprop
	 */
	function test_multiple_itemprop() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a">
<span itemprop="b">b1</span>
<span itemprop="b">b2</span>
</div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array("b" => array(0 => "b1", 1 => "b2"), "itemtype" => "a")); 
		$this->assertEqual($arr, $test_arr, "The Test for extracting multiple values of an itemprop failed.");		
	}

	/**
	 * Test for itemscope within item
	 */
	function test_itemscope_in_itemprop() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a">
<span itemprop="b">b1</span>
<div itemscope itemprop="c">
<span itemprop="d">d</span>
</div>
</div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array("b" =>  "b1", "c" => array( "d" => "d"), "itemtype" => "a")); 
		$this->assertEqual($arr, $test_arr, "The Test for extracting itemscope within itemprop.");		
	}

	/**
	 * Test for nested itemscopes
	 */
	function test_nested_itemscopes() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a">
<div itemscope itemprop="b">
<div itemscope itemprop="c">
<span itemprop="d">d</span>
</div>
</div>
</div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array("b" => array("c" => array("d" => "d")), "itemtype" => "a")); 
		$this->assertEqual($arr, $test_arr, "The Test for extracting nested itemscopes failed.");		
	}	

	
	/**
	 * Test for resolving itemref itemprop
	 */
	function test_resolving_itemref_itemprop() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a" itemref="c">
<span itemprop="b">b</span>
</div>
<div id="c" itemprop="d">d</div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array("b" => "b", "d" => "d", "itemtype" => "a")); 
		$this->assertEqual($arr, $test_arr, "The Test for resolving itemprop itemref failed.");		
	}	
	
	/**
	 * Test for resolving itemref multiple itemprops
	 */
	function test_resolving_itemref_multiple_itemprops() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a" itemref="c e">
<span itemprop="b">b</span>
</div>
<div id="c" itemprop="d">d</div>
<div id="e" itemprop="f">f</div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array("b" => "b", "d" => "d", "f" => "f", "itemtype" => "a")); 
		$this->assertEqual($arr, $test_arr, "The Test for resolving multple itemprops in itemref failed.");		
	}	

	/**
	 * Test for resolving itemref itemscope
	 */
	function test_resolving_itemref_itemscope() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a" itemref="c">
<span itemprop="b">b</span>
</div>
<div id="c"><div itemprop="d" itemscope><span itemprop="e">e</span></div></div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array("a" => array("b" => "b", "d" => array( "e" => "e"), "itemtype" => "a"), "untyped" => array( "e" => "e")); 
		
		$this->assertEqual($arr, $test_arr, "The Test for resolving itemscope in itemref failed.");		
	}	
	
	/**
	 * Test for picking up itemid
	 */
	function test_picking_up_itemid() {
		$html = <<< HTML
<html><body>
<div itemscope itemtype="a" itemid="a1">
<span itemprop="b">b</span>
<div id="c"><div itemprop="d" itemscope itemid="d1"><span itemprop="e">e</span></div></div>
</div>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_clean_results();
		$test_arr = array ( "a" => array ("b" => "b", "d" => array( "e" => "e", "itemid" => "d1"), "itemid" => "a1", "itemtype" => "a"));
	
		$this->assertEqual($arr, $test_arr, "The Test for picking up itemid failed.");		
	}	
	
	
	/**
	 * Test for itemscope without childs
	 */
	function test_itemscope_without_childs() {
		$html = <<< HTML
<html><body>
<img src="pepe.jpg" itemscope itemtype="http://www.error.com"/>
</body>
</html>		
HTML;
		$mdx = MD_Extract::create_by_HTML($html, "");
		$arr = $mdx->get_errors();
		$this->assertEqual($arr[0]['error'], MDX_ITEMSCOPE_WITHOUT_CHILDS, "ITEMSCOPE WITHOUT CHILDS test failed");		
	}
	
	
}
	
	
//Actual execution of tests
$test = new Test_MD_Basics();
$test->run( new HTMLReporter() );

?>