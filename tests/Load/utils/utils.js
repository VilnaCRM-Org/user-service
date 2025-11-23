import { check } from 'k6';
import http from 'k6/http';

export default class Utils {
  constructor() {
    const host = this.getConfig().apiHost;
    const port = this.getConfig().apiPort;

    this.baseUrl = `http://${host}:${port}/api`;
    this.baseHttpUrl = this.baseUrl + '/users';
    this.baseGraphQLUrl = this.baseUrl + '/graphql';
    this.graphQLIdPrefix = '/api/users/';
  }

  getConfig() {
    try {
      return JSON.parse(open('../config.json'));
    } catch (error) {
      try {
        return JSON.parse(open('../config.json.dist'));
      } catch (error) {
        console.log('Error occurred while trying to open config');
      }
    }
  }

  getBaseUrl() {
    return this.baseUrl;
  }

  getBaseHttpUrl() {
    return this.baseHttpUrl;
  }

  getBaseGraphQLUrl() {
    return this.baseGraphQLUrl;
  }

  getGraphQLIdPrefix() {
    return this.graphQLIdPrefix;
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

  getCLIVariable(variable) {
    return `${__ENV[variable]}`;
  }

  checkUserIsDefined(user) {
    check(user, { 'user is defined': u => u !== undefined });
  }

  generateUser() {
    // Generate unique email using multiple entropy sources
    const email = this.generateUniqueEmail();

    const firstNames = ['John', 'Jane', 'Mike', 'Sarah', 'David', 'Lisa', 'Robert', 'Emily'];
    const lastNames = [
      'Smith',
      'Johnson',
      'Williams',
      'Brown',
      'Jones',
      'Garcia',
      'Miller',
      'Davis',
    ];
    const firstName = firstNames[Math.floor(Math.random() * firstNames.length)];
    const lastName = lastNames[Math.floor(Math.random() * lastNames.length)];
    const initials = `${firstName}${lastName}`;

    const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 60; i++) {
      password += charset.charAt(Math.floor(Math.random() * charset.length));
    }

    return {
      email,
      password,
      initials,
    };
  }

  generateUniqueEmail() {
    // Get K6 runtime information for uniqueness
    const vuId = typeof __VU !== 'undefined' ? __VU : 1;
    const iteration = typeof __ITER !== 'undefined' ? __ITER : 0;

    // High-precision timestamp components
    const timestamp = Date.now();
    const microseconds =
      typeof performance !== 'undefined' && performance.now
        ? performance.now().toString().replace('.', '').substring(0, 8)
        : Math.random().toString().replace('.', '').substring(0, 8);

    // Additional entropy sources
    const randomString1 = Math.random().toString(36).substring(2, 10); // 8 chars
    const randomString2 = Math.random().toString(36).substring(2, 8); // 6 chars
    const processEntropy =
      typeof process !== 'undefined' && process.hrtime
        ? process.hrtime()[1].toString().substring(0, 6)
        : Math.floor(Math.random() * 1000000)
            .toString()
            .padStart(6, '0');

    // Domain selection
    const domains = ['example.com', 'test.org', 'demo.net'];
    const domain = domains[Math.floor(Math.random() * domains.length)];

    // Construct unique email with multiple entropy sources
    const uniqueId = `${vuId}_${iteration}_${timestamp}_${microseconds}_${randomString1}_${randomString2}_${processEntropy}`;

    return `user_${uniqueId}@${domain}`;
  }

  checkResponse(response, checkName, checkFunction) {
    check(response, { [checkName]: res => checkFunction(res) });
  }

  registerUser(user) {
    const payload = JSON.stringify(user);

    return http.post(this.getBaseHttpUrl(), payload, this.getJsonHeader());
  }
}
