#!/bin/sh

awslocal sqs create-queue --queue-name send-email
awslocal sqs create-queue --queue-name failed-emails