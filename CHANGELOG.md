# Upgrade from Laravel 5.8 to 8.0
- `docker-compose pull`
- `docker-compose build`
- `docker-compose -f docker-compose.install.yml run composer composer install --ignore-platform-reqs`
- `docker-compose run weblingservice php arisan migrate`

