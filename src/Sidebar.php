<?php

namespace Woland;

class Sidebar
{
    /// @var RequestedPath
    private $path;

    public function __construct(RequestedPath $path)
    {
        $this->path = $path;
    }

    /**
     * @return mixed[] [SplFileInfo, [...]]
     */
    public function getNestedArray()
    {
        if ($this->path->isNone()) {
            return [];
        }

        return $this->pathnameToNestedArray($this->path->favoritePathname);
    }

    /**
     * @param string $pathname
     * @return mixed[] [SplFileInfo, [...]]
     */
    private function pathnameToNestedArray($pathname, $level = 0)
    {
        // HACK hard-coded max depth.
        if ($level > 1) {
            return [];
        }

        $ret = [];

        foreach (new \GlobIterator("$pathname/*") as $file) {
            if (!$file->isDir()) {
                continue;
            }

            $ret[] = [
                $file,
                $this->pathnameToNestedArray($file->getPathname(), $level + 1)
            ];
        }

        return $ret;
    }
}
