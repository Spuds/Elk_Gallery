<?php
/**
 * @package Levertine Gallery
 * @copyright 2014 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.2.0 / elkarte
 */

/**
 * This file deals with handling feeds, typically Atom.
 */
class LevGal_Helper_Feed
{
	/** @var string */
	public $title = '';
	/** @var string */
	public $subtitle = '';
	/** @var string */
	public $id = '';
	/** @var string */
	public $alternateUrl = '';
	/** @var string */
	public $selfUrl = '';
	/** @var array */
	private $entries;
	/** @var int */
	private $updated = 0;

	public function __construct()
	{
		// We need the functions for juggling tags.
		require_once(CONTROLLERDIR . '/News.controller.php');

		$this->entries = array();
	}

	public function outputFeed()
	{
		global $context, $boardurl, $scripturl;

		$this->fixBuffers();
		$this->issueHeader();

		echo '<?xml version="1.0" encoding="UTF-8"?' . '>
<feed xmlns="https://www.w3.org/2005/Atom">
	<title>', $this->title, '</title>';

		if (!empty($this->subtitle))
		{
			echo '
	<subtitle>', strip_tags($this->subtitle), '</subtitle>';
		}

		echo '
	<link rel="alternate" type="text/html" href="', $this->alternateUrl, '" />
	<link rel="self" type="application/atom+xml" href="', $this->selfUrl, '" />
	<id>', !empty($this->id) ? $this->id : $this->alternateUrl, '</id>
	<icon>', $boardurl, '/favicon.ico</icon>
	<updated>', $this->getTimestamp($this->updated), '</updated>
	<author>
		<name>', strip_tags(un_htmlspecialchars($context['forum_name'])), '</name>
		<uri>', $scripturl, '</uri>
	</author>
	<generator uri="https://www.elkarte.net" version="', LEVGAL_VERSION, '">Levertine Gallery</generator>';

		foreach ($this->entries as $entry)
		{
			echo '
	<entry>
		<title type="html">', $entry['title'], '</title>
		<link rel="alternate" type="text/html" href="', $entry['link'], '" />
		<content type="html" xml:base="', $entry['link'], '">', $entry['content'], '</content>';

			if (is_array($entry['category']))
			{
				echo '
		<category term="', $entry['category'][0], '" scheme="', $entry['category'][1], '" />';
			}
			else
			{
				echo '
		<category term="', $entry['category'], '" />';
			}

			if (is_array($entry['author']))
			{
				echo '
		<author>
			<name>', $entry['author'][0], '</name>
			<uri>', $scripturl, '?action=profile;u=', $entry['author'][1], '</uri>
		</author>';
			}
			else
			{
				echo '
		<author>
			<name>', $entry['author'], '</name>
		</author>';
			}

			if (isset($entry['published']))
			{
				echo '
		<published>', $entry['published'], '</published>';
			}

			echo '
		<updated>', $entry['updated'], '</updated>
		<id>', $entry['id'], '</id>
	</entry>';
		}
		echo '
</feed>';

		obExit(false);
	}

	public function addEntry($item)
	{
		global $context;

		$the_item = array(
			'title' => cdata_parse($item['title']),
			'link' => $item['link'],
			'content' => cdata_parse($item['content']),
			'category' => $item['category'],
		);
		if (empty($item['author']))
		{
			$the_item['author'] = $context['forum_name'];
		}
		elseif (is_array($item['author']))
		{
			// We expect an array of username, id here
			if (empty($item['author'][1]) || !allowedTo('profile_view_any'))
			{
				$the_item['author'] = $item['author'][0];
			}
			else
			{
				$the_item['author'] = $item['author'];
			}
		}
		else
		{
			$the_item['author'] = $item['author'];
		}

		if (isset($item['published']))
		{
			$the_item['published'] = $this->getTimestamp($item['published']);
		}

		$item['updated'] = !empty($item['updated']) ? $item['updated'] : 0;
		if ($item['updated'] > $this->updated)
		{
			$this->updated = $item['updated'];
		}
		$the_item['updated'] = $this->getTimestamp($item['updated']);
		$the_item['id'] = $item['id'] ?? $item['link'];

		$this->entries[] = $the_item;
	}

	private function fixBuffers()
	{
		global $modSettings;

		ob_end_clean();
		if (!empty($modSettings['enableCompressedOutput']))
		{
			@ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}
	}

	private function issueHeader()
	{
		header('Content-Type: application/atom+xml');
	}

	private function getTimestamp($time = 0)
	{
		if (method_exists('Util', 'gmstrftime'))
		{
			return Util::gmstrftime('%Y-%m-%dT%H:%M:%SZ', $time === 0 ? time() : $time);
		}

		return gmstrftime('%Y-%m-%dT%H:%M:%SZ', $time === 0 ? time() : $time);
	}
}
