# CSV Import via Queue

Small API that accepts a CSV upload of Users, processes it asynchronously via a queued job, and exposes a status endpoint to track progress 

## Laravel and PHP versions

* Laravel 12 (Laravel doesn't have proper LTS, the idea is be up to date always if possible)
* PHP 8.4 (Laravel 12 allows PHP version between 8.2 - 8.4)

## Setup

### Requirements
PHP 8.4, Composer 2, common php extensions.

### Installation
1. Install dependencies:
   ```bash
   composer install
   ```

2. Copy the example environment file:
   ```bash
   cp .env.example .env
   ```

3. Generate the application key:
   ```bash
   php artisan key:generate
   ```
   
4. Run the migrations::
   ```bash
   php artisan migrate
   ```

### Run the server and the queue worker

1. Serve the application:
   ```bash
   php artisan serve
   ```

2. Run the queue worker (on another cli tab)
   ```bash
   php artisan queue:work
   ```
