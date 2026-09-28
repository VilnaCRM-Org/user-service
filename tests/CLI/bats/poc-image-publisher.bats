#!/usr/bin/env bats

@test "TEST image publisher rejects bad runs and emits closed evidence" {
  run make test-poc-publisher
  [ "$status" -eq 0 ]
}
