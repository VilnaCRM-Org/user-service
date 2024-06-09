FROM mcr.microsoft.com/playwright:v1.44.1-jammy

RUN apt-get update && apt-get install -y python3 make g++ \
    && npm install -g pnpm \
    && apt-get clean

WORKDIR /app

COPY . .

RUN pnpm install

CMD ["pnpm", "run", "dev"]
