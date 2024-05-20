FROM node:20-alpine3.17

RUN apk add --no-cache python3 make g++  \
    && npm install -g pnpm

WORKDIR /app

COPY package*.json ./

COPY checkNodeVersion.js ./

RUN pnpm install

COPY . .

EXPOSE 3000

CMD ["pnpm", "run", "dev"]