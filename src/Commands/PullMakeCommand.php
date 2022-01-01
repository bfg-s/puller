<?php

namespace Bfg\Puller\Commands;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class PullMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:pull';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Puller worker class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Pull';

    protected $interface = null;

    /**
     * Execute the console command.
     *
     * @return void|bool
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        if (!is_dir(app_path('Pulls'))) {
            mkdir(app_path('Pulls'), 0777, 1);
        }

        $interfaces = \Puller::channelInterfaces();
        $type = $this->option('type');

        if (!$type || !isset($interfaces[$type])) {
            if (count($interfaces) == 1) {
                $type = array_key_first($interfaces);
            } else {
                $type = $this->choice("What \"Pull\" thrust do you want to create?", $interfaces, array_key_first($interfaces));
                $this->type = ucfirst($type) . ' ' . $this->type;
            }
        }

        if (isset($interfaces[$type])) {
            $this->interface = $interfaces[$type];
        }

        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/pull.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
                        ? $customPath
                        : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return is_dir(app_path('Pulls')) ? $rootNamespace.'\\Pulls' : $rootNamespace;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)
            ->replaceImplements($stub)
            ->replaceClass($stub, $name);
    }

    /**
     * Replace the implements and uses for the given stub.
     *
     * @param  string  $stub
     * @return $this
     */
    protected function replaceImplements(&$stub)
    {
        $uses = "";
        $implements = "";
        if ($this->interface) {
            $uses = "\nuse $this->interface;";
            $implements = " implements " . class_basename($this->interface);
        }

        $stub = str_replace(
            ['{{ uses }}', '{{ implements }}'],
            [$uses, $implements],
            $stub
        );

        return $this;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['type', null, InputOption::VALUE_OPTIONAL, 'The type of pull'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
        ];
    }
}
