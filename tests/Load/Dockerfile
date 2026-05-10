FROM golang:1.25-alpine AS builder

RUN apk add --no-cache git

RUN go install go.k6.io/xk6/cmd/xk6@v1.4.1 && \
    xk6 build --with github.com/avitalique/xk6-file@v1.6.1 \
              --with github.com/mstoykov/xk6-counter@v0.0.1

FROM grafana/k6:latest

COPY --from=builder /go/k6 /usr/bin/k6
