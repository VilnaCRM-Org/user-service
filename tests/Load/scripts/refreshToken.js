import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import AuthFlowUtils from '../utils/authFlowUtils.js';

const scenarioName = 'refreshToken';

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

export default function refreshAccessToken(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = authFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(signInResult.response, 'sign-in for refresh token is status 200', res => res.status === 200);

  const refreshToken = signInResult.body?.refresh_token;
  utils.checkResponse(signInResult.response, 'sign-in returns refresh token for refresh flow', () => typeof refreshToken === 'string' && refreshToken.length > 0);

  if (typeof refreshToken !== 'string' || refreshToken.length === 0) {
    return;
  }

  const refreshResult = authFlowUtils.refreshToken(refreshToken);
  utils.checkResponse(refreshResult.response, 'token refresh is status 200', res => res.status === 200);
  utils.checkResponse(
    refreshResult.response,
    'token refresh returns access and refresh token',
    () => typeof refreshResult.body?.access_token === 'string' && typeof refreshResult.body?.refresh_token === 'string'
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
