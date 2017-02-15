<?php

if (ini_get('auto_prepend_file') && !in_array(realpath(ini_get('auto_prepend_file')), get_included_files(), true)) {
    require ini_get('auto_prepend_file');
}

$_SERVER = array_merge($_SERVER, $_ENV);

$path = $_SERVER['DOCUMENT_ROOT'];
$scriptName = ltrim($_SERVER['SCRIPT_NAME'], '/');

if (is_file($path . DIRECTORY_SEPARATOR . $scriptName)) {
    return false;
}

function includeIfExists($file)
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

function run($path, $scriptName)
{
    $container = Berti\container();

    $configFile = getenv('BERTI_CONFIG');
    if (is_file($configFile)) {
        call_user_func(include $configFile, $container);
    }

    $buildDir = getenv('BERTI_BUILD_DIR') ?: $path;

    $documentCollector = $container['document.collector'];
    $documentProcessor = $container['document.processor'];
    $assetCollector = $container['asset.collector'];
    $assetProcessor = $container['asset.processor'];

    $documents = $documentCollector(
        $path,
        $buildDir
    );

    /** @var \Berti\Document $document */
    foreach ($documents as $document) {
        $currentScriptName = $scriptName;

        if ($currentScriptName !== $document->output->getRelativePathname()) {
            $currentScriptName .= DIRECTORY_SEPARATOR . 'index.html';
        }

        $currentScriptName = ltrim($currentScriptName, '/');

        if ($currentScriptName !== $document->output->getRelativePathname()) {
            continue;
        }

        http_response_code(200);
        echo $documentProcessor($buildDir, $document, $documents);
        return;
    }
    $assets = $assetCollector(
        $path,
        $buildDir
    );

    /** @var \Berti\Asset $asset */
    foreach ($assets as $asset) {
        if ($scriptName !== $asset->output->getRelativePathname()) {
            continue;
        }

        $handle = finfo_open(FILEINFO_MIME);
        $type = finfo_file($handle, $asset->input->getRealPath());
        finfo_close($handle);

        http_response_code(200);
        header('Content-Type: ' . $type);
        echo $assetProcessor($asset, $assets);
        return;
    }

    http_response_code(404);
}

run($path, $scriptName);

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
