import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";
import counter from "k6/x/counter"

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
    getUser(data.users[counter.up()]);
}

function getUser(user) {
    utils.checkUserIsDefined(user);

    const id = utils.getGraphQLIdPrefix() + user.id;

    const query = `
      query{
  user(id: "${id}"){
        id
    }
    }`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: query}),
        utils.getJsonHeader(),
    );

    check(res, {
        'user returned': (r) =>
            JSON.parse(r.body).data.user.id === `${id}`,
    });
}
