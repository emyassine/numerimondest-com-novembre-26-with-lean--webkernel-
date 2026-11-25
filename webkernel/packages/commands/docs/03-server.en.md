# `server`

Starts the local HTTP server and serves application requests through `public/index.php`.

```sh
./platform server
./platform server --host=0.0.0.0 --port=8080
./platform server --mount=/account:/srv/apps/codeigniter --mount=/docs:/srv/apps/docs
```

The default address is `127.0.0.1:8000`. `--profile-lifecycle` enables lifecycle profiling. `--with-jit` and `--with-jit=false` enable or disable JIT for the server process.

Use `--mount=/url-prefix:/application-directory` to route another PHP front-controller application through the same server. The `=` separator is also accepted. The directory must contain `index.php`; the application path can be anywhere readable by the server. Repeat `--mount` to add more applications. The built-in public application remains mounted at `/`.
