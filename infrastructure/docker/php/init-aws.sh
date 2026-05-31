#!/bin/sh

aws sqs create-queue --queue-name send-email
aws sqs create-queue --queue-name failed-emails
aws sqs create-queue --queue-name insert-user