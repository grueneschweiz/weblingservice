# Webling Service

[![Tests](https://github.com/grueneschweiz/weblingservice/actions/workflows/tests.yml/badge.svg)](https://github.com/grueneschweiz/weblingservice/actions/workflows/tests.yml)
[![Coverage Status](https://coveralls.io/repos/github/grueneschweiz/weblingservice/badge.svg)](https://coveralls.io/github/grueneschweiz/weblingservice)

> Read the [API documentation](docs/API.md)

**UNDER DEVELOPEMENT**

This project aims to add some crucial but missing functionality to Webling,
while using Weblings RESTful API and exposing a new, higher lever RESTful
API. It is based on the fabulous [Laravel](https://laravel.com/) framework
to speed up the development. Check out the [docs](https://laravel.com/docs/9.x)
and start contributing 😍.

## Contributing ...

... is cool, simple and helps to make the 🌍 a better place 🤩

1. Install [docker](https://store.docker.com/search?offering=community&type=edition)
1. Start docker
1. Clone this repo `git clone https://github.com/grueneschweiz/weblingservice.git`
1. `cd` into the folder containing the repo
1. If your UID or GID is different to 1000 add `UserID=X` and `GroupID=Y` to a file .env.temp 
   (where X is your `id -u` and Y is `id -g`) and `source .env.temp`
1. Execute `docker-compose run app composer install` and have a ☕️ while it installs.
1. Execute `docker-compose up -d` to start up the stack. The first time you run
   this command, it will take a minute or two. Subsequent calls will be much faster.
1. Execute `docker exec wsapp cp .env.example .env` to get an instance of the environment variables
1. Execute `docker exec wsapp php artisan key:generate` to generate the app secrets
1. Execute `docker exec wsapp php artisan migrate` to initialise the database tables
1. Execute `docker exec wsapp php artisan passport:install` to setup oAuth2

Yupii, you're nearly done. Just add the `WEBLING_API_KEY`, `WEBLING_FINANCE_ADMIN_API_KEY`, `WEBLING_BASE_URL`
to the `.env` file and you're ready to go. From now on, you can just start up the
stack with a single `docker-compose up -d`, without repeating all the commands
from above.

### Docker Cheat Sheet

- Start up: `docker-compose up -d`
- Shut down: `docker-compose down`
- Execute Laravel CLI commands (enter container): `docker exec -it wsapp bash` use `exit` to escape the
  container.
- Add dependency using composer: `docker-compose run wsapp composer require DEPENDENCY`

### Tooling

#### Mailhog

All mail you send out of the application will be caught by [Mailhog](http://localhost:8020)

#### MySQL

Use the handy [phpMyAdmin](http://localhost:8010) or access the mysql CLI using
`docker exec -it wsmysql mysql --user=laravel --password=laravel laravel`

## Manage Client Credentials Grant Tokens

The CLI is your interface.

- `php artisan client:list` lists all clients
- `php artisan client:add <name> (--root-group=<id>)...` adds new clients.
  You may add multiple root groups while repeating the option (speed things up using
  the `-g` shorthand).
- `php artisan client:delete <client-id>` deletes your client. Add multiple
  client_ids separated by a space to delete several clients at a time.
- `php artisan client:edit <client-id> [--name=<new-name>] [--root-group=<id>]...`
  updates your client. If you provide any group ids, the client is linked to only
  the given groups. Earlier assignments that are not in the list, are removed.

### Exchange Secret for Token

```
curl -X POST \
	-F "grant_type=client_credentials" \
	-F "client_id=%client_id%" \
	-F "client_secret=%client_secret%" \
	-F "scope=" \
	https://%mydomain.tld%/oauth/token
```
