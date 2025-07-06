Here's a comprehensive solution to deploy multiple apps (Flask, Laravel, Express.js) using Docker, Apache reverse proxy, and GitHub Actions CI/CD:

### 1. Directory Structure
```
~/home/root/apps/
├── flask-app/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── .env.prod
│   └── ... (app code)
├── laravel-app/
│   ├── Dockerfile
│   ├── docker-compose.yml
│   ├── .env.prod
│   └── ... (app code)
└── express-app/
    ├── Dockerfile
    ├── docker-compose.yml
    ├── .env.prod
    └── ... (app code)
```

### 2. Docker Setup for Each App

**Flask App (`flask-app/Dockerfile`):**
```Dockerfile
FROM python:3.10-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
CMD ["gunicorn", "-b", "0.0.0.0:5000", "app:app"]
```

**Laravel App (`laravel-app/Dockerfile`):**
```Dockerfile
FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage
```

**Express App (`express-app/Dockerfile`):**
```Dockerfile
FROM node:18-alpine
WORKDIR /app
COPY package*.json .
RUN npm install --production
COPY . .
EXPOSE 3000
CMD ["node", "server.js"]
```

### 3. Docker Compose Files

**Flask App (`flask-app/docker-compose.yml`):**
```yaml
version: '3.8'
services:
  web:
    build: .
    container_name: flask_app
    ports:
      - "127.0.0.1:5000:5000"
    env_file:
      - .env.prod
    restart: always
```

**Laravel App (`laravel-app/docker-compose.yml`):**
```yaml
version: '3.8'
services:
  web:
    build: .
    container_name: laravel_app
    ports:
      - "127.0.0.1:8000:80"
    volumes:
      - ./:/var/www/html
    env_file:
      - .env.prod
    restart: always
```

**Express App (`express-app/docker-compose.yml`):**
```yaml
version: '3.8'
services:
  web:
    build: .
    container_name: express_app
    ports:
      - "127.0.0.1:3000:3000"
    env_file:
      - .env.prod
    restart: always
```

### 4. Apache Reverse Proxy Setup
Enable required modules:
```bash
sudo a2enmod proxy proxy_http ssl
sudo systemctl restart apache2
```

**Create Virtual Hosts (`/etc/apache2/sites-available/`):**

1. `flask.example.com.conf`:
```apache
<VirtualHost *:80>
    ServerName flask.example.com
    ProxyPass / http://localhost:5000/
    ProxyPassReverse / http://localhost:5000/
</VirtualHost>
```

2. `laravel.example.com.conf`:
```apache
<VirtualHost *:80>
    ServerName laravel.example.com
    ProxyPass / http://localhost:8000/
    ProxyPassReverse / http://localhost:8000/
</VirtualHost>
```

3. `express.example.com.conf`:
```apache
<VirtualHost *:80>
    ServerName express.example.com
    ProxyPass / http://localhost:3000/
    ProxyPassReverse / http://localhost:3000/
</VirtualHost>
```

Enable sites:
```bash
sudo a2ensite flask.example.com.conf
sudo a2ensite laravel.example.com.conf
sudo a2ensite express.example.com.conf
sudo systemctl reload apache2
```

### 5. GitHub Actions CI/CD

**Flask App CI/CD (`.github/workflows/flask-cd.yml`):**
```yaml
name: Flask App Deployment

on:
  push:
    branches: [main]
    paths: 'flask-app/**'

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
    - name: Checkout
      uses: actions/checkout@v3

    - name: Docker Login
      uses: docker/login-action@v2
      with:
        username: ${{ secrets.DOCKERHUB_USERNAME }}
        password: ${{ secrets.DOCKERHUB_TOKEN }}

    - name: Build and Push Image
      run: |
        cd flask-app
        docker build -t ${{ secrets.DOCKERHUB_USERNAME }}/flask-app:latest .
        docker push ${{ secrets.DOCKERHUB_USERNAME }}/flask-app:latest

    - name: Deploy to Server
      uses: appleboy/ssh-action@v1
      with:
        host: ${{ secrets.SERVER_IP }}
        username: root
        key: ${{ secrets.SSH_PRIVATE_KEY }}
        script: |
          cd ~/home/root/apps/flask-app
          docker-compose pull
          docker-compose up -d
```

**Note:** Create similar workflows for Laravel and Express apps (adjust paths and image names).

### 6. Environment Configuration

**Example `.env.prod` (for all apps):**
```ini
# MySQL Configuration (use remote DB)
DB_HOST=your-remote-db.com
DB_PORT=3306
DB_USER=prod_user
DB_PASSWORD=secure_password
DB_NAME=app_prod
```

### 7. Deployment Workflow

1. Push code to GitHub
2. GitHub Actions builds Docker image
3. Image pushed to Docker Hub
4. SSH into server pulls new image
5. Docker Compose restarts container

### 8. Security Best Practices

1. **SSH Security:**
   ```bash
   # On server:
   sudo nano /etc/ssh/sshd_config
   # Change Port, disable root login, use key auth only
   ```

2. **Firewall Rules:**
   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw allow 22/tcp
   sudo ufw enable
   ```

3. **HTTPS Setup (Certbot):**
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d flask.example.com
   # Repeat for other domains
   ```

### 9. Management Commands

**Start all apps:**
```bash
cd ~/home/root/apps
find . -name docker-compose.yml -execdir docker-compose up -d \;
```

**View logs:**
```bash
docker logs flask_app
docker logs laravel_app
docker logs express_app
```

### Key Advantages:
1. **Isolated Containers:** Each app runs in separate containers
2. **Zero Downtime:** `docker-compose up -d` updates without downtime
3. **Secure:** Only Apache exposed on port 80/443
4. **Automated:** CI/CD handles testing and deployment
5. **Scalable:** Add new apps by replicating the structure

### Troubleshooting Tips:
1. Check Apache error logs: `journalctl -u apache2 -f`
2. Verify container status: `docker ps -a`
3. Test port binding: `curl localhost:5000` (from server)
4. Verify DNS resolution for subdomains

This setup provides a secure, scalable foundation for deploying multiple web applications with automated CI/CD pipelines.