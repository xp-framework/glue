Glue
====
This tool glues together XP Framework projects. It depends on a `glue.json` file which contains project and dependency information:

```json
{
  "name"    : "thekid\/dialog",
  "version" : "4.0.0",
  "require" : {
    "xp-forge\/mustache" : "1.2+",
    "xp-framework\/core" : "5.9~"
  }
}
```

To fetch the dependencies, simply run Glue's *install* command:

```sh
$ glue install
[200 ##########] xp-forge/mustache @ 1.2+: xpbuild@public 1.2.0
[200 ##########] xp-framework/core @ 5.9~: xpbuild@public 5.9.11
```

This will fetch the libraries according to the dependency information, place them inside the current directory and adjust the class path accordingly.

***After this step, you're ready to run the checked out software.***

Setup
-----
To set up glue, symlink the `glue` script into a directory in your PATH and place a `glue.ini` file next to it. Open the configuration file and add something along the lines of the following:

```ini
[sources]
source[checkout@local]="xp-framework@/path/to/devel/xp|xp-forge@/path/to/devel/xp"
source[xpbuild@public]="http://builds.planet-xp.net/"
source[artifactory@idev]="https://friebe:******@artifactory.example.com/artifactory/"
```