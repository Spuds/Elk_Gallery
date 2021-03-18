<?php
/**
 * @package Levertine Gallery
 * @copyright 2014-2015 Peter Spicer (levertine.com)
 * @license proprietary
 *
 * @version 1.1.1 / elkarte
 */

/**
 * This file deals with getting Exif tags into a presentable form.
 */
class LevGal_Model_Metadata_ExifTag
{
	const TYPE_UNS_BYTE = 1;
	const TYPE_ASCII = 2;
	const TYPE_UNS_SHORT = 3;
	const TYPE_UNS_LONG = 4;
	const TYPE_UNS_RATIONAL = 5;
	const TYPE_SGN_BYTE = 6;
	const TYPE_UNDEFINED = 7;
	const TYPE_SGN_SHORT = 8;
	const TYPE_SGN_LONG = 9;
	const TYPE_SGN_RATIONAL = 10;
	const TYPE_FLOAT = 11;
	const TYPE_DOUBLE = 12;

	public function __construct()
	{
		loadLanguage('levgal_lng/LevGal-Exif');
	}

	public function formatData($data)
	{
		foreach ($data as $tag => $value)
		{
			if (!is_array($value))
			{
				$method = 'parseTag' . str_replace('/', '', $tag);
				$data[$tag] = method_exists($this, $method) ? $this->$method($value) : $value;
			}
			else
			{
				$data[$tag] = $this->formatData($value);
			}
		}

		return $data;
	}

	public function identifyTag($tag_bytes)
	{
		static $tags = null;
		if ($tags === null)
		{
			// IFD0: Camera information
			// IFD1: Thumbnail information
			// Exif SubIFD: Image information
			// Interopability IFD: additional meta
			// Other: other tags that may be present
			$tags = array(
				'0001' => 'InteroperabilityIndex',            // Interoperability IFD; text, 3 bytes
				'0002' => 'InteroperabilityVersion',        // Interoperability IFD; unsigned int
				'000b' => 'ACDComment',                        // IFD0; text <= 999 bytes
				'00fe' => 'ImageType',                        // IFD0; signed long
				'00ff' => 'SubfileType',                    // Other; no further info
				'0100' => 'ImageWidth',                        // IFD1; unsigned short
				'0101' => 'ImageLength',                    // IFD1; unsigned short
				'0102' => 'BitsPerSample',                    // IFD1; unsigned short
				'0103' => 'Compression',                    // IFD1; unsigned short: 1, 6
				'0106' => 'PhotometricInterpret',            // IFD1; unsigned short, 0..4
				'010e' => 'ImageDescription',                // IFD0; text <= 999 bytes
				'010f' => 'Make',                            // IFD0; text <= 999 bytes
				'0110' => 'Model',                            // IFD0; text <= 999 bytes
				'0111' => 'StripOffsets',                    // IFD1; no further info
				'0112' => 'Orientation',                    // IFD0; unsigned short, 1..9
				'0115' => 'SamplePerPixel',                    // IFD0; unsigned short
				'0116' => 'RowsPerStrip',                    // IFD1; no further info
				'0117' => 'StripByteCounts',                // IFD1; no further info
				'011a' => 'xResolution',                    // IFD0; positive rational
				'011b' => 'yResolution',                    // IFD0; positive rational
				'011c' => 'PlanarConfig',                    // IFD0; unsigned short, 1..2
				'0128' => 'ResolutionUnit',                    // IFD0; unsigned short, 1..3
				'012d' => 'TransferFunction',                // Other; no further info
				'0131' => 'Software',                        // IFD0; text <= 999 bytes
				'0132' => 'DateTime',                        // IFD0; YYYY:MM:DD HH:MM:SS datetime
				'013b' => 'Artist',                            // IFD0; text <= 999 bytes
				'013c' => 'HostComputer',                    // IFD0; text (maxlength?)
				'013d' => 'Predictor',                        // Other; no further info
				'013e' => 'WhitePoint',                        // IFD0; 2x positive rational numbers
				'013f' => 'PrimaryChromaticities',            // IFD0; 6x positive rational numbers
				'0142' => 'TileWidth',                        // Other; no further info
				'0143' => 'TileLength',                        // Other; no further info
				'0144' => 'TileOffsets',                    // Other; no further info
				'0145' => 'TileByteCounts',                    // Other; no further info
				'014a' => 'SubIFDs',                        // Other; no further info
				'015b' => 'JPEGTables',                        // Other; no further info
				'0201' => 'JpegIFOffset',                    // IFD1; no further info
				'0202' => 'JpegIFByteCount',                // IFD1; no further info
				'0211' => 'YCbCrCoefficients',                // IFD0; 3x positive rational numbers
				'0212' => 'YCbCrSubSampling',                // IFD1; no further info
				'0213' => 'YCbCrPositioning',                // IFD0; unsigned short, 1..2
				'0214' => 'ReferenceBlackWhite',            // IFD0; 6x positive rational numbers
				'1000' => 'RelatedImageFileFormat',            // Interoperability IFD: text <= 999 bytes
				'1001' => 'RelatedImageWidth',                // Interoperability IFD: unsigned short
				'1002' => 'RelatedImageLength',                // Interoperability IFD: unsigned short
				'828d' => 'CFARepeatPatternDim',            // Other; no further info
				'828e' => 'CFAPattern',                        // Other; no further info
				'828f' => 'BatteryLevel',                    // Other; no further info
				'8298' => 'Copyright',                        // IFD0; text <= 999 bytes
				'829a' => 'ExposureTime',                    // Exif; secs or fractions of sec, 1/x
				'829d' => 'FNumber',                        // Exif; positive rational number
				'83bb' => 'IPTC/NAA',                        // Other; no further info
				'8649' => 'PhotoshopSettings',                // IFD0; no further info
				'8769' => 'ExifOffset',                        // IFD0; unsigned int
				'8773' => 'InterColorProfile',                // Other; no further info
				'8822' => 'ExposureProgram',                // Exif; unsigned int, 1..9
				'8824' => 'SpectralSensitivity',            // Exif; no further info
				'8825' => 'GPSInfoOffset',                    // IFD0; unsigned int
				'8827' => 'ISOSpeedRatings',                // Exif; unsigned short
				'8828' => 'OECF',                            // Other; no further info
				'8829' => 'Interlace',                        // Other; no further info
				'882a' => 'TimeZoneOffset',                    // Other; no further info
				'882b' => 'SelfTimerMode',                    // Other; no further info
				'8830' => 'SensitivityType',                // Exif; unsigned short, 0..7
				'8832' => 'RecommendedExposureIndex',        // Exif; no further info
				'9000' => 'ExifVersion',                    // Exif; no further info
				'9003' => 'DateTimeOriginal',                // Exif; YYYY:MM:DD HH:MM:SS datetime
				'9004' => 'DateTimeDigitized',                // Exif; YYYY:MM:DD HH:MM:SS datetime
				'9101' => 'ComponentsConfiguration',        // Exif; no further info
				'9102' => 'CompressedBitsPerPixel',            // Exif; positive rational number
				'9201' => 'ShutterSpeedValue',                // Exif; secs or fractions of sec, 1/x
				'9202' => 'ApertureValue',                    // Exif; positive rational number
				'9203' => 'BrightnessValue',                // Exif; positive rational number
				'9204' => 'ExposureBiasValue',                // Exif; positive rational number
				'9205' => 'MaxApertureValue',                // Exif; positive rational number
				'9206' => 'SubjectDistance',                // Exif; positive rational number
				'9207' => 'MeteringMode',                    // Exif; unsigned int, 1..6 or 255
				'9208' => 'LightSource',                    // Exif; unsigned int, 1..255
				'9209' => 'Flash',                            // Exif; unsigned int, 1..255
				'920a' => 'FocalLength',                    // Exif; positive rational number (mm)
				'920b' => 'FlashEnergy',                    // Other; no further info
				'920c' => 'SpatialFrequencyResponse',        // Other; no further info
				'920d' => 'Noise',                            // Other; no further info
				'9211' => 'ImageNumber',                    // Other; no further info
				'9212' => 'SecurityClassification',            // Other; no further info
				'9213' => 'ImageHistory',                    // Exif; text <= 999 bytes
				'9214' => 'SubjectLocation',                // Other; no further info
				'9215' => 'ExposureIndex',                    // Other; no further info
				'9216' => 'TIFF/EPStandardID',                // Other; no further info
				'927c' => 'MakerNote',                        // Exif; vendor specific data
				'9286' => 'UserCommentOld',                    // IFD0; no further info
				'9290' => 'SubsecTime',                        // Exif; text <= 999 bytes
				'9291' => 'SubsecTimeOriginal',                // Exif; text <= 999 bytes
				'9292' => 'SubsecTimeDigitized',            // Exif; text <= 999 bytes
				'9c9b' => 'XPTitle',                        // Exif extension, unsigned bytestream <= 999 bytes in UCS2
				'9c9c' => 'XPComment',                        // Exif extension, unsigned bytestream <= 999 bytes in UCS2
				'9c9d' => 'XPAuthor',                        // Exif extension, unsigned bytestream <= 999 bytes in UCS2
				'9c9e' => 'XPKeywords',                        // Exif extension, unsigned bytestream <= 999 bytes in UCS2
				'9c9f' => 'XPSubject',                        // Exif extension, unsigned bytestream <= 999 bytes in UCS2
				'a000' => 'FlashPixVersion',                // Exif; no further info
				'a001' => 'ColorSpace',                        // Exif; unsigned int: 1 or 65535
				'a002' => 'ExifImageWidth',                    // Exif; unsigned short
				'a003' => 'ExifImageHeight',                // Exif; unsigned short
				'a004' => 'RelatedSoundFile',                // Exif; text, 12 bytes
				'a005' => 'ExifInteroperabilityOffset',        // Exif; unsigned int
				'a20b' => 'FlashEnergy',                    // Other; no further info
				'a20c' => 'SpacialFreqResponse',            // Exif; no further info
				'a20e' => 'FocalPlaneXResolution',            // Exif; positive rational number
				'a20f' => 'FocalPlaneYResolution',            // Exif; positive rational number
				'a210' => 'FocalPlaneResolutionUnit',        // Exif; unsigned short, 1..3
				'a214' => 'SubjectLocation',                // Exif; 2x unsigned short
				'a215' => 'ExposureIndex',                    // Exif; positive rational number
				'a217' => 'SensingMethod',                    // Exif; unsigned short, 1..8
				'a300' => 'FileSource',                        // Exif; int
				'a301' => 'SceneType',                        // Exif; int
				'a302' => 'CFAPattern',                        // Exif; no further info
				'a401' => 'CustomerRender',                    // Exif; unsigned short, 0..1
				'a402' => 'ExposureMode',                    // Exif; unsigned short, 0..2
				'a403' => 'WhiteBalance',                    // Exif; unsigned short, 0..1
				'a404' => 'DigitalZoomRatio',                // Exif; positive rational number
				'a405' => 'FocalLengthIn35mmFilm',            // Exif; no further info
				'a406' => 'SceneCaptureMode',                // Exif; unsigned short, 0..3
				'a407' => 'GainControl',                    // Exif; unsigned short, 0..4
				'a408' => 'Contrast',                        // Exif; unsigned short, 0..2
				'a409' => 'Saturation',                        // Exif; unsigned short, 0..2
				'a40a' => 'Sharpness',                        // Exif; unsigned short, 0..2
				'a434' => 'LensInfo',                        // Exif; no further info
			);
		}

		return $tags[$tag_bytes] ?? 'UnknownTag[' . $tag_bytes . ']';
	}

	public function identifyType($type_bytes)
	{
		static $types = null;
		if ($types === null)
		{
			$types = array(
				'0001' => array(self::TYPE_UNS_BYTE, 1),
				'0002' => array(self::TYPE_ASCII, 1),
				'0003' => array(self::TYPE_UNS_SHORT, 2),
				'0004' => array(self::TYPE_UNS_LONG, 4),
				'0005' => array(self::TYPE_UNS_RATIONAL, 8),
				'0006' => array(self::TYPE_SGN_BYTE, 1),
				'0007' => array(self::TYPE_UNDEFINED, 1),
				'0008' => array(self::TYPE_SGN_SHORT, 2),
				'0009' => array(self::TYPE_SGN_LONG, 4),
				'000a' => array(self::TYPE_SGN_RATIONAL, 8),
				'000b' => array(self::TYPE_FLOAT, 4),
				'000c' => array(self::TYPE_DOUBLE, 8),
			);
		}

		return $types[$type_bytes] ?? array('error[' . $type_bytes . ']', 0);
	}

	public function parseTagMake($data)
	{
		// Tag id: 010f
		return ucwords(strtolower(trim($data))); // This is the maker name, typically. Title case preferred.
	}

	public function parseTagDateTime($data)
	{
		// Tag id: 0132
		return LevGal_Helper_Format::time(strtotime($data), 'unmodified');
	}

	public function parseTagExposureTime($data)
	{
		// Tag id: 829a
		return $this->formatExposure($data);
	}

	public function parseTagFNumber($data)
	{
		// Tag id: 829d
		global $txt;

		return sprintf($txt['lgal_exif_fnumber'], round($data, 2));
	}

	public function parseTagExposureProgram($data)
	{
		// Tag id: 8822
		return $this->genericLookup('exposure', $data);
	}

	public function parseTagSensitivityType($data)
	{
		// Tag id: 8830
		return $this->genericLookup('sensitivity', $data);
	}

	public function parseTagShutterSpeedValue($data)
	{
		// Tag id: 9201
		global $txt;
		// This is given in the APEX mode.
		$data = exp($data * log(2));
		if ($data > 1)
		{
			$data = floor($data);
		}
		if ($data > 0)
		{
			$recip = 1 / $data;
			list ($num, $div) = $this->convertToFraction($recip);
			if ($num >= 1 && $div == 1)
			{
				return sprintf($txt['lgal_exif_seconds'], round($num, 2));
			}
			else
			{
				return sprintf($txt['lgal_exif_1n_seconds'], $num, $div);
			}
		}

		return null;
	}

	public function parseTagBrightnessValue($data)
	{
		// Tag id: 9203
		return round($data, 4);
	}

	public function parseTagMeteringMode($data)
	{
		// Tag id: 9207
		return $this->genericLookup('metering', $data);
	}

	public function parseTagLightSource($data)
	{
		// Tag id: 9208
		return $this->genericLookup('lightsource', $data);
	}

	public function parseTagFlash($data)
	{
		// Tag id: 9209
		// This could actually be parsed out with bitmasking but this is the list defined in spec.
		return $this->genericLookup('flash', $data);
	}

	public function parseTagFocalLength($data)
	{
		// Tag id: 920a
		global $txt;

		return sprintf($txt['lgal_exif_focal_length'], $data);
	}

	public function parseTagDigitalZoomRatio($data)
	{
		// Tag id: a404
		global $txt;

		return $data > 0 ? sprintf($txt['lgal_exif_digital_zoom'], $data) : $txt['lgal_exif_digital_zoom_unused'];
	}

	public function parseTagContrast($data)
	{
		// Tag id: a408
		return $this->genericLookup('contrast', $data);
	}

	public function parseTagSharpness($data)
	{
		// Tag id: a40a
		return $this->genericLookup('sharpness', $data);
	}

	protected function genericLookup($item, $value)
	{
		global $txt;

		return $txt['lgal_exif_' . $item . '_' . $value] ?? sprintf($txt['lgal_exif_' . $item . '_unknown'], $value);
	}

	public function formatExposure($data)
	{
		global $txt;
		if (strpos($data, '/') !== false)
		{
			return $txt['lgal_exif_bulb'];
		}
		else
		{
			if ($data >= 1)
			{
				return sprintf($txt['lgal_exif_seconds'], round($data, 2));
			}
			else
			{
				list ($num, $div) = $this->convertToFraction($data);

				return sprintf($txt['lgal_exif_1n_seconds'], $num, $div);
			}
		}
	}

	public function convertToFraction($data)
	{
		if ($data != 0)
		{
			$div = 0;
			for ($num = 1; $num < 100; $num++)
			{
				$recip = 1 / $data * $num;
				$div = round($recip, 0);
				if (abs($div - $recip) < 0.025)
				{
					return array($num, $div);
				}
			}
		}

		// Return *something*.
		return array(0, 1);
	}
}
