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
	/** @var array[]  */
	private $embed;
	/** @var attay[] */
	private $item_list;
	/** @var string */
	private $type;
	/** @var string */
	private $align;
	/** @var int */
	private $id;
	/** @var int  */
	private static $count = 1;

	public function __construct()
	{
		$this->embed = array(
			'simple' => array(),
			'complex' => array(),
		);
	}

	public function addSimple($id_item)
	{
		$this->embed['simple'][$id_item] = true;
		$this->item_list[$id_item] = true;
	}

	public function addComplex($description)
	{
		$this->item_list[$this->id] = true;
		$this->embed['complex'][self::$count++] = array(
			'id' => $this->id,
			'align' => $this->align,
			'type' => $this->type,
			'description' => trim($description),
		);
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
		global $txt, $settings;

		// First, load the items.
		$itemModel = new LevGal_Model_ItemList();
		$items = $itemModel->getItemsById(array_keys($this->item_list));

		foreach (array_keys($this->embed['simple']) as $id_item)
		{
			$search = '!<lgalmediasimple: ' . $id_item . '>';
			if (isset($items[$id_item]))
			{
				$buffer = str_replace($search, '<a href="' . $items[$id_item]['item_url'] . '" class="bbc_link"><img src="' . $items[$id_item]['thumbnail'] . '" alt="' . $items[$id_item]['item_name'] . '" title="' . $items[$id_item]['item_name'] . '" class="bbc_img" /></a>', $buffer);
			}
			else
			{
				$buffer = str_replace($search, '<img src="' . $settings['default_theme_url'] . '/levgal_res/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" class="bbc_img" />', $buffer);
			}
		}

		foreach ($this->embed['complex'] as $counter => $item)
		{
			$search = '!<lgalmediacomplex: ' . $counter . '>';
			if (isset($items[$item['id']]))
			{
				$caption = !empty($item['description']) ? $item['description'] : $items[$item['id']]['item_name'];
				$align = $item['align'] === 'center' ? '<div class="centertext">' : '<div style="text-align:' . $item['align'] . '">';
				$using = $item['type'] === 'preview' ? $items[$item['id']]['preview'] : $items[$item['id']]['thumbnail'];

				$buffer = str_replace($search, $align . '<a href="' . $items[$item['id']]['item_url'] . '" class="bbc_link"><img src="' . $using . '" alt="' . $items[$item['id']]['item_name'] . '" title="' . $items[$item['id']]['item_name'] . '" class="bbc_img" /></a><br /><a href="' . $items[$item['id']]['item_url'] . '" class="bbc_link">' . $caption . '</a></div>', $buffer);
			}
			else
			{
				$buffer = str_replace($search, '<img src="' . $settings['default_theme_url'] . '/levgal_res/icons/_invalid.png" alt="' . $txt['lgal_bbc_no_item'] . '" title="' . $txt['lgal_bbc_no_item'] . '" class="bbc_img" />', $buffer);
			}
		}
	}
}
