<?php

declare(strict_types=1);

namespace flight\commands;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;

class ControllerCommand extends AbstractBaseCommand
{
    /****
     * Initializes the ControllerCommand with configuration and defines the required controller name argument.
     *
     * @param array<string,mixed> $config Configuration settings loaded from .runway-config.json.
     */
    public function __construct(array $config)
    {
        parent::__construct('make:controller', 'Create a controller', $config);
        $this->argument('<controller>', 'The name of the controller to create (with or without the Controller suffix)');
    }

    /**
     * Creates a new controller class file with the specified name in the application's controllers directory.
     *
     * If the controller name does not end with "Controller", the suffix is appended automatically. The method generates a PHP class file using the Nette PHP Generator, including a constructor and a protected Engine property, and saves it to the appropriate location. Outputs error messages if the configuration is missing or the file already exists, and creates the target directory if necessary.
     *
     * @param string $controller Name of the controller to create (without or with "Controller" suffix).
     */
    public function execute(string $controller)
    {
        $io = $this->app()->io();
        if (isset($this->config['app_root']) === false) {
            $io->error('app_root not set in .runway-config.json', true);
            return;
        }

        if (!preg_match('/Controller$/', $controller)) {
            $controller .= 'Controller';
        }

        $controllerPath = getcwd() . DIRECTORY_SEPARATOR . $this->config['app_root'] . 'controllers' . DIRECTORY_SEPARATOR . $controller . '.php';
        if (file_exists($controllerPath) === true) {
            $io->error($controller . ' already exists.', true);
            return;
        }

        if (is_dir(dirname($controllerPath)) === false) {
            $io->info('Creating directory ' . dirname($controllerPath), true);
            mkdir(dirname($controllerPath), 0755, true);
        }

        $file = new PhpFile();
        $file->setStrictTypes();

        $namespace = new PhpNamespace('app\\controllers');
        $namespace->addUse('flight\\Engine');

        $class = new ClassType($controller);
        $class->addProperty('app')
            ->setVisibility('protected')
            ->setType('flight\\Engine')
            ->addComment('@var Engine');
        $method = $class->addMethod('__construct')
            ->addComment('Constructor')
            ->setVisibility('public')
            ->setBody('$this->app = $app;');
        $method->addParameter('app')
            ->setType('flight\\Engine');

        $namespace->add($class);
        $file->addNamespace($namespace);

        $this->persistClass($controller, $file);

        $io->ok('Controller successfully created at ' . $controllerPath, true);
    }

    /**
     * Writes the generated controller class to a PHP file in the application's controllers directory.
     *
     * @param string $controllerName The name of the controller class to save.
     * @param PhpFile $file The generated PHP file object representing the controller class.
     */
    protected function persistClass(string $controllerName, PhpFile $file)
    {
        $printer = new \Nette\PhpGenerator\PsrPrinter();
        file_put_contents(getcwd() . DIRECTORY_SEPARATOR . $this->config['app_root'] . 'controllers' . DIRECTORY_SEPARATOR . $controllerName . '.php', $printer->printFile($file));
    }
}
