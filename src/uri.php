<?php

namespace Berti;

function uri_rewriter(
    string $url,
    string $sourceFile,
    string $targetFile
): string
{
    // First check also matches protocol-relative urls like //example.com
    if (
        (isset($url[0])  && '/' === $url[0]) ||
        false !== strpos($url, '://') ||
        0 === strpos($url, 'data:')
    ) {
        return $url;
    }

    $sourceUrl = (string) $sourceFile;

    if ('' !== $sourceUrl) {
        if ('/' !== $sourceUrl[strlen($sourceUrl) - 1]) {
            $sourceUrl = dirname($sourceFile);
        }

        $sourceUrl = trim($sourceUrl, '/');

        if ('.' === $sourceUrl) {
            $sourceUrl = '';
        }
    }

    $targetUrl = (string) $targetFile;

    if ('' !== $targetUrl) {
        if ('/' !== $targetUrl[strlen($targetUrl) - 1]) {
            $targetUrl = dirname($targetUrl);
        }

        $targetUrl = trim($targetUrl, '/');

        if ('.' === $targetUrl) {
            $targetUrl = '';
        }
    }

    if ($targetUrl === $sourceUrl) {
        return $url;
    }

    if ('' === $sourceUrl) {
        $targetUrlParts = explode('/', $targetUrl);
        $urlParts = explode('/', $url);

        $len = min(count($targetUrlParts), count($urlParts));
        $count = 0;

        for ($i = 0; $i < $len; $i++) {
            if ($targetUrlParts[$i] === $urlParts[$i]) {
                $count++;
            } else {
                break;
            }
        }

        if ($count > 0) {
            $targetUrl = implode('/', array_slice($targetUrlParts, $count));
            $url = implode('/', array_slice($urlParts, $count));
        }

        if ('' === $targetUrl) {
            return $url;
        }

        return str_repeat('../', count(explode('/', $targetUrl))) . $url;
    }

    if ('' === $targetUrl) {
        return uri_canonicalizer($sourceUrl . '/' . $url);
    }

    if (0 === strpos($targetUrl, $sourceUrl)) {
        $prepend = $targetUrl;
        $count = 0;

        while ($prepend !== $sourceUrl) {
            $count++;
            $prepend = dirname($prepend);
        }

        return str_repeat('../', $count) . $url;
    }

    if (0 === strpos($sourceUrl, $targetUrl)) {
        $path = $sourceUrl;
        while (0 === strpos($url, '../') && $path !== $targetUrl) {
            $path = dirname($path);
            $url = substr($url, 3);
        }

        return $url;
    }

    $prepend = str_repeat('../', count(explode('/', $targetUrl)));
    $path = $sourceUrl . '/' . $url;

    return $prepend . uri_canonicalizer($path);
}

function uri_canonicalizer(string $path, string $separator = '/'): string
{
    $path = str_replace(['\\', '/'], $separator, $path);

    $first = '';

    // Protocol relative url, starting with exactly 2 /
    if (0 === strpos($path, '//') && (!isset($path[2]) || $path[2] !== $separator)) {
        $first = $separator . $separator;
        $path = (string) substr($path, 2);
    // Absolute url
    } elseif (false !== strpos($path, '://')) {
        list($first, $path) = explode('://', $path, 2);
        $first .= '://';
    } elseif ($separator === $path[0]) {
        $first = $separator;
        $path = (string) substr($path, 1);
    }

    $parts = array_filter(explode($separator, $path));

    // Ensure trailing / is preserved
    if (substr($path, -strlen($separator)) === $separator) {
        $parts[] = '';
    }

    $canonicalized = [];
    $startDrop = '' !== $first;
    $dropNextCounter = 0;

    foreach ($parts as $part) {
        if ('.' === $part) {
            continue;
        }

        if ('..' !== $part) {
            $startDrop = true;
            if ($dropNextCounter > 0) {
                $dropNextCounter--;
                continue;
            }

            $canonicalized[] = $part;
            continue;
        }

        if (!$startDrop) {
            $canonicalized[] = $part;
        } else {
            if (0 === count($canonicalized)) {
                $dropNextCounter++;
            } else {
                array_pop($canonicalized);
            }
        }
    }

    return $first . implode($separator, $canonicalized);
}
