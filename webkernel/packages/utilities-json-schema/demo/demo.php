<?php

$autoload_path = $argv[1] ?? null;
if (! is_string($autoload_path) || ! is_file($autoload_path)) {
    fwrite(STDERR, sprintf(
        "Usage: php %s /path/to/composer/autoload.php\n",
        $argv[0],
    ));
    exit(1);
}

require $autoload_path;

$data = json_decode(file_get_contents('data.json'));

// Validate
$validator = new Webkernel\Utilities\JsonSchema\Validator();
$validator->validate($data, (object) ['$ref' => 'file://' . realpath('schema.json')]);

if ($validator->isValid()) {
    echo "The supplied JSON validates against the schema.\n";
} else {
    echo "JSON does not validate. Violations:\n";
    foreach ($validator->getErrors() as $error) {
        echo sprintf("[%s] %s\n", $error['property'], $error['message']);
    }
}
