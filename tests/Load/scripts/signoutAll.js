import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import AuthFlowUtils from '../utils/authFlowUtils.js';

const scenarioName = 'signoutAll';

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

export default function signOutAllSessions(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = authFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(signInResult.response, 'sign-in for signout all is status 200', res => res.status === 200);

  const accessToken = signInResult.body?.access_token;
  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    utils.checkResponse(signInResult.response, 'sign-in returns access token for signout all', () => false);
    return;
  }

  const signOutAllResult = authFlowUtils.signOutAll(accessToken);
  utils.checkResponse(signOutAllResult.response, 'signout all is status 204', res => res.status === 204);
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
