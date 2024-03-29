import http from 'k6/http';
import {ScenarioUtils} from "./scenarioUtils.js";
import { check } from 'k6';
import {InsertUsersUtils} from "./insertUsersUtils.js";
import {Utils} from "./utils.js";

const scenarioName = 'GRAPHQL_GET_USERS';
const scenarioUtils = new ScenarioUtils(scenarioName);
const insertUsersUtils = new InsertUsersUtils(scenarioName);
const utils = new Utils();

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options = scenarioUtils.getOptions();

export default function () {
    getUsers();
}

function getUsers() {
    let num = utils.getRandomNumber(1, 10000);

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
        'is status 200': (r) => r.status === 200,
    });
}
