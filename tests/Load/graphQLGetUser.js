import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import exec from 'k6/execution';

const scenarioName = 'graphqlGetUser';
const utils = new Utils();
const scenarioUtils = new ScenarioUtils(utils, scenarioName);
const insertUsersUtils = new InsertUsersUtils(utils, scenarioName);

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function (data) {
    getUser(data.users[exec.instance.iterationsInterrupted + exec.instance.iterationsCompleted]);
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
