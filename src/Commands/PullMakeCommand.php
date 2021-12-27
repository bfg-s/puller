<?php

namespace Bfg\Puller\Commands;

use Bfg\Puller\Interfaces\PullLikeAlpineInterface;
use Bfg\Puller\Interfaces\PullLikeLivewireInterface;
use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
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
        if ($this->option('livewire')) {
            $uses = "\nuse " . PullLikeLivewireInterface::class . ";";
            $implements = " implements PullLikeLivewireInterface";
        } else if ($this->option('alpine')) {
            $uses = "\nuse " . PullLikeAlpineInterface::class . ";";
            $implements = " implements PullLikeAlpineInterface";
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
            ['livewire', null, InputOption::VALUE_NONE, 'Create the Livewire puller'],
            ['alpine', null, InputOption::VALUE_NONE, 'Create the Alpine puller'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the model already exists'],
        ];
    }
}
