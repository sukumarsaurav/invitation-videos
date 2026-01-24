# Remotion Renderer Dockerfile for Cloud Run
# Optimized for "Budget Guard" configuration (2GB RAM, 1 CPU)
# Multi-stage build for smaller image size

# ============================================
# Stage 1: Build
# ============================================
FROM node:20-slim AS builder

WORKDIR /app

# Copy package files first (better caching)
COPY package*.json ./

# Install ALL dependencies (including devDependencies for build)
RUN npm ci

# Copy source files
COPY . .

# Build TypeScript and Remotion bundle
RUN npm run build

# ============================================
# Stage 2: Production
# ============================================
FROM node:20-slim

# Install Chrome dependencies, FFmpeg, and curl (for health checks)
RUN apt-get update && apt-get install -y \
    chromium \
    ffmpeg \
    curl \
    fonts-noto-color-emoji \
    fonts-noto-cjk \
    fonts-freefont-ttf \
    fonts-indic \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Set Chrome path for Remotion
ENV CHROME_PATH=/usr/bin/chromium
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

# Create non-root user for security
RUN groupadd -r renderer && useradd -r -g renderer renderer

# Create app directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install production dependencies only
RUN npm ci --only=production && npm cache clean --force

# Copy built files from builder stage
COPY --from=builder /app/dist ./dist
COPY --from=builder /app/remotion ./remotion

# Create temp directory with correct permissions
RUN mkdir -p /app/temp && chown -R renderer:renderer /app

# Switch to non-root user
USER renderer

# Expose port
EXPOSE 8080

# Set environment
ENV NODE_ENV=production
ENV PORT=8080

# Health check using curl (more reliable)
HEALTHCHECK --interval=30s --timeout=10s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:8080/health || exit 1

# Start server
CMD ["node", "dist/index.js"]
