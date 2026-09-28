#!/bin/sh

awslocal sqs create-queue --queue-name send-email
awslocal sqs create-queue --queue-name failed-emails
awslocal sqs create-queue --queue-name insert-user
awslocal sqs create-queue --queue-name health-check-queue
awslocal sqs create-queue --queue-name domain-events
awslocal sqs create-queue --queue-name failed-domain-events
