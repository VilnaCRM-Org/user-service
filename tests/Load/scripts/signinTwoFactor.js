import counter from 'k6/x/counter';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import AuthFlowUtils from '../utils/authFlowUtils.js';
import TotpUtils from '../utils/totpUtils.js';

const scenarioName = 'signinTwoFactor';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const authFlowUtils = new AuthFlowUtils(utils);
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
  let lastAttempt = authFlowUtils.confirmTwoFactor(accessToken, candidateCodes[0]);

  if (lastAttempt.response.status === 200) {
    return lastAttempt;
  }

  for (let index = 1; index < candidateCodes.length; index += 1) {
    lastAttempt = authFlowUtils.confirmTwoFactor(accessToken, candidateCodes[index]);
    if (lastAttempt.response.status === 200) {
      return lastAttempt;
    }
  }

  return lastAttempt;
}

export default function completeSignInTwoFactor(data) {
  const user = data.users[counter.up() % data.users.length];
  utils.checkUserIsDefined(user);

  const firstSignInResult = authFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    firstSignInResult.response,
    'first sign-in for signin 2fa is status 200',
    res => res.status === 200
  );

  const accessToken = firstSignInResult.body?.access_token;
  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    utils.checkResponse(
      firstSignInResult.response,
      'first sign-in returns access token for signin 2fa',
      () => false
    );
    return;
  }

  const setupResult = authFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(
    setupResult.response,
    'setup 2fa before signin 2fa is status 200',
    res => res.status === 200
  );

  const secret = setupResult.body?.secret;
  if (typeof secret !== 'string' || secret.length === 0) {
    utils.checkResponse(
      setupResult.response,
      'setup 2fa returns secret for signin 2fa',
      () => false
    );
    return;
  }

  const confirmResult = confirmWithCandidateCodes(accessToken, secret);
  utils.checkResponse(
    confirmResult.response,
    'confirm 2fa before signin 2fa is status 200',
    res => res.status === 200
  );

  const recoveryCode = confirmResult.body?.recovery_codes?.[0];
  if (typeof recoveryCode !== 'string' || recoveryCode.length === 0) {
    utils.checkResponse(
      confirmResult.response,
      'confirm 2fa returns recovery code for signin 2fa',
      () => false
    );
    return;
  }

  const secondSignInResult = authFlowUtils.signIn(user.email, user.password);
  utils.checkResponse(
    secondSignInResult.response,
    'second sign-in requiring 2fa is status 200',
    res => res.status === 200
  );

  const pendingSessionId = secondSignInResult.body?.pending_session_id;
  if (typeof pendingSessionId !== 'string' || pendingSessionId.length === 0) {
    utils.checkResponse(
      secondSignInResult.response,
      'second sign-in returns pending session id',
      () => false
    );
    return;
  }

  const completeResult = authFlowUtils.completeTwoFactor(pendingSessionId, recoveryCode);
  utils.checkResponse(
    completeResult.response,
    'signin 2fa completion is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    completeResult.response,
    'signin 2fa completion returns access and refresh token',
    () =>
      typeof completeResult.body?.access_token === 'string' &&
      typeof completeResult.body?.refresh_token === 'string'
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
