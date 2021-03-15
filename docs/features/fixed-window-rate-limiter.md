# Fixed Window Rate Limiter

A "rate limiter" controls how frequently some event (e.g. an HTTP request or a login attempt) is allowed to happen. 
Rate limiting is commonly used as a defensive measure to protect services from excessive use (intended or not) 
and maintain their availability. 
It's also useful to control your internal or outbound processes (e.g. limit the number of simultaneously processed messages).

Currently, only Fixed Window Rate Limiter is implemented on this application This is the simplest technique and 
it's based on setting a limit for a given interval of time (e.g. 5,000 requests per hour or 3 login attempts every 
15 minutes).

## Table of Contents

- [Related environment variables](#related-environment-variables)

### Related environment variables

| Variable | Description |
| -----------------------------------  |:--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `RATE_LIMITER_STORAGE_DSN`           | The data source name of the rate limiter storage. Ex: `redis://localhost`. This storage is needed to compute the the API consume. For more information, please check [symfony/rate-limiter](https://github.com/symfony/symfony-docs/blob/5.x/rate_limiter.rst) documentation.                                 |
| `RATE_LIMITER_FIXED_WINDOW_ROUTES`   | A list of route names (defined in [routes.yaml](../../config/routes.yaml)) separated by comma. Ex: `"getAccessToken,refreshAccessToken,bulkCreateUsersAssignments"`. You can also use `"*"` to add rate limiter to all the routes in the application.                                                         |
| `RATE_LIMITER_FIXED_WINDOW_LIMIT`    | Number of requests that can be done during the given interval. For more information, please check [symfony/rate-limiter](https://github.com/symfony/symfony-docs/blob/5.x/rate_limiter.rst) documentation.                                                                                                    |
| `RATE_LIMITER_FIXED_WINDOW_INTERVAL` | The value of the interval option must be a number followed by any of the units accepted by the PHP date relative formats (e.g. 3 seconds, 10 hours, 1 day, etc.). For more information, please check [symfony/rate-limiter](https://github.com/symfony/symfony-docs/blob/5.x/rate_limiter.rst) documentation. |
| `LOCK_DSN`                           | The data source name of the LOCK storage. This extension is a dependency of the symfony/rate-limiter component. For more information, please check the [symfony/lock](https://symfony.com/doc/current/components/lock.html) documentation.  Ex: `redis://localhost`                                           |
