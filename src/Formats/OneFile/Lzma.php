<?php
namespace wapmorgan\UnifiedArchive\Formats\OneFile;

use wapmorgan\UnifiedArchive\Formats;

/**
 * Class Lzma
 *
 * @package wapmorgan\UnifiedArchive\Formats
 * @requires ext-lzma2
 */
class Lzma extends OneFileDriver
{
    const FORMAT_SUFFIX =  'xz';

    /**
     * @return array
     */
    public static function getSupportedFormats()
    {
        return [
            Formats::LZMA,
        ];
    }

    /**
     * @param $format
     * @return bool
     */
    public static function checkFormatSupport($format)
    {
        switch ($format) {
            case Formats::LZMA:
                return extension_loaded('xz');
        }
    }

    /**
     * @inheritDoc
     */
    public static function getDescription()
    {
        return 'adapter for ext-xz';
    }

    /**
     * @inheritDoc
     */
    public static function getInstallationInstruction()
    {
        return 'install `xz` extension';
    }

    /**
     * @inheritDoc
     */
    public function __construct($archiveFileName, $format, $password = null)
    {
        parent::__construct($archiveFileName, $password);
        $this->modificationTime = filemtime($this->fileName);
    }

    /**
     * @param string $fileName
     *
     * @return string|false
     */
    public function getFileContent($fileName = null)
    {
        return stream_get_contents(xzopen($this->fileName, 'r'));
    }

    /**
     * @param string $fileName
     *
     * @return bool|resource|string
     */
    public function getFileResource($fileName = null)
    {
        return xzopen($this->fileName, 'r');
    }

    /**
     * @param $data
     * @param $compressionLevel
     * @return mixed|string
     */
    protected static function compressData($data, $compressionLevel)
    {
        $fp = xzopen('php://temp', 'w');
        xzwrite($fp, $data);
        $data = stream_get_contents($fp);
        xzclose($fp);
        return $data;
    }
}