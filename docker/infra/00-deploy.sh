#!/usr/bin/env sh
set -eu

cd /opt/livewire-pos

echo "🔄 Handling host environment setup..."
if [ ! -f .env ]; then
    if [ -f .env.production.example ]; then
        cp .env.production.example .env
    else
        touch .env
    fi
fi

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

echo " Generating key ..."
sudo ECR_REGISTRY="$AWS_ECR_REGISTRY" docker compose -f docker-compose.dev.yml --env-file .env exec -T app php artisan key:generate

echo " Running migrations inside active container..."
sudo ECR_REGISTRY="$AWS_ECR_REGISTRY" docker compose -f docker-compose.dev.yml --env-file .env exec -T app php artisan migrate --force

echo " Cleaning up old image layers..."
sudo docker image prune -f

echo "🎉 AWS ECR Deployment completed successfully!"
