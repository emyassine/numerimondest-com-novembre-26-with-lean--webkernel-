# `completion`

Prints a shell completion script for Bash or Zsh. Bash is the default.

```sh
./platform completion
./platform completion zsh
```

The generated script queries the command registry for completion candidates. `_complete` is an internal command used by those scripts.
