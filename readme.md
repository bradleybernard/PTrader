# PredictIt Trader
A proof of concept trading bot that purchases "NO" shares for PredictIt.org Twitter count markets. The bot used Twitter's streaming API  and PredictIt's API to be the fastest to purchase a "NO" share when a contract was no longer feasible.

## Setup
1) Fetch PredictIt markets through their API based on title "how many tweets will @__ tweet"
2) Run Twitter HTTP streaming daemon process to subscribe to the scraped Twitter accounts tweets
3) On a new tweet event, check markets for that Twitter account and trade if the market count just passed a contract
4) Notifications on successful purchases through email/SMS

## Stats
- Visual interface for markets with graphs and current tweet contract. 
- Also shows recent trades for accounts and their balances.

## Example
- @potus tweets his 30th tweet and there is a contract for 25-29 tweets. The bot would purchase no on the 25-29 contract since the tweet pushed it over that range.

## Disclaimer
ONLY a proof of concept bot. PredictIt does not allow scraping or extracting data from their site. This bot directly violates their Terms of Service so use at your own risk!

### Screenshots
![1](/Screenshots/1.png?raw=true "1")
![2](/Screenshots/2.png?raw=true "2")

