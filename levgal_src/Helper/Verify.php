<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with the verification widget, abstracting it away to keep the logic simple to follow.
 */
class LevGal_Helper_Verify
{
	/** @var int */
	private $id;

	public function __construct($id)
	{
		$this->id = $id;
	}

	private function initialize($do_test)
	{
		require_once(SUBSDIR . '/VerificationControls.class.php');
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

		template_verification_controls($this->id, '<strong>' . $txt['verification'] . ':</strong>', '<br />');
	}
}
