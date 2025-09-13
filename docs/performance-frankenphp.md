Welcome to the **Performance and Optimization** GitHub page, which is dedicated to showcasing our comprehensive approach to enhancing the efficiency and speed of our User Service application. Our goal is to share insights, methodologies, and results from rigorous testing and optimization processes to help developers achieve peak performance in their own applications.

## Testing Environment

To ensure our User Service is optimized for high performance, we conducted extensive testing using standardized infrastructure on AWS. This approach allows us to identify bottlenecks, optimize code, and achieve significant performance improvements. By utilizing AWS for our testing environment, we ensure consistency and uniformity across all tests, enabling developers to work with the same setup and achieve comparable results. This unified testing framework provides a reliable foundation for evaluating performance, regardless of geographic location or hardware variations, ensuring all team members can collaborate effectively on optimization efforts. Below are the details of the hardware and software components used in our testing environment:

### Server Specifications:
- **Instance Type:** c6i.4xlarge
- **CPU:** 16 core
- **Memory:** 32 GB RAM
- **Storage:** 30 GB

### Software Specifications:
- **Operating System:** Ubuntu 24.04 LTS
- **User Service Version:** 2.3.0
- **PHP Version:** 8.3
- **Symfony Version:** 7.1/7.2
- **Database:** MariaDB 11.4
- **Load Testing Tools:** Grafana K6

## Benchmarks

Here you will find the results of load tests for each User Service endpoint, with a graph, that shows how execution parameters were changing over time for different load scenarios. Also, the metric for Spike testing will be provided, alongside a table, that will show the most important of them.

Each endpoint was tested for smoke, average, stress, and spike load scenarios. You can learn more about them [here](https://grafana.com/docs/k6/latest/testing-guides/test-types/).
Also, you can find HTML files with load test reports [here](https://github.com/VilnaCRM-Org/user-service/tree/main/tests/Load/results/franken)

The most important metrics for each test, which you'll find in tables include:
- **Target rps:** This number specifies the max requests per second rate, that will be reached during the test.
- **Real rps:** This number specifies the average requests per second rate, that was reached during a testing scenario.
- **Virtual users:**  The number of simulated users accessing the service simultaneously. This helps in understanding how the application performs under different levels of user concurrency.
- **Rise duration:** The time period over which the load is gradually increased from zero to the desired number of requests per second or virtual users. This helps to observe how the system scales with increasing load.
- **Plateau duration:**  The time period over which the load is holding the peak load. This helps to monitor the system's ability to handle the constant load gracefully.
- **Fall duration:** The time period over which the load is gradually decreased back to zero from the peak load. This helps to monitor the system's ability to gracefully handle the reduction in load and ensure there are no residual issues.
- **P(99):** This number specifies the time, which it took for 99% of requests to receive a successful response.

### REST API
- [Get User](#Get-User-Test)
- [Get User Collection](#Get-User-Test)
- [Create User](#Create-User-Test)
- [Create User Batch](#Create-User-Batch-Test)
- [Confirm User](#Confirm-User-Test)
- [Replace User](#Replace-User-Test)
- [Update User](#Update-User-Test)
- [Delete User](#Delete-User-Test)
- [Resend Confirmation Email To User](#Resend-Confirmation-Email-To-User-Test)
- [OAuth](#OAuth-Test)
- [Health](#Health-Test)

### GraphQL
- [Get User](#GraphQL-Get-User-Test)
- [Get User Collection](#GraphQL-Get-User-Collection-Test)
- [Create User](#GraphQL-Create-User-Test)
- [Confirm User](#GraphQL-Confirm-User-Test)
- [Update User](#GraphQL-Update-User-Test)
- [Delete User](#GraphQL-Delete-User-Test)
- [Resend Confirmation Email To User](#GraphQL-Resend-Confirmation-Email-To-User-Test)


### Get User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 400        | 56        | 400           | 10s            | 10s            |  3ms  |

![image](https://github.com/user-attachments/assets/9fd483d7-1a4c-436b-a35f-c7fa2935fe35)
![image](https://github.com/user-attachments/assets/57b57a13-c44a-416b-8120-1b5c2079f4d3)


[Go back to navigation](#REST-API)

### Get User Collection Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | Users retrieved with each request| P(99) |                        
|------------|-----------|---------------|----------------|----------------|----------------------------------|-------|
| 400        | 62        | 400           | 10s            | 10s            |                         50       | 9ms   |

![image](https://github.com/user-attachments/assets/b0a53b62-4dc7-4774-8006-2a57cb0d7e3c)
![image](https://github.com/user-attachments/assets/57cca0d9-5c97-4150-a5a5-26106820416d)

[Go back to navigation](#REST-API)

### Create User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99)  |                          
|------------|-----------|---------------|----------------|----------------|--------|
| 200        | 32        | 200           | 10s            | 10s            | 23ms   |   

![image](https://github.com/user-attachments/assets/dee04500-3328-48a8-84a7-a840dcf86acc)
![image](https://github.com/user-attachments/assets/c49fcd55-e53a-48c6-87af-c2fdd0765a28)

[Go back to navigation](#REST-API)

### Create User Batch Test

| Target RPS | BatchSize | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99)  |                           
|------------|-----------|-----------|---------------|----------------|----------------|--------|
| 200        | 10        | 14        | 200           | 10s            | 10s            | 5s     |  

![image](https://github.com/user-attachments/assets/6ee1ade8-33c0-406a-94fd-6be8e9de77ab)
![image](https://github.com/user-attachments/assets/bd605c7d-3a3d-4fc2-83dd-9e39bd7e078b)

[Go back to navigation](#REST-API)

### Confirm User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 76        | 200           | 10s            | 10s            | 3ms   |  

![image](https://github.com/user-attachments/assets/c571bb98-eabe-4dc3-892a-2831879dd02c)
![image](https://github.com/user-attachments/assets/1818b07a-faa3-4a3c-91f3-a89797fed2f6)

[Go back to navigation](#REST-API)

### Replace User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 35        | 200           | 10s            | 10s            | 31ms  |  

![image](https://github.com/user-attachments/assets/b85da2e9-5d76-49bb-beef-a38e418c553c)
![image](https://github.com/user-attachments/assets/14c3a1b2-93ac-49cc-9090-4153abb357bc)

[Go back to navigation](#REST-API)

### Update User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 35        | 200           | 10s            | 10s            | 82ms  |  

![image](https://github.com/user-attachments/assets/5b4f523f-9bd3-4934-bfd3-8a1d8c243d3a)
![image](https://github.com/user-attachments/assets/2cab68e5-82ab-4735-a8b2-c1f385aa1fb8)

[Go back to navigation](#REST-API)

### Delete User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  |  Fall Duration | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 400        | 37        | 400           | 10s            | 10s            | 4ms   |  

![image](https://github.com/user-attachments/assets/584594c0-fdc0-4658-b14f-350191a6dafe)
![image](https://github.com/user-attachments/assets/098a90ad-59db-400f-99ec-f6092c60a59a)

[Go back to navigation](#REST-API)

### Resend Confirmation Email To User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 37        | 200           | 10s            | 10s            | 21ms  |  

![image](https://github.com/user-attachments/assets/3968e7e7-dc32-438c-be3b-788c9637e6da)
![image](https://github.com/user-attachments/assets/1991016c-1689-451f-9fbd-c3e4df7cbfa1)

[Go back to navigation](#REST-API)

### OAuth Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 400        |  48       | 400           | 10s            | 10s            | 8ms   | 

![image](https://github.com/user-attachments/assets/ea0fc951-1d38-41bf-947d-f4338e8e222c)
![image](https://github.com/user-attachments/assets/26d00abe-0f8b-486c-9e1d-08e12925480a)

[Go back to navigation](#REST-API)

### Health Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        |  40       | 200           | 10s            | 10s            | 6ms   | 

![image](https://github.com/user-attachments/assets/56162eed-e887-44d8-b71c-005d53814f52)
![image](https://github.com/user-attachments/assets/16b7ebe1-44c6-4347-b3a8-8947035236c0)

[Go back to navigation](#REST-API)

### GraphQL Get User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 400        | 62        | 400           | 10s            | 10s            | 3ms   | 

![image](https://github.com/user-attachments/assets/b03a596f-bcb5-4a31-b8b9-08069ddecd4d)
![image](https://github.com/user-attachments/assets/9f42f8b5-aa57-45ef-9e33-317b5e038baa)

[Go back to navigation](#REST-API)

### GraphQL Get User Collection Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | Users retrieved with each request| P(99) |                          
|------------|-----------|---------------|----------------|----------------|----------------------------------|-------|
| 400        | 62        | 400           | 10s            | 10s            | 50                               | 8ms   |

![image](https://github.com/user-attachments/assets/9d591569-6fee-47df-bb53-45bc75acf528)
![image](https://github.com/user-attachments/assets/ab7f3e23-fb34-4e67-a163-cf206b185faf)

[Go back to navigation](#REST-API)

### GraphQL Create User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 32        | 200           | 10s            | 10s            | 56ms  | 

![image](https://github.com/user-attachments/assets/0b60885a-60b8-47ba-8aca-7ab4175a91a3)
![image](https://github.com/user-attachments/assets/1764d21a-e4e3-45d6-b6ad-e910495b5861)

[Go back to navigation](#REST-API)

### GraphQL Confirm User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 76        | 200           | 10s            | 10s            | 7ms   |

![image](https://github.com/user-attachments/assets/56d031fa-b1e6-4eef-a4aa-a83502a81071)
![image](https://github.com/user-attachments/assets/3e1eb968-bc55-45ec-80a8-c8852358b1d7)

[Go back to navigation](#REST-API)

### GraphQL Update User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 35        | 200           | 10s            | 10s            | 27ms  |

![image](https://github.com/user-attachments/assets/74cad3f4-f4e1-43c4-a861-262fb69c65c5)
![image](https://github.com/user-attachments/assets/f988c689-76fb-44bc-a33a-fedfcd0b5de4)

[Go back to navigation](#REST-API)

### GraphQL Delete User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 400        | 47        | 400           | 10s            | 10s            | 5ms   |

![image](https://github.com/user-attachments/assets/993556e9-45b6-4bc2-ae34-b09d99cdd013)
![image](https://github.com/user-attachments/assets/86196990-26d8-422d-90a9-2b510ec72c05)

[Go back to navigation](#REST-API)

### GraphQL Resend Confirmation Email To User Test

| Target RPS | Real RPS  | Virtual Users | Rise Duration  | Fall Duration  | P(99) |                          
|------------|-----------|---------------|----------------|----------------|-------|
| 200        | 37        | 200           | 10s            | 10s            | 21ms  |

![image](https://github.com/user-attachments/assets/9d15589e-2626-4c1a-a8fd-a516f71ea5dd)
![image](https://github.com/user-attachments/assets/5c2636b0-c8a5-495a-950c-30c91060b3bf)

[Go back to navigation](#REST-API)

Learn more about [Comparing Load Test Results: PHP‐FPM vs. FrankenPHP](php-fpm-vs-frankenphp.md).
  