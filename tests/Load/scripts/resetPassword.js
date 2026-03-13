import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import AuthFlowUtils from '../utils/authFlowUtils.js';

const scenarioName = 'resetPassword';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const authFlowUtils = new AuthFlowUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users,
  };
}

export const options = scenarioUtils.getOptions();

export default function resetPassword(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const result = authFlowUtils.requestPasswordReset(user.email);
  utils.checkResponse(
    result.response,
    'request password reset is status 204',
    res => res.status === 204
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
