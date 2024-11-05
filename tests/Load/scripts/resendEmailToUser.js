import http from 'k6/http';
import counter from 'k6/x/counter';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'resendEmailToUser';

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

export default function resendEmail(data) {
  const user = data.users[counter.up()];
  utils.checkUserIsDefined(user);

  const { id } = user;

  const response = http.post(
    `${utils.getBaseHttpUrl()}/${id}/resend-confirmation-email`,
    JSON.stringify(null),
    utils.getJsonHeader()
  );

  utils.checkResponse(response, 'is status 200', (res) => res.status === 200);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
