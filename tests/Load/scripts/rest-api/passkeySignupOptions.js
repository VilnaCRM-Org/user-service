import http from 'k6/http';

import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'passkeySignupOptions';
const runId = Date.now();

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);

export const options = scenarioUtils.getOptions();

export default function passkeySignupOptions() {
  const payload = JSON.stringify({
    email: `passkey-signup-${runId}-${iterationId()}@example.test`,
    initials: 'PasskeyLoad',
    displayName: 'Passkey Load',
  });

  const response = http.post(
    `${utils.getBaseUrl()}/passkeys/signup/options`,
    payload,
    utils.getJsonHeader()
  );
  const body = parseJson(response);

  utils.checkResponse(response, 'passkey signup options is status 200', res => res.status === 200);
  utils.checkResponse(
    response,
    'passkey signup options returns challenge',
    () => typeof body?.challenge_id === 'string' && body.challenge_id.length > 0
  );
  utils.checkResponse(
    response,
    'passkey signup options returns browser public key JSON',
    () =>
      typeof body?.public_key?.challenge === 'string' &&
      body.public_key.authenticatorSelection?.userVerification === 'required' &&
      body.public_key.authenticatorSelection?.residentKey === 'required'
  );
}

function parseJson(response) {
  try {
    return response.json();
  } catch {
    return null;
  }
}

function iterationId() {
  const vuId = typeof globalThis.__VU === 'number' ? globalThis.__VU : 1;
  const iteration = typeof globalThis.__ITER === 'number' ? globalThis.__ITER : 0;

  return `${vuId}-${iteration}`;
}
