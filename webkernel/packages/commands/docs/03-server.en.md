# `server`

Starts the local HTTP server and serves application requests through `public/index.php`.

```sh
./platform server
./platform server --host=0.0.0.0 --port=8080
```

The default address is `127.0.0.1:8000`. `--profile-lifecycle` enables lifecycle profiling. `--with-jit` and `--with-jit=false` enable or disable JIT for the server process.
