<?php
/**
 * 
 * @author Emiliano Martínez Luque ( http://www.metonymie.com)
 *
 */


require_once("../unit_testing/simpletest/unit_tester.php");
require_once("../unit_testing/simpletest/reporter.php");
require_once("itemscope_for_tidy.php");



/*
 * Class for testing the basic cases of data extraction. 
 * 
 * 
 */

class Test_itemscope extends UnitTestCase {

	function __Construct() {
	}

	function test_basic() {
		$str = '<div itemscope>blabla</div>';
		$expected = '<div itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}
	
	function test_not_picking_inside_quotes() {
		$str = '<div class=" itemscope ">blabla</div>';
		$expected = '<div class=" itemscope ">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}
	
	function test_picking_after_quotes() {
		$str = '<div class="pepe" itemscope>blabla</div>';
		$expected = '<div class="pepe" itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}

	function test_transforming_incomplete() {
		$str = '<div itemscope=>blabla</div>';
		//Yes, this is horrible. But tidy fixes it. 
		$expected = '<div itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);
		
		$this->assertEqual($res, $expected, "Test failed.");		
	}

	/**
	 * 
	 * This is how tidy and firefox do it. So I'm replicating their behaviour.
	 */
	function test_not_transforming_incomplete() {
		$str = '<div itemscope=">blabla</div>';
		$expected = '<div itemscope=">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}

	/**
	 * 
	 * This is wierd but it might seem better.
	 */
	function test_not_transforming_incomplete2() {
		$str = '<div itemscope=">blabla</div><div class="pepe">hola</div>';
		$expected = '<div itemscope="1">hola</div>';

		$res = itemscope_for_tidy($str);
		$this->assertEqual($res, $expected, "Test failed.");		
	}	

	
	function test_picking_after_repeated_open_lt() {
		$str = '<<div itemscope>blabla</div>';
		$expected = '<<div itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}

	function test_picking_after_repeated_open_lt2() {
		$str = '<<<div itemscope>blabla</div>';
		$expected = '<<<div itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}

	function test_picking_with_gt_inside_quotes() {
		$str = '<div title="pepe>" itemscope>blabla</div>';
		$expected = '<div title="pepe>" itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}
	
	function test_picking_after_extra_quotes() {
		$str = '<div class="a"" itemscope>blabla</div>';
		$expected = '<div class="a"" itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}

	function test_picking_after_spaces_in_previous() {
		$str = '<div class = "a" itemscope>blabla</div>';
		$expected = '<div class = "a" itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}

	function test_extra_gts() {
		$str = '<div itemscope>>>blabla</div><div itemscope>blabla</div>';
		$expected = '<div itemscope="1">>>blabla</div><div itemscope="1">blabla</div>';

		$res = itemscope_for_tidy($str);

		$this->assertEqual($res, $expected, "Test failed.");		
	}
	
}
	
	
//Actual execution of tests
$test = new Test_itemscope();
$test->run( new HTMLReporter() );

?>