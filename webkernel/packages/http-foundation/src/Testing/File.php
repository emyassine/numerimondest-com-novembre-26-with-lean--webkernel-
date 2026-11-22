<?php declare(strict_types=1);
//> This file is part of Webkernel.
//> (c) 2025 - 2027 Numerimondes, El Moumen Yassine
//> Yassine El Moumen <yassine@numerimondes.com> | <platform@webkernelphp.com>
//> For the full copyright and license information, please view the LICENSE
//> file that was distributed with this source code.

namespace Webkernel\HttpFoundation\Testing;

use \Webkernel\HttpFoundation\UploadedFile;

class File extends UploadedFile
{
    /**
     * The name of the file.
     *
     * @var string
     */
    public $name;

    /**
     * The temporary file resource.
     *
     * @var resource
     */
    public $tempFile;

    /**
     * The "size" to report.
     *
     * @var int
     */
    public $sizeToReport;

    /**
     * The MIME type to report.
     *
     * @var string|null
     */
    public $mimeTypeToReport;

    /**
     * Create a new file instance.
     *
     * @param  string  $name
     * @param  resource  $tempFile
     */
    public function __construct($name, $tempFile)
    {
        $this->name = $name;
        $this->tempFile = $tempFile;

        parent::__construct(
            $this->tempFilePath(), $name, $this->getMimeType(),
            null, true
        );
    }

    /**
     * Create a new fake file.
     *
     * @param  string  $name
     * @param  string|int  $kilobytes
     * @return \Webkernel\HttpFoundation\Testing\File
     */
    public static function create($name, $kilobytes = 0)
    {
        return (new FileFactory)->create($name, $kilobytes);
    }

    /**
     * Create a new fake file with content.
     *
     * @param  string  $name
     * @param  string  $content
     * @return \Webkernel\HttpFoundation\Testing\File
     */
    public static function createWithContent($name, $content)
    {
        return (new FileFactory)->createWithContent($name, $content);
    }

    /**
     * Create a new fake image.
     *
     * @param  string  $name
     * @param  int  $width
     * @param  int  $height
     * @return \Webkernel\HttpFoundation\Testing\File
     */
    public static function image($name, $width = 10, $height = 10)
    {
        return (new FileFactory)->image($name, $width, $height);
    }

    /**
     * Set the "size" of the file in kilobytes.
     *
     * @param  int  $kilobytes
     * @return $this
     */
    public function size($kilobytes)
    {
        $this->sizeToReport = $kilobytes * 1024;

        return $this;
    }

    /**
     * Get the size of the file.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->sizeToReport ?: parent::getSize();
    }

    /**
     * Set the MIME type for the file.
     *
     * @param  string  $mimeType
     * @return $this
     */
    public function mimeType($mimeType)
    {
        $this->mimeTypeToReport = $mimeType;

        return $this;
    }

    /**
     * Get the MIME type of the file.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeTypeToReport ?: MimeType::from($this->name);
    }

    /**
     * Get the path to the temporary file.
     *
     * @return string
     */
    protected function tempFilePath()
    {
        return stream_get_meta_data($this->tempFile)['uri'];
    }
}
