
group "default" {
  targets = ["php", "nginx"]
}

target "_common" {
  context    = "."
  dockerfile = "docker/Dockerfile"
  pull       = true
}

target "php" {
  inherits = ["_common"]
  target   = "php"
}

target "nginx" {
  inherits = ["_common"]
  target   = "nginx"
}
