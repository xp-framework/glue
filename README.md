Glue
====
This tool glues together XP Framework projects. It depends on a `glue.json` file which contains project and dependency information:

```json
{
  "name"    : "thekid/dialog",
  "version" : "4.0.0",
  "require" : {
    "xp-forge/mustache" : ">=1.2",
    "xp-framework/core" : "~5.9"
  }
}
```

To fetch the dependencies, simply run Glue's *install* command:

```sh
$ glue install
[200 ##########] xp-forge/mustache @ >=1.2: xpbuild@public 1.2.0
[200 ##########] xp-framework/core @ ~5.9: checkout@local 5.9.11

OK, 2 dependencies processed, 5 paths registered
Memory used: 3367.71 kB (3625.53 kB peak)
Time taken: 1.206 seconds
```

This will fetch the libraries according to the dependency information, place them inside the current directory and adjust the class path accordingly.

***After this step, you're ready to run the checked out software.***

Initial setup
-------------
To set up glue, symlink the `glue` script into a directory in your PATH and place a `glue.ini` file next to it. 

```sh
$ cd ~/bin
$ ln -s /path/to/glue/glue
$ cp /path/to/glue/glue.ini .
```

Open the configuration file and add something along the lines of the following:

```ini
[sources]
checkout@local="xp-framework@/path/to/devel/xp|xp-forge@/path/to/devel/xp"
xpbuild@public="http://builds.planet-xp.net/"
artifactory@idev="https://friebe:******@artifactory.example.com/artifactory/"
```

Version specifiers
------------------
You can use the following to select the version you'd like to have installed:

| Specifier         | Meaning |
| ----------------- | --------|
| `1.0.0`, `1.0`    | Exact version match required. |
| `!=1.0.0`         | Any version not equal to `1.0.0` will match this. |
| `>1.2`, `>=1.2.3` | A greater than / great than or equal to constraint. |
| `<1.2`, `<=1.2.3` | A less than / less than or equal to constraint. |
| `>=1.2,<1.3`      | Use commas to separate multiple conditions applied with a logical **and**. |
| `~1.2`            | The next significant release, meaning `>=1.2,<2.0`, so any 1.x version is OK. |
| `1.2.*`           | Any version starting with `1.2` matches this wildcard. |

Comparisons are performed accordint to [semantic versioning](http://semver.org/).
