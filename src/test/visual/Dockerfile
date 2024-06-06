FROM mcr.microsoft.com/playwright:v1.40.1-focal


# Set working directory
WORKDIR /app

# Copy test code
COPY visual/ /app/src/test/visual/
COPY package.json /app/
# Install dependencies
RUN npm cache clean --force
RUN npm install -g playwright
RUN npm install
RUN apt-get update && apt-get install -y wget gnupg ca-certificates && \
   curl -sL https://deb.nodesource.com/setup_16.x | bash - && \
   apt install -y nodejs
