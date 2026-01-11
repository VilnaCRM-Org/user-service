import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'deleteUser';

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

export default function deleteUser(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const { id } = user;

  const response = http.del(`${utils.getBaseHttpUrl()}/${id}`, null, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 204', res => res.status === 204);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
