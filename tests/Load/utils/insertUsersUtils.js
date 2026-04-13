import http from 'k6/http';
import { sleep } from 'k6';

export default class InsertUsersUtils {
  constructor(utils, scenarioName) {
    this.utils = utils;
    this.config = utils.getConfig();
    this.scenarioName = scenarioName;
    this.additionalUsersRatio = 1.1;
    this.smokeConfig = this.config.endpoints[scenarioName].smoke;
    this.averageConfig = this.config.endpoints[scenarioName].average;
    this.stressConfig = this.config.endpoints[scenarioName].stress;
    this.spikeConfig = this.config.endpoints[scenarioName].spike;
  }

  loadInsertedUsers() {
    return JSON.parse(open(`/loadTests/${this.utils.getConfig()['usersFileName']}`));
  }

  *usersGenerator(numberOfUsers) {
    for (let i = 0; i < numberOfUsers; i++) {
      const user = this.utils.generateUser();

      yield user;
    }
  }

  prepareUserBatch(batchSize) {
    const generator = this.usersGenerator(batchSize);
    const batch = [];
    const userPasswords = {};

    for (let requestIndex = 0; requestIndex < batchSize; requestIndex++) {
      const user = generator.next().value;
      batch.push(user);
      userPasswords[user.email] = user.password;
    }

    return [batch, userPasswords];
  }

  *requestGenerator(numberOfRequest, batchSize, serviceToken) {
    for (let i = 0; i < numberOfRequest; i++) {
      const [batch, userPasswords] = this.prepareUserBatch(batchSize);

      const payload = JSON.stringify({
        users: batch,
      });

      const request = {
        method: 'POST',
        url: `${this.utils.getBaseHttpUrl()}/batch`,
        body: payload,
        params: this.utils.getJsonHeaderWithAuth(serviceToken),
      };

      yield [request, userPasswords];
    }
  }

  prepareRequestBatch(numberOfUsers, batchSize, serviceToken) {
    const numberOfRequests = Math.ceil(numberOfUsers / batchSize);
    const generator = this.requestGenerator(numberOfRequests, batchSize, serviceToken);
    const requestBatch = [];

    for (let requestIndex = 0; requestIndex < numberOfRequests; requestIndex++) {
      const { value, done } = generator.next();
      if (done) break;
      const [request, passwords] = value;
      requestBatch.push({
        request,
        passwords,
      });
    }

    return requestBatch;
  }

  insertUsers(numberOfUsers) {
    const serviceToken = this.utils.getCLIVariable('serviceToken');
    if (serviceToken === 'undefined' || serviceToken === '') {
      throw new Error('Missing serviceToken environment variable for batch user creation');
    }

    const configuredBatchSize = this.config.endpoints.createUserBatch?.batchSize ?? 10;
    const safeBatchSize = configuredBatchSize > 0 ? configuredBatchSize : 10;
    const batchSize = Math.min(safeBatchSize, numberOfUsers);
    const users = [];
    let pendingRequests = this.prepareRequestBatch(numberOfUsers, batchSize, serviceToken);
    const maxAttempts = 3;

    for (let attempt = 1; attempt <= maxAttempts; attempt += 1) {
      const responses = http.batch(pendingRequests.map(({ request }) => request));
      const retryRequests = [];

      responses.forEach((response, index) => {
        const batchRequest = pendingRequests[index];

        if (response.status === 200 || response.status === 201) {
          try {
            JSON.parse(response.body).forEach(user => {
              user.password = batchRequest.passwords[user.email];
              users.push(user);
            });
          } catch (parseError) {
            console.log(`Failed to parse response body for batch ${index}: ${response.body}`);
            throw new Error(`Failed to parse batch response: ${response.body}`);
          }

          return;
        }

        if (response.status >= 500 && attempt < maxAttempts) {
          console.log(
            `Batch request ${index + 1} failed with status ${response.status}; retrying attempt ${attempt + 1}/${maxAttempts}.`
          );
          retryRequests.push(batchRequest);
          return;
        }

        console.log(
          `Batch request ${index + 1} failed with status ${response.status}: ${response.body}`
        );
        throw new Error(`Batch request failed with status ${response.status}: ${response.body}`);
      });

      if (retryRequests.length === 0) {
        return users;
      }

      pendingRequests = retryRequests;
      sleep(1);
    }

    throw new Error(`Batch request failed after ${maxAttempts} attempts.`);
  }

  countRequestForRampingRate(startRps, targetRps, duration) {
    const acceleration = (targetRps - startRps) / duration;

    return Math.round(startRps * duration + (acceleration * duration * duration) / 2);
  }

  prepareUsers() {
    return this.insertUsers(this.countTotalRequest());
  }

  countTotalRequest() {
    const requestsMap = {
      run_smoke: this.countSmokeRequest.bind(this),
      run_average: this.countAverageRequest.bind(this),
      run_stress: this.countStressRequest.bind(this),
      run_spike: this.countSpikeRequest.bind(this),
    };

    let totalRequests = 0;

    for (const key in requestsMap) {
      if (this.utils.getCLIVariable(key) !== 'false') {
        totalRequests += requestsMap[key]();
      }
    }

    return Math.round(totalRequests * this.additionalUsersRatio);
  }

  countSmokeRequest() {
    return this.smokeConfig.rps * this.smokeConfig.duration;
  }

  countAverageRequest() {
    return this.countDefaultRequests(this.averageConfig);
  }

  countStressRequest() {
    return this.countDefaultRequests(this.stressConfig);
  }

  countDefaultRequests(config) {
    const riseRequests = this.countRequestForRampingRate(0, config.rps, config.duration.rise);

    const plateauRequests = config.rps * config.duration.plateau;

    const fallRequests = this.countRequestForRampingRate(config.rps, 0, config.duration.fall);

    return riseRequests + plateauRequests + fallRequests;
  }

  countSpikeRequest() {
    const spikeRiseRequests = this.countRequestForRampingRate(
      0,
      this.spikeConfig.rps,
      this.spikeConfig.duration.rise
    );

    const spikeFallRequests = this.countRequestForRampingRate(
      this.spikeConfig.rps,
      0,
      this.spikeConfig.duration.fall
    );

    return spikeRiseRequests + spikeFallRequests;
  }
}
