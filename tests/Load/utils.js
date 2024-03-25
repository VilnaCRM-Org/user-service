import faker from "k6/x/faker";
import http from 'k6/http';
import * as dotenv from "k6/x/dotenv";

const env = dotenv.parse(open(".env.test"));

class Utils {
    constructor() {
        this.smokeTestDuration = Number(this.getFromEnv('LOAD_TEST_SMOKE_TEST_DURATION'));
        this.averageTestDurationRise = Number(this.getFromEnv('LOAD_TEST_AVERAGE_TEST_DURATION_RISE'));
        this.averageTestDurationPlateau = Number(this.getFromEnv('LOAD_TEST_AVERAGE_TEST_DURATION_PLATEAU'));
        this.averageTestDurationFall = Number(this.getFromEnv('LOAD_TEST_AVERAGE_TEST_DURATION_FALL'));
        this.averageTestDuration = this.averageTestDurationRise +
            this.averageTestDurationPlateau + this.averageTestDurationFall;
        this.stressTestDurationRise = Number(this.getFromEnv('LOAD_TEST_STRESS_TEST_DURATION_RISE'));
        this.stressTestDurationPlateau = Number(this.getFromEnv('LOAD_TEST_STRESS_TEST_DURATION_PLATEAU'));
        this.stressTestDurationFall = Number(this.getFromEnv('LOAD_TEST_STRESS_TEST_DURATION_FALL'));
        this.stressTestDuration = this.stressTestDurationRise +
            this.stressTestDurationPlateau + this.stressTestDurationFall;
        this.spikeTestDurationRise = Number(this.getFromEnv('LOAD_TEST_SPIKE_TEST_DURATION_RISE'));
        this.spikeTestDurationFall = Number(this.getFromEnv('LOAD_TEST_SPIKE_TEST_DURATION_FALL'));

        this.smokeRatePerSecond = this.getFromEnv('LOAD_TEST_SMOKE_RPS');
        this.averageRatePerSecond = this.getFromEnv('LOAD_TEST_AVERAGE_RPS');
        this.stressRatePerSecond = this.getFromEnv('LOAD_TEST_STRESS_RPS');
        this.spikeTargetRatePerSecond = this.getFromEnv('LOAD_TEST_SPIKE_RPS');

        const host = this.getFromEnv('LOAD_TEST_API_HOST');

        this.baseUrl = `https://${host}`;
        this.baseHttpUrl = this.baseUrl + '/api/users'
        this.mailCatcherUrl = 'http://localhost:1080/messages';
    }

    getMailCatcherUrl(){
        return this.mailCatcherUrl;
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
            'checks{scenario:smoke}' : ['rate>0.99'],
            'checks{scenario:average}' : ['rate>0.99'],
            'checks{scenario:stress}' : ['rate>0.99'],
            'checks{scenario:spike}' : ['rate>0.75'],
        }
    }

    getSmokeScenario(ratePerSecond) {
        return {
            executor: 'constant-arrival-rate',
            rate: ratePerSecond,
            timeUnit: '1s',
            duration: this.smokeTestDuration + 's',
            preAllocatedVUs: Number(this.getFromEnv('LOAD_TEST_SMOKE_VUS')),
            tags: {test_type: 'smoke'},
        }
    }

    getAverageScenario(targetRatePerSecond) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: Number(this.getFromEnv('LOAD_TEST_AVERAGE_VUS')),
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: this.averageTestDurationRise + 's'
                },
                {
                    target: targetRatePerSecond,
                    duration: this.averageTestDurationPlateau + 's'
                },
                {target: 0, duration: this.averageTestDurationFall + 's'},
            ],
            startTime: this.smokeTestDuration + 's',
            tags: {test_type: 'average'},
        }
    }

    getStressScenario(targetRatePerSecond) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: Number(this.getFromEnv('LOAD_TEST_STRESS_VUS')),
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: this.stressTestDurationRise + 's'
                },
                {
                    target: targetRatePerSecond,
                    duration: this.stressTestDurationPlateau + 's'
                },
                {target: 0, duration: this.stressTestDurationFall + 's'},
            ],
            startTime: this.smokeTestDuration + this.averageTestDuration + 's',
            tags: {test_type: 'stress'},
        }
    }

    getSpikeScenario(targetRatePerSecond) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: Number(this.getFromEnv('LOAD_TEST_SPIKE_VUS')),
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

    generateRequests(numberOfUsers) {
        const requests = [];
        const userPasswords = {};

        for (let i = 0; i < numberOfUsers; i++) {
            const email = faker.person.email();
            const initials = faker.person.name();
            const password = faker.internet.password(true, true, true, false, false, 60);

            requests.push({
                method: 'POST',
                url: this.getBaseHttpUrl(),
                body: JSON.stringify({
                    email,
                    password,
                    initials,
                }),
                params: this.getJsonHeader(),
            });

            userPasswords[email] = password;
        }

        return [requests, userPasswords];
    }

    insertUsers(numberOfUsers) {
        const batchSize = this.getFromEnv('LOAD_TEST_BATCH_SIZE');
        const [requests, userPasswords] = this.generateRequests(numberOfUsers);
        const users = [];

        for (let i = 0; i < numberOfUsers; i += batchSize) {
            const batch = requests.slice(i, i + batchSize);
            const responses = http.batch(batch);

            responses.forEach((response) => {
                const user = JSON.parse(response.body);
                user.password = userPasswords[user.email];
                users.push(user);
            });
        }

        return users;
    }
}

export const utils = new Utils();
