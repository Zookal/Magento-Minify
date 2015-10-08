<?php

/**
 * @category    WBL_Minify
 * @package     Minify
 * @copyright   Copyright (c)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class WBL_Minify_Model_Design_Package extends Mage_Core_Model_Design_Package
{

    /**
     * Implemented a versioned directory
     * Make sure merger dir exists and writeable
     * Also can clean it up
     *
     * @param string $dirRelativeName
     * @param bool $cleanup
     *
     * @return bool
     */
    protected function _initMergerDir($dirRelativeName, $cleanup = false)
    {
        $version  = Mage::helper('core')->getStoreReleaseVersion(DS);
        $mediaDir = Mage::getBaseDir() . '/media/';
        if (Mage::getStoreConfigFlag('dev/minification/force_default_media_path')) {
            $mediaDir = Mage::getBaseDir() . DIRECTORY_SEPARATOR . 'media';
        } else {
            $mediaDir = Mage::getBaseDir('media');
        }
        try {
            $dir = $mediaDir . DS . $dirRelativeName . DS . $version;
            if ($cleanup) {
                Varien_Io_File::rmdirRecursive($dir);
                Mage::helper('core/file_storage_database')->deleteFolder($dir);
            }
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            return is_writeable($dir) ? $dir : false;
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return false;
    }

    /**
     * Implemented a versioned directory
     * Merge specified javascript files and return URL to the merged file on success
     *
     * @param $files
     *
     * @return string
     */
    public function getMergedJsUrl($files)
    {
        $version        = Mage::helper('core')->getStoreReleaseVersion(DS);
        $targetFilename = md5(implode(',', $files)) . '.js';
        $targetDir      = $this->_initMergerDir('js');
        if (!$targetDir) {
            return '';
        }
        if ($this->_mergeFiles($files, $targetDir . DS . $targetFilename, false, null, 'js')) {
            if (Mage::getStoreConfigFlag('dev/minification/force_default_media_path')) {
                return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, Mage::app()->getRequest()->isSecure()) . 'media/js/' . $version . '/' . $targetFilename;
            } else {
                return Mage::getBaseUrl('media', Mage::app()->getRequest()->isSecure()) . 'js/' . $version . '/' . $targetFilename;
            }
        }
        return '';
    }

    /**
     * Implemented a versioned directory
     * Merge specified css files and return URL to the merged file on success
     *
     * @param $files
     *
     * @return string
     */
    public function getMergedCssUrl($files)
    {
        $version = Mage::helper('core')->getStoreReleaseVersion(DS);
        // secure or unsecure
        $isSecure  = Mage::app()->getRequest()->isSecure();
        $mergerDir = $isSecure ? 'css_secure' : 'css';
        $targetDir = $this->_initMergerDir($mergerDir);
        if (!$targetDir) {
            return '';
        }

        // base hostname & port
        if (Mage::getStoreConfigFlag('dev/minification/force_default_media_path')) {
            $baseMediaUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, $isSecure) . 'media/';
        } else {
            $baseMediaUrl = Mage::getBaseUrl('media', $isSecure);
        }
        $hostname = parse_url($baseMediaUrl, PHP_URL_HOST);
        $port     = parse_url($baseMediaUrl, PHP_URL_PORT);
        if (false === $port) {
            $port = $isSecure ? 443 : 80;
        }

        // merge into target file
        $targetFilename   = md5(implode(',', $files) . "|{$hostname}|{$port}") . '.css';
        $mergeFilesResult = $this->_mergeFiles(
            $files, $targetDir . DS . $targetFilename,
            false,
            [$this, 'beforeMergeCss'],
            'css'
        );
        if ($mergeFilesResult) {
            return $baseMediaUrl . $mergerDir . '/' . $version . '/' . $targetFilename;
        }
        return '';
    }
}
