<?php

/**
 * @category    WBL_Minify
 * @package     Minify
 * @copyright   Copyright (c)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class WBL_Minify_Helper_Core_Data extends Mage_Core_Helper_Data
{
    const XML_PATH_MINIFY_ENABLE_YUICOMPRESSOR = 'dev/minification/enable_yuicompressor';
    const XML_PATH_MINIFY_VERSION_PATH         = 'dev/minification/version_config_path';

    private $_logEnabled = false;

    public function __construct()
    {
        $this->_logEnabled = Mage::getStoreConfigFlag('dev/minification/enable_log');
    }

    /**
     * @return bool
     */
    public function isYUICompressEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_MINIFY_ENABLE_YUICOMPRESSOR);
    }

    public function logException(Exception $e)
    {
        if ($this->_logEnabled) {
            Mage::logException($e);
        }
    }

    /**
     * @param $directorySeparator
     *
     * @return int|string
     */
    public function getStoreReleaseVersion($directorySeparator = null)
    {
        $directorySeparator = null === $directorySeparator ? DS : $directorySeparator;
        $configVersionPath  = trim(Mage::getStoreConfig(self::XML_PATH_MINIFY_VERSION_PATH));
        if (false === strpos($configVersionPath, '/')) {
            return (empty($configVersionPath) ? '' : $configVersionPath . $directorySeparator) . $this->getStoreId();
        }
        return Mage::getStoreConfig($configVersionPath) . $directorySeparator . $this->getStoreId();
    }

    /**
     *
     */
    protected function _initYUICompressor()
    {
        if ($this->isYUICompressEnabled()) {
            Minify_YUICompressor::setBaseDir(Mage::getBaseDir());
            Minify_YUICompressor::setTempDir();
        }
    }

    /**
     * @param string $data
     * @param string $fileName
     *
     * @return string
     */
    public function minifyJsCss($data, $fileName)
    {
        $this->_initYUICompressor();
        $YUICompressorFailed = false;
        switch (pathinfo($fileName, PATHINFO_EXTENSION)) {
            case 'js':
                if ($this->isYUICompressEnabled()) {
                    try {
                        Varien_Profiler::start('Minify_YUICompressor::minifyJs');
                        $data = Minify_YUICompressor::minifyJs($data);
                        Varien_Profiler::stop('Minify_YUICompressor::minifyJs');
                        $YUICompressorFailed = false;
                    } catch (Exception $e) {
                        // Mage::log(Minify_YUICompressor::$yuiCommand);
                        $this->logException($e);
                        $YUICompressorFailed = true;
                    }
                }
                /**
                 * refactor and use https://github.com/tedivm/JShrink
                 */
                if (!$this->isYUICompressEnabled() || $YUICompressorFailed === true) {
                    Varien_Profiler::start('Minify_JSMin::minify');
                    $data = Minify_JSMin::minify($data);
                    Varien_Profiler::stop('Minify_JSMin::minify');
                }
                break;

            case 'css':
                if ($this->isYUICompressEnabled()) {
                    try {
                        Varien_Profiler::start('Minify_YUICompressor::minifyCss');
                        $data = Minify_YUICompressor::minifyCss($data);
                        Varien_Profiler::stop('Minify_YUICompressor::minifyCss');
                        $YUICompressorFailed = false;
                    } catch (Exception $e) {
                        // Mage::log(Minify_YUICompressor::$yuiCommand);
                        $this->logException($e);
                        $YUICompressorFailed = true;
                    }
                }

                if (!$this->isYUICompressEnabled() || $YUICompressorFailed === true) {
                    Varien_Profiler::start('Minify_Css_Compressor::process');
                    $data = Minify_Css_Compressor::process($data);
                    Varien_Profiler::stop('Minify_Css_Compressor::process');
                }
                break;

            default:
                return false;
        }

        return $data;
    }

    /**
     *
     * Merge specified files into one
     *
     * By default will not merge, if there is already merged file exists and it
     * was modified after its components
     * If target file is specified, will attempt to write merged contents into it,
     * otherwise will return merged content
     * May apply callback to each file contents. Callback gets parameters:
     * (<existing system filename>, <file contents>)
     * May filter files by specified extension(s)
     * Returns false on error
     *
     * @param array        $srcFiles
     * @param string|bool  $targetFile - file path to be written
     * @param bool         $mustMerge
     * @param callback     $beforeMergeCallback
     * @param array|string $extensionsFilter
     *
     * @throws Exception
     * @return bool|string
     */
    public function mergeFiles(
        array $srcFiles,
        $targetFile = false,
        $mustMerge = false,
        $beforeMergeCallback = null,
        $extensionsFilter = []
    )
    {
        try {
            // check whether merger is required
            $shouldMerge = $mustMerge || !$targetFile;
            if (!$shouldMerge) {
                if (!file_exists($targetFile)) {
                    $shouldMerge = true;
                } else {
                    $targetMtime = filemtime($targetFile);
                    foreach ($srcFiles as $file) {
                        if (!file_exists($file) || @filemtime($file) > $targetMtime) {
                            $shouldMerge = true;
                            break;
                        }
                    }
                }
            }

            // no need to merge
            if (false === $shouldMerge) {
                return true;
            }

            // merge contents into the file
            if ($targetFile && !is_writeable(dirname($targetFile))) {
                // no translation intentionally
                throw new Exception(sprintf('Path %s is not writeable.', dirname($targetFile)));
            }

            // filter by extensions
            if ($extensionsFilter) {
                if ($extensionsFilter == 'css') {
                    $extensionsFilter = ['css'];
                }
                if (!is_array($extensionsFilter)) {
                    $extensionsFilter = [$extensionsFilter];
                }
                if (!empty($srcFiles)) {
                    foreach ($srcFiles as $key => $file) {
                        $fileExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (!in_array($fileExt, $extensionsFilter)) {
                            unset($srcFiles[$key]);
                        }
                    }
                }
            }
            if (empty($srcFiles)) {
                // no translation intentionally
                throw new Exception('No files to compile.');
            }

            $data = '';
            foreach ($srcFiles as $file) {
                if (!file_exists($file)) {
                    continue;
                }
                $contents = file_get_contents($file) . "\n";
                if ($beforeMergeCallback && is_callable($beforeMergeCallback)) {
                    $contents = call_user_func($beforeMergeCallback, $file, $contents);
                }
                $data .= $contents;
            }
            if (!$data) {
                // no translation intentionally
                throw new Exception(sprintf("No content found in files:\n%s", implode("\n", $srcFiles)));
            }
            if ($targetFile) {

                //only the following line has been added for WBL_Minify
                if (Mage::getStoreConfigFlag('dev/minification/enable_minification')){
                    $data = $this->minifyJsCss($data, $targetFile);
                }

                file_put_contents($targetFile, $data, LOCK_EX);
            } else {
                return $data; // no need to write to file, just return data
            }

            return true; // no need in merger or merged into file successfully
        } catch (Exception $e) {
            $this->logException($e);
        }
        return false;
    }
}
