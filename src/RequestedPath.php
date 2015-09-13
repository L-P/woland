<?php

namespace Woland;

use \Psr\Http\Message\RequestInterface;

/// Request and path info squished together.
class RequestedPath
{
    use \lpeltier\Struct;

    /// @var string favorite name.
    public $favorite;

    /// @var string path relative to the favorite.
    public $path;

    /// @var \SplFileInfo
    public $info;

    /**
     * @param string[] $favorites
     * @return self
     */
    public static function fromRequest(RequestInterface $request, array $favorites)
    {
        $uriPath = $request->getUri()->getPath();
        $parts = array_values(array_filter(explode('/', $uriPath), 'strlen'));
        $favorite = (count($parts) > 0) ? $parts[0] : null;
        $path = implode('/', array_slice($parts, 1));

        /* This should never happen has the browser resolves relative URIs by
         * itself but it is still possible to write the GET request and send
         * it manually. A real user won't ever see this message. */
        if (in_array('..', $parts, true)) {
            throw new \RuntimeException("`$uriPath` is not a valid path.");
        }

        if ($favorite === null) {
            return new self();
        } else if (!array_key_exists($favorite, $favorites)) {
            throw new \RuntimeException('Unknown favorite.');
        }

        $info = new \SplFileInfo($favorites[$favorite] . "/$path");
        return new self(compact('favorite', 'info', 'path'));
    }

    /**
     * @return true if the requested path is the app index.
     */
    public function isNone()
    {
        return $this->favorite === null;
    }
}
