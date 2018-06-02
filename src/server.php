<?php

namespace Berti;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\Factory;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\resolve;
use function RingCentral\Psr7\stream_for;

function server(
    callable $documentCollector,
    callable $documentProcessor,
    callable $assetCollector,
    callable $assetProcessor,
    callable $mimeTypeDetector,
    string $outputDirectoryIndex,
    string $srcDir,
    string $buildDir,
    OutputInterface $output,
    string $address = '127.0.0.1:8000'
): int
{
    $loop = Factory::create();

    $server = new HttpServer(function (ServerRequestInterface $request) use (
        $documentCollector,
        $documentProcessor,
        $assetCollector,
        $assetProcessor,
        $mimeTypeDetector,
        $outputDirectoryIndex,
        $srcDir,
        $buildDir,
        $output
    ) {
        $scriptName = ltrim($request->getUri()->getPath(), '/');

        $srcFile = new \SplFileInfo($srcDir . DIRECTORY_SEPARATOR . $scriptName);
        $file = new \SplFileInfo($buildDir . DIRECTORY_SEPARATOR . $scriptName);

        if ($srcFile->isFile()) {
            $response = file(
                $mimeTypeDetector,
                $srcFile
            );
        } elseif ($file->isFile()) {
            $response = file(
                $mimeTypeDetector,
                $file
            );
        } else {
            $response = run(
                $documentCollector,
                $documentProcessor,
                $assetCollector,
                $assetProcessor,
                $mimeTypeDetector,
                $outputDirectoryIndex,
                $srcDir,
                $scriptName,
                $buildDir
            );
        }

        return resolve($response)
            ->then(function (ResponseInterface $response) use ($output, $request) {
                $output->writeln(
                    sprintf(
                        '%s:%d [%d]: %s',
                        $request->getServerParams()['REMOTE_ADDR'],
                        $request->getServerParams()['REMOTE_PORT'],
                        $response->getStatusCode(),
                        $request->getUri()
                    )
                );

                return $response;
            });
    });

    $server->on('error', function ($error) use ($output) {
        $output->writeln((string) $error);
    });

    $socket = new SocketServer($address, $loop);
    $server->listen($socket);

    $output->writeln('Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()));

    $loop->run();
}

function file(
    callable $mimeTypeDetector,
    \SplFileInfo $file
): Response
{
    return send(
        $mimeTypeDetector,
        $file,
        stream_for($file->openFile('rb'))
    );
}

function send(
    callable $mimeTypeDetector,
    \SplFileInfo $file,
    StreamInterface $body
): Response
{
    return new Response(
        200,
        ['Content-Type' => $mimeTypeDetector($file)],
        $body
    );
}

function run(
    $documentCollector,
    $documentProcessor,
    $assetCollector,
    $assetProcessor,
    $mimeTypeDetector,
    $outputDirectoryIndex,
    string $srcDir,
    string $scriptName,
    string $buildDir
): Response
{
    /** @var \Berti\Document[] $documents */
    $documents = $documentCollector(
        $srcDir,
        $buildDir
    );

    /** @var \Berti\Asset[] $assets */
    $assets = $assetCollector(
        $srcDir,
        $buildDir
    );

    foreach ($documents as $document) {
        $currentScriptName = trim($scriptName, '/');
        $documentPath = str_replace('\\', '/', $document->output->getRelativePathname());

        if ($currentScriptName !== $documentPath) {
            $currentScriptName .= '/' . $outputDirectoryIndex;
        }

        $currentScriptName = ltrim($currentScriptName, '/');

        if ($currentScriptName !== $documentPath) {
            continue;
        }

        return send(
            $mimeTypeDetector,
            $document->output,
            stream_for($documentProcessor($buildDir, $document, $documents, $assets))
        );
    }

    foreach ($assets as $asset) {
        if ($scriptName !== $asset->output->getRelativePathname()) {
            continue;
        }

        return send(
            $mimeTypeDetector,
            $asset->output,
            stream_for($assetProcessor($asset, $assets))
        );
    }

    return new Response(
        404,
        ['Content-Type' => 'text/plain']
    );
}
