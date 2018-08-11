# Webling Service

**UNDER DEVELOPEMENT**

This project aims to add some crucial but missing functionality to Webling,
while using Weblings RESTful API and exposing a new, higher lever RESTful
API. It is based on the fabulous [Laravel](https://laravel.com/) Framework
to speed up the development. Check out the [Docs](https://laravel.com/docs/5.6)
and start contributing 😍.

## Contributing ...
... is cool, simple and helps to make the 🌍 a better place 🤩
1. Install [Docker](https://store.docker.com/search?offering=community&type=edition)
1. Start docker
1. Create a folder and `cd` into it
1. Clone this repo (`git clone https://github.com/grueneschweiz/weblingservice.git`)
1. Execute `docker-compose -f docker-compose.install.yml up` and have a ☕️ while it installs
1. Execute `docker-compose up -d` to start up the stack
1. After a few seconds: Visit [localhost:8000](localhost:8000)

### Docker Cheat Sheet
- Install: `docker-compose -f docker-compose.install.yml up`
- Start up: `docker-compose up -d`
- Shut down: `docker-compose down`
- Execute Laravel CLI commands: `docker exec -it wsapp bash`
- Add dependency using composer: `docker-compose -f docker-compose.install.yml 
run composer composer require DEPENDENCY` (yes, `composer composer` is correct,
the first one defines the container to start the second one is the command to
execute)
- Add dependency from npm: `docker-compose -f docker-compose.install.yml 
run node npm --install DEPENDENCY` (You may want to use --save or --save-dev as
well. Check out the [Docs](https://docs.npmjs.com/cli/install).)

### Tooling
#### Mailhog
All mail you send out of the application will be caught by [Mailhog](localhost:8020)

#### MySQL
Use the handy [phpMyAdmin](localhost:8010) or access the mysql CLI using
`docker exec -it wsmysql mysql --user=laravel --password=laravel laravel` 

#### Laravel mix
Works out of the box ☺️

#### NPM
Access the watching container using `docker exec -it wsnode bash`
