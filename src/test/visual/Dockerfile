FROM mcr.microsoft.com/playwright:v1.44.1-jammy

RUN apt-get update && apt-get install -y python3 make g++ \
    && npm install -g pnpm \
    && apt-get clean

WORKDIR /app

COPY package*.json ./

COPY checkNodeVersion.js ./

RUN pnpm install

COPY . .

EXPOSE 3000

CMD ["pnpm", "run", "dev"]
