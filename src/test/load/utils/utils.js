import { check } from 'k6';

export default class Utils {
  constructor() {
    const { protocol, host, port } = this.getConfig();

    this.baseUrl = `${protocol}://${host}:${port}`;
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

  shouldExecuteScenario(variable) {
    return __ENV[variable];
  }

  checkResponse(response, checkName, checkFunction) {
    check(response, {
      [checkName]: res => checkFunction(res),
    });
  }
}
