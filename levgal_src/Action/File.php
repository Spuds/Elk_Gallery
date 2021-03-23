<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license LGPL (v3)
 *
 * @version 1.0.4 / elkarte
 */

/**
 * This file provides the handling for serving files, site/?media/file/*.
 */
class LevGal_Action_File extends LevGal_Action_Abstract
{
	/** @var bool */
	private $item_id;
	/** @var bool */
	private $item_slug;
	/** @var \LevGal_Model_Item */
	private $item_obj;
	/** @var bool */
	private $is_downloading = false;
	/** @var string */
	private $viewtype = 'raw';
	/** @var \LevGal_Model_File */
	private $file_model = false;
	/** @var bool */
	private $file_details = false;
	/** @var mixed */
	private $file_paths = false;
	/** @var int */
	private $file_start = 0;
	/** @var int */
	private $file_end = 0;
	/** @var bool */
	private $whole_file = true;

	public function __construct()
	{
		global $context;

		// We do NOT want to call the parent constructor in this case because we don't need it.

		// Attempt to get something useful.
		list ($this->item_slug, $this->item_id) = $this->getSlugAndId();

		// Fetch some details.
		$this->item_obj = new LevGal_Model_Item();
		$context['item_details'] = $this->item_obj->getItemInfoById($this->item_id);

		// Does the item even exist? Can they see it if it does?
		if (!$context['item_details'] || !$this->item_obj->isVisible())
		{
			LevGal_Helper_Http::fatalError('error_no_item');
		}

		// Does the item slug provided match the provided slug? If not, run away.
		if ($context['item_details']['item_slug'] != $this->item_slug)
		{
			LevGal_Helper_Http::hardRedirect($context['item_details']['item_url'] . (empty($_GET['sub']) ? '' : $_GET['sub'] . '/'));
		}
	}

	public function actionIndex()
	{
		// First obtain the model of the file itself, which will also obtain the album id.
		// This will, amongst other things, validate the item exists and prepare to do
		// our redirect magic if needed.
		$this->file_model = new LevGal_Model_File();
		$this->file_details = $this->file_model->getFileInfoById($this->item_id);
		$this->file_paths = $this->file_model->getFilePaths();

		// Does the item exist?
		if (empty($this->file_details) || empty($this->file_paths[$this->viewtype]))
		{
			LevGal_Helper_Http::setResponseExit(404, '404 File Not Found');
		}

		// Whereas with albums we can check permissions before redirection, for files we may not be able to.
		if ($this->file_details['item_slug'] != $this->item_slug)
		{
			LevGal_Helper_Http::hardRedirect($this->file_model->getFileUrl() . ($this->viewtype !== 'raw' ? $this->viewtype . '/' : ''));
		}

		// Just let's make sure we get the actual size of what we're sending, since thumbnails will
		// be smaller.
		$this->file_details['filesize'] = @filesize($this->file_paths[$this->viewtype]);

		// file_start = 0, file_end = n where n is the last byte index (thus 0-10 = 11 bytes total)
		$this->file_end = $this->file_details['filesize'] - 1;

		// End buffers.
		$this->endBuffers();

		// So, whatever file the user asked for definitely exists.
		// If we are serving a whole file, there are ways we might want to handle caching.
		if (!empty($_SERVER['HTTP_RANGE']))
		{
			$this->processRangeHeader();
		}
		else
		{
			// Let's do some header checks. Last-Modified and ETags are fun, right? They will
			// exit with appropriate headers if found.
			$this->processCachingHeaders();
		}

		// Before we go any further, let's operate on the presumption that, actually, we should
		// check visibility.  We didn't check it sooner since all we did before was validate
		// caching headers. There's little truly useful information.
		if (!$this->file_model->isVisible())
		{
			LevGal_Helper_Http::setResponseExit(404, '404 File Not Found');
		}

		// And so it begins.
		$this->setStandardHeaders();
		$this->setContentType();
		$this->setDisposition();

		// HEAD requests should go no further.
		$this->checkHeadRequest();

		// Let's use mod_xsendfile if we can.
		if ($this->whole_file)
		{
			$this->xSendFile();
		}

		$this->setContentDuration();

		// Finally... sending chunks.
		if (!$this->sendChunks())
		{
			LevGal_Helper_Http::setResponseExit(500, 'Something went wrong :(');
		}

		exit;
	}

	public function actionThumb()
	{
		$this->viewtype = 'thumb';

		return $this->actionIndex();
	}

	public function actionPreview()
	{
		$this->viewtype = 'preview';

		return $this->actionIndex();
	}

	public function actionDownload()
	{
		$this->is_downloading = true;

		return $this->actionIndex();
	}

	protected function endBuffers()
	{
		// Whatever happens, we need to clear any buffers.
		while (@ob_get_level() > 0)
		{
			@ob_end_clean();
		}
	}

	protected function processCachingHeaders()
	{
		// First, try and use an Expires header to prevent re-requests.
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 525600 * 60) . ' GMT');

		// Second, If-Modified-Since
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($this->file_paths[$this->viewtype])) . ' GMT');
		if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']))
		{
			list ($modified_since) = explode(';', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
			if ($this->file_model->modifiedSince(strtotime($modified_since)))
			{
				LevGal_Helper_Http::setResponseExit(304);
			}
		}

		// Third, ETag.
		$eTag = $this->file_model->getETag();
		if ($eTag !== '')
		{
			if (!empty($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $eTag)
			{
				LevGal_Helper_Http::setResponseExit(304);
			}
			header('ETag: ' . $eTag);
		}
	}

	protected function processRangeHeader()
	{
		global $modSettings;

		if (preg_match('~bytes=\h*(\d+)-(\d*)[\D.*]?~i', $_SERVER['HTTP_RANGE'], $matches))
		{
			// Can't start before the end of the file
			$this->file_start = (int) $matches[1];
			if (!empty($matches[2]))
			{
				$this->file_end = (int) $matches[2];
			}
			if ($this->file_start > $this->file_end)
			{
				$temp = $this->file_end;
				$this->file_end = $this->file_start;
				$this->file_start = $temp;
			}
		}
		else
		{
			// Range request without a bytes header? That's sort of not valid.
			LevGal_Helper_Http::setResponseExit(400);
		}

		// Check it's in bounds.
		if ($this->file_start > $this->file_details['filesize'] || $this->file_end > $this->file_details['filesize'])
		{
			LevGal_Helper_Http::setResponseExit(416);
		}

		// Attempt to be all funky and cap the size being sent. Except on iOS which gets very upset if we try this.
		if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'AppleCoreMedia') === false)
		{
			if ($this->file_end - $this->file_start + 1 > $modSettings['lgal_chunk_size'])
			{
			$this->file_end = $this->file_start + ($modSettings['lgal_chunk_size'] - 1);
			}
		}

		LevGal_Helper_Http::setResponse(206);
		header('Content-Range: bytes ' . $this->file_start . '-' . $this->file_end . '/' . $this->file_details['filesize']);
		$this->whole_file = false;
	}

	protected function setStandardHeaders()
	{
		header_remove('Pragma');
		header('Content-Encoding: none');
		header('Accept-Ranges: bytes');
		header('Connection:Keep-Alive');
		header('Content-Length: ' . ($this->file_end - $this->file_start + 1));
	}

	protected function setContentType()
	{
		// The mime isn't speaking, he is however sending headers. This is unfortunately complicated
		// because non-image files can/do have an image thumbnail.
		$content_type = '';

		// Firstly, deal with thumbnails, because we might have a different format thumbnail to whatever we started with.
		// And "tweak" the filename suitably.
		if ($this->viewtype !== 'raw')
		{
			switch (substr($this->file_paths[$this->viewtype], -7))
			{
				case 'jpg.dat':
					$content_type = 'image/jpeg';
					$this->file_details['filename'] .= '.jpg';
					break;
				case 'png.dat':
					$content_type = 'image/png';
					$this->file_details['filename'] .= '.png';
					break;
			}
		}

		// Let's try to send something useful.
		if (empty($content_type) && !empty($this->file_details['mime_type']))
		{
			$content_type = $this->file_details['mime_type'];

			// There are some magic overrides we have to use for iOS devices even though what we record is basically right.
			if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false))
			{
				if ($content_type === 'audio/mp4' && $this->file_details['extension'] === 'm4a')
				{
					$content_type = 'audio/x-m4a';
				}
				elseif ($content_type === 'video/mp4' && $this->file_details['extension'] === 'm4v')
				{
					$content_type = 'video/x-m4v';
				}
			}
		}

		// If in doubt, send *something*.
		if (empty($content_type))
		{
			$content_type = 'application/octet-stream';
		}

		header('Content-Type: ' . $content_type);
	}

	protected function setDisposition()
	{
		$disposition = $this->is_downloading ? 'attachment' : 'inline';

		$fileName = str_replace('"', '',  $this->file_details['filename']);

		// Send as UTF-8 if the name requires that
		$altName = '';
		if (preg_match('~[\x80-\xFF]~', $fileName))
		{
			$altName = "; filename*=UTF-8''" . rawurlencode($fileName);
		}

		header('Content-Disposition: ' . $disposition . '; filename="' . $fileName . '"' . $altName);
	}

	protected function setContentDuration()
	{
		global $context;

		// Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Configuring_servers_for_Ogg_media
		// Ref: http://tools.ietf.org/html/rfc3803
		if (!empty($context['item_details']['meta']['playtime']))
		{
			header('Content-Duration: ' . $context['item_details']['meta']['playtime']);
			header('X-Content-Duration: ' . $context['item_details']['meta']['playtime']);
		}
	}

	protected function checkHeadRequest()
	{
		// If this is a HEAD request, we really don't want to be doing any of this.
		if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'HEAD')
		{
			exit;
		}
	}

	protected function xSendFile()
	{
		global $modSettings;
		if (!empty($modSettings['lgal_xsendfile']))
		{
			header('X-Sendfile: ' . $this->file_paths[$this->viewtype]);
			exit;
		}
	}

	protected function sendChunks()
	{
		// Nice friendly vars
		$current_pos = $this->file_start;
		$segment_size = 8192; // 8KB segment size seems to be best for actually serving files.
		$file_name = $this->file_paths[$this->viewtype];
		$actual_end = $this->file_end + 1;

		if ($file_handle = @fopen($file_name, 'rb'))
		{
			fseek($file_handle, $this->file_start);
			while (!feof($file_handle) && ($current_pos < $actual_end) && (connection_status() == CONNECTION_NORMAL))
			{
				detectServer()->setTimeLimit(10);
				$chunk = @fread($file_handle, min($segment_size, $actual_end - $current_pos));
				echo $chunk;
				$current_pos += strlen($chunk);
			}
			fclose($file_handle);

			return true;
		}

		return false;
	}
}
