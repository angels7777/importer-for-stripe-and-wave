<?php

namespace App\Commands;

class ListBusinesses extends BaseCommand
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'list-businesses';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List all businesses';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $businesses = collect($this->client->listBusinesses()['businesses']['edges'] ?? [])
            ->map(fn($business) => [
                $business['node']['id'],
                $business['node']['name'],
                $business['node']['isClassicAccounting'] ? '1' : '0',
                $business['node']['isClassicInvoicing'] ? '1' : '0',
                $business['node']['isPersonal'] ? '1' : '0',
            ]);

        $this->table([
            'id',
            'name',
            'isClassicAccounting',
            'isClassicInvoicing',
            'isPersonal',
        ], $businesses);
    }
}
