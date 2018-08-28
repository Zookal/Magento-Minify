<?php
/**
 * @category    WBL_Minify
 * @package     Minify
 * @copyright   Copyright (c)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Compress Javascript/CSS using the YUI Compressor
 *
 * You must set $jarFile and $tempDir before calling the minify functions.
 * Also, depending on your shell's environment, you may need to specify
 * the full path to java in $javaExecutable or use putenv() to setup the
 * Java environment.
 *
 * <code>
 * Minify_YUICompressor::$jarFile = '/path/to/yuicompressor-2.3.5.jar';
 * Minify_YUICompressor::$tempDir = '/tmp';
 * $code = Minify_YUICompressor::minifyJs(
 *   $code
 *   ,array('nomunge' => true, 'line-break' => 1000)
 * );
 * </code>
 *
 * @todo    unit tests, $options docs
 *
 * @package Minify
 * @author  Stephen Clay <steve@mrclay.org>
 */
class Minify_YUICompressor
{

    /**
     * Filepath of the YUI Compressor jar file. This must be set before
     * calling minifyJs() or minifyCss().
     *
     * @var string
     */
    protected static $_jarFile = null;

    /**
     * Writable temp directory. This must be set before calling minifyJs()
     * or minifyCss().
     *
     * @var string
     */
    protected static $_tempDir = null;

    /**
     * Filepath of "java" executable (may be needed if not in shell's PATH)
     *
     * @var string
     */
    public static $javaExecutable = 'java';

    /**
     * Contains the shell command for yui for debugging purposes
     *
     * @var string
     */
    public static $yuiCommand = '';

    /**
     * @param string $tempDir
     */
    public static function setTempDir($tempDir = null)
    {
        self::$_tempDir = null === $tempDir ? realpath(sys_get_temp_dir()) : $tempDir;
    }

    /**
     * @param string $baseDir
     */
    public static function setBaseDir($baseDir)
    {
        self::$_jarFile = $baseDir . DS . 'lib' . DS . 'yuicompressor' . DS . 'yuicompressor.jar';
    }

    /**
     * @return string
     */
    public static function getJarFile()
    {
        return self::$_jarFile;
    }

    /**
     * Minify a Javascript string
     *
     * @param string $js
     *
     * @param array  $options (verbose is ignored)
     *
     * @see http://www.julienlecomte.net/yuicompressor/README
     *
     * @return string
     */
    public static function minifyJs($js, $options = array())
    {
        return self::_minify('js', $js, $options);
    }

    /**
     * Minify a CSS string
     *
     * @param string $css
     *
     * @param array  $options (verbose is ignored)
     *
     * @see http://www.julienlecomte.net/yuicompressor/README
     *
     * @return string
     */
    public static function minifyCss($css, $options = array())
    {
        return self::_minify('css', $css, $options);
    }

    /**
     * @param $type
     * @param $content
     * @param $options
     *
     * @return string
     * @throws Exception
     */
    protected static function _minify($type, $content, $options)
    {
        self::_prepare();
        if (!($tmpFile = tempnam(self::$_tempDir, 'yuic_'))) {
            throw new Exception('Minify_YUICompressor : could not create temp file.');
        }
        file_put_contents($tmpFile, $content);
        self::$yuiCommand = self::_getCmd($options, $type, $tmpFile);

        $result_code = 0;
        $output      = array();
        exec(self::$yuiCommand, $output, $result_code);
        unlink($tmpFile);
        if ((int)$result_code !== 0) {
            throw new Exception('Minify_YUICompressor : YUI compressor execution failed.' . "\n" . 'result code: ' . $result_code . "\n" . 'Yui command: ' . self::$yuiCommand);
        }
        return implode("\n", $output);
    }

    /**
     * @param $userOptions
     * @param $type
     * @param $tmpFile
     *
     * @return string
     */
    protected static function _getCmd($userOptions, $type, $tmpFile)
    {
        $o   = array_merge(
            array(
                'charset'             => ''
            , 'line-break'            => 3000
            , 'type'                  => $type
            , 'nomunge'               => false
            , 'preserve-semi'         => false
            , 'disable-optimizations' => false
            )
            , $userOptions
        );
        $cmd = self::$javaExecutable . ' -jar ' . escapeshellarg(self::$_jarFile)
            . ' --type ' . $type
            . (preg_match('/^[\\da-zA-Z0-9\\-]+$/', $o['charset'])
                ? " --charset {$o['charset']}"
                : '')
            . (is_numeric($o['line-break']) && $o['line-break'] >= 0
                ? ' --line-break ' . (int)$o['line-break']
                : '');
        if ($type === 'js') {
            foreach (array('nomunge', 'preserve-semi', 'disable-optimizations') as $opt) {
                $cmd .= empty($o[$opt]) === false
                    ? ' --' . $opt
                    : '';
            }
        }
        return $cmd . ' ' . escapeshellarg($tmpFile);
    }

    protected static function _prepare()
    {
        if (!is_link(self::$_jarFile)) {
            throw new Exception('Minify_YUICompressor : $jarFile(' . self::$_jarFile . ') is not a valid link.');
        }
        if (!is_dir(self::$_tempDir)) {
            throw new Exception('Minify_YUICompressor : $tempDir(' . self::$_tempDir . ') is not a valid directory.');
        }
        if (!is_writable(self::$_tempDir)) {
            throw new Exception('Minify_YUICompressor : $tempDir(' . self::$_tempDir . ') is not writable.');
        }
    }
}

