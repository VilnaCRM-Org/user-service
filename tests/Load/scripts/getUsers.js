import http from 'k6/http';

import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';

const scenarioName = 'getUsers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const usersToGetInOneRequest = utils.getConfig().endpoints[scenarioName].usersToGetInOneRequest;
const mailCatcherUtils = new MailCatcherUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users: users,
  };
}

export const options = scenarioUtils.getOptions();

export default function getUsers() {
  let page = utils.getRandomNumber(1, 5);

  const response = http.get(
    `${utils.getBaseHttpUrl()}?page=${page}&itemsPerPage=${usersToGetInOneRequest}`,
    utils.getJsonHeader()
  );

  utils.checkResponse(response, 'is status 200', res => res.status === 200);
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
