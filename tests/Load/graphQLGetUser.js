import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import {check} from 'k6';
import {InsertUsersUtils} from "./insertUsersUtils.js";
import {Utils} from "./utils.js";

const scenarioName = 'GRAPHQL_GET_USER';
const scenarioUtils = new ScenarioUtils(scenarioName);
const insertUsersUtils = new InsertUsersUtils(scenarioName);
const utils = new Utils();

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    getUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function getUser(user) {
    const id = user.id;

    const query = `
      query{
  user(id: "/api/users/${id}"){
        id
        email
    }
    }`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: query}),
        utils.getJsonHeader(),
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
