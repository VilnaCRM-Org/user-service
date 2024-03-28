FROM golang:1.22-alpine as builder

WORKDIR /app

ENV CGO_ENABLED 0
RUN go install go.k6.io/xk6/cmd/xk6@latest

RUN xk6 build \
    --with github.com/szkiba/xk6-faker@latest  \
    --with github.com/szkiba/xk6-dotenv@latest

FROM alpine

COPY --from=builder /app/k6 /bin/
COPY ./tests/Load ./scripts
COPY .env.test ./scripts

ENTRYPOINT [ "k6" ]