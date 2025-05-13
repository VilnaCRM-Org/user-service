import http from 'k6/http';

import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';


const scenarioName = 'createUserBatch';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const batchSize = utils.getConfig().endpoints[scenarioName].batchSize;
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function createUser() {
  const generator = insertUsersUtils.usersGenerator(batchSize);
  const batch = [];

  for (let userIndex = 0; userIndex < batchSize; userIndex++) {
    batch.push(generator.next().value);
  }

  const payload = JSON.stringify({
    users: batch,
  });

  const response = http.post(`${utils.getBaseHttpUrl()}/batch`, payload, utils.getJsonHeader());

  utils.checkResponse(response, 'is status 201', res => res.status === 201);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
