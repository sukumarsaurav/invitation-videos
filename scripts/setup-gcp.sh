#!/bin/bash
# ============================================
# GCP Setup Script for Remotion Renderer
# ============================================
# 
# This script sets up the required Google Cloud resources:
# 1. Enables required APIs
# 2. Creates Cloud Tasks queue with "Bouncer" configuration
# 3. Creates service account (optional)
#
# Prerequisites:
# - gcloud CLI installed and authenticated
# - Billing enabled on your GCP project
#
# Usage:
#   ./setup-gcp.sh [PROJECT_ID]

set -e

# Configuration
PROJECT_ID="${1:-$(gcloud config get-value project 2>/dev/null)}"
REGION="us-central1"  # Free tier region!
QUEUE_NAME="render-queue"

if [ -z "$PROJECT_ID" ]; then
    echo "❌ Error: No project ID specified and none set in gcloud config"
    echo "Usage: ./setup-gcp.sh [PROJECT_ID]"
    exit 1
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║   🚀  GCP SETUP - REMOTION RENDERER                          ║"
echo "╠═══════════════════════════════════════════════════════════════╣"
echo "║   Project: $PROJECT_ID"
echo "║   Region:  $REGION (Free Tier 🎉)"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Set project
echo "📋 Setting project..."
gcloud config set project "$PROJECT_ID"

# Enable APIs
echo ""
echo "🔌 Enabling required APIs..."
gcloud services enable \
    run.googleapis.com \
    cloudtasks.googleapis.com \
    cloudbuild.googleapis.com \
    containerregistry.googleapis.com \
    --quiet

echo "   ✅ APIs enabled"

# Create Cloud Tasks queue with "Bouncer" configuration
echo ""
echo "📝 Creating Cloud Tasks queue..."

# Check if queue exists
if gcloud tasks queues describe "$QUEUE_NAME" --location="$REGION" &>/dev/null; then
    echo "   Queue already exists. Updating configuration..."
    gcloud tasks queues update "$QUEUE_NAME" \
        --location="$REGION" \
        --max-dispatches-per-second=1 \
        --max-concurrent-dispatches=1 \
        --max-attempts=3 \
        --min-backoff=60s \
        --max-backoff=3600s
else
    gcloud tasks queues create "$QUEUE_NAME" \
        --location="$REGION" \
        --max-dispatches-per-second=1 \
        --max-concurrent-dispatches=1 \
        --max-attempts=3 \
        --min-backoff=60s \
        --max-backoff=3600s
fi

echo "   ✅ Queue created with 'Bouncer' configuration"
echo "      - max-dispatches-per-second: 1"
echo "      - max-concurrent-dispatches: 1"

# Summary
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║   ✅  GCP SETUP COMPLETE                                      ║"
echo "╠═══════════════════════════════════════════════════════════════╣"
echo "║   Next steps:                                                 ║"
echo "║   1. Run ./scripts/deploy-renderer.sh to deploy              ║"
echo "║   2. Update .env with CLOUD_RUN_URL after deployment         ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Save configuration for later use
cat > .gcp-config << EOF
PROJECT_ID=$PROJECT_ID
REGION=$REGION
QUEUE_NAME=$QUEUE_NAME
EOF

echo "Configuration saved to .gcp-config"
