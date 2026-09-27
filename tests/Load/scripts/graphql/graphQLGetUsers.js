import http from 'k6/http';

import InsertUsersUtils from '../../utils/insertUsersUtils.js';
import MailCatcherUtils from '../../utils/mailCatcherUtils.js';
import ScenarioUtils from '../../utils/scenarioUtils.js';
import Utils from '../../utils/utils.js';

const scenarioName = 'graphQLGetUsers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

const usersToGetInOneRequest = utils.getConfig().endpoints[scenarioName].usersToGetInOneRequest;
const users = insertUsersUtils.loadInsertedUsers();

export const options = scenarioUtils.getOptions();

export default function getUsers() {
  const user = users[utils.getRandomNumber(0, users.length - 1)];
  utils.checkUserIsDefined(user);

  const query = `
        query{
            users(first: ${usersToGetInOneRequest}){
                edges{
                    node{
                        id
                    }
                }
            }
        }`;

  const response = http.post(
    utils.getBaseGraphQLUrl(),
    JSON.stringify({ query: query }),
    utils.getJsonHeaderWithAuth(user.accessToken)
  );

  // The users query is scoped to the authenticated caller (object-level
  // authorization), so it returns only the caller's own record regardless of
  // how many records are requested via `first`.
  utils.checkResponse(
    response,
    'own user returned',
    res => JSON.parse(res.body).data.users.edges.length === 1
  );
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
