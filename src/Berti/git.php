<?php

namespace Berti;

function git_ignored($path)
{
    $oldWorkingDir = getcwd();
    chdir($path);
    exec('git clean -ndX', $output);
    chdir($oldWorkingDir);

    $ignored = [];

    foreach ($output as $line) {
        list(, , $file) = explode(" ", $line, 3);
        $ignored[] = $file;
    }

    return $ignored;
}

function git_ignored_finder_filter($path)
{
    $ignored = git_ignored($path);

    return function (\SplFileInfo $file) use ($ignored) {
        foreach ($ignored as $path) {
            if (false !== strpos($file->getPathname(), $path)) {
                return false;
            }
        }
    };
}
