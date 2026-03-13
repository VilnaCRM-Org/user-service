import exec from 'k6/execution';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import GraphQLAuthFlowUtils from '../utils/graphQLAuthFlowUtils.js';
import AuthFlowUtils from '../utils/authFlowUtils.js';

const scenarioName = 'graphQLConfirmPasswordReset';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const graphQLAuthFlowUtils = new GraphQLAuthFlowUtils(utils);
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

export default async function graphQLConfirmPasswordReset(data) {
  const num = exec.scenario.iterationInTest + 1;
  const user = data.users[num % data.users.length];
  utils.checkUserIsDefined(user);

  const token = await mailCatcherUtils.getPasswordResetToken(num);

  const newPassword = utils.generateValidPassword();
  const result = graphQLAuthFlowUtils.confirmPasswordReset(token, newPassword);

  utils.checkResponse(
    result.response,
    'graphQL confirm password reset is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    result.response,
    'graphQL confirm password reset returns empty user payload',
    () =>
      result.body?.data?.confirmPasswordResetUser !== undefined &&
      result.body.data.confirmPasswordResetUser.user === null
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
