import exec from 'k6/execution';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import AuthFlowUtils from '../../utils/authFlowUtils.js';

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
  console.log(`Waiting for ${expectedEmails} password reset emails to arrive in mailcatcher...`);
  const arrived = mailCatcherUtils.waitForEmails(expectedEmails, 60);
  if (!arrived) {
    console.log(
      `Warning: Only ${mailCatcherUtils.getMessageCount()} of ${expectedEmails} emails arrived`
    );
  } else {
    console.log(`All ${expectedEmails} password reset emails arrived`);
  }

  return {
    users: users,
  };
}

export const options = scenarioUtils.getOptions();

export default async function resetPasswordConfirm(data) {
  const num = exec.scenario.iterationInTest + 1;
  const user = data.users[num % data.users.length];
  utils.checkUserIsDefined(user);

  const token = await mailCatcherUtils.getPasswordResetToken(num);

  const newPassword = utils.generateValidPassword();
  const result = authFlowUtils.confirmPasswordReset(token, newPassword);

  utils.checkResponse(
    result.response,
    'confirm password reset is status 204',
    res => res.status === 204
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
