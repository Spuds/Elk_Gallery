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
					$item = $item + $items[$item['id']];
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

	private function simpleTemplate($counter, $item)
	{
		global $txt;

		if ($item['item_type'] === 'image')
		{
			// For lightbox functionality, we need unique id's and message groupings for group navigation
			return '
			<figure class="item_image">
				<a href="' . $item['item_base'] . '" id="link_' . $counter . 'm" data-lightboximage="' . $counter . 'm" data-lightboxmessage="' . $item['id_msg'] . '">
					<img class="bbc_image has_lightbox" src="' . $item['thumbnail'] . '" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" />
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
			<img src="' . $item['thumbnail'] . '" class="bbc_img" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" />
		</a>';
	}

	private function complexTemplate($counter, $item)
	{
		$caption = !empty($item['description']) ? $item['description'] : $item['item_name'];
		$align = $item['align'] === 'center' ? '<figure class="centertext">' : '<figure style="float:' . $item['align'] . '">';
		$using = $item['type'] === 'preview' ? $item['preview'] : $item['thumbnail'];

		if ($item['item_type'] === 'image')
		{
			// Lightbox if its a thumb, otherwise a link
			return
				$align . ($item['type'] === 'preview'
				? '<a class="bbc_link" href="' . $item['item_url'] . '">'
				: '<a href="' . $item['item_base'] . '" id="link_' . $counter . 'm" data-lightboximage="' . $counter . 'm" data-lightboxmessage="' . $item['id_msg'] . '">') . '
					<img class="bbc_img' . ($item['type'] === 'preview' ? '' : ' has_lightbox') . '" src="' . $using . '" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" />
				</a>
				<figcaption class="centertext">
					<a class="bbc_link" href="' . $item['item_url'] . '" >' . $caption . '</a>
				</figcaption>
			</figure>';
		}

		return
			$align . '
				<a href="' . $item['item_url'] . '" class="bbc_link">
					<img class="bbc_img" src="' . $using . '" alt="' . $item['item_name'] . '" title="' . $item['item_name'] . '" />
				</a>
				<figcaption>
					<a class="bbc_link" href="' . $item['item_url'] . '" >' . $caption . '</a>
				</figcaption>	
			</figure>';
	}

	private function invalidTemplate()
	{
		global $settings, $txt;

		return '
		<img class="bbc_img" src="' . $settings['default_theme_url'] . '/levgal_res/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" />';
	}

	public function getItems()
	{
		// Load the items we need to render
		$itemModel = new LevGal_Model_ItemList();

		return $itemModel->getItemsById(array_keys($this->item_list));
	}
}
