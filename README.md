# Portal de proveedores de Suministros Cerro Verde S.A.

Laboratorio web de la semana 12 del curso CIB-203 Seguridad Defensiva y Ofensiva. Es el portal de proveedores de Suministros Cerro Verde S.A., una empresa ficticia. Todos los nombres, empresas, instituciones y datos del portal son sintéticos.

## Aviso de uso

* Esta aplicación es vulnerable a propósito. Úsela solo con fines educativos y solo en su propia máquina virtual.
* El laboratorio publica sus puertos únicamente en `127.0.0.1`. No lo exponga a ninguna red ni lo instale en un servidor.
* Practique únicamente contra este laboratorio. Atacar sistemas sin autorización es un delito.

## Requisitos

* Kali Linux con `docker.io` y `docker-compose` (Compose v2, que se usa como `docker compose`).

  ```bash
  sudo apt update
  sudo apt install -y docker.io docker-compose
  sudo usermod -aG docker "$USER"   # luego cierre sesión y vuelva a entrar
  ```

* 6 GB de RAM y 10 GB de disco libres.

## Instalación

```bash
git clone https://github.com/wrodriguez-cenfotec/webchivo.git
cd webchivo
cp .env.example .env
bash scripts/check-ghcr-images.sh
docker compose pull
docker compose up -d
```

Agregue esta línea a `/etc/hosts`:

```
127.0.0.1 proveedores.lab
```

Luego abra `http://proveedores.lab:8080` en el navegador.

Si `check-ghcr-images.sh` muestra `NOT FOUND OR INACCESSIBLE`, compruebe el valor de `IMAGE_TAG` en `.env` y los tags publicados en GitHub Packages. Un workflow exitoso sobre `main` publica `latest` y un tag `sha-*`, pero no crea por sí mismo una versión `v*`. No es necesario reconstruir imágenes locales para resolver un tag inexistente.

¿El portal no carga en el primer minuto? La base de datos se inicializa en el primer arranque. Espere un poco y revise el estado con `docker compose ps`.

## Cambiar el nivel

El laboratorio tiene dos niveles:

* `vulnerable`: el nivel de práctica.
* `hardened`: el mismo portal con contramedidas, para comprobar que funcionan.

Cambie el valor de `APP_LEVEL` en `.env` y aplique el cambio:

```bash
docker compose up -d
```

No hace falta reconstruir ni descargar nada de nuevo, y las banderas no cambian. El nivel activo aparece en la parte superior de cada página.

## Registros

```bash
docker compose logs -f app
```

Muestra el registro de acceso de Apache, útil para analizar las solicitudes desde el punto de vista defensivo.

## Eliminar todo

```bash
docker compose down -v
```

Elimina los contenedores, la red y el volumen de la base de datos. El siguiente `docker compose up -d` parte de cero.

## Sección para mantenimiento

Esta sección es para el personal docente que publica las imágenes.

### Requisitos adicionales

* `docker-buildx`, para construir las imágenes con el secreto de las banderas.
* `gh`, la CLI de GitHub, para cargar los secretos.

### Banderas

Las banderas reales viven solo en el archivo `.env` local del personal docente. Agregue una línea por bandera:

```
FLAG_01=<valor de la bandera 1>
FLAG_02=<valor de la bandera 2>
FLAG_03=<valor de la bandera 3>
FLAG_04=<valor de la bandera 4>
FLAG_05=<valor de la bandera 5>
FLAG_06=<valor de la bandera 6>
FLAG_07=<valor de la bandera 7>
FLAG_08=<valor de la bandera 8>
```

* Cada valor es exactamente el que se carga en el sistema de puntuación del CTF.
* Cada valor tiene entre 8 y 64 caracteres, solo letras, dígitos, `_`, `-`, `{` y `}`, sin espacios ni comillas.
* **El archivo `.env` con banderas nunca se sube al repositorio ni se comparte.**

### Secretos de GitHub

```bash
gh secret set -f .env --repo <propietario>/<repositorio>
base64 -w0 tests/smoke.sh | gh secret set SMOKE_TESTS --repo <propietario>/<repositorio>
```

El primer comando crea un secreto por cada línea de `.env`, incluidas las variables de ejecución como `APP_LEVEL`. Eso no causa problemas, porque el flujo de trabajo solo usa `FLAG_01` a `FLAG_08`.

### Archivos locales

`tests/smoke.sh` y la carpeta `spec/` (especificación y soluciones) se guardan solo en la máquina local, están en `.gitignore` y nunca se suben al repositorio.

### Construcción local

```bash
docker compose -f compose.yml -f compose.build.yml build --no-cache
docker compose -f compose.yml -f compose.build.yml up -d
```

La construcción lee las banderas desde `.env`. Sin líneas `FLAG_0N` usa banderas de prueba. Use siempre `--no-cache` después de cambiar las banderas.

### Publicación

1. Revise que `GHCR_OWNER` e `IMAGE_TAG` de `.env.example` sean correctos.
2. Si se va a publicar una versión `v*`, cree y suba una etiqueta igual a `IMAGE_TAG` (el ejemplo usa `v1.0.0`; no reutilice un tag existente):

   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```

3. Cuando termine el flujo de trabajo, haga públicos los tres paquetes en GitHub Packages: `cib203-lab-web-app`, `cib203-lab-web-db` y `cib203-lab-web-reviewer`.

### Verificación antes de la clase

En un clon limpio, copie `.env.example` a `.env` y compruebe que las referencias distribuidas a los estudiantes son descargables:

```bash
cp .env.example .env
bash scripts/check-ghcr-images.sh
docker compose pull
```

Si se modifica el tag recomendado, actualice `.env.example` **solo después** de confirmar la publicación de los tres manifiestos. Esta comprobación valida disponibilidad, no contenido ni banderas.

```bash
seal/verify_image.sh
seal/check_leaks.sh
```

El primero confirma que las imágenes publicadas contienen exactamente las banderas de `.env`. El segundo confirma que ningún valor aparece en el repositorio, en su historial ni en el historial de las imágenes. Ambos imprimen solo líneas de estado, nunca los valores.
