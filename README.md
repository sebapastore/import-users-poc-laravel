# CSV Import via Queue

Small API that accepts a CSV upload of Users, processes it asynchronously via a queued job, and exposes a status endpoint to track progress 

## Laravel and PHP versions

* Laravel 12 (Laravel doesn't have proper LTS, the idea is be up to date always if possible)
* PHP 8.4 (Laravel 12 allows PHP version between 8.2 - 8.4)

## Local Setup

### Requirements
PHP 8.4, Composer 2, common php extensions.

### Installation


1. Install dependencies:
   ```bash
   composer install
   ```

2. Copy the example environment file. **This will set a default `API_TOKEN=123456` that will be used as a fixed token for the API auth**. You can update the value if you want but you will have to take that into account when using the tests examples:
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

### Test the endpoints (TODO)

On another cli tab:

> For easier inspection of JSON output in the terminal, you can pipe your `curl` request to `jq`:


1. Test POST /api/import with a valid CSV
```bash
curl -X POST http://localhost:8000/api/imports \
-H "Authorization: Bearer 123456" \
-H "Accept: application/json" \
-F "file=@storage/app/test/users_valid.csv"
```

2. Test POST /api/import with a CSV containing some row errors
```bash
curl -X POST http://localhost:8000/api/imports \
-H "Authorization: Bearer 123456" \
-H "Accept: application/json" \
-F "file=@storage/app/test/users_mixed.csv"
```

3. Test POST /api/import with a CSV that has extra columns at the end
```bash
curl -X POST http://localhost:8000/api/imports \
-H "Authorization: Bearer 123456" \
-H "Accept: application/json" \
-F "file=@storage/app/test/users_edge.csv"
```

4. Test POST /api/import with a CSV that has wrong headers
```bash
curl -X POST http://localhost:8000/api/imports \
-H "Authorization: Bearer 123456" \
-H "Accept: application/json" \
-F "file=@storage/app/test/users_header_error.csv"
```

5. Test GET /api/import/{id} with the returned import_id from POST /api/import
```bash
curl -H "Authorization: Bearer 123456" \
-H "Accept: application/json" \
-X GET http://localhost:8000/api/imports/{id}
```

6. Test GET /api/users/
```bash
curl -X GET http://localhost:8000/api/users \
-H "Authorization: Bearer 123456" \
-H "Accept: application/json"
```

## Considerations

* Authentication uses a fixed token. This is not what I would choose for production, but it keeps things simple for this exercise.
* Uploaded files are stored locally. Ideally, they should go to a Storage bucket.
* CSV headers must be in a specific order for simplicity.
* The current implementation has memory limitations. For large datasets (hundreds of thousands or millions of rows), I would implement a batch system with jobs that process chunks of rows (100–1000 each), passing only a specific file pointer and row count to each job.
* Ideally, I would set up Git hooks and a CI pipeline with GitHub Workflows to run tests, static analysis at a reasonable level, and code formatting.
* Default values are set in the business logic rather than at the database level. I find this often makes the code clearer.
* I chose to use `{"row": null, "message": "some error message"}` for generic errors not tied to a specific row.
* In a real-world application, salaries might be stored as integers with proper conversion, possibly using a Value Object.
* Testing: I included some tests for specific classes to demonstrate different approaches. The coverage is not sufficient for a real project, but it shows intent.
