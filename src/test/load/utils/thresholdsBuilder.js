export default class ThresholdsBuilder {
  constructor() {
    this.thresholds = {};
  }

  addThreshold(testType, config) {
    this.thresholds[`http_req_duration{test_type:${testType}}`] = ['p(99)<' + config.threshold];
    this.thresholds[`checks{scenario:${testType}}`] = ['rate>0.99'];
    return this;
  }

  build() {
    return this.thresholds;
  }
}
