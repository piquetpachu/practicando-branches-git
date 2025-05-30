<?php

declare(strict_types=1);

namespace flight\net;

use Exception;

class UploadedFile
{
    /**
     * @var string $name The name of the uploaded file.
     */
    private string $name;

    /**
     * @var string $mimeType The MIME type of the uploaded file.
     */
    private string $mimeType;

    /**
     * @var int $size The size of the uploaded file in bytes.
     */
    private int $size;

    /**
     * @var string $tmpName The temporary name of the uploaded file.
     */
    private string $tmpName;

    /**
     * @var int $error The error code associated with the uploaded file.
     */
    private int $error;

    /**
     * Initializes a new instance representing an uploaded file.
     *
     * @param string $name Original filename as provided by the client.
     * @param string $mimeType MIME type as provided by the client.
     * @param int $size Size of the uploaded file in bytes.
     * @param string $tmpName Temporary filename assigned by PHP.
     * @param int $error PHP upload error code.
     */
    public function __construct(string $name, string $mimeType, int $size, string $tmpName, int $error)
    {
        $this->name = $name;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->tmpName = $tmpName;
        $this->error = $error;
    }

    /**
     * Returns the original filename provided by the client for the uploaded file.
     *
     * @return string The original client-side filename.
     */
    public function getClientFilename(): string
    {
        return $this->name;
    }

    /**
     * Returns the MIME type of the uploaded file as reported by the client.
     *
     * @return string Client-provided MIME type of the uploaded file.
     */
    public function getClientMediaType(): string
    {
        return $this->mimeType;
    }

    /****
     * Retrieves the size of the uploaded file in bytes.
     *
     * @return int File size in bytes.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Returns the temporary filename assigned to the uploaded file by the server.
     *
     * @return string Temporary file path on the server.
     */
    public function getTempName(): string
    {
        return $this->tmpName;
    }

    /****
     * Returns the PHP upload error code for the uploaded file.
     *
     * @return int The PHP file upload error code constant.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Moves the uploaded file to a specified target path.
     *
     * Throws an Exception if the upload encountered an error or if the file cannot be moved.
     *
     * @param string $targetPath Destination path for the uploaded file.
     * @throws Exception If the upload failed or the file cannot be moved.
     */
    public function moveTo(string $targetPath): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($this->error));
        }

        $isUploadedFile = is_uploaded_file($this->tmpName) === true;
        if (
            $isUploadedFile === true
            &&
            move_uploaded_file($this->tmpName, $targetPath) === false
        ) {
            throw new Exception('Cannot move uploaded file'); // @codeCoverageIgnore
        } elseif ($isUploadedFile === false && getenv('PHPUNIT_TEST')) {
            rename($this->tmpName, $targetPath);
        }
    }

    /**
     * Returns a human-readable message for a given PHP file upload error code.
     *
     * @param int $error The PHP upload error code.
     * @return string The corresponding error message.
     */
    protected function getUploadErrorMessage(int $error): string
    {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'An unknown error occurred. Error code: ' . $error;
        }
    }
}
