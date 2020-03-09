<?php

namespace App\Commands;

use App\Exceptions\WaveApiClientException;
use App\StripeApiClient;
use App\WaveApiClient;
use Carbon\Carbon;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class ImportTransactions extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'import
                            {--live-run : Whether to run the import in live mode (defaults to a dry run)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Run or test an import of data from Stripe';

    protected $stripe;

    protected $wave;

    protected $sales_tax_id;

    public function __construct(StripeApiClient $stripe_client, WaveApiClient $wave_client)
    {
        parent::__construct();

        $this->stripe = $stripe_client;
        $this->wave = $wave_client;
        $this->sales_tax_id = config('services.wave.sales_tax_id');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $business_id = $this->getBusinessId();

        $anchor_account_id = $this->getAnchorAccountId($business_id);
        $stripe_fee_account_id = $this->getStripeFeeAccountId($business_id);
        $ticket_account_id = $this->getTicketSalesAccountId($business_id);
        $sponsorship_account_id = $this->getSponsorshipAccountId($business_id);

        $payouts = $this->stripe->listPayouts();

        foreach ($payouts as $payout) {
            $transactions = $this->stripe->getTransactionsForPayout($payout->id);

            $payload = [
                'input' => [
                    'businessId' => $business_id,
                    'externalId' => $payout->id,
                    'date' => Carbon::createFromTimestampUTC($payout->arrival_date)->format('Y-m-d'),
                    'description' => $payout->description,
                    'anchor' => [
                        'direction' => 'DEPOSIT',
                        'accountId' => $anchor_account_id,
                        'amount' => number_format($payout->amount / 100, 2)
                    ],
                    'lineItems' => [],
                ]
            ];

            foreach ($transactions as $transaction) {
                // If this is a sponsorship, we don't need to assign sales tax.
                if (Str::contains(strtolower($transaction->description), 'sponsorship')) {
                    $line_item = [
                        'amount' => number_format($transaction->amount / 100, 2),
                        'accountId' => $sponsorship_account_id,
                        'balance' => 'CREDIT',
                        'description' => 'Sponsorships'
                    ];
                } else {
                    $line_item = [
                        'amount' => number_format($transaction->amount / 100, 2),
                        'accountId' => $ticket_account_id,
                        'balance' => 'CREDIT',
                        'description' => 'Total ticket purchases amount',
                        'taxes' => [
                            'salesTaxId' => $this->sales_tax_id,
                            'amount' => 8.25
                        ]
                    ];
                }

                $payload['input']['lineItems'][] = $line_item;

                // Stripe fee
                $payload['input']['lineItems'][] = [
                    'accountId' => $stripe_fee_account_id,
                    'amount' => number_format($transaction->fee / 100, 2),
                    'balance' => 'DEBIT',
                    'description' => (Str::contains(strtolower($transaction->description), 'sponsorship'))
                        ? 'Sponsorship Stripe fees'
                        : 'Stripe fees'
                ];
            }

            dd($payload, $this->option('live-run'));
        }

        // TODO: run mutations
    }

    protected function getBusinessId()
    {
        $businesses = collect($this->wave->listBusinesses()['businesses']['edges'] ?? []);

        $business = $this->choice(
            'Select a Business',
            $businesses->pluck('node.name')->all(),
        );

        return $businesses->where('node.name', $business)->first()['node']['id'];
    }

    protected function getAnchorAccountId(string $business_id)
    {
        $accounts = $this->getAccounts($business_id);

        $account = $this->choice(
            'Which account should be used as the anchor?',
            $accounts->pluck('node.name')->all(),
        );

        $this->line('You selected account `' . $account . '` as the anchor account.');

        return $accounts->where('node.name', $account)->first()['node']['id'];
    }

    protected function getTicketSalesAccountId(string $business_id)
    {
        $accounts = $this->getAccounts($business_id);

        $account = $this->choice(
            'Which account should be used for ticket sales income?',
            $accounts->pluck('node.name')->all(),
        );

        $this->line('You selected account `' . $account . '` as the ticket sales account.');

        return $accounts->where('node.name', $account)->first()['node']['id'];
    }

    protected function getSponsorshipAccountId(string $business_id)
    {
        $accounts = $this->getAccounts($business_id);

        $account = $this->choice(
            'Which account should be used for sponsorship income?',
            $accounts->pluck('node.name')->all(),
        );

        $this->line('You selected account `' . $account . '` as the sponsorship account.');

        return $accounts->where('node.name', $account)->first()['node']['id'];
    }

    protected function getStripeFeeAccountId(string $business_id)
    {
        $accounts = $this->getAccounts($business_id);

        $account = $this->choice(
            'Which account should be used for Stripe fees?',
            $accounts->pluck('node.name')->all(),
        );

        $this->line('You selected account `' . $account . '` as the stripe fee account.');

        return $accounts->where('node.name', $account)->first()['node']['id'];
    }

    protected function getAccounts(string $business_id)
    {
        try {
            $accounts = $this->wave->listAccounts($business_id);
        } catch (WaveApiClientException $e) {
            $this->error($e->getMessage());
            $this->error(json_encode($e->getErrors()));
        }

        return collect($accounts['business']['accounts']['edges'] ?? []);
    }
}
