# Webling Service

[![Build Status](https://travis-ci.com/grueneschweiz/weblingservice.svg?branch=master)](https://travis-ci.com/grueneschweiz/weblingservice)
[![Coverage Status](https://coveralls.io/repos/github/grueneschweiz/weblingservice/badge.svg)](https://coveralls.io/github/grueneschweiz/weblingservice)

**UNDER DEVELOPEMENT**

This project aims to add some crucial but missing functionality to Webling,
while using Weblings RESTful API and exposing a new, higher lever RESTful
API. It is based on the fabulous [Laravel](https://laravel.com/) framework
to speed up the development. Check out the [docs](https://laravel.com/docs/5.7)
and start contributing 😍.

## Contributing ...
... is cool, simple and helps to make the 🌍 a better place 🤩
1. Install [docker](https://store.docker.com/search?offering=community&type=edition)
1. Start docker
1. Clone this repo `git clone https://github.com/grueneschweiz/weblingservice.git`
1. `cd` into the folder containing the repo
1. Execute `docker-compose -f docker-compose.install.yml up` and have a ☕️ while 
it installs. `wsnode_install_webling` and `wscomposer_install_webling` should exit with `code 0`.
1. Execute `docker-compose up -d` to start up the stack. The first time you run
   this command, it will take a minute or two. Subsequent calls will be much faster.
1. Execute `docker exec wsapp cp .env.travis .env` to get an instance of the environment variables
1. Execute `docker exec wsapp php artisan key:generate` to generate the app secrets
1. Execute `docker exec wsapp php artisan migrate` to initialise the database tables
1. Execute `docker exec wsapp php artisan passport:install` to setup oAuth2

Yupii, you're nearly done. Just add the `WEBLING_API_KEY` and `WEBLING_BASE_URL`
to the `.env` file and your ready to go. From now on, you can just start up the
stack with a single `docker-compose up -d`, without repeating all the commands
from above.

### Docker Cheat Sheet
- Install: `docker-compose -f docker-compose.install.yml up`
- Start up: `docker-compose up -d`
- Shut down: `docker-compose down`
- Execute Laravel CLI commands (enter container): `docker exec -it wsapp_webling bash` use `exit` to escape the container.
- Add dependency using composer: `docker-compose -f docker-compose.install.yml 
run composer composer require DEPENDENCY` (yes, `composer composer` is correct,
the first one defines the container to start the second one is the command to
execute)
- Add dependency from npm: `docker-compose -f docker-compose.install.yml 
run node npm --install DEPENDENCY` (You may want to use --save or --save-dev as
well. Check out the [Docs](https://docs.npmjs.com/cli/install).)

### Tooling
#### Mailhog
All mail you send out of the application will be caught by [Mailhog](http://localhost:8020)

#### MySQL
Use the handy [phpMyAdmin](http://localhost:8010) or access the mysql CLI using
`docker exec -it wsmysql mysql --user=laravel --password=laravel laravel` 

#### Laravel mix
Works out of the box ☺️

#### NPM
Access the watching container using `docker exec -it wsnode bash`

## Consuming the API
### Authentication
The API is secured with OAuth2. Use the client credentials flow to authenticate yourself.
To do so send a `POST` request to the `/oauth/token` endpoint containing the following
data (replace the `%values%` with your credentials).
```JSON
{
  "grant_type"    : "client_credentials",
  "client_id"     : "%client-id%",
  "client_secret" : "%client-secret%",
  "scope"         : ""
}
```
The Webling Service will respond with the access token. You may now access the
protected api endpoints adding the token to your request header. The header field
must satisfy the following form.
```
Authorization: Bearer %token%
```

## Manage Client Credentials Grant Tokens
The CLI is your interface.
- `php artisan client:list` lists all clients
- `php artisan client:add <name> (--root-group=<id>)...` adds new clients.
You may add multiple root groups while repeating the option (speed thins up using
the `-g` shorthand.
- `php artisan client:delete <client-id>` deletes your client. Add multiple
client_ids separated by a space to delete several clients at a time.
- `php artisan client:edit <client-id> [--name=<new-name>] [--root-group=<id>]...`
updates your client. If you provide any group ids, the client is linked to only
the given groups. Earlier assignments that are not in the list, are removed.
