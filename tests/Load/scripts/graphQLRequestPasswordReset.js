import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'graphQLRequestPasswordReset';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function requestPasswordReset() {
  const email = utils.generateRandomEmail();
  const mutationName = 'requestPasswordReset';

  const mutation = `
     mutation {
        ${mutationName}(
            input: {
                email: "${email}"
            }
        ) {
            user {
                id
                email
            }
        }
     }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getJsonHeader()
  );

  utils.checkResponse(response, 'password reset request successful', res => {
    const body = JSON.parse(res.body);
    return res.status === 200 && !body.errors;
  });
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}
