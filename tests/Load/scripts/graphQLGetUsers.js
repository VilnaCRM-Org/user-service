import http from 'k6/http';
import InsertUsersUtils from '../utils/insertUsersUtils.js';
import ScenarioUtils from '../utils/scenarioUtils.js';
import Utils from '../utils/utils.js';
import MailCatcherUtils from '../utils/mailCatcherUtils.js';

const scenarioName = 'graphQLGetUsers';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);
const mailCatcherUtils = new MailCatcherUtils(utils);

const usersToGetInOneRequest = utils.getConfig().endpoints[scenarioName].usersToGetInOneRequest;
const users = insertUsersUtils.loadInsertedUsers();

export function setup() {
  return {
    users: users,
  };
}

export const options = scenarioUtils.getOptions();

export default function getUsers() {
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
    utils.getJsonHeader(),
  );

  utils.checkResponse(
    response,
    'users returned',
    res => JSON.parse(res.body).data.users.edges.length === usersToGetInOneRequest,
  );
}

export function teardown() {
  mailCatcherUtils.clearMessages();
}
