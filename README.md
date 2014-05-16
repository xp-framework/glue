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