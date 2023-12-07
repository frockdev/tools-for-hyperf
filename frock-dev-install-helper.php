<?php

$composerJson = json_decode(file_get_contents(getcwd().'/composer.json'), true);

if (getenv('REVERSE')=='0') {
    if (!isset($composerJson['repositories']['frock_hyperf']))
        $composerJson['repositories']['frock_hyperf'] = [
            'type' => 'path',
            'url' => 'frock-hyperf',
            "options" => [
                "symlink" => true
            ]
        ];

    $composerJson['require']['frock-dev/tools-for-hyperf'] = 'dev-main';
} elseif (getenv('REVERSE')=='1') {
    if (isset($composerJson['repositories']['frock_hyperf'])) {
        unset($composerJson['repositories']['frock_hyperf']);
    }

    unset($composerJson['require']['frock-dev/tools-for-hyperf']);
} else {
    echo 'Please set REVERSE=1 or REVERSE=0';
    exit(1);
}

$resultJson = json_encode($composerJson, JSON_PRETTY_PRINT);
$resultJson = str_replace('\\/', '/', $resultJson);

file_put_contents(getcwd().'/composer.json', $resultJson);
