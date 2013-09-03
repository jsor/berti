<?php

namespace Berti;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Asset
{
    public $input;
    public $output;

    public function __construct(SplFileInfo $input, SplFileInfo $output)
    {
        $this->input = $input;
        $this->output = $output;
    }
}

function asset_finder($path)
{
    $finder = new Finder();

    return $finder
        ->name('/\.(html?|js|css|jpe?g|gif|png)$/')
        ->files()
        ->in($path)
    ;
}

function asset_collector($finder, $path, $targetDir)
{
    $assets = [];

    foreach ($finder($path)->sortByName() as $file) {
        $relativePath = ltrim($file->getRelativePathName(), DIRECTORY_SEPARATOR);

        $outputFile = new SplFileInfo(
            $targetDir.DIRECTORY_SEPARATOR.$relativePath,
            $file->getRelativePath(),
            $relativePath
        );

        $assets[] = new Asset($file, $outputFile);
    }

    return $assets;
}

function asset_processor($outputFilter, Asset $asset, array $assetCollection)
{
    return $outputFilter($asset, $assetCollection);
}
