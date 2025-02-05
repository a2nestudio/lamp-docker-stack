# Repositorio

Setup básico para trabajar lamp con docker.

- wordpress -> `localhost:8080`
- mysql -> `localhost:33061`
- phpmyadmin -> `localhost:8081`

## Configuraciones

`docker compose up -d`

Ver archivo docker-compose.yml

### Verificar y cambiar usuario

Cambiar usuario de la carpeta asociada al volumen "wp", el volumen en linux lo crea Apache con el usuario "www-data". Esto es para poder modificar "wordpress".

- Verificar id: `usuario@:~$ id` -> retorna los ID de usuario y grupo
- Cambiar: `sudo chown -R {el_id} ./wp` -> wp es el volumen creado

  