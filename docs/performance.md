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
Also, you can find HTML files with load test reports [here](https://github.com/VilnaCRM-Org/user-service/tree/main/tests/Load/results/fpm)

The most important metrics for each test, which you'll find in tables include:

- **Target rps:** This number specifies the max requests per second rate, that will be reached during the test.
- **Real rps:** This number specifies the average requests per second rate, that was reached during a testing scenario.
- **Virtual users:** The number of simulated users accessing the service simultaneously. This helps in understanding how the application performs under different levels of user concurrency.
- **Rise duration:** The time period over which the load is gradually increased from zero to the desired number of requests per second or virtual users. This helps to observe how the system scales with increasing load.
- **Plateau duration:** The time period over which the load is holding the peak load. This helps to monitor the system's ability to handle the constant load gracefully.
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

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 6ms   |

![image](https://github.com/user-attachments/assets/08978579-a4f1-43ae-ad49-b2c3b58a4e3f)
![image](https://github.com/user-attachments/assets/e25787b6-3b11-4b92-95c6-b5f812207f04)

[Go back to navigation](#REST-API)

### Get User Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Users retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 50                                | 13ms  |

![image](https://github.com/user-attachments/assets/b0a53b62-4dc7-4774-8006-2a57cb0d7e3c)
![image](https://github.com/user-attachments/assets/57cca0d9-5c97-4150-a5a5-26106820416d)

[Go back to navigation](#REST-API)

### Create User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 24       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/695725de-94e9-48a4-a835-72b9a6619b22)
![image](https://github.com/user-attachments/assets/a9b4829b-2787-4704-af11-e07e29fcd0ec)

[Go back to navigation](#REST-API)

### Create User Batch Test

| Target RPS | BatchSize | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | --------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 10        | 9        | 200           | 10s           | 10s           | 8s    |

![image](https://github.com/user-attachments/assets/27137601-44c4-4673-ac2b-6dde91c6b30d)
![image](https://github.com/user-attachments/assets/8ed4d76a-700d-43a6-bf64-c878d404105e)

[Go back to navigation](#REST-API)

### Confirm User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 75       | 200           | 10s           | 10s           | 10ms  |

![image](https://github.com/user-attachments/assets/4b11b4c8-71b7-431d-a962-fda920d679f1)
![image](https://github.com/user-attachments/assets/b07f8fd5-814d-4c7c-8e4a-93c33b262c2a)

[Go back to navigation](#REST-API)

### Replace User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/afc13378-58bf-40c3-997c-3d84bf475469)
![image](https://github.com/user-attachments/assets/eaf59243-fec3-455c-add7-e9c880fa53e6)

[Go back to navigation](#REST-API)

### Update User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 4s    |

![image](https://github.com/user-attachments/assets/d116710f-8b45-485d-950f-b211d8489463)
![image](https://github.com/user-attachments/assets/7033be7b-cabc-4903-b68e-3c38cf14046d)

[Go back to navigation](#REST-API)

### Delete User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 10ms  |

![image](https://github.com/user-attachments/assets/ffb83454-c5a9-4c1a-b656-11b47b9d1a36)
![image](https://github.com/user-attachments/assets/10bc0498-3c3e-46c0-9d5b-5771904dd58e)

[Go back to navigation](#REST-API)

### Resend Confirmation Email To User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 22       | 200           | 10s           | 10s           | 4s    |

![image](https://github.com/user-attachments/assets/9d4f5b33-b13b-4462-9efc-f2c07456d2c7)
![image](https://github.com/user-attachments/assets/5954f769-ee45-4e4c-9fc7-089d2f017a21)

[Go back to navigation](#REST-API)

### OAuth Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 48       | 400           | 10s           | 10s           | 87ms  |

![image](https://github.com/user-attachments/assets/308a1efc-5205-4e64-bfdd-f4ee3151d5a2)
![image](https://github.com/user-attachments/assets/2962ab51-7c68-496c-ad05-de0be3593b3d)

[Go back to navigation](#REST-API)

### Health Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 40       | 200           | 10s           | 10s           | 8ms   |

![image](https://github.com/user-attachments/assets/f14f5897-a2a5-463f-8331-abbdbc6e427d)
![image](https://github.com/user-attachments/assets/a879272b-3169-469d-b792-458a0374e83d)

[Go back to navigation](#REST-API)

### GraphQL Get User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 25ms  |

![image](https://github.com/user-attachments/assets/0676fa54-27dc-4107-914e-f89e99eec7ca)
![image](https://github.com/user-attachments/assets/f45ac6c7-20b6-439c-a013-95cafbe7b841)

[Go back to navigation](#REST-API)

### GraphQL Get User Collection Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | Users retrieved with each request | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | --------------------------------- | ----- |
| 400        | 62       | 400           | 10s           | 10s           | 50                                | 15ms  |

![image](https://github.com/user-attachments/assets/b244ca63-b4cf-4474-a588-514a9482386d)
![image](https://github.com/user-attachments/assets/9ade9e0f-6d21-4b4d-9426-5c6310e3aa72)

[Go back to navigation](#REST-API)

### GraphQL Create User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 24       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/b41ed804-2817-40ab-b482-4cafd2bc748f)
![image](https://github.com/user-attachments/assets/9193a707-7f22-43c5-935b-9ddd4aa29b6e)

[Go back to navigation](#REST-API)

### GraphQL Confirm User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 76       | 200           | 10s           | 10s           | 14ms  |

![image](https://github.com/user-attachments/assets/e766ae54-f008-45e2-a899-e074187b453d)
![image](https://github.com/user-attachments/assets/c83d1e28-4f43-4e74-b057-5a872da758e9)

[Go back to navigation](#REST-API)

### GraphQL Update User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 23       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/ff06fac5-1316-48d6-a10d-3befa6ca56a3)
![image](https://github.com/user-attachments/assets/42824035-e35b-4b61-8fb2-7ef9e9a06b57)

[Go back to navigation](#REST-API)

### GraphQL Delete User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 400        | 47       | 400           | 10s           | 10s           | 11ms  |

![image](https://github.com/user-attachments/assets/8e7603ee-c3a6-4877-88dc-7eb255926d9f)
![image](https://github.com/user-attachments/assets/2f1f84e3-913e-42c4-bf0c-50d4fb389ac8)

[Go back to navigation](#REST-API)

### GraphQL Resend Confirmation Email To User Test

| Target RPS | Real RPS | Virtual Users | Rise Duration | Fall Duration | P(99) |
| ---------- | -------- | ------------- | ------------- | ------------- | ----- |
| 200        | 22       | 200           | 10s           | 10s           | 3s    |

![image](https://github.com/user-attachments/assets/7a492b2e-dba5-4b46-8bff-6c81b1453ecb)
![image](https://github.com/user-attachments/assets/ee19a537-72ee-47fa-9aeb-1a3c23da6937)

[Go back to navigation](#REST-API)

Learn more about [Performance and Optimization (frankenphp)](performance-frankenphp.md).
