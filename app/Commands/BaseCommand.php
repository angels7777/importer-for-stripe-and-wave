<?php

namespace App\Commands;

use App\WaveApiClient;
use LaravelZero\Framework\Commands\Command;

class BaseCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'base-command';

    protected $client;

    public function __construct(WaveApiClient $client)
    {
        parent::__construct();

        $this->client = $client;
    }
}
