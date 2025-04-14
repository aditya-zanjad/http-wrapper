<?php

declare(strict_types=1);

namespace AdityaZanjad\Http\Utils;

use Exception;
use Throwable;

/**
 * @version 1.0
 */
class File
{
    /**
     * @var null|string|resource $file
     */
    protected $file;

    /**
     * @var bool $alreadyOpened
     */
    protected bool $alreadyOpened;

    /**
     * @var array<string, mixed> $metadata
     */
    protected array $metadata;

    /**
     * @var null|string $error
     */
    protected $error;

    /**
     * @param string|resource $file
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    /**
     * Open the file. If something goes wrong, throw an exception.
     *
     * @param string $mode
     *
     * @return static
     */
    public function open(string $mode = 'read'): static
    {
        if ($this->alreadyOpened) {
            return $this;
        }

        $file = null;

        if (file_exists($this->file)) {
            try {
                $file = fopen($this->file, $this->decideOpenMode($mode));
            } catch (Throwable $e) {
                // dd($e);
                $this->error = 'The function "fopen()" failed to open the file.';
                return $this;
            }
        }

        // If opening the file still failed due to some reason(s).
        if ($file === false) {
            $this->error = 'The function "fopen()" returned the boolean value "false".';
            return $this;
        }

        if (is_null($file)) {
            $file = $this->file;
        }

        if (!is_resource($file)) {
            $this->error = 'The file must be either a valid file path OR a resource.';
            return $this;
        }

        $metadata = stream_get_meta_data($file);

        // Make sure that the current resource is a valid file.
        if ($metadata['wrapper_type'] !== 'plain_file') {
            $this->error = 'The given resource type is not a valid file.';
            return $this;
        }

        $this->file             =   $file;
        $this->error            =   null;
        $this->metadata         =   $metadata;
        $this->alreadyOpened    =   true;

        return $this;
    }

    /**
     * Figure out which mode to apply when opening the file. For example, read-only, read & write etc.
     *
     * For example, read-only, read and write, clean file & write it from the beginning etc.
     *
     * @param string $givenMode
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function decideOpenMode(string $givenMode): string
    {
        $validModes = [
            'read'              =>  'r',
            'read|write'        =>  'r+',
            'write'             =>  'w',
            'write|read'        =>  'w+',
            'append|write'      =>  'a',
            'append|read|write' =>  'a+',
            'create|write'      =>  'x',
            'create|read|write' =>  'x+',
            'lock|write'        =>  'c',
            'lock|read|write'   =>  'c+'
        ];

        if (in_array($givenMode, $validModes, true)) {
            return $givenMode;
        }

        if (!array_key_exists($givenMode, $validModes)) {
            throw new Exception("The given file operation mode [{$givenMode}] is invalid.");
        }

        return $validModes[$givenMode];
    }

    /**
     * Close the file.
     *
     * @return static
     */
    public function close(): static
    {
        if (!$this->alreadyOpened) {
            return $this;
        }

        fclose($this->file);

        $this->file             =   $this->metadata['uri'];
        $this->metadata         =   [];
        $this->alreadyOpened    =   false;

        return $this;
    }

    /**
     * Check if the file is currently opened or not.
     *
     * @return bool
     */
    public function isOpened(): bool
    {
        return $this->alreadyOpened;
    }

    /**
     * Check if the file is currently closed or not.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return !$this->alreadyOpened;
    }

    /**
     * Get all the necessary metadata about the current file.
     *
     * @return null|array<string, mixed>
     */
    public function metadata()
    {
        if ($this->alreadyOpened) {
            return [
                'name'  =>  basename($this->metadata['uri']),
                'path'  =>  $this->metadata['uri'],
                'size'  =>  filesize($this->metadata['uri']),
                'mime'  =>  mime_content_type($this->file),
            ];
        }

        $metadata = stat($this->file);

        if ($metadata === false) {
            return null;
        }

        return [
            'name'  =>  basename($this->file),
            'path'  =>  $this->file,
            'size'  =>  $metadata['size'],
            'mime'  =>  $this->file
        ];
    }

    /**
     * Get name of the file.
     *
     * @return null|string
     */
    public function name()
    {
        return basename($this->path());
    }

    /**
     * Get a full path to the file.
     *
     * @throws \Exception
     *
     * @return null|string
     */
    public function path()
    {
        if (!$this->alreadyOpened) {
            return $this->file;
        }

        if (!isset($this->metadata['uri'])) {
            throw new Exception("Unable to obtain the path information of the file.");
        }

        return $this->metadata['uri'];
    }

    /**
     * Get MIME type of the file.
     *
     * @return null|string
     */
    public function mime()
    {
        $path = $this->alreadyOpened ? $this->path() : $this->file;
        return mime_content_type($path);
    }

    /**
     * Get the extension of a file.
     *
     * @return null|string
     */
    public function extension()
    {
        $path       =   $this->alreadyOpened ? $this->metadata['uri'] : $this->file;
        $extension  =   pathinfo($path, PATHINFO_EXTENSION);

        if (!$extension) {
            return null;
        }

        return $extension;
    }

    /**
     * Get size of the file in the specified unit.
     *
     * @param string $unit
     *
     * @throws \Exception
     *
     * @return int
     */
    public function size(string $unit = 'B'): int
    {
        $path   =   $this->alreadyOpened ? $this->metadata['uri'] : $this->file;
        $size   =   filesize($path);

        if ($size === false) {
            throw new Exception("An unknown issue occurred when tried to calculate the file size.");
        }

        switch ($unit) {
            // Bits
            // 1 Byte = 8 Bits
            case 'b':
                $size = $size * 8;
                break;

                // Bytes
            case 'B':
                // No Action
                break;

                // Kilo-Bytes
                // 1 KB = 1024 Bytes;
            case 'KB':
                $size = (int) number_format($size / 1024, 2);
                break;

                // Mega-Bytes
                // 1 MB = 1024 KB = 1,048,576 Bytes
            case 'MB':
                $size = (int) number_format($size / 1048576, 2);
                break;

                // Giga-Bytes
                // 1 GB = 1024MB = 1,048,576 KB = 1,073,741,824 Bytes
            case 'GB':
                $size = (int) number_format($size / 1073741824, 2);
                break;

                // Tera-Bytes
                // 1 TB = 1024 GB = 1,048,576 MB = 8,388,608 KB = 1,099,511,627,776 Bytes
            case 'TB':
                $size = (int) number_format($size / 1099511627776, 2);
                break;

                // Peta-Bytes
                // 1 PB = 1024 TB = 1,048,576 GB = 1,073,741,824 MB = 1,099,511,627,776 KB = 1,125,899,906,842,624 Bytes
            case 'PB':
                $size = (int) number_format($size / 1125899906842624, 2);
                break;

                // Exa-Bytes
                // 1 EB = 1024 PB = 1,048,576 TB = 1,073,741,824 GB = 1,099,511,627,776 MB = 1,125,899,906,842,624 KB = 1,152,921,504,606,846,976 Bytes
            case 'EB':
                $size = (int) number_format($size / 1152921504606846976, 2);
                break;

                // Zeta-Bytes
                // 1 ZB = 1024 EB = 1,048,576 PB = 1,073,741,824 TB = 1,099,511,627,776 GB = 1,125,899,906,842,624 MB = 1,152,921,504,606,846,976 KB = 1,180,591,620,717,411,303,424 Bytes
            case 'ZB':
                $size = (int) number_format($size / 1180591620717411303424, 2);
                break;

                // Yotta-Bytes
                // 1 YB = 1024 ZB = 1,048,576 EB = 1,073,741,824 PB = 1,099,511,627,776 TB = 1,125,899,906,842,624 GB = 1,152,921,504,606,846,976 MB = 1,180,591,620,717,411,303,424 KB = 1,208,925,819,614,629,174,706,176 Bytes
            case 'YB':
                $size = (int) number_format($size / 1208925819614629174706176, 2);
                break;

            default:
                throw new Exception("[Developer][Exception]: The given file size unit [{$unit}] is invalid.");
        }

        return $size;
    }

    /**
     * Get contents of the file.
     *
     * @return string
     */
    public function contents()
    {
        return $this->alreadyOpened ? fread($this->file, $this->size()) : file_get_contents($this->path());
    }
}
