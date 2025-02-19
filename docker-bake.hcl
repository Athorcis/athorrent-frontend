
group "default" {
  targets = ["php", "nginx"]
}

target "php" {
  context = "."
  target = "php"
  dockerfile = "Dockerfile"
  tags = ["athorcis/athorrent-frontend:latest"]
}

target "nginx" {
  context = "."
  target = "nginx"
  dockerfile = "Dockerfile"
  tags = ["athorcis/athorrent-frontend-nginx:latest"]
}
