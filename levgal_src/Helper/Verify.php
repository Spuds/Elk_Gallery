<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with the verification widget, abstracting it away to keep the logic simple to follow.
 */
class LevGal_Helper_Verify
{
	private $id;

	public function __construct($id)
	{
		$this->id = $id;
	}

	private function initialize($do_test)
	{
		require_once(SOURCEDIR . '/Subs-Editor.php');
		$options = array(
			'id' => $this->id,
		);

		return create_control_verification($options, $do_test);
	}

	public function setupOnly()
	{
		return $this->initialize(false);
	}

	public function setupAndTest()
	{
		return $this->initialize(true);
	}

	public function output()
	{
		global $txt;

		echo '
				<strong>', $txt['verification'], ':</strong>', template_control_verification($this->id, 'quick_reply'), '<br />';
	}
}
