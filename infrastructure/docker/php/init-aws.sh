#!/bin/sh

awslocal sqs create-queue --queue-name emails
awslocal sqs create-queue --queue-name failed-emails