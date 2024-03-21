import faker from "k6/x/faker";
import http from 'k6/http';
import * as dotenv from "k6/x/dotenv";

const env = dotenv.parse(open(".env.test"));

class Utils{
    constructor() {
        this.smokeTestDuration = Number(this.getFromEnv('SMOKE_TEST_DURATION'));
        this.averageTestDuration = Number(this.getFromEnv('AVERAGE_TEST_DURATION'));
        this.stressTestDuration = Number(this.getFromEnv('STRESS_TEST_DURATION'));
        this.spikeTestDurationRise = Number(this.getFromEnv('SPIKE_TEST_DURATION_RISE'));
        this.spikeTestDurationFall = Number(this.getFromEnv('SPIKE_TEST_DURATION_FALL'));

        this.smokeRatePerSecond = this.getFromEnv('SMOKE_RPS');
        this.averageRatePerSecond= this.getFromEnv('AVERAGE_RPS');
        this.stressRatePerSecond= this.getFromEnv('STRESS_RPS');
        this.spikeTargetRatePerSecond = this.getFromEnv('SPIKE_RPS');

        const host = this.getFromEnv('HOST');

        this.baseUrl = `https://${host}`;
        this.baseHttpUrl = this.baseUrl + '/api/users'
    }

    getScenarios() {
        return {
            smoke: this.getSmokeScenario(this.smokeRatePerSecond),
            average: this.getAverageScenario(this.averageRatePerSecond),
            stress: this.getStressScenario(this.stressRatePerSecond),
            spike: this.getSpikeScenario(this.spikeTargetRatePerSecond),
        }
    }

    getThresholds(
        smokeThreshold,
        averageThreshold,
        stressThreshold,
        spikeThreshold
    ) {
        return {
            'http_req_duration{test_type:smoke}': ['p(99)<' + smokeThreshold],
            'http_req_duration{test_type:average}': ['p(99)<' + averageThreshold],
            'http_req_duration{test_type:stress}': ['p(99)<' + stressThreshold],
            'http_req_duration{test_type:spike}': ['p(99)<' + spikeThreshold],
            'http_req_failed{test_type:smoke}': ['rate<0.01'],
            'http_req_failed{test_type:average}': ['rate<0.01'],
            'http_req_failed{test_type:stress}': ['rate<0.1'],
        }
    }

    getSmokeScenario(ratePerSecond) {
        return {
            executor: 'constant-arrival-rate',
            rate: ratePerSecond,
            timeUnit: '1s',
            duration: this.smokeTestDuration + 's',
            preAllocatedVUs: 3,
            tags: {test_type: 'smoke'},
        }
    }

    getAverageScenario(ratePerSecond) {
        return {
            executor: 'constant-arrival-rate',
            rate: ratePerSecond,
            timeUnit: '1s',
            duration: this.averageTestDuration + 's',
            preAllocatedVUs: 20,
            startTime: this.smokeTestDuration + 's',
            tags: {test_type: 'average'},
        }
    }

    getStressScenario(ratePerSecond) {
        return {
            executor: 'constant-arrival-rate',
            rate: ratePerSecond,
            timeUnit: '1s',
            duration: this.stressTestDuration + 's',
            preAllocatedVUs: 200,
            startTime: this.smokeTestDuration + this.averageTestDuration + 's',
            tags: {test_type: 'stress'},
        }
    }

    getSpikeScenario(targetRatePerSecond) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: 500,
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: this.spikeTestDurationRise + 's'
                },
                {target: 0, duration: this.spikeTestDurationFall + 's'},
            ],
            startTime: this.smokeTestDuration + this.averageTestDuration + this.stressTestDuration + 's',
            tags: {test_type: 'spike'},
        }
    }

    getJsonHeader() {
        return {
            headers: {
                'Content-Type': 'application/json',
            },
        };
    }

    getMergePatchHeader() {
        return {
            headers: {
                'Content-Type': 'application/merge-patch+json',
            },
        };
    }

    getRandomNumber(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    }

    getBaseUrl() {
        return this.baseUrl;
    }

    getBaseHttpUrl() {
        return this.baseHttpUrl;
    }

    getFromEnv(varName) {
        return env[varName];
    }

    insertUsers(numberOfUsers) {
        const users = [];

        for (let i = 0; i < numberOfUsers; i++) {
            const email = faker.person.email();
            const initials = faker.person.name();
            const password = faker.internet.password(true, true, true, false, false, 60);

            const payload = JSON.stringify({
                email: email,
                password: password,
                initials: initials,
            });

            const response = http.post(
                this.getBaseHttpUrl(),
                payload,
                this.getJsonHeader()
            )

            const user = JSON.parse(response.body);
            user.password = password
            users.push(user);
        }

        return users;
    }
}

export const utils = new Utils();
