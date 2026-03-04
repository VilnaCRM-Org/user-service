import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import AuthFlowUtils from '../utils/authFlowUtils.js';

const scenarioName = 'setupTwoFactor';

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

export default function setupTwoFactor(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = authFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(signInResult.response, 'sign-in for setup 2fa is status 200', res => res.status === 200);

  const accessToken = signInResult.body?.access_token;
  utils.checkResponse(
    signInResult.response,
    'sign-in returns access token for setup 2fa',
    () => typeof accessToken === 'string' && accessToken.length > 0
  );

  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    return;
  }

  const setupResult = authFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(setupResult.response, 'setup 2fa is status 200', res => res.status === 200);
  utils.checkResponse(
    setupResult.response,
    'setup 2fa returns secret and otpauth uri',
    () => typeof setupResult.body?.secret === 'string' && typeof setupResult.body?.otpauth_uri === 'string'
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
