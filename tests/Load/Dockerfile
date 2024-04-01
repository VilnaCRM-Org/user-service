FROM golang:1.22-alpine as builder

WORKDIR /app

ENV CGO_ENABLED 0
RUN go install go.k6.io/xk6/cmd/xk6@v0.11.0

RUN xk6 build \
    --with github.com/szkiba/xk6-faker@v0.3.0

FROM alpine

COPY --from=builder /app/k6 /bin/
COPY ./tests/Load ./scripts

ENTRYPOINT [ "k6" ]