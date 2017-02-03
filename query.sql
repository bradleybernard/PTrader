SELECT name, market_id, COUNT(*) as tweet_cnt, TIMESTAMPDIFF(DAY, NOW(), markets.date_end) as remaining 
FROM tweets
INNER JOIN markets ON markets.twitter_id = tweets.twitter_id
WHERE tweets.api_created_at >= markets.date_start 
AND tweets.api_created_at <= markets.date_end
GROUP BY markets.market_id, markets.name, markets.date_end
