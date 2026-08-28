#!/usr/bin/env sh
set -eu

cd /opt/livewire_media

echo "🔄 Handling host environment setup..."
if [ ! -f .env ]; then
    if [ -f .env.production.example ]; then
        cp .env.production.example .env
    else
        touch .env
    fi
fi

#  Inject DB variables passed down by GitHub secrets
echo "⚙️ Injecting app key, database configurations securely..."
sed -i "s|^APP_KEY=.*|APP_KEY=$APP_KEY|" .env
sed -i "s|^DB_HOST=.*|DB_HOST=$DB_HOST|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env
sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD|" .env

#  Install AWS CLI on VPS if it doesn't exist
if ! command -v aws >/dev/null 2>&1; then
    echo "📦 Installing AWS CLI on VPS host..."
    sudo apt-get update && sudo apt-get install -y unzip curl
    curl "https://amazonaws.com" -o "awscliv2.zip"
    unzip -q awscliv2.zip
    sudo ./aws/install --update
    rm -rf aws awscliv2.zip
fi

echo "🔐 Authenticating VPS Docker client with AWS ECR..."
export AWS_ACCESS_KEY_ID="$AWS_ACCESS_KEY_ID"
export AWS_SECRET_ACCESS_KEY="$AWS_SECRET_ACCESS_KEY"
export AWS_DEFAULT_REGION="$AWS_REGION"

aws ecr get-login-password --region "$AWS_REGION" | sudo docker login --username AWS --password-stdin "$AWS_ECR_REGISTRY"

echo " Pulling newest production images from AWS ECR..."
sudo ECR_REGISTRY="$AWS_ECR_REGISTRY" docker compose -f docker-compose.dev.yml --env-file .env pull

echo " Restarting application containers..."
sudo ECR_REGISTRY="$AWS_ECR_REGISTRY" docker compose -f docker-compose.dev.yml --env-file .env up -d --remove-orphans

echo " Running migrations inside active container..."
sudo ECR_REGISTRY="$AWS_ECR_REGISTRY" docker compose -f docker-compose.dev.yml --env-file .env exec -T app php artisan migrate --force

echo " Cleaning up old image layers..."
sudo docker image prune -f

echo "🎉 AWS ECR Deployment completed successfully!"
