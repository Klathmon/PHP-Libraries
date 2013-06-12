<?php
/**
 * Created by Gregory Benner
 * Date: 6/09/2013
 */

namespace Klathmon;

/**
 * Class Compression
 *
 * My compression class, supports all the methods marked true in $supportedAlgorithms.
 *
 * @package Klathmon
 */
class Compression
{
    /**
     * @var bool[]
     */
    private static $supportedAlgorithms
        = array(
            'gzip'    => true,
            'deflate' => true,
            'bzip2'   => false
        );

    /**
     * @var string
     */
    /**
     * @var int
     */
    private $encoding, $level;

    /**
     * Sets up the encoding and the level used.
     *
     * @param string $algorithm
     * @param int    $level
     *
     * @throws \Exception 1: Given encoding method is not supported!
     */
    public function __construct($algorithm = 'gzip', $level = 6)
    {
        $algorithm = strtolower($algorithm);
        if (!isset(self::$supportedAlgorithms[$algorithm]) && !self::$supportedAlgorithms[$algorithm]) {
            throw new \Exception('Given encoding method is not supported!', 1);
        }

        $this->encoding = strtolower($algorithm);
        $this->level    = $level;
    }

    /**
     * Compresses the data.
     *
     * @param string $data
     *
     * @return string
     */
    public function compress($data)
    {
        switch ($this->encoding) {
        case 'deflate':
            $compressed = gzdeflate($data, $this->level);
            break;
        case 'gzip':
            $compressed = gzcompress($data, $this->level);
            break;
        case 'bzip2':
            $compressed = bzcompress($data, $this->level);
            break;
        }
        return $compressed;
    }

    /**
     * Decompresses the data.
     *
     * @param string $data
     *
     * @return string
     */
    public function decompress($data)
    {
        switch ($this->encoding) {
        case 'deflate':
            $decompressed = gzinflate($data);
            break;
        case 'gzip':
            $decompressed = gzuncompress($data);
            break;
        case 'bzip2':
            $decompressed = bzdecompress($data);
            break;
        }
        return $decompressed;
    }
}