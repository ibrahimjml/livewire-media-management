# Livewire Media Management

A production-ready Media management platform handling file uploads, directory structures, and nested folders. Features dynamic media disk switching (AWS S3, Cloudflare R2, DigitalOcean Spaces, and Local storage) inspired by Botble.

---

## 🏗️ Architecture Design

This application uses  **Multi-Service Single Container** structure tailored for high performance on cost-efficient or resource-constrained VPS hosting environments.

```text
Public Internet Request (https://media.jmal.store)
       ⬇ [Port 443 / SSL Terminated]
🌐 VPS Host Nginx (Acts as a reverse proxy front door)
  
🐳 Isolated Docker Container (Listens internally on Port 8080)
       ⬇
   🎛️ Supervisord (PID 1 Process Master Monitoring Sub-processes)
       ├── 🌐 Container Nginx
       └── 🐘 PHP 8.3 FPM Engine
              ⬇
   🗄️ External Services (AWS RDS Database & AWS S3 Cloud Storage)
```

---

## 🛠️ Production Setup

### 1. Prerequisites
* **VPS Engine:** Ubuntu server instance with Docker and Docker Compose installed.
* **AWS Services:** An active AWS Account with an ECR private repository, an RDS MySQL database instance, and an IAM user accessor containing power-user registry permissions.

---

### 2. VPS Host Nginx Reverse Proxy Setup
Your host Nginx acts as the secure perimeter traffic director, routing requests into your isolated container port interface.

1. Create a server site file on your VPS at `/etc/nginx/sites-available/media.jmal.store`:
```nginx
server {
    server_name media.jmal.store;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location ~ /\. {
        deny all;
    }
}
```
2. Symlink the configuration to activate your site parameters:
```bash
sudo ln -s /etc/nginx/sites-available/media.jmal.store /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl restart nginx
```
3. Issue a secure SSL using Certbot:
```bash
sudo certbot --nginx -d media.jmal.store
```

---

### 3. GitHub Actions Environment Configuration
To run automated deployments via Amazon ECR, navigate to your repository **Settings ➔ Secrets and variables ➔ Actions** and input these parameters:

#### Repository Variables (Plaintext)
* `AWS_REGION` ➔ `us-east-1`
* `AWS_ECR_REGISTRY` ➔ `://amazonaws.com`
* `APP_NAME` ➔ `livewire_media`
* `AWS_REPOSITORY` ➔ `media/livewire-media`

---

### 4. Workspace Structure
Ensure your local project folder matches this directory layout before pushing changes to your repository:

```text
project-root/
├── .github/workflows/
│   └── deploy.yml             # CI/CD engine config
├── docker/
│   ├── DockerFile             # Multi-stage production build script
│   ├── nginx/
│   │   └── default.conf       # Inner proxy config with fastcgi;
│   ├── supervisord.conf       # Master service tracking manager block
│   └── infra/
│       └── 00-deploy.sh       # Automated VPS provisioning shell script
├── docker-compose.dev.yml     # Application orchestration template
└── .env.production.example    # Server base layout defaults
```

---

### 5. Automated CI/CD Execution Process
Once the structures above match your local files, push your codebase directly to your main tracking branch:

