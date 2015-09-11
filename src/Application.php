<?php

namespace Woland;

use \Psr\Http\Message\RequestInterface;

class Application
{
    /**
     * @var string[] paths to display in the initial view. Those are also the
     * only paths you will be able to access through the app.
     *
     * ["name" => "path",]
     *
     * The key is displayed as the favorite name.
     */
    private $favorites;

    /**
     * @param string[] $favorites @see $this->favorites.
     */
    public function __construct(array $favorites)
    {
        if (count($favorites) < 1) {
            throw new \RuntimeException('$favorites can\'t be empty, you need at least one path.');
        }

        $this->favorites = array_map('realpath', $favorites);
    }

    public function __invoke(RequestInterface $request)
    {
        header('Content-Type: text/plain; charset=UTF-8');
        $path = $request->getUri()->getPath();

        echo "Requested path: $path\n";
        echo "Favorites:\n";
        var_export($this->favorites);
    }
}
