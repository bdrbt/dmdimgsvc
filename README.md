## Image service 

Service for upload and store user images.

### Reuqirements

- docker
- gnu make (optional)

### Using make file
```bash
$ make 
help                      Shows available subcommands
up                        Run docker stack
down                      Remove docker stack
test                      Run all tests
test-pest                 Run pest tests locally
swagger                   Generate OpenAPI/Swagger documentation
clear-cache               Clear all caches
rebuild                   Rebuild app container image
```

### Running
```bash
docker compose up -d 
```

### Rebuild
```bash
docker compose up -d --build
```

### Run k6 tests
```bash
docker compose --profile testing run --rm -e BASE_URL="http://web:80" k6
```

### Swagger docs
Navigate to http://localhost:8080/api/documentation
