Woland
======

What?
-----
Woland is a read-only file browser accessible through HTTP.

Why?
----
When I replaced ownCloud with [Radicale](http://radicale.org/) and
[Syncthing](https://syncthing.net/), I needed a way to browse my files.
Nothing fancy, only a quick navigation panel on the left and the files on the
right.

The name Woland comes from [The Master and Margarita](https://en.wikipedia.org/wiki/The_Master_and_Margarita)
which I was reading when I needed a name for the repo.

How?
----
Woland is written for PHP ≥ 5.5 using the [Slim](http://www.slimframework.com/)
micro-framework.

The configuration for Woland must be placed in `~/.config/woland.json`, here is
a sample: 

```json
{
    "cache": "/home/azazello/.cache/woland",
    "favorites": {
        "music": "/path/to/music",
        "archives": "/path/to/archives"
    }
}
```

Favorites will be the only accessible directories.
