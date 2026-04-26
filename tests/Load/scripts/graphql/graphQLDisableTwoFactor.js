import exec from 'k6/execution';

import GraphQLAuthFlowUtils from '../../utils/graphQLAuthFlowUtils.js';
import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import TotpUtils from '../../utils/totpUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'graphQLDisableTwoFactor';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const graphQLAuthFlowUtils = new GraphQLAuthFlowUtils(utils);
const totpUtils = new TotpUtils();

const users = insertUsersUtils.loadInsertedUsers();

export const options = scenarioUtils.getOptions();

function getUser() {
  const messageNumber = insertUsersUtils.getMessageNumberForProfile(
    exec.scenario.name,
    exec.scenario.iterationInTest
  );

  return users[(messageNumber - 1) % users.length];
}

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

export default function graphQLDisableTwoFactor() {
  const user = getUser();
  utils.checkUserIsDefined(user);

  let accessToken = user.accessToken;

  if (typeof accessToken !== 'string' || accessToken.length === 0) {
    const signInResult = graphQLAuthFlowUtils.signIn(user.email, user.password);
    utils.checkResponse(
      signInResult.response,
      'sign-in for graphQL disable 2fa is status 200',
      res => res.status === 200
    );

    accessToken = signInResult.body?.data?.signInUser?.user?.accessToken;
    if (typeof accessToken !== 'string' || accessToken.length === 0) {
      utils.checkResponse(
        signInResult.response,
        'sign-in returns access token for graphQL disable 2fa',
        () => false
      );
      return;
    }
  }

  const setupResult = graphQLAuthFlowUtils.setupTwoFactor(accessToken);
  utils.checkResponse(
    setupResult.response,
    'setup 2fa before graphQL disable 2fa is status 200',
    res => res.status === 200
  );

  const secret = setupResult.body?.data?.setupTwoFactorUser?.user?.secret;
  if (typeof secret !== 'string' || secret.length === 0) {
    utils.checkResponse(
      setupResult.response,
      'setup 2fa returns secret for graphQL disable 2fa',
      () => false
    );
    return;
  }

  const confirmResult = confirmWithCandidateCodes(accessToken, secret);
  utils.checkResponse(
    confirmResult.response,
    'confirm 2fa before graphQL disable 2fa is status 200',
    res => res.status === 200
  );

  const recoveryCodes = confirmResult.body?.data?.confirmTwoFactorUser?.user?.recoveryCodes;
  if (!Array.isArray(recoveryCodes) || recoveryCodes.length === 0) {
    utils.checkResponse(
      confirmResult.response,
      'confirm 2fa returns recovery codes for graphQL disable 2fa',
      () => false
    );
    return;
  }

  const disableResult = graphQLAuthFlowUtils.disableTwoFactor(accessToken, recoveryCodes[0]);
  utils.checkResponse(
    disableResult.response,
    'graphQL disable 2fa is status 200',
    res => res.status === 200
  );
  utils.checkResponse(
    disableResult.response,
    'graphQL disable 2fa returns success',
    () => disableResult.body?.data?.disableTwoFactorUser?.user?.success === true
  );
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
