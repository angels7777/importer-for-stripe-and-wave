<?php

namespace App\Commands;

use App\Exceptions\WaveApiClientException;

class ListAccounts extends BaseCommand
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'list-accounts';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List all accounts in a business';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $businesses = collect($this->client->listBusinesses()['businesses']['edges'] ?? []);

        $business = $this->choice(
            'Select a Business',
            $businesses->pluck('node.name')->all(),
        );

        $business_id = $businesses->where('node.name', $business)->first()['node']['id'];

        try {
            $accounts = $this->client->listAccounts($business_id);
        } catch (WaveApiClientException $e) {
            $this->error($e->getMessage());
            $this->error(json_encode($e->getErrors()));
        }

        $accounts = collect($accounts['business']['accounts']['edges'] ?? [])
            ->map(fn($account) => [
                $account['node']['id'],
                $account['node']['name'],
                $account['node']['type']['name'],
                $account['node']['subtype']['name'] ?? '',
                $account['node']['normalBalanceType'],
            ]);

        $this->table([
            'Id',
            'Name',
            'Type',
            'Subtype',
            'Normal Balance Type',
        ], $accounts);
    }
}
