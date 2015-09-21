<?php

namespace Woland;

class Cache
{
    /// @var string where to store the data.
    private $dir;

    /// @param string $dir @see $this->dir
    public function __construct($dir)
    {
        $this->dir = $dir;
        $this->setupDir($this->dir);
    }

    /**
     * Create dir if needed.
     *
     * @param string $dir path
     */
    private function setupDir($dir)
    {
        if (file_exists($dir)) {
            if (!is_dir($dir) || !is_writable($dir)) {
                throw new \RuntimeException("Can't write to dir `$dir`.");
            }
        } else {
            if (mkdir($dir, 0755, true) !== true) {
                throw new \RuntimeException("Unable to create dir `$dir`.");
            }
        }
    }

    /// @var string $id
    public function get($id)
    {
        $path = $this->getPath($id);
        if (file_exists($path)) {
            if (!is_readable($path)) {
                throw new \RuntimeException("Can't read `$path` for id `$id`.");
            }
            return file_get_contents($path);
        } else {
            return null;
        }
    }

    /**
     * @var string id
     * @var string data
     */
    public function set($id, $data)
    {
        assert('is_string($data)');
        $path = $this->getPath($id);
        $this->setupDir(dirname($path));
        file_put_contents($path, $data);
    }

    /**
     * @param string id
     * @return string
     */
    private function getPath($id)
    {
        assert('is_string($id)');
        $hash = sha1($id);
        $subdir = substr($hash, 0, 2);
        $name = substr($hash, 2);

        return $this->dir . "/$subdir/$name";
    }
}
