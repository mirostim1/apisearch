### **ApiSearch API documentation**

ApiSearch is an application that is used to search GitHub issues and pull requests using a default search term. Based on the search, the application generates a result for the search word and returns that result to the user in a JSON response, and also stores that result in the database so that the same query for the same word in the future will be faster.
There are two versions of the API: v1 and v2, and the difference in usage will be presented later.

**Installation requirements:**

- PHP >= 8.0, MySQL 8.0.xx, Composer 2.5.x, Git, Symfony CLI tool (Symfony binary)

**Installation steps:**
- git clone repo: git clone https://github.com/mirostim1/apisearch.git. It can be used main or master branch because both contain the same source code.

Then in your root project folder use this series of commands via terminal:

- _**composer install && composer update**_
- (copy and place prod.decrypt.private.php to folder: config/secrets/prod)
- _**php bin/console secrets:set --local DATABASE_URL**_ and type input: _**mysql://User:Password@127.0.0.1:3306/apisearch_production_db?serverVersion=8.0.32&charset=utf8mb4**_. Here change the word User with your database user, and Password with your user's password. Also, if you named the database differently in the previous step, then change _**apisearch_production_db**_ in the string as well
- create database via MySQL CLI or by command: _**php bin/console doctrine:database:create**_ (database with given name in previous step will be created)
- **_php bin/console doctrine:migrations:migrate_**
- _**symfony server:prod**_
- ***symfony server:start*** (add flag -d if you want to start server in the detached mode)

**API usage**

As we mentioned earlier, there are two versions of the API v1 and v2.

**Basic use of API v1:**

API v1 is used to get the search result for the required word (required) and parameters (optional). The result is the sum of all the results obtained from the GitHub API issues and pull request endpoint. From these results, the total positive reactions (+1 or like) are taken, which are compared with the total negative reactions (-1 dislike) and the result is those with more by dividing by the total number of reactions and multiplying by the number 10 to get a score that is a number from 1-10. If there are more total positive results, then the message 'Rocks' is added to the API response along with the score and other data, and if there are more negative results, the message 'Sucks' is added.

**Basic examples of using this API (v1)**

http://127.0.0.1:8000/api/v1/score?term=php7

While the example with added all parameters that can be used looks like this:

http://127.0.0.1:8000/api/v1/score?term=php7&sort=reactions&order=desc&per_page=100&page=1

More about these parameters and what values they can contain can be found at this link:

https://docs.github.com/en/rest/search?apiVersion=2022-11-28#search-issues-and-pull-requests

**Basic use of API (v2)**

The same basics apply to this version of the API, the only thing is that the structure of the request itself is different, as will be seen from the example below. On this version of the endpoint, the word term in request is not required before the search term. And this version of the API saves the entire query string from the request in the database, not just the search term like API v1.

**Basic examples of using this API (v2):**

http://127.0.0.1:8000/api/v2/score?php7

While the example with all included parameters in the request would look like this:

http://127.0.0.1:8000/api/v2/score?php7&sort=reactions&order=desc&per_page=100&page=1

Regarding the documentation of these API endpoints (v1 and v2) there is OpenApi v3 visual documentation at:

http://127.0.0.1/api/doc

or JSON format at the address:

http://127.0.0.1/api/doc.json


