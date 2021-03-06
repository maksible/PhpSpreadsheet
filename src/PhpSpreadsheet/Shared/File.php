<?php

namespace PhpOffice\PhpSpreadsheet\Shared;

use InvalidArgumentException;
use ZipArchive;

class File
{
    /**
     * Use Temp or File Upload Temp for temporary files.
     *
     * @var bool
     */
    protected static $useUploadTempDirectory = false;

    /**
     * Set the flag indicating whether the File Upload Temp directory should be used for temporary files.
     *
     * @param bool $useUploadTempDir Use File Upload Temporary directory (true or false)
     */
    public static function setUseUploadTempDirectory($useUploadTempDir)
    {
        self::$useUploadTempDirectory = (bool) $useUploadTempDir;
    }

    /**
     * Get the flag indicating whether the File Upload Temp directory should be used for temporary files.
     *
     * @return bool Use File Upload Temporary directory (true or false)
     */
    public static function getUseUploadTempDirectory()
    {
        return self::$useUploadTempDirectory;
    }

    /**
     * Verify if a file exists.
     *
     * @param string $pFilename Filename
     *
     * @return bool
     */
    public static function fileExists($pFilename)
    {
        // Sick construction, but it seems that
        // file_exists returns strange values when
        // doing the original file_exists on ZIP archives...
        if (strtolower(substr($pFilename, 0, 3)) == 'zip') {
            // Open ZIP file and verify if the file exists
            $zipFile = substr($pFilename, 6, strpos($pFilename, '#') - 6);
            $archiveFile = substr($pFilename, strpos($pFilename, '#') + 1);

            $zip = new ZipArchive();
            if ($zip->open($zipFile) === true) {
                $returnValue = ($zip->getFromName($archiveFile) !== false);
                $zip->close();

                return $returnValue;
            }

            return false;
        }

        return file_exists($pFilename);
    }

    /**
     * Returns canonicalized absolute pathname, also for ZIP archives.
     *
     * @param string $pFilename
     *
     * @return string
     */
    public static function realpath($pFilename)
    {
        // Returnvalue
        $returnValue = '';

        // Try using realpath()
        if (file_exists($pFilename)) {
            $returnValue = realpath($pFilename);
        }

        // Found something?
        if ($returnValue == '' || ($returnValue === null)) {
            $pathArray = explode('/', $pFilename);
            while (in_array('..', $pathArray) && $pathArray[0] != '..') {
                $iMax = count($pathArray);
                for ($i = 0; $i < $iMax; ++$i) {
                    if ($pathArray[$i] == '..' && $i > 0) {
                        unset($pathArray[$i], $pathArray[$i - 1]);

                        break;
                    }
                }
            }
            $returnValue = implode('/', $pathArray);
        }

        // Return
        return $returnValue;
    }

    /**
     * Get the systems temporary directory.
     *
     * @return string
     */
    public static function sysGetTempDir()
    {
        if (self::$useUploadTempDirectory) {
            //  use upload-directory when defined to allow running on environments having very restricted
            //      open_basedir configs
            if (ini_get('upload_tmp_dir') !== false) {
                if ($temp = ini_get('upload_tmp_dir')) {
                    if (file_exists($temp)) {
                        return realpath($temp);
                    }
                }
            }
        }

        return realpath(sys_get_temp_dir());
    }

    /**
     * Assert that given path is an existing file and is readable, otherwise throw exception.
     *
     * @param string $filename
     *
     * @throws InvalidArgumentException
     */
    public static function assertFile($filename)
    {
        if (!is_file($filename)) {
            throw new InvalidArgumentException('File "' . $filename . '" does not exist.');
        }

        if (!is_readable($filename)) {
            throw new InvalidArgumentException('Could not open "' . $filename . '" for reading.');
        }
    }

    /**
     * Deletes given file if its exists with proper permissions and is NOT BEING USED BY SOME APPLICATIONS.
     *
     * @param string $file
     *
     * @throws InvalidArgumentException
     */
    public static function delete($file)
    {
        if (file_exists($file)) {
            // '@before unlink' will stop displaying "Resource Unavailable" error because of file is open some where.
            // 'unlink($file) !== true' will check if file is deleted successfully.
            // throws Exception so that we can handle Error easily instead of displaying to users.
            if (@unlink($file) !== true) {
                throw new InvalidArgumentException('Could not delete file: ' . $file . ' Please check for file permissions and make sure  file is not being used by some applications.');
            }
        }
    }
}
