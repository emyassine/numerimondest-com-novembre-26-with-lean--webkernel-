# Freshness and Performance

## Observation

Local files are fingerprinted by content. Watched directories include sorted nested entries, so creation, removal and rename invalidate the observed target. A `ChangeSet` contains the result of one observation; querying it has no I/O. The next `watchNow()` performs a new observation and compares against the previous one.

Caller-provided resource readers return a string that is hashed in memory. Their freshness is only as reliable as their returned value: an ETag that does not change with content, for example, hides that change. Callbacks can perform network or database I/O, which is why polling should run where that work belongs, such as a build process.

Each source is read once per observation snapshot even when several targets share it. A compiler's candidate is checked against a new local snapshot before its artifact is published. External writers can still change a source immediately after that check; use immutable releases or coordinate writes when a stronger boundary is required.

## Publication

A target artifact is named by its content digest. The complete artifact is written before its `current.json` pointer is replaced atomically. This prevents readers from observing a partially written output. Publication is atomic per target; several artifacts do not form one transaction.

`current()` returns only this process's last ready artifact. It performs no I/O. `CacheStore::read()` reads and verifies the last published artifact, but it does not check the current sources. The package does not provide a shared-memory transport or freshness handshake between the CLI process and HTTP workers.

## Config integration and first access

`webkernel/config` uses this package's `SourceSnapshot` to validate its compiled input cache. A full `Config::boot()` checks tracked file and directory metadata without reading every source file. A lazy keyed `config()` lookup loads its section first and schedules freshness validation at shutdown; that call may therefore serve the last compiled section while a rebuild is pending. This keeps source validation out of the lookup, but it does not make a cold CLI lookup sub-100 µs.

After a key is resolved, repeated reads use its in-memory value. Do not run `watchNow()` from that key lookup: content hashing and directory scans belong to explicit observation or build work, not the request lookup. Measure section loading, full boot validation, observation and compilation as separate costs, and state whether the timed operation includes freshness validation.

Measure fresh-process Config access, repeated Config reads, local observation duration, remote callback duration, compilation, and publication separately. Record source counts, bytes read, OPcache state and the filesystem used.
