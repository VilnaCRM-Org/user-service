import {Utils} from "./utils.js";
import {Env} from "./env.js";
import http from 'k6/http';
import faker from "k6/x/faker";

export class InsertUsersUtils{
    constructor(scenarioName) {
        this.utils = new Utils();
        this.env = new Env();
        this.scenarioConfig = this.env.getScenarioConfig(scenarioName);
    }

    * requestGenerator(numberOfUsers) {
        for (let i = 0; i < numberOfUsers; i++) {
            const email = faker.person.email();
            const initials = faker.person.name();
            const password = faker.internet.password(true, true, true, false, false, 60);

            const request = {
                method: 'POST',
                url: this.utils.getBaseHttpUrl(),
                body: JSON.stringify({
                    email,
                    password,
                    initials,
                }),
                params: this.utils.getJsonHeader(),
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
        const batchSize = this.env.get('LOAD_TEST_BATCH_SIZE');

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
        let totalRequest = Number(this.env.get('LOAD_TEST_USERS_TO_INSERT'));

        if(this.env.get('LOAD_TEST_AUTO_DETERMINE_USERS_TO_INSERT') === 'true'){
            const smokeRequests = this.scenarioConfig.smokeRps * this.scenarioConfig.smokeDuration;
            const averageRequests =
                this.countRequestForRampingRate(0, this.scenarioConfig.averageRps, this.scenarioConfig.averageRiseDuration)
                + this.scenarioConfig.averageRps * this.scenarioConfig.averagePlateauDuration
                + this.countRequestForRampingRate(this.scenarioConfig.averageRps, 0, this.scenarioConfig.averageFallDuration);
            const stressRequests =
                this.countRequestForRampingRate(0, this.scenarioConfig.stressRps, this.scenarioConfig.stressRiseDuration)
                + this.scenarioConfig.stressRps * this.scenarioConfig.stressPlateauDuration
                + this.countRequestForRampingRate(this.scenarioConfig.stressRps, 0, this.scenarioConfig.stressFallDuration);
            const spikeRequests =
                this.countRequestForRampingRate(0, this.scenarioConfig.spikeRps, this.scenarioConfig.spikeRiseDuration)
                + this.countRequestForRampingRate(this.scenarioConfig.spikeRps, 0, this.scenarioConfig.spikeFallDuration);

            totalRequest = smokeRequests + averageRequests + stressRequests + spikeRequests;
        }

        return this.insertUsers(totalRequest);
    }
}