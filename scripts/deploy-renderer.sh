#!/bin/bash
# ============================================
# Deploy Remotion Renderer to Cloud Run
# ============================================
# 
# "Budget Guard" Configuration:
# - 4GB RAM, 2 CPU (minimum for reliable Remotion + Chrome)
# - min-instances=0 (zero idle cost)
# - max-instances=1 (impossible to have surprise bills)
# - concurrency=1 (no competing renders)
# - us-central1 (free tier region)
#
# Prerequisites:
# - gcloud CLI installed and authenticated
# - Docker installed (for local building)
# - Run ./scripts/setup-gcp.sh first
#
# Usage:
#   ./deploy-renderer.sh

set -e

# Load configuration if exists
if [ -f .gcp-config ]; then
    source .gcp-config
fi

# Configuration
PROJECT_ID="${PROJECT_ID:-$(gcloud config get-value project 2>/dev/null)}"
REGION="${REGION:-us-central1}"
SERVICE_NAME="remotion-renderer"
IMAGE_NAME="gcr.io/$PROJECT_ID/$SERVICE_NAME"

# Required environment variables from .env
CALLBACK_URL="${CALLBACK_URL:-https://invitationvideos.com/api/renders/receive-video.php}"
STATUS_URL="${STATUS_URL:-https://invitationvideos.com/api/remotion/update-order.php}"
RENDERER_SECRET_KEY="${RENDERER_SECRET_KEY:-rmtn_render_secret_key}"

if [ -z "$PROJECT_ID" ]; then
    echo "❌ Error: No project ID specified"
    echo "Run ./scripts/setup-gcp.sh first or set PROJECT_ID"
    exit 1
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║   🚀  DEPLOYING REMOTION RENDERER                            ║"
echo "╠═══════════════════════════════════════════════════════════════╣"
echo "║   Project: $PROJECT_ID"
echo "║   Region:  $REGION"
echo "║   Service: $SERVICE_NAME"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Navigate to renderer directory
cd "$(dirname "$0")/../renderer"

# Build Docker image
echo "🐳 Step 1: Building Docker image..."
docker build -t "$IMAGE_NAME" .
echo "   ✅ Image built"

# Push to Container Registry
echo ""
echo "📤 Step 2: Pushing to Container Registry..."
docker push "$IMAGE_NAME"
echo "   ✅ Image pushed"

# Deploy to Cloud Run with "Budget Guard" settings
echo ""
echo "🚀 Step 3: Deploying to Cloud Run..."
echo "   Using 'Budget Guard' configuration for cost optimization"

gcloud run deploy "$SERVICE_NAME" \
    --image "$IMAGE_NAME" \
    --platform managed \
    --region "$REGION" \
    --memory 4Gi \
    --cpu 2 \
    --concurrency 1 \
    --timeout 15m \
    --min-instances 0 \
    --max-instances 1 \
    --allow-unauthenticated \
    --set-env-vars="CALLBACK_URL=$CALLBACK_URL,STATUS_URL=$STATUS_URL,RENDERER_SECRET_KEY=$RENDERER_SECRET_KEY" \
    --quiet

echo "   ✅ Deployed"

# Get the service URL
SERVICE_URL=$(gcloud run services describe "$SERVICE_NAME" --region "$REGION" --format="value(status.url)")

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║   ✅  DEPLOYMENT COMPLETE                                     ║"
echo "╠═══════════════════════════════════════════════════════════════╣"
echo "║                                                               ║"
echo "║   Service URL: $SERVICE_URL"
echo "║                                                               ║"
echo "║   Budget Guard Settings Applied:                              ║"
echo "║   ├── Region: us-central1 (Free Tier ✓)                      ║"
echo "║   ├── Memory: 4GiB (Reliable for Remotion+Chrome)            ║"
echo "║   ├── CPU: 2 (Faster renders, still cost-effective)          ║"
echo "║   ├── Concurrency: 1 (No competing renders)                  ║"
echo "║   ├── Min instances: 0 (Zero idle cost)                      ║"
echo "║   └── Max instances: 1 (No surprise bills!)                  ║"
echo "║                                                               ║"
echo "║   Free Tier Capacity: ~1,000 videos/month                    ║"
echo "║                                                               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 Next steps:"
echo "   1. Add this to your PHP .env file:"
echo "      CLOUD_RUN_URL=$SERVICE_URL"
echo ""
echo "   2. Test the health endpoint:"
echo "      curl $SERVICE_URL/health"
echo ""

# Save service URL
echo "CLOUD_RUN_URL=$SERVICE_URL" >> ../.gcp-config
