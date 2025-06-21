import http from 'k6/http';
import counter from 'k6/x/counter';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import InsertUsersUtils from '../utils/insertUsersUtils.js';

const scenarioName = 'graphQLConfirmUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users: users,
  };
}

export const options = scenarioUtils.getOptions();

export default async function confirmUser() {
  const num = counter.up();
  const mutationName = 'confirmUser';

  const token = await mailCatcherUtils.getConfirmationToken(num);

  const mutation = `
     mutation {
        ${mutationName}(input: { token: "${token}" }) {
            user {
               id
            }
        }
     }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: mutation }),
    utils.getJsonHeader(),
  );

  utils.checkResponse(
    response,
    'confirmed user returned',
    res => JSON.parse(res.body).data[mutationName].user.id !== undefined,
  );
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
