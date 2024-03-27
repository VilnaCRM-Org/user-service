import faker from "k6/x/faker";
import http from 'k6/http';
import * as dotenv from "k6/x/dotenv";

const env = dotenv.parse(open(".env.test"));

class Utils {
    constructor() {
        const host = this.getFromEnv('LOAD_TEST_API_HOST');
        const mailCatcherPort = this.getFromEnv('LOAD_TEST_MAILCATCHER_PORT');

        this.loadTestPrefix = 'LOAD_TEST_';

        this.baseUrl = `https://${host}/api`;
        this.baseHttpUrl = this.baseUrl + '/users';
        this.baseGraphQLUrl = this.baseUrl + '/graphql';
        this.mailCatcherUrl = `http://${host}:${mailCatcherPort}/messages`;
    }

    getMailCatcherUrl() {
        return this.mailCatcherUrl;
    }

    getBaseGraphQLUrl() {
        return this.baseGraphQLUrl;
    }

    getOptions(scenarioName) {
        return {
            insecureSkipTLSVerify: true,
            scenarios: this.getScenarios(
                scenarioName
            ),
            thresholds: this.getThresholds(
                scenarioName
            )
        }
    }

    getScenarios(
        scenarioName
    ) {
        const smokeRps = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SMOKE_RPS');
        const smokeVus = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SMOKE_VUS');
        const smokeDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SMOKE_DURATION');
        const averageRps = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_RPS');
        const averageVus = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_VUS');
        const averageRiseDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_RISE');
        const averagePlateauDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_PLATEAU');
        const averageFallDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_FALL');
        const stressRps = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_RPS');
        const stressVus = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_VUS');
        const stressRiseDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_RISE');
        const stressPlateauDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_PLATEAU');
        const stressFallDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_FALL');
        const spikeRps = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_RPS');
        const spikeVus = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_VUS');
        const spikeRiseDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_DURATION_RISE');
        const spikeFallDuration = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_DURATION_FALL');
        const delay = Number(utils.getFromEnv('LOAD_TEST_DELAY_BETWEEN_SCENARIOS'));
        const averageTestStartTime = Number(smokeDuration) + delay;
        const stressTestStartTime = averageTestStartTime
            + Number(averageRiseDuration)
            + Number(averagePlateauDuration)
            + Number(averageFallDuration) + delay;
        const spikeTestStartTime = stressTestStartTime
            + Number(stressRiseDuration)
            + Number(stressPlateauDuration)
            + Number(stressFallDuration) + delay;
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
        scenarioName
    ) {
        const smokeThreshold = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SMOKE_THRESHOLD');
        const averageThreshold = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_THRESHOLD');
        const stressThreshold = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_THRESHOLD');
        const spikeThreshold = utils.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_THRESHOLD');
        return {
            'http_req_duration{test_type:smoke}': ['p(99)<' + smokeThreshold],
            'http_req_duration{test_type:average}': ['p(99)<' + averageThreshold],
            'http_req_duration{test_type:stress}': ['p(99)<' + stressThreshold],
            'http_req_duration{test_type:spike}': ['p(99)<' + spikeThreshold],
            'checks{scenario:smoke}': ['rate>0.99'],
            'checks{scenario:average}': ['rate>0.99'],
            'checks{scenario:stress}': ['rate>0.99'],
            'checks{scenario:spike}': ['rate>0.75'],
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
            startTime: startTime + 's',
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

    * requestGenerator(numberOfUsers) {
        for (let i = 0; i < numberOfUsers; i++) {
            const email = faker.person.email();
            const initials = faker.person.name();
            const password = faker.internet.password(true, true, true, false, false, 60);

            const request = {
                method: 'POST',
                url: this.getBaseHttpUrl(),
                body: JSON.stringify({
                    email,
                    password,
                    initials,
                }),
                params: this.getJsonHeader(),
            };

            yield [request, email, password];
        }
    }

    insertUsers(numberOfUsers) {
        const batchSize = this.getFromEnv('LOAD_TEST_BATCH_SIZE');
        const generator = this.requestGenerator(numberOfUsers);
        const batch = [];
        const userPasswords = [];
        const users = [];

        for (let i = 0; i < numberOfUsers; i += batchSize) {
            for (let j = 0; j < batchSize; j++) {
                const {value, done} = generator.next();
                const [request, email, password] = value;
                batch.push(request);
                userPasswords[email] = password;
            }

            const responses = http.batch(batch);

            responses.forEach((response) => {
                const user = JSON.parse(response.body);
                user.password = userPasswords[user.email];
                users.push(user);
            });

            batch.length = 0;
            userPasswords.length = 0;
        }

        return users;
    }
}

export const utils = new Utils();
