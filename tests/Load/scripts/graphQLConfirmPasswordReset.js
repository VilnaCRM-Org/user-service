import http from 'k6/http';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'graphQLConfirmPasswordReset';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

export const options = scenarioUtils.getOptions();

export default function confirmPasswordReset() {
  // Generate test data
  const token = utils.generateToken();
  const newPassword = utils.generatePassword();
  const mutationName = 'confirmPasswordReset';

  const mutation = `
     mutation {
        ${mutationName}(
            input: {
                token: "${token}"
                newPassword: "${newPassword}"
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

  utils.checkResponse(
    response,
    'confirm password reset response received',
    res => {
      const body = JSON.parse(res.body);
      // Expecting errors for invalid tokens in load test, which is normal
      return res.status === 200;
    }
  );
}

export function teardown(data) {
  mailCatcherUtils.clearMessages();
}