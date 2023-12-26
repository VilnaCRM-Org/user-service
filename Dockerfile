FROM node:20.1-alpine3.17

RUN npm install -g pnpm

WORKDIR /app

COPY package*.json ./

RUN pnpm install

COPY . .

EXPOSE 3000

CMD ["pnpm", "run", "dev"]