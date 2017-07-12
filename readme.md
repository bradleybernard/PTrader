# PredictIt Trader
An automated trading bot that purchases no shares for PredictIt.org Twitter count markets. The bot used Twitter's streaming API  and PredictIt's API to be the first to purchase a no share when a contract was no longer feasible.

### Screenshots
![1](/Screenshots/1.png?raw=true "1")
![2](/Screenshots/2.png?raw=true "2")


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
Only a proof of concept bot. PredictIt does not allow scraping or automated trading since it slows down their site and clouds the markets.
