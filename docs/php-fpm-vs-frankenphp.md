On this page, we compare the performance metrics observed in two sets of load tests for the same application:

1. **PHP-FPM** traditional setup
2. **FrankenPHP** modern application server for PHP built on top of the Caddy web server

Below, you will find a summary and analysis of the key metrics, such as **Requests per Second** (RPS) and **99th percentile latency** (P99) across various endpoints and scenarios (REST and GraphQL).

---

## 1. Executive Summary

- **Overall Throughput (RPS):**

  - In many **Create/Update** endpoints (both REST and GraphQL), **FrankenPHP** pushed **more real RPS** than PHP-FPM (e.g., Create User, Replace User, Update User).
  - In some tests (e.g., Get User, Delete User), **PHP-FPM** had a slightly higher real RPS but also higher latency than FrankenPHP.
  - Several tests (e.g., OAuth, Health, some GraphQL queries) showed **identical or very close real RPS** across both setups.

- **Latency (99th Percentile):**

  - **FrankenPHP** generally demonstrated **consistently lower P99** latencies across most tests.
  - The difference was especially striking in certain endpoints like **OAuth** (87ms vs. 8ms) and **GraphQL Create/Update** user tests (3s vs. tens of ms).
  - In short, FrankenPHP often served requests not only faster on average but also with a more predictable (lower) worst-case latency.

- **Summary Impression:**
  - **FrankenPHP** tends to provide a **lower-latency** response profile and is capable of handling **higher throughput** in many write-heavy (create, update, batch) endpoints.
  - **PHP-FPM** occasionally reached a higher real RPS on a few read-heavy or simpler endpoints, though its P99 latencies were often higher.

Below is a detailed breakdown per endpoint.

---

## 2. REST Endpoints Comparison

### 2.1 Get User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 400     | 400        |
| **Real RPS**   | 62      | 56         |
| **P(99)**      | 6ms     | 3ms        |

**Analysis:**

- **PHP-FPM** manages a slightly higher throughput here (62 vs. 56 RPS).
- **FrankenPHP** shows a notably lower 99th percentile latency (3ms vs. 6ms).

---

### 2.2 Get User Collection

| Metric                | PHP-FPM | FrankenPHP |
| --------------------- | ------- | ---------- |
| **Target RPS**        | 400     | 400        |
| **Real RPS**          | 62      | 62         |
| **Users per request** | 50      | 50         |
| **P(99)**             | 13ms    | 9ms        |

**Analysis:**

- The **throughput** is identical (62 RPS).
- **FrankenPHP** again shows **lower latency** (9ms vs. 13ms at P99).

---

### 2.3 Create User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 24      | 32         |
| **P(99)**      | 3s      | 23ms       |

**Analysis:**

- **FrankenPHP** handles significantly more throughput (32 vs. 24) while drastically reducing P99 (23ms vs. 3s).
- This is a **major performance difference** in FrankenPHP’s favor.

---

### 2.4 Create User Batch

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Batch Size** | 10      | 10         |
| **Real RPS**   | 9       | 14         |
| **P(99)**      | 8s      | 5s         |

**Analysis:**

- FrankenPHP processes **50% more RPS** (14 vs. 9).
- While both exhibit high latency for batch creation, FrankenPHP is still **faster** (5s vs. 8s).

---

### 2.5 Confirm User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 75      | 76         |
| **P(99)**      | 10ms    | 3ms        |

**Analysis:**

- Throughput is similar (75 vs. 76 RPS).
- FrankenPHP offers a **significantly lower** P99 (3ms vs. 10ms).

---

### 2.6 Replace User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 23      | 35         |
| **P(99)**      | 3s      | 31ms       |

**Analysis:**

- FrankenPHP yields **higher throughput** (35 vs. 23) and **lower** 99th percentile latency (31ms vs. 3s).

---

### 2.7 Update User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 23      | 35         |
| **P(99)**      | 4s      | 82ms       |

**Analysis:**

- FrankenPHP again offers **higher throughput** (35 vs. 23) and far better latency (82ms vs. 4s).

---

### 2.8 Delete User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 400     | 400        |
| **Real RPS**   | 47      | 37         |
| **P(99)**      | 10ms    | 4ms        |

**Analysis:**

- **PHP-FPM** achieves **slightly higher** throughput (47 vs. 37 RPS).
- FrankenPHP has a **lower** P99 latency (4ms vs. 10ms).

---

### 2.9 Resend Confirmation Email

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 22      | 37         |
| **P(99)**      | 4s      | 21ms       |

**Analysis:**

- FrankenPHP nearly **doubles** the throughput (37 vs. 22) with drastically **lower** P99 (21ms vs. 4s).

---

### 2.10 OAuth Test

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 400     | 400        |
| **Real RPS**   | 48      | 48         |
| **P(99)**      | 87ms    | 8ms        |

**Analysis:**

- Same throughput for both (48 RPS).
- FrankenPHP’s 99th percentile latency is an **order of magnitude lower** (8ms vs. 87ms).

---

### 2.11 Health Test

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 40      | 40         |
| **P(99)**      | 8ms     | 6ms        |

**Analysis:**

- Identical throughput (40 RPS).
- **FrankenPHP** is slightly lower in P99 (6ms vs. 8ms).

---

## 3. GraphQL Endpoints Comparison

### 3.1 GraphQL Get User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 400     | 400        |
| **Real RPS**   | 62      | 62         |
| **P(99)**      | 25ms    | 3ms        |

**Analysis:**

- Same throughput (62 RPS).
- **FrankenPHP** drastically lower P99 (3ms vs. 25ms).

---

### 3.2 GraphQL Get User Collection

| Metric                | PHP-FPM | FrankenPHP |
| --------------------- | ------- | ---------- |
| **Target RPS**        | 400     | 400        |
| **Real RPS**          | 62      | 62         |
| **Users per request** | 50      | 50         |
| **P(99)**             | 15ms    | 8ms        |

**Analysis:**

- Identical throughput (62).
- FrankenPHP with **about half** the P99 latency.

---

### 3.3 GraphQL Create User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 24      | 32         |
| **P(99)**      | 3s      | 56ms       |

**Analysis:**

- **FrankenPHP** achieves higher throughput (32 vs. 24) and drastically lower P99 (56ms vs. 3s).

---

### 3.4 GraphQL Confirm User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 76      | 76         |
| **P(99)**      | 14ms    | 7ms        |

**Analysis:**

- Same throughput (76).
- FrankenPHP cuts the P99 in half (7ms vs. 14ms).

---

### 3.5 GraphQL Update User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 23      | 35         |
| **P(99)**      | 3s      | 27ms       |

**Analysis:**

- FrankenPHP again offers **more RPS** and **drastically lower** P99 latency.

---

### 3.6 GraphQL Delete User

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 400     | 400        |
| **Real RPS**   | 47      | 47         |
| **P(99)**      | 11ms    | 5ms        |

**Analysis:**

- Throughput is the same (47).
- FrankenPHP’s P99 is half (5ms vs. 11ms).

---

### 3.7 GraphQL Resend Confirmation Email

| Metric         | PHP-FPM | FrankenPHP |
| -------------- | ------- | ---------- |
| **Target RPS** | 200     | 200        |
| **Real RPS**   | 22      | 37         |
| **P(99)**      | 3s      | 21ms       |

**Analysis:**

- FrankenPHP’s throughput is higher (37 vs. 22) and P99 is dramatically lower (21ms vs. 3s).

---

## 4. Overall Observations

1. **Latency**: FrankenPHP consistently exhibits **lower** 99th percentile times, often by large margins (seconds vs. milliseconds).
2. **Throughput**:
   - FrankenPHP typically meets or exceeds PHP-FPM’s real RPS in create/update/delete flows.
   - For some high-read or simpler endpoints, either there’s parity (identical real RPS) or PHP-FPM occasionally squeezes out a bit more throughput (e.g., “Get User,” “Delete User” in REST).
3. **Possible Reasons**:
   - FrankenPHP’s design (PHP embedded directly in a high-performance Rust HTTP server) likely reduces overhead compared to the multi-process overhead of PHP-FPM + web server.
   - The concurrency model and I/O approach in FrankenPHP can yield better tail latency (leading to better P99 times).
4. **Practical Implications**:
   - For high-throughput, write-heavy scenarios—like **batch creation or frequent user updates**—FrankenPHP can handle more load with faster response times.
   - Even in read-heavy scenarios, FrankenPHP rarely lags behind in latency, showing more consistent performance overall.

---

## 5. Conclusion

The data strongly suggests that **FrankenPHP** provides better or comparable performance in most scenarios, particularly in terms of **lower tail latency** (99th percentile) and higher RPS for create/update workloads.

- **If your application** is sensitive to **response times** (e.g., you need consistently fast user interactions, or your load tests revolve around tail latency), **FrankenPHP** appears to offer a substantial advantage.
- **If your main concern** is raw throughput for simpler endpoints (like basic read operations), you may not see as large a difference. However, for the majority of test cases, especially underwritten or mixed loads, FrankenPHP yields both **higher throughput** and **better worst-case latency**.

Ultimately, **FrankenPHP** has shown promise as an alternative to the traditional **PHP-FPM** stack, with the caveat that it is still relatively new and may have a smaller ecosystem. If performance is a critical factor, it’s worth evaluating FrankenPHP in a production-like environment.

---

Learn more about [Testing Documentation](testing.md).
