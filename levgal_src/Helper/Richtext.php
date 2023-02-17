<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

use BBC\ParserWrapper;

/**
 * This file deals with the richtext editor widget since we will need this in several
 * places and it's better to reuse.
 */
class LevGal_Helper_Richtext
{
	/** @var string */
	private $form_var;
	/** @var string  */
	private $sanitized = '';

	public function __construct($form_var)
	{
		$this->form_var = $form_var;
	}

	public function getId()
	{
		return $this->form_var;
	}

	public function createEditor($editorOptions)
	{
		global $context;

		require_once(SUBSDIR . '/Editor.subs.php');

		$defaults = array(
			'id' => $this->form_var,
			'value' => '',
			'height' => '175px',
			'width' => '100%',
			'preview_type' => false,
		);
		$editorOptions = array_merge($defaults, $editorOptions);

		create_control_richedit($editorOptions);

		// We sometimes need to apply our own event handling, for example.
		if (isset($editorOptions['js']))
		{
			$context['controls']['richedit'][$editorOptions['id']]['js'] = $editorOptions['js'];
		}
	}

	public function displayEditWindow()
	{
		echo '
			<div class="editor_wrapper">'; // closed in displayButtons

		echo '
				', template_control_richedit($this->form_var, 'smileyBox_' . $this->form_var, 'bbcBox_' . $this->form_var);
	}

	public function displayButtons()
	{
		global $context, $txt;

		$editor_context = &$context['controls']['richedit'][$this->form_var];

		echo '
				<div id="post_confirm_buttons" class="right_submit">
					<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
					<input type="submit"', isset($editor_context['labels']['name']) ? ' name="' . $editor_context['labels']['name'] . '"' : '', ' value="', $editor_context['labels']['post_button'] ?? $txt['post'], '" tabindex="', $context['tabindex']++, '"', isset($editor_context['js']['post_button']) ? ' onclick="' . $editor_context['js']['post_button'] . '"' : '', ' />';

		echo '
				</div>
			</div>';
	}

	private function prepareWYSIWYG()
	{
		if (!empty($_REQUEST[$this->form_var . '_mode']) && isset($_REQUEST[$this->form_var]))
		{
			require_once(SUBSDIR . '/Editor.subs.php');

			$bbc_converter = new Html_2_BBC($_REQUEST[$this->form_var]);
			$_REQUEST[$this->form_var] = $bbc_converter->get_bbc();
			$_REQUEST[$this->form_var] = un_htmlspecialchars($_REQUEST[$this->form_var]);
			$_POST[$this->form_var] = $_REQUEST[$this->form_var];
		}
	}

	public function isEmpty()
	{
		// Before we check there is content, we may have to do some massaging.
		$this->prepareWYSIWYG();

		return (empty($_POST[$this->form_var]) || Util::htmltrim(Util::htmlspecialchars($_POST[$this->form_var])) === '');
	}

	public function sanitizeContent()
	{
		require_once(SUBSDIR . '/Post.subs.php');

		$this->sanitized = Util::htmlspecialchars($_POST[$this->form_var], ENT_QUOTES);
		preparsecode($this->sanitized);

		$parser = ParserWrapper::instance();

		return !(Util::htmltrim(strip_tags($parser->parseMessage($this->sanitized, false), '<img>')) === '' && (!allowedTo('admin_forum') || strpos($this->sanitized, '[html]') === false));
	}

	public function getForDB()
	{
		return $this->sanitized;
	}

	public function getForForm($comment = null)
	{
		require_once(SUBSDIR . '/Post.subs.php');
		$comment = $comment ?? $this->sanitized;
		$comment = un_preparsecode($comment);
		censor($comment);

		return str_replace(array('"', '<', '>', '&nbsp;'), array('&quot;', '&lt;', '&gt;', ' '), $comment);
	}
}
