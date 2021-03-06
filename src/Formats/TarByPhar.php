<?php
namespace wapmorgan\UnifiedArchive\Formats;

use Exception;
use FilesystemIterator;
use Phar;
use PharData;
use RecursiveIteratorIterator;
use wapmorgan\UnifiedArchive\ArchiveEntry;
use wapmorgan\UnifiedArchive\ArchiveInformation;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveCreationException;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveExtractionException;
use wapmorgan\UnifiedArchive\Exceptions\ArchiveModificationException;
use wapmorgan\UnifiedArchive\Exceptions\UnsupportedArchiveException;
use wapmorgan\UnifiedArchive\Exceptions\UnsupportedOperationException;
use wapmorgan\UnifiedArchive\Formats;

class TarByPhar extends BasicDriver
{
    /**
     * @var false|string
     */
    protected $archiveFileName;

    /**
     * @var PharData
     */
    protected $tar;

    /**
     * @var int Flags for iterator
     */
    const PHAR_FLAGS = FilesystemIterator::UNIX_PATHS;

    /**
     * @return array
     */
    public static function getSupportedFormats()
    {
        return [
            Formats::TAR,
            Formats::TAR_GZIP,
            Formats::TAR_BZIP,
        ];
    }

    /**
     * @param $format
     * @return bool
     */
    public static function checkFormatSupport($format)
    {
        $availability = class_exists('\PharData');
        switch ($format) {
            case Formats::TAR:
                return $availability;
            case Formats::TAR_GZIP:
                return $availability && extension_loaded('zlib');
            case Formats::TAR_BZIP:
                return $availability && extension_loaded('bz2');
        }
    }

    /**
     * @inheritDoc
     */
    public static function getDescription()
    {
        return 'adapter for ext-phar';
    }

    /**
     * @inheritDoc
     */
    public static function getInstallationInstruction()
    {
        return 'install `phar` extension and optionally php-extensions (zlib, bzip2)';
    }

    /**
     * @inheritDoc
     */
    public function __construct($archiveFileName, $format, $password = null)
    {
        $this->archiveFileName = realpath($archiveFileName);
        $this->open();
    }

    /**
     *
     */
    protected function open()
    {
        $this->tar = new PharData($this->archiveFileName, self::PHAR_FLAGS);
    }

    /**
     * @inheritDoc
     */
    public function getArchiveInformation()
    {
        $information = new ArchiveInformation();
        $stream_path_length = strlen('phar://'.$this->archiveFileName.'/');
        foreach (new RecursiveIteratorIterator($this->tar) as $i => $file) {
            $information->files[] = substr($file->getPathname(), $stream_path_length);
            $information->compressedFilesSize += $file->getCompressedSize();
            $information->uncompressedFilesSize += filesize($file->getPathname());
        }
        return $information;
    }

    /**
     * @inheritDoc
     */
    public function getFileNames()
    {
        $files = [];

        $stream_path_length = strlen('phar://'.$this->archiveFileName.'/');
        foreach (new RecursiveIteratorIterator($this->tar) as $i => $file) {
            $files[] = substr($file->getPathname(), $stream_path_length);
        }

        return $files;
    }

    /**
     * @inheritDoc
     */
    public function isFileExists($fileName)
    {
        try {
            $this->tar->offsetGet($fileName);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getFileData($fileName)
    {
        /** @var \PharFileInfo $entry_info */
        $entry_info = $this->tar->offsetGet($fileName);
        return new ArchiveEntry($fileName, $entry_info->getSize(), filesize($entry_info->getPathname()),
            0, $entry_info->isCompressed());
    }

    /**
     * @inheritDoc
     */
    public function getFileContent($fileName)
    {
        return $this->tar->offsetGet($fileName)->getContent();
    }

    /**
     * @inheritDoc
     */
    public function getFileResource($fileName)
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $this->tar->offsetGet($fileName)->getContent());
        rewind($resource);
        return $resource;
    }

    /**
     * @inheritDoc
     */
    public function extractFiles($outputFolder, array $files)
    {
        $result = $this->tar->extractTo($outputFolder, $files, true);
        if ($result === false) {
            throw new ArchiveExtractionException('Error when extracting from '.$this->archiveFileName);
        }
        return count($files);
    }

    /**
     * @inheritDoc
     */
    public function extractArchive($outputFolder)
    {
        $result = $this->tar->extractTo($outputFolder, null, true);
        if ($result === false) {
            throw new ArchiveExtractionException('Error when extracting from '.$this->archiveFileName);
        }

        return 1;
    }

    /**
     * @inheritDoc
     */
    public function deleteFiles(array $files)
    {
        $deleted = 0;

        foreach ($files as $i => $file) {
            if ($this->tar->delete($file))
                $deleted++;
        }

        $this->tar = null;
        $this->open();

        return $deleted;
    }

    /**
     * @inheritDoc
     */
    public function addFiles(array $files)
    {
        $added = 0;
        try {
            foreach ($files as $localName => $filename) {
                if (is_null($filename)) {
                    $this->tar->addEmptyDir($localName);
                } else {
                    $this->tar->addFile($filename, $localName);
                    $added++;
                }
            }
        } catch (Exception $e) {
            throw new ArchiveModificationException('Could not add file "'.$filename.'": '.$e->getMessage(), $e->getCode());
        }
        $this->tar = null;
        // reopen to refresh files list properly
        $this->open();
        return $added;
    }

    /**
     * @param $format
     * @return bool
     */
    public static function canCreateArchive($format)
    {
        return true;
    }

    /**
     * @param $format
     * @return bool
     */
    public static function canAddFiles($format)
    {
        return true;
    }

    /**
     * @param $format
     * @return bool
     */
    public static function canDeleteFiles($format)
    {
        return true;
    }

    /**
     * @param array $files
     * @param string $archiveFileName
     * @param int $compressionLevel
     * @param null $password
     * @return int
     * @throws ArchiveCreationException
     */
    public static function createArchive(array $files, $archiveFileName, $compressionLevel = self::COMPRESSION_AVERAGE, $password = null)
    {
        if (preg_match('~^(.+)\.(tar\.(gz|bz2))$~i', $archiveFileName, $match)) {
            $ext = $match[2];
            $basename = $match[1];
        } else {
            $ext = pathinfo($archiveFileName, PATHINFO_EXTENSION);
            $basename = dirname($archiveFileName).'/'.basename($archiveFileName, '.'.$ext);
        }
        $tar = new PharData($basename.'.tar', 0, null, Phar::TAR);

        try {
            foreach ($files as $localName => $filename) {
                if (is_null($filename)) {
                    if (!in_array($localName, ['/', ''], true)) {
                        if ($tar->addEmptyDir($localName) === false) {
                            throw new ArchiveCreationException('Error when adding directory '.$localName.' to archive');
                        }
                    }
                } else {
                    if ($tar->addFile($filename, $localName) === false) {
                        throw new ArchiveCreationException('Error when adding file '.$localName.' to archive');
                    }
                }
            }
        } catch (Exception $e) {
            throw new ArchiveCreationException('Error when creating archive: '.$e->getMessage(), $e->getCode(), $e);
        }

        switch (strtolower(pathinfo($archiveFileName, PATHINFO_EXTENSION))) {
            case 'gz':
            case 'tgz':
                $tar->compress(Phar::GZ, $ext);
                break;
            case 'bz2':
            case 'tbz2':
                $tar->compress(Phar::BZ2, $ext);
                break;
        }
        $tar = null;

        return count($files);
    }

    /**
     * @param string $inArchiveName
     * @param string $content
     * @return bool
     */
    public function addFileFromString($inArchiveName, $content)
    {
        $this->tar->addFromString($inArchiveName, $content);
        return true;
    }
}