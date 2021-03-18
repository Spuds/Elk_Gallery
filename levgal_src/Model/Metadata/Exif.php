<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with getting Exif information.
 */
class LevGal_Model_Metadata_Exif
{
	/** @var string */
	private $file;
	/** @var array  */
	private $errors;
	/** @var resource */
	private $handle;
	/** @var resource */
	private $seek;
	/** @var array  */
	private $data;
	/** @var bool  */
	private $intelByteOrder = true;
	/** @var int  */
	private $offset = 0;
	/** @var \LevGal_Model_Metadata_ExifTag  */
	private $tag;

	public function __construct($file)
	{
		$this->file = $file;
		$this->errors = array();
		$this->data = array();

		if (!$this->openFile())
		{
			return array('errors' => $this->errors);
		}

		$this->tag = new LevGal_Model_Metadata_ExifTag();
	}

	public function __destruct()
	{
		$this->closeFile();
	}

	protected function openFile()
	{
		$this->handle = @fopen($this->file, 'rb');
		$this->seek = @fopen($this->file, 'rb');

		if (!$this->handle || !$this->seek)
		{
			$this->errors['not_readable'] = true;

			return false;
		}

		return true;
	}

	protected function closeFile()
	{
		@fclose($this->handle);
		@fclose($this->seek);
	}

	protected function skipBytes($num, $handle = true)
	{
		fseek($handle ? $this->handle : $this->seek, $num, SEEK_CUR);
	}

	protected function readBytes($num, $handle = true)
	{
		return fread($handle ? $this->handle : $this->seek, $num);
	}

	protected function readBytesEndianHex($num, $handle = true)
	{
		$bytes = bin2hex($this->readBytes($num, $handle));

		return $this->intelByteOrder ? $this->switchEndian($bytes) : $bytes;
	}

	protected function readBytesAsInt($num_bytes, $handle = true)
	{
		$bytes = bin2hex($this->readBytes($num_bytes, $handle));

		return hexdec($this->intelByteOrder ? $this->switchEndian($bytes) : $bytes);
	}

	protected function switchEndian($input)
	{
		return implode('', array_reverse(str_split($input, 2)));
	}

	protected function detectEndianness()
	{
		$header = $this->readBytes(2);
		switch ($header)
		{
			case 'II':
				$this->data['Endian'] = 'Intel';
				break;
			case 'MM':
				$this->data['Endian'] = 'Motorola';
				$this->intelByteOrder = false;
				break;
			default:
				$this->data['Endian'] = 'unknown'; // and assume Intel endianness.
				break;
		}
	}

	public function getExif()
	{
		$this->offset = 0;
		// Check it's a JPEG.
		$data = $this->readBytes(2);
		if ($data != "\xff\xd8")
		{
			$this->closeFile();
			$this->errors['not_jpeg'] = true;

			return array('errors' => $this->errors);
		}

		// Start looking for directory data.
		$data = '';
		$size = 0;
		$header = '';
		$loop = 0;

		while (!feof($this->handle) && ++$loop < 250) // To prevent infinite looping through the entire file.
		{
			$data = $this->readBytes(2);
			$size = hexdec(bin2hex($this->readBytes(2))); // This one is not Intel ordered.

			switch ($data)
			{
				case "\xff\xc0": // Start of Frame, End of Frame markers
				case "\xff\xd9":
					break;
				case "\xff\xe0": // JFIF marker
					$this->data['JFIF']['Valid'] = true;
					$this->data['JFIF']['Size'] = $size;
					if ($size > 2)
					{
						$jfif_data = $this->readBytes($size - 2);
						$this->data['JFIF']['Identifier'] = substr($jfif_data, 0, 5);
						$this->data['JFIF']['ExtensionCode'] = bin2hex(substr($jfif_data, 6, 1));
					}
					$this->offset += $size + 2;
					break;
				case "\xff\xe1": // APP1 marker (Exif / TIFF IFD, or JPEG thumbnail)
					$header = $this->readBytes(6);
					if ($header == "Exif\x00\x00")
					{
						$this->data['EXIF']['Valid'] = true;
						$this->data['APP1']['Valid'] = true;
						$this->data['APP1']['Size'] = $size;
						break 2; // Need to exit both this switch and the while loop since we hit what we were looking for.
					}
					else
					{
						// We hit an APP1 marker but not an Exif one. Let's skip to the next one.
						if ($size > 2)
						{
							$data = $this->readBytes($size - 2 - 6); // (skip Exif marker plus size)
						}
						$this->offset += $size + 2;
					}
					break;
				case "\xff\xe2": // APP2 marker, data fetchable by way of readBytes($size - 2);
				case "\xff\xed": // IPTC marker, data fetchable by way of readBytes($size - 2)
				case "\xff\xfe": // Comment extension marker (COM), data fetchable by way of readBytes($size - 2)
					if ($size > 2)
					{
						// We have extra data in these segments if we readBytes($size - 2)
						// But we're not doing anything with it, so no real need to preserve it.
						$data = $this->readBytes($size - 2);
					}
					$this->offset += $size + 2;
					break;
				default: // Some unknown marker, skip it anyway
					if ($size > 2)
					{
						$data = $this->readBytes($size - 2);
					}
					$this->offset += $size + 2;
			}
		}

		// Right, is this one the Exif header? It should be the last one we found, if we found one.
		if ($header != "Exif\x00\x00")
		{
			$this->closeFile();

			return $this->data;
		}

		// Onwards into the meat of the header.
		$this->detectEndianness();

		// Need a little skip. This should just be a marker of 0x002a.
		$this->skipBytes(2);
		$offset = $this->readBytesAsInt(4);

		// Probably a bit far away to be legit...
		if ($offset > 100000)
		{
			unset ($this->data['EXIF']['Valid']);
			$this->closeFile();

			return $this->data;
		}

		// Need to push the file position on a bit.
		if ($offset > 8)
		{
			$this->skipBytes($offset - 8);
		}
		$this->offset += 12;

		$this->readBlock('IFD0');

		$this->data['IFD1Offset'] = $this->readBytesAsInt(4);

		// Did we have some Exif data?
		if (empty($this->data['IFD0']['ExifOffset']))
		{
			$this->closeFile();

			return $this->data;
		}

		// Now we hunt us some SubIFDs.
		$seek = fseek($this->handle, $this->offset + $this->data['IFD0']['ExifOffset']);
		if ($seek == -1)
		{
			$this->errors['no_SubIFD'] = true;
		}

		$this->readBlock('SubIFD');

		if (empty($this->data['IFD1Offset']))
		{
			$this->closeFile();

			return $this->data;
		}

		$seek = fseek($this->handle, $this->offset + $this->data['IFD1Offset']);
		if ($seek == -1)
		{
			$this->errors['no_IFD1'] = true;
		}

		$this->readBlock('IFD1');

		// More data to play with?
		if (empty($this->data['SubIFD']['ExifInteroperabilityOffset']))
		{
			$this->closeFile();

			return $this->data;
		}

		$seek = fseek($this->handle, $this->offset + $this->data['SubIFD']['ExifInteroperabilityOffset']);
		if ($seek == -1)
		{
			$this->errors['no_InteroperabilityIFD'] = true;
		}

		$this->readBlock('InteroperabilityIFD');

		$this->closeFile();

		return $this->data;
	}

	protected function readBlock($block)
	{
		$this->data[$block]['NumTags'] = $this->readBytesAsInt(2);
		if ($this->data[$block]['NumTags'] < 1000)
		{
			for ($i = 0; $i < $this->data[$block]['NumTags']; $i++)
			{
				$this->readEntry($block);
			}
		}
		else
		{
			$this->errors['illegal_' . $block . '_size'] = true;
		}
	}

	protected function readEntry($block)
	{
		if (feof($this->handle))
		{
			$this->errors['unexpected_eof'] = true;

			return;
		}

		$tag_bytes = $this->readBytesEndianHex(2);
		$tag_name = $this->tag->identifyTag($tag_bytes);

		$type_bytes = $this->readBytesEndianHex(2);
		list ($type_name, $size) = $this->tag->identifyType($type_bytes);

		$byte_count = $size * $this->readBytesAsInt(4);

		$value = $this->readBytes(4);
		if ($byte_count <= 4)
		{
			$data = $value;
		}
		elseif ($byte_count < 100000)
		{
			$value = bin2hex($value);
			$offset = hexdec($this->intelByteOrder ? $this->switchEndian($value) : $value);
			$seek = fseek($this->seek, $this->offset + $offset);

			if ($seek == 0)
			{
				$data = $this->readBytes($byte_count, false); // use $this->seek not $this->handle
			}
			elseif (empty($this->errors['read_error']))
			{
				$this->errors['read_error'] = 1;
			}
			else
			{
				$this->errors['read_error']++;
			}
		}
		else
		{
			$this->errors['oversize_' . $block . '_block'] = true;

			return;
		}

		// Here we might switch out if $tag_name === 'GPSInfoOffset' to get geodata.
		// Additionally if we ever wanted to do vendor-specific extensions with 'MakerNote', here's the place.
		$this->data[$block][$tag_name] = $this->prepareData($tag_name, $type_name, $data);
	}

	protected function prepareData($tag, $type, $data)
	{
		static $type_list = null;
		if ($type_list === null)
		{
			$type_list = array(
				'rational' => array(LevGal_Model_Metadata_ExifTag::TYPE_UNS_RATIONAL, LevGal_Model_Metadata_ExifTag::TYPE_SGN_RATIONAL),
				'numeric' => array(LevGal_Model_Metadata_ExifTag::TYPE_UNS_SHORT, LevGal_Model_Metadata_ExifTag::TYPE_SGN_SHORT, LevGal_Model_Metadata_ExifTag::TYPE_UNS_LONG, LevGal_Model_Metadata_ExifTag::TYPE_SGN_LONG, LevGal_Model_Metadata_ExifTag::TYPE_FLOAT, LevGal_Model_Metadata_ExifTag::TYPE_DOUBLE),
			);
		}

		if ($type == LevGal_Model_Metadata_ExifTag::TYPE_UNS_BYTE)
		{
			if (in_array($tag, array('XPTitle', 'XPComment', 'XPAuthor', 'XPKeywords', 'XPSubject')))
			{
				$data = $this->parseUCS2toEntity($data); // These are all UCS-2 fields added by Windows Explorer.
			}
		}
		if ($type == LevGal_Model_Metadata_ExifTag::TYPE_ASCII)
		{
			// Strings are null-terminated.
			if (($pos = strpos($data, chr(0))) !== false)
			{
				$data = substr($data, 0, $pos);
			}

			if ($tag === 'Make') // 010f
			{
				$data = ucwords(strtolower(trim($data))); // The specification indicates this is 7-bit ASCII
			}
		}
		elseif ($type == LevGal_Model_Metadata_ExifTag::TYPE_UNDEFINED)
		{
			// We still want to preserve this, but we need to do it by way of base-64 encoding in the DB.
			// Tags such as MakerNote (0x927c) come into this category.
			$data = base64_encode($data);
		}
		elseif (in_array($type, $type_list['rational']))
		{
			// First, get the top/bottom values, allowing for endianness.
			$data = bin2hex($data);
			if ($this->intelByteOrder)
			{
				$data = $this->switchEndian($data);
				$top = hexdec(substr($data, 8, 8));
				$bottom = hexdec(substr($data, 0, 8));
			}
			else
			{
				$top = hexdec(substr($data, 0, 8));
				$bottom = hexdec(substr($data, 8, 8));
			}
			// And allow for signed values.
			$top -= ($type == LevGal_Model_Metadata_ExifTag::TYPE_SGN_RATIONAL && $top > 0x7FFFFFFF) ? 0x100000000 : 0;
			if ($bottom != 0)
			{
				$data = $top / $bottom;
			}
			elseif ($top == 0)
			{
				$data = 0;
			}
			else
			{
				$data = $top . '/' . $bottom;
			}
		}
		elseif (in_array($type, $type_list['numeric']))
		{
			$data = bin2hex($data);
			if ($this->intelByteOrder)
			{
				$data = $this->switchEndian($data);
			}
			if (!$this->intelByteOrder && ($type == LevGal_Model_Metadata_ExifTag::TYPE_UNS_SHORT || $type == LevGal_Model_Metadata_ExifTag::TYPE_SGN_SHORT))
			{
				$data = substr($data, 0, 4);
			}
			$data = hexdec($data);

			// If we're dealing with signed numbers, let's actually fix the sign.
			$data -= ($type == LevGal_Model_Metadata_ExifTag::TYPE_SGN_SHORT && $data > 0x7FFF) ? 0x10000 : 0;
			$data -= ($type == LevGal_Model_Metadata_ExifTag::TYPE_SGN_LONG && $data > 0x7FFFFFFF) ? 0x100000000 : 0;
		}

		// This just *gets* it. Formatting is handled by ExifTag not here.
		return $data;
	}

	protected function parseUCS2toEntity($data)
	{
		// We are going to be parsing this into ASCII with entities, which is the only way we can handle it in all encodings SMF supports.
		// We can assume the endianness is LE because this *should* be Windows XP+ only, which is LE only.

		$result = '';
		for ($i = 0, $n = strlen($data); $i < $n; $i += 2)
		{
			$codepoint = (ord(substr($data, $i + 1, 1)) << 8) + ord(substr($data, $i, 1));
			if ($codepoint >= 0xD800)
			{
				// It's in UCS-2 4-byte. This means we have the value less 0x10000 split into two 10-bit segments.
				$codepoint -= 0xD800;
				$secondary = (ord(substr($data, $i + 3, 1)) << 8) + ord(substr($data, $i + 2, 1));
				$secondary -= 0xDC00;
				$i += 2; // We've consumed two more bytes, push it along.
				$codepoint = ($codepoint << 10) + $secondary + 0x10000;
			}

			// At this point we have a decoded codepoint we can work with.
			if ($codepoint == 0)
			{
				continue;
			}
			elseif ($codepoint > 127)
			{
				// It's beyond the ASCII plane, entitify it.
				$result .= '&#x' . $codepoint . ';';
			}
			else
			{
				$result .= chr($codepoint);
			}
		}

		return $result;
	}
}
