import { check } from 'k6';

export default class Utils {
  constructor() {
    const { protocol, host, port, params } = this.getConfig();

    this.baseUrl = `${protocol}://${host}:${port}`;
    this.params = params;
  }

  getConfig() {
    try {
      return JSON.parse(open('config.json'));
    } catch (error) {
      return JSON.parse(open('config.json.dist'));
    }
  }

  getBaseUrl() {
    return this.baseUrl;
  }

  getParams() {
    return this.params;
  }

  shouldExecuteScenario(variable) {
    return __ENV[variable];
  }

  checkResponse(response, checkName, checkFunction) {
    check(response, {
      [checkName]: res => checkFunction(res),
    });
  }
}
