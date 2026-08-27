<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 2.0.0 / elkarte
 */

namespace Addons\Levertine\Source\Helper;

use ElkArte\VerificationControls\VerificationControlsIntegrate;

/**
 * This file deals with the verification widget, abstracting it away to keep the logic simple to follow.
 */
class Verify
{
	/** @var int */
	private $id;

	public function __construct($id)
	{
		$this->id = $id;
	}

	private function initialize($do_test)
	{
		global $context;

		$verificationOptions = [
			'id' => $this->id,
		];

		$context['require_verification'] = VerificationControlsIntegrate::create($verificationOptions, $do_test);
		$context['visual_verification_id'] = $verificationOptions['id'];
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
