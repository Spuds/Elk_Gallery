<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0 / elkarte
 */

/**
 * This file deals with the foundations of the gallery.
 */
class LevGal_Controller extends Action_Controller
{
	public function action_index()
	{
		// Just pass through if we ever get here
		$this->LevGal();
	}

	public function LevGal()
	{
		global $txt;

		// For things we will want within the gallery.
		loadLanguage('levgal_lng/LevGal');

		if (!allowedTo('lgal_view'))
		{
			// If they're a guest, this will catch them.
			loadLanguage('levgal_lng/LevGal-Errors');
			is_not_guest($txt['cannot_lgal_view']);

			// If they're not, this will instead catch them.
			$_GET['action'] = $_GET['board'] = $_GET['topic'] = '';
			writeLog(true);
			LevGal_Helper_Http::fatalError('cannot_lgal_view');
		}

		// OK, dispatch time. First of all, can we load the class in question?
		$action = 'Home';
		if (!empty($_GET['sa']))
		{
			// Check it's valid and attempt to load it. Remember we can't load the abstract class,
			// better not do that.
			if (preg_match('~^[a-z]+$~i', $_GET['sa']) && strtolower($_GET['sa']) !== 'abstract')
			{
				$action = ucfirst(strtolower($_GET['sa']));
			}
			else
			{
				LevGal_Helper_Http::fatalError('levgal_invalid_action');
			}
		}

		// So it's probably legitimate. Let's try to load it.
		try
		{
			$class = 'LevGal_Action_' . $action;
			$handler = new $class();
			$sub_action = isset($_GET['sub']) && preg_match('~^[a-z_]+$~i', $_GET['sub']) ? ucfirst(strtolower($_GET['sub'])) : 'Index';
			$method = 'action' . $sub_action;

			if (method_exists($handler, $method))
			{
				$handler->$method();
			}
			else
			{
				LevGal_Helper_Http::fatalError('levgal_invalid_action');
			}
		}
		catch (RuntimeException $e)
		{
			// This means it wasn't a valid class.
			LevGal_Helper_Http::fatalError('levgal_invalid_action');
		}
	}
}

function levgal_pageindex($base_url, $current_page, $num_pages)
{
	global $modSettings;

	$links = array();
	// First, the << and < links, requires us to be on any page that isn't the first one.
	if ($current_page > 1)
	{
		$links[] = '<a class="navPages" href="' . $base_url . '">&laquo;</a>';
		$links[] = '<a class="navPages" href="' . $base_url . 'page-' . ($current_page - 1) . '">&lsaquo;</a>';
	}
	else
	{
		$links[] = '<span class="navPages">&laquo;</span>';
		$links[] = '<span class="navPages">&lsaquo;</span>';
	}

	// Shamelessly borrowed from constructPageIndex. But without quite so much faff.
	$PageContiguous = (int) ($modSettings['compactTopicPagesContiguous'] - ($modSettings['compactTopicPagesContiguous'] % 2)) / 2;

	// Show the first page. (>1< ... 6 7 [8] 9 10 ... 15)
	if ($current_page > ($PageContiguous + 1))
	{
		$links[] = '<a class="navPages" href="' . $base_url . '">1</a>';
	}

	// Show the ... after the first page.  (1 >...< 6 7 [8] 9 10 ... 15)
	if ($current_page > ($PageContiguous + 2))
	{
		$links[] = '<span style="font-weight: bold;" onclick="' . htmlspecialchars('levgal_expandPages(this, ' . JavaScriptEscape($base_url . 'page-%1$d/') . ', ' . 2 . ', ' . ($current_page - $PageContiguous - 1) . ');') . '" onmouseover="this.style.cursor = \'pointer\';"> ... </span>';
	}

	// Show the pages before the current one. (1 ... >6 7< [8] 9 10 ... 15)
	for ($nCont = $PageContiguous; $nCont >= 1; $nCont--)
	{
		if ($current_page > $nCont)
		{
			$this_page = $current_page - $nCont;
			$links[] = '<a class="navPages" href="' . $base_url . ($this_page != 1 ? 'page-' . $this_page . '/' : '') . '">' . $this_page . '</a>';
		}
	}

	// Show the current page. (1 ... 6 7 >[8]< 9 10 ... 15)
	$links[] = '<span class="current_page">' . $current_page . '</span>';

	// Show the pages after the current one... (1 ... 6 7 [8] >9 10< ... 15)
	//$tmpMaxPages = (int) (($max_value - 1) / $num_per_page) * $num_per_page;
	for ($nCont = 1; $nCont <= $PageContiguous; $nCont++)
	{
		if ($current_page + $nCont <= $num_pages)
		{
			$this_page = $current_page + $nCont;
			$links[] = '<a class="navPages" href="' . $base_url . 'page-' . $this_page . '/">' . $this_page . '</a>';
		}
	}

	// Show the '...' part near the end. (1 ... 6 7 [8] 9 10 >...< 15)
	if ($current_page + $PageContiguous + 1 < $num_pages)
	{
		$links[] = '<span style="font-weight: bold;" onclick="levgal_expandPages(this, \'' . ($base_url . 'page-%1$d/') . '\', ' . ($current_page + $PageContiguous + 1) . ', ' . ($num_pages - 1) . ');" onmouseover="this.style.cursor=\'pointer\';"> ... </span>';
	}

	// Show the last number in the list. (1 ... 6 7 [8] 9 10 ... >15<)
	if ($current_page + $PageContiguous < $num_pages)
	{
		$links[] = '<a class="navPages" href="' . $base_url . 'page-' . $num_pages . '/">' . $num_pages . '</a>';
	}

	// Lastly, the > and >> links, which require us to be on any page that isn't the last one.
	if ($current_page < $num_pages)
	{
		$links[] = '<a class="navPages" href="' . $base_url . 'page-' . ($current_page + 1) . '/">&rsaquo;</a>';
		$links[] = '<a class="navPages" href="' . $base_url . 'page-' . ($num_pages) . '/">&raquo;</a>';
	}
	else
	{
		$links[] = '<span class="navPages">&rsaquo;</span>';
		$links[] = '<span class="navPages">&raquo;</span>';
	}

	$wrapped = array_map(
		function ($el) {
			return "<li class=\"linavPages\">$el</li>";
		},
		$links
	);

	return '<ul class="pagelinks">' . implode(' ', $wrapped) . '</ul>';
}
