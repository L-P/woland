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
    private function pathnameToNestedArray($pathname)
    {
        $ret = [];

        foreach (new \GlobIterator("$pathname/*") as $file) {
            if (!$file->isDir()) {
                continue;
            }

            $ret[] = [
                $file,
                $this->pathnameToNestedArray($file->getPathname())
            ];
        }

        return $ret;
    }
}
