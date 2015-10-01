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
    },
    "secure": true,
    "realm": "Woland",
    "users": {
        "bememoth": "$2y$10$8wrLhgCrDeFsqaYX3BXXReVrqVEJU3LKuXEpHY3QQvFFKAtjOorlC"
    }
}
```

Favorites will be the only accessible directories.

If you want to use HTTP basic authentication, you must define some users. This
won't work using plain HTTP unless you set `secure` to `false`.

Users passwords must be hashed using `password_hash`. You can use the following
command to quickly get a password hash:

```shell
php -r 'echo "hash: " . password_hash(readline("password: "), PASSWORD_DEFAULT), "\n";'
```
