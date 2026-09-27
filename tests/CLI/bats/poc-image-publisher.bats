#!/usr/bin/env bats

@test "TEST image publisher rejects bad runs and emits closed evidence" {
  run env PYTHONDONTWRITEBYTECODE=1 python3 -m unittest discover -s tests/CLI/poc_publisher -p 'test_*.py'
  [ "$status" -eq 0 ]
}
