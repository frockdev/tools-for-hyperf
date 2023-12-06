<?php

namespace FrockDev\ToolsForHyperf\Commands;

use Hyperf\Command\Command;
use Symfony\Component\Console\Input\InputArgument;

class AddGeneratedNamespacesToComposerJson extends Command
{

    protected ?string $name = 'frock:add-generated-namespaces-to-composer-json';

    protected function getArguments()
    {
        return [
            ['DEVSPACE_NAME', InputArgument::REQUIRED, 'Here is an explanation of this parameter']
        ];
    }

    public function handle() {

        $composerJson = json_decode(file_get_contents(BASE_PATH.'/composer.json'), true);

        $composerJson['autoload']['psr-4'] = [];
        $composerJson['autoload']['psr-4']['App\\'] = 'app/';
        $composerJson['autoload']['psr-4']['Database\\Factories\\'] = 'database/factories/';
        $composerJson['autoload']['psr-4']['Database\\Seeders\\'] = 'database/seeders/';

        $projectName = $this->input->getArgument('DEVSPACE_NAME');
        $projectName = $this->fixProjectName($projectName);

        $projectOwner = $this->getProjectOwnerByFullName($composerJson['name']);

        shell_exec('mv '.BASE_PATH.'/protoGenerated/'.ucfirst($projectOwner).'/'.$projectName.'Contracts/* '.BASE_PATH.'/protoGenerated');
        shell_exec('rm -rf '.BASE_PATH.'/protoGenerated/'.ucfirst($projectOwner));

        foreach (scandir('/var/www/php/protoGenerated/') as $moduleDir) {
            if ($moduleDir==='.' || $moduleDir==='..') continue;
            $composerJson['autoload']['psr-4'][ucfirst($projectOwner).'\\'.$projectName.'Contracts'.'\\'.$moduleDir.'\\'] = 'protoGenerated/'.$moduleDir.'/';
        }

        file_put_contents(BASE_PATH.'/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

    }

    private function fixProjectName($projectName): string
    {
        $projectName = preg_replace("/[^\w0-9]+/", '-', $projectName);
        $exploded = explode('-', $projectName);
        foreach ($exploded as &$word) {
            $word = ucfirst($word);
        }
        return implode('', $exploded);
    }

    private function getProjectOwnerByFullName(string $projectFullName): string
    {
        $nameExploded = explode('/', $projectFullName);
        return $nameExploded[0];
    }
}