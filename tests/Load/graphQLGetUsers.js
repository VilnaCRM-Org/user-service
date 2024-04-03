import http from 'k6/http';
import {ScenarioUtils} from "./utils/scenarioUtils.js";
import {check} from 'k6';
import {InsertUsersUtils} from "./utils/insertUsersUtils.js";
import {Utils} from "./utils/utils.js";

const scenarioName = 'graphqlGetUsers';
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
    getUsers(data.users.length);
}

function getUsers(usersAmount) {
    let num = utils.getRandomNumber(1, usersAmount);

    const query = `
        query{
        users(first: ${num}){
            edges{
                node{
                    id
                }
            }
        }
    }`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: query}),
        utils.getJsonHeader(),
    );

    check(res, {
        'users returned': (r) =>
            JSON.parse(r.body).data.users.edges.length === num,
    });
}
