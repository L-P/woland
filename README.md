Woland
======

What?
-----
Woland is a file browser written in PHP 5.5.

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
Woland is written for PHP ≥ 5.5 without a framework. I initally planned to use
Silex or Slim but even those two would have been overkill for what I had in
mind.

I initially wanted Woland to run from a _.phar_ archive without a real server
in front of it. This will be implemented the day I implement authentication.

Running Woland
--------------
1. Put composer in your `$PATH` and run `make`.
2. Write your configuration in `~/.config/woland.json`. The configuration is a
   simple JSON object with the _favorite_ name as the key and a directory
   full path as the value. eg.
   ```json
   {
       "music" => "/home/behemoth/music",
       "pictures" => "/home/azazello/pics"
   }
   ```
3. Run `make server` to run Woland on `localhost:8082`.

Planned features
----------------
* Single _.phar_ file.
* Embed media files.
* Auto-detect best view mode from mime types.
* Authentication. (requires state)
* Thumbnails. (requires state)
