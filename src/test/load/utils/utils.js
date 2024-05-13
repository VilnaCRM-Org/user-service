import { check } from 'k6';

export default class Utils {
  constructor() {
    const { host, port } = this.getConfig();

    this.baseUrl = `https://${host}:${port}`;
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

  getCLIVariable(variable) {
    return `${__ENV[variable]}`;
  }

  checkResponse(response, checkName, checkFunction) {
    check(response, {
      [checkName]: res => checkFunction(res),
    });
  }
}
