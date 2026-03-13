import counter from 'k6/x/counter';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../../utils/graphQLAuthFlowUtils.js';

const scenarioName = 'graphQLSetupTwoFactor';

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

export default function graphQLSetupTwoFactor(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    signInResult.response,
    'sign-in for graphQL setup 2fa is status 200',
    res => res.status === 200
  );

  const accessToken = signInResult.body?.data?.signInUser?.user?.accessToken;
  utils.checkResponse(
    signInResult.response,
    'sign-in returns access token for graphQL setup 2fa',
    () => typeof accessToken === 'string' && accessToken.length > 0
  );

  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    return;
  }

  const setupResult = graphQLAuthFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(
    setupResult.response,
    'graphQL setup 2fa is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    setupResult.response,
    'graphQL setup 2fa returns secret and otpauthUri',
    () =>
      typeof setupResult.body?.data?.setupTwoFactorUser?.user?.secret === 'string' &&
      typeof setupResult.body?.data?.setupTwoFactorUser?.user?.otpauthUri === 'string'
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
