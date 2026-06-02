import { sleep } from 'k6';
import { SharedArray } from 'k6/data';
import http from 'k6/http';

export default class InsertUsersUtils {
  constructor(utils, scenarioName) {
    this.utils = utils;
    this.config = utils.getConfig();
    this.scenarioName = scenarioName;
    this.additionalUsersRatio = 1.1;
    this.seedRequestConcurrency = this.parsePositiveInteger(
      this.utils.getEnv('LOAD_TEST_SEED_REQUEST_CONCURRENCY'),
      5
    );
    this.seedRequestRetries = this.parseNonNegativeInteger(
      this.utils.getEnv('LOAD_TEST_SEED_REQUEST_RETRIES'),
      3
    );
    this.seedRetryDelaySeconds = this.parsePositiveNumber(
      this.utils.getEnv('LOAD_TEST_SEED_RETRY_DELAY_SECONDS'),
      2
    );
    this.seedRequestTimeout = this.utils.getEnv('LOAD_TEST_SEED_REQUEST_TIMEOUT') ?? '120s';
    this.smokeConfig = this.config.endpoints[scenarioName].smoke;
    this.averageConfig = this.config.endpoints[scenarioName].average;
    this.stressConfig = this.config.endpoints[scenarioName].stress;
    this.spikeConfig = this.config.endpoints[scenarioName].spike;
    this.profileDefinitions = [
      {
        name: 'smoke',
        key: 'run_smoke',
        countRequests: () => this.countSmokeRequest(),
      },
      {
        name: 'average',
        key: 'run_average',
        countRequests: () => this.countAverageRequest(),
      },
      {
        name: 'stress',
        key: 'run_stress',
        countRequests: () => this.countStressRequest(),
      },
      {
        name: 'spike',
        key: 'run_spike',
        countRequests: () => this.countSpikeRequest(),
      },
    ];
  }

  loadInsertedUsers() {
    const usersFileName = this.utils.getConfig().usersFileName;

    return new SharedArray(`${this.scenarioName}-inserted-users`, () =>
      JSON.parse(open(`/loadTests/${usersFileName}`))
    );
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

  prepareRequest(userCount, serviceToken) {
    const [batch, userPasswords] = this.prepareUserBatch(userCount);

    const payload = JSON.stringify({
      users: batch,
    });

    return {
      request: {
        method: 'POST',
        url: `${this.utils.getBaseHttpUrl()}/batch`,
        body: payload,
        params: {
          ...this.utils.getJsonHeaderWithAuth(serviceToken),
          timeout: this.seedRequestTimeout,
        },
      },
      passwords: userPasswords,
      userCount,
    };
  }

  *requestGenerator(numberOfUsers, batchSize, serviceToken) {
    let remainingUsers = numberOfUsers;

    while (remainingUsers > 0) {
      const userCount = Math.min(batchSize, remainingUsers);
      remainingUsers -= userCount;

      yield this.prepareRequest(userCount, serviceToken);
    }
  }

  prepareRequestBatch(numberOfUsers, batchSize, serviceToken) {
    const generator = this.requestGenerator(numberOfUsers, batchSize, serviceToken);
    const requestBatch = [];
    let nextRequest = generator.next();

    while (!nextRequest.done) {
      requestBatch.push(nextRequest.value);
      nextRequest = generator.next();
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
    const pendingRequests = this.prepareRequestBatch(numberOfUsers, batchSize, serviceToken);
    const users = [];

    for (let index = 0; index < pendingRequests.length; index += this.seedRequestConcurrency) {
      const chunk = pendingRequests.slice(index, index + this.seedRequestConcurrency);
      users.push(...this.insertRequestChunk(chunk, serviceToken));
    }

    return users;
  }

  insertRequestChunk(requests, serviceToken) {
    let pendingRequests = requests;
    const users = [];

    for (let attempt = 0; attempt <= this.seedRequestRetries; attempt++) {
      const failedRequests = [];
      const responses = http.batch(pendingRequests.map(({ request }) => request));

      responses.forEach((response, index) => {
        const batchRequest = pendingRequests[index];

        if (response.status === 200 || response.status === 201) {
          users.push(...this.parseCreatedUsers(response, batchRequest.passwords));

          return;
        }

        if (this.isRetryableResponse(response) && attempt < this.seedRequestRetries) {
          failedRequests.push(this.prepareRequest(batchRequest.userCount, serviceToken));

          return;
        }

        throw new Error(`Batch request failed with status ${response.status}: ${response.body}`);
      });

      if (failedRequests.length === 0) {
        return users;
      }

      pendingRequests = failedRequests;
      sleep(this.seedRetryDelaySeconds * (attempt + 1));
    }

    return users;
  }

  parseCreatedUsers(response, passwords) {
    try {
      return JSON.parse(response.body).map(user => {
        user.password = passwords[user.email];

        return user;
      });
    } catch (parseError) {
      throw new Error(`Failed to parse batch response: ${response.body}`);
    }
  }

  isRetryableResponse(response) {
    return (
      response.status === 0 ||
      response.status === 408 ||
      response.status === 429 ||
      response.status >= 500
    );
  }

  parsePositiveInteger(value, defaultValue) {
    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : defaultValue;
  }

  parseNonNegativeInteger(value, defaultValue) {
    const parsed = Number.parseInt(value, 10);

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : defaultValue;
  }

  parsePositiveNumber(value, defaultValue) {
    const parsed = Number.parseFloat(value);

    return Number.isFinite(parsed) && parsed > 0 ? parsed : defaultValue;
  }

  countRequestForRampingRate(startRps, targetRps, duration) {
    const acceleration = (targetRps - startRps) / duration;

    return Math.round(startRps * duration + (acceleration * duration * duration) / 2);
  }

  prepareUsers() {
    return this.insertUsers(this.countTotalRequest());
  }

  countTotalRequest() {
    const totalRequests = this.getProfileDefinitions()
      .filter(profile => this.utils.getCLIVariable(profile.key) !== 'false')
      .reduce((requests, profile) => requests + profile.countRequests(), 0);

    return Math.round(totalRequests * this.additionalUsersRatio);
  }

  countPreparedUsersBeforeProfile(profileName) {
    const totalRequests = this.countRequestsBeforeProfile(profileName);

    return Math.round(totalRequests * this.additionalUsersRatio);
  }

  countRequestsBeforeProfile(profileName) {
    let requests = 0;

    for (const profile of this.getProfileDefinitions()) {
      if (profile.name === profileName) {
        return requests;
      }

      if (this.utils.getCLIVariable(profile.key) !== 'false') {
        requests += profile.countRequests();
      }
    }

    throw new Error(`Unknown load test profile "${profileName}"`);
  }

  getMessageNumberForProfile(profileName, iterationInTest) {
    return this.countPreparedUsersBeforeProfile(profileName) + iterationInTest + 1;
  }

  pickUser(users, userIndex) {
    if (users.length === 0) {
      throw new Error(`No inserted users are available for "${this.scenarioName}"`);
    }

    const user = users[userIndex % users.length];
    this.utils.checkUserIsDefined(user);

    return user;
  }

  pickUserForProfile(users, profileName, iterationInTest) {
    return this.pickUser(users, this.getMessageNumberForProfile(profileName, iterationInTest) - 1);
  }

  wrapMessageNumberForUsers(users, messageNumber) {
    if (users.length === 0) {
      throw new Error(`No inserted users are available for "${this.scenarioName}"`);
    }

    return ((messageNumber - 1) % users.length) + 1;
  }

  getProfileDefinitions() {
    return this.profileDefinitions;
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
