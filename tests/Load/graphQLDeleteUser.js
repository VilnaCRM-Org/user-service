import http from 'k6/http';
import { ScenarioUtils } from "./scenarioUtils.js";
import { check } from 'k6';
import {Utils} from "./utils.js";
import {InsertUsersUtils} from "./insertUsersUtils.js";

const scenarioName = 'GRAPHQL_DELETE_USER';
const scenarioUtils = new ScenarioUtils(scenarioName);
const insertUsersUtils = new InsertUsersUtils(scenarioName);
const utils = new Utils();

export function setup() {
    return {
        users: insertUsersUtils.prepareUsers()
    }
}

export const options= scenarioUtils.getOptions();

export default function (data) {
    deleteUser(data.users[utils.getRandomNumber(0, data.users.length - 1)]);
}

function deleteUser(user) {
    const id = user.id;

    const mutation = `
     mutation{
        deleteUser(input:{
            id: "${id}"
        }){
            user{
                id
            }
        }
    }`;

    const res = http.post(
        utils.getBaseGraphQLUrl(),
        JSON.stringify({query: mutation}),
        utils.getJsonHeader(),
    );

    check(res, {
        'is status 200': (r) => r.status === 200,
    });
}
