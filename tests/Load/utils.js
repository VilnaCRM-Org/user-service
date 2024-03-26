import faker from "k6/x/faker";
import http from 'k6/http';
import * as dotenv from "k6/x/dotenv";

const env = dotenv.parse(open(".env.test"));

class Utils {
    constructor() {
        const host = this.getFromEnv('LOAD_TEST_API_HOST');

        this.baseUrl = `https://${host}`;
        this.baseHttpUrl = this.baseUrl + '/api/users'
        this.mailCatcherUrl = `http://${host}:1080/messages`;
    }

    getMailCatcherUrl(){
        return this.mailCatcherUrl;
    }

    getScenarios(
        smokeRps,
        smokeVus,
        smokeDuration,
        averageRps,
        averageVus,
        averageRiseDuration,
        averagePlateauDuration,
        averageFallDuration,
        stressRps,
        stressVus,
        stressRiseDuration,
        stressPlateauDuration,
        stressFallDuration,
        spikeRps,
        spikeVus,
        spikeRiseDuration,
        spikeFallDuration,
    ){
        const averageTestStartTime = Number(smokeDuration);
        const stressTestStartTime = averageTestStartTime
            + Number(averageRiseDuration)
            + Number(averagePlateauDuration)
            + Number(averageFallDuration);
        const spikeTestStartTime = stressTestStartTime
            + Number(stressRiseDuration)
            + Number(stressPlateauDuration)
            + Number(stressFallDuration);
        return {
            smoke: utils.getSmokeScenario(
                smokeRps,
                smokeVus,
                smokeDuration,
            ),
            average: utils.getAverageScenario(
                averageRps,
                averageVus,
                averageRiseDuration,
                averagePlateauDuration,
                averageFallDuration,
                averageTestStartTime,
            ),
            stress: utils.getStressScenario(
                stressRps,
                stressVus,
                stressRiseDuration,
                stressPlateauDuration,
                stressFallDuration,
                stressTestStartTime,
            ),
            spike: utils.getSpikeScenario(
                spikeRps,
                spikeVus,
                spikeRiseDuration,
                spikeFallDuration,
                spikeTestStartTime,
            )
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

    getSmokeScenario(
        ratePerSecond,
        vus,
        duration
    ) {
        return {
            executor: 'constant-arrival-rate',
            rate: ratePerSecond,
            timeUnit: '1s',
            duration: duration + 's',
            preAllocatedVUs: vus,
            tags: {test_type: 'smoke'},
        }
    }

    getAverageScenario(
        targetRatePerSecond,
        vus,
        riseDuration,
        plateauDuration,
        fallDuration,
        startTime
    ) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: vus,
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: riseDuration + 's'
                },
                {
                    target: targetRatePerSecond,
                    duration: plateauDuration + 's'
                },
                {target: 0, duration: fallDuration + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'average'},
        }
    }

    getStressScenario(
        targetRatePerSecond,
        vus,
        riseDuration,
        plateauDuration,
        fallDuration,
        startTime
    ) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: vus,
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: riseDuration + 's'
                },
                {
                    target: targetRatePerSecond,
                    duration: plateauDuration + 's'
                },
                {target: 0, duration: fallDuration + 's'},
            ],
            startTime: startTime + 's',
            tags: {test_type: 'stress'},
        }
    }

    getSpikeScenario(
        targetRatePerSecond,
        vus,
        riseDuration,
        fallDuration,
        startTime
    ) {
        return {
            executor: 'ramping-arrival-rate',
            startRate: 0,
            timeUnit: '1s',
            preAllocatedVUs: vus,
            stages: [
                {
                    target: targetRatePerSecond,
                    duration: riseDuration + 's'
                },
                {target: 0, duration: fallDuration + 's'},
            ],
            startTime: startTime+ 's',
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
