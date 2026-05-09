import exec from 'k6/execution';

import AuthFlowUtils from '../../utils/authFlowUtils.js';
import GraphQLAuthFlowUtils from '../../utils/graphQLAuthFlowUtils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

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
  if (!mailCatcherUtils.waitForEmails(expectedEmails, 60)) {
    throw new Error(
      `Only ${mailCatcherUtils.getMessageCount()} of ${expectedEmails} password reset emails arrived`
    );
  }
}

export const options = scenarioUtils.getOptions();

export default async function graphQLConfirmPasswordReset() {
  const messageNumber = insertUsersUtils.getMessageNumberForProfile(
    exec.scenario.name,
    exec.scenario.iterationInTest
  );
  const wrappedMessageNumber = insertUsersUtils.wrapMessageNumberForUsers(users, messageNumber);
  const user = users[wrappedMessageNumber - 1];

  utils.checkUserIsDefined(user);

  const token = await mailCatcherUtils.getPasswordResetToken(wrappedMessageNumber);

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

export function teardown() {
  mailCatcherUtils.clearMessages();
}
