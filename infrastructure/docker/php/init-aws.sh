#!/bin/sh

aws --endpoint-url http://localhost:4566 --no-sign-request sqs create-queue --queue-name send-email
aws --endpoint-url http://localhost:4566 --no-sign-request sqs create-queue --queue-name failed-emails
aws --endpoint-url http://localhost:4566 --no-sign-request sqs create-queue --queue-name insert-user