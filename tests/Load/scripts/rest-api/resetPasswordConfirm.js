import exec from 'k6/execution';

import AuthFlowUtils from '../../utils/authFlowUtils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'resetPasswordConfirm';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const authFlowUtils = new AuthFlowUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  mailCatcherUtils.clearMessages();

  for (let i = 0; i < users.length; i++) {
    authFlowUtils.requestPasswordReset(users[i].email);
  }

  const expectedEmails = users.length;
  if (!mailCatcherUtils.waitForEmails(expectedEmails, 60)) {
    throw new Error(
      `Only ${mailCatcherUtils.getMessageCount()} of ${expectedEmails} password reset emails arrived`
    );
  }
}

export const options = scenarioUtils.getOptions();

export default async function resetPasswordConfirm() {
  const messageNumber = insertUsersUtils.getMessageNumberForProfile(
    exec.scenario.name,
    exec.scenario.iterationInTest
  );
  const wrappedMessageNumber = insertUsersUtils.wrapMessageNumberForUsers(users, messageNumber);
  const user = users[wrappedMessageNumber - 1];

  utils.checkUserIsDefined(user);

  const token = await mailCatcherUtils.getPasswordResetToken(wrappedMessageNumber);

  const newPassword = utils.generateValidPassword();
  const result = authFlowUtils.confirmPasswordReset(token, newPassword);

  utils.checkResponse(
    result.response,
    'confirm password reset is status 204',
    res => res.status === 204
  );
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
