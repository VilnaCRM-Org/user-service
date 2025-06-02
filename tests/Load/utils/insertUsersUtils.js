import http from 'k6/http';
import exec from 'k6/x/exec';

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

  execInsertUsersCommand() {
    const runSmoke = this.utils.getCLIVariable('run_smoke') || 'true';
    const runAverage = this.utils.getCLIVariable('run_average') || 'true';
    const runStress = this.utils.getCLIVariable('run_stress') || 'true';
    const runSpike = this.utils.getCLIVariable('run_spike') || 'true';
    exec.command('make', [
      `SCENARIO_NAME=${this.scenarioName}`,
      `RUN_SMOKE=${runSmoke}`,
      `RUN_AVERAGE=${runAverage}`,
      `RUN_STRESS=${runStress}`,
      `RUN_SPIKE=${runSpike}`,
      `load-tests-prepare-users`,
    ]);
  }

  loadInsertedUsers() {
    return JSON.parse(open(`../${this.utils.getConfig()['usersFileName']}`));
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

  *requestGenerator(numberOfRequest, batchSize) {
    for (let i = 0; i < numberOfRequest; i++) {
      const [batch, userPasswords] = this.prepareUserBatch(batchSize);

      const payload = JSON.stringify({
        users: batch,
      });

      const request = {
        method: 'POST',
        url: `${this.utils.getBaseHttpUrl()}/batch`,
        body: payload,
        params: this.utils.getJsonHeader(),
      };

      yield [request, userPasswords];
    }
  }

  prepareRequestBatch(numberOfUsers, batchSize) {
    const numberOfRequests = Math.ceil(numberOfUsers / batchSize);
    const generator = this.requestGenerator(numberOfRequests, batchSize);
    const requestBatch = [];
    const userPasswords = {};

    for (let requestIndex = 0; requestIndex < numberOfRequests; requestIndex++) {
      const { value, done } = generator.next();
      if (done) break;
      const [request, passwords] = value;
      requestBatch.push(request);
      Object.assign(userPasswords, passwords);
    }

    return [requestBatch, userPasswords];
  }

  insertUsers(numberOfUsers) {
    const batchSize = Math.min(this.config.batchSize, numberOfUsers);

    const users = [];

    const [requestBatch, userPasswords] = this.prepareRequestBatch(numberOfUsers, batchSize);

    try {
      const responses = http.batch(requestBatch);
      responses.forEach(response => {
        JSON.parse(response.body).forEach(user => {
          user.password = userPasswords[user.email];
          users.push(user);
        });
      });
    } catch () {
      throw new Error(
        'Error occurred during user insertion, try to lower batchSize in a config file'
      );
    }

    return users;
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
