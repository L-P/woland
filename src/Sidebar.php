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
    public function getFullTree()
    {
        if ($this->path->isNone()) {
            // Better to throw than displaying the root.
            throw new \RuntimeException('No valid path for tree.');
        }

        return $this->pathnameToNestedArray($this->path->favoritePathname, 2);
    }

    /**
     * @param string $pathname
     * @return mixed[] [SplFileInfo, [...]]
     */
    private function pathnameToNestedArray($pathname, $maxDepth, $depth = 0)
    {
        if ($depth >= $maxDepth) {
            return [];
        }

        $ret = [];

        foreach (new \GlobIterator("$pathname/*") as $file) {
            if (!$file->isDir()) {
                continue;
            }

            $ret[] = [
                $file,
                $this->pathnameToNestedArray($file->getPathname(), $maxDepth, $depth + 1)
            ];
        }

        return $ret;
    }
}
