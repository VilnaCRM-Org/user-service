import faker from "k6/x/faker";
import http from 'k6/http';
import * as dotenv from "k6/x/dotenv";

const env = dotenv.parse(open(".env.test"));

export class Utils {
    constructor(scenarioName) {
        const host = this.getFromEnv('LOAD_TEST_API_HOST');
        const mailCatcherPort = this.getFromEnv('LOAD_TEST_MAILCATCHER_PORT');
        const delay = Number(this.getFromEnv('LOAD_TEST_DELAY_BETWEEN_SCENARIOS'));

        this.loadTestPrefix = 'LOAD_TEST_';

        this.baseUrl = `https://${host}/api`;
        this.baseHttpUrl = this.baseUrl + '/users';
        this.baseGraphQLUrl = this.baseUrl + '/graphql';
        this.mailCatcherUrl = `http://${host}:${mailCatcherPort}/messages`;

        this.scenarioName = scenarioName;
        this.smokeRps = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_SMOKE_RPS'));
        this.smokeVus = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_SMOKE_VUS'));
        this.smokeDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_SMOKE_DURATION'));
        this.averageRps = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_RPS'));
        this.averageVus = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_VUS'));
        this.averageRiseDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_RISE'));
        this.averagePlateauDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_PLATEAU'));
        this.averageFallDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_AVERAGE_DURATION_FALL'));
        this.stressRps = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_RPS'));
        this.stressVus = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_VUS'));
        this.stressRiseDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_RISE'));
        this.stressPlateauDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_PLATEAU'));
        this.stressFallDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_STRESS_DURATION_FALL'));
        this.spikeRps = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_RPS'));
        this.spikeVus = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_VUS'));
        this.spikeRiseDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_DURATION_RISE'));
        this.spikeFallDuration = Number(this.getFromEnv(this.loadTestPrefix + scenarioName + '_SPIKE_DURATION_FALL'));
        this.averageTestStartTime = this.smokeDuration + delay;
        this.stressTestStartTime = this.averageTestStartTime
            + this.averageRiseDuration
            + this.averagePlateauDuration
            + this.averageFallDuration + delay;
        this.spikeTestStartTime = this.stressTestStartTime
            + this.stressRiseDuration
            + this.stressPlateauDuration
            + this.stressFallDuration + delay;
    }

    getBaseUrl() {
        return this.baseUrl;
    }

    getBaseHttpUrl() {
        return this.baseHttpUrl;
    }

    getMailCatcherUrl() {
        return this.mailCatcherUrl;
    }

    getBaseGraphQLUrl() {
        return this.baseGraphQLUrl;
    }

    getFromEnv(varName) {
        return env[varName];
    }

    getOptions() {
        return {
            insecureSkipTLSVerify: true,
            scenarios: this.getScenarios(
                this.scenarioName
            ),
            thresholds: this.getThresholds(
                this.scenarioName
            )
        }
    }

    getScenarios() {
        return {
            smoke: this.getSmokeScenario(
                this.smokeRps,
                this.smokeVus,
                this.smokeDuration,
            ),
            average: this.getAverageScenario(
                this.averageRps,
                this.averageVus,
                this.averageRiseDuration,
                this.averagePlateauDuration,
                this.averageFallDuration,
                this.averageTestStartTime,
            ),
            stress: this.getStressScenario(
                this.stressRps,
                this.stressVus,
                this.stressRiseDuration,
                this.stressPlateauDuration,
                this.stressFallDuration,
                this.stressTestStartTime,
            ),
            spike: this.getSpikeScenario(
                this.spikeRps,
                this.spikeVus,
                this.spikeRiseDuration,
                this.spikeFallDuration,
                this.spikeTestStartTime,
            )
        }
    }

    getThresholds() {
        const smokeThreshold = this.getFromEnv(this.loadTestPrefix + this.scenarioName + '_SMOKE_THRESHOLD');
        const averageThreshold = this.getFromEnv(this.loadTestPrefix + this.scenarioName + '_AVERAGE_THRESHOLD');
        const stressThreshold = this.getFromEnv(this.loadTestPrefix + this.scenarioName + '_STRESS_THRESHOLD');
        const spikeThreshold = this.getFromEnv(this.loadTestPrefix + this.scenarioName + '_SPIKE_THRESHOLD');
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

    async getConfirmationToken(email) {
        let token = null;
        for (let attempt = 0; attempt < utils.getFromEnv('LOAD_TEST_MAX_GETTING_EMAIL_RETRIES'); attempt++) {
            const result = await this.retrieveTokenFromMailCatcher(email);
            if (result) {
                token = result;
                break;
            }
        }
        return token;
    }

    async retrieveTokenFromMailCatcher(email) {
        const messages = await http.get(utils.getMailCatcherUrl());
        if (messages.status === 200) {
            let messageId;
            for (const message of JSON.parse(messages.body)) {
                for (const recipient of message.recipients) {
                    if (recipient.includes(`<${email}>`)) {
                        messageId = message.id;
                        break;
                    }
                }
            }

            const message = await http.get(utils.getMailCatcherUrl() + `/${messageId}.source`);

            return this.extractConfirmationToken(message.body)
        }

        return null;
    }

    extractConfirmationToken(emailBody) {
        const tokenRegex = /token - ([a-f0-9]+(?:=\r?\n\s*[a-f0-9]+)*)/i;
        const hexPattern = /[a-f0-9]/gi;
        const match = emailBody.match(tokenRegex);
        if (match && match[1]) {
            const matches = match[1].match(hexPattern);
            return matches.join('');
        }
        return null;
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

    prepareBatch(batchSize) {
        const generator = this.requestGenerator(batchSize);
        const batch = [];
        const userPasswords = {};

        for (let requestIndex = 0; requestIndex < batchSize; requestIndex++) {
            const {value, done} = generator.next();
            if (done) break;
            const [request, email, password] = value;
            batch.push(request);
            userPasswords[email] = password;
        }

        return [batch, userPasswords];
    }

    insertUsers(numberOfUsers) {
        const batchSize = this.getFromEnv('LOAD_TEST_BATCH_SIZE');

        const users = [];

        for (let createdUsers = 0; createdUsers < numberOfUsers; createdUsers += batchSize) {
            const [batch, userPasswords] = this.prepareBatch(batchSize);

            const responses = http.batch(batch);

            responses.forEach((response) => {
                const user = JSON.parse(response.body);
                user.password = userPasswords[user.email];
                users.push(user);
            });
        }

        return users;
    }

    countRequestForRampingRate(
        startRps,
        targetRps,
        duration
    ) {
        const acceleration = (targetRps - startRps) / duration;

        return startRps * duration + acceleration * duration * duration / 2;
    }

    prepareUsers() {
        let totalRequest = Number(this.getFromEnv('LOAD_TEST_USERS_TO_INSERT'));

        if(this.getFromEnv('LOAD_TEST_AUTO_DETERMINE_USERS_TO_INSERT') === 'true'){
            const smokeRequests = this.smokeRps * this.smokeDuration;
            const averageRequests =
                this.countRequestForRampingRate(0, this.averageRps, this.averageRiseDuration)
                + this.averageRps * this.averagePlateauDuration
                + this.countRequestForRampingRate(this.averageRps, 0, this.averageFallDuration);
            const stressRequests =
                this.countRequestForRampingRate(0, this.stressRps, this.stressRiseDuration)
                + this.stressRps * this.stressPlateauDuration
                + this.countRequestForRampingRate(this.stressRps, 0, this.stressFallDuration);
            const spikeRequests =
                this.countRequestForRampingRate(0, this.spikeRps, this.spikeRiseDuration)
                + this.countRequestForRampingRate(this.spikeRps, 0, this.spikeFallDuration);

            totalRequest = smokeRequests + averageRequests + stressRequests + spikeRequests;
        }

        return this.insertUsers(totalRequest);
    }
}
