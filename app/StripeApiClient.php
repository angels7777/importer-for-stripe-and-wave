<?php

namespace App;

use Illuminate\Support\Str;
use Stripe\BalanceTransaction;
use Stripe\Stripe;
use Stripe\Payout;

class StripeApiClient
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret_key'));
    }

    public function listPayouts()
    {
        $payoutsCollection = Payout::all(['limit' => 100]);
        $payouts = collect();

        foreach ($payoutsCollection->autoPagingIterator() as $payout) {
            $payouts->push($payout);
        }

        return $payouts;
    }

    public function getTransactionsForPayout(string $payout_id)
    {
        $transactionsCollection = BalanceTransaction::all(['limit' => 100, 'payout' => $payout_id]);
        $transactions = collect();

        foreach ($transactionsCollection->autoPagingIterator() as $transaction) {
            if (!in_array($transaction->type, ['payout'], true)) {
                $transactions->push($transaction);
            }
        }

        return $transactions;
    }
}
