import http from 'k6/http';
import counter from 'k6/x/counter';

import InsertUsersUtils from './utils/insertUsersUtils.js';
import ScenarioUtils from './utils/scenarioUtils.js';
import Utils from './utils/utils.js';


const scenarioName = 'graphqlGetUser';

const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

const users = insertUsersUtils.getInsertedUsers()

export function setup() {
    return {
        users: users
    };
}

export const options = scenarioUtils.getOptions();

export default function getUser(data) {
    const user = data.users[counter.up()];
    utils.checkUserIsDefined(user);

    const id = utils.getGraphQLIdPrefix() + user.id;

    const query = `
      query{
          user(id: "${id}"){
              id
          }
      }`;

    const response = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: query}),
        utils.getJsonHeader(),
    );

    utils.checkResponse(
        response,
        'user returned',
        (res) => JSON.parse(res.body).data.user.id === `${id}`
    );
}
