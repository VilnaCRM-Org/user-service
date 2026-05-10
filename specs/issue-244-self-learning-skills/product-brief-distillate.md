# Product Brief Distillate

Self-learning skills are implemented as a deterministic local data pipeline:

`captured run -> intervention signal -> episode JSONL -> proposed skill patch -> eval gate`

The system is proxy-compatible through `OPENAI_BASE_URL`, but CI does not depend on a live proxy.
