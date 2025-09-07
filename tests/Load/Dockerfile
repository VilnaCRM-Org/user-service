FROM golang:1.24-alpine AS builder

# Install git which is required by xk6
RUN apk add --no-cache git

# Install xk6 and build k6 with extensions
RUN go install go.k6.io/xk6/cmd/xk6@latest && \
    xk6 build --with github.com/avitalique/xk6-file@latest \
              --with github.com/mstoykov/xk6-counter@latest

# Use the grafana/k6 base image for the final stage
FROM grafana/k6:latest

# Copy the custom k6 binary from the builder stage
COPY --from=builder /go/k6 /usr/bin/k6
