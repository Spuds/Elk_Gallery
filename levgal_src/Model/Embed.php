<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.1.0 / elkarte
 */

/**
 * This file deals with the mechanics involved in embedding things inside posts.
 */
class LevGal_Model_Embed
{
	/** @var array[] */
	private $embed;
	/** @var array[] */
	private $item_list;
	/** @var string */
	private $type;
	/** @var string */
	private $align;
	/** @var int */
	private $id;
	/** @var int */
	private static $count = 1;

	public function __construct()
	{
		$this->embed = array(
			'simple' => array(),
			'complex' => array(),
		);
	}

	public function addSimple()
	{
		$this->item_list[$this->id] = true;
		$this->embed['simple'][self::$count++] = array(
			'id_msg' => $this->getMsg(),
			'id' => $this->id,
		);
	}

	public function addComplex($description)
	{
		$this->item_list[$this->id] = true;
		$this->embed['complex'][self::$count++] = array(
			'id' => $this->id,
			'align' => $this->align,
			'type' => $this->type,
			'description' => trim($description),
			'id_msg' => $this->getMsg(),
		);
	}

	public function getMsg()
	{
		global $context;

		return (int) empty($context['id_msg']) ? 0 : $context['id_msg'];
	}

	public function getCount()
	{
		return self::$count;
	}

	public function setId($id_item)
	{
		$this->id = (int) $id_item;

		return $this->getCount();
	}

	public function setAlign($align)
	{
		$this->align = in_array($align, array('left', 'center', 'right')) ? $align : 'center';

		return $this;
	}

	public function setType($type)
	{
		$this->type = in_array($type, array('thumb', 'preview')) ? $type : 'thumb';

		return $this;
	}

	public function processBuffer(&$buffer)
	{
		global $modSettings;

		// Account for other gallery tags
		if (!empty($modSettings['lgal_import_rendering']))
		{
			$this->processSMG($buffer);
		}

		// First, load the items.
		$items = $this->getItems();

		foreach (['simple', 'complex'] as $type)
		{
			foreach ($this->embed[$type] as $counter => $item)
			{
				// Look for !<lgalmediasimple: and !<lgalmediacomplex: tags
				$search = '!<lgalmedia' . $type . ': ' . $counter . '>';
				if (isset($items[$item['id']]))
				{
					// Union the item array (id, id_msg) with the LevGal_Model_ItemList results
					$item += $items[$item['id']];
					$method = $type . 'Template';
					$buffer = str_replace($search, $this->$method($counter, $item), $buffer);
				}
				else
				{
					$buffer = str_replace($search, $this->invalidTemplate(), $buffer);
				}
			}
		}
	}

	/**
	 * Processes the SMG tags in the given buffer.
	 *
	 * This method searches for SMG tags in the buffer and replaces them with the corresponding
	 * replacement values.
	 *
	 * @param string &$buffer The buffer to process, passed by reference.
	 * @return void
	 */
	public function processSMG(&$buffer)
	{
		global $context;

		// Just the <body> section
		$toCheck = preg_match('~(.*?)(<body\b[^>]*>.*?<\/body>)(.*)~s', $buffer, $matches);
		if ($toCheck !== false)
		{
			// Any [smg] bbc codes?
			preg_match_all('~\[smg\s+([^]]*?(?:&quot;.+?&quot;.*?(?!&quot;))?)]( ?<br />)?[\r\n]?~i', $matches[2], $aeva_cruft);
			if (!empty($aeva_cruft))
			{
				foreach ($aeva_cruft[1] as $id => $aeva_tag)
				{
					$parsed = $this->aevaParse($aeva_tag);

					if ($parsed !== false)
					{
						$count = $context['lgal_embeds']->setId($parsed['id']);
						if (empty($parsed['align']) && empty($parse['type']))
						{
							// Set up a simple tag for id=""
							$context['lgal_embeds']->addSimple();
							$replace = '!<lgalmediasimple: ' . $count . '>';
						}
						else
						{
							// Set up a more complex tag with some align/type options
							$context['lgal_embeds']->setAlign($parsed['align'])->setType($parsed['type'])->addComplex('');
							$replace = '!<lgalmediacomplex: ' . $count . '>';
						}

						// Just like the parser would do, but less safe
						$matches[2] = str_replace($aeva_cruft[0][$id], $replace, $matches[2]);
					}
				}
			}

			$buffer = $matches[1] . $matches[2] . $matches[3];
		}
	}

	/**
	 * Parses the given [SMG} tag data and extracts the parameters we are interested in processing
	 *
	 * @param string $data The data to be parsed.
	 * @return array|false An associative array containing the extracted parameters, or false if the 'id' parameter is empty.
	 */
	public function aevaParse($data)
	{
		$params = array(
			'id' => array('match' => '(\d+(?:,\d+)*)'),
			'type' => array('match' => '(normal|box|av|link|preview|full|album)'),
			'align' => array('match' => '(none|right|left|center)'),
		);

		// This is a hack, but the smg tag is non-compliant to any standard tagging ElkArte supports
		$done = array('id' => '', 'type' => '', 'align' => '');
		foreach ($params as $id => $cond)
		{
			if (preg_match('~' . $id . '=(?:&quot;)?' . $cond['match'] . '(?:&quot;)?~i', $data, $match))
			{
				$done[$id] = $match[1];
			}
		}

		return empty($done['id']) ? false : $done;
	}

	/**
	 * Processes the PBE (Post by Email) tags in the given buffer and replaces them with corresponding HTML links.
	 *
	 * @param string $buffer The text buffer to be processed.
	 * @return void
	 */
	public function processPBE(&$buffer)
	{
		global $txt;

		// First, load the items.
		$items = $this->getItems();

		foreach (['simple', 'complex'] as $type)
		{
			foreach ($this->embed[$type] as $counter => $item)
			{
				$search = '!<lgalmedia' . $type . ': ' . $counter . '>';
				if (isset($items[$item['id']]))
				{
					$item = $item + $items[$item['id']];
					$buffer = str_replace($search, '<a href="' . $item['item_url'] . '" class="levgal">' . $item['item_name'] . '</a>', $buffer);
				}
				else
				{
					$buffer = str_replace($search, $txt['levgal_email_photo_missing'], $buffer);
				}
			}
		}
	}

	/**
	 * Generates a simple template for an item.
	 *
	 * This method generates a simple HTML template for an item based on its type.
	 * If the item type is 'image', it generates a figure element with a link to the full image,
	 * an image element, and a link to the item URL. Otherwise, it generates a link to the item URL
	 * with an image element.
	 *
	 * @param int $counter The counter value used for generating unique IDs.
	 * @param array $item The item details.
	 * @return string The generated HTML template.
	 */
	private function simpleTemplate($counter, $item)
	{
		global $txt;

		if ($item['item_type'] === 'image')
		{
			// For lightbox functionality, we need unique id's and message groupings for group navigation
			return '
			<figure class="item_image">
				<a href="' . $item['item_base'] . '" id="link_' . $counter . 'm" data-lightboximage="' . $counter . 'm" data-lightboxmessage="' . $item['id_msg'] . '">
					<img class="bbc_image has_lightbox" src="' . $item['thumbnail'] . '" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" loading="lazy" />
				</a>
				<figcaption class="item_link">
					<a href="' . $item['item_url'] . '">
						<i class="icon icon-big i-help" title="' . $txt['lgal_item_info'] . '"></i>
					</a>
				</figcaption>
			</figure>';
		}

		return '
		<a href="' . $item['item_url'] . '" class="bbc_link">
			<img src="' . $item['thumbnail'] . '" class="bbc_img" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" loading="lazy" />
		</a>';
	}

	/**
	 * Generates the HTML output for a complex media item.
	 *
	 * This method takes a counter and an item array, and generates the HTML output for a complex media item.
	 * It determines the caption, alignment, and image source based on the provided item array.
	 *
	 * @param int $counter The counter value for the media item.
	 * @param array $item The array representing the media item.
	 * @return string The HTML output for the complex media item.
	 */
	private function complexTemplate($counter, $item)
	{
		global $txt;

		$caption = !empty($item['description']) ? $item['description'] : $item['item_name'];
		$align = $item['align'] === 'center' ? '<figure class="centertext">' : '<figure style="float:' . $item['align'] . '">';
		$using = $item['type'] === 'preview' ? $item['preview'] : $item['thumbnail'];

		// Process [media align=xxx]123[/media] as a "simple" aligned
		if ($caption !== '_lgal_simple_')
		{
			$caption = '
			<figcaption class="centertext">
				<a class="bbc_link" href="' . $item['item_url'] . '" >' . $caption . '</a>
			</figcaption>';
		}
		else
		{
			$caption = '
			<figcaption class="item_link">
				<a href="' . $item['item_url'] . '">
					<i class="icon icon-big i-help" title="' . $txt['lgal_item_info'] . '"></i>
				</a>
			</figcaption>';
		}

		if ($item['item_type'] === 'image')
		{
			// Lightbox if a thumb, otherwise a link
			return
				$align . ($item['type'] === 'preview'
				? '<a class="bbc_link" href="' . $item['item_url'] . '">'
				: '<a href="' . $item['item_base'] . '" id="link_' . $counter . 'm" data-lightboximage="' . $counter . 'm" data-lightboxmessage="' . $item['id_msg'] . '">') . '
					<img class="bbc_img' . ($item['type'] === 'preview' ? '' : ' has_lightbox') . '" src="' . $using . '" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" loading="lazy" />
				</a>' . $caption . '</figure>';
		}

		return
			$align . '
				<a href="' . $item['item_url'] . '" class="bbc_link">
					<img class="bbc_img" src="' . $using . '" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" loading="lazy" />
				</a>
				<figcaption>
					<a class="bbc_link" href="' . $item['item_url'] . '" >' . $caption . '</a>
				</figcaption>	
			</figure>';
	}

	/**
	 * Returns the HTML code for displaying an invalid template image.
	 *
	 * This method generates the HTML code for displaying an image indicating that the template
	 * is invalid. The image is a warning icon with the alt and title attributes set to the
	 * corresponding text defined in the language file.
	 *
	 * @return string The HTML code for the invalid template image.
	 */
	private function invalidTemplate()
	{
		global $settings, $txt;

		return '
		<img class="bbc_img" src="' . $settings['default_theme_url'] . '/levgal_res/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
	}

	/**
	 * Retrieves the items needed for rendering.
	 *
	 * This method loads the items specified in the item list and returns them.
	 * If the item list is empty, it returns an empty array.
	 *
	 * @return array The items to render.
	 */
	public function getItems()
	{
		// Load the items we need to render
		if (empty($this->item_list))
		{
			return [];
		}

		return (new LevGal_Model_ItemList())->getItemsById(array_keys($this->item_list));
	}
}
