<?php

namespace Bfg\Puller\Commands;

use Bfg\Puller\Core\LaravelHooks;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class PullEventsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'puller:events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show all events that can be a message.';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle()
    {
        $eventList = LaravelHooks::getEvents();
        $guards = array_keys(config('auth.guards'));
        $apply_guards = $this->option('all')
            ? $guards
            : ($this->option('guard') ? explode(',', $this->option('guard')) : [config('puller.guard')]);
        $new_apply_guards = [];
        foreach ($guards as $ex_guard) {
            if ($this->option($ex_guard)) {
                $new_apply_guards[] = $ex_guard;
            }
        }
        if ($new_apply_guards) $apply_guards = $new_apply_guards;
        $sortedList = [];
        foreach ($guards as $guard) {
            if (in_array($guard, $apply_guards)) {
                foreach ($eventList as $event) {
                    $pattern = "\\".ucfirst(\Str::camel($guard))."Message";
                    if (\Str::is("*$pattern*", $event)) {
                        $name = str_replace(["\\", ":-"], ":", \Str::snake(trim(
                            preg_replace("/.*(".preg_quote($pattern).")(.*)/", "$2", $event),
                            '\\'
                        ), '-'));
                        $sortedList[] = [$name, $event, $guard];
                    }
                }
            }
        }

        $this->table(['Event Name', 'Event Class', 'Event Guard'], $sortedList);

        return 0;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $return = [
            ['guard', null, InputOption::VALUE_OPTIONAL, 'The guard for show of Puller Messages'],
            ['all', null, InputOption::VALUE_NONE, 'Show events for all guards'],
        ];

        foreach (array_keys(config('auth.guards')) as $array_key) {
            $return[] = [$array_key, null, InputOption::VALUE_NONE, "Select the {$array_key} guard for show of Puller Messages"];
        }

        return $return;
    }
}
