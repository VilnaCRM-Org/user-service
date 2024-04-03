import http from 'k6/http';
import faker from "k6/x/faker";

export class InsertUsersUtils {
    constructor(utils, scenarioName) {
        this.utils = utils;
        this.config = utils.getConfig();
        this.additionalUsersRatio = 1.1;
        this.smokeConfig = this.config.endpoints[scenarioName].smoke;
        this.averageConfig = this.config.endpoints[scenarioName].average;
        this.stressConfig = this.config.endpoints[scenarioName].stress;
        this.spikeConfig = this.config.endpoints[scenarioName].spike;
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
        const batchSize = this.config.batchSize;

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

        return Math.round((startRps * duration + acceleration * duration * duration / 2));
    }

    prepareUsers() {
        let totalRequest = this.config.usersToInsert;

        if (this.config.autoDetermineUsersToInsert) {
            totalRequest = this.countTotalRequest();
        }

        return this.insertUsers(totalRequest);
    }

    countTotalRequest() {
        let smokeRequests = 0;
        let averageRequests = 0;
        let stressRequests = 0;
        let spikeRequests = 0;

        if (this.utils.getCLIVariable('run_smoke') !== 'false') {
            smokeRequests = this.countSmokeRequest();
        }
        if (this.utils.getCLIVariable('run_average') !== 'false') {
            averageRequests = this.countAverageRequest();
        }
        if (this.utils.getCLIVariable('run_stress') !== 'false') {
            stressRequests = this.countStressRequest();
        }
        if (this.utils.getCLIVariable('run_spike') !== 'false') {
            spikeRequests = this.countSpikeRequest();
        }

        return Math.round(
            (smokeRequests +
                averageRequests +
                stressRequests +
                spikeRequests
            ) * this.additionalUsersRatio
        );
    }

    countSmokeRequest() {
        return this.smokeConfig.rps * this.smokeConfig.duration
    }

    countAverageRequest() {
        const averageRiseRequests =
            this.countRequestForRampingRate(
                0,
                this.averageConfig.rps,
                this.averageConfig.duration.rise
            );

        const averagePlateauRequests =
            this.averageConfig.rps * this.averageConfig.duration.plateau;

        const averageFallRequests =
            this.countRequestForRampingRate(
                this.averageConfig.rps,
                0, this.averageConfig.duration.fall
            );

        return averageRiseRequests
            + averagePlateauRequests
            + averageFallRequests;
    }

    countStressRequest() {
        const stressRiseRequests =
            this.countRequestForRampingRate(
                0,
                this.stressConfig.rps,
                this.stressConfig.duration.rise
            );

        const stressPlateauRequests =
            this.stressConfig.rps * this.stressConfig.duration.plateau;

        const stressFallRequests =
            this.countRequestForRampingRate(
                this.stressConfig.rps,
                0, this.stressConfig.duration.fall
            );

        return stressRiseRequests
            + stressPlateauRequests
            + stressFallRequests;
    }

    countSpikeRequest() {
        const spikeRiseRequests =
            this.countRequestForRampingRate(
                0,
                this.spikeConfig.rps,
                this.spikeConfig.duration.rise
            );

        const spikeFallRequests =
            this.countRequestForRampingRate(
                this.spikeConfig.rps,
                0, this.spikeConfig.duration.fall
            );

        return spikeRiseRequests + spikeFallRequests;
    }
}