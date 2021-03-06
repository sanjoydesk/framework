<?php

namespace Cygnite\Common;

use ZipArchive;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class Zip
{
    private $zip;

    private $path = '';

    public function __construct()
    {
        if (!extension_loaded('zip')) {
            throw new \Exception('Zip extension not loaded !');
        }

        $this->zip = new ZipArchive();
    }

    private function open_zip_archive($filepath)
    {
        $this->path = $filepath;
        if ($this->zip->open($filepath, ZipArchive::CREATE) == false) {
            throw new \Exception("Cannot open $filepath \n ");
        }
    }

    /**
     * Prevent cloning.
     */
    final private function __clone()
    {
    }

    public function make($filename, $pathLocation, $new_location = '', $zip_name = '')
    {
        $this->open_zip_archive($pathLocation.$filename);

        $dir_handler = opendir($pathLocation);

        if (true == $dir_handler) {
            $file = readdir($dir_handler);

            while (true == ($file = readdir($dir_handler))) {
                if (is_file($pathLocation.$file)) {
                    $this->add_file($pathLocation.$filename, $new_location.$file);
                } else {
                    if ($file != '.' && $file != '..' and is_dir($pathLocation.$file)) {
                        $this->add_dir($new_location.$file.DS);
                        $this->make_zip($pathLocation.$file.DS, $new_location.$file.DS);
                    }
                }
            }
        }
        closedir($dir_handler);
        $this->close_zip_archive(); // echo $file;exit;
        $this->makeZip($zip_name, $pathLocation.$file);
    }

    /**
     * adds a file to .zip file.
     *
     * @false string $path_to_file the path on the hard drive where the file exists
     * @false string $put_into_dir the name of the directory inside the .zip file to put the file into
     */
    private function add_file($put_into_dir, $path_to_file)
    {
        $this->zip->addFromString($put_into_dir, file_get_contents($path_to_file));
    }

    /**
     * This function is used to add a directory inside of .zip file to put files into.
     *
     * @false string $dir_name the name of the directory to add, can be created nested directories as well like
     *        dir1/dir2/dir3
     */
    private function add_dir($dir_name)
    {
        $this->zip->addEmptyDir($dir_name);
    }

    private function makeZip($zip_name, $file_path)
    {
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        // Security checks
        if (!file_exists($zip_name) || $zip_name == '') {
            throw new Exception('The zip archive file not specified to download.');
        }

        $downloader = new \Cygnite\Common\File\File();
        $downloader->download($file_path);

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename='.basename($zip_name).';');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($zip_name));
        readfile("$zip_name");
    }

    protected function parseDirectory($rootPath, $separator = '/')
    {
        $fileArray = [];
        $handle = opendir($rootPath);
        while (($file = @readdir($handle)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($rootPath.$separator.$file)) {
                    $array = $this->parseDirectory($rootPath.$separator.$file);
                    $fileArray = array_merge($array, $fileArray);
                } else {
                    $fileArray[] = $rootPath.$separator.$file;
                }
            }
        }

        return $fileArray;
    }

    /**
     * Function to Zip entire directory with all its files and subdirectories.
     *
     * @false  string $dirName
     *
     * @param $dirName
     * @param $outputDir
     *
     * @return void
     */
    public function zipDirectory($dirName, $outputDir)
    {
        if (!is_dir($dirName)) {
            trigger_error("CreateZipFile FATAL ERROR: Could not locate the specified directory $dirName", E_USER_ERROR);
        }
        $tmp = $this->parseDirectory($dirName);
        $count = count($tmp);
        $this->addDirectory($outputDir);
        for ($i = 0; $i < $count; $i++) {
            $fileToZip = trim($tmp[$i]);
            $newOutputDir = substr($fileToZip, 0, (strrpos($fileToZip, '/') + 1));
            $outputDir = $outputDir.$newOutputDir;
            $fileContents = file_get_contents($fileToZip);
            $this->addFile($fileContents, $fileToZip);
        }
    }

    private function close_zip_archive()
    {
        $this->zip->close();
    }

    public function __destruct()
    {
        $this->zip->close();
        unset($this->zip);
    }
}
