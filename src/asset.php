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

function asset_finder(string $path): Finder
{
    return (new Finder())
        ->append([]);
}

function asset_collector(
    callable $finder,
    string $path,
    string $targetDir
): array
{
    $assets = [];

    foreach ($finder($path)->sortByName() as $file) {
        $relativePath = ltrim($file->getRelativePathName(), DIRECTORY_SEPARATOR);

        $outputFile = new SplFileInfo(
            $targetDir . DIRECTORY_SEPARATOR . $relativePath,
            $file->getRelativePath(),
            $relativePath
        );

        $assets[] = new Asset($file, $outputFile);
    }

    return $assets;
}

function asset_processor(
    callable $outputFilter,
    Asset $asset,
    array $assetCollection
): string
{
    return $outputFilter($asset->input->getContents(), $asset, $assetCollection);
}
