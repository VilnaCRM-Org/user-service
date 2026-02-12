import http from 'k6/http';
import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'signin';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users: users,
  };
}

export const options = scenarioUtils.getOptions();

export default function signIn(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const payload = JSON.stringify({
    email: user.email,
    password: user.password,
    rememberMe: false,
  });

  const response = http.post(`${utils.getBaseUrl()}/signin`, payload, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
