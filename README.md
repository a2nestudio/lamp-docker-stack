# Stack WordPress Docker

Setup básico para trabajar LAMP con Docker, incluyendo tema personalizado con API REST.

## 🌐 Servicios Disponibles

- **WordPress** -> `localhost:8080`
- **MySQL** -> `localhost:33061`
- **phpMyAdmin** -> `localhost:8081`

## ⚡ Inicio Rápido

### 1. Levantar servicios

```bash
docker compose up -d
```

### 2. Verificar estado

```bash
docker compose ps
```

Ver archivo `docker-compose.yml` para configuraciones detalladas.

## 🔧 Configuraciones Importantes

### Verificar y cambiar usuario

Cambiar usuario de la carpeta asociada al volumen "wp", el volumen en linux lo crea Apache con el usuario "www-data". Esto es para poder modificar "wordpress".

- **Verificar id**: `usuario@:~$ id` -> retorna los ID de usuario y grupo
- **Cambiar propietario**: `sudo chown -R {el_id} ./wp` -> wp es el volumen creado
- **Si se requiere**, cambiar permisos dentro del contenedor: `root@contenedor:/# chmod 777 -R /var/www/html`

### Configuración inicial de WordPress

1. Acceder a `http://localhost:8080`
2. Completar instalación de WordPress
3. **Importante**: Configurar permalink como "Nombre entrada"
4. Activar tema "dr"

### API del tema "dr"

- **URL base**: `/index.php/wp-json/api/v1/`
- **Endpoints disponibles**:
  - `GET /pages/{slug}` - Obtener página por slug
  - `GET /evento/{slug}` - Obtener evento por slug
  - `GET /evento/activo` - Obtener evento activo

## 📁 Estructura del Proyecto

```plain
PHP-WORDPRESS-STACK-DOCKER/
├── docker-compose.yml          # Configuración de servicios
├── wp/                         # Archivos WordPress
│   └── wp-content/
│       └── themes/
│           └── dr/             # Tema personalizado
│               ├── functions.php
│               ├── style.css
│               └── scripts/
│                   ├── API_functions.php  # API REST personalizada
│                   └── Utils.php         # Funciones auxiliares
└── README.md
```

## 🛠️ Comandos Útiles

### Docker

```bash
# Detener servicios
docker compose down

# Ver logs
docker compose logs -f

# Acceder al contenedor WordPress
docker exec -it wp_general bash

# Acceder al contenedor MySQL
docker exec -it db_general mysql -u admin -p
```

### Base de Datos

- **Host**: `db` (desde WordPress) / `localhost:33061` (desde host)
- **Usuario**: `admin`
- **Contraseña**: `pass`
- **Base de datos**: `wp_db`

## 🔍 Solución de Problemas

### Error de permisos en archivos

```bash
sudo chown -R $(id -u):$(id -g) ./wp
```

### API no funciona

1. Verificar permalinks en WordPress (Configuración → Enlaces permanentes)
2. Verificar que el tema "dr" esté activo
3. Comprobar que los archivos del tema estén presentes

### Reiniciar servicios

```bash
docker compose restart
```
