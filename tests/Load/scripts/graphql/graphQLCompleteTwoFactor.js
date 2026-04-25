import exec from 'k6/execution';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import GraphQLAuthFlowUtils from '../../utils/graphQLAuthFlowUtils.js';
import TotpUtils from '../../utils/totpUtils.js';

const scenarioName = 'graphQLCompleteTwoFactor';

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

  if (graphQLAuthFlowUtils.hasConfirmedTwoFactor(lastAttempt)) {
    return lastAttempt;
  }

  for (let index = 1; index < candidateCodes.length; index += 1) {
    lastAttempt = graphQLAuthFlowUtils.confirmTwoFactor(accessToken, candidateCodes[index]);
    if (graphQLAuthFlowUtils.hasConfirmedTwoFactor(lastAttempt)) {
      return lastAttempt;
    }
  }

  return lastAttempt;
}

export default function graphQLCompleteTwoFactor(data) {
  const user = data.users[exec.scenario.iterationInTest % data.users.length];
  utils.checkUserIsDefined(user);

  const firstSignInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    firstSignInResult.response,
    'first sign-in for graphQL complete 2fa is status 200',
    res => res.status === 200
  );

  const accessToken = firstSignInResult.body?.data?.signInUser?.user?.accessToken;
  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    utils.checkResponse(
      firstSignInResult.response,
      'first sign-in returns access token for graphQL complete 2fa',
      () => false
    );
    return;
  }

  const setupResult = graphQLAuthFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(
    setupResult.response,
    'setup 2fa before graphQL complete 2fa is status 200',
    res => res.status === 200
  );

  const secret = setupResult.body?.data?.setupTwoFactorUser?.user?.secret;
  if (typeof secret !== 'string' || secret.length === 0) {
    utils.checkResponse(
      setupResult.response,
      'setup 2fa returns secret for graphQL complete 2fa',
      () => false
    );
    return;
  }

  const confirmResult = confirmWithCandidateCodes(accessToken, secret);
  utils.checkResponse(
    confirmResult.response,
    'confirm 2fa before graphQL complete 2fa is status 200',
    res => res.status === 200
  );

  const recoveryCode = confirmResult.body?.data?.confirmTwoFactorUser?.user?.recoveryCodes?.[0];
  if (typeof recoveryCode !== 'string' || recoveryCode.length === 0) {
    utils.checkResponse(
      confirmResult.response,
      'confirm 2fa returns recovery code for graphQL complete 2fa',
      () => false
    );
    return;
  }

  const secondSignInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    secondSignInResult.response,
    'second sign-in requiring 2fa for graphQL complete 2fa is status 200',
    res => res.status === 200
  );

  const pendingSessionId = secondSignInResult.body?.data?.signInUser?.user?.pendingSessionId;
  if (typeof pendingSessionId !== 'string' || pendingSessionId.length === 0) {
    utils.checkResponse(
      secondSignInResult.response,
      'second sign-in returns pending session id for graphQL complete 2fa',
      () => false
    );
    return;
  }

  const completeResult = graphQLAuthFlowUtils.completeTwoFactor(pendingSessionId, recoveryCode);
  utils.checkResponse(
    completeResult.response,
    'graphQL complete 2fa is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    completeResult.response,
    'graphQL complete 2fa returns access and refresh token',
    () =>
      typeof completeResult.body?.data?.completeTwoFactorUser?.user?.accessToken === 'string' &&
      typeof completeResult.body?.data?.completeTwoFactorUser?.user?.refreshToken === 'string'
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
