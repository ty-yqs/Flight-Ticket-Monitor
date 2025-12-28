FROM node:18-bullseye-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
  && apt-get install -y --no-install-recommends ca-certificates wget gnupg2 fonts-liberation libasound2 libatk1.0-0 libatk-bridge2.0-0 libcups2 libx11-xcb1 libx11-6 libxcomposite1 libxrandr2 libxss1 libnss3 libgbm1 libgtk-3-0 \
  && rm -rf /var/lib/apt/lists/*

# Install chromium (for Puppeteer compatibility)
RUN apt-get update && apt-get install -y --no-install-recommends chromium && rm -rf /var/lib/apt/lists/*

ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

WORKDIR /var/www/html

# copy package.json and install dependencies at build time
COPY ../package.json ./package.json
RUN npm ci --production --no-audit --no-fund || true

# default command: run fetcher
CMD ["npm", "start"]
