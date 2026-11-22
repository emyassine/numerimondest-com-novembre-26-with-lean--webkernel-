# `bin`

Runs an executable installed by Composer in `internal/dependencies/bin/`. The child process runs from the application root and inherits the terminal streams and environment.

```sh
./platform bin <binary> -- <arguments>
./platform bin phpunit -- --version
```

The binary name accepts letters, numbers, dots, underscores, and hyphens. Arguments after `--` are passed directly to the executable.
