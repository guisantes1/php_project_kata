# PHP Project Kata HF

Este es un proyecto en PHP para ejecutar con Docker, sin otras necesidades de tener instalado otros paquetes en su sistema. Las instrucciones están pensadas para usuarios de **Mac (Intel y Apple Silicon)**, **Windows**, y **Linux**.

---

## 🧰 Requisitos

- Tener instalado **Docker Desktop** o Docker Engine.
- Acceso a una terminal: Terminal (macOS), PowerShell/CMD (Windows), o shell (Linux).

---

## 🐳 ¿No tiene Docker instalado? Siga estos pasos:

### 🔹 Mac

1. Vaya a: https://www.docker.com/products/docker-desktop  
2. Descargue la versión correspondiente:
   - Apple chip (M1, M2, M3): **Download for Mac (Apple chip)**
   - Intel: **Download for Mac (Intel chip)**
3. Abra el `.dmg`, arrastre Docker a Aplicaciones y ejecútelo.
4. Asegúrese de ver el ícono de la ballena en la barra superior.
5. Verifique la instalación ejecutando en su terminal:

   ```bash
   docker --version
   docker compose version
   ```

---

### 🔹 Windows

1. Descargue Docker desde: https://www.docker.com/products/docker-desktop  
2. Instálelo y sigua las instrucciones que le pueda pedir (puede pedirle activar WSL2).
3. Reinicie si lo requiere.
4. Abra PowerShell y pruebe:

   ```powershell
   docker --version
   docker compose version
   ```

---

### 🔹 Linux (Ubuntu/Debian)

Abra la terminal y ejecute:

```bash
sudo apt update
sudo apt install docker.io docker-compose-plugin
sudo systemctl start docker
sudo systemctl enable docker
```

Verifique su instalación:

```bash
docker --version
docker compose version
```

Si no tiene permisos para ejecutar Docker como usuario normal:

```bash
sudo usermod -aG docker $USER
# Luego cierre sesión y vuelva a entrar
```

---

## 🚀 Levantar el entorno del proyecto

### 1. Clone este repositorio

```bash
git clone https://github.com/guisantes1/php_project_kata
cd php_project_kata
```

### 2. Levantar el servidor con Docker

#### Si usa Docker moderno (20.10+):

```bash
docker compose up --build
```

#### Si usa una versión antigua:

```bash
docker-compose up --build
```

> ⚠️ Si ve un error tipo `command not found`, pruebe con `docker compose` (sin guión) y asegúrese de que Docker esté correctamente instalado y corriendo.

---

## 🌍 Acceder a la aplicación

Abra su navegador en:

```
http://localhost:8080
```

Verá la salida de `index.php`. En él encontrará el proceder de la tarea adjunta en este [PDF](Kata%20-%20Mars%20Rover%20Mission.pdf).

---

## 📁 Estructura esperada del proyecto

```
php_project_kata/
├── Dockerfile                    # Define cómo construir el entorno PHP del proyecto
├── docker-compose.yml            # Levanta el proyecto con Docker (PHP + servidor web)
├── index.php                     # Página principal: interfaz y lógica de ejecución
├── utils.php                     # Funciones para validar datos y simular los rovers
├── download.php                  # Genera un PDF con los resultados del análisis
├── script.js                     # Lógica de frontend: drag & drop, formularios, reset
├── styles.css                    # Estilos visuales del sitio (colores, fuentes, etc.)
├── entrada_planeta.txt           # Ejemplo de entrada para probar el simulador
├── Kata - Mars Rover Mission.pdf # Enunciado de la tarea en formato PDF
├── README.md                     # El archivo que está leyendo con la descripción del proyecto
└── fpdf/                         # Librería PHP para crear archivos PDF (FPDF)


```

---


## 🛑 Detener y limpiar

Cuando termine de trabajar, deténga el entorno en su Terminal con el comando:

```bash
docker compose down
```

