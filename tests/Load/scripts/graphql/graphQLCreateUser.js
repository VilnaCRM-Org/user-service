import http from 'k6/http';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';

const scenarioName = 'graphQLCreateUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export function setup() {
  const authUser = utils.generateUser();
  const registerResponse = utils.registerUser(authUser);
  utils.checkResponse(registerResponse, 'is status 201', res => res.status === 201);

  const signInPayload = JSON.stringify({
    email: authUser.email,
    password: authUser.password,
    rememberMe: false,
  });
  const signInResponse = http.post(
    `${utils.getBaseUrl()}/signin`,
    signInPayload,
    utils.getJsonHeader()
  );
  if (signInResponse.status !== 200) {
    throw new Error(
      `Failed to authenticate GraphQL bootstrap user. Status: ${signInResponse.status}`
    );
  }

  const body = JSON.parse(signInResponse.body);
  if (typeof body.access_token !== 'string' || body.access_token === '') {
    throw new Error('GraphQL bootstrap sign-in response does not contain access_token');
  }

  return { accessToken: body.access_token };
}

export default function createUser(data) {
  const user = utils.generateUser();
  const mutationName = 'createUser';

  const mutation = `
     mutation {
        ${mutationName}(
            input: {
                email: "${user.email}"
                initials: "${user.initials}"
                password: "${user.password}"
            }
        ) {
            user {
                email
            }
        }
     }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getJsonHeaderWithAuth(data.accessToken)
  );

  utils.checkResponse(
    response,
    'created user returned',
    res => JSON.parse(res.body).data[mutationName].user.email === `${user.email}`
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
