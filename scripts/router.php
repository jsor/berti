<?php

if (ini_get('auto_prepend_file') && !in_array(realpath(ini_get('auto_prepend_file')), get_included_files(), true)) {
    require ini_get('auto_prepend_file');
}

$_SERVER = array_merge($_SERVER, $_ENV);

function includeIfExists(string $file)
{
    return file_exists($file) ? include $file : false;
}

if (
    !includeIfExists(__DIR__ . '/../vendor/autoload.php') &&
    !includeIfExists(__DIR__ . '/../../../autoload.php')
) {
    echo 'You must set up the project dependencies using `composer install`' . PHP_EOL;
    exit(1);
}

function sendFile(string $path, string $content = null)
{
    $handle = finfo_open(FILEINFO_MIME);
    $type = finfo_file($handle, $path);
    finfo_close($handle);

    http_response_code(200);
    header('Content-Type: ' . $type);

    if (null !== $content) {
        echo $content;
    } else {
        readfile($path);
    }
}

function run(string $path, string $scriptName, string $buildDir)
{
    $container = Berti\container();

    $configFile = getenv('BERTI_CONFIG');
    if (is_file($configFile)) {
        (include $configFile)($container);
    }

    $documentCollector = $container['document.collector'];
    $documentProcessor = $container['document.processor'];
    $assetCollector = $container['asset.collector'];
    $assetProcessor = $container['asset.processor'];

    /** @var \Berti\Document[] $documents */
    $documents = $documentCollector(
        $path,
        $buildDir
    );

    foreach ($documents as $document) {
        $currentScriptName = trim($scriptName, '/');
        $documentPath = str_replace('\\', '/', $document->output->getRelativePathname());

        if ($currentScriptName !== $documentPath) {
            $currentScriptName .= '/' . $container['output.directory_index'];
        }

        $currentScriptName = ltrim($currentScriptName, '/');

        if ($currentScriptName !== $documentPath) {
            continue;
        }

        http_response_code(200);
        echo $documentProcessor($buildDir, $document, $documents);
        return;
    }

    /** @var \Berti\Asset[] $assets */
    $assets = $assetCollector(
        $path,
        $buildDir
    );

    foreach ($assets as $asset) {
        if ($scriptName !== $asset->output->getRelativePathname()) {
            continue;
        }

        sendFile(
            $asset->input->getRealPath(),
            $assetProcessor($asset, $assets)
        );
        return;
    }

    http_response_code(404);
}

$path = $_SERVER['DOCUMENT_ROOT'];
$scriptName = ltrim($_SERVER['SCRIPT_NAME'], '/');

$buildDir = getenv('BERTI_BUILD_DIR') ?: $path;

if (is_file($path . DIRECTORY_SEPARATOR . $scriptName)) {
    return false;
}

if (is_file($buildDir . DIRECTORY_SEPARATOR . $scriptName)) {
    sendFile($buildDir . DIRECTORY_SEPARATOR . $scriptName);
} else {
    run($path, $scriptName, $buildDir);
}

error_log(
    sprintf(
        '%s:%d [%d]: %s',
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['REMOTE_PORT'],
        http_response_code(),
        $_SERVER['REQUEST_URI']
    ),
    4
);
