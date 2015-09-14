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
     * Return the subtree of the current dir inside the tree of parent folders.
     *
     * @return mixed[] [SplFileInfo, [...]]
     */
    public function getPartialTree()
    {
        if ($this->path->isNone()) {
            // Better to throw than displaying the root.
            throw new \RuntimeException('No valid path for tree.');
        }

        // Current dir subdirs at depth 3.
        $curPath = $this->path->info->getPathname();
        $ret = [
            new \SplFileInfo($curPath),
            $this->pathnameToNestedArray($curPath, 3)
        ];

        // All parent dirs with no depth.
        while ($curPath !== $this->path->favoritePathname) {
            $curPath = dirname($curPath);
            $ret = [
                new \SplFileInfo($curPath),
                [$ret]
            ];
        }

        return [$ret];
    }

    /**
     * @param string $pathname
     * @return mixed[] [SplFileInfo, [...]]
     */
    private function pathnameToNestedArray($pathname, $maxDepth, $depth = 0)
    {
        if ($depth >= $maxDepth)
            return [];

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
