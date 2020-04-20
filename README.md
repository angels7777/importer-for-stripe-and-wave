## Description
This tool can be used to import payouts from Stripe into Wave Accounting, while correctly splitting and applying sales tax.

For our purposes, we need to split transactions between conference ticket sales (taxable) and conference sponsorships (not taxable). We also need to split out the fees for payment processing (Stripe and Ti.to).

You should be able to easily modify this script to split payouts according to your own criteria.

## Config
Copy the `.env.example` file to `.env`, and replace the values.

You will need to set up an application in your Wave account with a full access token (versus the OAuth flow). See the documentation here to get started:

https://developer.waveapps.com/hc/en-us/articles/360020948171-Create-a-Wave-Account-and-Test-Businesses

## Usage
`php wave-stripe` to see the list of commands. The default command has an interactive prompt for setting the correct business and accounts to operate on.

Accounts can be determined interactively or provided as options. Here is an example of a command with all accounts provided as options:

`php wave-stripe import --date=2020-03-15 --business="Longhorn PHP, LLC" --salestax="Texas Sales & Use Tax" --anchor="CHK *2310" --stripe="Stripe Processing Fees" --sales="Sales - Unearned Income" --sponsorships="Sponsorships - Unearned Income"`