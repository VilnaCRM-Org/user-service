import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../../utils/graphQLAuthFlowUtils.js';

const scenarioName = 'graphQLRefreshToken';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const graphQLAuthFlowUtils = new GraphQLAuthFlowUtils(utils);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users,
  };
}

export const options = scenarioUtils.getOptions();

export default function graphQLRefreshToken(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    signInResult.response,
    'sign-in for graphQL refresh token is status 200',
    res => res.status === 200
  );

  const refreshToken = signInResult.body?.data?.signInUser?.user?.refreshToken;
  utils.checkResponse(
    signInResult.response,
    'sign-in returns refresh token for graphQL refresh flow',
    () => typeof refreshToken === 'string' && refreshToken.length > 0
  );

  if (typeof refreshToken !== 'string' || refreshToken.length === 0) {
    return;
  }

  const refreshResult = graphQLAuthFlowUtils.refreshToken(refreshToken);
  utils.checkResponse(
    refreshResult.response,
    'graphQL token refresh is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    refreshResult.response,
    'graphQL token refresh returns access and refresh token',
    () =>
      typeof refreshResult.body?.data?.refreshTokenUser?.user?.accessToken === 'string' &&
      typeof refreshResult.body?.data?.refreshTokenUser?.user?.refreshToken === 'string'
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
