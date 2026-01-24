#!/bin/bash
# ============================================
# Setup GCS Bucket for Template Assets
# ============================================
# 
# Creates a Google Cloud Storage bucket for template assets:
# - Background videos/images
# - Overlay images
# - Music files
#
# These assets are accessed by Cloud Run during rendering.
#
# Usage:
#   ./setup-gcs-assets.sh [PROJECT_ID] [BUCKET_NAME]

set -e

# Configuration
PROJECT_ID="${1:-$(gcloud config get-value project 2>/dev/null)}"
BUCKET_NAME="${2:-invitationvideos-assets}"
REGION="us-central1"

if [ -z "$PROJECT_ID" ]; then
    echo "❌ Error: No project ID specified"
    echo "Usage: ./setup-gcs-assets.sh [PROJECT_ID] [BUCKET_NAME]"
    exit 1
fi

echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║   📦  GCS BUCKET SETUP - TEMPLATE ASSETS                     ║"
echo "╠═══════════════════════════════════════════════════════════════╣"
echo "║   Project: $PROJECT_ID"
echo "║   Bucket:  $BUCKET_NAME"
echo "║   Region:  $REGION (Free tier)"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""

# Set project
gcloud config set project "$PROJECT_ID"

# Enable Cloud Storage API
echo "🔌 Enabling Cloud Storage API..."
gcloud services enable storage.googleapis.com --quiet

# Check if bucket exists
if gsutil ls -b gs://$BUCKET_NAME &>/dev/null; then
    echo "✅ Bucket already exists: gs://$BUCKET_NAME"
else
    echo "📦 Creating bucket..."
    gsutil mb -l $REGION -p $PROJECT_ID gs://$BUCKET_NAME
    echo "✅ Bucket created: gs://$BUCKET_NAME"
fi

# Set public read access (templates are public)
echo ""
echo "🔓 Setting public read access for template assets..."
gsutil iam ch allUsers:objectViewer gs://$BUCKET_NAME

# Enable CORS for web access
echo ""
echo "🌐 Configuring CORS for web access..."
cat > /tmp/cors-config.json << 'EOF'
[
  {
    "origin": ["*"],
    "method": ["GET", "HEAD"],
    "responseHeader": ["Content-Type", "Content-Length"],
    "maxAgeSeconds": 3600
  }
]
EOF
gsutil cors set /tmp/cors-config.json gs://$BUCKET_NAME
rm /tmp/cors-config.json

# Create folder structure
echo ""
echo "📁 Creating folder structure..."
# Create placeholder files to establish folder structure
echo "Placeholder" | gsutil cp - gs://$BUCKET_NAME/templates/.placeholder
echo "Placeholder" | gsutil cp - gs://$BUCKET_NAME/music/.placeholder

# Clean up placeholders
gsutil rm gs://$BUCKET_NAME/templates/.placeholder 2>/dev/null || true
gsutil rm gs://$BUCKET_NAME/music/.placeholder 2>/dev/null || true

# Summary
BUCKET_URL="https://storage.googleapis.com/$BUCKET_NAME"
echo ""
echo "╔═══════════════════════════════════════════════════════════════╗"
echo "║   ✅  GCS BUCKET SETUP COMPLETE                               ║"
echo "╠═══════════════════════════════════════════════════════════════╣"
echo "║                                                               ║"
echo "║   Bucket URL: $BUCKET_URL"
echo "║                                                               ║"
echo "║   Folder Structure:                                           ║"
echo "║   gs://$BUCKET_NAME/"
echo "║   ├── templates/                                              ║"
echo "║   │   ├── royal-wedding-gold/                                ║"
echo "║   │   │   ├── background.mp4                                 ║"
echo "║   │   │   ├── overlay.png                                    ║"
echo "║   │   │   └── music.mp3                                      ║"
echo "║   │   └── modern-minimalist/                                 ║"
echo "║   │       └── ...                                            ║"
echo "║   └── music/                                                 ║"
echo "║       └── shared-tracks/                                     ║"
echo "║                                                               ║"
echo "║   Free Tier: 5GB/month                                       ║"
echo "║                                                               ║"
echo "╚═══════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 Next steps:"
echo "   1. Upload template assets:"
echo "      gsutil cp ./assets/* gs://$BUCKET_NAME/templates/your-template/"
echo ""
echo "   2. Update templates table with asset URLs:"
echo "      UPDATE templates SET asset_base_url = '$BUCKET_URL/templates/your-template/'"
echo ""

# Save configuration
echo "GCS_BUCKET_NAME=$BUCKET_NAME" >> ../.gcp-config
echo "GCS_BUCKET_URL=$BUCKET_URL" >> ../.gcp-config
echo ""
echo "Configuration saved to .gcp-config"
