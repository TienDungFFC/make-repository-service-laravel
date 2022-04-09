<?php
namespace Dungnt\LaravelMakeRepositoryService\Generators\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Dungnt\LaravelMakeRepositoryService\Exceptions\FileAlreadyExistsException;
use Dungnt\LaravelMakeRepositoryService\Generators\BindingsRepositoryGenerator;
use Dungnt\LaravelMakeRepositoryService\Generators\RepositoryEloquentGenerator;
use Dungnt\LaravelMakeRepositoryService\Generators\RepositoryInterfaceGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Facades\File;

class RepositoryCommand extends Command
{

    /**
     * The name of command.
     *
     * @var string
     */
    protected $name = 'make:repository';

    /**
     * The description of command.
     *
     * @var string
     */
    protected $description = 'Create a new repository.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Execute the command.
     *
     * @see fire()
     * @return void
     */
    public function handle(): void
    {
        $this->laravel->call([$this, 'fire'], func_get_args());
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    public function fire(): void
    {
        try {
            (new RepositoryEloquentGenerator([
                'name'      => $this->argument('name'),
                'force'     => $this->option('force'),
            ]))->run();

            (new RepositoryInterfaceGenerator([
                'name'  => $this->argument('name'),
                'force' => $this->option('force'),
            ]))->run();

            $this->info('Repository created successfully.');

            /**
             * Binding Repository to Service Provider
             */
            $bindingGenerator = new BindingsRepositoryGenerator([
                'name' => $this->argument('name'),
                'force' => $this->option('force'),
            ]);
            // generate repository service provider
            if (! file_exists($bindingGenerator->getPath())) {
                $this->call('make:provider', [
                    'name' => $bindingGenerator->getConfigGeneratorClassPath($bindingGenerator->getPathConfigNode()),
                ]);
                // placeholder to mark the place in file where to prepend repository bindings
                $provider = File::get($bindingGenerator->getPath());
                File::put($bindingGenerator->getPath(), vsprintf(str_replace('//', '%s', $provider), [
                    '//',
                    $bindingGenerator->bindPlaceholder
                ]));
            }
            $bindingGenerator->run();
            $this->info($this->type . ' created successfully.');
        } catch (FileAlreadyExistsException $e) {
            $this->error($this->type . ' already exists!');

            return;
        }
    }


    /**
     * The array of command arguments.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return [
            [
                'name',
                InputArgument::REQUIRED,
                'The name of class being generated.',
                null
            ],
        ];
    }


    /**
     * The array of command options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return [
            [
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force the creation if file already exists.',
                null
            ],
        ];
    }
}