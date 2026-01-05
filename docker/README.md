# k1 Docker

Build:
  docker build -t k1 .

Run:
  docker run -p 8080:8080 -e K1_API_TOKEN=secret k1
