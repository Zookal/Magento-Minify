<?php

class WBL_Minify_Model_Design_Package extends Mage_Core_Model_Design_Package
{

    /**
     * Make sure merger dir exists and writeable
     * Also can clean it up
     *
     * @param string $dirRelativeName
     * @param bool   $cleanup
     *
     * @return bool
     */
    protected function _initMergerDir($dirRelativeName, $cleanup = false)
    {
        $version  = Mage::helper('core')->getStoreReleaseVersion();
        $mediaDir = Mage::getBaseDir('media');
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
}
