import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../utils/graphQLAuthFlowUtils.js';
import TotpUtils from '../utils/totpUtils.js';

const scenarioName = 'graphQLConfirmTwoFactor';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const graphQLAuthFlowUtils = new GraphQLAuthFlowUtils(utils);
const totpUtils = new TotpUtils();

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users,
  };
}

export const options = scenarioUtils.getOptions();

function confirmWithCandidateCodes(accessToken, secret) {
  const candidateCodes = totpUtils.generateCandidateCodes(secret);
  let lastAttempt = graphQLAuthFlowUtils.confirmTwoFactor(accessToken, candidateCodes[0]);

  if (lastAttempt.response.status === 200) {
    return lastAttempt;
  }

  for (let index = 1; index < candidateCodes.length; index += 1) {
    lastAttempt = graphQLAuthFlowUtils.confirmTwoFactor(accessToken, candidateCodes[index]);
    if (lastAttempt.response.status === 200) {
      return lastAttempt;
    }
  }

  return lastAttempt;
}

export default function graphQLConfirmTwoFactor(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const signInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    signInResult.response,
    'sign-in for graphQL confirm 2fa is status 200',
    res => res.status === 200
  );

  const accessToken = signInResult.body?.data?.signInUser?.user?.accessToken;
  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    utils.checkResponse(
      signInResult.response,
      'sign-in returns access token for graphQL confirm 2fa',
      () => false
    );
    return;
  }

  const setupResult = graphQLAuthFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(
    setupResult.response,
    'setup 2fa before graphQL confirm 2fa is status 200',
    res => res.status === 200
  );

  const secret = setupResult.body?.data?.setupTwoFactorUser?.user?.secret;
  if (typeof secret !== 'string' || secret.length === 0) {
    utils.checkResponse(
      setupResult.response,
      'setup 2fa returns secret for graphQL confirm 2fa',
      () => false
    );
    return;
  }

  const confirmResult = confirmWithCandidateCodes(accessToken, secret);
  utils.checkResponse(
    confirmResult.response,
    'graphQL confirm 2fa is status 200',
    res => res.status === 200
  );
  utils.checkResponse(confirmResult.response, 'graphQL confirm 2fa returns recovery codes', () =>
    Array.isArray(confirmResult.body?.data?.confirmTwoFactorUser?.user?.recoveryCodes)
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
